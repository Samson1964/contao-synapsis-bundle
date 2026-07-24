<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Frontend;

/**
 * Wandelt einen kleinen, sicheren Satz an BB-Code in HTML um (fuer Signaturen).
 *
 * Sicherheit: Der Text wird ZUERST vollstaendig HTML-maskiert, danach werden
 * nur wohlgeformte BB-Code-Marken durch festes HTML ersetzt. So kann kein
 * eingegebenes HTML/JavaScript ausbrechen; URLs muessen mit http(s):// beginnen.
 *
 * Unterstuetzt: [b] [i] [u] [s], [url]…[/url], [url=…]…[/url], [color=…]…[/color].
 */
final class BBCode
{
    public static function toHtml(string $text): string
    {
        // 1. Alles maskieren - danach existiert kein aktives HTML mehr.
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // 2. Einfache Formatierungen.
        $simple = [
            '#\[b\](.*?)\[/b\]#is' => '<strong>$1</strong>',
            '#\[i\](.*?)\[/i\]#is' => '<em>$1</em>',
            '#\[u\](.*?)\[/u\]#is' => '<span style="text-decoration:underline">$1</span>',
            '#\[s\](.*?)\[/s\]#is' => '<span style="text-decoration:line-through">$1</span>',
        ];

        $text = preg_replace(array_keys($simple), array_values($simple), $text);

        // 3. Links mit eigenem Text: [url=http://ziel]Text[/url]
        $text = preg_replace_callback(
            '#\[url=(https?://[^\]\s"]+)\](.*?)\[/url\]#is',
            static fn (array $m): string => '<a href="'.$m[1].'" rel="nofollow noopener" target="_blank">'.$m[2].'</a>',
            $text
        );

        // 4. Reine Links: [url]http://ziel[/url]
        $text = preg_replace_callback(
            '#\[url\](https?://[^\[\s"]+)\[/url\]#is',
            static fn (array $m): string => '<a href="'.$m[1].'" rel="nofollow noopener" target="_blank">'.$m[1].'</a>',
            $text
        );

        // 5. Farbe: [color=#abc] oder [color=red]. Nur Hex oder reine Buchstaben.
        $text = preg_replace_callback(
            '#\[color=(\#[0-9a-fA-F]{3,6}|[a-zA-Z]{1,20})\](.*?)\[/color\]#is',
            static fn (array $m): string => '<span style="color:'.$m[1].'">'.$m[2].'</span>',
            $text
        );

        // 6. Zeilenumbrueche erhalten.
        return nl2br($text, false);
    }
}
