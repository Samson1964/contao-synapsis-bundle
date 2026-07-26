<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Frontend;

use Doctrine\DBAL\Connection;

/**
 * Verwaltung der Forensperren (Tabelle tl_synapsis_ban).
 *
 * Ein gesperrtes Mitglied darf keine Themen mehr erstellen und nicht mehr
 * antworten; Lesen bleibt moeglich. Die Sperre gilt forumweit (fuer alle
 * Startpunkte), da sie sich auf das Contao-Mitglied bezieht.
 *
 * Reine Datenzugriffslogik ueber die DBAL-Connection (wie ReadTracker/
 * LikeManager), damit sie unabhaengig vom Contao-Framework testbar ist.
 */
class BanManager
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * Zwischenspeicher member => gesperrt? Vermeidet wiederholte Abfragen
     * innerhalb eines Requests (z. B. je Beitrag in der Themenansicht).
     *
     * @var array<int, bool>
     */
    private $cache = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Ist das Mitglied fuer das Forum gesperrt?
     */
    public function isBanned(int $memberId): bool
    {
        if ($memberId <= 0) {
            return false;
        }

        if (!\array_key_exists($memberId, $this->cache)) {
            $this->cache[$memberId] = (bool) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM tl_synapsis_ban WHERE member = ?',
                [$memberId]
            );
        }

        return $this->cache[$memberId];
    }

    /**
     * Sperrt ein Mitglied. Liefert false, wenn die ID ungueltig ist oder das
     * Mitglied bereits gesperrt war (keine doppelte Sperre).
     */
    public function ban(int $memberId, int $byMemberId, string $reason = ''): bool
    {
        if ($memberId <= 0 || $this->isBanned($memberId)) {
            return false;
        }

        $this->connection->insert('tl_synapsis_ban', [
            'tstamp' => time(),
            'member' => $memberId,
            'reason' => mb_substr($reason, 0, 1000),
            'bannedBy' => $byMemberId,
        ]);

        $this->cache[$memberId] = true;

        return true;
    }

    /**
     * Hebt die Sperre eines Mitglieds wieder auf.
     */
    public function unban(int $memberId): void
    {
        if ($memberId <= 0) {
            return;
        }

        $this->connection->executeStatement('DELETE FROM tl_synapsis_ban WHERE member = ?', [$memberId]);
        $this->cache[$memberId] = false;
    }

    /**
     * Liefert alle Sperren (neueste zuerst) zur Anzeige in der Verwaltung.
     *
     * @return array<int, array{member:int, reason:string, bannedBy:int, tstamp:int}>
     */
    public function all(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT member, reason, bannedBy, tstamp FROM tl_synapsis_ban ORDER BY tstamp DESC'
        );

        $bans = [];

        foreach ($rows as $row) {
            $bans[] = [
                'member' => (int) $row['member'],
                'reason' => (string) ($row['reason'] ?? ''),
                'bannedBy' => (int) $row['bannedBy'],
                'tstamp' => (int) $row['tstamp'],
            ];
        }

        return $bans;
    }
}
