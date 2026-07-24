<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Frontend;

/**
 * Wertet Lese- und Schreibrechte eines Forenknotens aus.
 *
 * Zwei getrennte Achsen, jeweils im Baum vererbt:
 *
 *   Mitglieder  ueber "protected" + "groups": ist ein Knoten (oder ein
 *               uebergeordneter Knoten) geschuetzt, muss das Mitglied einer der
 *               dort erlaubten Gruppen angehoeren. Ein Mitglied mit Lesezugriff
 *               auf ein (offenes) Forum darf dort auch schreiben.
 *
 *   Gaeste      ueber die Checkboxen "guestRead" und "guestWrite" (Opt-in):
 *               Gaeste duerfen NUR lesen, wenn irgendwo in der Kette guestRead
 *               (oder guestWrite) gesetzt ist, und nur schreiben, wenn irgendwo
 *               guestWrite gesetzt ist. Ohne gesetztes Flag haben Gaeste keinen
 *               Zugriff (fail-safe: vergessenes Haekchen haelt den Bereich
 *               privat). Der Mitglieder-Schutz spielt fuer Gaeste keine Rolle.
 *
 * Veroeffentlichung wird immer geprueft: ist ein Knoten der Kette unveroeffent-
 * licht, ist der Bereich fuer niemanden sichtbar.
 *
 * Die Klasse arbeitet ohne Datenbank- oder Framework-Abhaengigkeit, damit die
 * Vererbungsregeln isoliert testbar bleiben. Die Kette der Knoten (Zielknoten
 * unten bis Startpunkt oben) liefert das aufrufende Modul.
 */
class ForumAccess
{
    /**
     * Prueft den Lesezugriff auf eine Knotenkette.
     *
     * @param array<array<string, mixed>> $chain          Knoten von unten (Ziel) bis oben (Startpunkt); Schluessel: published, protected, groups, guestRead, guestWrite
     * @param bool                        $isGuest        Nicht angemeldeter Besucher
     * @param array<int>                  $memberGroupIds Gruppen des angemeldeten Mitglieds (bei Gaesten leer)
     */
    public function canRead(array $chain, bool $isGuest, array $memberGroupIds): bool
    {
        if ([] === $chain || !$this->isPublishedChain($chain)) {
            return false;
        }

        // Lesbar, wenn das Mitglied die Mitglieder-Schutzpruefung besteht ODER
        // der Bereich fuer Gaeste (und damit oeffentlich) freigegeben ist.
        $memberOk = !$isGuest && $this->memberAllowed($chain, $memberGroupIds);

        return $memberOk || $this->guestAllowed($chain, false);
    }

    /**
     * Prueft den Schreibzugriff auf eine Knotenkette (setzt Lesezugriff voraus).
     *
     * Geschlossene Foren bzw. gesperrte Themen pruefen die aufrufenden Stellen
     * gesondert; hier geht es rein um die Berechtigung.
     *
     * @param array<array<string, mixed>> $chain
     * @param array<int>                  $memberGroupIds
     */
    public function canWrite(array $chain, bool $isGuest, array $memberGroupIds): bool
    {
        if (!$this->canRead($chain, $isGuest, $memberGroupIds)) {
            return false;
        }

        // Schreiben darf, wer der erlaubten Mitgliedergruppe angehoert ODER wo
        // Gaeste ausdruecklich schreiben duerfen. Reiner Gaeste-Lesezugriff
        // (guestRead ohne guestWrite) berechtigt niemanden zum Schreiben.
        $memberOk = !$isGuest && $this->memberAllowed($chain, $memberGroupIds);

        return $memberOk || $this->guestAllowed($chain, true);
    }

    /**
     * Prueft ausschliesslich die Veroeffentlichung der gesamten Kette.
     *
     * @param array<array<string, mixed>> $chain
     */
    public function isPublishedChain(array $chain): bool
    {
        foreach ($chain as $node) {
            if (empty($node['published'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gast-Berechtigung: fuer das Lesen genuegt guestRead ODER guestWrite an
     * einem beliebigen Knoten der Kette, fuer das Schreiben ist guestWrite
     * noetig (Schreibrecht schliesst Lesen ein).
     *
     * @param array<array<string, mixed>> $chain
     */
    private function guestAllowed(array $chain, bool $write): bool
    {
        foreach ($chain as $node) {
            if (!empty($node['guestWrite'])) {
                return true;
            }

            if (!$write && !empty($node['guestRead'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mitglieder-Berechtigung: fuer jeden geschuetzten Knoten der Kette muss
     * das Mitglied einer der erlaubten Gruppen angehoeren.
     *
     * @param array<array<string, mixed>> $chain
     * @param array<int>                  $memberGroupIds
     */
    private function memberAllowed(array $chain, array $memberGroupIds): bool
    {
        foreach ($chain as $node) {
            if (empty($node['protected'])) {
                continue;
            }

            $allowed = $this->normalizeGroups($node['groups'] ?? null);

            if (empty($allowed) || [] === array_intersect($allowed, $memberGroupIds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Fuehrt den serialisierten oder bereits entpackten Gruppenwert in eine
     * Liste von Integer-IDs ueber.
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
