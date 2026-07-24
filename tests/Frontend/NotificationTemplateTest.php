<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Tests\Frontend;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSynapsisBundle\Frontend\NotificationTemplate;

/**
 * Prueft die Platzhalter-Ersetzung der E-Mail-Vorlagen.
 */
class NotificationTemplateTest extends TestCase
{
    public function testErsetztAllePlatzhalter(): void
    {
        $out = NotificationTemplate::render(
            'Hallo ##name##, im Thema "##topic##": ##url##',
            ['name' => 'Anna', 'topic' => 'Schach', 'url' => 'https://example.com/t']
        );

        $this->assertSame('Hallo Anna, im Thema "Schach": https://example.com/t', $out);
    }

    public function testMehrfachesVorkommen(): void
    {
        $this->assertSame('a a a', NotificationTemplate::render('##x## ##x## ##x##', ['x' => 'a']));
    }

    public function testUnbekannterPlatzhalterBleibtStehen(): void
    {
        $this->assertSame('##foo## ok', NotificationTemplate::render('##foo## ##bar##', ['bar' => 'ok']));
    }

    public function testProzentzeichenBleibtUnveraendert(): void
    {
        // Genau der Fall, den sprintf brechen wuerde.
        $this->assertSame('100 % ##t##->X', NotificationTemplate::render('100 % ##t##->##topic##', ['topic' => 'X']));
    }

    public function testLeereVorlage(): void
    {
        $this->assertSame('', NotificationTemplate::render('', ['topic' => 'X']));
    }
}
