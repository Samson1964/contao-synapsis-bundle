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
 * Prueft die Vererbung von Veroeffentlichung und Zugriffsschutz.
 */
class ForumAccessTest extends TestCase
{
    /**
     * @var ForumAccess
     */
    private $access;

    protected function setUp(): void
    {
        $this->access = new ForumAccess();
    }

    /**
     * Ein veroeffentlichter, ungeschuetzter Knoten ist frei zugaenglich.
     */
    public function testOeffentlicherKnotenIstZugaenglich(): void
    {
        $chain = [$this->node(true, false, null)];

        $this->assertTrue($this->access->isAccessible($chain, []));
    }

    /**
     * Ein unveroeffentlichter Knoten ist fuer niemanden sichtbar.
     */
    public function testUnveroeffentlichterKnotenIstGesperrt(): void
    {
        $chain = [$this->node(false, false, null)];

        $this->assertFalse($this->access->isAccessible($chain, [1, 2, 3]));
    }

    /**
     * Ein geschuetzter Knoten ohne passende Gruppe bleibt gesperrt.
     */
    public function testGeschuetzterKnotenOhneGruppeGesperrt(): void
    {
        $chain = [$this->node(true, true, [5])];

        $this->assertFalse($this->access->isAccessible($chain, [1, 2]));
    }

    /**
     * Mit passender Gruppe ist der geschuetzte Knoten zugaenglich.
     */
    public function testGeschuetzterKnotenMitGruppeFrei(): void
    {
        $chain = [$this->node(true, true, [5, 8])];

        $this->assertTrue($this->access->isAccessible($chain, [8]));
    }

    /**
     * Ein geschuetzter Knoten ganz ohne erlaubte Gruppen ist fuer niemanden
     * zugaenglich.
     */
    public function testGeschuetzterKnotenOhneErlaubteGruppen(): void
    {
        $chain = [$this->node(true, true, [])];

        $this->assertFalse($this->access->isAccessible($chain, [1, 2, 3]));
    }

    /**
     * Der Schutz eines uebergeordneten Knotens wird an die Kinder vererbt.
     */
    public function testSchutzWirdVomElternknotenGeerbt(): void
    {
        $chain = [
            $this->node(true, false, null), // Zielforum: offen
            $this->node(true, true, [9]),   // Kategorie: geschuetzt
        ];

        $this->assertFalse($this->access->isAccessible($chain, [3]));
        $this->assertTrue($this->access->isAccessible($chain, [9]));
    }

    /**
     * Eine unveroeffentlichte Kategorie verbirgt das darunter liegende Forum.
     */
    public function testUnveroeffentlichterElternknotenVerbirgtKind(): void
    {
        $chain = [
            $this->node(true, false, null),  // Forum: veroeffentlicht
            $this->node(false, false, null), // Kategorie: unveroeffentlicht
        ];

        $this->assertFalse($this->access->isAccessible($chain, []));
        $this->assertFalse($this->access->isPublishedChain($chain));
    }

    /**
     * Serialisierte Gruppenwerte (wie aus der Datenbank) werden korrekt
     * ausgewertet.
     */
    public function testSerialisierteGruppenWerdenAkzeptiert(): void
    {
        $chain = [$this->node(true, true, null)];
        $chain[0]['groups'] = serialize(['7']);

        $this->assertTrue($this->access->isAccessible($chain, [7]));
        $this->assertFalse($this->access->isAccessible($chain, [4]));
    }

    /**
     * Baut einen Knoten-Datensatz fuer die Kette.
     *
     * @param array<int>|null $groups
     *
     * @return array<string, mixed>
     */
    private function node(bool $published, bool $protected, ?array $groups): array
    {
        return [
            'published' => $published ? '1' : '',
            'protected' => $protected ? '1' : '',
            'groups' => null === $groups ? null : serialize($groups),
        ];
    }
}
