<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Backend;

use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * Importiert die Foren des Contao-Bundles "Support-Ticket-System" (Fast-Media)
 * direkt aus den vorhandenen Datenbanktabellen in eine Synapsis-Kategorie.
 *
 * Anders als der phpBB-Import werden keine Dateien hochgeladen, sondern die
 * lebenden Tabellen der aktuellen Datenbank gelesen:
 *   - tl_support_archive (type "forum"/"support") -> Foren
 *   - tl_support_ticket                            -> Themen
 *   - tl_support_comment                           -> Beitraege (Text ist HTML)
 *
 * Das Ticket selbst hat keinen eigenen Nachrichtentext; der Eroeffnungsbeitrag
 * ist der aelteste Kommentar des Tickets. Die Verfasser sind echte Contao-
 * Mitglieder: member_id verweist auf tl_member.id und wird 1:1 uebernommen
 * (author = member_id, authorName als Momentaufnahme des Benutzernamens).
 *
 * tl_support_category (reine Ticket-Kategorien) und tl_support_notify
 * (Benachrichtigungen) werden nicht ausgewertet. Datei-Anhaenge (enclosure)
 * werden nicht uebernommen.
 */
class SupportImporter
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var int
     */
    private $sorting = 0;

    /**
     * Zwischenspeicher member_id => Benutzername (aus tl_member).
     *
     * @var array<int, string>
     */
    private $memberNames = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Sind die benoetigten Support-Tabellen in dieser Datenbank vorhanden? Nur
     * dann wird der Import ueberhaupt angeboten.
     */
    public function isAvailable(): bool
    {
        return $this->tableExists('tl_support_archive')
            && $this->tableExists('tl_support_ticket')
            && $this->tableExists('tl_support_comment');
    }

    /**
     * Liefert die auswaehlbaren Support-Foren (type "forum" und "support") fuer
     * die Auswahl "welche Foren importieren".
     *
     * @return array<int, array{id:int, name:string}> nach Titel sortiert
     */
    public function listForums(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, title FROM tl_support_archive WHERE type IN ('forum', 'support') ORDER BY title"
        );

        $forums = [];

        foreach ($rows as $row) {
            $forums[] = [
                'id' => (int) $row['id'],
                'name' => StringUtil::decodeEntities((string) $row['title']),
            ];
        }

        return $forums;
    }

    /**
     * Importiert die ausgewaehlten Support-Foren samt Themen und Beitraegen in
     * die Zielkategorie.
     *
     * @param array<int>|null $onlyForumIds Nur diese tl_support_archive.id importieren
     *                                       (null = alle Foren)
     *
     * @return array<string, int> Kennzahlen des Imports
     *
     * @throws \RuntimeException bei ungueltigem Ziel
     */
    public function import(int $targetCategoryId, ?array $onlyForumIds = null): array
    {
        $targetType = (string) $this->connection->fetchOne('SELECT type FROM tl_synapsis_forum WHERE id = ?', [$targetCategoryId]);

        if ('category' !== $targetType) {
            throw new \RuntimeException('Das Ziel muss eine Kategorie sein.');
        }

        $filter = null !== $onlyForumIds ? array_flip(array_map('intval', $onlyForumIds)) : null;

        $stats = ['forums' => 0, 'topics' => 0, 'posts' => 0, 'skipped' => 0];

        $this->connection->beginTransaction();

        try {
            $forumMap = $this->importForums($targetCategoryId, $filter, $stats);
            $topicMap = $this->importTopics($forumMap, $stats);
            $this->importPosts($topicMap, $stats);

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }

        return $stats;
    }

    /**
     * Legt fuer jedes ausgewaehlte Support-Forum ein Synapsis-Forum unter der
     * Zielkategorie an.
     *
     * @param array<int, int>|null $filter tl_support_archive.id => 1 (nur diese) oder null
     * @param array<string, int>   $stats
     *
     * @return array<int, int> tl_support_archive.id => Synapsis-Forum-ID
     */
    private function importForums(int $targetId, ?array $filter, array &$stats): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, title, teaser FROM tl_support_archive WHERE type IN ('forum', 'support') ORDER BY id"
        );

        $map = [];
        $now = time();

        foreach ($rows as $row) {
            $archiveId = (int) $row['id'];

            if (null !== $filter && !isset($filter[$archiveId])) {
                continue;
            }

            $title = StringUtil::decodeEntities((string) $row['title']);
            $desc = trim(strip_tags((string) ($row['teaser'] ?? '')));

            $this->connection->insert('tl_synapsis_forum', [
                'pid' => $targetId,
                'tstamp' => $now,
                'sorting' => $this->sorting += 128,
                'type' => 'forum',
                'title' => $title,
                'alias' => $this->uniqueAlias('tl_synapsis_forum', $title),
                'description' => $desc,
                // Importierte Foren zunaechst oeffentlich lesbar schalten, damit die
                // uebernommenen Inhalte sichtbar sind; im Backend anpassbar.
                'guestRead' => '1',
                'published' => '1',
            ]);

            $map[$archiveId] = (int) $this->connection->lastInsertId();
            ++$stats['forums'];
        }

        return $map;
    }

    /**
     * Legt Themen an, deren Forum importiert wurde und die mindestens einen
     * veroeffentlichten Beitrag (Kommentar) besitzen - leere Tickets werden
     * uebersprungen. Chronologisch nach Ticket-Datum.
     *
     * @param array<int, int>    $forumMap
     * @param array<string, int> $stats
     *
     * @return array<int, int> tl_support_ticket.id => Synapsis-Themen-ID
     */
    private function importTopics(array $forumMap, array &$stats): array
    {
        // Tickets mit mindestens einem veroeffentlichten Kommentar ermitteln.
        $withPostsRows = $this->connection->fetchAllAssociative(
            "SELECT DISTINCT pid FROM tl_support_comment WHERE published = '1'"
        );
        $withPosts = array_flip(array_map('intval', array_column($withPostsRows, 'pid')));

        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, pid, title, date, member_id, hits, closed, published FROM tl_support_ticket ORDER BY CAST(date AS UNSIGNED), id'
        );

        $map = [];
        $now = time();

        foreach ($rows as $row) {
            $forumId = (int) $row['pid'];
            $ticketId = (int) $row['id'];

            if (!isset($forumMap[$forumId]) || !isset($withPosts[$ticketId])) {
                continue;
            }

            $title = StringUtil::decodeEntities((string) $row['title']);
            $author = (int) $row['member_id'];

            $this->connection->insert('tl_synapsis_topic', [
                'pid' => $forumMap[$forumId],
                'tstamp' => $now,
                'title' => $title,
                'alias' => $this->uniqueAlias('tl_synapsis_topic', $title),
                'author' => $author,
                'authorName' => $this->memberName($author),
                'date' => (int) $row['date'],
                'locked' => '1' === (string) $row['closed'] ? '1' : '',
                'views' => (int) $row['hits'],
                'published' => '1' === (string) $row['published'] ? '1' : '',
            ]);

            $map[$ticketId] = (int) $this->connection->lastInsertId();
            ++$stats['topics'];
        }

        return $map;
    }

    /**
     * Legt Beitraege aus den Kommentaren an, deren Thema importiert wurde
     * (chronologisch, damit der aelteste Kommentar der Eroeffnungsbeitrag ist).
     * Der Kommentartext ist bereits HTML und wird unveraendert uebernommen.
     *
     * @param array<int, int>    $topicMap
     * @param array<string, int> $stats
     */
    private function importPosts(array $topicMap, array &$stats): void
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, pid, comment, date, member_id, published FROM tl_support_comment ORDER BY CAST(date AS UNSIGNED), id'
        );

        $now = time();

        foreach ($rows as $row) {
            $ticketId = (int) $row['pid'];

            if (!isset($topicMap[$ticketId]) || '1' !== (string) $row['published']) {
                ++$stats['skipped'];

                continue;
            }

            $author = (int) $row['member_id'];

            $this->connection->insert('tl_synapsis_post', [
                'pid' => $topicMap[$ticketId],
                'tstamp' => $now,
                'author' => $author,
                'authorName' => $this->memberName($author),
                'date' => (int) $row['date'],
                'text' => (string) $row['comment'],
                'published' => '1',
            ]);

            ++$stats['posts'];
        }
    }

    /**
     * Benutzername eines Mitglieds als Momentaufnahme (wie beim Schreiben im
     * Frontend). Wird der Live-Name spaeter aus tl_member aufgeloest; existiert
     * das Mitglied nicht mehr, dient dieser Name als Rueckfallebene.
     */
    private function memberName(int $id): string
    {
        if ($id <= 0) {
            return '';
        }

        if (!\array_key_exists($id, $this->memberNames)) {
            $this->memberNames[$id] = (string) $this->connection->fetchOne('SELECT username FROM tl_member WHERE id = ?', [$id]);
        }

        return $this->memberNames[$id];
    }

    /**
     * Existiert die Tabelle in der aktuellen Datenbank? Ueber information_schema,
     * damit es unabhaengig von der DBAL-Version (Contao 4.13/5) funktioniert.
     */
    private function tableExists(string $table): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );
    }

    /**
     * Liefert einen eindeutigen Alias aus dem Titel; bei Kollision mit Suffix.
     */
    private function uniqueAlias(string $table, string $title): string
    {
        $alias = StringUtil::generateAlias($title);

        if ('' === $alias) {
            $alias = 'import-'.substr(md5(uniqid('', true)), 0, 8);
        }

        if ($this->connection->fetchOne('SELECT id FROM '.$table.' WHERE alias = ?', [$alias])) {
            $alias .= '-'.substr(md5(uniqid('', true)), 0, 6);
        }

        return $alias;
    }
}
