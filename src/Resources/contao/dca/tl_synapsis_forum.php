<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DC_Table;
use Schachbulle\ContaoSynapsisBundle\Frontend\LucideIcons;
use Schachbulle\ContaoSynapsisBundle\SchachbulleContaoSynapsisBundle;

// Contao-Version zuverlaessig anhand des installierten Pakets bestimmen; davon
// haengen Treiberklasse und Bauart der Operationsleiste ab (native Toggle-
// Operation ab Contao 5, vollstaendige Operationsarrays fuer Contao 4.13).
$synapsisC5 = SchachbulleContaoSynapsisBundle::isContao5();

/*
 * Tabelle tl_synapsis_forum
 *
 * Bildet die komplette Forenstruktur als Baum ab (Sortier-Modus 5, analog zur
 * Seitenstruktur tl_page). Der Typ eines Knotens bestimmt seine Rolle:
 *
 *   root     = Startpunkt, also eine eigenstaendige Forenstruktur
 *   category = Kategorie, gruppiert Foren und enthaelt selbst keine Themen
 *   forum    = Forum, enthaelt die Themen aus tl_synapsis_topic
 *
 * Welcher Typ an welcher Stelle erlaubt ist, entscheidet der options_callback
 * des Feldes "type" (siehe ForumListener).
 */
$GLOBALS['TL_DCA']['tl_synapsis_forum'] = array
(
    'config' => array
    (
        // Contao 5 erwartet den FQCN der Treiberklasse. Aeltere Contao-4.13-
        // Versionen kennen nur den Kurznamen "Table", deshalb die Weiche.
        'dataContainer'    => $synapsisC5 ? DC_Table::class : 'Table',
        'ctable'           => array('tl_synapsis_topic'),
        'enableVersioning' => true,
        'markAsCopy'       => 'title',
        'sql' => array
        (
            'keys' => array
            (
                'id'    => 'primary',
                'pid'   => 'index',
                'alias' => 'index',
                'type'  => 'index',
            ),
        ),
    ),

    'list' => array
    (
        'sorting' => array
        (
            'mode'        => 5, // Baumstruktur (eine Tabelle), wie die Seitenstruktur
            'icon'        => 'pagemounts.svg',
            'panelLayout' => 'search',
        ),
        'label' => array
        (
            'fields' => array('title'),
            'format' => '%s',
            // label_callback: ForumListener::renderLabel (services.yaml)
        ),
        'global_operations' => array
        (
            'all' => array
            (
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ),
        ),
        // 'operations' werden nach dieser Array-Definition versionsabhaengig
        // gesetzt (siehe unten), damit Contao 5 seine native Toggle-Operation
        // erhaelt und Contao 4.13 vollstaendige Operationsarrays.
    ),

    'palettes' => array
    (
        '__selector__' => array('type', 'protected'),
        'default'      => '{type_legend},type',
        'root'         => '{type_legend},type;{title_legend},title,alias;{meta_legend},description;{icon_legend},forumIcon;{protected_legend:hide},protected;{guest_legend:hide},guestRead,guestWrite;{roles_legend:hide},adminGroups,adminMembers,modGroups,modMembers,showModerators;{poll_legend:hide},pollGroups,pollMembers;{publish_legend},published',
        'category'     => '{type_legend},type;{title_legend},title,alias;{meta_legend},description;{icon_legend},forumIcon;{protected_legend:hide},protected;{guest_legend:hide},guestRead,guestWrite;{roles_legend:hide},adminGroups,adminMembers,modGroups,modMembers;{poll_legend:hide},pollGroups,pollMembers;{publish_legend},published',
        'forum'        => '{type_legend},type;{title_legend},title,alias;{meta_legend},description;{icon_legend},forumIcon;{config_legend},closed;{protected_legend:hide},protected;{guest_legend:hide},guestRead,guestWrite;{roles_legend:hide},adminGroups,adminMembers,modGroups,modMembers;{poll_legend:hide},pollGroups,pollMembers;{publish_legend},published',
    ),

    'subpalettes' => array
    (
        'protected' => 'groups',
    ),

    'fields' => array
    (
        'id' => array
        (
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ),
        'pid' => array
        (
            'foreignKey' => 'tl_synapsis_forum.title',
            'sql'        => "int(10) unsigned NOT NULL default 0",
            'relation'   => array('type' => 'belongsTo', 'load' => 'lazy'),
        ),
        'sorting' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'tstamp' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'type' => array
        (
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'select',
            'reference' => &$GLOBALS['TL_LANG']['tl_synapsis_forum']['types'],
            // options_callback: ForumListener::getTypeOptions (services.yaml)
            'eval'      => array('mandatory' => true, 'submitOnChange' => true, 'helpwizard' => false, 'tl_class' => 'w50'),
            'sql'       => "varchar(32) NOT NULL default 'forum'",
        ),
        'title' => array
        (
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) NOT NULL default ''",
        ),
        'alias' => array
        (
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            // save_callback: ForumListener::generateAlias (services.yaml)
            'eval'      => array('rgxp' => 'alias', 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) BINARY NOT NULL default ''",
        ),
        'description' => array
        (
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'textarea',
            'eval'      => array('style' => 'height:60px', 'decodeEntities' => true, 'tl_class' => 'clr'),
            'sql'       => "text NULL",
        ),
        // Icon fuer Foren (Lucide). Wird vererbt: Startpunkt = Standard,
        // Kategorie und Forum koennen ihn ueberschreiben. Leer = erben.
        'forumIcon' => array
        (
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => LucideIcons::names(),
            // wizard-Callback: ForumListener::iconWizard (visuelles Icon-Raster)
            'eval'      => array('includeBlankOption' => true, 'tl_class' => 'clr'),
            'sql'       => "varchar(64) NOT NULL default ''",
        ),
        'closed' => array
        (
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50'),
            'sql'       => "char(1) NOT NULL default ''",
        ),
        'protected' => array
        (
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'eval'      => array('submitOnChange' => true, 'tl_class' => 'w50'),
            'sql'       => "char(1) NOT NULL default ''",
        ),
        'groups' => array
        (
            'exclude'   => true,
            'inputType' => 'checkbox',
            // options_callback: ForumListener::getGroupOptions (services.yaml)
            // fuehrt ausschliesslich echte Mitgliedergruppen (Gaeste separat)
            'eval'      => array('mandatory' => true, 'multiple' => true),
            'sql'       => "blob NULL",
        ),
        // Gaeste-Lesezugriff (Opt-in, wird im Baum vererbt)
        'guestRead' => array
        (
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50'),
            'sql'       => "char(1) NOT NULL default ''",
        ),
        // Gaeste-Schreibrecht (Opt-in, schliesst Lesen ein, wird vererbt)
        'guestWrite' => array
        (
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50'),
            'sql'       => "char(1) NOT NULL default ''",
        ),
        // Umfragen-Erstellrecht (wird im Baum vererbt): erlaubte
        // Mitgliedergruppen ...
        'pollGroups' => array
        (
            'exclude'   => true,
            'inputType' => 'checkbox',
            // options_callback: ForumListener::getMemberGroupOptions (services.yaml)
            'eval'      => array('multiple' => true, 'tl_class' => 'clr'),
            'sql'       => "blob NULL",
        ),
        // ... und zusaetzlich einzeln berechtigte Mitglieder.
        'pollMembers' => array
        (
            'exclude'   => true,
            // Picker statt Auswahlliste: skaliert auch bei zehntausenden
            // Mitgliedern (Modal mit Suche und Blaettern statt 40.000 Optionen).
            'inputType' => 'picker',
            'foreignKey' => 'tl_member.username',
            'eval'      => array('multiple' => true, 'fieldType' => 'checkbox', 'tl_class' => 'clr'),
            'relation'  => array('type' => 'hasMany', 'load' => 'lazy', 'table' => 'tl_member'),
            'sql'       => "blob NULL",
        ),
        // Rollen (vererbt): Administratoren ...
        'adminGroups' => array
        (
            'exclude'   => true,
            'inputType' => 'checkbox',
            // options_callback: ForumListener::getMemberGroupOptions
            'eval'      => array('multiple' => true, 'tl_class' => 'clr'),
            'sql'       => "blob NULL",
        ),
        'adminMembers' => array
        (
            'exclude'   => true,
            // Picker statt Auswahlliste (siehe pollMembers).
            'inputType' => 'picker',
            'foreignKey' => 'tl_member.username',
            'eval'      => array('multiple' => true, 'fieldType' => 'checkbox', 'tl_class' => 'clr'),
            'relation'  => array('type' => 'hasMany', 'load' => 'lazy', 'table' => 'tl_member'),
            'sql'       => "blob NULL",
        ),
        // ... und Moderatoren.
        'modGroups' => array
        (
            'exclude'   => true,
            'inputType' => 'checkbox',
            // options_callback: ForumListener::getMemberGroupOptions
            'eval'      => array('multiple' => true, 'tl_class' => 'clr'),
            'sql'       => "blob NULL",
        ),
        'modMembers' => array
        (
            'exclude'   => true,
            // Picker statt Auswahlliste (siehe pollMembers).
            'inputType' => 'picker',
            'foreignKey' => 'tl_member.username',
            'eval'      => array('multiple' => true, 'fieldType' => 'checkbox', 'tl_class' => 'clr'),
            'relation'  => array('type' => 'hasMany', 'load' => 'lazy', 'table' => 'tl_member'),
            'sql'       => "blob NULL",
        ),
        // Nur am Startpunkt: Moderatoren-Namen im Frontend bei jedem Forum
        // anzeigen (Gruppen werden zu Einzelnamen aufgeloest).
        'showModerators' => array
        (
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'clr'),
            'sql'       => "char(1) NOT NULL default ''",
        ),
        'published' => array
        (
            'exclude'   => true,
            'toggle'    => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'eval'      => array('doNotCopy' => true, 'tl_class' => 'w50'),
            'sql'       => "char(1) NOT NULL default ''",
        ),
    ),
);

/*
 * Operationsleiste versionsabhaengig setzen
 *
 * Contao 5 kennt String-Referenzen auf die Standardoperationen; darueber erhaelt
 * man den nativen Veroeffentlichungs-Toggle (korrektes Icon, Rechtepruefung) ohne
 * eigenen Callback. Die eigene Operation "topics" bleibt ein vollstaendiges Array
 * und wird nur bei Foren angezeigt (button_callback: ForumListener::topicsButton).
 *
 * Contao 4.13 kennt diese String-Referenzen nicht und benoetigt vollstaendige
 * Operationsarrays inkl. eigener Toggle-Operation.
 */
if ($synapsisC5) {
    $GLOBALS['TL_DCA']['tl_synapsis_forum']['list']['operations'] = array
    (
        'edit',
        'topics' => array
        (
            'href' => 'table=tl_synapsis_topic',
            'icon' => 'articles.svg',
        ),
        'copy',
        'cut',
        'delete',
        'toggle',
        'show',
    );
} else {
    $GLOBALS['TL_DCA']['tl_synapsis_forum']['list']['operations'] = array
    (
        'edit' => array
        (
            'href' => 'act=edit',
            'icon' => 'edit.svg',
        ),
        'topics' => array
        (
            'href' => 'table=tl_synapsis_topic',
            'icon' => 'articles.svg',
        ),
        'copy' => array
        (
            'href' => 'act=paste&amp;mode=copy',
            'icon' => 'copy.svg',
        ),
        'cut' => array
        (
            'href' => 'act=paste&amp;mode=cut',
            'icon' => 'cut.svg',
        ),
        'delete' => array
        (
            'href'       => 'act=delete',
            'icon'       => 'delete.svg',
            'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '').'\'))return false;Backend.getScrollOffset()"',
        ),
        'toggle' => array
        (
            'href'         => 'act=toggle&amp;field=published',
            'icon'         => 'visible.svg',
            'showInHeader' => true,
        ),
        'show' => array
        (
            'href' => 'act=show',
            'icon' => 'show.svg',
        ),
    );
}
