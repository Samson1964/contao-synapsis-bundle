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
 * Importiert einen phpBB-CSV-Export in eine Synapsis-Kategorie: Foren, Themen,
 * Beitraege (Text nach HTML gewandelt) und Umfragen. phpBB-Benutzer sind fremd
 * und werden als Gast mit ihrem Namen abgelegt. Private Nachrichten und
 * Datei-Anhaenge werden nicht uebernommen.
 *
 * Weitere Importformate lassen sich spaeter ueber die Formatauswahl ergaenzen.
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
     * Erzeugt die Backend-Seite und verarbeitet einen Import.
     */
    public function generate(): string
    {
        $connection = System::getContainer()->get('database_connection');

        $message = '';

        if ('synapsis_phpbb_import' === Input::post('FORM_SUBMIT')) {
            $message = $this->handleImport($connection);
        }

        return $this->render($connection, $message);
    }

    /**
     * Verarbeitet die hochgeladenen phpBB-CSV-Dateien.
     *
     * @param \Doctrine\DBAL\Connection $connection
     */
    private function handleImport($connection): string
    {
        $target = (int) Input::post('target');

        if (0 === $target) {
            return $this->error('Bitte eine Ziel-Kategorie auswählen.');
        }

        $csv = [];

        foreach (self::FILES as $key => [$required, $desc]) {
            $field = 'file_'.$key;

            if (!empty($_FILES[$field]['tmp_name']) && is_uploaded_file($_FILES[$field]['tmp_name'])) {
                $csv[$key] = (string) file_get_contents($_FILES[$field]['tmp_name']);
            } elseif ($required) {
                return $this->error('Bitte die Datei „'.$key.'" auswählen ('.$desc.').');
            }
        }

        try {
            $stats = (new PhpbbImporter($connection))->import($csv, $target);
        } catch (\Throwable $e) {
            return $this->error(StringUtil::specialchars($e->getMessage()));
        }

        return '<p class="tl_confirm">phpBB-Import abgeschlossen: '
            .$stats['forums'].' Foren, '
            .$stats['topics'].' Themen, '
            .$stats['posts'].' Beiträge, '
            .$stats['polls'].' Umfragen ('.$stats['votes'].' Stimmen) übernommen. '
            .$stats['skipped'].' Beiträge übersprungen (nicht freigegeben oder ohne Thema).</p>';
    }

    private function error(string $text): string
    {
        return '<p class="tl_error">'.$text.'</p>';
    }

    /**
     * Rendert das Import-Formular im Backend-Stil.
     *
     * @param \Doctrine\DBAL\Connection $connection
     */
    private function render($connection, string $message): string
    {
        $token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        $categories = $connection->fetchAllAssociative("SELECT id, title FROM tl_synapsis_forum WHERE type = 'category' ORDER BY sorting");

        $html = '<div id="tl_buttons"></div>';
        $html .= '<h2 class="sub_headline">Synapsis: phpBB-Import</h2>';
        $html .= $message;

        if ([] === $categories) {
            $html .= '<p class="tl_info">Es ist noch keine Kategorie vorhanden. Legen Sie zuerst im Forum-Modul einen Startpunkt und darin eine Kategorie an – der Import erfolgt immer in eine Kategorie.</p>';

            return $html;
        }

        $targetOptions = '';

        foreach ($categories as $c) {
            $targetOptions .= '<option value="'.$c['id'].'">'.StringUtil::specialchars($c['title']).'</option>';
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
            .'<input type="hidden" name="FORM_SUBMIT" value="synapsis_phpbb_import">'
            .'<input type="hidden" name="REQUEST_TOKEN" value="'.$token.'">'
            .'<fieldset class="tl_tbox"><legend>phpBB-Import in eine Kategorie</legend>'
            .'<div class="widget"><h3><label for="format">Format</label></h3>'
            .'<select name="format" id="format" class="tl_select"><option value="phpbb">phpBB (CSV-Export)</option></select>'
            .'<p class="tl_help tl_tip">Derzeit wird der CSV-Export von phpBB unterstützt.</p></div>'
            .'<div class="widget"><h3><label for="target">Ziel-Kategorie</label></h3>'
            .'<select name="target" id="target" class="tl_select">'.$targetOptions.'</select>'
            .'<p class="tl_help tl_tip">Die phpBB-Foren werden als Foren unter dieser Kategorie angelegt.</p></div>'
            .$fileFields
            .'</fieldset></div>'
            .'<div class="tl_formbody_submit"><div class="tl_submit_container">'
            .'<button type="submit" class="tl_submit">phpBB importieren</button>'
            .'</div></div></form>';

        return $html;
    }
}
