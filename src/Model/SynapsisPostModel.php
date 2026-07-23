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
 * Model der Tabelle tl_synapsis_post.
 *
 * Ein Beitrag haengt immer an einem Thema (pid -> tl_synapsis_topic). Der Autor
 * ist ein Contao-Mitglied (tl_member).
 *
 * @property int         $id
 * @property int         $pid
 * @property int         $tstamp
 * @property int         $author
 * @property int         $date
 * @property string      $text
 * @property string|null $attachments
 * @property bool        $published
 */
class SynapsisPostModel extends Model
{
    /**
     * Name der Tabelle.
     *
     * @var string
     */
    protected static $strTable = 'tl_synapsis_post';
}
