<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Tests\Frontend;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSynapsisBundle\Frontend\RankResolver;

/**
 * Prueft die Ermittlung der Rangstufe anhand der Beitragszahl.
 */
class RankResolverTest extends TestCase
{
    public function testStandardstufen(): void
    {
        $r = RankResolver::fromConfig('');

        $this->assertSame('Neuling', $r->titleFor(0));
        $this->assertSame('Neuling', $r->titleFor(9));
        $this->assertSame('Mitglied', $r->titleFor(10));
        $this->assertSame('Stammgast', $r->titleFor(50));
        $this->assertSame('Erfahren', $r->titleFor(149 + 1));
        $this->assertSame('Veteran', $r->titleFor(1000));
    }

    public function testEigeneKonfiguration(): void
    {
        $r = RankResolver::fromConfig("0|Gast\n5|Aktiv\n100|Profi");

        $this->assertSame('Gast', $r->titleFor(0));
        $this->assertSame('Aktiv', $r->titleFor(5));
        $this->assertSame('Aktiv', $r->titleFor(99));
        $this->assertSame('Profi', $r->titleFor(100));
    }

    public function testUnsortierteKonfigurationWirdGeordnet(): void
    {
        $r = RankResolver::fromConfig("100|Profi\n0|Gast\n5|Aktiv");

        $this->assertSame('Profi', $r->titleFor(100));
        $this->assertSame('Gast', $r->titleFor(0));
    }

    public function testUnbrauchbareZeilenWerdenIgnoriert(): void
    {
        $r = RankResolver::fromConfig("0|Gast\nkaputt\n\n50|Rang ohne Zahl richtig|Extra");

        $this->assertSame('Gast', $r->titleFor(0));
        // "50|Rang ohne Zahl richtig|Extra" -> min=50, Titel="Rang ohne Zahl richtig|Extra"
        $this->assertSame('Rang ohne Zahl richtig|Extra', $r->titleFor(50));
    }

    public function testKeinePassendeStufe(): void
    {
        // Wenn die niedrigste Stufe > 0 ist, kann eine Beitragszahl darunter leer sein.
        $r = RankResolver::fromConfig('5|Aktiv');

        $this->assertSame('', $r->titleFor(0));
        $this->assertSame('Aktiv', $r->titleFor(5));
    }
}
