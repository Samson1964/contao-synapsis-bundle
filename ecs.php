<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

use Symplify\EasyCodingStandard\Config\ECSConfig;

/*
 * Konfiguration der Contao Coding Standards.
 *
 * Geprueft werden nur die eigenen PHP-Klassen unter src/ und tests/. Die
 * DCA- und Sprachdateien unter src/Resources/contao/ folgen bewusst dem
 * klassischen Contao-Stil (array()-Schreibweise) und bleiben ausgenommen.
 */
return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->import(__DIR__.'/vendor/contao/easy-coding-standard/config/contao.php');

    $ecsConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    $ecsConfig->skip([
        __DIR__.'/src/Resources/contao',
    ]);
};
