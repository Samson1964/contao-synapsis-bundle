<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DC_Table;
use Schachbulle\ContaoSynapsisBundle\SchachbulleContaoSynapsisBundle;

/*
 * Tabelle tl_synapsis_post
 *
 * Beitraege eines Themas. Sie haengen ueber pid/ptable an tl_synapsis_topic und
 * werden in der Elternansicht (Sortier-Modus 4) chronologisch aufgelistet. Der
 * Text wird mit TinyMCE erfasst, Dateianhaenge liegen im Feld "attachments".
 */
$GLOBALS['TL_DCA']['tl_synapsis_post'] = array
(
    'config' => array
    (
        'dataContainer'    => SchachbulleContaoSynapsisBundle::isContao5() ? DC_Table::class : 'Table',
        'ptable'           => 'tl_synapsis_topic',
        'enableVersioning' => true,
        'sql' => array
        (
            'keys' => array
            (
                'id'     => 'primary',
                'pid'    => 'index',
                'author' => 'index',
            ),
        ),
    ),

    'list' => array
    (
        'sorting' => array
        (
            'mode'              => 4, // Elternansicht (Beitraege eines Themas)
            'fields'            => array('date'),
            'headerFields'      => array('title', 'author', 'date', 'views', 'published'),
            'panelLayout'       => 'filter;search,limit',
            'child_record_class' => 'no_padding',
            // child_record_callback: PostListener::renderPost (services.yaml)
        ),
        'label' => array
        (
            'fields' => array('date'),
            'format' => '%s',
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
        'default' => '{meta_legend},author,authorName,date;{text_legend},text;{attachment_legend},attachments;{publish_legend},published',
    ),

    'fields' => array
    (
        'id' => array
        (
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ),
        'pid' => array
        (
            'foreignKey' => 'tl_synapsis_topic.title',
            'sql'        => "int(10) unsigned NOT NULL default 0",
            'relation'   => array('type' => 'belongsTo', 'load' => 'lazy'),
        ),
        'tstamp' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0",
        ),
        'author' => array
        (
            'exclude'    => true,
            'filter'     => true,
            'search'     => true,
            'inputType'  => 'select',
            'foreignKey' => 'tl_member.username',
            'eval'       => array('mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'),
            'sql'        => "int(10) unsigned NOT NULL default 0",
            'relation'   => array('type' => 'hasOne', 'load' => 'lazy'),
        ),
        // Benutzername zum Zeitpunkt des Schreibens (Momentaufnahme). Wird als
        // Autor angezeigt, wenn das Konto (author) nicht mehr existiert:
        // „Gast (frueherer Benutzername)". Bei Importen aus Fremdsystemen ist
        // author=0 und hier steht der urspruengliche Name.
        'authorName' => array
        (
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => array('maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) NOT NULL default ''",
        ),
        'date' => array
        (
            'default'   => time(),
            'exclude'   => true,
            'sorting'   => true,
            'flag'      => 8,
            'inputType' => 'text',
            'eval'      => array('rgxp' => 'datim', 'datepicker' => true, 'mandatory' => true, 'tl_class' => 'w50 wizard'),
            'sql'       => "int(10) unsigned NULL",
        ),
        'text' => array
        (
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'textarea',
            'eval'      => array('rte' => 'tinyMCE', 'mandatory' => true, 'tl_class' => 'clr'),
            'sql'       => "text NULL",
        ),
        'attachments' => array
        (
            'exclude'   => true,
            'inputType' => 'fileTree',
            'eval'      => array('multiple' => true, 'fieldType' => 'checkbox', 'filesOnly' => true, 'isSortable' => true, 'tl_class' => 'clr'),
            'sql'       => "blob NULL",
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
