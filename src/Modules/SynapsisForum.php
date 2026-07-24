<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Modules;

use Contao\BackendTemplate;
use Contao\Database;
use Contao\Date;
use Contao\Dbafs;
use Contao\FilesModel;
use Contao\FileUpload;
use Contao\FrontendUser;
use Contao\Input;
use Contao\Module;
use Contao\Pagination;
use Contao\StringUtil;
use Contao\System;
use Schachbulle\ContaoSynapsisBundle\Frontend\ForumAccess;

/**
 * Frontend-Modul des Synapsis-Forums.
 *
 * Ein einziges Modul stellt saemtliche Ansichten dar; die aktive Ansicht ergibt
 * sich aus den URL-Parametern (mybb-Prinzip):
 *
 *   (ohne)              Uebersicht des Startpunkts: Kategorien, Foren, neueste
 *                       Themen und Statistiken
 *   ?forum=<id>         Themenliste eines Forums (seitenweise)
 *   ?topic=<id>         Beitraege eines Themas (seitenweise) samt Antwortformular
 *   ?forum=<id>&new=1   Formular fuer ein neues Thema
 *
 * Als Legacy-Modul (extends \Contao\Module) laeuft es unveraendert unter
 * Contao 4.13 und Contao 5.
 */
class SynapsisForum extends Module
{
    /**
     * Standard-Template (wird je nach Ansicht in generate() umgesetzt).
     *
     * @var string
     */
    protected $strTemplate = 'mod_synapsis_forum';

    /**
     * Aktive Ansicht: index | forum | topic | newtopic.
     *
     * @var string
     */
    private $view = 'index';

    /**
     * ID des Startpunkts (Wurzel der Forenstruktur).
     *
     * @var int
     */
    private $rootId = 0;

    /**
     * Datensatz des aktiven Forums (Ansichten forum/newtopic).
     *
     * @var array<string, mixed>|null
     */
    private $activeForum;

    /**
     * Datensatz des aktiven Themas (Ansicht topic).
     *
     * @var array<string, mixed>|null
     */
    private $activeTopic;

    /**
     * Zugriffshelfer fuer die Vererbung von Schutz und Veroeffentlichung.
     *
     * @var ForumAccess
     */
    private $access;

    /**
     * Erzeugt das Modul bzw. im Backend eine Platzhalterdarstellung.
     */
    public function generate(): string
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        $scopeMatcher = System::getContainer()->get('contao.routing.scope_matcher');

        if (null !== $request && $scopeMatcher->isBackendRequest($request)) {
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '### SYNAPSIS FORUM ###';
            $template->title = $this->headline;
            $template->id = $this->id;
            $template->link = $this->name;

            return $template->parse();
        }

        $this->access = new ForumAccess();
        $this->rootId = (int) $this->synapsis_root;

        if (0 === $this->rootId) {
            return '';
        }

        $this->resolveView();

        return parent::generate();
    }

    /**
     * Baut die aktive Ansicht auf.
     */
    protected function compile(): void
    {
        // Stylesheet des Forums einbinden
        $GLOBALS['TL_CSS']['synapsis'] = 'bundles/schachbullecontaosynapsis/css/synapsis.css|static';

        $this->Template->view = $this->view;
        $this->Template->baseUrl = $this->pageUrl([]);
        $this->Template->loggedIn = $this->isMemberLoggedIn();

        switch ($this->view) {
            case 'forum':
                $this->compileForum();
                break;

            case 'topic':
                $this->compileTopic();
                break;

            case 'newtopic':
                $this->compileNewTopic();
                break;

            default:
                $this->compileIndex();
        }
    }

    /**
     * Ermittelt Ansicht und aktive Datensaetze aus den URL-Parametern und
     * setzt das passende Template.
     */
    private function resolveView(): void
    {
        $topicId = (int) Input::get('topic');
        $forumId = (int) Input::get('forum');

        if ($topicId > 0 && null !== ($topic = $this->findTopic($topicId))) {
            $this->activeTopic = $topic;
            $this->activeForum = $this->findForum((int) $topic['pid']);
            $this->view = 'topic';
            $this->strTemplate = 'mod_synapsis_topic';

            return;
        }

        if ($forumId > 0 && null !== ($forum = $this->findForum($forumId))) {
            $this->activeForum = $forum;

            if (Input::get('new') && $this->isMemberLoggedIn() && !$forum['closed']) {
                $this->view = 'newtopic';
                $this->strTemplate = 'mod_synapsis_newtopic';

                return;
            }

            $this->view = 'forum';
            $this->strTemplate = 'mod_synapsis_forum_view';

            return;
        }

        $this->view = 'index';
        $this->strTemplate = 'mod_synapsis_forum';
    }

    // -------------------------------------------------------------------------
    // Ansichten
    // -------------------------------------------------------------------------

    /**
     * Uebersicht: Kategorien mit ihren Foren, neueste Themen, Statistiken.
     */
    private function compileIndex(): void
    {
        $categories = [];

        // Direkte Kinder des Startpunkts (Kategorien und Foren)
        foreach ($this->findChildren($this->rootId) as $node) {
            if (!$this->isVisible((int) $node['id'])) {
                continue;
            }

            if ('forum' === $node['type']) {
                // Forum direkt unter dem Startpunkt: eigene Pseudo-Kategorie
                $categories[] = [
                    'title' => '',
                    'description' => '',
                    'forums' => [$this->decorateForum($node)],
                ];

                continue;
            }

            // Kategorie: ihre Foren einsammeln
            $forums = [];

            foreach ($this->findChildren((int) $node['id']) as $child) {
                if ('forum' === $child['type'] && $this->isVisible((int) $child['id'])) {
                    $forums[] = $this->decorateForum($child);
                }
            }

            $categories[] = [
                'title' => $node['title'],
                'description' => $node['description'],
                'forums' => $forums,
            ];
        }

        $this->Template->categories = $categories;
        $this->Template->newestTopics = $this->findNewestTopics(10);
        $this->Template->statistics = $this->buildStatistics();
    }

    /**
     * Themenliste eines Forums (seitenweise, angeheftete zuerst).
     */
    private function compileForum(): void
    {
        $forumId = (int) $this->activeForum['id'];

        $this->Template->forum = $this->activeForum;
        $this->Template->breadcrumb = $this->buildBreadcrumb($forumId);
        $this->Template->newTopicUrl = (!$this->activeForum['closed'] && $this->isMemberLoggedIn())
            ? $this->pageUrl(['forum' => $forumId, 'new' => 1])
            : '';
        $this->Template->closed = (bool) $this->activeForum['closed'];

        // Unterforen
        $subforums = [];

        foreach ($this->findChildren($forumId) as $child) {
            if ('forum' === $child['type'] && $this->isVisible((int) $child['id'])) {
                $subforums[] = $this->decorateForum($child);
            }
        }

        $this->Template->subforums = $subforums;

        // Themen mit Seitennummerierung
        $perPage = max(1, (int) $this->synapsis_perPage);
        $total = (int) Database::getInstance()
            ->prepare('SELECT COUNT(*) FROM tl_synapsis_topic WHERE pid = ? AND published = ?')
            ->execute($forumId, '1')
            ->row(true)[0]
        ;

        $pagination = new Pagination($total, $perPage, 7, 'page_s'.$this->id);
        $offset = ($this->getCurrentPage() - 1) * $perPage;

        $topics = Database::getInstance()
            ->prepare('SELECT * FROM tl_synapsis_topic WHERE pid = ? AND published = ? ORDER BY sticky DESC, date DESC')
            ->limit($perPage, $offset)
            ->execute($forumId, '1')
        ;

        $rows = [];

        while ($topics->next()) {
            $rows[] = $this->decorateTopic($topics->row());
        }

        $this->Template->topics = $rows;
        $this->Template->pagination = $pagination->generate("\n");
        $this->Template->empty = [] === $rows;
    }

    /**
     * Beitraege eines Themas samt Antwortformular; erhoeht den Ansichtszaehler.
     */
    private function compileTopic(): void
    {
        $topicId = (int) $this->activeTopic['id'];

        // Antwort verarbeiten, bevor die Liste gerendert wird
        $this->handlePostSubmission();

        // Ansichtszaehler erhoehen (einmal pro Aufruf)
        Database::getInstance()
            ->prepare('UPDATE tl_synapsis_topic SET views = views + 1 WHERE id = ?')
            ->execute($topicId)
        ;

        $this->Template->topic = $this->activeTopic;
        $this->Template->forum = $this->activeForum;
        $this->Template->breadcrumb = $this->buildBreadcrumb((int) $this->activeForum['id'], (string) $this->activeTopic['title']);

        $perPage = max(1, (int) $this->synapsis_perPage);
        $total = (int) Database::getInstance()
            ->prepare('SELECT COUNT(*) FROM tl_synapsis_post WHERE pid = ? AND published = ?')
            ->execute($topicId, '1')
            ->row(true)[0]
        ;

        $pagination = new Pagination($total, $perPage, 7, 'page_p'.$this->id);
        $offset = ($this->getCurrentPage() - 1) * $perPage;

        $posts = Database::getInstance()
            ->prepare('SELECT * FROM tl_synapsis_post WHERE pid = ? AND published = ? ORDER BY date ASC')
            ->limit($perPage, $offset)
            ->execute($topicId, '1')
        ;

        $rows = [];

        while ($posts->next()) {
            $rows[] = $this->decoratePost($posts->row());
        }

        $this->Template->posts = $rows;
        $this->Template->pagination = $pagination->generate("\n");

        // Antwortformular nur fuer angemeldete Mitglieder in offenen Themen
        $canReply = $this->isMemberLoggedIn() && !$this->activeTopic['locked'] && !$this->activeForum['closed'];
        $this->Template->canReply = $canReply;
        $this->Template->locked = (bool) $this->activeTopic['locked'];

        if ($canReply) {
            $this->enableEditor();
            $this->Template->allowUploads = (bool) $this->synapsis_allowUploads;
            $this->Template->formAction = $this->pageUrl(['topic' => $topicId]);
            $this->Template->formId = 'synapsis_reply_'.$this->id;
        }
    }

    /**
     * Formular fuer ein neues Thema.
     */
    private function compileNewTopic(): void
    {
        $forumId = (int) $this->activeForum['id'];

        $this->handleTopicSubmission();

        $this->enableEditor();

        $this->Template->forum = $this->activeForum;
        $this->Template->breadcrumb = $this->buildBreadcrumb($forumId);
        $this->Template->allowUploads = (bool) $this->synapsis_allowUploads;
        $this->Template->formAction = $this->pageUrl(['forum' => $forumId, 'new' => 1]);
        $this->Template->formId = 'synapsis_topic_'.$this->id;
        $this->Template->cancelUrl = $this->pageUrl(['forum' => $forumId]);
    }

    // -------------------------------------------------------------------------
    // Formularverarbeitung
    // -------------------------------------------------------------------------

    /**
     * Legt ein neues Thema mitsamt erstem Beitrag an und leitet darauf um.
     */
    private function handleTopicSubmission(): void
    {
        if ('tl_synapsis_topic_'.$this->id !== Input::post('FORM_SUBMIT')) {
            return;
        }

        if (!$this->isMemberLoggedIn() || $this->activeForum['closed']) {
            return;
        }

        $title = trim((string) Input::post('title'));
        $text = $this->cleanText((string) Input::postHtml('text', true));

        if ('' === $title || '' === strip_tags($text)) {
            $this->Template->formError = $GLOBALS['TL_LANG']['MSC']['synapsisEmptyFields'] ?? 'Bitte Titel und Text ausfuellen.';

            return;
        }

        $memberId = (int) FrontendUser::getInstance()->id;
        $now = time();

        $topicId = (int) Database::getInstance()
            ->prepare('INSERT INTO tl_synapsis_topic %s')
            ->set([
                'pid' => (int) $this->activeForum['id'],
                'tstamp' => $now,
                'title' => $title,
                'alias' => $this->uniqueAlias('tl_synapsis_topic', $title),
                'author' => $memberId,
                'date' => $now,
                'published' => '1',
            ])
            ->execute()
            ->insertId
        ;

        $this->insertPost($topicId, $memberId, $text, $now);

        $this->redirect($this->pageUrl(['topic' => $topicId]));
    }

    /**
     * Legt eine Antwort im aktiven Thema an und leitet zurueck darauf.
     */
    private function handlePostSubmission(): void
    {
        if ('synapsis_reply_'.$this->id !== Input::post('FORM_SUBMIT')) {
            return;
        }

        if (!$this->isMemberLoggedIn() || $this->activeTopic['locked'] || $this->activeForum['closed']) {
            return;
        }

        $text = $this->cleanText((string) Input::postHtml('text', true));

        if ('' === strip_tags($text)) {
            $this->Template->formError = $GLOBALS['TL_LANG']['MSC']['synapsisEmptyText'] ?? 'Bitte einen Text eingeben.';

            return;
        }

        $now = time();
        $this->insertPost((int) $this->activeTopic['id'], (int) FrontendUser::getInstance()->id, $text, $now);

        // Thema als aktualisiert markieren
        Database::getInstance()
            ->prepare('UPDATE tl_synapsis_topic SET tstamp = ? WHERE id = ?')
            ->execute($now, (int) $this->activeTopic['id'])
        ;

        $this->redirect($this->pageUrl(['topic' => (int) $this->activeTopic['id'], 'page_p'.$this->id => $this->lastPostPage()]));
    }

    /**
     * Fuegt einen Beitrag ein und verarbeitet optionale Dateianhaenge.
     */
    private function insertPost(int $topicId, int $memberId, string $text, int $timestamp): int
    {
        $attachments = $this->synapsis_allowUploads ? $this->handleUploads() : null;

        return (int) Database::getInstance()
            ->prepare('INSERT INTO tl_synapsis_post %s')
            ->set([
                'pid' => $topicId,
                'tstamp' => $timestamp,
                'author' => $memberId,
                'date' => $timestamp,
                'text' => $text,
                'attachments' => $attachments,
                'published' => '1',
            ])
            ->execute()
            ->insertId
        ;
    }

    /**
     * Verschiebt hochgeladene Dateien in den konfigurierten Ordner und liefert
     * die serialisierten UUIDs zurueck.
     *
     * @return string|null
     */
    private function handleUploads()
    {
        $folderModel = FilesModel::findByUuid($this->synapsis_uploadFolder);

        if (null === $folderModel || empty($_FILES['attachment']['name'][0] ?? $_FILES['attachment']['name'] ?? '')) {
            return null;
        }

        $upload = new FileUpload();
        $upload->setName('attachment');

        try {
            $files = $upload->uploadTo($folderModel->path);
        } catch (\Exception $e) {
            return null;
        }

        $uuids = [];

        foreach ((array) $files as $file) {
            $fileModel = Dbafs::addResource($file);

            if (null !== $fileModel) {
                $uuids[] = $fileModel->uuid;
            }
        }

        return [] === $uuids ? null : serialize($uuids);
    }

    // -------------------------------------------------------------------------
    // Aufbereitung einzelner Datensaetze fuer die Templates
    // -------------------------------------------------------------------------

    /**
     * Reichert ein Forum um Verlinkung, Zaehlungen und den letzten Beitrag an.
     *
     * @param array<string, mixed> $forum
     *
     * @return array<string, mixed>
     */
    private function decorateForum(array $forum): array
    {
        $forumId = (int) $forum['id'];
        $forumIds = $this->collectForumIds($forumId);
        $placeholders = implode(',', array_fill(0, count($forumIds), '?'));

        $topicCount = (int) Database::getInstance()
            ->prepare('SELECT COUNT(*) FROM tl_synapsis_topic WHERE pid IN ('.$placeholders.') AND published = ?')
            ->execute(...array_merge($forumIds, ['1']))
            ->row(true)[0]
        ;

        $postCount = (int) Database::getInstance()
            ->prepare('SELECT COUNT(*) FROM tl_synapsis_post p INNER JOIN tl_synapsis_topic t ON t.id = p.pid WHERE t.pid IN ('.$placeholders.') AND p.published = ? AND t.published = ?')
            ->execute(...array_merge($forumIds, ['1', '1']))
            ->row(true)[0]
        ;

        $forum['url'] = $this->pageUrl(['forum' => $forumId]);
        $forum['topicCount'] = $topicCount;
        $forum['postCount'] = $postCount;
        $forum['lastPost'] = $this->findLastPost($forumIds);
        $forum['icon'] = $forum['closed'] ? 'lock' : 'message-square';

        return $forum;
    }

    /**
     * Reichert ein Thema um Verlinkung, Autor, Zaehlungen und den letzten
     * Beitrag an.
     *
     * @param array<string, mixed> $topic
     *
     * @return array<string, mixed>
     */
    private function decorateTopic(array $topic): array
    {
        $topicId = (int) $topic['id'];

        $topic['url'] = $this->pageUrl(['topic' => $topicId]);
        $topic['authorName'] = $this->memberName((int) $topic['author']);
        $topic['authorAvatar'] = $this->avatar((int) $topic['author']);
        $topic['dateFormatted'] = $this->formatDate((int) $topic['date']);
        $topic['replyCount'] = max(0, (int) Database::getInstance()
            ->prepare('SELECT COUNT(*) FROM tl_synapsis_post WHERE pid = ? AND published = ?')
            ->execute($topicId, '1')
            ->row(true)[0] - 1)
        ;
        $topic['lastPost'] = $this->findLastPost([], $topicId);

        return $topic;
    }

    /**
     * Reichert einen Beitrag um Autor, Datum, Beitragszahl und Anhaenge an.
     *
     * @param array<string, mixed> $post
     *
     * @return array<string, mixed>
     */
    private function decoratePost(array $post): array
    {
        $authorId = (int) $post['author'];

        $post['authorName'] = $this->memberName($authorId);
        $post['authorAvatar'] = $this->avatar($authorId);
        $post['authorPostCount'] = (int) Database::getInstance()
            ->prepare('SELECT COUNT(*) FROM tl_synapsis_post WHERE author = ? AND published = ?')
            ->execute($authorId, '1')
            ->row(true)[0]
        ;
        $post['dateFormatted'] = $this->formatDate((int) $post['date']);
        $post['attachmentList'] = $this->renderAttachments($post['attachments'] ?? null);

        return $post;
    }

    // -------------------------------------------------------------------------
    // Statistik, Anhaenge, Avatare
    // -------------------------------------------------------------------------

    /**
     * Ermittelt Forenweite Kennzahlen fuer die Uebersicht.
     *
     * @return array<string, mixed>
     */
    private function buildStatistics(): array
    {
        $forumIds = $this->collectForumIds($this->rootId);

        if ([] === $forumIds) {
            return ['topics' => 0, 'posts' => 0, 'members' => 0, 'topPosters' => []];
        }

        $placeholders = implode(',', array_fill(0, count($forumIds), '?'));

        $topics = (int) Database::getInstance()
            ->prepare('SELECT COUNT(*) FROM tl_synapsis_topic WHERE pid IN ('.$placeholders.') AND published = ?')
            ->execute(...array_merge($forumIds, ['1']))
            ->row(true)[0]
        ;

        $posts = (int) Database::getInstance()
            ->prepare('SELECT COUNT(*) FROM tl_synapsis_post p INNER JOIN tl_synapsis_topic t ON t.id = p.pid WHERE t.pid IN ('.$placeholders.') AND p.published = ? AND t.published = ?')
            ->execute(...array_merge($forumIds, ['1', '1']))
            ->row(true)[0]
        ;

        // Aktivste Mitglieder (Beitraege je Mitglied)
        $topPosters = Database::getInstance()
            ->prepare('SELECT p.author, COUNT(*) AS anzahl FROM tl_synapsis_post p INNER JOIN tl_synapsis_topic t ON t.id = p.pid WHERE t.pid IN ('.$placeholders.') AND p.published = ? GROUP BY p.author ORDER BY anzahl DESC')
            ->limit(5)
            ->execute(...array_merge($forumIds, ['1']))
        ;

        $list = [];

        while ($topPosters->next()) {
            $list[] = [
                'name' => $this->memberName((int) $topPosters->author),
                'avatar' => $this->avatar((int) $topPosters->author),
                'count' => (int) $topPosters->anzahl,
            ];
        }

        return [
            'topics' => $topics,
            'posts' => $posts,
            'members' => count($list),
            'topPosters' => $list,
        ];
    }

    /**
     * Rendert die Dateianhaenge eines Beitrags: Bilder inline, sonst als Link.
     *
     * @param mixed $attachments
     *
     * @return array<array<string, string>>
     */
    private function renderAttachments($attachments): array
    {
        $uuids = StringUtil::deserialize($attachments, true);

        if ([] === $uuids) {
            return [];
        }

        $models = FilesModel::findMultipleByUuids($uuids);

        if (null === $models) {
            return [];
        }

        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $imageExtensions = StringUtil::trimsplit(',', (string) \Contao\Config::get('validImageTypes'));
        $result = [];

        foreach ($models as $model) {
            if (!is_file($rootDir.'/'.$model->path)) {
                continue;
            }

            $extension = strtolower(pathinfo($model->path, PATHINFO_EXTENSION));

            $result[] = [
                'path' => $model->path,
                'name' => basename($model->path),
                'isImage' => in_array($extension, $imageExtensions, true) ? '1' : '',
            ];
        }

        return $result;
    }

    /**
     * Erzeugt ein Lucide-Avatar-Icon, dessen Farbe sich aus der Mitglieds-ID
     * ableitet, damit jedes Mitglied ein wiedererkennbares Standard-Icon hat.
     */
    private function avatar(int $memberId): string
    {
        $hue = ($memberId * 47) % 360;
        $color = 'hsl('.$hue.', 55%, 45%)';

        // Lucide "circle-user-round"
        return '<span class="synapsis-avatar" style="background:'.$color.'">'
            .'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
            .'<path d="M18 20a6 6 0 0 0-12 0"/><circle cx="12" cy="10" r="4"/><circle cx="12" cy="12" r="10"/>'
            .'</svg></span>'
        ;
    }

    // -------------------------------------------------------------------------
    // Datenbank-Helfer
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>|null
     */
    private function findForum(int $id): ?array
    {
        $row = Database::getInstance()
            ->prepare("SELECT * FROM tl_synapsis_forum WHERE id = ? AND type = 'forum'")
            ->execute($id)
            ->row()
        ;

        if (empty($row) || !$this->isVisible($id)) {
            return null;
        }

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findTopic(int $id): ?array
    {
        $row = Database::getInstance()
            ->prepare('SELECT * FROM tl_synapsis_topic WHERE id = ? AND published = ?')
            ->execute($id, '1')
            ->row()
        ;

        if (empty($row) || !$this->isVisible((int) $row['pid'])) {
            return null;
        }

        return $row;
    }

    /**
     * Direkte, veroeffentlichte Kinder eines Knotens in Sortierreihenfolge.
     *
     * @return array<array<string, mixed>>
     */
    private function findChildren(int $pid): array
    {
        return Database::getInstance()
            ->prepare('SELECT * FROM tl_synapsis_forum WHERE pid = ? AND published = ? ORDER BY sorting')
            ->execute($pid, '1')
            ->fetchAllAssoc()
        ;
    }

    /**
     * Sammelt die IDs aller Foren unterhalb eines Knotens (inkl. des Knotens
     * selbst, falls es ein Forum ist).
     *
     * @return array<int>
     */
    private function collectForumIds(int $startId): array
    {
        $ids = [];
        $queue = [$startId];

        $startType = (string) Database::getInstance()
            ->prepare('SELECT type FROM tl_synapsis_forum WHERE id = ?')
            ->execute($startId)
            ->row(true)[0]
        ;

        if ('forum' === $startType) {
            $ids[] = $startId;
        }

        while ([] !== $queue) {
            $current = array_shift($queue);

            $children = Database::getInstance()
                ->prepare('SELECT id, type FROM tl_synapsis_forum WHERE pid = ?')
                ->execute($current)
                ->fetchAllAssoc()
            ;

            foreach ($children as $child) {
                $queue[] = (int) $child['id'];

                if ('forum' === $child['type']) {
                    $ids[] = (int) $child['id'];
                }
            }
        }

        return [] === $ids ? [0] : array_values(array_unique($ids));
    }

    /**
     * Neueste Themen des gesamten Startpunkts.
     *
     * @return array<array<string, mixed>>
     */
    private function findNewestTopics(int $limit): array
    {
        $forumIds = $this->collectForumIds($this->rootId);
        $placeholders = implode(',', array_fill(0, count($forumIds), '?'));

        $topics = Database::getInstance()
            ->prepare('SELECT * FROM tl_synapsis_topic WHERE pid IN ('.$placeholders.') AND published = ? ORDER BY date DESC')
            ->limit($limit)
            ->execute(...array_merge($forumIds, ['1']))
        ;

        $rows = [];

        while ($topics->next()) {
            $rows[] = $this->decorateTopic($topics->row());
        }

        return $rows;
    }

    /**
     * Ermittelt den letzten Beitrag eines Forenbereichs oder eines Themas.
     *
     * @param array<int> $forumIds
     *
     * @return array<string, mixed>|null
     */
    private function findLastPost(array $forumIds, int $topicId = 0): ?array
    {
        if ($topicId > 0) {
            $row = Database::getInstance()
                ->prepare('SELECT p.date, p.author FROM tl_synapsis_post p WHERE p.pid = ? AND p.published = ? ORDER BY p.date DESC')
                ->limit(1)
                ->execute($topicId, '1')
                ->row()
            ;
        } else {
            if ([] === $forumIds) {
                return null;
            }

            $placeholders = implode(',', array_fill(0, count($forumIds), '?'));

            $row = Database::getInstance()
                ->prepare('SELECT p.date, p.author, t.title, t.id AS topicId FROM tl_synapsis_post p INNER JOIN tl_synapsis_topic t ON t.id = p.pid WHERE t.pid IN ('.$placeholders.') AND p.published = ? AND t.published = ? ORDER BY p.date DESC')
                ->limit(1)
                ->execute(...array_merge($forumIds, ['1', '1']))
                ->row()
            ;
        }

        if (empty($row)) {
            return null;
        }

        return [
            'authorName' => $this->memberName((int) $row['author']),
            'dateFormatted' => $this->formatDate((int) $row['date']),
            'title' => $row['title'] ?? '',
            'url' => isset($row['topicId']) ? $this->pageUrl(['topic' => (int) $row['topicId']]) : '',
        ];
    }

    /**
     * Baut die Kette vom Forum bis zum Startpunkt und wertet Sichtbarkeit sowie
     * Zugriffsschutz aus.
     */
    private function isVisible(int $forumId): bool
    {
        $chain = [];
        $currentId = $forumId;
        $guard = 0;

        while ($currentId > 0 && $guard < 100) {
            $node = Database::getInstance()
                ->prepare('SELECT id, pid, published, protected, groups FROM tl_synapsis_forum WHERE id = ?')
                ->execute($currentId)
                ->row()
            ;

            if (empty($node)) {
                return false;
            }

            $chain[] = $node;
            $currentId = (int) $node['pid'];
            ++$guard;

            // Nur bis zum Startpunkt dieses Moduls aufsteigen
            if ((int) $node['id'] === $this->rootId) {
                break;
            }
        }

        return $this->access->isAccessible($chain, $this->getMemberGroupIds());
    }

    /**
     * Baut die Brotkrumen-Navigation vom Startpunkt bis zum aktuellen Knoten.
     *
     * @return array<array<string, string>>
     */
    private function buildBreadcrumb(int $forumId, string $topicTitle = ''): array
    {
        $items = [];
        $currentId = $forumId;
        $guard = 0;

        while ($currentId > 0 && $guard < 100) {
            $node = Database::getInstance()
                ->prepare('SELECT id, pid, type, title FROM tl_synapsis_forum WHERE id = ?')
                ->execute($currentId)
                ->row()
            ;

            if (empty($node)) {
                break;
            }

            if ((int) $node['id'] === $this->rootId) {
                break;
            }

            array_unshift($items, [
                'title' => (string) $node['title'],
                'url' => 'forum' === $node['type'] ? $this->pageUrl(['forum' => (int) $node['id']]) : '',
            ]);

            $currentId = (int) $node['pid'];
            ++$guard;
        }

        array_unshift($items, [
            'title' => $GLOBALS['TL_LANG']['MSC']['synapsisHome'] ?? 'Forum',
            'url' => $this->pageUrl([]),
        ]);

        if ('' !== $topicTitle) {
            $items[] = ['title' => $topicTitle, 'url' => ''];
        }

        return $items;
    }

    // -------------------------------------------------------------------------
    // Allgemeine Helfer
    // -------------------------------------------------------------------------

    /**
     * Baut eine URL auf der aktuellen Seite mit den angegebenen Parametern.
     *
     * @param array<string, int|string> $params
     */
    private function pageUrl(array $params): string
    {
        global $objPage;

        $base = null !== $objPage ? $objPage->getFrontendUrl() : '';
        $params = array_filter($params, static fn ($value) => '' !== $value && null !== $value);

        return [] === $params ? $base : $base.'?'.http_build_query($params);
    }

    /**
     * Aktuelle Seitennummer der Paginierung (fuer beide Zaehler-Parameter).
     */
    private function getCurrentPage(): int
    {
        $forumPage = (int) Input::get('page_s'.$this->id);
        $topicPage = (int) Input::get('page_p'.$this->id);

        return max(1, $forumPage, $topicPage);
    }

    /**
     * Grobe Berechnung der letzten Beitragsseite (fuer den Sprung nach dem
     * Absenden einer Antwort).
     */
    private function lastPostPage(): int
    {
        $perPage = max(1, (int) $this->synapsis_perPage);
        $total = (int) Database::getInstance()
            ->prepare('SELECT COUNT(*) FROM tl_synapsis_post WHERE pid = ? AND published = ?')
            ->execute((int) $this->activeTopic['id'], '1')
            ->row(true)[0]
        ;

        return (int) ceil(max(1, $total) / $perPage);
    }

    /**
     * Prueft, ob ein Frontend-Mitglied angemeldet ist.
     */
    private function isMemberLoggedIn(): bool
    {
        return System::getContainer()->get('contao.security.token_checker')->hasFrontendUser();
    }

    /**
     * Gruppen-IDs des angemeldeten Mitglieds (leer, wenn nicht angemeldet).
     *
     * @return array<int>
     */
    private function getMemberGroupIds(): array
    {
        if (!$this->isMemberLoggedIn()) {
            return [];
        }

        $groups = StringUtil::deserialize(FrontendUser::getInstance()->groups, true);

        return array_map('intval', $groups);
    }

    /**
     * Liefert den Anzeigenamen eines Mitglieds.
     */
    private function memberName(int $memberId): string
    {
        if (0 === $memberId) {
            return $GLOBALS['TL_LANG']['MSC']['synapsisUnknown'] ?? 'Unbekannt';
        }

        $member = Database::getInstance()
            ->prepare('SELECT firstname, lastname, username FROM tl_member WHERE id = ?')
            ->execute($memberId)
            ->row()
        ;

        if (empty($member)) {
            return $GLOBALS['TL_LANG']['MSC']['synapsisUnknown'] ?? 'Unbekannt';
        }

        $name = trim(($member['firstname'] ?? '').' '.($member['lastname'] ?? ''));

        return '' !== $name ? $name : (string) $member['username'];
    }

    /**
     * Formatiert einen Zeitstempel im eingestellten Datums-/Zeitformat.
     */
    private function formatDate(int $timestamp): string
    {
        if (0 === $timestamp) {
            return '';
        }

        $format = (string) \Contao\Config::get('datim');

        return Date::parse('' !== $format ? $format : 'd.m.Y H:i', $timestamp);
    }

    /**
     * Saeubert den vom Editor gelieferten HTML-Text.
     */
    private function cleanText(string $text): string
    {
        return StringUtil::restoreBasicEntities(StringUtil::stripInsertTags($text));
    }

    /**
     * Erzeugt einen eindeutigen Alias aus einem Titel.
     */
    private function uniqueAlias(string $table, string $title): string
    {
        $alias = StringUtil::generateAlias($title);
        $exists = (bool) Database::getInstance()
            ->prepare('SELECT id FROM '.$table.' WHERE alias = ?')
            ->execute($alias)
            ->numRows
        ;

        return $exists ? $alias.'-'.substr(md5(uniqid('', true)), 0, 6) : $alias;
    }

    /**
     * Bindet TinyMCE (aus den Contao-Assets) fuer die Beitragsfelder ein.
     */
    private function enableEditor(): void
    {
        if (!$this->synapsis_editor) {
            $this->Template->editor = false;

            return;
        }

        $this->Template->editor = true;

        $GLOBALS['TL_JAVASCRIPT']['synapsis_tinymce'] = 'assets/tinymce4/js/tinymce.min.js|static';

        $GLOBALS['TL_BODY']['synapsis_tinymce_init'] = '<script>document.addEventListener("DOMContentLoaded",function(){'
            .'if(typeof tinymce==="undefined")return;'
            .'tinymce.init({'
            .'selector:"textarea.synapsis-editor",'
            .'license_key:"gpl",'
            .'base_url:"'.\Contao\Controller::addStaticUrlTo('assets/tinymce4/js').'",'
            .'suffix:".min",'
            .'menubar:false,'
            .'height:260,'
            .'plugins:"lists link image emoticons autolink",'
            .'toolbar:"bold italic | bullist numlist | link image emoticons",'
            .'branding:false'
            .'});});</script>'
        ;
    }
}
