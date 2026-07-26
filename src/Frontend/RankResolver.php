<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Frontend;

/**
 * Ermittelt die Rangstufe eines Mitglieds anhand seiner Beitragszahl.
 *
 * Die Stufen werden als Liste "Mindestbeitraege|Titel" konfiguriert (eine pro
 * Zeile). Fehlt eine Konfiguration, gelten sinnvolle Standardstufen. Rein und
 * ohne Framework-Abhaengigkeit, damit die Logik isoliert testbar bleibt.
 */
final class RankResolver
{
    /**
     * Standardstufen (Mindestbeitraege => Titel).
     *
     * @var array<int, string>
     */
    private const DEFAULTS = [
        0 => 'Neuling',
        10 => 'Mitglied',
        50 => 'Stammgast',
        150 => 'Erfahren',
        500 => 'Veteran',
    ];

    /**
     * Stufen als [Mindestbeitraege, Titel], absteigend nach Mindestbeitraegen.
     *
     * @var array<int, array{0: int, 1: string}>
     */
    private $ranks;

    /**
     * @param array<int, array{0: int, 1: string}> $ranks
     */
    public function __construct(array $ranks)
    {
        // Absteigend sortieren, damit die erste passende Stufe die hoechste ist.
        usort($ranks, static fn ($a, $b) => $b[0] <=> $a[0]);
        $this->ranks = $ranks;
    }

    /**
     * Baut den Resolver aus der Textkonfiguration ("min|Titel" je Zeile). Leere
     * oder unbrauchbare Konfiguration liefert die Standardstufen.
     */
    public static function fromConfig(string $config): self
    {
        $ranks = [];

        foreach (preg_split('/\r\n|\r|\n/', trim($config)) ?: [] as $line) {
            $line = trim($line);

            if ('' === $line || false === strpos($line, '|')) {
                continue;
            }

            [$min, $title] = explode('|', $line, 2);
            $title = trim($title);

            if ('' !== $title && is_numeric(trim($min))) {
                $ranks[] = [(int) trim($min), $title];
            }
        }

        if ([] === $ranks) {
            foreach (self::DEFAULTS as $min => $title) {
                $ranks[] = [$min, $title];
            }
        }

        return new self($ranks);
    }

    /**
     * Liefert den Titel fuer eine Beitragszahl (leerer String, wenn keine Stufe
     * greift).
     */
    public function titleFor(int $postCount): string
    {
        foreach ($this->ranks as [$min, $title]) {
            if ($postCount >= $min) {
                return $title;
            }
        }

        return '';
    }
}
