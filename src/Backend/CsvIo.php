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
 * Import einer Forenstruktur aus zwei CSV-Dateien.
 *
 * Der Import ist auf die Uebernahme aus Fremdsystemen (z. B. phpBB) ausgelegt
 * und erwartet zwei Dateien:
 *
 *   1. Struktur-CSV  (Kategorien und Foren)
 *      Spalten: ref, parent, type, title, alias, description, forumIcon,
 *               closed, protected, groups, guestRead, guestWrite, published
 *      - type ist "category" oder "forum"
 *      - ref ist eine in dieser Datei eindeutige Nummer (z. B. die forum_id des
 *        Fremdsystems), parent verweist auf die ref des Elternknotens
 *        (leer oder 0 = oberste Ebene, haengt an das gewaehlte Ziel)
 *
 *   2. Inhalt-CSV  (Themen und Beitraege)
 *      Spalten: forum, topic, type, title, author, authorName, date, text,
 *               sticky, locked, published, views
 *      - type ist "topic" oder "post"
 *      - eine Themenzeile verweist mit "forum" auf die ref eines Forums aus der
 *        Struktur-CSV und traegt mit "topic" eine eigene, eindeutige Nummer
 *      - eine Beitragszeile verweist mit "topic" auf die Themen-Nummer
 *      - author ist die Mitglieds-ID (0 = Gast/Fremdsystem), authorName der
 *        anzuzeigende Benutzername
 *
 * Das Ziel bestimmt, was auf oberster Ebene der Struktur erwartet wird:
 *   - Ziel Startpunkt -> oberste Zeilen muessen Kategorien sein
 *   - Ziel Kategorie  -> oberste Zeilen muessen Foren sein
 */
class CsvIo
{
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

    /**
     * Importiert Struktur (Pflicht) und optional Inhalte unter das Ziel.
     *
     * @param string $structureCsv Kategorien/Foren (Pflicht)
     * @param string $contentCsv   Themen/Beitraege (optional, '' = keine)
     * @param int    $targetId     Ziel-Knoten (Startpunkt oder Kategorie)
     *
     * @return array{forums:int, topics:int, posts:int} Anzahl importierter Datensaetze
     *
     * @throws \RuntimeException bei ungueltigem Ziel oder unpassender Struktur
     */
    public function import(string $structureCsv, string $contentCsv, int $targetId): array
    {
        $targetType = (string) $this->connection->fetchOne(
            'SELECT type FROM tl_synapsis_forum WHERE id = ?',
            [$targetId]
        );

        if ('root' !== $targetType && 'category' !== $targetType) {
            throw new \RuntimeException('Das Ziel muss ein Startpunkt oder eine Kategorie sein.');
        }

        $expectedTop = 'root' === $targetType ? 'category' : 'forum';

        $structureRows = $this->parse($structureCsv);

        if ([] === $structureRows) {
            throw new \RuntimeException('Die Struktur-Datei enthält keine Datensätze.');
        }

        $contentRows = '' !== trim($contentCsv) ? $this->parse($contentCsv) : [];

        $stats = ['forums' => 0, 'topics' => 0, 'posts' => 0];

        $this->connection->beginTransaction();

        try {
            [$forumMap, $forumType] = $this->importStructure($structureRows, $targetId, $expectedTop, $stats);
            $this->importContent($contentRows, $forumMap, $forumType, $stats);

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }

        return $stats;
    }

    /**
     * Legt Kategorien und Foren an und liefert die Zuordnung ref => neue ID
     * sowie ref => type.
     *
     * @param array<int, array<string, string>> $rows
     * @param array{forums:int, topics:int, posts:int} $stats
     *
     * @return array{0: array<string, int>, 1: array<string, string>}
     */
    private function importStructure(array $rows, int $targetId, string $expectedTop, array &$stats): array
    {
        // Struktur der obersten Ebene pruefen
        foreach ($rows as $row) {
            if ($this->isTopLevel($row['parent'] ?? '') && ($row['type'] ?? '') !== $expectedTop) {
                throw new \RuntimeException(sprintf(
                    'Die Struktur-Datei passt nicht zum Ziel: erwartet werden „%s"-Einträge auf oberster Ebene.',
                    $expectedTop
                ));
            }
        }

        $map = [];
        $type = [];
        $now = time();

        foreach ($rows as $row) {
            $rowType = (string) ($row['type'] ?? '');

            if ('category' !== $rowType && 'forum' !== $rowType) {
                continue;
            }

            $parentRef = (string) ($row['parent'] ?? '');

            if ($this->isTopLevel($parentRef)) {
                $newPid = $targetId;
            } elseif (isset($map[$parentRef])) {
                $newPid = $map[$parentRef];
            } else {
                // Elternforum (noch) nicht angelegt -> Zeile ueberspringen
                continue;
            }

            $newId = $this->insertForum($row, $newPid, $now);
            ++$stats['forums'];

            $ref = (string) ($row['ref'] ?? '');

            if ('' !== $ref) {
                $map[$ref] = $newId;
                $type[$ref] = $rowType;
            }
        }

        return [$map, $type];
    }

    /**
     * Legt Themen und Beitraege an. Themen haengen ueber die Forum-Referenz an
     * den Strukturbaum, Beitraege ueber die Themen-Referenz an ihr Thema.
     *
     * @param array<int, array<string, string>> $rows
     * @param array<string, int>    $forumMap  Struktur-ref => neue Forum-ID
     * @param array<string, string> $forumType Struktur-ref => type
     * @param array{forums:int, topics:int, posts:int} $stats
     */
    private function importContent(array $rows, array $forumMap, array $forumType, array &$stats): void
    {
        $topicMap = [];
        $now = time();

        foreach ($rows as $row) {
            $type = (string) ($row['type'] ?? '');

            if ('topic' === $type) {
                $forumRef = (string) ($row['forum'] ?? '');

                // Themen nur unter tatsaechlichen Foren (nicht Kategorien) anlegen
                if (!isset($forumMap[$forumRef]) || ($forumType[$forumRef] ?? '') !== 'forum') {
                    continue;
                }

                $newId = $this->insertTopic($row, $forumMap[$forumRef], $now);
                ++$stats['topics'];

                $topicRef = (string) ($row['topic'] ?? '');

                if ('' !== $topicRef) {
                    $topicMap[$topicRef] = $newId;
                }
            } elseif ('post' === $type) {
                $topicRef = (string) ($row['topic'] ?? '');

                if (!isset($topicMap[$topicRef])) {
                    continue;
                }

                $this->insertPost($row, $topicMap[$topicRef], $now);
                ++$stats['posts'];
            }
        }
    }

    /**
     * Oberste Ebene, wenn kein Elternverweis gesetzt ist ('' oder '0').
     */
    private function isTopLevel(string $parentRef): bool
    {
        $parentRef = trim($parentRef);

        return '' === $parentRef || '0' === $parentRef;
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
            'authorName' => $row['authorName'] ?? '',
            'date' => (int) ($row['date'] ?? $now),
            'sticky' => $row['sticky'] ?? '',
            'locked' => $row['locked'] ?? '',
            'views' => (int) ($row['views'] ?? 0),
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
            'authorName' => $row['authorName'] ?? '',
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
