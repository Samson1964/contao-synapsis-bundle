<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\EventListener\DataContainer;

use Doctrine\DBAL\Connection;

/**
 * Callbacks der Modul-Konfiguration (tl_module).
 */
class ModuleListener
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Liefert die verfuegbaren Startpunkte zur Auswahl im Modul
     * (options_callback des Feldes synapsis_root).
     *
     * @return array<int, string>
     */
    public function getRootOptions(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, title FROM tl_synapsis_forum WHERE type = 'root' ORDER BY sorting"
        );

        $options = [];

        foreach ($rows as $row) {
            $options[(int) $row['id']] = $row['title'];
        }

        return $options;
    }
}
