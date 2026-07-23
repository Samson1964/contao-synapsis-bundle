<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\EventListener\DataContainer;

use Contao\Config;
use Contao\Date;
use Doctrine\DBAL\Connection;

/**
 * Gemeinsame Basis der Callback-Klassen von Themen und Beitraegen.
 *
 * Beide Tabellen zeigen im Backend denselben Zusatz an - naemlich Autor und
 * Zeitpunkt - weshalb die Hilfsmethoden hier gebuendelt sind.
 */
abstract class AbstractRecordListener
{
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Liefert den Anzeigenamen eines Mitglieds (tl_member).
     *
     * Faellt auf den Benutzernamen bzw. einen Platzhalter zurueck, falls das
     * Mitglied zwischenzeitlich geloescht wurde.
     */
    protected function getMemberName(int $memberId): string
    {
        if (0 === $memberId) {
            return $GLOBALS['TL_LANG']['tl_synapsis_topic']['unknownMember'] ?? 'Unbekannt';
        }

        $member = $this->connection->fetchAssociative(
            'SELECT firstname, lastname, username FROM tl_member WHERE id = ?',
            [$memberId]
        );

        if (!$member) {
            return $GLOBALS['TL_LANG']['tl_synapsis_topic']['unknownMember'] ?? 'Unbekannt';
        }

        $name = trim(($member['firstname'] ?? '').' '.($member['lastname'] ?? ''));

        return '' !== $name ? $name : (string) $member['username'];
    }

    /**
     * Formatiert einen Zeitstempel im eingestellten Datums-/Zeitformat.
     *
     * Ist in der Contao-Konfiguration kein Format hinterlegt, greift ein
     * deutsches Standardformat, damit die Backend-Liste nie ohne Datum bleibt.
     */
    protected function formatDate(int $timestamp): string
    {
        if (0 === $timestamp) {
            return '';
        }

        $format = (string) Config::get('datim');

        if ('' === $format) {
            $format = 'd.m.Y H:i';
        }

        return Date::parse($format, $timestamp);
    }
}
