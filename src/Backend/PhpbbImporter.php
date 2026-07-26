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
 * Importiert einen phpBB-CSV-Export in eine Synapsis-Kategorie.
 *
 * Erwartet die CSV-Inhalte der phpBB-Tabellen (Spaltennamen wie im phpBB-Schema)
 * als Zeichenketten:
 *   - forums   (Pflicht): phpbb_forums   -> Foren (nur forum_type = 1)
 *   - topics   (Pflicht): phpbb_topics   -> Themen
 *   - posts    (Pflicht): phpbb_posts    -> Beitraege (Text nach HTML gewandelt)
 *   - users    (optional): phpbb_users   -> Anzeigenamen fuer poster_id
 *   - poll_options (optional): phpbb_poll_options -> Umfrage-Antworten
 *
 * phpBB-Benutzer sind im Zielsystem fremd: alle Beitraege/Themen werden als Gast
 * (author = 0) mit dem gespeicherten Benutzernamen abgelegt ("Gast (Name)").
 * Umfrage-Ergebnisse werden ueber die gespeicherten Stimmenzahlen
 * (poll_option_total) mit anonymen Stimmen nachgebildet. Private Nachrichten und
 * Datei-Anhaenge werden nicht uebernommen.
 */
class PhpbbImporter
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
     * Fortlaufender Ersatz-Mitgliedsschluessel fuer anonyme Umfrage-Stimmen.
     * Beginnt in einem hohen, positiven Bereich (die member-Spalte ist unsigned),
     * weit oberhalb echter, kleiner Mitglieds-IDs - so gibt es keine Kollision.
     *
     * @var int
     */
    private $voteSeq = 900000000;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param array<string, string> $csv Zuordnung Tabellenname => CSV-Inhalt
     *
     * @return array<string, int> Kennzahlen des Imports
     *
     * @throws \RuntimeException bei ungueltigem Ziel oder fehlenden Pflichtdateien
     */
    public function import(array $csv, int $targetCategoryId): array
    {
        $targetType = (string) $this->connection->fetchOne('SELECT type FROM tl_synapsis_forum WHERE id = ?', [$targetCategoryId]);

        if ('category' !== $targetType) {
            throw new \RuntimeException('Das Ziel muss eine Kategorie sein.');
        }

        $forums = $this->parse($csv['forums'] ?? '');
        $topics = $this->parse($csv['topics'] ?? '');
        $posts = $this->parse($csv['posts'] ?? '');
        $users = $this->indexUsers($this->parse($csv['users'] ?? ''));
        $pollOptions = $this->parse($csv['poll_options'] ?? '');

        if ([] === $forums) {
            throw new \RuntimeException('Die Foren-Datei (phpbb_forums) fehlt oder ist leer.');
        }

        if ([] === $posts) {
            throw new \RuntimeException('Die Beitraege-Datei (phpbb_posts) fehlt oder ist leer.');
        }

        $stats = ['forums' => 0, 'topics' => 0, 'posts' => 0, 'polls' => 0, 'votes' => 0, 'skipped' => 0];

        $this->connection->beginTransaction();

        try {
            $forumMap = $this->importForums($forums, $targetCategoryId, $stats);
            $topicMap = $this->importTopics($topics, $forumMap, $users, $stats);
            $this->importPosts($posts, $topicMap, $users, $stats);
            $this->importPolls($topics, $topicMap, $pollOptions, $stats);

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }

        return $stats;
    }

    /**
     * Legt fuer jedes echte phpBB-Forum (forum_type = 1) ein Synapsis-Forum unter
     * der Zielkategorie an. phpBB-Kategorien (Container, type 0) und Links
     * (type 2) werden uebersprungen.
     *
     * @param array<int, array<string, string>> $rows
     * @param array<string, int>                $stats
     *
     * @return array<int, int> phpBB forum_id => Synapsis-Forum-ID
     */
    private function importForums(array $rows, int $targetId, array &$stats): array
    {
        // Nach left_id sortieren, damit die Reihenfolge der Baumdarstellung passt.
        usort($rows, static fn ($a, $b) => (int) ($a['left_id'] ?? 0) <=> (int) ($b['left_id'] ?? 0));

        $map = [];
        $now = time();

        foreach ($rows as $row) {
            if (1 !== (int) ($row['forum_type'] ?? 0)) {
                continue;
            }

            $desc = trim(strip_tags(PhpbbTextConverter::toHtml((string) ($row['forum_desc'] ?? ''), (string) ($row['forum_desc_uid'] ?? ''))));

            $this->connection->insert('tl_synapsis_forum', [
                'pid' => $targetId,
                'tstamp' => $now,
                'sorting' => $this->sorting += 128,
                'type' => 'forum',
                'title' => (string) ($row['forum_name'] ?? ''),
                'alias' => $this->uniqueAlias('tl_synapsis_forum', (string) ($row['forum_name'] ?? '')),
                'description' => $desc,
                'closed' => 1 === (int) ($row['forum_status'] ?? 0) ? '1' : '',
                // Importierte Foren zunaechst oeffentlich lesbar schalten, damit die
                // uebernommenen Inhalte sichtbar sind; im Backend anpassbar.
                'guestRead' => '1',
                'published' => '1',
            ]);

            $map[(int) $row['forum_id']] = (int) $this->connection->lastInsertId();
            ++$stats['forums'];
        }

        return $map;
    }

    /**
     * Legt Themen an, deren Forum importiert wurde.
     *
     * @param array<int, array<string, string>> $rows
     * @param array<int, int>                   $forumMap
     * @param array<int, string>                $users
     * @param array<string, int>                $stats
     *
     * @return array<int, int> phpBB topic_id => Synapsis-Themen-ID
     */
    private function importTopics(array $rows, array $forumMap, array $users, array &$stats): array
    {
        $map = [];
        $now = time();

        foreach ($rows as $row) {
            $forumId = (int) ($row['forum_id'] ?? 0);

            if (!isset($forumMap[$forumId])) {
                continue;
            }

            $name = $this->resolveName((int) ($row['topic_poster'] ?? 0), (string) ($row['topic_first_poster_name'] ?? ''), $users);

            $this->connection->insert('tl_synapsis_topic', [
                'pid' => $forumMap[$forumId],
                'tstamp' => $now,
                'title' => (string) ($row['topic_title'] ?? ''),
                'alias' => $this->uniqueAlias('tl_synapsis_topic', (string) ($row['topic_title'] ?? '')),
                'author' => 0,
                'authorName' => $name,
                'date' => (int) ($row['topic_time'] ?? $now),
                'sticky' => (int) ($row['topic_type'] ?? 0) >= 1 ? '1' : '',
                'locked' => 1 === (int) ($row['topic_status'] ?? 0) ? '1' : '',
                'views' => (int) ($row['topic_views'] ?? 0),
                'published' => '1',
            ]);

            $map[(int) $row['topic_id']] = (int) $this->connection->lastInsertId();
            ++$stats['topics'];
        }

        return $map;
    }

    /**
     * Legt Beitraege an, deren Thema importiert wurde (chronologisch).
     *
     * @param array<int, array<string, string>> $rows
     * @param array<int, int>                   $topicMap
     * @param array<int, string>                $users
     * @param array<string, int>                $stats
     */
    private function importPosts(array $rows, array $topicMap, array $users, array &$stats): void
    {
        usort($rows, static fn ($a, $b) => (int) ($a['post_time'] ?? 0) <=> (int) ($b['post_time'] ?? 0) ?: (int) ($a['post_id'] ?? 0) <=> (int) ($b['post_id'] ?? 0));

        $now = time();

        foreach ($rows as $row) {
            $topicId = (int) ($row['topic_id'] ?? 0);

            if (!isset($topicMap[$topicId]) || !$this->isApproved($row)) {
                ++$stats['skipped'];

                continue;
            }

            $name = $this->resolveName((int) ($row['poster_id'] ?? 0), (string) ($row['post_username'] ?? ''), $users);
            $text = PhpbbTextConverter::toHtml((string) ($row['post_text'] ?? ''), (string) ($row['bbcode_uid'] ?? ''));

            $this->connection->insert('tl_synapsis_post', [
                'pid' => $topicMap[$topicId],
                'tstamp' => $now,
                'author' => 0,
                'authorName' => $name,
                'date' => (int) ($row['post_time'] ?? $now),
                'text' => $text,
                'published' => '1',
            ]);

            ++$stats['posts'];
        }
    }

    /**
     * Legt Umfragen an: Frage + Antwortmoeglichkeiten je Thema, und bildet die
     * gespeicherten Stimmenzahlen (poll_option_total) mit anonymen Stimmen nach.
     *
     * @param array<int, array<string, string>> $topics
     * @param array<int, int>                   $topicMap
     * @param array<int, array<string, string>> $pollOptions
     * @param array<string, int>                $stats
     */
    private function importPolls(array $topics, array $topicMap, array $pollOptions, array &$stats): void
    {
        // Antwortmoeglichkeiten nach topic_id gruppieren.
        $optionsByTopic = [];

        foreach ($pollOptions as $opt) {
            $tid = (int) ($opt['topic_id'] ?? 0);
            $optionsByTopic[$tid][] = $opt;
        }

        $now = time();

        foreach ($topics as $topic) {
            $phpbbTopicId = (int) ($topic['topic_id'] ?? 0);
            $question = trim((string) ($topic['poll_title'] ?? ''));

            if ('' === $question || !isset($topicMap[$phpbbTopicId]) || empty($optionsByTopic[$phpbbTopicId])) {
                continue;
            }

            $length = (int) ($topic['poll_length'] ?? 0);
            $start = (int) ($topic['poll_start'] ?? ($topic['topic_time'] ?? 0));

            $this->connection->insert('tl_synapsis_poll', [
                'pid' => $topicMap[$phpbbTopicId],
                'tstamp' => $now,
                'question' => $question,
                'multiple' => (int) ($topic['poll_max_options'] ?? 1) > 1 ? '1' : '',
                'closeDate' => $length > 0 ? $start + $length : 0,
                'hideResults' => '',
            ]);
            $pollId = (int) $this->connection->lastInsertId();
            ++$stats['polls'];

            $options = $optionsByTopic[$phpbbTopicId];
            usort($options, static fn ($a, $b) => (int) ($a['poll_option_id'] ?? 0) <=> (int) ($b['poll_option_id'] ?? 0));

            $sorting = 0;

            foreach ($options as $opt) {
                $this->connection->insert('tl_synapsis_poll_option', [
                    'pid' => $pollId,
                    'tstamp' => $now,
                    'sorting' => $sorting += 128,
                    'label' => (string) ($opt['poll_option_text'] ?? ''),
                ]);
                $choiceId = (int) $this->connection->lastInsertId();

                // Stimmenzahl mit anonymen Stimmen nachbilden (Ergebnis bleibt
                // erhalten). Sicherheitskappe gegen Ausreisser.
                $total = min((int) ($opt['poll_option_total'] ?? 0), 10000);

                for ($i = 0; $i < $total; ++$i) {
                    $this->connection->insert('tl_synapsis_poll_vote', [
                        'tstamp' => $now,
                        'poll' => $pollId,
                        'choice' => $choiceId,
                        'member' => $this->voteSeq++,
                    ]);
                    ++$stats['votes'];
                }
            }
        }
    }

    /**
     * Ist ein Beitrag sichtbar/freigegeben? Unterstuetzt beide phpBB-Spalten
     * (post_visibility ab 3.1, post_approved bei 3.0). Fehlen beide, gilt der
     * Beitrag als sichtbar.
     *
     * @param array<string, string> $row
     */
    private function isApproved(array $row): bool
    {
        if (\array_key_exists('post_visibility', $row) && '' !== $row['post_visibility']) {
            return 1 === (int) $row['post_visibility'];
        }

        if (\array_key_exists('post_approved', $row) && '' !== $row['post_approved']) {
            return 1 === (int) $row['post_approved'];
        }

        return true;
    }

    /**
     * Ermittelt den anzuzeigenden Namen: bei registrierten Nutzern der
     * Benutzername, bei Gaesten der gespeicherte Name; sonst "Gast".
     *
     * @param array<int, string> $users
     */
    private function resolveName(int $posterId, string $storedName, array $users): string
    {
        // 1 = ANONYMOUS in phpBB -> gespeicherter Gastname hat Vorrang.
        if ($posterId > 1 && isset($users[$posterId]) && '' !== $users[$posterId]) {
            return $users[$posterId];
        }

        $storedName = trim($storedName);

        if ('' !== $storedName) {
            return $storedName;
        }

        if (isset($users[$posterId]) && '' !== $users[$posterId]) {
            return $users[$posterId];
        }

        return 'Gast';
    }

    /**
     * Baut die Zuordnung user_id => username aus der phpBB-Benutzertabelle.
     *
     * @param array<int, array<string, string>> $rows
     *
     * @return array<int, string>
     */
    private function indexUsers(array $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $id = (int) ($row['user_id'] ?? 0);

            if ($id > 0) {
                $map[$id] = (string) ($row['username'] ?? '');
            }
        }

        return $map;
    }

    /**
     * Zerlegt CSV in assoziative Zeilen (fgetcsv, korrektes Parsen quotierter
     * Felder mit eingebetteten Zeilenumbruechen). Leerer Inhalt liefert [].
     *
     * @return array<int, array<string, string>>
     */
    private function parse(string $csv): array
    {
        if ('' === trim($csv)) {
            return [];
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csv);
        rewind($handle);

        $header = fgetcsv($handle, 0, ',', '"', '"');

        if (false === $header) {
            fclose($handle);

            return [];
        }

        $header = array_map('strval', $header);
        $count = \count($header);
        $rows = [];

        while (false !== ($line = fgetcsv($handle, 0, ',', '"', '"'))) {
            if (1 === \count($line) && (null === $line[0] || '' === $line[0])) {
                continue;
            }

            $rows[] = array_combine($header, array_pad(\array_slice($line, 0, $count), $count, ''));
        }

        fclose($handle);

        return $rows;
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
