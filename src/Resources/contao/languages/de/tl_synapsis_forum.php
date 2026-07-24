<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Deutsche Sprachdatei der Tabelle tl_synapsis_forum (Forenstruktur).
 */

// Typen (Referenz des Feldes "type")
$GLOBALS['TL_LANG']['tl_synapsis_forum']['types'] = array
(
    'root'     => 'Startpunkt',
    'category' => 'Kategorie',
    'forum'    => 'Forum',
);

// Felder
$GLOBALS['TL_LANG']['tl_synapsis_forum']['type']        = array('Typ', 'Startpunkt, Kategorie oder Forum. Welche Typen zur Auswahl stehen, hängt von der Position in der Struktur ab.');
$GLOBALS['TL_LANG']['tl_synapsis_forum']['title']       = array('Bezeichnung', 'Geben Sie die Bezeichnung ein.');
$GLOBALS['TL_LANG']['tl_synapsis_forum']['alias']       = array('Alias', 'Der Alias ist eine eindeutige Referenz, die anstelle der numerischen ID aufgerufen werden kann. Wird er leer gelassen, erzeugt Contao ihn aus der Bezeichnung.');
$GLOBALS['TL_LANG']['tl_synapsis_forum']['description'] = array('Beschreibung', 'Geben Sie eine optionale Beschreibung ein, die im Frontend unter der Bezeichnung erscheint.');
$GLOBALS['TL_LANG']['tl_synapsis_forum']['closed']      = array('Forum schließen', 'In einem geschlossenen Forum können keine neuen Themen und Beiträge erstellt werden.');
$GLOBALS['TL_LANG']['tl_synapsis_forum']['protected']   = array('Zugriff schützen', 'Den Bereich nur für bestimmte Mitgliedergruppen sichtbar machen. Der Schutz wird an alle untergeordneten Kategorien, Foren und Themen vererbt.');
$GLOBALS['TL_LANG']['tl_synapsis_forum']['groups']      = array('Erlaubte Mitgliedergruppen', 'Diese Mitgliedergruppen erhalten Zugriff auf den Bereich.');
$GLOBALS['TL_LANG']['tl_synapsis_forum']['guestRead']   = array('Gäste dürfen lesen', 'Nicht angemeldete Besucher (Gäste) dürfen diesen Bereich lesen, aber nicht schreiben. Die Freigabe wird nach unten vererbt.');
$GLOBALS['TL_LANG']['tl_synapsis_forum']['guestWrite']  = array('Gäste dürfen schreiben', 'Gäste dürfen in diesem Bereich Themen anlegen und antworten (schließt das Lesen ein). Die Freigabe wird nach unten vererbt.');
$GLOBALS['TL_LANG']['tl_synapsis_forum']['published']   = array('Veröffentlichen', 'Den Eintrag im Frontend sichtbar schalten.');

// Legenden
$GLOBALS['TL_LANG']['tl_synapsis_forum']['type_legend']      = 'Typ';
$GLOBALS['TL_LANG']['tl_synapsis_forum']['title_legend']     = 'Bezeichnung und Alias';
$GLOBALS['TL_LANG']['tl_synapsis_forum']['meta_legend']      = 'Beschreibung';
$GLOBALS['TL_LANG']['tl_synapsis_forum']['config_legend']    = 'Einstellungen';
$GLOBALS['TL_LANG']['tl_synapsis_forum']['protected_legend'] = 'Zugriffsschutz (Mitglieder)';
$GLOBALS['TL_LANG']['tl_synapsis_forum']['guest_legend']     = 'Gäste-Zugriff';
$GLOBALS['TL_LANG']['tl_synapsis_forum']['publish_legend']   = 'Veröffentlichung';

// Schaltflächen
$GLOBALS['TL_LANG']['tl_synapsis_forum']['new']    = array('Neu', 'Einen neuen Eintrag anlegen.');
$GLOBALS['TL_LANG']['tl_synapsis_forum']['edit']   = array('Eintrag bearbeiten', 'Eintrag ID %s bearbeiten.');
$GLOBALS['TL_LANG']['tl_synapsis_forum']['topics'] = array('Themen verwalten', 'Die Themen des Forums ID %s verwalten.');
$GLOBALS['TL_LANG']['tl_synapsis_forum']['copy']   = array('Eintrag kopieren', 'Eintrag ID %s kopieren.');
$GLOBALS['TL_LANG']['tl_synapsis_forum']['cut']    = array('Eintrag verschieben', 'Eintrag ID %s verschieben.');
$GLOBALS['TL_LANG']['tl_synapsis_forum']['delete'] = array('Eintrag löschen', 'Eintrag ID %s löschen.');
$GLOBALS['TL_LANG']['tl_synapsis_forum']['show']   = array('Details anzeigen', 'Die Details des Eintrags ID %s anzeigen.');

// Sonstiges
$GLOBALS['TL_LANG']['tl_synapsis_forum']['closedLabel'] = 'geschlossen';
