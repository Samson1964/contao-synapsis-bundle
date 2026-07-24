<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Tests\EventListener\DataContainer;

use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSynapsisBundle\EventListener\DataContainer\ForumListener;

/**
 * Prueft die Strukturregeln der Forenstruktur.
 *
 * Getestet wird der options_callback des Feldes "type": Er entscheidet, welcher
 * Typ an welcher Stelle des Baums angelegt werden darf. Die Regeln sind der
 * Kern des Backend-Moduls - ohne sie koennten etwa Themen unter Kategorien
 * landen.
 */
class ForumListenerTest extends TestCase
{
    /**
     * Auf oberster Ebene ist ausschliesslich ein Startpunkt erlaubt.
     *
     * Geprueft werden die Schluessel (= gespeicherte Werte); die Labels stammen
     * aus der Sprachdatei, die im Unit-Test nicht geladen ist.
     */
    public function testNurStartpunktAufObersterEbene(): void
    {
        $listener = new ForumListener($this->mockConnection(null));

        $this->assertSame(['root'], array_keys($listener->getTypeOptions($this->mockDataContainer(1))));
    }

    /**
     * In einem Startpunkt sind ausschliesslich Kategorien erlaubt.
     */
    public function testNurKategorienImStartpunkt(): void
    {
        $listener = new ForumListener($this->mockConnection('root'));

        $this->assertSame(['category'], array_keys($listener->getTypeOptions($this->mockDataContainer(5))));
    }

    /**
     * Eine Kategorie darf nur Foren enthalten.
     */
    public function testNurForenInEinerKategorie(): void
    {
        $listener = new ForumListener($this->mockConnection('category'));

        $this->assertSame(['forum'], array_keys($listener->getTypeOptions($this->mockDataContainer(7))));
    }

    /**
     * In einem Forum sind keine weiteren Baumknoten erlaubt - die Themen haengen
     * als eigene Kindtabelle am Forum.
     */
    public function testKeineBaumknotenInEinemForum(): void
    {
        $listener = new ForumListener($this->mockConnection('forum'));

        $this->assertSame([], $listener->getTypeOptions($this->mockDataContainer(9)));
    }

    /**
     * Liefert eine Connection, die erst die pid und dann den Typ des
     * Elterndatensatzes zurueckgibt.
     *
     * @param string|null $parentType Typ des Elterndatensatzes (null = oberste Ebene)
     *
     * @return Connection&\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockConnection(?string $parentType): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchOne')
            ->willReturnCallback(
                static function (string $sql) use ($parentType) {
                    // Erste Abfrage: pid des aktuellen Datensatzes
                    if (false !== strpos($sql, 'SELECT pid')) {
                        return null === $parentType ? 0 : 42;
                    }

                    // Zweite Abfrage: Typ des Elterndatensatzes
                    return $parentType;
                }
            )
        ;

        return $connection;
    }

    /**
     * Der Data Container liefert seine ID ueber die magische Methode __get,
     * deshalb wird diese im Mock gestubbt.
     *
     * @return DataContainer&\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockDataContainer(int $id): DataContainer
    {
        $dc = $this->createMock(DataContainer::class);
        $dc
            ->method('__get')
            ->willReturnCallback(static fn (string $key) => 'id' === $key ? $id : null)
        ;

        return $dc;
    }
}
