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
 * Verwaltet den Lesestand der Mitglieder (Tabelle tl_synapsis_read).
 *
 * Ein Thema gilt fuer ein Mitglied als ungelesen, wenn sein neuester
 * veroeffentlichter Beitrag juenger ist als der gespeicherte Lesestand
 * (lastRead) - oder wenn noch kein Lesestand existiert. Gaeste (memberId <= 0)
 * werden nicht getrackt und haben nie ungelesene Themen.
 */
final class ReadTracker
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Setzt den Lesestand eines Mitglieds fuer ein Thema auf den neuesten
     * Beitrag (das Thema gilt danach als vollstaendig gelesen).
     */
    public function markRead(int $memberId, int $topicId): void
    {
        if ($memberId <= 0) {
            return;
        }

        $latest = (int) $this->connection->fetchOne(
            "SELECT MAX(date) FROM tl_synapsis_post WHERE pid = ? AND published = '1'",
            [$topicId]
        );

        if ($latest <= 0) {
            return;
        }

        $now = time();
        $exists = $this->connection->fetchOne(
            'SELECT id FROM tl_synapsis_read WHERE member = ? AND topic = ?',
            [$memberId, $topicId]
        );

        if (false !== $exists) {
            $this->connection->update(
                'tl_synapsis_read',
                ['lastRead' => $latest, 'tstamp' => $now],
                ['member' => $memberId, 'topic' => $topicId]
            );
        } else {
            $this->connection->insert('tl_synapsis_read', [
                'member' => $memberId,
                'topic' => $topicId,
                'lastRead' => $latest,
                'tstamp' => $now,
            ]);
        }
    }

    /**
     * Markiert alle (derzeit ungelesenen) Themen der angegebenen Foren fuer das
     * Mitglied als gelesen ("Forum als gelesen markieren").
     *
     * @param array<int> $forumIds
     */
    public function markAllRead(int $memberId, array $forumIds): void
    {
        if ($memberId <= 0 || [] === $forumIds) {
            return;
        }

        foreach ($this->unreadTopicIds($memberId, $forumIds) as $topicId) {
            $this->markRead($memberId, $topicId);
        }
    }

    /**
     * Ist das Thema fuer das Mitglied ungelesen? $latestTs ist der Zeitpunkt
     * des neuesten Beitrags im Thema.
     */
    public function isUnread(int $memberId, int $topicId, int $latestTs): bool
    {
        if ($memberId <= 0 || $latestTs <= 0) {
            return false;
        }

        $lastRead = (int) $this->connection->fetchOne(
            'SELECT lastRead FROM tl_synapsis_read WHERE member = ? AND topic = ?',
            [$memberId, $topicId]
        );

        return $latestTs > $lastRead;
    }

    /**
     * Enthaelt mindestens eines der Foren ein fuer das Mitglied ungelesenes Thema?
     *
     * @param array<int> $forumIds
     */
    public function forumHasUnread(int $memberId, array $forumIds): bool
    {
        if ($memberId <= 0 || [] === $forumIds) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, \count($forumIds), '?'));

        $sql = 'SELECT 1 FROM tl_synapsis_topic t'
            .' WHERE t.pid IN ('.$placeholders.") AND t.published = '1'"
            ." AND (SELECT MAX(p.date) FROM tl_synapsis_post p WHERE p.pid = t.id AND p.published = '1')"
            .' > COALESCE((SELECT r.lastRead FROM tl_synapsis_read r WHERE r.member = ? AND r.topic = t.id), 0)'
            .' LIMIT 1';

        $params = array_merge($forumIds, [$memberId]);

        return false !== $this->connection->fetchOne($sql, $params);
    }

    /**
     * Liefert die IDs der fuer das Mitglied ungelesenen Themen in den Foren,
     * neuester Beitrag zuerst (fuer die Ansicht "Ungelesene Beitraege").
     *
     * @param array<int> $forumIds
     *
     * @return array<int>
     */
    public function unreadTopicIds(int $memberId, array $forumIds): array
    {
        if ($memberId <= 0 || [] === $forumIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, \count($forumIds), '?'));

        $sql = 'SELECT t.id,'
            ." (SELECT MAX(p.date) FROM tl_synapsis_post p WHERE p.pid = t.id AND p.published = '1') AS lastPost"
            .' FROM tl_synapsis_topic t'
            .' WHERE t.pid IN ('.$placeholders.") AND t.published = '1'"
            ." AND (SELECT MAX(p.date) FROM tl_synapsis_post p WHERE p.pid = t.id AND p.published = '1')"
            .' > COALESCE((SELECT r.lastRead FROM tl_synapsis_read r WHERE r.member = ? AND r.topic = t.id), 0)'
            .' ORDER BY lastPost DESC';

        $params = array_merge($forumIds, [$memberId]);

        return array_map('intval', $this->connection->fetchFirstColumn($sql, $params));
    }
}
