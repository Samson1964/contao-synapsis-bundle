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
 * Verwaltet Umfragen: Anlegen zu einem Thema, Abstimmen und Auswertung.
 *
 * Single Choice (multiple=false) speichert je Mitglied genau eine Stimme,
 * Multiple Choice eine je gewaehlter Option. Wer bereits abgestimmt hat, kann
 * nicht erneut abstimmen. Gaeste (memberId <= 0) koennen nicht abstimmen.
 */
final class PollManager
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
     * Legt eine Umfrage zu einem Thema an. Erwartet eine Frage, mindestens zwei
     * nicht-leere Antwortmoeglichkeiten und ein Enddatum (Zeitpunkt, ab dem
     * nicht mehr abgestimmt werden kann); sonst wird nichts angelegt (0).
     *
     * @param array<string> $options
     * @param int           $closeDate    Umfrageende als Zeitstempel (> 0 Pflicht)
     * @param bool          $hideResults  Ergebnisse erst nach Umfrageende zeigen
     */
    public function create(int $topicId, string $question, bool $multiple, array $options, int $closeDate, bool $hideResults): int
    {
        $question = trim($question);
        $options = array_values(array_filter(array_map('trim', $options), static fn ($o) => '' !== $o));

        if ($topicId <= 0 || '' === $question || \count($options) < 2 || $closeDate <= 0) {
            return 0;
        }

        $now = time();

        $this->connection->insert('tl_synapsis_poll', [
            'pid' => $topicId,
            'tstamp' => $now,
            'question' => mb_substr($question, 0, 255),
            'multiple' => $multiple ? '1' : '',
            'closeDate' => $closeDate,
            'hideResults' => $hideResults ? '1' : '',
        ]);
        $pollId = (int) $this->connection->lastInsertId();

        $sorting = 0;

        foreach ($options as $label) {
            $this->connection->insert('tl_synapsis_poll_option', [
                'pid' => $pollId,
                'tstamp' => $now,
                'sorting' => $sorting += 128,
                'label' => mb_substr($label, 0, 255),
            ]);
        }

        return $pollId;
    }

    /**
     * Umfrage eines Themas (oder null).
     *
     * @return array<string, mixed>|null
     */
    public function findByTopic(int $topicId): ?array
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM tl_synapsis_poll WHERE pid = ? ORDER BY id', [$topicId]);

        return $row ?: null;
    }

    /**
     * Antwortmoeglichkeiten einer Umfrage inkl. Stimmenzahl.
     *
     * @return array<int, array{id:int, label:string, votes:int}>
     */
    public function options(int $pollId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT o.id, o.label, (SELECT COUNT(*) FROM tl_synapsis_poll_vote v WHERE v.choice = o.id) AS votes'
            .' FROM tl_synapsis_poll_option o WHERE o.pid = ? ORDER BY o.sorting, o.id',
            [$pollId]
        );

        return array_map(
            static fn (array $r): array => [
                'id' => (int) $r['id'],
                'label' => (string) $r['label'],
                'votes' => (int) $r['votes'],
            ],
            $rows
        );
    }

    /**
     * Anzahl der Mitglieder, die in dieser Umfrage abgestimmt haben.
     */
    public function totalVoters(int $pollId): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(DISTINCT member) FROM tl_synapsis_poll_vote WHERE poll = ?', [$pollId]);
    }

    /**
     * Hat das Mitglied in dieser Umfrage bereits abgestimmt?
     */
    public function hasVoted(int $pollId, int $memberId): bool
    {
        if ($memberId <= 0) {
            return false;
        }

        return false !== $this->connection->fetchOne('SELECT id FROM tl_synapsis_poll_vote WHERE poll = ? AND member = ?', [$pollId, $memberId]);
    }

    /**
     * Speichert die Stimme(n) eines Mitglieds. Bei Single Choice zaehlt nur die
     * erste gewaehlte Option. Liefert false, wenn ungueltig oder bereits
     * abgestimmt.
     *
     * @param array<int> $optionIds Gewaehlte Antwort-IDs
     */
    public function vote(int $pollId, int $memberId, array $optionIds): bool
    {
        if ($memberId <= 0) {
            return false;
        }

        $poll = $this->connection->fetchAssociative('SELECT id, multiple, closeDate FROM tl_synapsis_poll WHERE id = ?', [$pollId]);

        if (!$poll || $this->hasVoted($pollId, $memberId)) {
            return false;
        }

        // Nach Umfrageende ist keine Stimmabgabe mehr moeglich.
        if ($this->hasEnded($poll)) {
            return false;
        }

        // Nur gueltige Optionen dieser Umfrage zulassen.
        $validIds = array_map('intval', $this->connection->fetchFirstColumn('SELECT id FROM tl_synapsis_poll_option WHERE pid = ?', [$pollId]));
        $optionIds = array_values(array_intersect(array_map('intval', $optionIds), $validIds));

        if ([] === $optionIds) {
            return false;
        }

        // Single Choice: nur die erste Wahl uebernehmen.
        if ('1' !== (string) $poll['multiple']) {
            $optionIds = [$optionIds[0]];
        }

        $now = time();

        foreach ($optionIds as $choiceId) {
            $this->connection->insert('tl_synapsis_poll_vote', [
                'tstamp' => $now,
                'poll' => $pollId,
                'choice' => $choiceId,
                'member' => $memberId,
            ]);
        }

        return true;
    }

    /**
     * Ist die Umfrage beendet (Enddatum gesetzt und erreicht)?
     *
     * @param array<string, mixed> $poll Datensatz mit Schluessel "closeDate"
     */
    public function hasEnded(array $poll): bool
    {
        $closeDate = (int) ($poll['closeDate'] ?? 0);

        return $closeDate > 0 && time() >= $closeDate;
    }
}
