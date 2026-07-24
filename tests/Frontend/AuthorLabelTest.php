<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Tests\Frontend;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSynapsisBundle\Frontend\AuthorLabel;

/**
 * Prueft die Anzeige des Autornamens fuer Mitglieder, Gaeste und geloeschte Konten.
 */
class AuthorLabelTest extends TestCase
{
    public function testAktivesMitgliedZeigtLiveNamen(): void
    {
        // Konto existiert -> aktueller Name schlaegt den gespeicherten Namen.
        $this->assertSame('Max Mustermann', AuthorLabel::format('Max Mustermann', 'mmuster'));
    }

    public function testGeloeschtesKontoZeigtGastMitFrueheremNamen(): void
    {
        // Kein Live-Name mehr (Konto geloescht), aber gespeicherter Benutzername.
        $this->assertSame('Gast (mmuster)', AuthorLabel::format(null, 'mmuster'));
    }

    public function testImportGastMitNamen(): void
    {
        // Fremdsystem-Import: author=0, Name aus der Quelle.
        $this->assertSame('Gast (phpBB-User)', AuthorLabel::format(null, 'phpBB-User'));
    }

    public function testGastOhneNamen(): void
    {
        $this->assertSame('Gast', AuthorLabel::format(null, ''));
    }

    public function testLeererLiveNameGiltAlsGast(): void
    {
        // Ein leerer/whitespace Live-Name darf nicht als Name durchgehen.
        $this->assertSame('Gast (alt)', AuthorLabel::format('   ', 'alt'));
    }

    public function testEigenesGastwort(): void
    {
        $this->assertSame('Besucher (x)', AuthorLabel::format(null, 'x', 'Besucher'));
    }

    public function testNamenWerdenGetrimmt(): void
    {
        $this->assertSame('Anna', AuthorLabel::format('  Anna  ', ''));
        $this->assertSame('Gast (live)', AuthorLabel::format(null, '  live  '));
    }
}
