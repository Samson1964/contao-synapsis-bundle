<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Frontend;

/**
 * Wertet die Sichtbarkeit und den Zugriffsschutz eines Forenknotens aus.
 *
 * Veroeffentlichung und Zugriffsschutz werden in der Baumstruktur vererbt:
 * Ein Knoten ist nur sichtbar, wenn er selbst und alle uebergeordneten Knoten
 * veroeffentlicht sind; er ist nur zugaenglich, wenn das Mitglied fuer jeden
 * geschuetzten Knoten der Kette (Knoten selbst und Elternknoten) mindestens
 * einer der erlaubten Mitgliedergruppen angehoert.
 *
 * Die Klasse arbeitet bewusst ohne Datenbank- oder Framework-Abhaengigkeit,
 * damit die Vererbungsregeln isoliert testbar bleiben. Die Kette der Knoten
 * liefert das aufrufende Modul.
 */
class ForumAccess
{
    /**
     * Prueft, ob eine Knotenkette fuer die angegebenen Mitgliedergruppen
     * sichtbar und zugaenglich ist.
     *
     * @param array<array<string, mixed>> $chain          Knoten von unten (Zielknoten) bis oben (Startpunkt); jeder Eintrag mit den Schluesseln "published", "protected" und "groups"
     * @param array<int>                  $memberGroupIds IDs der Gruppen des angemeldeten Mitglieds (leer = nicht angemeldet)
     */
    public function isAccessible(array $chain, array $memberGroupIds): bool
    {
        foreach ($chain as $node) {
            if (!$this->isPublished($node)) {
                return false;
            }

            if (!$this->isUnlocked($node, $memberGroupIds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Prueft ausschliesslich die Veroeffentlichung der gesamten Kette.
     *
     * @param array<array<string, mixed>> $chain
     */
    public function isPublishedChain(array $chain): bool
    {
        foreach ($chain as $node) {
            if (!$this->isPublished($node)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $node
     */
    private function isPublished(array $node): bool
    {
        return (bool) ($node['published'] ?? false);
    }

    /**
     * Ein Knoten ist freigeschaltet, wenn er nicht geschuetzt ist oder das
     * Mitglied einer der erlaubten Gruppen angehoert.
     *
     * @param array<string, mixed> $node
     * @param array<int>           $memberGroupIds
     */
    private function isUnlocked(array $node, array $memberGroupIds): bool
    {
        if (empty($node['protected'])) {
            return true;
        }

        $allowed = $this->normalizeGroups($node['groups'] ?? null);

        if (empty($allowed)) {
            // Geschuetzt, aber ohne erlaubte Gruppe => fuer niemanden zugaenglich
            return false;
        }

        return [] !== array_intersect($allowed, $memberGroupIds);
    }

    /**
     * Fuehrt den in der Datenbank serialisierten oder bereits entpackten
     * Gruppenwert in eine Liste von Integer-IDs ueber.
     *
     * @param mixed $groups
     *
     * @return array<int>
     */
    private function normalizeGroups($groups): array
    {
        if (is_string($groups)) {
            $unserialized = @unserialize($groups, ['allowed_classes' => false]);
            $groups = false === $unserialized && 'b:0;' !== $groups ? $groups : $unserialized;
        }

        if (!is_array($groups)) {
            return [];
        }

        return array_map('intval', array_filter($groups, static fn ($value) => '' !== $value && null !== $value));
    }
}
