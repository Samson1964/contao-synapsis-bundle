<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Tests\Frontend;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSynapsisBundle\Frontend\ForumAccess;

/**
 * Prueft Lese-/Schreibrechte und ihre Vererbung (Mitglieder und Gaeste).
 */
class ForumAccessTest extends TestCase
{
    private const GUEST = true;
    private const MEMBER = false;

    /**
     * @var ForumAccess
     */
    private $access;

    protected function setUp(): void
    {
        $this->access = new ForumAccess();
    }

    // --- Mitglieder -----------------------------------------------------------

    public function testMitgliedLiestUngeschuetztenBereich(): void
    {
        $chain = [$this->node()];

        $this->assertTrue($this->access->canRead($chain, self::MEMBER, [3]));
        $this->assertTrue($this->access->canWrite($chain, self::MEMBER, [3]));
    }

    public function testUnveroeffentlichtIstFuerNiemandenLesbar(): void
    {
        $chain = [$this->node(['published' => false])];

        $this->assertFalse($this->access->canRead($chain, self::MEMBER, [1, 2]));
        $this->assertFalse($this->access->canRead($chain, self::GUEST, []));
    }

    public function testGeschuetzterBereichNurMitPassenderGruppe(): void
    {
        $chain = [$this->node(['protected' => true, 'groups' => [5]])];

        $this->assertFalse($this->access->canRead($chain, self::MEMBER, [1, 2]));
        $this->assertTrue($this->access->canRead($chain, self::MEMBER, [5]));
        $this->assertTrue($this->access->canWrite($chain, self::MEMBER, [5]));
    }

    public function testSchutzWirdVomElternknotenGeerbt(): void
    {
        $chain = [
            $this->node(),                                          // Forum: offen
            $this->node(['protected' => true, 'groups' => [9]]),   // Kategorie: geschuetzt
        ];

        $this->assertFalse($this->access->canRead($chain, self::MEMBER, [3]));
        $this->assertTrue($this->access->canRead($chain, self::MEMBER, [9]));
    }

    // --- Gaeste ---------------------------------------------------------------

    public function testGastOhneFreigabeHatKeinenZugriff(): void
    {
        $chain = [$this->node()]; // ungeschuetzt, aber kein Gaeste-Flag

        $this->assertFalse($this->access->canRead($chain, self::GUEST, []));
        $this->assertFalse($this->access->canWrite($chain, self::GUEST, []));
    }

    public function testGastDarfNurLesen(): void
    {
        $chain = [$this->node(['guestRead' => true])];

        $this->assertTrue($this->access->canRead($chain, self::GUEST, []));
        $this->assertFalse($this->access->canWrite($chain, self::GUEST, []));
    }

    public function testGastDarfSchreibenSchliesstLesenEin(): void
    {
        $chain = [$this->node(['guestWrite' => true])];

        $this->assertTrue($this->access->canRead($chain, self::GUEST, []));
        $this->assertTrue($this->access->canWrite($chain, self::GUEST, []));
    }

    public function testGaesteFreigabeWirdVererbt(): void
    {
        // guestRead auf der Kategorie -> Forum darunter ist fuer Gaeste lesbar
        $chain = [
            $this->node(),                       // Forum: kein eigenes Flag
            $this->node(['guestRead' => true]),  // Kategorie: Gaeste duerfen lesen
        ];

        $this->assertTrue($this->access->canRead($chain, self::GUEST, []));
        $this->assertFalse($this->access->canWrite($chain, self::GUEST, []));
    }

    public function testGaesteSchreibrechtWirdVererbt(): void
    {
        $chain = [
            $this->node(),
            $this->node(['guestWrite' => true]),
        ];

        $this->assertTrue($this->access->canWrite($chain, self::GUEST, []));
    }

    // --- Zusammenspiel Mitglieder/Gaeste --------------------------------------

    public function testGaesteFreigabeMachtBereichOeffentlichLesbar(): void
    {
        // Geschuetzt fuer Gruppe 5, zusaetzlich guestRead: auch ein Mitglied
        // der falschen Gruppe (und ein Gast) darf lesen, aber nicht schreiben.
        $chain = [$this->node(['protected' => true, 'groups' => [5], 'guestRead' => true])];

        $this->assertTrue($this->access->canRead($chain, self::MEMBER, [1]));   // falsche Gruppe, aber oeffentlich
        $this->assertTrue($this->access->canRead($chain, self::GUEST, []));
        $this->assertFalse($this->access->canWrite($chain, self::MEMBER, [1])); // nur lesen
        $this->assertFalse($this->access->canWrite($chain, self::GUEST, []));
        $this->assertTrue($this->access->canWrite($chain, self::MEMBER, [5]));  // richtige Gruppe darf schreiben
    }

    /**
     * Baut einen Knoten-Datensatz; Vorgaben entsprechen einem offenen,
     * veroeffentlichten Knoten ohne Gaeste-Freigabe.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function node(array $overrides = []): array
    {
        $defaults = [
            'published' => true,
            'protected' => false,
            'groups' => null,
            'guestRead' => false,
            'guestWrite' => false,
        ];

        $node = array_merge($defaults, $overrides);

        return [
            'published' => $node['published'] ? '1' : '',
            'protected' => $node['protected'] ? '1' : '',
            'groups' => null === $node['groups'] ? null : serialize($node['groups']),
            'guestRead' => $node['guestRead'] ? '1' : '',
            'guestWrite' => $node['guestWrite'] ? '1' : '',
        ];
    }
}
