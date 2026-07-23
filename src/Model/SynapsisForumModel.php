<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Model;

use Contao\Model;

/**
 * Model der Tabelle tl_synapsis_forum.
 *
 * Die Tabelle bildet die komplette Forenstruktur als Baum ab. Der Typ eines
 * Datensatzes entscheidet ueber seine Rolle:
 *
 *   root     = Startpunkt (eine eigenstaendige Forenstruktur)
 *   category = Kategorie (gruppiert Foren, enthaelt selbst keine Themen)
 *   forum    = Forum (enthaelt Themen aus tl_synapsis_topic)
 *
 * @property int         $id
 * @property int         $pid
 * @property int         $sorting
 * @property int         $tstamp
 * @property string      $type
 * @property string      $title
 * @property string      $alias
 * @property string      $description
 * @property bool        $closed
 * @property bool        $protected
 * @property string|null $groups
 * @property bool        $published
 */
class SynapsisForumModel extends Model
{
    /**
     * Name der Tabelle.
     *
     * @var string
     */
    protected static $strTable = 'tl_synapsis_forum';
}
