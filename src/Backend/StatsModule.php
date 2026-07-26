<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Backend;

use Contao\Backend;
use Contao\Date;
use Contao\StringUtil;
use Contao\System;

/**
 * Backend-Modul "Statistik" (Gruppe Synapsis, do=synapsis_stats).
 *
 * Reine Lese-Uebersicht: Gesamtzahlen (Startpunkte, Kategorien, Foren, Themen,
 * Beitraege, aktive Mitglieder, offene Meldungen), eine Aufstellung je Startpunkt
 * sowie die aktivsten Mitglieder und die letzten Beitraege.
 */
class StatsModule extends Backend
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @param mixed $dc Data-Container (hier ungenutzt)
     */
    public function __construct($dc = null)
    {
        parent::__construct();
        $this->connection = System::getContainer()->get('database_connection');
    }

    public function generate(): string
    {
        $html = '<div id="tl_buttons"></div>';
        $html .= '<h2 class="sub_headline">Synapsis: Statistik</h2>';

        $html .= $this->totals();
        $html .= $this->perStartpoint();
        $html .= $this->topPosters();
        $html .= $this->recentPosts();

        return $html;
    }

    /**
     * Gesamtzahlen als Kachelreihe.
     */
    private function totals(): string
    {
        $types = [];

        foreach ($this->connection->fetchAllAssociative("SELECT type, COUNT(*) AS n FROM tl_synapsis_forum GROUP BY type") as $row) {
            $types[(string) $row['type']] = (int) $row['n'];
        }

        $tiles = [
            'Startpunkte' => $types['root'] ?? 0,
            'Kategorien' => $types['category'] ?? 0,
            'Foren' => $types['forum'] ?? 0,
            'Themen' => (int) $this->connection->fetchOne("SELECT COUNT(*) FROM tl_synapsis_topic WHERE published = '1'"),
            'Beiträge' => (int) $this->connection->fetchOne("SELECT COUNT(*) FROM tl_synapsis_post WHERE published = '1'"),
            'Aktive Mitglieder' => (int) $this->connection->fetchOne('SELECT COUNT(DISTINCT author) FROM tl_synapsis_post WHERE author > 0'),
            'Offene Meldungen' => (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_synapsis_report'),
        ];

        $cells = '';

        foreach ($tiles as $label => $value) {
            $cells .= '<div style="flex:1 1 120px;min-width:120px;background:var(--contao-bg-color,#fff);border:1px solid #ddd;border-radius:4px;padding:12px 14px;text-align:center">'
                .'<div style="font-size:26px;font-weight:700">'.number_format($value, 0, ',', '.').'</div>'
                .'<div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.03em">'.$label.'</div>'
                .'</div>';
        }

        return '<div class="tl_box"><div style="display:flex;flex-wrap:wrap;gap:10px">'.$cells.'</div></div>';
    }

    /**
     * Aufstellung je Startpunkt (Foren/Themen/Beitraege).
     */
    private function perStartpoint(): string
    {
        $roots = $this->connection->fetchAllAssociative("SELECT id, title FROM tl_synapsis_forum WHERE type = 'root' ORDER BY sorting");

        if ([] === $roots) {
            return '<p class="tl_info">Es ist noch kein Startpunkt vorhanden.</p>';
        }

        $rows = '';

        foreach ($roots as $root) {
            $forumIds = $this->descendantForumIds((int) $root['id']);

            if ([] === $forumIds) {
                $topics = 0;
                $posts = 0;
            } else {
                $ph = implode(',', array_fill(0, \count($forumIds), '?'));
                $topics = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM tl_synapsis_topic WHERE pid IN ($ph) AND published = '1'", $forumIds);
                $posts = (int) $this->connection->fetchOne(
                    "SELECT COUNT(*) FROM tl_synapsis_post p INNER JOIN tl_synapsis_topic t ON t.id = p.pid WHERE t.pid IN ($ph) AND p.published = '1' AND t.published = '1'",
                    $forumIds
                );
            }

            $rows .= '<tr>'
                .'<td>'.StringUtil::specialchars((string) $root['title']).'</td>'
                .'<td style="text-align:right">'.\count($forumIds).'</td>'
                .'<td style="text-align:right">'.$topics.'</td>'
                .'<td style="text-align:right">'.$posts.'</td>'
                .'</tr>';
        }

        return '<h3 class="sub_headline">Je Startpunkt</h3>'
            .'<table class="tl_listing" style="width:100%"><thead><tr>'
            .'<th>Startpunkt</th><th style="text-align:right">Foren</th><th style="text-align:right">Themen</th><th style="text-align:right">Beiträge</th>'
            .'</tr></thead><tbody>'.$rows.'</tbody></table>';
    }

    /**
     * Aktivste Mitglieder (Top 10 nach Beitragszahl).
     */
    private function topPosters(): string
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT p.author, COUNT(*) AS n FROM tl_synapsis_post p WHERE p.author > 0 AND p.published = '1' GROUP BY p.author ORDER BY n DESC LIMIT 10"
        );

        if ([] === $rows) {
            return '';
        }

        $body = '';

        foreach ($rows as $row) {
            $member = $this->connection->fetchAssociative('SELECT firstname, lastname, username FROM tl_member WHERE id = ?', [(int) $row['author']]);
            $name = $member ? trim(($member['firstname'] ?? '').' '.($member['lastname'] ?? '')) : '';
            $name = '' !== $name ? $name : (string) ($member['username'] ?? ('#'.$row['author']));

            $body .= '<tr><td>'.StringUtil::specialchars($name).'</td><td style="text-align:right">'.(int) $row['n'].'</td></tr>';
        }

        return '<h3 class="sub_headline">Aktivste Mitglieder</h3>'
            .'<table class="tl_listing" style="width:100%"><thead><tr><th>Mitglied</th><th style="text-align:right">Beiträge</th></tr></thead><tbody>'.$body.'</tbody></table>';
    }

    /**
     * Die letzten 10 Beitraege.
     */
    private function recentPosts(): string
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT p.date, p.author, p.authorName, t.title FROM tl_synapsis_post p INNER JOIN tl_synapsis_topic t ON t.id = p.pid WHERE p.published = '1' ORDER BY p.date DESC LIMIT 10"
        );

        if ([] === $rows) {
            return '';
        }

        $format = (string) \Contao\Config::get('datim');
        $body = '';

        foreach ($rows as $row) {
            $name = '' !== (string) ($row['authorName'] ?? '') ? (string) $row['authorName'] : 'Gast';
            $body .= '<tr>'
                .'<td>'.StringUtil::specialchars((string) $row['title']).'</td>'
                .'<td>'.StringUtil::specialchars($name).'</td>'
                .'<td>'.Date::parse('' !== $format ? $format : 'd.m.Y H:i', (int) $row['date']).'</td>'
                .'</tr>';
        }

        return '<h3 class="sub_headline">Letzte Beiträge</h3>'
            .'<table class="tl_listing" style="width:100%"><thead><tr><th>Thema</th><th>Verfasser</th><th>Datum</th></tr></thead><tbody>'.$body.'</tbody></table>';
    }

    /**
     * Alle Foren-IDs unterhalb eines Startpunkts (type = forum).
     *
     * @return array<int>
     */
    private function descendantForumIds(int $rootId): array
    {
        $ids = [];
        $queue = [$rootId];
        $guard = 0;

        while ([] !== $queue && $guard < 10000) {
            ++$guard;
            $current = (int) array_shift($queue);

            $children = $this->connection->fetchAllAssociative('SELECT id, type FROM tl_synapsis_forum WHERE pid = ?', [$current]);

            foreach ($children as $child) {
                $childId = (int) $child['id'];

                if ('forum' === (string) $child['type']) {
                    $ids[] = $childId;
                }

                $queue[] = $childId;
            }
        }

        return array_values(array_unique($ids));
    }
}
