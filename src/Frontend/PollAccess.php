<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Frontend;

/**
 * Entscheidet, ob ein Mitglied in einem Forum eine Umfrage erstellen darf.
 *
 * Das Recht wird an Startpunkt, Kategorie oder Forum vergeben (Mitgliedergruppen
 * und/oder einzelne Mitglieder) und vererbt sich nach unten: Erlaubt ein
 * beliebiger Knoten der Kette (Ziel bis Startpunkt) das Mitglied - ueber eine
 * seiner Gruppen oder direkt -, darf es dort Umfragen anlegen. Ist nirgends in
 * der Kette etwas vergeben, darf niemand (Default: gesperrt). Gaeste nie.
 *
 * Ohne Datenbank-/Framework-Abhaengigkeit, damit die Vererbung isoliert testbar
 * bleibt.
 */
class PollAccess
{
    /**
     * @param array<array<string, mixed>> $chain          Knoten von unten (Ziel) bis oben (Startpunkt); Schluessel: pollGroups, pollMembers
     * @param array<int>                  $memberGroupIds Gruppen des Mitglieds
     * @param int                         $memberId       ID des Mitglieds (0 = Gast)
     */
    public function canCreate(array $chain, array $memberGroupIds, int $memberId): bool
    {
        if ($memberId <= 0) {
            return false;
        }

        foreach ($chain as $node) {
            $groups = $this->normalize($node['pollGroups'] ?? null);

            if ([] !== array_intersect($groups, $memberGroupIds)) {
                return true;
            }

            $members = $this->normalize($node['pollMembers'] ?? null);

            if (\in_array($memberId, $members, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fuehrt einen serialisierten oder bereits entpackten Wert in eine Liste
     * von Integer-IDs ueber.
     *
     * @param mixed $value
     *
     * @return array<int>
     */
    private function normalize($value): array
    {
        if (\is_string($value)) {
            $unserialized = @unserialize($value, ['allowed_classes' => false]);
            $value = false === $unserialized && 'b:0;' !== $value ? $value : $unserialized;
        }

        if (!\is_array($value)) {
            return [];
        }

        return array_map('intval', array_filter($value, static fn ($v) => '' !== $v && null !== $v));
    }
}
