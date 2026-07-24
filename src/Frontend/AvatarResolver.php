<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Frontend;

/**
 * Erzeugt das Avatar-Markup eines Mitglieds.
 *
 * Ist das Bundle terminal42/contao-avatar installiert und liefert es fuer das
 * Mitglied ein Bild, wird dieses verwendet; sonst ein farbiges Standard-Icon
 * (Lucide "circle-user-round"). Die Aufloesung des externen Avatars uebernimmt
 * der Aufrufer und uebergibt das Ergebnis als $external - so bleibt die
 * Markup-Logik ohne Framework testbar.
 */
final class AvatarResolver
{
    /**
     * @param int         $memberId Mitglieds-ID (0 = Gast)
     * @param string|null $external Ergebnis des externen Avatar-Bundles: leer,
     *                              eine Bild-URL oder fertiges Bild-Markup
     */
    public static function render(int $memberId, ?string $external = null): string
    {
        $external = null !== $external ? trim($external) : '';

        if ('' !== $external) {
            // Fertiges Bild-Markup unveraendert uebernehmen ...
            if (false !== stripos($external, '<img') || false !== stripos($external, '<picture')) {
                return '<span class="synapsis-avatar synapsis-avatar--img">'.$external.'</span>';
            }

            // ... sonst als Bildquelle behandeln.
            return '<span class="synapsis-avatar synapsis-avatar--img">'
                .'<img src="'.htmlspecialchars($external, ENT_QUOTES).'" alt="" loading="lazy">'
                .'</span>';
        }

        return self::lucide($memberId);
    }

    /**
     * Farbiges Standard-Icon, deterministisch aus der Mitglieds-ID eingefaerbt.
     */
    private static function lucide(int $memberId): string
    {
        $hue = ($memberId * 47) % 360;
        $color = 'hsl('.$hue.', 55%, 45%)';

        return '<span class="synapsis-avatar" style="background:'.$color.'">'
            .'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
            .'<path d="M18 20a6 6 0 0 0-12 0"/><circle cx="12" cy="10" r="4"/><circle cx="12" cy="12" r="10"/>'
            .'</svg></span>'
        ;
    }
}
