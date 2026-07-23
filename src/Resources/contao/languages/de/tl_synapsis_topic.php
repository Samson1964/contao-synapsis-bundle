<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Deutsche Sprachdatei der Tabelle tl_synapsis_topic (Themen).
 */

// Felder
$GLOBALS['TL_LANG']['tl_synapsis_topic']['title']     = array('Titel', 'Geben Sie den Titel des Themas ein.');
$GLOBALS['TL_LANG']['tl_synapsis_topic']['alias']     = array('Alias', 'Der Alias ist eine eindeutige Referenz, die anstelle der numerischen ID aufgerufen werden kann.');
$GLOBALS['TL_LANG']['tl_synapsis_topic']['author']    = array('Autor', 'Das Mitglied, das das Thema erstellt hat.');
$GLOBALS['TL_LANG']['tl_synapsis_topic']['date']      = array('Datum', 'Zeitpunkt der Erstellung des Themas.');
$GLOBALS['TL_LANG']['tl_synapsis_topic']['sticky']    = array('Angeheftet', 'Das Thema wird im Forum immer oben angezeigt.');
$GLOBALS['TL_LANG']['tl_synapsis_topic']['locked']    = array('Geschlossen', 'In einem geschlossenen Thema sind keine weiteren Antworten möglich.');
$GLOBALS['TL_LANG']['tl_synapsis_topic']['views']     = array('Ansichten', 'Wie oft das Thema im Frontend aufgerufen wurde.');
$GLOBALS['TL_LANG']['tl_synapsis_topic']['published'] = array('Veröffentlichen', 'Das Thema im Frontend sichtbar schalten.');

// Legenden
$GLOBALS['TL_LANG']['tl_synapsis_topic']['title_legend']   = 'Titel und Alias';
$GLOBALS['TL_LANG']['tl_synapsis_topic']['meta_legend']    = 'Autor und Datum';
$GLOBALS['TL_LANG']['tl_synapsis_topic']['config_legend']  = 'Einstellungen';
$GLOBALS['TL_LANG']['tl_synapsis_topic']['publish_legend'] = 'Veröffentlichung';

// Schaltflächen
$GLOBALS['TL_LANG']['tl_synapsis_topic']['new']    = array('Neues Thema', 'Ein neues Thema anlegen.');
$GLOBALS['TL_LANG']['tl_synapsis_topic']['edit']   = array('Thema bearbeiten', 'Thema ID %s bearbeiten.');
$GLOBALS['TL_LANG']['tl_synapsis_topic']['posts']  = array('Beiträge verwalten', 'Die Beiträge des Themas ID %s verwalten.');
$GLOBALS['TL_LANG']['tl_synapsis_topic']['copy']   = array('Thema kopieren', 'Thema ID %s kopieren.');
$GLOBALS['TL_LANG']['tl_synapsis_topic']['delete'] = array('Thema löschen', 'Thema ID %s löschen.');
$GLOBALS['TL_LANG']['tl_synapsis_topic']['show']   = array('Details anzeigen', 'Die Details des Themas ID %s anzeigen.');

// Sonstiges
$GLOBALS['TL_LANG']['tl_synapsis_topic']['unknownMember'] = 'Unbekannt';
