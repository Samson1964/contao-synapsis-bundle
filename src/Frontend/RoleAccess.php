<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Frontend;

/**
 * Bestimmt die Rolle (Administrator/Moderator) eines Mitglieds in einem Forum.
 *
 * Die Rollen werden an Startpunkt, Kategorie oder Forum ueber Mitgliedergruppen
 * und/oder einzelne Mitglieder vergeben und vererben sich nach unten: Weist ein
 * beliebiger Knoten der Kette (Ziel bis Startpunkt) das Mitglied - ueber eine
 * seiner Gruppen oder direkt - der Rolle zu, gilt sie dort. Gaeste nie.
 *
 * Ohne Datenbank-/Framework-Abhaengigkeit, damit die Vererbung isoliert testbar
 * bleibt.
 */
class RoleAccess
{
    /**
     * Ist das Mitglied Administrator (Felder adminGroups/adminMembers)?
     *
     * @param array<array<string, mixed>> $chain
     * @param array<int>                  $memberGroupIds
     */
    public function isAdmin(array $chain, array $memberGroupIds, int $memberId): bool
    {
        return $this->granted($chain, $memberGroupIds, $memberId, 'adminGroups', 'adminMembers');
    }

    /**
     * Ist das Mitglied Moderator (Felder modGroups/modMembers)?
     *
     * @param array<array<string, mixed>> $chain
     * @param array<int>                  $memberGroupIds
     */
    public function isModerator(array $chain, array $memberGroupIds, int $memberId): bool
    {
        return $this->granted($chain, $memberGroupIds, $memberId, 'modGroups', 'modMembers');
    }

    /**
     * Prueft, ob eines der beiden Felder (Gruppen/Mitglieder) das Mitglied
     * irgendwo in der Kette zulaesst.
     *
     * @param array<array<string, mixed>> $chain
     * @param array<int>                  $memberGroupIds
     */
    private function granted(array $chain, array $memberGroupIds, int $memberId, string $groupsKey, string $membersKey): bool
    {
        if ($memberId <= 0) {
            return false;
        }

        foreach ($chain as $node) {
            if ([] !== array_intersect($this->normalize($node[$groupsKey] ?? null), $memberGroupIds)) {
                return true;
            }

            if (\in_array($memberId, $this->normalize($node[$membersKey] ?? null), true)) {
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
