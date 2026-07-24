<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Frontend;

/**
 * Ermittelt den anzuzeigenden Autornamen eines Themas oder Beitrags.
 *
 * Reine Formatierungslogik ohne Datenbankzugriff, damit sie unabhaengig
 * testbar ist. Die Aufloesung von Mitglieds-ID zu Live-Name uebernimmt der
 * Aufrufer und uebergibt das Ergebnis als $liveName.
 */
final class AuthorLabel
{
    /**
     * @param string|null $liveName  Aktueller Anzeigename eines noch existierenden
     *                               Mitglieds; null bei Gaesten oder geloeschten Konten
     * @param string      $storedName Gespeicherter Benutzername (Momentaufnahme beim
     *                                Schreiben bzw. Name aus einem Fremdsystem-Import)
     * @param string      $guestWord  Bezeichnung fuer Gaeste (i. d. R. "Gast")
     */
    public static function format(?string $liveName, string $storedName, string $guestWord = 'Gast'): string
    {
        // Existiert das Mitglied noch, wird immer der aktuelle Name gezeigt.
        if (null !== $liveName && '' !== trim($liveName)) {
            return trim($liveName);
        }

        // Gast oder geloeschtes Konto: gespeicherten Namen in Klammern anhaengen.
        $storedName = trim($storedName);

        return '' !== $storedName ? sprintf('%s (%s)', $guestWord, $storedName) : $guestWord;
    }
}
