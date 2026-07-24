<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

use Schachbulle\ContaoSynapsisBundle\Model\SynapsisForumModel;
use Schachbulle\ContaoSynapsisBundle\Model\SynapsisPostModel;
use Schachbulle\ContaoSynapsisBundle\Model\SynapsisSubscriptionModel;
use Schachbulle\ContaoSynapsisBundle\Model\SynapsisTopicModel;

/*
 * Backend-Bereich "Synapsis" mit zwei Modulen.
 *
 * Da es inzwischen zwei Module gibt (Forumverwaltung und CSV Import/Export),
 * bekommen sie eine eigene Backend-Gruppe "synapsis" statt in "Inhalte" zu
 * liegen. Modulschluessel bewusst mit "synapsis_"-Praefix (do=synapsis_forum
 * bzw. do=synapsis_csv), um Kollisionen mit anderen Forum-Bundles (z. B.
 * do=forum) zu vermeiden.
 *
 * Beim Forum-Modul muessen alle drei Tabellen aufgefuehrt sein, damit der
 * Wechsel von der Forenstruktur zu den Themen und weiter zu den Beitraegen
 * erlaubt ist.
 */
$GLOBALS['BE_MOD']['synapsis']['synapsis_forum'] = array
(
    'tables' => array('tl_synapsis_forum', 'tl_synapsis_topic', 'tl_synapsis_post'),
    'icon'   => 'bundles/schachbullecontaosynapsis/icons/forum.svg',
);

$GLOBALS['BE_MOD']['synapsis']['synapsis_csv'] = array
(
    'callback' => \Schachbulle\ContaoSynapsisBundle\Backend\CsvModule::class,
    'icon'     => 'bundles/schachbullecontaosynapsis/icons/forum.svg',
);

/*
 * Frontend-Modul registrieren
 *
 * Ein einziges Modul stellt alle Ansichten des Forums dar. Als Legacy-Modul
 * (extends \Contao\Module) laeuft es unter Contao 4.13 und Contao 5.
 */
$GLOBALS['FE_MOD']['synapsis']['synapsis_forum'] = \Schachbulle\ContaoSynapsisBundle\Modules\SynapsisForum::class;

/*
 * Models registrieren
 *
 * Dadurch findet Contao zu jeder Tabelle die passende Model-Klasse, etwa bei
 * Relationen (Model::getRelated()).
 */
$GLOBALS['TL_MODELS']['tl_synapsis_forum'] = SynapsisForumModel::class;
$GLOBALS['TL_MODELS']['tl_synapsis_topic'] = SynapsisTopicModel::class;
$GLOBALS['TL_MODELS']['tl_synapsis_post'] = SynapsisPostModel::class;
$GLOBALS['TL_MODELS']['tl_synapsis_subscription'] = SynapsisSubscriptionModel::class;
