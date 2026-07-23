<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DataContainer;
use Contao\DC_Table;

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
        'dataContainer'    => method_exists(DataContainer::class, 'getDriverForTable') ? DC_Table::class : 'Table',
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
        'operations' => array
        (
            'edit' => array
            (
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ),
            'topics' => array
            (
                // Wechselt in die Themen des Forums; nur bei Foren sichtbar
                // (button_callback: ForumListener::topicsButton)
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
            'show' => array
            (
                'href' => 'act=show',
                'icon' => 'show.svg',
            ),
        ),
    ),

    'palettes' => array
    (
        '__selector__' => array('type', 'protected'),
        'default'      => '{type_legend},type',
        'root'         => '{type_legend},type;{title_legend},title,alias;{meta_legend},description;{protected_legend:hide},protected;{publish_legend},published',
        'category'     => '{type_legend},type;{title_legend},title,alias;{meta_legend},description;{protected_legend:hide},protected;{publish_legend},published',
        'forum'        => '{type_legend},type;{title_legend},title,alias;{meta_legend},description;{config_legend},closed;{protected_legend:hide},protected;{publish_legend},published',
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
            'exclude'    => true,
            'inputType'  => 'checkbox',
            'foreignKey' => 'tl_member_group.name',
            'eval'       => array('mandatory' => true, 'multiple' => true),
            'sql'        => "blob NULL",
            'relation'   => array('type' => 'hasMany', 'load' => 'lazy'),
        ),
        'published' => array
        (
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'eval'      => array('doNotCopy' => true, 'tl_class' => 'w50'),
            'sql'       => "char(1) NOT NULL default ''",
        ),
    ),
);
