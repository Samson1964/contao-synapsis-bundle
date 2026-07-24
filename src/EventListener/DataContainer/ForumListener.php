<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Schachbulle\ContaoSynapsisBundle\Frontend\LucideIcons;

/**
 * Callbacks der Forenstruktur (tl_synapsis_forum).
 *
 * Die Klasse haelt die Logik der Baumstruktur zusammen: welcher Typ an welcher
 * Stelle erlaubt ist, wie ein Knoten im Baum dargestellt wird und wie der Alias
 * erzeugt wird. Die Registrierung erfolgt ueber Service-Tags (services.yaml),
 * nicht ueber die config.php.
 */
class ForumListener
{
    /**
     * Erlaubte Kindtypen je Elterntyp.
     *
     * Die Struktur ist streng dreistufig:
     *   - oberste Ebene (pid 0): nur Startpunkte
     *   - im Startpunkt:          nur Kategorien
     *   - in der Kategorie:       nur Foren
     *   - im Forum:               keine weiteren Baumknoten (die Themen haengen
     *                             als eigene Kindtabelle am Forum)
     *
     * @var array<string, array<string>>
     */
    private const ALLOWED_CHILDREN = [
        'root' => ['category'],
        'category' => ['forum'],
        'forum' => [],
    ];

    /**
     * Icons der einzelnen Typen (Kernicons, inkl. Dark-Mode-Varianten).
     *
     * @var array<string, string>
     */
    private const ICONS = [
        'root' => 'root.svg',
        'category' => 'folderC.svg',
        'forum' => 'articles.svg',
    ];

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Liefert die an der aktuellen Position erlaubten Typen (options_callback).
     *
     * Wichtig: Das Ergebnis ist ein assoziatives Array (Wert => Beschriftung).
     * Bei einer einfachen Liste wuerde Contao den numerischen Schluessel (0, 1)
     * statt des Typnamens speichern, wodurch die passende Palette nicht mehr
     * greift und der Datensatz faelschlich nur die Typ-Auswahl anzeigt.
     *
     * @return array<string, string>
     */
    public function getTypeOptions(?DataContainer $dc = null): array
    {
        $labels = $GLOBALS['TL_LANG']['tl_synapsis_forum']['types'] ?? [];
        $options = [];

        foreach ($this->allowedTypesFor($dc) as $type) {
            $options[$type] = $labels[$type] ?? $type;
        }

        return $options;
    }

    /**
     * Liefert die auswaehlbaren Mitgliedergruppen inklusive der fiktiven Gruppe
     * "Gaeste" (-1), wie in Contao ueblich (options_callback des Feldes
     * "groups").
     *
     * Die Gaeste-Gruppe hat Vorrang vor den Checkboxen im Bereich
     * "Gaeste-Zugriff": ist sie ausgewaehlt, regelt sie den Gaeste-Lesezugriff,
     * und die Checkboxen bleiben ohne Wirkung (siehe ForumAccess).
     *
     * @return array<int, string>
     */
    public function getGroupOptions(): array
    {
        $options = [-1 => ($GLOBALS['TL_LANG']['MSC']['guests'] ?? 'Gäste')];

        $rows = $this->connection->fetchAllAssociative('SELECT id, name FROM tl_member_group ORDER BY name');

        foreach ($rows as $row) {
            $options[(int) $row['id']] = $row['name'];
        }

        return $options;
    }

    /**
     * Belegt den Typ eines neu angelegten Knotens passend zur Position vor
     * (oncreate_callback).
     *
     * Ohne diese Vorbelegung stuende im Datensatz der SQL-Standardwert "forum",
     * der an der jeweiligen Position aber gar nicht erlaubt ist - Contao zeigt
     * ihn dann als "Unbekannte Option: forum" in der Typ-Auswahl an.
     *
     * @param array<string, mixed> $set Eingefuegte Werte (hier ungenutzt)
     */
    public function onCreate(string $table, int $insertId, array $set, ?DataContainer $dc = null): void
    {
        $pid = (int) $this->connection->fetchOne('SELECT pid FROM tl_synapsis_forum WHERE id = ?', [$insertId]);

        if (0 === $pid) {
            $allowed = ['root'];
        } else {
            $parentType = (string) $this->connection->fetchOne('SELECT type FROM tl_synapsis_forum WHERE id = ?', [$pid]);
            $allowed = self::ALLOWED_CHILDREN[$parentType] ?? [];
        }

        if ([] !== $allowed) {
            $this->connection->update('tl_synapsis_forum', ['type' => $allowed[0]], ['id' => $insertId]);
        }
    }

    /**
     * Rendert unter dem Icon-Auswahlfeld ein anklickbares Raster mit allen
     * verfuegbaren Lucide-Icons (wizard-Callback des Feldes "forumIcon").
     *
     * Ein Klick auf ein Icon setzt den Wert des Auswahlfelds "ctrl_forumIcon".
     * So ist visuell erkennbar, wie ein Icon aussieht, statt nur den Namen zu
     * sehen.
     */
    public function iconWizard(?DataContainer $dc = null): string
    {
        $sections = '';

        foreach (LucideIcons::categories() as $label => $names) {
            $buttons = '';

            foreach ($names as $name) {
                $buttons .= '<button type="button" data-icon="'.$name.'" title="'.$name.'">'.LucideIcons::svg($name).'</button>';
            }

            $sections .= '<div class="syn-iconcat"><span class="syn-iconcat-label">'.StringUtil::specialchars($label).'</span>'
                .'<div class="syn-iconrow">'.$buttons.'</div></div>';
        }

        return '<div id="synapsis_iconwizard">'.$sections.'</div>'
            .'<style>'
            .'#synapsis_iconwizard{width:100%;margin-top:8px}'
            .'#synapsis_iconwizard .syn-iconcat{margin-bottom:12px}'
            .'#synapsis_iconwizard .syn-iconcat-label{display:block;font-weight:bold;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;margin-bottom:4px}'
            .'#synapsis_iconwizard .syn-iconrow{display:flex;flex-wrap:wrap;gap:5px}'
            .'#synapsis_iconwizard button{width:36px;height:36px;padding:6px;border:1px solid #ccc;border-radius:4px;background:#fff;cursor:pointer;color:#4b5563;line-height:0}'
            .'#synapsis_iconwizard button svg{width:22px;height:22px;display:block}'
            .'#synapsis_iconwizard button:hover{background:#eef1f4}'
            .'#synapsis_iconwizard button.active{border-color:#f47c00;box-shadow:0 0 0 1px #f47c00;background:#fff7ee}'
            .'</style>'
            .'<script>(function(){var w=document.getElementById("synapsis_iconwizard");if(!w)return;'
            .'var s=document.getElementById("ctrl_forumIcon");'
            .'function m(){var v=s?s.value:"";w.querySelectorAll("[data-icon]").forEach(function(b){b.classList.toggle("active",b.getAttribute("data-icon")===v)})}'
            .'w.addEventListener("click",function(e){var b=e.target.closest("[data-icon]");if(!b)return;e.preventDefault();'
            .'if(s){s.value=b.getAttribute("data-icon");s.dispatchEvent(new Event("change"))}m()});'
            .'if(s){s.addEventListener("change",m)}m()})();</script>';
    }

    /**
     * Ermittelt die an der aktuellen Position erlaubten Typen.
     *
     * @return array<string>
     */
    private function allowedTypesFor(?DataContainer $dc): array
    {
        $pid = $this->getParentId($dc);

        if (0 === $pid) {
            return ['root'];
        }

        $parentType = (string) $this->connection->fetchOne(
            'SELECT type FROM tl_synapsis_forum WHERE id = ?',
            [$pid]
        );

        return self::ALLOWED_CHILDREN[$parentType] ?? [];
    }

    /**
     * Stellt einen Knoten der Forenstruktur dar (label_callback im Baummodus).
     *
     * @param array<string, mixed> $row            Datensatz des Knotens
     * @param string               $label          Vorgefertigtes Label (Titel)
     * @param DataContainer|null   $dc             Data Container
     * @param string               $imageAttribute Zusaetzliche Attribute des Icons
     * @param bool                 $returnImage    Nur das Icon zurueckgeben
     * @param bool                 $protected      Knoten liegt in einem geschuetzten Bereich
     */
    public function renderLabel(array $row, string $label, ?DataContainer $dc = null, string $imageAttribute = '', bool $returnImage = false, bool $protected = false): string
    {
        $type = (string) ($row['type'] ?? 'forum');
        $icon = self::ICONS[$type] ?? self::ICONS['forum'];

        // Unveroeffentlichte Knoten mit gedaempftem Icon kennzeichnen
        $attribute = $imageAttribute;

        if (!($row['published'] ?? false)) {
            $attribute = trim($imageAttribute.' style="opacity:0.4"');
        }

        $image = Image::getHtml($icon, '', $attribute);

        if ($returnImage) {
            return $image;
        }

        // Geschlossene Foren und unveroeffentlichte Knoten kenntlich machen
        $suffix = '';

        if ($row['closed'] ?? false) {
            $suffix .= ' <span class="tl_gray">['.($GLOBALS['TL_LANG']['tl_synapsis_forum']['closedLabel'] ?? 'geschlossen').']</span>';
        }

        if (!($row['published'] ?? false)) {
            $label = '<span class="tl_gray">'.$label.'</span>';
        }

        return $image.' '.$label.$suffix;
    }

    /**
     * Erzeugt bei Bedarf den Alias aus dem Titel (save_callback).
     *
     * @param mixed $value Eingegebener Alias
     *
     * @throws \RuntimeException wenn der Alias bereits vergeben ist
     */
    public function generateAlias($value, DataContainer $dc): string
    {
        $value = (string) $value;
        $id = (int) $dc->id;

        if ('' === $value) {
            $title = (string) Input::post('title');

            if ('' === $title) {
                $title = (string) $this->connection->fetchOne('SELECT title FROM tl_synapsis_forum WHERE id = ?', [$id]);
            }

            $value = StringUtil::generateAlias($title);

            // Bei Dubletten die ID anhaengen, damit der Alias eindeutig bleibt
            if ($this->aliasExists($value, $id)) {
                $value .= '-'.$id;
            }

            return $value;
        }

        if ($this->aliasExists($value, $id)) {
            throw new \RuntimeException(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'] ?? 'Der Alias "%s" ist bereits vergeben.', $value));
        }

        return $value;
    }

    /**
     * Zeigt die Schaltflaeche "Themen" nur bei Foren an (button_callback).
     *
     * Kategorien und Startpunkte koennen keine Themen enthalten, deshalb wird
     * die Schaltflaeche dort ausgeblendet.
     *
     * @param array<string, mixed> $row
     * @param string|null          $href
     * @param string               $label
     * @param string               $title
     * @param string|null          $icon
     * @param mixed                $attributes
     */
    public function topicsButton(array $row, $href, $label, $title, $icon, $attributes): string
    {
        if ('forum' !== ($row['type'] ?? '')) {
            return '';
        }

        return '<a href="'.Backend::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'" '.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * Ermittelt die ID des uebergeordneten Datensatzes.
     *
     * Beim Anlegen eines neuen Datensatzes steht die pid noch nicht in der
     * Datenbank; Contao uebergibt sie dann per URL (mode=2 = "hineinfuegen",
     * mode=1 = "danach einfuegen", pid ist dann ein Geschwisterdatensatz).
     */
    private function getParentId(?DataContainer $dc): int
    {
        if (null !== $dc && $dc->id) {
            $pid = $this->connection->fetchOne('SELECT pid FROM tl_synapsis_forum WHERE id = ?', [(int) $dc->id]);

            if (false !== $pid && null !== $pid) {
                return (int) $pid;
            }
        }

        $pid = (int) Input::get('pid');

        if (0 === $pid) {
            return 0;
        }

        if (1 === (int) Input::get('mode')) {
            return (int) $this->connection->fetchOne('SELECT pid FROM tl_synapsis_forum WHERE id = ?', [$pid]);
        }

        return $pid;
    }

    /**
     * Prueft, ob der Alias bereits von einem anderen Datensatz verwendet wird.
     */
    private function aliasExists(string $alias, int $id): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT id FROM tl_synapsis_forum WHERE alias = ? AND id != ?',
            [$alias, $id]
        );
    }
}
