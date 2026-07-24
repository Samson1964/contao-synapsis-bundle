<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Tests\Frontend;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSynapsisBundle\Frontend\PollAccess;

/**
 * Prueft das (vererbte) Recht, Umfragen zu erstellen.
 */
class PollAccessTest extends TestCase
{
    /**
     * @var PollAccess
     */
    private $access;

    protected function setUp(): void
    {
        $this->access = new PollAccess();
    }

    private function node(array $groups = [], array $members = []): array
    {
        return ['pollGroups' => $groups, 'pollMembers' => $members];
    }

    public function testDefaultGesperrt(): void
    {
        // Nichts vergeben -> niemand darf.
        $chain = [$this->node(), $this->node()];
        $this->assertFalse($this->access->canCreate($chain, [3], 7));
    }

    public function testGruppeErlaubt(): void
    {
        $chain = [$this->node(['5'])];
        $this->assertTrue($this->access->canCreate($chain, [5], 7));
        $this->assertFalse($this->access->canCreate($chain, [3], 7));
    }

    public function testEinzelnesMitgliedErlaubt(): void
    {
        $chain = [$this->node([], ['7'])];
        $this->assertTrue($this->access->canCreate($chain, [3], 7));
        $this->assertFalse($this->access->canCreate($chain, [3], 8));
    }

    public function testVererbungVomStartpunkt(): void
    {
        // Recht am Startpunkt (oben) vergeben -> gilt auch im Forum (unten).
        $chain = [
            $this->node(),          // Forum (Ziel)
            $this->node(),          // Kategorie
            $this->node(['9']),     // Startpunkt
        ];
        $this->assertTrue($this->access->canCreate($chain, [9], 7));
    }

    public function testVereinigungUeberDieKette(): void
    {
        // Startpunkt erlaubt Gruppe 9, Forum erlaubt Mitglied 7 zusaetzlich.
        $chain = [
            $this->node([], ['7']),
            $this->node(),
            $this->node(['9']),
        ];
        $this->assertTrue($this->access->canCreate($chain, [1], 7)); // ueber Mitglied
        $this->assertTrue($this->access->canCreate($chain, [9], 5)); // ueber Gruppe
        $this->assertFalse($this->access->canCreate($chain, [1], 5)); // weder noch
    }

    public function testGaesteNie(): void
    {
        $chain = [$this->node(['5'], ['0'])];
        $this->assertFalse($this->access->canCreate($chain, [5], 0));
        $this->assertFalse($this->access->canCreate($chain, [5], -1));
    }

    public function testSerialisierteWerte(): void
    {
        // Wie aus der Datenbank (Contao-Blob).
        $chain = [['pollGroups' => serialize(['5', '9']), 'pollMembers' => serialize(['7'])]];
        $this->assertTrue($this->access->canCreate($chain, [9], 1));
        $this->assertTrue($this->access->canCreate($chain, [1], 7));
        $this->assertFalse($this->access->canCreate($chain, [1], 1));
    }
}
