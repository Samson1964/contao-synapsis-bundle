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
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;

/**
 * Sorgt dafuer, dass die globalen Foreneinstellungen als einzelner Datensatz
 * (id=1) existieren, und leitet die Modulseite direkt in dessen
 * Bearbeiten-Dialog um - so verhaelt sich das Modul wie eine Einstellungsseite.
 */
class SettingsListener
{
    /**
     * Waehlbare Farbschemata: Schluessel => [Bezeichnung, Akzent, Akzent dunkel].
     * Muss zu den CSS-Klassen (synapsis-scheme--<key>) und der Auswahlliste des
     * Feldes colorScheme passen.
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const SCHEMES = [
        '' => ['Standard (Blau)', '#2f6f9f', '#21506f'],
        'petrol' => ['Petrol', '#0d4a63', '#062f40'],
        'gold' => ['Gold', '#a67c00', '#6c5700'],
        'rot' => ['Rot', '#b23a2e', '#7f2820'],
        'orange' => ['Orange', '#f47c00', '#c96500'],
    ];

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

    /**
     * Rendert unter dem Auswahlfeld "colorScheme" ein anklickbares Raster mit
     * Farbvorschauen (wizard-Callback) - analog zum Lucide-Icon-Wizard. Ein Klick
     * auf eine Vorschau setzt den Wert des Auswahlfelds "ctrl_colorScheme".
     */
    public function colorSchemeWizard(?DataContainer $dc = null): string
    {
        $buttons = '';

        foreach (self::SCHEMES as $key => [$label, $accent, $dark]) {
            $buttons .= '<button type="button" data-scheme="'.$key.'" title="'.StringUtil::specialchars($label).'">'
                .'<span class="syn-swatch" style="background:linear-gradient(180deg,'.$accent.' 55%,'.$dark.' 55%)"></span>'
                .'<span class="syn-swlabel">'.StringUtil::specialchars($label).'</span>'
                .'</button>';
        }

        return '<div id="synapsis_schemewizard">'.$buttons.'</div>'
            .'<style>'
            .'#synapsis_schemewizard{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}'
            .'#synapsis_schemewizard button{display:flex;flex-direction:column;align-items:center;gap:4px;width:96px;padding:6px;border:1px solid #ccc;border-radius:4px;background:#fff;cursor:pointer;font-size:11px;color:#4b5563}'
            .'#synapsis_schemewizard .syn-swatch{display:block;width:100%;height:34px;border-radius:3px;border:1px solid rgba(0,0,0,.1)}'
            .'#synapsis_schemewizard .syn-swlabel{line-height:1.2;text-align:center}'
            .'#synapsis_schemewizard button:hover{background:#eef1f4}'
            .'#synapsis_schemewizard button.active{border-color:#f47c00;box-shadow:0 0 0 1px #f47c00;background:#fff7ee}'
            .'</style>'
            .'<script>(function(){var w=document.getElementById("synapsis_schemewizard");if(!w)return;'
            .'var s=document.getElementById("ctrl_colorScheme");'
            .'function m(){var v=s?s.value:"";w.querySelectorAll("[data-scheme]").forEach(function(b){b.classList.toggle("active",b.getAttribute("data-scheme")===v)})}'
            .'w.addEventListener("click",function(e){var b=e.target.closest("[data-scheme]");if(!b)return;e.preventDefault();'
            .'if(s){s.value=b.getAttribute("data-scheme");s.dispatchEvent(new Event("change"))}m()});'
            .'if(s){s.addEventListener("change",m)}m()})();</script>';
    }
}
