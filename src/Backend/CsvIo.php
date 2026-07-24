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
 * Export und Import einer Forenstruktur als CSV.
 *
 * Der Export eines Startpunkts schreibt dessen komplette Unterstruktur
 * (Kategorien, Foren, Themen, Beitraege) in eine einzige CSV-Datei. Jede Zeile
 * hat eine laufende Nummer (ref) und verweist per parent auf die Nummer ihres
 * Elternknotens (0 = oberste Ebene). Dadurch laesst sich der Baum beim Import
 * eindeutig wiederherstellen, unabhaengig von den urspruenglichen IDs.
 *
 * Beim Import wird der Inhalt unter ein Ziel gehaengt:
 *   - Ziel Startpunkt  -> die obersten Zeilen muessen Kategorien sein
 *   - Ziel Kategorie   -> die obersten Zeilen muessen Foren sein
 *
 * So kann eine vorher geloeschte Struktur vollstaendig wiederhergestellt werden.
 */
class CsvIo
{
    /**
     * Spaltenreihenfolge der CSV.
     *
     * @var array<string>
     */
    private const COLUMNS = [
        'ref', 'parent', 'type', 'title', 'alias', 'description', 'forumIcon',
        'closed', 'protected', 'groups', 'guestRead', 'guestWrite', 'published',
        'sticky', 'locked', 'author', 'date', 'text',
    ];

    /**
     * @var Connection
     */
    private $connection;

    /**
     * Laufender Sortierwert beim Import.
     *
     * @var int
     */
    private $sorting = 0;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    // -------------------------------------------------------------------------
    // Export
    // -------------------------------------------------------------------------

    /**
     * Exportiert die Unterstruktur eines Startpunkts als CSV-String.
     */
    public function export(int $rootId): string
    {
        $rows = [];
        $ref = 0;

        $this->walk($rootId, 0, $ref, $rows);

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, self::COLUMNS);

        foreach ($rows as $row) {
            $line = [];
            foreach (self::COLUMNS as $col) {
                $line[] = $row[$col] ?? '';
            }
            fputcsv($handle, $line);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Durchlaeuft die Kinder eines Knotens und sammelt Zeilen (Elternknoten vor
     * Kindknoten, damit der Import die Zuordnung aufloesen kann).
     *
     * @param array<int, array<string, mixed>> $rows
     */
    private function walk(int $pid, int $parentRef, int &$ref, array &$rows): void
    {
        $children = $this->connection->fetchAllAssociative(
            'SELECT * FROM tl_synapsis_forum WHERE pid = ? ORDER BY sorting',
            [$pid]
        );

        foreach ($children as $node) {
            $myRef = ++$ref;
            $rows[] = $this->forumRow($node, $myRef, $parentRef);

            if ('forum' === $node['type']) {
                $topics = $this->connection->fetchAllAssociative(
                    'SELECT * FROM tl_synapsis_topic WHERE pid = ? ORDER BY date',
                    [(int) $node['id']]
                );

                foreach ($topics as $topic) {
                    $topicRef = ++$ref;
                    $rows[] = $this->topicRow($topic, $topicRef, $myRef);

                    $posts = $this->connection->fetchAllAssociative(
                        'SELECT * FROM tl_synapsis_post WHERE pid = ? ORDER BY date',
                        [(int) $topic['id']]
                    );

                    foreach ($posts as $post) {
                        $rows[] = $this->postRow($post, ++$ref, $topicRef);
                    }
                }
            }

            $this->walk((int) $node['id'], $myRef, $ref, $rows);
        }
    }

    /**
     * @param array<string, mixed> $node
     *
     * @return array<string, string>
     */
    private function forumRow(array $node, int $ref, int $parentRef): array
    {
        return [
            'ref' => (string) $ref,
            'parent' => (string) $parentRef,
            'type' => (string) $node['type'],
            'title' => (string) $node['title'],
            'alias' => (string) $node['alias'],
            'description' => (string) ($node['description'] ?? ''),
            'forumIcon' => (string) ($node['forumIcon'] ?? ''),
            'closed' => (string) ($node['closed'] ?? ''),
            'protected' => (string) ($node['protected'] ?? ''),
            'groups' => implode(',', StringUtil::deserialize($node['groups'] ?? null, true)),
            'guestRead' => (string) ($node['guestRead'] ?? ''),
            'guestWrite' => (string) ($node['guestWrite'] ?? ''),
            'published' => (string) ($node['published'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $topic
     *
     * @return array<string, string>
     */
    private function topicRow(array $topic, int $ref, int $parentRef): array
    {
        return [
            'ref' => (string) $ref,
            'parent' => (string) $parentRef,
            'type' => 'topic',
            'title' => (string) $topic['title'],
            'alias' => (string) $topic['alias'],
            'published' => (string) ($topic['published'] ?? ''),
            'sticky' => (string) ($topic['sticky'] ?? ''),
            'locked' => (string) ($topic['locked'] ?? ''),
            'author' => (string) ($topic['author'] ?? '0'),
            'date' => (string) ($topic['date'] ?? '0'),
        ];
    }

    /**
     * @param array<string, mixed> $post
     *
     * @return array<string, string>
     */
    private function postRow(array $post, int $ref, int $parentRef): array
    {
        return [
            'ref' => (string) $ref,
            'parent' => (string) $parentRef,
            'type' => 'post',
            'published' => (string) ($post['published'] ?? ''),
            'author' => (string) ($post['author'] ?? '0'),
            'date' => (string) ($post['date'] ?? '0'),
            'text' => (string) ($post['text'] ?? ''),
        ];
    }

    // -------------------------------------------------------------------------
    // Import
    // -------------------------------------------------------------------------

    /**
     * Importiert eine CSV unter das angegebene Ziel (Startpunkt oder Kategorie).
     *
     * @return array{forums:int, topics:int, posts:int} Anzahl importierter Datensaetze
     *
     * @throws \RuntimeException bei ungueltigem Ziel oder unpassender Struktur
     */
    public function import(string $csv, int $targetId): array
    {
        $targetType = (string) $this->connection->fetchOne(
            'SELECT type FROM tl_synapsis_forum WHERE id = ?',
            [$targetId]
        );

        if ('root' !== $targetType && 'category' !== $targetType) {
            throw new \RuntimeException('Das Ziel muss ein Startpunkt oder eine Kategorie sein.');
        }

        $expectedTop = 'root' === $targetType ? 'category' : 'forum';

        $rows = $this->parse($csv);

        if ([] === $rows) {
            throw new \RuntimeException('Die CSV-Datei enthält keine Datensätze.');
        }

        // Struktur der obersten Ebene pruefen
        foreach ($rows as $row) {
            if ('0' === (string) $row['parent'] && $row['type'] !== $expectedTop) {
                throw new \RuntimeException(sprintf(
                    'Die Datei passt nicht zum Ziel: erwartet werden „%s"-Einträge auf oberster Ebene.',
                    $expectedTop
                ));
            }
        }

        $map = [];
        $stats = ['forums' => 0, 'topics' => 0, 'posts' => 0];
        $now = time();

        foreach ($rows as $row) {
            $parentRef = (string) $row['parent'];
            $newPid = '0' === $parentRef ? $targetId : ($map[$parentRef] ?? 0);

            if (0 === $newPid && '0' !== $parentRef) {
                // Elternzeile nicht gefunden -> ueberspringen
                continue;
            }

            $type = (string) $row['type'];

            if ('category' === $type || 'forum' === $type) {
                $newId = $this->insertForum($row, $newPid, $now);
                ++$stats['forums'];
            } elseif ('topic' === $type) {
                $newId = $this->insertTopic($row, $newPid, $now);
                ++$stats['topics'];
            } elseif ('post' === $type) {
                $newId = $this->insertPost($row, $newPid, $now);
                ++$stats['posts'];
            } else {
                continue;
            }

            $map[(string) $row['ref']] = $newId;
        }

        return $stats;
    }

    /**
     * Zerlegt die CSV in ein Array assoziativer Zeilen (Spaltenname => Wert).
     *
     * @return array<int, array<string, string>>
     */
    private function parse(string $csv): array
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csv);
        rewind($handle);

        $header = fgetcsv($handle);

        if (false === $header) {
            fclose($handle);

            return [];
        }

        $header = array_map('strval', $header);
        $rows = [];

        while (false !== ($line = fgetcsv($handle))) {
            if ([null] === $line || [] === $line) {
                continue;
            }

            $row = [];

            foreach ($header as $i => $col) {
                $row[$col] = (string) ($line[$i] ?? '');
            }

            if ('' === ($row['type'] ?? '')) {
                continue;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param array<string, string> $row
     */
    private function insertForum(array $row, int $pid, int $now): int
    {
        $groups = $this->groupsBlob($row['groups'] ?? '');

        $this->connection->insert('tl_synapsis_forum', [
            'pid' => $pid,
            'tstamp' => $now,
            'sorting' => $this->sorting += 128,
            'type' => 'category' === $row['type'] ? 'category' : 'forum',
            'title' => $row['title'] ?? '',
            'alias' => $this->uniqueAlias('tl_synapsis_forum', $row['alias'] ?? '', $row['title'] ?? ''),
            'description' => $row['description'] ?? '',
            'forumIcon' => $row['forumIcon'] ?? '',
            'closed' => $row['closed'] ?? '',
            'protected' => $row['protected'] ?? '',
            'groups' => $groups,
            'guestRead' => $row['guestRead'] ?? '',
            'guestWrite' => $row['guestWrite'] ?? '',
            'published' => '' !== ($row['published'] ?? '') ? $row['published'] : '1',
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @param array<string, string> $row
     */
    private function insertTopic(array $row, int $pid, int $now): int
    {
        $this->connection->insert('tl_synapsis_topic', [
            'pid' => $pid,
            'tstamp' => $now,
            'title' => $row['title'] ?? '',
            'alias' => $this->uniqueAlias('tl_synapsis_topic', $row['alias'] ?? '', $row['title'] ?? ''),
            'author' => (int) ($row['author'] ?? 0),
            'date' => (int) ($row['date'] ?? $now),
            'sticky' => $row['sticky'] ?? '',
            'locked' => $row['locked'] ?? '',
            'views' => 0,
            'published' => '' !== ($row['published'] ?? '') ? $row['published'] : '1',
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @param array<string, string> $row
     */
    private function insertPost(array $row, int $pid, int $now): int
    {
        $this->connection->insert('tl_synapsis_post', [
            'pid' => $pid,
            'tstamp' => $now,
            'author' => (int) ($row['author'] ?? 0),
            'date' => (int) ($row['date'] ?? $now),
            'text' => $row['text'] ?? '',
            'published' => '' !== ($row['published'] ?? '') ? $row['published'] : '1',
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Serialisiert eine kommaseparierte Gruppenliste in das Contao-Blob-Format
     * (oder null, wenn leer).
     *
     * @return string|null
     */
    private function groupsBlob(string $value)
    {
        $value = trim($value);

        if ('' === $value) {
            return null;
        }

        return serialize(array_values(array_filter(array_map('trim', explode(',', $value)), static fn ($v) => '' !== $v)));
    }

    /**
     * Liefert einen eindeutigen Alias: bevorzugt den mitgelieferten, sonst aus
     * dem Titel erzeugt; bei Kollision mit Zufallssuffix.
     */
    private function uniqueAlias(string $table, string $preferred, string $title): string
    {
        $alias = '' !== $preferred ? $preferred : StringUtil::generateAlias($title);

        if ('' === $alias) {
            $alias = 'import-'.substr(md5(uniqid('', true)), 0, 8);
        }

        $exists = (bool) $this->connection->fetchOne('SELECT id FROM '.$table.' WHERE alias = ?', [$alias]);

        if ($exists) {
            $alias .= '-'.substr(md5(uniqid('', true)), 0, 6);
        }

        return $alias;
    }
}
