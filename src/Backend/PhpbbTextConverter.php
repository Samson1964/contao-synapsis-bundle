<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Backend;

/**
 * Wandelt phpBB-Beitragstext in HTML fuer Synapsis um.
 *
 * phpBB speichert Beitraege in zwei Formaten:
 *   - Legacy (phpBB 3.0): BBCode mit angehaengter bbcode_uid, z. B.
 *     "[b:1a2b3c]fett[/b:1a2b3c]"; Smilies/Links als HTML-Kommentar-Marker.
 *   - XML (phpBB 3.1+): "<r>...<B><s>[b]</s>fett<e>[/b]</e></B>...</r>" bzw.
 *     "<t>reiner Text</t>".
 *
 * Beide werden auf ein schlankes, sicheres HTML abgebildet (fett, kursiv,
 * unterstrichen, Zitat, Link, Bild, Liste, Code, Farbe, Zeilenumbrueche).
 * Unbekannte Auszeichnungen werden entfernt, der Text bleibt erhalten.
 *
 * Rein und ohne Framework-Abhaengigkeit, damit die Umwandlung isoliert testbar
 * bleibt.
 */
final class PhpbbTextConverter
{
    public static function toHtml(string $text, string $uid = ''): string
    {
        if ('' === trim($text)) {
            return '';
        }

        $html = self::isXml($text) ? self::fromXml($text) : self::fromLegacy($text, $uid);

        return self::harden($html);
    }

    /**
     * Erkennt das XML-Format von phpBB 3.1+ (Wurzelelement <r> oder <t>).
     */
    private static function isXml(string $text): bool
    {
        return 1 === preg_match('#^\s*<[rt][ >/]#', $text);
    }

    /**
     * Wandelt das phpBB-3.1+-XML in HTML um.
     */
    private static function fromXml(string $text): string
    {
        $s = $text;

        // Marker-Tags entfernen (sie enthalten die literalen BBCode-Zeichen wie
        // "[b]" bzw. Ignorier-Whitespace) - der eigentliche Inhalt steckt im
        // Elementnamen.
        $s = preg_replace('#<[se]>.*?</[se]>#s', '', $s) ?? $s;
        $s = preg_replace('#<i>.*?</i>#s', '', $s) ?? $s;
        $s = preg_replace('#<(?:s|e|i)/>#', '', $s) ?? $s;

        // Links und Bilder (mit Attributen)
        $s = preg_replace('#<URL url="([^"]*)">#i', '<a href="$1">', $s) ?? $s;
        $s = preg_replace('#</URL>#i', '</a>', $s) ?? $s;
        $s = preg_replace('#<EMAIL[^>]*>(.*?)</EMAIL>#is', '$1', $s) ?? $s;
        $s = preg_replace('#<IMG[^>]*\bsrc="([^"]*)"[^>]*>#i', '<img src="$1" alt="">', $s) ?? $s;

        // Farbe
        $s = preg_replace('#<COLOR color="([^"]*)">#i', '<span style="color:$1">', $s) ?? $s;
        $s = preg_replace('#</COLOR>#i', '</span>', $s) ?? $s;

        // Einfache Auszeichnungen
        foreach (['B' => 'strong', 'I' => 'em', 'U' => 'u'] as $from => $to) {
            $s = preg_replace('#<'.$from.'>#', '<'.$to.'>', $s) ?? $s;
            $s = preg_replace('#</'.$from.'>#', '</'.$to.'>', $s) ?? $s;
        }

        // Bloecke
        $s = preg_replace('#<QUOTE[^>]*>#i', '<blockquote>', $s) ?? $s;
        $s = preg_replace('#</QUOTE>#i', '</blockquote>', $s) ?? $s;
        $s = preg_replace('#<LIST[^>]*>#i', '<ul>', $s) ?? $s;
        $s = preg_replace('#</LIST>#i', '</ul>', $s) ?? $s;
        $s = preg_replace('#<LI>#i', '<li>', $s) ?? $s;
        $s = preg_replace('#</LI>#i', '</li>', $s) ?? $s;
        $s = preg_replace('#<CODE[^>]*>#i', '<pre>', $s) ?? $s;
        $s = preg_replace('#</CODE>#i', '</pre>', $s) ?? $s;

        // Emoji/Smiley: sichtbaren Text behalten
        $s = preg_replace('#<E[^>]*>(.*?)</E>#is', '$1', $s) ?? $s;

        // Zeilenumbrueche
        $s = preg_replace('#<br\s*/?>#i', '<br>', $s) ?? $s;

        // Wurzel- und alle uebrigen phpBB-Tags (Grossbuchstaben) entfernen,
        // Text behalten.
        $s = preg_replace('#</?[rt]>#', '', $s) ?? $s;
        $s = preg_replace('#</?[A-Z][A-Z0-9]*[^>]*>#', '', $s) ?? $s;

        return trim($s);
    }

    /**
     * Wandelt Legacy-BBCode (mit bbcode_uid) in HTML um.
     */
    private static function fromLegacy(string $text, string $uid): string
    {
        $s = $text;

        // Smilies als <img class="smilies" ... alt=":D" ...> -> alt behalten
        $s = preg_replace('#<img[^>]*class="[^"]*smilies[^"]*"[^>]*alt="([^"]*)"[^>]*/?>#i', '$1', $s) ?? $s;
        // phpBB-Kommentar-Marker (m = magic url, s: = smiley, e = email, l = local)
        $s = preg_replace('#<!-- [a-z][^>]*? -->#', '', $s) ?? $s;

        // bbcode_uid entfernen -> aus "[b:uid]" wird "[b]"
        if ('' !== $uid) {
            $s = str_replace(':'.$uid, '', $s);
        }

        // Listeneintraege
        $s = str_replace('[*]', '<li>', $s);

        // Paarige BBCodes
        $s = preg_replace('#\[b\](.*?)\[/b\]#is', '<strong>$1</strong>', $s) ?? $s;
        $s = preg_replace('#\[i\](.*?)\[/i\]#is', '<em>$1</em>', $s) ?? $s;
        $s = preg_replace('#\[u\](.*?)\[/u\]#is', '<u>$1</u>', $s) ?? $s;
        $s = preg_replace('#\[url=(?:&quot;)?([^\]"]*?)(?:&quot;)?\](.*?)\[/url\]#is', '<a href="$1">$2</a>', $s) ?? $s;
        $s = preg_replace('#\[url\](.*?)\[/url\]#is', '<a href="$1">$1</a>', $s) ?? $s;
        $s = preg_replace('#\[img\](.*?)\[/img\]#is', '<img src="$1" alt="">', $s) ?? $s;
        $s = preg_replace('#\[color=([^\]]*)\](.*?)\[/color\]#is', '<span style="color:$1">$2</span>', $s) ?? $s;
        $s = preg_replace('#\[code\](.*?)\[/code\]#is', '<pre>$1</pre>', $s) ?? $s;
        $s = preg_replace('#\[size=[^\]]*\](.*?)\[/size\]#is', '$1', $s) ?? $s;
        // Zitat mit optionalem Autor
        $s = preg_replace('#\[quote(?:=(?:&quot;)?[^\]]*?(?:&quot;)?)?\]#is', '<blockquote>', $s) ?? $s;
        $s = preg_replace('#\[/quote\]#is', '</blockquote>', $s) ?? $s;
        // Listen
        $s = preg_replace('#\[list(?:=[^\]]*)?\]#is', '<ul>', $s) ?? $s;
        $s = preg_replace('#\[/list\]#is', '</ul>', $s) ?? $s;

        // Uebrige unbekannte BBCodes entfernen
        $s = preg_replace('#\[/?[a-z*][^\]]*\]#i', '', $s) ?? $s;

        // Zeilenumbrueche in <br> wandeln (ohne XHTML-Slash)
        $s = nl2br($s, false);

        return trim($s);
    }

    /**
     * Entfernt gefaehrliche Elemente aus dem Ergebnis (Defensive; der Import ist
     * zwar Administrator-getrieben, der Ursprungstext aber Nutzereingabe).
     */
    private static function harden(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? $html;
        $html = preg_replace('#(href|src)\s*=\s*("javascript:[^"]*"|\'javascript:[^\']*\')#i', '$1="#"', $html) ?? $html;

        return $html;
    }
}
