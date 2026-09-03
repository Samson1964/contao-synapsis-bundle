<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Tests\Dca;

use Contao\CoreBundle\ContaoCoreBundle;
use PHPUnit\Framework\TestCase;

/**
 * Prueft, dass die Palette des Frontend-Moduls nur Felder benennt, die es auch gibt.
 *
 * Hintergrund: Die Palette "synapsis_forum" mischt eigene Felder mit Feldern der
 * Kern-DCA tl_module. Verschwindet ein Kernfeld zwischen zwei Contao-Fassungen
 * (so geschehen mit "guests": in 4.13 vorhanden, in Contao 5 ersatzlos entfernt),
 * benennt die Palette anschliessend ein Feld, das gar nicht existiert. Contao
 * uebergeht das **stillschweigend** - kein Fehler, kein Logeintrag, die Option
 * fehlt im Backend einfach. Genau deshalb faellt so etwas ohne Test nicht auf.
 */
class ModulePaletteTest extends TestCase
{
    /**
     * Liest die Palette "synapsis_forum" aus der DCA-Datei des Bundles und zerlegt
     * sie in einzelne Feldnamen.
     *
     * Die Datei wird bewusst als Text ausgewertet statt eingebunden: Beim Einbinden
     * wuerde sie in $GLOBALS schreiben und andere Tests beeinflussen.
     *
     * @return array<int, string> Feldnamen ohne Legenden, in Reihenfolge der Palette
     */
    private function paletteFelder(): array
    {
        $datei = __DIR__.'/../../src/Resources/contao/dca/tl_module.php';
        $quelltext = file_get_contents($datei);

        self::assertIsString($quelltext, 'DCA-Datei tl_module.php ist nicht lesbar.');
        self::assertSame(1, preg_match("#palettes'\]\['synapsis_forum'\] = (.+?);\n#s", $quelltext, $treffer), 'Palette synapsis_forum nicht gefunden.');

        // Zeichenketten-Verkettung aufloesen, Legenden entfernen, an ; und , trennen
        $palette = preg_replace('#["\'.\s]+#', '', $treffer[1]) ?? '';
        $palette = preg_replace('#\{[^}]+\}#', '', $palette) ?? '';

        return array_values(array_filter(array_map('trim', preg_split('#[;,]#', $palette) ?: [])));
    }

    /**
     * Ermittelt die Felder der Kern-DCA tl_module der gerade installierten Contao-Fassung.
     *
     * Der Ablageort der DCA-Dateien hat sich mit Contao 5 geaendert (frueher unter
     * src/Resources/contao, heute direkt unter contao/), daher werden beide Orte
     * geprueft.
     *
     * @return array<int, string> Namen aller im Kern definierten Felder von tl_module
     */
    private function kernFelder(): array
    {
        $wurzel = \dirname((string) (new \ReflectionClass(ContaoCoreBundle::class))->getFileName());

        foreach (['/../contao/dca/tl_module.php', '/Resources/contao/dca/tl_module.php'] as $pfad) {
            if (is_file($wurzel.$pfad)) {
                $quelltext = (string) file_get_contents($wurzel.$pfad);
                preg_match_all("#'(\w+)' => array#", $quelltext, $treffer);

                return $treffer[1];
            }
        }

        self::fail('Kern-DCA tl_module.php wurde nicht gefunden.');
    }

    /**
     * Jedes Feld der Palette muss entweder vom Bundle selbst oder vom Contao-Kern
     * stammen - andernfalls zeigt das Backend die Option lautlos nicht an.
     */
    public function testAlleFelderDerPaletteExistieren(): void
    {
        $eigene = ['synapsis_root', 'synapsis_perPage', 'synapsis_editor', 'synapsis_allowUploads'];
        $kern = $this->kernFelder();

        foreach ($this->paletteFelder() as $feld) {
            self::assertTrue(
                \in_array($feld, $eigene, true) || \in_array($feld, $kern, true),
                sprintf('Die Palette nennt das Feld "%s", das es in dieser Contao-Fassung weder im Bundle noch im Kern gibt.', $feld)
            );
        }
    }

    /**
     * Sicherung gegen einen Rueckfall: "guests" wurde in Contao 5 entfernt und
     * darf in keiner Fassung wieder in die Palette wandern.
     */
    public function testGuestsIstNichtMehrTeilDerPalette(): void
    {
        self::assertNotContains('guests', $this->paletteFelder());
    }

    /**
     * Auch die Unterpalette muss ein Feld benennen, das die DCA definiert - sonst
     * bliebe der Upload-Ordner unsichtbar, sobald jemand Uploads aktiviert.
     */
    public function testUnterpaletteNenntVorhandenesFeld(): void
    {
        $quelltext = (string) file_get_contents(__DIR__.'/../../src/Resources/contao/dca/tl_module.php');

        self::assertSame(
            1,
            preg_match("#subpalettes'\]\['synapsis_allowUploads'\] = '(\w+)'#", $quelltext, $treffer),
            'Unterpalette synapsis_allowUploads nicht gefunden.'
        );

        self::assertStringContainsString(
            sprintf("fields']['%s']", $treffer[1]),
            $quelltext,
            sprintf('Die Unterpalette nennt das Feld "%s", das die DCA nicht definiert.', $treffer[1])
        );
    }
}
