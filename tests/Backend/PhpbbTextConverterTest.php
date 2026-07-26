<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Tests\Backend;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSynapsisBundle\Backend\PhpbbTextConverter;

/**
 * Prueft die Umwandlung von phpBB-Beitragstext (Legacy-BBCode und XML) nach HTML.
 */
class PhpbbTextConverterTest extends TestCase
{
    public function testLeer(): void
    {
        $this->assertSame('', PhpbbTextConverter::toHtml('', ''));
        $this->assertSame('', PhpbbTextConverter::toHtml('   ', 'abc'));
    }

    public function testLegacyEinfacherText(): void
    {
        $this->assertSame('Hallo Welt', PhpbbTextConverter::toHtml('Hallo Welt', ''));
    }

    public function testLegacyFettKursivUnterstrichen(): void
    {
        $uid = '1a2b3c';
        $this->assertSame('<strong>x</strong>', PhpbbTextConverter::toHtml('[b:'.$uid.']x[/b:'.$uid.']', $uid));
        $this->assertSame('<em>x</em>', PhpbbTextConverter::toHtml('[i:'.$uid.']x[/i:'.$uid.']', $uid));
        $this->assertSame('<u>x</u>', PhpbbTextConverter::toHtml('[u:'.$uid.']x[/u:'.$uid.']', $uid));
    }

    public function testLegacyLink(): void
    {
        $uid = 'zz';
        $this->assertSame('<a href="https://example.com">Text</a>', PhpbbTextConverter::toHtml('[url=https://example.com:'.$uid.']Text[/url:'.$uid.']', $uid));
        $this->assertSame('<a href="https://x.de">https://x.de</a>', PhpbbTextConverter::toHtml('[url:'.$uid.']https://x.de[/url:'.$uid.']', $uid));
    }

    public function testLegacyZitat(): void
    {
        $uid = 'q1';
        $html = PhpbbTextConverter::toHtml('[quote="Max:'.$uid.'"]Hallo[/quote:'.$uid.']', $uid);
        $this->assertSame('<blockquote>Hallo</blockquote>', $html);
    }

    public function testLegacyListe(): void
    {
        $uid = 'l1';
        $html = PhpbbTextConverter::toHtml('[list:'.$uid.'][*:'.$uid.']Eins[*:'.$uid.']Zwei[/list:'.$uid.']', $uid);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>Eins', $html);
        $this->assertStringContainsString('<li>Zwei', $html);
        $this->assertStringContainsString('</ul>', $html);
    }

    public function testLegacySmileyBehaeltAlt(): void
    {
        $in = 'Hallo <!-- s:D --><img class="smilies" src="images/smilies/icon_e_biggrin.gif" alt=":D" title="Sehr glücklich" /><!-- s:D --> Welt';
        $html = PhpbbTextConverter::toHtml($in, '');
        $this->assertStringContainsString(':D', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<!--', $html);
    }

    public function testLegacyMagicUrlMarkerEntfernt(): void
    {
        $in = 'siehe <!-- m --><a class="postlink" href="https://x.de">https://x.de</a><!-- m --> dort';
        $html = PhpbbTextConverter::toHtml($in, '');
        $this->assertStringNotContainsString('<!-- m -->', $html);
        $this->assertStringContainsString('href="https://x.de"', $html);
    }

    public function testLegacyZeilenumbruch(): void
    {
        $this->assertSame("Zeile1<br>\nZeile2", PhpbbTextConverter::toHtml("Zeile1\nZeile2", ''));
    }

    public function testXmlPlainText(): void
    {
        $this->assertSame('Nur Text', PhpbbTextConverter::toHtml('<t>Nur Text</t>', ''));
    }

    public function testXmlFett(): void
    {
        $in = '<r><B><s>[b]</s>fett<e>[/b]</e></B></r>';
        $this->assertSame('<strong>fett</strong>', PhpbbTextConverter::toHtml($in, ''));
    }

    public function testXmlLink(): void
    {
        $in = '<r><URL url="https://example.com"><s>[url]</s>https://example.com<e>[/url]</e></URL></r>';
        $html = PhpbbTextConverter::toHtml($in, '');
        $this->assertSame('<a href="https://example.com">https://example.com</a>', $html);
    }

    public function testXmlZitat(): void
    {
        $in = '<r><QUOTE author="Max"><s>[quote=Max]</s>Hallo<e>[/quote]</e></QUOTE></r>';
        $this->assertSame('<blockquote>Hallo</blockquote>', PhpbbTextConverter::toHtml($in, ''));
    }

    public function testXmlBrUndUnbekannteTagsEntfernt(): void
    {
        $in = '<r>Zeile1<br/>Zeile2<UNKNOWN foo="1">x</UNKNOWN></r>';
        $html = PhpbbTextConverter::toHtml($in, '');
        $this->assertStringContainsString('Zeile1<br>Zeile2', $html);
        $this->assertStringNotContainsString('UNKNOWN', $html);
        $this->assertStringContainsString('x', $html);
    }

    public function testSicherheitScriptEntfernt(): void
    {
        $in = 'Hallo <script>alert(1)</script> Welt';
        $html = PhpbbTextConverter::toHtml($in, '');
        $this->assertStringNotContainsString('<script', $html);
    }

    public function testSicherheitOnAttributEntfernt(): void
    {
        $in = '<r><IMG src="x.jpg" onerror="alert(1)">y</r>';
        $html = PhpbbTextConverter::toHtml($in, '');
        $this->assertStringNotContainsString('onerror', $html);
    }
}
