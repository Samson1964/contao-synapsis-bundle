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
}
