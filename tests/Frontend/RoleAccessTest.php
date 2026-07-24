<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Tests\Frontend;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSynapsisBundle\Frontend\RoleAccess;

/**
 * Prueft die vererbte Rollenzuweisung (Administrator/Moderator).
 */
class RoleAccessTest extends TestCase
{
    /**
     * @var RoleAccess
     */
    private $access;

    protected function setUp(): void
    {
        $this->access = new RoleAccess();
    }

    private function node(array $data = []): array
    {
        return array_merge(['adminGroups' => [], 'adminMembers' => [], 'modGroups' => [], 'modMembers' => []], $data);
    }

    public function testDefaultKeineRolle(): void
    {
        $chain = [$this->node(), $this->node()];
        $this->assertFalse($this->access->isAdmin($chain, [3], 7));
        $this->assertFalse($this->access->isModerator($chain, [3], 7));
    }

    public function testAdminUeberGruppe(): void
    {
        $chain = [$this->node(['adminGroups' => ['5']])];
        $this->assertTrue($this->access->isAdmin($chain, [5], 7));
        $this->assertFalse($this->access->isModerator($chain, [5], 7));
    }

    public function testModeratorUeberMitglied(): void
    {
        $chain = [$this->node(['modMembers' => ['7']])];
        $this->assertTrue($this->access->isModerator($chain, [3], 7));
        $this->assertFalse($this->access->isAdmin($chain, [3], 7));
        $this->assertFalse($this->access->isModerator($chain, [3], 8));
    }

    public function testVererbungVomStartpunkt(): void
    {
        // Rolle am Startpunkt (oben) vergeben -> gilt auch im Forum (unten).
        $chain = [
            $this->node(),                              // Forum
            $this->node(),                              // Kategorie
            $this->node(['adminMembers' => ['7']]),     // Startpunkt
        ];
        $this->assertTrue($this->access->isAdmin($chain, [1], 7));
    }

    public function testGaesteNie(): void
    {
        $chain = [$this->node(['adminMembers' => ['0'], 'modGroups' => ['5']])];
        $this->assertFalse($this->access->isAdmin($chain, [5], 0));
        $this->assertFalse($this->access->isModerator($chain, [5], 0));
    }

    public function testSerialisierteWerte(): void
    {
        $chain = [$this->node(['modGroups' => serialize(['5', '9']), 'adminMembers' => serialize(['7'])])];
        $this->assertTrue($this->access->isModerator($chain, [9], 1));
        $this->assertTrue($this->access->isAdmin($chain, [1], 7));
        $this->assertFalse($this->access->isModerator($chain, [1], 1));
    }
}
