<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Haupt-Bundle-Klasse des Synapsis-Forums.
 *
 * Bindet das Bundle in den Symfony-/Contao-Kernel ein. Die DependencyInjection-
 * Extension wird ueber die Symfony-Namenskonvention automatisch gefunden.
 */
class SchachbulleContaoSynapsisBundle extends Bundle
{
    /**
     * Prueft anhand der installierten Version, ob Contao 5 (oder neuer) laeuft.
     *
     * Wird von den DCA-Dateien genutzt, um Treiberklasse und Operationsleiste
     * versionsgerecht zu setzen. Die fruehere Erkennung ueber method_exists()
     * war unzuverlaessig, weil einzelne Methoden bereits in Contao 4.13
     * existieren.
     */
    public static function isContao5(): bool
    {
        if (!class_exists(\Composer\InstalledVersions::class)) {
            return false;
        }

        return version_compare(
            (string) \Composer\InstalledVersions::getVersion('contao/core-bundle'),
            '5.0.0',
            '>='
        );
    }
}
