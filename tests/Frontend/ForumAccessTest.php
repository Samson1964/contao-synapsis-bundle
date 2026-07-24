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

    // --- Gaeste ueber Checkboxen ---------------------------------------------

    public function testGastLiestOeffentlichesForum(): void
    {
        // Ungeschuetzt = oeffentlich: Gaeste lesen, schreiben aber nicht.
        $chain = [$this->node()];

        $this->assertTrue($this->access->canRead($chain, self::GUEST, []));
        $this->assertFalse($this->access->canWrite($chain, self::GUEST, []));
    }

    public function testGeschuetztesForumOhneGaesteFreigabeGesperrt(): void
    {
        // Nur fuer Mitgliedergruppe 5, keine Gaeste-Freigabe.
        $chain = [$this->node(['protected' => true, 'groups' => [5]])];

        $this->assertFalse($this->access->canRead($chain, self::GUEST, []));
    }

    public function testGuestReadCheckboxOeffnetGeschuetztesForum(): void
    {
        // Geschuetzt fuer Gruppe 5, aber guestRead -> Gaeste (und alle) lesen.
        $chain = [$this->node(['protected' => true, 'groups' => [5], 'guestRead' => true])];

        $this->assertTrue($this->access->canRead($chain, self::GUEST, []));
        $this->assertFalse($this->access->canWrite($chain, self::GUEST, []));
        $this->assertTrue($this->access->canRead($chain, self::MEMBER, [1]));   // falsche Gruppe, aber oeffentlich lesbar
        $this->assertFalse($this->access->canWrite($chain, self::MEMBER, [1])); // nur lesen
        $this->assertTrue($this->access->canWrite($chain, self::MEMBER, [5]));  // richtige Gruppe schreibt
    }

    public function testGuestWriteCheckboxSchliesstLesenEin(): void
    {
        $chain = [$this->node(['protected' => true, 'groups' => [5], 'guestWrite' => true])];

        $this->assertTrue($this->access->canRead($chain, self::GUEST, []));
        $this->assertTrue($this->access->canWrite($chain, self::GUEST, []));
    }

    public function testGuestReadWirdVererbt(): void
    {
        $chain = [
            $this->node(['protected' => true, 'groups' => [5]]),   // Forum: mitgliederpflichtig
            $this->node(['guestRead' => true]),                    // Kategorie: Gaeste duerfen lesen
        ];

        $this->assertTrue($this->access->canRead($chain, self::GUEST, []));
        $this->assertFalse($this->access->canWrite($chain, self::GUEST, []));
    }

    // --- Gaeste-Gruppe (-1) hat Vorrang vor den Checkboxen -------------------

    public function testGaesteGruppeGewaehrtLesezugriff(): void
    {
        // Geschuetzt fuer Gruppe 5 UND Gaeste (-1): Gaeste lesen, schreiben nicht.
        $chain = [$this->node(['protected' => true, 'groups' => [5, -1]])];

        $this->assertTrue($this->access->canRead($chain, self::GUEST, []));
        $this->assertFalse($this->access->canWrite($chain, self::GUEST, []));
    }

    public function testGaesteGruppeSchlaegtSchreibCheckbox(): void
    {
        // Gaeste-Gruppe (-1) UND guestWrite gesetzt: die Gruppe hat Vorrang,
        // Gaeste bleiben nur-lesend (guestWrite ohne Wirkung).
        $chain = [$this->node(['protected' => true, 'groups' => [-1], 'guestWrite' => true])];

        $this->assertTrue($this->access->canRead($chain, self::GUEST, []));
        $this->assertFalse($this->access->canWrite($chain, self::GUEST, []));
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
