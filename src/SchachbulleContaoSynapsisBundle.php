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
}
