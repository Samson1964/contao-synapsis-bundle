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
 * Model der Tabelle tl_synapsis_topic.
 *
 * Ein Thema haengt immer an einem Forum (pid -> tl_synapsis_forum) und buendelt
 * die zugehoerigen Beitraege aus tl_synapsis_post. Der Autor ist ein Contao-
 * Mitglied (tl_member), da Themen im Frontend erstellt werden.
 *
 * @property int    $id
 * @property int    $pid
 * @property int    $tstamp
 * @property string $title
 * @property string $alias
 * @property int    $author
 * @property int    $date
 * @property int    $views
 * @property bool   $sticky
 * @property bool   $locked
 * @property bool   $published
 */
class SynapsisTopicModel extends Model
{
    /**
     * Name der Tabelle.
     *
     * @var string
     */
    protected static $strTable = 'tl_synapsis_topic';
}
