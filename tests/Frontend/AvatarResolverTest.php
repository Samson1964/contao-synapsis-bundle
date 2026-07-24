<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Tests\Frontend;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSynapsisBundle\Frontend\AvatarResolver;

/**
 * Prueft das Avatar-Markup: Fallback-Icon, Bild-URL und fertiges Bild-Markup.
 */
class AvatarResolverTest extends TestCase
{
    public function testFallbackOhneExternenAvatar(): void
    {
        $html = AvatarResolver::render(5, null);

        $this->assertStringContainsString('synapsis-avatar', $html);
        $this->assertStringContainsString('<svg', $html);
        $this->assertStringNotContainsString('synapsis-avatar--img', $html);
    }

    public function testLeererExternerAvatarFaelltZurueck(): void
    {
        $this->assertStringContainsString('<svg', AvatarResolver::render(5, ''));
        $this->assertStringContainsString('<svg', AvatarResolver::render(5, '   '));
    }

    public function testBildUrlWirdZuImg(): void
    {
        $html = AvatarResolver::render(5, 'https://example.com/avatar.jpg');

        $this->assertStringContainsString('synapsis-avatar--img', $html);
        $this->assertStringContainsString('<img src="https://example.com/avatar.jpg"', $html);
        $this->assertStringNotContainsString('<svg', $html);
    }

    public function testFertigesBildMarkupWirdUebernommen(): void
    {
        $html = AvatarResolver::render(5, '<img src="x.jpg" alt="Avatar">');

        $this->assertStringContainsString('synapsis-avatar--img', $html);
        $this->assertStringContainsString('<img src="x.jpg" alt="Avatar">', $html);
    }

    public function testPictureMarkupWirdUebernommen(): void
    {
        $html = AvatarResolver::render(5, '<picture><img src="x.webp"></picture>');

        $this->assertStringContainsString('<picture>', $html);
    }

    public function testUrlWirdMaskiert(): void
    {
        // Anfuehrungszeichen in der URL duerfen das Attribut nicht sprengen.
        $html = AvatarResolver::render(5, 'a.jpg" onerror="alert(1)');

        $this->assertStringNotContainsString('onerror="alert(1)"', $html);
        $this->assertStringContainsString('&quot;', $html);
    }

    public function testGastBekommtFallback(): void
    {
        $this->assertStringContainsString('<svg', AvatarResolver::render(0, null));
    }
}
