<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Tests\Frontend;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSynapsisBundle\Frontend\BBCode;

/**
 * Prueft die BB-Code-Umwandlung fuer Signaturen - inklusive XSS-Sicherheit.
 */
class BBCodeTest extends TestCase
{
    public function testFettKursivUnterstrichen(): void
    {
        $this->assertSame('<strong>x</strong>', BBCode::toHtml('[b]x[/b]'));
        $this->assertSame('<em>x</em>', BBCode::toHtml('[i]x[/i]'));
        $this->assertStringContainsString('text-decoration:underline', BBCode::toHtml('[u]x[/u]'));
        $this->assertStringContainsString('line-through', BBCode::toHtml('[s]x[/s]'));
    }

    public function testLinkMitText(): void
    {
        $html = BBCode::toHtml('[url=https://example.com]Beispiel[/url]');

        $this->assertStringContainsString('<a href="https://example.com"', $html);
        $this->assertStringContainsString('>Beispiel</a>', $html);
        $this->assertStringContainsString('rel="nofollow noopener"', $html);
    }

    public function testReinerLink(): void
    {
        $html = BBCode::toHtml('[url]http://example.com[/url]');

        $this->assertStringContainsString('<a href="http://example.com"', $html);
        $this->assertStringContainsString('>http://example.com</a>', $html);
    }

    public function testFarbeHexUndName(): void
    {
        $this->assertStringContainsString('color:#f00', BBCode::toHtml('[color=#f00]x[/color]'));
        $this->assertStringContainsString('color:red', BBCode::toHtml('[color=red]x[/color]'));
    }

    public function testZeilenumbruch(): void
    {
        $this->assertStringContainsString('<br', BBCode::toHtml("a\nb"));
    }

    public function testHtmlWirdMaskiert(): void
    {
        $html = BBCode::toHtml('<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testJavascriptUrlWirdNichtVerlinkt(): void
    {
        // Kein http(s):// -> keine Umwandlung in einen Link.
        $html = BBCode::toHtml('[url=javascript:alert(1)]x[/url]');

        $this->assertStringNotContainsString('<a ', $html);
        $this->assertStringNotContainsString('javascript:alert(1)"', $html);
    }

    public function testAusbruchsversuchInUrlBleibtHarmlos(): void
    {
        // Anfuehrungszeichen werden vor der Ersetzung maskiert und koennen das
        // Attribut nicht schliessen; es entsteht kein onmouseover-Attribut.
        $html = BBCode::toHtml('[url=https://x" onmouseover="alert(1)]y[/url]');

        $this->assertStringNotContainsString(' onmouseover="alert(1)"', $html);
    }

    public function testUngueltigeFarbeWirdNichtErsetzt(): void
    {
        // ";" ist nicht erlaubt -> es entsteht KEIN style-Attribut; der Text
        // bleibt als harmloser Klartext stehen (keine Style-Injektion).
        $html = BBCode::toHtml('[color=red;background:blue]x[/color]');

        $this->assertStringNotContainsString('<span style=', $html);
        $this->assertStringContainsString('[color=red;background:blue]', $html);
    }

    public function testKombination(): void
    {
        $html = BBCode::toHtml('Hallo [b]Welt[/b] und [i]mehr[/i]');

        $this->assertStringContainsString('Hallo <strong>Welt</strong> und <em>mehr</em>', $html);
    }

    public function testContaoEntitiesWerdenErkannt(): void
    {
        // Contao speichert "=" als &#61; - der Link muss trotzdem erkannt werden.
        $html = BBCode::toHtml('[url&#61;https://example.com]Test[/url]');

        $this->assertStringContainsString('<a href="https://example.com"', $html);
        $this->assertStringContainsString('>Test</a>', $html);
    }

    public function testFarbeMitContaoEntities(): void
    {
        // "=" -> &#61; und "#" -> &#35;
        $html = BBCode::toHtml('[color&#61;&#35;ff0000]rot[/color]');

        $this->assertStringContainsString('color:#ff0000', $html);
        $this->assertStringContainsString('>rot</span>', $html);
    }

    public function testInsertTagsWerdenNeutralisiert(): void
    {
        $html = BBCode::toHtml('Hallo {{php::echo 1}} Welt');

        // Kein aktives Insert-Tag mehr im Ausgabetext.
        $this->assertStringNotContainsString('{{php', $html);
        $this->assertStringContainsString('&#123;&#123;', $html);
    }
}
