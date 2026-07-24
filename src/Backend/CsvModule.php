<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Backend;

use Contao\Backend;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend-Modul "CSV Import / Export" (Gruppe Inhalte, do=synapsis_csv).
 *
 * Exportiert die komplette Struktur eines Startpunkts als CSV und importiert
 * eine solche CSV wieder unter einen Startpunkt oder eine Kategorie - so laesst
 * sich eine geloeschte Struktur vollstaendig wiederherstellen.
 */
class CsvModule extends Backend
{
    /**
     * @param mixed $dc Data-Container (hier ungenutzt)
     */
    public function __construct($dc = null)
    {
        parent::__construct();
    }

    /**
     * Erzeugt die Backend-Seite (bzw. loest den CSV-Download aus).
     */
    public function generate(): string
    {
        $connection = System::getContainer()->get('database_connection');
        $io = new CsvIo($connection);

        // --- Export: als Download ausliefern ---
        $source = (int) Input::get('source');

        if ($source > 0) {
            $row = $connection->fetchAssociative("SELECT title, alias FROM tl_synapsis_forum WHERE id = ? AND type = 'root'", [$source]);

            if ($row) {
                $csv = $io->export($source);
                $name = 'synapsis-'.($row['alias'] ?: 'export').'.csv';

                throw new ResponseException(new Response($csv, 200, [
                    'Content-Type' => 'text/csv; charset=utf-8',
                    'Content-Disposition' => 'attachment; filename="'.$name.'"',
                ]));
            }
        }

        $message = '';

        // --- Import verarbeiten ---
        if ('synapsis_csv_import' === Input::post('FORM_SUBMIT')) {
            $message = $this->handleImport($io);
        }

        return $this->render($connection, $message);
    }

    /**
     * Verarbeitet die hochgeladene CSV und liefert eine Ergebnismeldung (HTML).
     *
     * @param CsvIo $io
     */
    private function handleImport(CsvIo $io): string
    {
        $target = (int) Input::post('target');

        if (0 === $target) {
            return $this->error('Bitte ein Ziel auswählen.');
        }

        if (empty($_FILES['csvfile']['tmp_name']) || !is_uploaded_file($_FILES['csvfile']['tmp_name'])) {
            return $this->error('Bitte eine CSV-Datei auswählen.');
        }

        $csv = (string) file_get_contents($_FILES['csvfile']['tmp_name']);

        try {
            $stats = $io->import($csv, $target);
        } catch (\Throwable $e) {
            return $this->error(StringUtil::specialchars($e->getMessage()));
        }

        return '<p class="tl_confirm">Import abgeschlossen: '
            .$stats['forums'].' Kategorien/Foren, '
            .$stats['topics'].' Themen, '
            .$stats['posts'].' Beiträge importiert.</p>';
    }

    private function error(string $text): string
    {
        return '<p class="tl_error">'.$text.'</p>';
    }

    /**
     * Rendert die Export- und Import-Formulare im Backend-Stil.
     *
     * @param \Doctrine\DBAL\Connection $connection
     */
    private function render($connection, string $message): string
    {
        $token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        $roots = $connection->fetchAllAssociative("SELECT id, title FROM tl_synapsis_forum WHERE type = 'root' ORDER BY sorting");
        $categories = $connection->fetchAllAssociative("SELECT id, title FROM tl_synapsis_forum WHERE type = 'category' ORDER BY sorting");

        // Export-Optionen (Startpunkte)
        $exportOptions = '';
        foreach ($roots as $r) {
            $exportOptions .= '<option value="'.$r['id'].'">'.StringUtil::specialchars($r['title']).'</option>';
        }

        // Import-Ziele (Startpunkte und Kategorien)
        $targetOptions = '';
        foreach ($roots as $r) {
            $targetOptions .= '<option value="'.$r['id'].'">Startpunkt: '.StringUtil::specialchars($r['title']).'</option>';
        }
        foreach ($categories as $c) {
            $targetOptions .= '<option value="'.$c['id'].'">Kategorie: '.StringUtil::specialchars($c['title']).'</option>';
        }

        $html = '<div id="tl_buttons"></div>';
        $html .= '<h2 class="sub_headline">Synapsis: CSV Import / Export</h2>';
        $html .= $message;

        if ([] === $roots) {
            $html .= '<p class="tl_info">Es ist noch kein Startpunkt vorhanden. Legen Sie zuerst im Forum-Modul einen Startpunkt an.</p>';

            return $html;
        }

        // Export
        $html .= '<form method="get" class="tl_form">'
            .'<div class="tl_formbody_edit">'
            .'<input type="hidden" name="do" value="synapsis_csv">'
            .'<fieldset class="tl_tbox"><legend>Export eines Startpunkts</legend>'
            .'<div class="widget"><h3><label for="source">Startpunkt</label></h3>'
            .'<select name="source" id="source" class="tl_select">'.$exportOptions.'</select></div>'
            .'</fieldset></div>'
            .'<div class="tl_formbody_submit"><div class="tl_submit_container">'
            .'<button type="submit" class="tl_submit">Als CSV exportieren</button>'
            .'</div></div></form>';

        // Import
        $html .= '<form method="post" enctype="multipart/form-data" class="tl_form">'
            .'<div class="tl_formbody_edit">'
            .'<input type="hidden" name="FORM_SUBMIT" value="synapsis_csv_import">'
            .'<input type="hidden" name="REQUEST_TOKEN" value="'.$token.'">'
            .'<fieldset class="tl_tbox"><legend>Import in einen Startpunkt oder eine Kategorie</legend>'
            .'<div class="widget"><h3><label for="target">Ziel</label></h3>'
            .'<select name="target" id="target" class="tl_select">'.$targetOptions.'</select></div>'
            .'<div class="widget"><h3><label for="csvfile">CSV-Datei</label></h3>'
            .'<input type="file" name="csvfile" id="csvfile" class="tl_upload_field" accept=".csv"></div>'
            .'<p class="tl_help tl_tip">Startpunkt als Ziel erwartet Kategorien auf oberster Ebene, eine Kategorie erwartet Foren. So wird eine gelöschte Struktur wiederhergestellt.</p>'
            .'</fieldset></div>'
            .'<div class="tl_formbody_submit"><div class="tl_submit_container">'
            .'<button type="submit" class="tl_submit">CSV importieren</button>'
            .'</div></div></form>';

        return $html;
    }
}
