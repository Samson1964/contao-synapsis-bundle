<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Frontend;

/**
 * Ersetzt Platzhalter der Form ##name## in E-Mail-Vorlagen durch Werte.
 *
 * Bewusst ueber einfache Tokens statt sprintf: von Admins bearbeitbare Vorlagen
 * duerfen ein einzelnes "%" enthalten, ohne dass die Formatierung bricht, und
 * die Reihenfolge der Platzhalter ist frei.
 */
final class NotificationTemplate
{
    /**
     * @param array<string, string> $tokens Zuordnung Platzhaltername => Wert
     *                                      (ohne die ##); z. B. ['topic' => '…']
     */
    public static function render(string $template, array $tokens): string
    {
        $search = [];
        $replace = [];

        foreach ($tokens as $key => $value) {
            $search[] = '##'.$key.'##';
            $replace[] = (string) $value;
        }

        return str_replace($search, $replace, $template);
    }

    /**
     * Wandelt den HTML-Text eines Beitrags in Klartext fuer die Textmail um.
     *
     * Die Absatz- und Zeilenumbrueche des Editors bleiben dabei erhalten
     * (<br>, </p>, </div>, Listeneintraege), alles Uebrige wird entfernt und
     * HTML-Entitaeten werden aufgeloest. Mehr als eine Leerzeile am Stueck wird
     * zusammengefasst, damit die Mail nicht auseinanderfaellt.
     *
     * @param string $html Beitragstext, wie er in der Datenbank steht
     *
     * @return string Klartext ohne fuehrende/abschliessende Leerzeichen; leerer
     *                Text ergibt eine leere Zeichenkette
     */
    public static function plainText(string $html): string
    {
        // Listeneintraege als Aufzaehlung erkennbar halten (je eine Zeile).
        $text = preg_replace('#<li[^>]*>#i', "\n- ", $html) ?? $html;
        $text = preg_replace('#</li>#i', '', $text) ?? $text;

        // Zeilenumbrueche und Blockenden: ein Block endet mit einer Leerzeile,
        // damit Absaetze in der Mail als Absaetze lesbar bleiben.
        $text = preg_replace('#<br\s*/?>#i', "\n", $text) ?? $text;
        $text = preg_replace('#</(p|div|ul|ol|blockquote|h[1-6]|tr)>#i', "\n\n", $text) ?? $text;

        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Geschuetzte Leerzeichen und Wagenruecklaeufe vereinheitlichen.
        $text = str_replace(["\xC2\xA0", "\r\n", "\r"], [' ', "\n", "\n"], $text);

        // Leerzeichen am Zeilenende weg, mehrfache Leerzeilen zusammenfassen.
        $text = preg_replace('/[ \t]+\n/', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
