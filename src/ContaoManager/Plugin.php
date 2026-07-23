<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Schachbulle\ContaoSynapsisBundle\SchachbulleContaoSynapsisBundle;

/**
 * Contao-Manager-Plugin zur Registrierung des Synapsis-Bundles.
 *
 * Ueber dieses Plugin erkennt der Contao Manager das Bundle automatisch und
 * laedt es nach dem Contao-Core-Bundle, damit dessen Dienste bereitstehen.
 */
class Plugin implements BundlePluginInterface
{
    /**
     * Registriert das Bundle im Contao-Kernel.
     *
     * @return array<BundleConfig>
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(SchachbulleContaoSynapsisBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
