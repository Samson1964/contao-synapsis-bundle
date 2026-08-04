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
 *   Gaeste      werden - wie in Contao ueblich - als Gruppe -1 behandelt. Ist
 *               die Gaeste-Gruppe in den erlaubten Gruppen eines geschuetzten
 *               Knotens enthalten, duerfen Gaeste dort LESEN (nie schreiben);
 *               diese Freigabe hat Vorrang vor den Checkboxen. Zusaetzlich gibt
 *               es die Checkboxen "guestRead"/"guestWrite" (Opt-in): sie
 *               greifen dort, wo der Zugriff nicht bereits ueber die
 *               Gaeste-Gruppe geregelt ist. guestRead = lesen, guestWrite =
 *               lesen und schreiben.
 *
 * "Gaeste duerfen lesen" bedeutet oeffentlich lesbar - dann duerfen auch
 * angemeldete Mitglieder ohne passende Gruppe lesen (aber nicht schreiben).
 *
 * WICHTIG: Ein geschuetzter Knoten, dessen Gruppen die Gaeste-Gruppe NICHT
 * enthalten, blockiert den oeffentlichen Zugriff fuer seinen gesamten
 * Teilbereich. Die Checkboxen an untergeordneten Knoten koennen einen solchen
 * uebergeordneten Schutz NICHT aufheben (sonst wuerde z. B. ein importiertes
 * Forum mit "Gaeste duerfen lesen" einen geschuetzten Startpunkt oeffnen).
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
     * ID der fiktiven Contao-Gruppe "Gaeste".
     */
    private const GUEST_GROUP = -1;

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

        // Gaeste gelten als Gruppe -1. Lesbar, wenn die Gruppenpruefung besteht
        // (Mitglied in erlaubter Gruppe bzw. Gast ueber die Gaeste-Gruppe) ODER
        // eine Gaeste-Checkbox den Bereich oeffentlich lesbar macht - Letzteres
        // aber nur, wenn kein geschuetzter Knoten der Kette Gaeste ausschliesst
        // (ein Kind kann einen uebergeordneten Schutz nicht aufheben).
        $effectiveGroups = $isGuest ? [self::GUEST_GROUP] : $memberGroupIds;

        return $this->memberAllowed($chain, $effectiveGroups)
            || (!$this->guestBlocked($chain) && $this->guestAllowed($chain, false));
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

        if ($isGuest) {
            // Regelt die Gaeste-Gruppe den Zugriff (geschuetzter Knoten mit -1),
            // sind Gaeste dort ausdruecklich nur-lesend - die Schreib-Checkbox
            // bleibt ohne Wirkung. Sonst zaehlt "Gaeste duerfen schreiben".
            $governedByGuestGroup = $this->isProtectedChain($chain)
                && $this->memberAllowed($chain, [self::GUEST_GROUP]);

            return !$governedByGuestGroup && $this->guestAllowed($chain, true);
        }

        // Mitglied: schreibt, wer einer erlaubten Gruppe angehoert oder wo Gaeste
        // (und damit alle) schreiben duerfen.
        return $this->memberAllowed($chain, $memberGroupIds) || $this->guestAllowed($chain, true);
    }

    /**
     * Ist mindestens ein Knoten der Kette geschuetzt?
     *
     * @param array<array<string, mixed>> $chain
     */
    private function isProtectedChain(array $chain): bool
    {
        foreach ($chain as $node) {
            if (!empty($node['protected'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Schliesst ein geschuetzter Knoten der Kette Gaeste aus? Das tut er, wenn
     * er Gaeste weder ueber die Gaeste-Gruppe (-1) noch ueber eine EIGENE
     * Checkbox (guestRead/guestWrite) zulaesst. Dann ist der gesamte
     * Teilbereich nicht oeffentlich - Checkboxen ANDERER Knoten (ob ueber-
     * oder untergeordnet) koennen diesen Schutz nicht aufheben. So kann weder
     * ein Kind einen geschuetzten Startpunkt oeffnen (z. B. ein importiertes
     * Forum mit guestRead) noch eine offene Kategorie ein Forum, das sich
     * selbst auf Mitgliedergruppen beschraenkt.
     *
     * @param array<array<string, mixed>> $chain
     */
    private function guestBlocked(array $chain): bool
    {
        foreach ($chain as $node) {
            if (empty($node['protected'])) {
                continue;
            }

            // Eigene Freigabe des geschuetzten Knotens (bewusste Entscheidung
            // des Verantwortlichen) - dieser Knoten blockiert nicht.
            if (!empty($node['guestRead']) || !empty($node['guestWrite'])) {
                continue;
            }

            if (!\in_array(self::GUEST_GROUP, $this->normalizeGroups($node['groups'] ?? null), true)) {
                return true;
            }
        }

        return false;
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
