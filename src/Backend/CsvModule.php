<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Backend;

use Contao\Backend;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;

/**
 * Backend-Modul "Import" (Gruppe Synapsis, do=synapsis_csv).
 *
 * Importiert derzeit einen phpBB-CSV-Export in eine Synapsis-Kategorie. Der
 * Ablauf ist zweistufig: zuerst die CSV-Dateien hochladen, dann auswaehlen,
 * welche Foren uebernommen werden. Die Formatauswahl ist so angelegt, dass
 * spaeter weitere Importquellen ergaenzt werden koennen, ohne einen weiteren
 * Menuepunkt anzulegen.
 *
 * phpBB-Benutzer sind im Zielsystem fremd und werden als Gast mit ihrem Namen
 * abgelegt. Private Nachrichten und Datei-Anhaenge werden nicht uebernommen.
 */
class CsvModule extends Backend
{
    /**
     * Hochzuladende phpBB-Tabellen: Schluessel => [Pflicht?, Beschreibung].
     *
     * @var array<string, array{0: bool, 1: string}>
     */
    private const FILES = [
        'forums' => [true, 'phpbb_forums – die Foren (nur echte Foren werden übernommen, keine phpBB-Kategorien).'],
        'topics' => [true, 'phpbb_topics – die Themen.'],
        'posts' => [true, 'phpbb_posts – die Beiträge.'],
        'users' => [false, 'phpbb_users – optional, liefert die Anzeigenamen registrierter Verfasser.'],
        'poll_options' => [false, 'phpbb_poll_options – optional, für die Übernahme von Umfragen.'],
    ];

    /**
     * @param mixed $dc Data-Container (hier ungenutzt)
     */
    public function __construct($dc = null)
    {
        parent::__construct();
    }

    /**
     * Erzeugt die Backend-Seite und verarbeitet die beiden Import-Schritte.
     */
    public function generate(): string
    {
        $connection = System::getContainer()->get('database_connection');

        $submit = Input::post('FORM_SUBMIT');

        if ('synapsis_phpbb_upload' === $submit) {
            return $this->handleUpload($connection);
        }

        if ('synapsis_phpbb_import' === $submit) {
            return $this->handleImport($connection);
        }

        return $this->renderUploadForm($connection, '');
    }

    /**
     * Schritt 1: Dateien entgegennehmen, zwischenspeichern und die Foren-Auswahl
     * anzeigen.
     *
     * @param \Doctrine\DBAL\Connection $connection
     */
    private function handleUpload($connection): string
    {
        $target = (int) Input::post('target');

        if (!$this->isCategory($connection, $target)) {
            return $this->renderUploadForm($connection, $this->error('Bitte eine Ziel-Kategorie auswählen.'));
        }

        // Eventuelle Reste einer frueheren Sitzung aufraeumen.
        $this->cleanupStored();

        $dir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'synapsis_import_'.bin2hex(random_bytes(8));

        if (!mkdir($dir, 0700, true) && !is_dir($dir)) {
            return $this->renderUploadForm($connection, $this->error('Temporäres Verzeichnis konnte nicht angelegt werden.'));
        }

        foreach (self::FILES as $key => [$required, $desc]) {
            $field = 'file_'.$key;

            if (!empty($_FILES[$field]['tmp_name']) && is_uploaded_file($_FILES[$field]['tmp_name'])) {
                move_uploaded_file($_FILES[$field]['tmp_name'], $dir.\DIRECTORY_SEPARATOR.$key.'.csv');
            } elseif ($required) {
                $this->removeDir($dir);

                return $this->renderUploadForm($connection, $this->error('Bitte die Datei „'.$key.'" auswählen ('.$desc.').'));
            }
        }

        $forums = (new PhpbbImporter($connection))->listForums((string) @file_get_contents($dir.\DIRECTORY_SEPARATOR.'forums.csv'));

        if ([] === $forums) {
            $this->removeDir($dir);

            return $this->renderUploadForm($connection, $this->error('In der Foren-Datei wurden keine importierbaren Foren gefunden.'));
        }

        $this->session()->set('synapsis_import', ['dir' => $dir, 'target' => $target, 'time' => time()]);

        return $this->renderSelectForm($connection, $target, $forums);
    }

    /**
     * Schritt 2: ausgewaehlte Foren importieren.
     *
     * @param \Doctrine\DBAL\Connection $connection
     */
    private function handleImport($connection): string
    {
        $data = $this->session()->get('synapsis_import');

        if (!\is_array($data) || empty($data['dir']) || !is_dir($data['dir'])) {
            return $this->renderUploadForm($connection, $this->error('Die hochgeladenen Dateien sind nicht mehr verfügbar (Sitzung abgelaufen). Bitte erneut hochladen.'));
        }

        $target = (int) $data['target'];
        $selected = array_values(array_filter(array_map('intval', (array) Input::post('forums'))));

        if ([] === $selected) {
            $forums = (new PhpbbImporter($connection))->listForums((string) @file_get_contents($data['dir'].\DIRECTORY_SEPARATOR.'forums.csv'));

            return $this->renderSelectForm($connection, $target, $forums, $this->error('Bitte mindestens ein Forum auswählen.'));
        }

        $csv = [];

        foreach (array_keys(self::FILES) as $key) {
            $file = $data['dir'].\DIRECTORY_SEPARATOR.$key.'.csv';

            if (is_file($file)) {
                $csv[$key] = (string) file_get_contents($file);
            }
        }

        try {
            $stats = (new PhpbbImporter($connection))->import($csv, $target, $selected);
        } catch (\Throwable $e) {
            return $this->renderUploadForm($connection, $this->error(StringUtil::specialchars($e->getMessage())));
        } finally {
            $this->cleanupStored();
        }

        $message = '<p class="tl_confirm">phpBB-Import abgeschlossen: '
            .$stats['forums'].' Foren, '
            .$stats['topics'].' Themen, '
            .$stats['posts'].' Beiträge, '
            .$stats['polls'].' Umfragen ('.$stats['votes'].' Stimmen) übernommen. '
            .$stats['skipped'].' Beiträge übersprungen (nicht freigegeben oder ohne Thema).</p>';

        return $this->renderUploadForm($connection, $message);
    }

    /**
     * Rendert Schritt 1 (Format, Ziel-Kategorie, Datei-Uploads).
     *
     * @param \Doctrine\DBAL\Connection $connection
     */
    private function renderUploadForm($connection, string $message): string
    {
        $token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        $html = '<div id="tl_buttons"></div>';
        $html .= '<h2 class="sub_headline">Synapsis: Import</h2>';
        $html .= $message;

        $targetOptions = $this->categoryOptions($connection);

        if ('' === $targetOptions) {
            $html .= '<p class="tl_info">Es ist noch keine Kategorie vorhanden. Legen Sie zuerst im Forum-Modul einen Startpunkt und darin eine Kategorie an – der Import erfolgt immer in eine Kategorie.</p>';

            return $html;
        }

        $fileFields = '';

        foreach (self::FILES as $key => [$required, $desc]) {
            $label = 'phpbb_'.$key.'.csv'.($required ? ' *' : ' (optional)');
            $fileFields .= '<div class="widget"><h3><label for="file_'.$key.'">'.$label.'</label></h3>'
                .'<input type="file" name="file_'.$key.'" id="file_'.$key.'" class="tl_upload_field" accept=".csv">'
                .'<p class="tl_help tl_tip">'.StringUtil::specialchars($desc).'</p></div>';
        }

        $html .= '<form method="post" enctype="multipart/form-data" class="tl_form">'
            .'<div class="tl_formbody_edit">'
            .'<input type="hidden" name="FORM_SUBMIT" value="synapsis_phpbb_upload">'
            .'<input type="hidden" name="REQUEST_TOKEN" value="'.$token.'">'
            .'<fieldset class="tl_tbox"><legend>Schritt 1: Dateien hochladen</legend>'
            .'<div class="widget"><h3><label for="format">Format</label></h3>'
            .'<select name="format" id="format" class="tl_select"><option value="phpbb">phpBB (CSV-Export)</option></select>'
            .'<p class="tl_help tl_tip">Derzeit wird der CSV-Export von phpBB unterstützt.</p></div>'
            .'<div class="widget"><h3><label for="target">Ziel-Kategorie</label></h3>'
            .'<select name="target" id="target" class="tl_select">'.$targetOptions.'</select>'
            .'<p class="tl_help tl_tip">Startpunkt › Kategorie. Die phpBB-Foren werden als Foren unter dieser Kategorie angelegt.</p></div>'
            .$fileFields
            .'</fieldset></div>'
            .'<div class="tl_formbody_submit"><div class="tl_submit_container">'
            .'<button type="submit" class="tl_submit">Weiter zur Forenauswahl</button>'
            .'</div></div></form>';

        return $html;
    }

    /**
     * Rendert Schritt 2 (Auswahl der zu importierenden Foren).
     *
     * @param \Doctrine\DBAL\Connection                 $connection
     * @param array<int, array{id:int, name:string}>    $forums
     */
    private function renderSelectForm($connection, int $target, array $forums, string $message = ''): string
    {
        $token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
        $targetTitle = (string) $connection->fetchOne("SELECT title FROM tl_synapsis_forum WHERE id = ?", [$target]);

        $html = '<div id="tl_buttons"></div>';
        $html .= '<h2 class="sub_headline">Synapsis: Import – Foren auswählen</h2>';
        $html .= $message;
        $html .= '<p class="tl_info">Ziel-Kategorie: <strong>'.StringUtil::specialchars($targetTitle).'</strong>. Wählen Sie die Foren, die importiert werden sollen.</p>';

        $checks = '';

        foreach ($forums as $forum) {
            $checks .= '<span class="tl_checkbox_single_container" style="display:block;margin:.3em 0">'
                .'<input type="checkbox" name="forums[]" id="forum_'.$forum['id'].'" class="tl_checkbox" value="'.$forum['id'].'" checked> '
                .'<label for="forum_'.$forum['id'].'">'.StringUtil::specialchars($forum['name']).'</label></span>';
        }

        $html .= '<form method="post" class="tl_form">'
            .'<div class="tl_formbody_edit">'
            .'<input type="hidden" name="FORM_SUBMIT" value="synapsis_phpbb_import">'
            .'<input type="hidden" name="REQUEST_TOKEN" value="'.$token.'">'
            .'<fieldset class="tl_tbox"><legend>Schritt 2: Foren auswählen</legend>'
            .'<div class="widget">'.$checks.'</div>'
            .'</fieldset></div>'
            .'<div class="tl_formbody_submit"><div class="tl_submit_container">'
            .'<button type="submit" class="tl_submit">Ausgewählte Foren importieren</button>'
            .'</div></div></form>';

        return $html;
    }

    /**
     * Liefert die Kategorien als <option>-Liste, jeweils mit ihrem Startpunkt
     * ("Startpunkt › Kategorie"), damit bei gleichnamigen Kategorien der richtige
     * Startpunkt erkennbar ist.
     *
     * @param \Doctrine\DBAL\Connection $connection
     */
    private function categoryOptions($connection): string
    {
        $rows = $connection->fetchAllAssociative(
            "SELECT c.id, c.title AS ctitle, r.title AS rtitle
             FROM tl_synapsis_forum c
             LEFT JOIN tl_synapsis_forum r ON r.id = c.pid
             WHERE c.type = 'category'
             ORDER BY r.title, c.sorting"
        );

        $options = '';

        foreach ($rows as $row) {
            $label = ('' !== (string) $row['rtitle'] ? $row['rtitle'].' › ' : '').$row['ctitle'];
            $options .= '<option value="'.$row['id'].'">'.StringUtil::specialchars($label).'</option>';
        }

        return $options;
    }

    /**
     * @param \Doctrine\DBAL\Connection $connection
     */
    private function isCategory($connection, int $id): bool
    {
        return $id > 0 && 'category' === (string) $connection->fetchOne('SELECT type FROM tl_synapsis_forum WHERE id = ?', [$id]);
    }

    private function error(string $text): string
    {
        return '<p class="tl_error">'.$text.'</p>';
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private function session()
    {
        return System::getContainer()->get('request_stack')->getCurrentRequest()->getSession();
    }

    /**
     * Entfernt die zwischengespeicherten Upload-Dateien der laufenden Sitzung.
     */
    private function cleanupStored(): void
    {
        $data = $this->session()->get('synapsis_import');

        if (\is_array($data) && !empty($data['dir'])) {
            $this->removeDir((string) $data['dir']);
        }

        $this->session()->remove('synapsis_import');
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach ((array) glob($dir.\DIRECTORY_SEPARATOR.'*') as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($dir);
    }
}
