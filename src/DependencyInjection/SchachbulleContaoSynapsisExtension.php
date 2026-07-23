<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * DependencyInjection-Extension des Synapsis-Bundles.
 *
 * Wird ueber die Symfony-Namenskonvention gefunden (Bundle-Klassenname ohne
 * "Bundle"-Suffix + "Extension") und laedt die Service-Definitionen. Der Alias
 * lautet "schachbulle_contao_synapsis" - passend zum Service-ID-Praefix in der
 * services.yaml.
 */
class SchachbulleContaoSynapsisExtension extends Extension
{
    /**
     * Laedt die Service-Konfiguration des Bundles in den Container.
     *
     * @param array<mixed> $configs Zusammengefuehrte Bundle-Konfiguration (hier ungenutzt)
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yaml');
    }
}
