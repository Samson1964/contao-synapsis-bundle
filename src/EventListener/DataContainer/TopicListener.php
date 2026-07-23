<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\EventListener\DataContainer;

use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;

/**
 * Callbacks der Themen (tl_synapsis_topic).
 */
class TopicListener extends AbstractRecordListener
{
    /**
     * Stellt ein Thema in der Elternansicht dar (child_record_callback).
     *
     * Hinweis: Contao 5.7 markiert den child_record_callback als veraltet,
     * unterstuetzt ihn aber weiterhin. Contao 4.13 benoetigt ihn zwingend,
     * deshalb bleibt er bis zur Anhebung der Mindestversion bestehen.
     *
     * @param array<string, mixed> $row
     */
    public function renderTopic(array $row): string
    {
        $marker = '';

        if ($row['sticky'] ?? false) {
            $marker .= Image::getHtml('featured.svg', $GLOBALS['TL_LANG']['tl_synapsis_topic']['sticky'][0] ?? 'Angeheftet').' ';
        }

        if ($row['locked'] ?? false) {
            $marker .= Image::getHtml('lock-locked.svg', $GLOBALS['TL_LANG']['tl_synapsis_topic']['locked'][0] ?? 'Geschlossen').' ';
        }

        $title = StringUtil::specialchars((string) ($row['title'] ?? ''));

        if (!($row['published'] ?? false)) {
            $title = '<span class="tl_gray">'.$title.'</span>';
        }

        $meta = sprintf(
            '%s, %s &middot; %s: %s',
            $this->getMemberName((int) ($row['author'] ?? 0)),
            $this->formatDate((int) ($row['date'] ?? 0)),
            $GLOBALS['TL_LANG']['tl_synapsis_topic']['views'][0] ?? 'Ansichten',
            (int) ($row['views'] ?? 0)
        );

        return '<div class="tl_content_left">'.$marker.'<strong>'.$title.'</strong> <span class="tl_gray">['.$meta.']</span></div>';
    }

    /**
     * Erzeugt bei Bedarf den Alias aus dem Titel (save_callback).
     *
     * @param mixed $value
     *
     * @throws \RuntimeException wenn der Alias im selben Forum bereits vergeben ist
     */
    public function generateAlias($value, DataContainer $dc): string
    {
        $value = (string) $value;
        $id = (int) $dc->id;

        if ('' === $value) {
            $title = (string) Input::post('title');

            if ('' === $title) {
                $title = (string) $this->connection->fetchOne('SELECT title FROM tl_synapsis_topic WHERE id = ?', [$id]);
            }

            $value = StringUtil::generateAlias($title);

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
     * Prueft, ob der Alias bereits von einem anderen Thema verwendet wird.
     */
    private function aliasExists(string $alias, int $id): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT id FROM tl_synapsis_topic WHERE alias = ? AND id != ?',
            [$alias, $id]
        );
    }
}
