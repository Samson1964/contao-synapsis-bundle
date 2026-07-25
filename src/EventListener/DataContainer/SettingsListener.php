<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\Controller;
use Contao\Database;
use Contao\Input;

/**
 * Sorgt dafuer, dass die globalen Foreneinstellungen als einzelner Datensatz
 * (id=1) existieren, und leitet die Modulseite direkt in dessen
 * Bearbeiten-Dialog um - so verhaelt sich das Modul wie eine Einstellungsseite.
 */
class SettingsListener
{
    /**
     * @param mixed $dc Data-Container (hier ungenutzt)
     */
    public function onLoad($dc = null): void
    {
        $db = Database::getInstance();

        $exists = $db->prepare('SELECT id FROM tl_synapsis_settings WHERE id = 1')->execute()->numRows;

        if (!$exists) {
            $db->prepare('INSERT INTO tl_synapsis_settings %s')
                ->set([
                    'id' => 1,
                    'tstamp' => time(),
                    'notifyEnabled' => '1',
                    'notifySubject' => 'Neue Antwort im Thema "##topic##"',
                    'notifyBody' => "Hallo ##name##,\n\nim Thema \"##topic##\" wurde eine neue Antwort verfasst.\n\n##url##\n",
                    'modCanPin' => '1',
                    'modCanLock' => '1',
                    'modCanMove' => '1',
                    'modCanEditPosts' => '1',
                    'teamNotifyOn' => 'both',
                    'teamSubject' => 'Forum: neuer Beitrag im Thema "##topic##"',
                    'teamBody' => "Im Forum \"##forum##\" hat ##author## im Thema \"##topic##\" geschrieben.\n\n##url##\n",
                ])
                ->execute()
            ;
        }

        // Von der Listenansicht direkt in die Bearbeitung des einzigen Satzes.
        if ('edit' !== Input::get('act')) {
            Controller::redirect(Backend::addToUrl('act=edit&id=1'));
        }
    }
}
