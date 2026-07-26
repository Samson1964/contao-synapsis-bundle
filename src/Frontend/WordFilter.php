<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Frontend;

/**
 * Ersetzt konfigurierte Woerter in Beitraegen und Titeln (Wortfilter).
 *
 * Die Konfiguration ist eine Liste (eine Regel pro Zeile):
 *   - "wort"          -> das Wort wird durch gleich viele Sternchen ersetzt
 *   - "wort=ersatz"   -> das Wort wird durch "ersatz" ersetzt
 *
 * Verglichen wird als ganzes Wort (Wortgrenzen) und ohne Beachtung der
 * Gross-/Kleinschreibung. In HTML werden nur die Textteile gefiltert, nicht die
 * Tags/Attribute. Rein und ohne Framework-Abhaengigkeit (isoliert testbar).
 */
final class WordFilter
{
    /**
     * Regeln als [Suchwort, Ersatz].
     *
     * @var array<int, array{0: string, 1: string}>
     */
    private $rules;

    /**
     * @param array<int, array{0: string, 1: string}> $rules
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Baut den Filter aus der Textkonfiguration ("wort" oder "wort=ersatz" je
     * Zeile).
     */
    public static function fromConfig(string $config): self
    {
        $rules = [];

        foreach (preg_split('/\r\n|\r|\n/', $config) ?: [] as $line) {
            $line = trim($line);

            if ('' === $line) {
                continue;
            }

            if (false !== strpos($line, '=')) {
                [$word, $replacement] = explode('=', $line, 2);
                $word = trim($word);
                $replacement = trim($replacement);
            } else {
                $word = $line;
                // Ohne Ersatz: gleich viele Sternchen wie Zeichen im Wort.
                $replacement = str_repeat('*', mb_strlen($word));
            }

            if ('' !== $word) {
                $rules[] = [$word, $replacement];
            }
        }

        return new self($rules);
    }

    /**
     * Ist ueberhaupt eine Regel konfiguriert?
     */
    public function isActive(): bool
    {
        return [] !== $this->rules;
    }

    /**
     * Filtert reinen Text (z. B. Titel).
     */
    public function filterText(string $text): string
    {
        if ([] === $this->rules || '' === $text) {
            return $text;
        }

        foreach ($this->rules as [$word, $replacement]) {
            $text = preg_replace('/\b'.preg_quote($word, '/').'\b/iu', $replacement, $text) ?? $text;
        }

        return $text;
    }

    /**
     * Filtert HTML: nur die Textabschnitte zwischen den Tags werden ersetzt, die
     * Tags selbst (Namen, Attribute, URLs) bleiben unangetastet.
     */
    public function filterHtml(string $html): string
    {
        if ([] === $this->rules || '' === $html) {
            return $html;
        }

        // In Tags (< ... >) und Text zerlegen; nur die Textteile filtern.
        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$html];

        foreach ($parts as $i => $part) {
            if (0 === $i % 2) {
                $parts[$i] = $this->filterText($part);
            }
        }

        return implode('', $parts);
    }
}
