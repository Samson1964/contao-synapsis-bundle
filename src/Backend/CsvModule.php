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
 * Backend-Modul "CSV Import" (Gruppe Synapsis, do=synapsis_csv).
 *
 * Importiert eine Forenstruktur aus zwei CSV-Dateien - eine mit Kategorien und
 * Foren, eine mit Themen und Beitraegen (siehe CsvIo). Damit lassen sich Daten
 * aus Fremdsystemen (z. B. phpBB) uebernehmen oder eine geloeschte Struktur
 * wiederherstellen.
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
     * Erzeugt die Backend-Seite und verarbeitet einen Import.
     */
    public function generate(): string
    {
        $connection = System::getContainer()->get('database_connection');
        $io = new CsvIo($connection);

        $message = '';

        if ('synapsis_csv_import' === Input::post('FORM_SUBMIT')) {
            $message = $this->handleImport($io);
        }

        return $this->render($connection, $message);
    }

    /**
     * Verarbeitet die hochgeladenen CSV-Dateien und liefert eine
     * Ergebnismeldung (HTML).
     */
    private function handleImport(CsvIo $io): string
    {
        $target = (int) Input::post('target');

        if (0 === $target) {
            return $this->error('Bitte ein Ziel auswählen.');
        }

        if (empty($_FILES['structurefile']['tmp_name']) || !is_uploaded_file($_FILES['structurefile']['tmp_name'])) {
            return $this->error('Bitte die Struktur-Datei (Kategorien/Foren) auswählen.');
        }

        $structureCsv = (string) file_get_contents($_FILES['structurefile']['tmp_name']);

        // Inhalt-Datei ist optional
        $contentCsv = '';

        if (!empty($_FILES['contentfile']['tmp_name']) && is_uploaded_file($_FILES['contentfile']['tmp_name'])) {
            $contentCsv = (string) file_get_contents($_FILES['contentfile']['tmp_name']);
        }

        try {
            $stats = $io->import($structureCsv, $contentCsv, $target);
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
     * Rendert das Import-Formular im Backend-Stil.
     *
     * @param \Doctrine\DBAL\Connection $connection
     */
    private function render($connection, string $message): string
    {
        $token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        $roots = $connection->fetchAllAssociative("SELECT id, title FROM tl_synapsis_forum WHERE type = 'root' ORDER BY sorting");
        $categories = $connection->fetchAllAssociative("SELECT id, title FROM tl_synapsis_forum WHERE type = 'category' ORDER BY sorting");

        // Import-Ziele (Startpunkte und Kategorien)
        $targetOptions = '';
        foreach ($roots as $r) {
            $targetOptions .= '<option value="'.$r['id'].'">Startpunkt: '.StringUtil::specialchars($r['title']).'</option>';
        }
        foreach ($categories as $c) {
            $targetOptions .= '<option value="'.$c['id'].'">Kategorie: '.StringUtil::specialchars($c['title']).'</option>';
        }

        $html = '<div id="tl_buttons"></div>';
        $html .= '<h2 class="sub_headline">Synapsis: CSV Import</h2>';
        $html .= $message;

        if ([] === $roots) {
            $html .= '<p class="tl_info">Es ist noch kein Startpunkt vorhanden. Legen Sie zuerst im Forum-Modul einen Startpunkt an.</p>';

            return $html;
        }

        $html .= '<form method="post" enctype="multipart/form-data" class="tl_form">'
            .'<div class="tl_formbody_edit">'
            .'<input type="hidden" name="FORM_SUBMIT" value="synapsis_csv_import">'
            .'<input type="hidden" name="REQUEST_TOKEN" value="'.$token.'">'
            .'<fieldset class="tl_tbox"><legend>Import in einen Startpunkt oder eine Kategorie</legend>'
            .'<div class="widget"><h3><label for="target">Ziel</label></h3>'
            .'<select name="target" id="target" class="tl_select">'.$targetOptions.'</select>'
            .'<p class="tl_help tl_tip">Startpunkt als Ziel erwartet Kategorien auf oberster Ebene, eine Kategorie erwartet Foren.</p></div>'
            .'<div class="widget"><h3><label for="structurefile">Struktur-Datei (Kategorien/Foren)</label></h3>'
            .'<input type="file" name="structurefile" id="structurefile" class="tl_upload_field" accept=".csv">'
            .'<p class="tl_help tl_tip">Pflicht. Spalten: ref, parent, type (category/forum), title, alias, description, forumIcon, closed, protected, groups, guestRead, guestWrite, published.</p></div>'
            .'<div class="widget"><h3><label for="contentfile">Inhalt-Datei (Themen/Beiträge)</label></h3>'
            .'<input type="file" name="contentfile" id="contentfile" class="tl_upload_field" accept=".csv">'
            .'<p class="tl_help tl_tip">Optional. Spalten: forum, topic, type (topic/post), title, author, authorName, date, text, sticky, locked, published, views. „forum" verweist auf die ref eines Forums der Struktur-Datei, „topic" gruppiert Beiträge unter ihr Thema.</p></div>'
            .'</fieldset></div>'
            .'<div class="tl_formbody_submit"><div class="tl_submit_container">'
            .'<button type="submit" class="tl_submit">CSV importieren</button>'
            .'</div></div></form>';

        return $html;
    }
}
