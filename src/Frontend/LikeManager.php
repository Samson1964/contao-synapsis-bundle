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
 * Verwaltet die "Gefaellt mir"-Markierungen (Tabelle tl_synapsis_like).
 *
 * Nur angemeldete Mitglieder koennen liken; den eigenen Beitrag nicht. Das
 * erneute Ausloesen entfernt die Markierung wieder (Toggle).
 */
final class LikeManager
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
     * Setzt oder entfernt die Markierung des Mitglieds fuer den Beitrag.
     * Gaeste und der Verfasser selbst werden ignoriert.
     */
    public function toggle(int $memberId, int $postId): void
    {
        if ($memberId <= 0 || $postId <= 0) {
            return;
        }

        $author = $this->connection->fetchOne('SELECT author FROM tl_synapsis_post WHERE id = ?', [$postId]);

        if (false === $author) {
            return; // Beitrag existiert nicht
        }

        if ((int) $author === $memberId) {
            return; // eigener Beitrag darf nicht geliked werden
        }

        $exists = $this->connection->fetchOne(
            'SELECT id FROM tl_synapsis_like WHERE member = ? AND post = ?',
            [$memberId, $postId]
        );

        if (false !== $exists) {
            $this->connection->delete('tl_synapsis_like', ['member' => $memberId, 'post' => $postId]);
        } else {
            $this->connection->insert('tl_synapsis_like', [
                'member' => $memberId,
                'post' => $postId,
                'tstamp' => time(),
            ]);
        }
    }

    /**
     * Hat das Mitglied den Beitrag markiert?
     */
    public function hasLiked(int $memberId, int $postId): bool
    {
        if ($memberId <= 0) {
            return false;
        }

        return false !== $this->connection->fetchOne(
            'SELECT id FROM tl_synapsis_like WHERE member = ? AND post = ?',
            [$memberId, $postId]
        );
    }

    /**
     * Anzahl der Markierungen eines Beitrags.
     */
    public function count(int $postId): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_synapsis_like WHERE post = ?', [$postId]);
    }

    /**
     * Mitglieds-IDs, die den Beitrag markiert haben (aelteste zuerst).
     *
     * @return array<int>
     */
    public function likerIds(int $postId): array
    {
        return array_map(
            'intval',
            $this->connection->fetchFirstColumn('SELECT member FROM tl_synapsis_like WHERE post = ? ORDER BY tstamp ASC, id ASC', [$postId])
        );
    }

    /**
     * IDs der Themen, in denen das Mitglied mindestens einen Beitrag mit
     * "Gefaellt mir" markiert hat - beschraenkt auf die uebergebenen Foren
     * (Startpunkt), neueste Markierung zuerst.
     *
     * @param array<int> $forumIds
     *
     * @return array<int>
     */
    public function likedTopicIds(int $memberId, array $forumIds): array
    {
        if ($memberId <= 0 || [] === $forumIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, \count($forumIds), '?'));

        $sql = 'SELECT t.id, MAX(l.tstamp) AS lastLike'
            .' FROM tl_synapsis_like l'
            .' INNER JOIN tl_synapsis_post p ON p.id = l.post'
            .' INNER JOIN tl_synapsis_topic t ON t.id = p.pid'
            .' WHERE l.member = ?'
            ." AND p.published = '1' AND t.published = '1'"
            .' AND t.pid IN ('.$placeholders.')'
            .' GROUP BY t.id ORDER BY lastLike DESC';

        $params = array_merge([$memberId], $forumIds);

        return array_map('intval', $this->connection->fetchFirstColumn($sql, $params));
    }
}
