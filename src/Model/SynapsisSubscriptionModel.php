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
 * Model der Tabelle tl_synapsis_subscription.
 *
 * Verbindet ein Mitglied (tl_member) mit einem abonnierten Thema
 * (tl_synapsis_topic). Bei einer neuen Antwort werden die Abonnenten
 * benachrichtigt.
 *
 * @property int $id
 * @property int $tstamp
 * @property int $member
 * @property int $topic
 */
class SynapsisSubscriptionModel extends Model
{
    /**
     * Name der Tabelle.
     *
     * @var string
     */
    protected static $strTable = 'tl_synapsis_subscription';
}
