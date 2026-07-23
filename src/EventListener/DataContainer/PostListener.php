<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\EventListener\DataContainer;

use Contao\StringUtil;

/**
 * Callbacks der Beitraege (tl_synapsis_post).
 */
class PostListener extends AbstractRecordListener
{
    /**
     * Stellt einen Beitrag in der Elternansicht dar (child_record_callback).
     *
     * Zeigt Autor und Zeitpunkt sowie einen gekuerzten Auszug des Beitrags.
     *
     * @param array<string, mixed> $row
     */
    public function renderPost(array $row): string
    {
        $header = sprintf(
            '%s &middot; %s',
            $this->getMemberName((int) ($row['author'] ?? 0)),
            $this->formatDate((int) ($row['date'] ?? 0))
        );

        $text = StringUtil::substr(strip_tags((string) ($row['text'] ?? '')), 200);

        if (!($row['published'] ?? false)) {
            $text = '<span class="tl_gray">'.$text.'</span>';
        }

        return '<div class="tl_content_left"><strong>'.$header.'</strong><br>'.$text.'</div>';
    }
}
