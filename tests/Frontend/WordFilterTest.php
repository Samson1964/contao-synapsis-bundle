<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Tests\Frontend;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSynapsisBundle\Frontend\WordFilter;

/**
 * Prueft den Wortfilter (Text und HTML).
 */
class WordFilterTest extends TestCase
{
    public function testLeereKonfigurationInaktiv(): void
    {
        $f = WordFilter::fromConfig('');
        $this->assertFalse($f->isActive());
        $this->assertSame('unveraendert', $f->filterText('unveraendert'));
    }

    public function testMaskierungMitSternchen(): void
    {
        $f = WordFilter::fromConfig('Mist');
        $this->assertTrue($f->isActive());
        $this->assertSame('So ein ****!', $f->filterText('So ein Mist!'));
    }

    public function testErsatzwort(): void
    {
        $f = WordFilter::fromConfig('doof=nett');
        $this->assertSame('Das ist nett', $f->filterText('Das ist doof'));
    }

    public function testOhneBeachtungDerGrossschreibung(): void
    {
        $f = WordFilter::fromConfig('mist=Mist');
        $this->assertSame('Mist Mist Mist', $f->filterText('Mist MIST mist'));
    }

    public function testNurGanzeWoerter(): void
    {
        // "Klasse" darf nicht durch das Wort "lasse" getroffen werden.
        $f = WordFilter::fromConfig('lasse=X');
        $this->assertSame('Klasse', $f->filterText('Klasse'));
        $this->assertSame('X das', $f->filterText('lasse das'));
    }

    public function testMehrereRegeln(): void
    {
        $f = WordFilter::fromConfig("doof=nett\nMist=Prima");
        $this->assertSame('nett und Prima', $f->filterText('doof und Mist'));
    }

    public function testHtmlTagsBleibenUnangetastet(): void
    {
        $f = WordFilter::fromConfig('spam=***');
        // "spam" im Text wird ersetzt, in href/Klassennamen NICHT.
        $in = '<a href="https://spam.example" class="spam-link">spam</a>';
        $out = $f->filterHtml($in);

        $this->assertStringContainsString('href="https://spam.example"', $out);
        $this->assertStringContainsString('class="spam-link"', $out);
        $this->assertStringContainsString('>***<', $out);
    }

    public function testHtmlTextWirdGefiltert(): void
    {
        $f = WordFilter::fromConfig('boese');
        $out = $f->filterHtml('<p>Ein <strong>boese</strong>s Wort</p>');
        $this->assertStringContainsString('<strong>*****</strong>', $out);
        $this->assertStringContainsString('<p>Ein ', $out);
    }
}
