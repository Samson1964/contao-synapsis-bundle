<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSynapsisBundle\Modules;

use Contao\BackendTemplate;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\Database;
use Contao\Date;
use Contao\Dbafs;
use Contao\Email;
use Contao\Environment;
use Contao\FilesModel;
use Contao\FileUpload;
use Contao\FrontendUser;
use Contao\Input;
use Contao\Module;
use Contao\Pagination;
use Contao\StringUtil;
use Contao\System;
use Composer\InstalledVersions;
use Symfony\Component\HttpFoundation\Response;
use Schachbulle\ContaoSynapsisBundle\Frontend\AuthorLabel;
use Schachbulle\ContaoSynapsisBundle\Frontend\AvatarResolver;
use Schachbulle\ContaoSynapsisBundle\Frontend\BanManager;
use Schachbulle\ContaoSynapsisBundle\Frontend\BBCode;
use Schachbulle\ContaoSynapsisBundle\Frontend\ForumAccess;
use Schachbulle\ContaoSynapsisBundle\Frontend\LikeManager;
use Schachbulle\ContaoSynapsisBundle\Frontend\LucideIcons;
use Schachbulle\ContaoSynapsisBundle\Frontend\NotificationTemplate;
use Schachbulle\ContaoSynapsisBundle\Frontend\PollAccess;
use Schachbulle\ContaoSynapsisBundle\Frontend\PollManager;
use Schachbulle\ContaoSynapsisBundle\Frontend\RankResolver;
use Schachbulle\ContaoSynapsisBundle\Frontend\ReadTracker;
use Schachbulle\ContaoSynapsisBundle\Frontend\RoleAccess;
use Schachbulle\ContaoSynapsisBundle\Frontend\WordFilter;
use Schachbulle\ContaoSynapsisBundle\SchachbulleContaoSynapsisBundle;

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
     * Datensatz der aktiven Kategorie (Ansicht category).
     *
     * @var array<string, mixed>|null
     */
    private $activeCategory;

    /**
     * Zugriffshelfer fuer die Vererbung von Schutz und Veroeffentlichung.
     *
     * @var ForumAccess
     */
    private $access;

    /**
     * Lesestand-Verwaltung (lazy erzeugt).
     *
     * @var ReadTracker|null
     */
    private $readTracker;

    /**
     * "Gefaellt mir"-Verwaltung (lazy erzeugt).
     *
     * @var LikeManager|null
     */
    private $likeManager;

    /**
     * Sperren-Verwaltung (Tabelle tl_synapsis_ban), lazy erzeugt.
     *
     * @var BanManager|null
     */
    private $banManager;

    /**
     * Zugriffshelfer fuer das Umfragen-Erstellrecht.
     *
     * @var PollAccess
     */
    private $pollAccess;

    /**
     * Zugriffshelfer fuer die Rollen (Administrator/Moderator).
     *
     * @var RoleAccess
     */
    private $roleAccess;

    /**
     * Umfragen-Verwaltung (lazy erzeugt).
     *
     * @var PollManager|null
     */
    private $pollManager;

    /**
     * Aktive Mitglieder-Unteransicht (subs|sig|me|unread|liked) oder '' (keine).
     *
     * @var string
     */
    private $panel = '';

    /**
     * Zwischenspeicher der lesbaren Foren-IDs dieses Startpunkts.
     *
     * @var array<int>|null
     */
    private $readableForumIdsCache;

    /**
     * Zwischenspeicher: darf der Besucher irgendwo im Startpunkt moderieren?
     *
     * @var bool|null
     */
    private $canSeeReportsCache;

    /**
     * Zwischenspeicher: darf der Besucher irgendwo im Startpunkt sperren?
     *
     * @var bool|null
     */
    private $canManageBansCache;

    /**
     * Zwischenspeicher: sollen Moderatoren-Namen im Frontend angezeigt werden?
     * (Einstellung am Startpunkt.)
     *
     * @var bool|null
     */
    private $showModeratorsCache;

    /**
     * Zwischenspeicher fuer die Rangstufen-Logik.
     *
     * @var RankResolver|null
     */
    private $rankResolver;

    /**
     * Zwischenspeicher: Mitglieds-ID => Rangtitel.
     *
     * @var array<int, string>
     */
    private $rankCache = [];

    /**
     * Datensatz des aktiven Profil-Mitglieds (Ansicht profile).
     *
     * @var array<string, mixed>|null
     */
    private $activeMember;

    /**
     * Zwischenspeicher fuer den Wortfilter.
     *
     * @var WordFilter|null
     */
    private $wordFilter;

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
        $this->pollAccess = new PollAccess();
        $this->roleAccess = new RoleAccess();
        $this->rootId = (int) $this->synapsis_root;

        if (0 === $this->rootId) {
            return '';
        }

        // RSS-Feed: XML ausgeben und die Seitenausgabe kurzschliessen.
        if (null !== Input::get('feed')) {
            $this->emitFeed();
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

        // Farbschema (global in den Einstellungen gewaehlt) als Wrapper-Klasse.
        $scheme = (string) ($this->forumSettings()['colorScheme'] ?? '');
        $this->Template->schemeClass = \in_array($scheme, ['petrol', 'gold', 'rot', 'orange'], true) ? ' synapsis-scheme--'.$scheme : '';

        // Praesenz des aktuellen Besuchers festhalten ("Wer ist online").
        $this->trackOnline();

        // Mitglieder-Navigation (untere Box): nur fuer angemeldete Mitglieder,
        // auf jeder Seite. Der aktive Menuepunkt wird nicht verlinkt.
        $this->Template->memberNav = $this->isMemberLoggedIn() ? $this->buildMemberNav() : [];
        $this->Template->memberNavTitle = $GLOBALS['TL_LANG']['MSC']['synapsisMemberArea'] ?? 'Mein Bereich';

        switch ($this->view) {
            case 'category':
                $this->compileCategory();
                break;

            case 'profile':
                $this->compileProfile();
                break;

            case 'forum':
                $this->compileForum();
                break;

            case 'topic':
                $this->compileTopic();
                break;

            case 'newtopic':
                $this->compileNewTopic();
                break;

            case 'panel':
                $this->compilePanel();
                break;

            case 'search':
                $this->compileSearch();
                break;

            default:
                $this->compileIndex();
        }
    }

    /**
     * Baut die Punkte der Mitglieder-Navigation ("Mein Bereich"). Der aktuell
     * geoeffnete Punkt ist als aktiv markiert (wird im Template nicht verlinkt).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildMemberNav(): array
    {
        $items = [
            'notify' => $GLOBALS['TL_LANG']['MSC']['synapsisNotifications'] ?? 'Benachrichtigungen',
            'me' => $GLOBALS['TL_LANG']['MSC']['synapsisMyPosts'] ?? 'Meine Beiträge',
            'unread' => $GLOBALS['TL_LANG']['MSC']['synapsisUnread'] ?? 'Ungelesene Beiträge',
            'liked' => $GLOBALS['TL_LANG']['MSC']['synapsisLikedPosts'] ?? 'Gefällt mir',
            'subs' => $GLOBALS['TL_LANG']['MSC']['synapsisSubscriptions'] ?? 'Abonnements',
            'sig' => $GLOBALS['TL_LANG']['MSC']['synapsisSignature'] ?? 'Signatur',
        ];

        // "Meldungen" nur fuer Mitglieder, die irgendwo moderieren duerfen.
        if ($this->canSeeReports()) {
            $items['reports'] = $GLOBALS['TL_LANG']['MSC']['synapsisReports'] ?? 'Meldungen';
        }

        // "Sperren" nur fuer Mitglieder mit Sperr-Recht.
        if ($this->canManageBans()) {
            $items['bans'] = $GLOBALS['TL_LANG']['MSC']['synapsisBans'] ?? 'Sperren';
        }

        // Ungelesene Benachrichtigungen als Zahl-Badge am Punkt "Benachrichtigungen".
        $unread = $this->unreadNotificationCount();

        $nav = [];

        foreach ($items as $key => $label) {
            $nav[] = [
                'key' => $key,
                'label' => $label,
                'url' => $this->pageUrl(['panel' => $key]),
                'active' => 'panel' === $this->view && $this->panel === $key,
                'badge' => 'notify' === $key && $unread > 0 ? $unread : 0,
            ];
        }

        return $nav;
    }

    /**
     * Ermittelt Ansicht und aktive Datensaetze aus den URL-Parametern und
     * setzt das passende Template.
     */
    private function resolveView(): void
    {
        // Mitglieder-Unteransichten (Abos, Signatur, Meine/Ungelesene Beitraege)
        $panel = (string) Input::get('panel');

        if ('' !== $panel && $this->isMemberLoggedIn() && \in_array($panel, ['subs', 'sig', 'me', 'unread', 'liked', 'reports', 'notify', 'bans'], true)) {
            $this->panel = $panel;
            $this->view = 'panel';
            $this->strTemplate = 'mod_synapsis_panel';

            return;
        }

        // Forensuche (innerhalb dieses Startpunkts)
        if (null !== Input::get('q')) {
            $this->view = 'search';
            $this->strTemplate = 'mod_synapsis_search';

            return;
        }

        // Mitgliederprofil (ueber Klick auf einen Autornamen)
        $memberId = (int) Input::get('member');

        if ($memberId > 0 && null !== ($member = $this->findProfileMember($memberId))) {
            $this->activeMember = $member;
            $this->view = 'profile';
            $this->strTemplate = 'mod_synapsis_profile';

            return;
        }

        // Einzelne Kategorie (ueber die Pfadnavigation aufrufbar)
        $categoryId = (int) Input::get('category');

        if ($categoryId > 0 && null !== ($category = $this->findCategory($categoryId))) {
            $this->activeCategory = $category;
            $this->view = 'category';
            $this->strTemplate = 'mod_synapsis_category';

            return;
        }

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

            if (Input::get('new') && !$forum['closed'] && $this->canWrite($forumId)) {
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
        // "Alles als gelesen markieren" verarbeiten, bevor gerendert wird.
        $this->handleMarkRead();

        $categories = [];

        // Direkte Kinder des Startpunkts (Kategorien und Foren)
        foreach ($this->findChildren($this->rootId) as $node) {
            if ('forum' === $node['type']) {
                // Forum direkt unter dem Startpunkt: eigene Pseudo-Kategorie
                if ($this->isVisible((int) $node['id'])) {
                    $categories[] = [
                        'title' => '',
                        'description' => '',
                        'forums' => [$this->decorateForum($node)],
                    ];
                }

                continue;
            }

            // Kategorie: ihre lesbaren Foren einsammeln. Die Kategorie selbst
            // traegt keine eigene Gaeste-Freigabe - sie wird angezeigt, sobald
            // sie mindestens ein fuer den Besucher lesbares Forum enthaelt (der
            // Mitglieder-/Veroeffentlichungsschutz wird pro Forum vererbt).
            $forums = [];

            foreach ($this->findChildren((int) $node['id']) as $child) {
                if ('forum' === $child['type'] && $this->isVisible((int) $child['id'])) {
                    $forums[] = $this->decorateForum($child);
                }
            }

            if ([] !== $forums) {
                $categories[] = [
                    'title' => $node['title'],
                    'description' => $node['description'],
                    'forums' => $forums,
                ];
            }
        }

        $this->Template->breadcrumb = $this->buildBreadcrumb(0);
        $this->Template->categories = $categories;
        $this->Template->newestTopics = $this->findNewestTopics(10);
        $this->Template->statistics = $this->buildStatistics();
        $this->Template->online = $this->onlineUsers();

        // "Alles als gelesen markieren" (ganzer Startpunkt), nur fuer Mitglieder.
        $this->Template->canMarkRead = $this->isMemberLoggedIn();
        $this->Template->markReadFormId = 'synapsis_readall_'.$this->id;
        $this->Template->markReadForum = 0;
        $this->Template->markReadAction = $this->pageUrl([]);

        // RSS-Feed des Startpunkts (Link + Auto-Discovery im Seitenkopf).
        $this->Template->feedUrl = $this->pageUrl(['feed' => 1]);
        $GLOBALS['TL_HEAD']['synapsis_feed'] = '<link rel="alternate" type="application/rss+xml" href="'.$this->absoluteUrl(['feed' => 1]).'">';
    }

    /**
     * Einzelansicht einer Kategorie: listet nur deren (lesbare) Foren. Ueber die
     * Pfadnavigation (Brotkrumen) erreichbar, damit eine Kategorie auch separat
     * aufgerufen werden kann.
     */
    private function compileCategory(): void
    {
        $categoryId = (int) $this->activeCategory['id'];

        $forums = [];

        foreach ($this->findChildren($categoryId) as $child) {
            if ('forum' === $child['type'] && $this->isVisible((int) $child['id'])) {
                $forums[] = $this->decorateForum($child);
            }
        }

        $this->Template->category = $this->activeCategory;
        $this->Template->breadcrumb = $this->buildBreadcrumb($categoryId);
        $this->Template->forums = $forums;
        $this->Template->empty = [] === $forums;
    }

    /**
     * Laedt eine veroeffentlichte Kategorie dieses Startpunkts (oder null). Die
     * Zugehoerigkeit zum Startpunkt wird ueber die Knotenliste geprueft, damit
     * keine fremde Kategorie aufgerufen werden kann.
     *
     * @return array<string, mixed>|null
     */
    private function findCategory(int $id): ?array
    {
        $row = Database::getInstance()
            ->prepare("SELECT * FROM tl_synapsis_forum WHERE id = ? AND type = 'category' AND published = ?")
            ->execute($id, '1')
            ->row()
        ;

        if (empty($row) || !$this->belongsToRoot($id)) {
            return null;
        }

        return $row;
    }

    /**
     * Prueft, ob ein Knoten (ueber die pid-Kette) zum Startpunkt dieses Moduls
     * gehoert.
     */
    private function belongsToRoot(int $id): bool
    {
        $currentId = $id;
        $guard = 0;

        while ($currentId > 0 && $guard < 100) {
            if ($currentId === $this->rootId) {
                return true;
            }

            $currentId = (int) Database::getInstance()
                ->prepare('SELECT pid FROM tl_synapsis_forum WHERE id = ?')
                ->execute($currentId)
                ->row(true)[0]
            ;
            ++$guard;
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Community & Mitglieder (Profil, "Wer ist online", Rangstufen)
    // -------------------------------------------------------------------------

    /**
     * Laedt ein Mitglied fuer die Profilansicht (oder null).
     *
     * @return array<string, mixed>|null
     */
    private function findProfileMember(int $id): ?array
    {
        $row = Database::getInstance()
            ->prepare('SELECT id, username, firstname, lastname, dateAdded FROM tl_member WHERE id = ?')
            ->execute($id)
            ->row()
        ;

        return empty($row) ? null : $row;
    }

    /**
     * Baut die Profilansicht eines Mitglieds: Avatar, Rang, Beitrittsdatum,
     * Beitragszahl (im Startpunkt), Signatur und die letzten Beitraege.
     */
    private function compileProfile(): void
    {
        $memberId = (int) $this->activeMember['id'];
        $name = $this->authorLabel($memberId, '');

        $breadcrumb = $this->buildBreadcrumb(0);
        $breadcrumb[] = ['title' => $name, 'url' => ''];
        $this->Template->breadcrumb = $breadcrumb;

        $signature = $this->memberSignature($memberId);
        $joined = (int) ($this->activeMember['dateAdded'] ?? 0);

        $this->Template->profile = [
            'name' => $name,
            'avatar' => $this->avatar($memberId),
            'rank' => $this->memberRank($memberId),
            'postCount' => $this->authorPostCountInRoot($memberId),
            'joined' => $joined > 0 ? $this->formatDate($joined) : '',
            'signature' => '' !== $signature ? BBCode::toHtml($signature) : '',
        ];

        $this->Template->recentPosts = $this->memberRecentPosts($memberId, 10);
    }

    /**
     * Die letzten Beitraege eines Mitglieds innerhalb dieses Startpunkts.
     *
     * @return array<array<string, mixed>>
     */
    private function memberRecentPosts(int $memberId, int $limit): array
    {
        $forumIds = $this->readableForumIds();
        $placeholders = implode(',', array_fill(0, \count($forumIds), '?'));

        $rows = Database::getInstance()
            ->prepare(
                'SELECT p.pid AS topicId, p.date, t.title FROM tl_synapsis_post p'
                .' INNER JOIN tl_synapsis_topic t ON t.id = p.pid'
                .' WHERE p.author = ? AND p.published = ? AND t.published = ? AND t.pid IN ('.$placeholders.')'
                .' ORDER BY p.date DESC'
            )
            ->limit($limit)
            ->execute(...array_merge([$memberId, '1', '1'], $forumIds))
            ->fetchAllAssoc()
        ;

        $items = [];

        foreach ($rows as $row) {
            $items[] = [
                'title' => (string) $row['title'],
                'url' => $this->pageUrl(['topic' => (int) $row['topicId']]),
                'dateFormatted' => $this->formatDate((int) $row['date']),
            ];
        }

        return $items;
    }

    /**
     * Haelt die Praesenz des aktuellen Besuchers fest ("Wer ist online").
     * Eine Zeile je Sitzung; member = 0 fuer Gaeste. Gelegentlich werden alte
     * Zeilen entfernt.
     */
    private function trackOnline(): void
    {
        if ('1' !== (string) ($this->forumSettings()['showOnline'] ?? '1')) {
            return;
        }

        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if (null === $request || !$request->hasSession()) {
            return;
        }

        $session = $request->getSession();

        if (!$session->isStarted()) {
            return;
        }

        $sessionId = substr(hash('sha256', (string) $session->getId()), 0, 64);
        $memberId = $this->isMemberLoggedIn() ? (int) FrontendUser::getInstance()->id : 0;
        $now = time();
        $db = Database::getInstance();

        $db->prepare('INSERT INTO tl_synapsis_online (sessionId, member, tstamp) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE member = ?, tstamp = ?')
            ->execute($sessionId, $memberId, $now, $memberId, $now)
        ;

        // Aufraeumen (nur gelegentlich, um Schreiblast gering zu halten).
        if (1 === random_int(1, 20)) {
            $db->prepare('DELETE FROM tl_synapsis_online WHERE tstamp < ?')->execute($now - 900);
        }
    }

    /**
     * Ermittelt die aktuell online-Mitglieder (Namen + Profil-Link) und die Zahl
     * der Gaeste (aktiv in den letzten 5 Minuten).
     *
     * @return array{members: array<array<string, string>>, guests: int}
     */
    private function onlineUsers(): array
    {
        if ('1' !== (string) ($this->forumSettings()['showOnline'] ?? '1')) {
            return ['members' => [], 'guests' => 0];
        }

        $threshold = time() - 300;
        $db = Database::getInstance();

        $rows = $db
            ->prepare('SELECT DISTINCT member FROM tl_synapsis_online WHERE tstamp >= ? AND member > 0')
            ->execute($threshold)
            ->fetchAllAssoc()
        ;

        $members = [];

        foreach (array_map('intval', array_column($rows, 'member')) as $mid) {
            $members[] = ['name' => $this->authorLabel($mid, ''), 'url' => $this->profileUrl($mid)];
        }

        $guests = (int) $db
            ->prepare('SELECT COUNT(*) FROM tl_synapsis_online WHERE tstamp >= ? AND member = 0')
            ->execute($threshold)
            ->row(true)[0]
        ;

        return ['members' => $members, 'guests' => $guests];
    }

    /**
     * Rangtitel eines Mitglieds anhand seiner Beitragszahl im Startpunkt
     * (leer, wenn Raenge abgeschaltet sind oder keine Stufe greift). Gecacht.
     */
    private function memberRank(int $memberId): string
    {
        if ($memberId <= 0 || '1' !== (string) ($this->forumSettings()['showRanks'] ?? '1')) {
            return '';
        }

        if (isset($this->rankCache[$memberId])) {
            return $this->rankCache[$memberId];
        }

        return $this->rankCache[$memberId] = $this->rankResolver()->titleFor($this->authorPostCountInRoot($memberId));
    }

    /**
     * Liefert die (gecachte) Rangstufen-Logik aus der Konfiguration.
     */
    private function rankResolver(): RankResolver
    {
        if (null === $this->rankResolver) {
            $this->rankResolver = RankResolver::fromConfig((string) ($this->forumSettings()['ranks'] ?? ''));
        }

        return $this->rankResolver;
    }

    /**
     * Liefert den (gecachten) Wortfilter aus der globalen Konfiguration.
     */
    private function wordFilter(): WordFilter
    {
        if (null === $this->wordFilter) {
            $this->wordFilter = WordFilter::fromConfig((string) ($this->forumSettings()['wordFilter'] ?? ''));
        }

        return $this->wordFilter;
    }

    /**
     * Profil-Link eines Mitglieds (leer fuer Gaeste, author = 0).
     */
    private function profileUrl(int $memberId): string
    {
        return $memberId > 0 ? $this->pageUrl(['member' => $memberId]) : '';
    }

    /**
     * Verarbeitet "als gelesen markieren": read_forum = 0 markiert den ganzen
     * Startpunkt, sonst nur das angegebene Forum (samt Unterforen). Nur fuer
     * angemeldete Mitglieder.
     */
    private function handleMarkRead(): void
    {
        if ('synapsis_readall_'.$this->id !== Input::post('FORM_SUBMIT') || !$this->isMemberLoggedIn()) {
            return;
        }

        $forumId = (int) Input::post('read_forum');
        $readable = $this->readableForumIds();

        $scope = $forumId > 0
            ? array_values(array_intersect($this->collectForumIds($forumId), $readable))
            : $readable;

        $this->readTracker()->markAllRead((int) FrontendUser::getInstance()->id, $scope);

        $this->redirect($forumId > 0 ? $this->pageUrl(['forum' => $forumId]) : $this->pageUrl([]));
    }

    /**
     * Gibt den RSS-Feed (neueste Themen) aus und schliesst die weitere
     * Seitenausgabe ueber eine ResponseException kurz. Mit ?forum=<id> der Feed
     * dieses Forums, sonst der des ganzen Startpunkts.
     *
     * @throws ResponseException immer
     */
    private function emitFeed(): void
    {
        $forumId = (int) Input::get('forum');

        if ($forumId > 0 && null !== $this->findForum($forumId)) {
            $forumIds = array_values(array_intersect($this->collectForumIds($forumId), $this->readableForumIds()));
            $title = $this->forumTitle($forumId);
            $channelLink = $this->absoluteUrl(['forum' => $forumId]);
        } else {
            $forumIds = $this->readableForumIds();
            $title = (string) ($this->forumTitle($this->rootId) ?: ($GLOBALS['TL_LANG']['MSC']['synapsisForum'] ?? 'Forum'));
            $channelLink = $this->absoluteUrl([]);
        }

        $xml = $this->buildFeedXml($forumIds, $title, $channelLink);

        throw new ResponseException(new Response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']));
    }

    /**
     * Baut das RSS-2.0-Dokument aus den neuesten Themen der Foren.
     *
     * @param array<int> $forumIds
     */
    private function buildFeedXml(array $forumIds, string $title, string $channelLink): string
    {
        $placeholders = implode(',', array_fill(0, \count($forumIds), '?'));

        $topics = Database::getInstance()
            ->prepare('SELECT id, title, date, author, authorName FROM tl_synapsis_topic WHERE pid IN ('.$placeholders.') AND published = ? ORDER BY date DESC')
            ->limit(25)
            ->execute(...array_merge($forumIds, ['1']))
            ->fetchAllAssoc()
        ;

        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $items = '';

        foreach ($topics as $topic) {
            $topicId = (int) $topic['id'];
            $link = $this->absoluteUrl(['topic' => $topicId]);
            $author = $this->authorLabel((int) $topic['author'], (string) ($topic['authorName'] ?? ''));

            // Kurzbeschreibung aus dem ersten Beitrag des Themas.
            $firstText = (string) Database::getInstance()
                ->prepare('SELECT text FROM tl_synapsis_post WHERE pid = ? AND published = ? ORDER BY date ASC')
                ->limit(1)
                ->execute($topicId, '1')
                ->row(true)[0] ?? '';
            $excerpt = trim(preg_replace('/\s+/', ' ', strip_tags($firstText)) ?? '');

            if (mb_strlen($excerpt) > 300) {
                $excerpt = mb_substr($excerpt, 0, 300).'…';
            }

            $items .= '<item>'
                .'<title>'.$esc((string) $topic['title']).'</title>'
                .'<link>'.$esc($link).'</link>'
                .'<guid isPermaLink="true">'.$esc($link).'</guid>'
                .'<dc:creator>'.$esc($author).'</dc:creator>'
                .'<pubDate>'.date('r', (int) $topic['date']).'</pubDate>'
                .'<description><![CDATA['.str_replace(']]>', ']]&gt;', $excerpt).']]></description>'
                .'</item>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">'
            .'<channel>'
            .'<title>'.$esc($title).'</title>'
            .'<link>'.$esc($channelLink).'</link>'
            .'<description>'.$esc($title).'</description>'
            .$items
            .'</channel></rss>';
    }

    /**
     * Themenliste eines Forums (seitenweise, angeheftete zuerst).
     */
    private function compileForum(): void
    {
        $forumId = (int) $this->activeForum['id'];

        // Forum-Abonnement (E-Mail bei neuem Thema) umschalten, bevor gerendert wird.
        $this->handleForumSubscription();
        // "Forum als gelesen markieren" verarbeiten.
        $this->handleMarkRead();
        // Massen-Moderation (mehrere Themen loeschen/schliessen/verschieben).
        $this->handleBulkModeration();

        // Forum als gelesen markieren: nur fuer angemeldete Mitglieder anbieten.
        $this->Template->canMarkRead = $this->isMemberLoggedIn();
        $this->Template->markReadFormId = 'synapsis_readall_'.$this->id;
        $this->Template->markReadForum = $forumId;
        $this->Template->markReadAction = $this->pageUrl(['forum' => $forumId]);

        // RSS-Feed dieses Forums (Link + Auto-Discovery im Seitenkopf).
        $this->Template->feedUrl = $this->pageUrl(['forum' => $forumId, 'feed' => 1]);
        $GLOBALS['TL_HEAD']['synapsis_feed'] = '<link rel="alternate" type="application/rss+xml" title="'.StringUtil::specialchars((string) $this->activeForum['title']).'" href="'.$this->absoluteUrl(['forum' => $forumId, 'feed' => 1]).'">';

        $this->Template->forum = $this->activeForum;
        $this->Template->breadcrumb = $this->buildBreadcrumb($forumId);
        $this->Template->newTopicUrl = (!$this->activeForum['closed'] && $this->canWrite($forumId))
            ? $this->pageUrl(['forum' => $forumId, 'new' => 1])
            : '';
        $this->Template->closed = (bool) $this->activeForum['closed'];

        // Moderatoren-Namen (nur wenn am Startpunkt aktiviert).
        $this->Template->moderators = $this->showModerators() ? $this->moderatorNames($forumId) : [];

        // Forum abonnieren: nur fuer angemeldete Mitglieder anbieten.
        $this->Template->canSubscribeForum = $this->isMemberLoggedIn();
        $this->Template->isForumSubscribed = $this->isForumSubscribed($forumId);
        $this->Template->forumSubscribeAction = $this->pageUrl(['forum' => $forumId]);
        $this->Template->forumSubscribeFormId = 'synapsis_fsub_'.$this->id;

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

        // Massen-Moderation: Auswahlkaestchen + Aktionsleiste nur fuer
        // berechtigte Moderatoren/Administratoren einblenden.
        $canBulkLock = $this->canModerate($forumId, 'lock');
        $canBulkMove = $this->canModerate($forumId, 'move');
        $canBulkDelete = $this->canModerate($forumId, 'edit');
        $canBulk = $canBulkLock || $canBulkMove || $canBulkDelete;

        $this->Template->canBulk = $canBulk;
        $this->Template->canBulkLock = $canBulkLock;
        $this->Template->canBulkMove = $canBulkMove;
        $this->Template->canBulkDelete = $canBulkDelete;
        $this->Template->bulkFormId = 'synapsis_bulk_'.$this->id;
        $this->Template->bulkAction = $this->pageUrl(['forum' => $forumId]);
        $this->Template->bulkTargets = $canBulkMove ? $this->moveTargets($forumId) : [];

        if ($canBulk) {
            $this->addBulkScript();
        }
    }

    /**
     * Beitraege eines Themas samt Antwortformular; erhoeht den Ansichtszaehler.
     */
    private function compileTopic(): void
    {
        $topicId = (int) $this->activeTopic['id'];

        // Abo-Umschaltung und Antwort verarbeiten, bevor die Liste gerendert wird
        $this->handleSubscription();
        $this->handleLike();
        $this->handleVote();
        $this->handlePin();
        $this->handleLock();
        $this->handleMove();
        $this->handleEdit();
        $this->handleDelete();
        $this->handleReport();
        $this->handleBan();
        $this->handlePostSubmission();

        // Ansichtszaehler erhoehen - aber pro Sitzung nur einmal, damit ein
        // Reload nicht mitzaehlt (kein IP-Speicher, DSGVO-freundlich)
        if ($this->registerView($topicId)) {
            Database::getInstance()
                ->prepare('UPDATE tl_synapsis_topic SET views = views + 1 WHERE id = ?')
                ->execute($topicId)
            ;
        }

        // Wortfilter auf den Titel der Themenansicht + Brotkrumen anwenden.
        $this->activeTopic['title'] = $this->wordFilter()->filterText((string) $this->activeTopic['title']);

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

        // Thema fuer das angemeldete Mitglied als gelesen markieren
        $this->markTopicRead($topicId);

        // Abonnement-Schaltflaeche fuer angemeldete Mitglieder
        $this->Template->canSubscribe = $this->isMemberLoggedIn();
        $this->Template->isSubscribed = $this->isSubscribed($topicId);
        $this->Template->subscribeAction = $this->pageUrl(['topic' => $topicId]);
        $this->Template->subscribeFormId = 'synapsis_sub_'.$this->id;

        // "Gefaellt mir": gemeinsame Formulardaten (pro Beitrag eine Schaltflaeche)
        $this->Template->likeFormId = 'synapsis_like_'.$this->id;
        $this->Template->likeAction = $this->pageUrl(['topic' => $topicId, 'page_p'.$this->id => $this->getCurrentPage()]);

        // Umfrage zum Thema (falls vorhanden)
        $this->Template->poll = $this->buildPoll($topicId);

        // Moderation (nur fuer Administratoren/berechtigte Moderatoren)
        $moderatedForum = (int) $this->activeForum['id'];
        $this->Template->canPin = $this->canModerate($moderatedForum, 'pin');
        $this->Template->isPinned = (bool) $this->activeTopic['sticky'];
        $this->Template->pinFormId = 'synapsis_pin_'.$this->id;
        $this->Template->pinAction = $this->pageUrl(['topic' => $topicId]);

        $this->Template->canLock = $this->canModerate($moderatedForum, 'lock');
        $this->Template->lockFormId = 'synapsis_lock_'.$this->id;

        $canMove = $this->canModerate($moderatedForum, 'move');
        $this->Template->canMove = $canMove;
        $this->Template->moveFormId = 'synapsis_move_'.$this->id;
        $this->Template->moveTargets = $canMove ? $this->moveTargets($moderatedForum) : [];
        $this->Template->moderateAction = $this->pageUrl(['topic' => $topicId]);
        $this->Template->deleteFormId = 'synapsis_delete_'.$this->id;
        $this->Template->banFormId = 'synapsis_ban_'.$this->id;

        // Melde-Formular, wenn ein Beitrag gemeldet werden soll
        $this->Template->reportPost = null;
        $this->Template->reportFormId = 'synapsis_report_'.$this->id;
        $reportId = (int) Input::get('report');

        if ($reportId > 0 && $this->currentAuthorId() > 0) {
            $reportable = $this->findPostInTopic($reportId);

            if (null !== $reportable && (int) $reportable['author'] !== $this->currentAuthorId()) {
                $this->Template->reportPost = [
                    'id' => $reportId,
                    'action' => $this->pageUrl(['topic' => $topicId, 'report' => $reportId]),
                    'cancelUrl' => $this->pageUrl(['topic' => $topicId]),
                ];
            }
        }

        // Bearbeiten-Formular, wenn ein Beitrag zum Bearbeiten aufgerufen wurde
        $this->Template->editPost = null;
        $editId = (int) Input::get('edit');

        if ($editId > 0) {
            $editable = $this->findPostInTopic($editId);

            if (null !== $editable && $this->canModifyPost($editable)) {
                $this->enableEditor();
                $this->Template->editPost = [
                    'id' => $editId,
                    'text' => (string) $editable['text'],
                    'action' => $this->pageUrl(['topic' => $topicId, 'edit' => $editId]),
                    'formId' => 'synapsis_edit_'.$this->id,
                    'cancelUrl' => $this->pageUrl(['topic' => $topicId]),
                ];
            }
        }

        // Antwortformular in offenen Themen fuer alle Schreibberechtigten
        // (Mitglieder bzw. Gaeste mit Schreibrecht).
        $canReply = !$this->activeTopic['locked']
            && !$this->activeForum['closed']
            && $this->canWrite((int) $this->activeForum['id']);
        $this->Template->canReply = $canReply;
        $this->Template->locked = (bool) $this->activeTopic['locked'];
        // Hinweis anzeigen, wenn das Mitglied fuer das Forum gesperrt ist.
        $this->Template->isBanned = $this->isCurrentMemberBanned();

        $this->Template->replyPrefill = '';
        $this->Template->quotePost = 0;
        $this->Template->draftKey = '';

        if ($canReply) {
            $this->enableEditor();
            $this->Template->allowUploads = (bool) $this->synapsis_allowUploads;
            $this->Template->formAction = $this->pageUrl(['topic' => $topicId]);
            $this->Template->formId = 'synapsis_reply_'.$this->id;
            $this->Template->replyPrefill = $this->buildQuotePrefill();

            // Beim Zitieren die Beitrags-ID mitfuehren, damit der Autor benachrichtigt wird.
            $quoteId = (int) Input::get('quote');
            $this->Template->quotePost = ($quoteId > 0 && null !== $this->findPostInTopic($quoteId)) ? $quoteId : 0;

            // Entwurf-Zwischenspeicherung fuer das Antwortfeld.
            $this->Template->draftKey = 'syn_draft_'.$this->id.'_topic_'.$topicId;
            $this->addDraftScript();
        }
    }

    /**
     * Baut den vorbelegten Antworttext, wenn ein Beitrag zitiert wird
     * (URL-Parameter quote). Leer, wenn nichts zu zitieren ist.
     */
    private function buildQuotePrefill(): string
    {
        $quoteId = (int) Input::get('quote');

        if ($quoteId <= 0) {
            return '';
        }

        $post = $this->findPostInTopic($quoteId);

        if (null === $post) {
            return '';
        }

        $author = $this->authorLabel((int) $post['author'], (string) ($post['authorName'] ?? ''));
        $intro = sprintf($GLOBALS['TL_LANG']['MSC']['synapsisWrote'] ?? '%s schrieb:', $author);

        return '<blockquote><p><strong>'.htmlspecialchars($intro, ENT_QUOTES).'</strong></p>'
            .(string) $post['text']
            .'</blockquote><p><br></p>';
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
        $this->Template->isBanned = $this->isCurrentMemberBanned();
        $this->Template->allowUploads = (bool) $this->synapsis_allowUploads;
        $this->Template->formAction = $this->pageUrl(['forum' => $forumId, 'new' => 1]);
        $this->Template->formId = 'synapsis_topic_'.$this->id;
        $this->Template->cancelUrl = $this->pageUrl(['forum' => $forumId]);
        // Entwurf-Zwischenspeicherung fuer das Textfeld des neuen Themas.
        $this->Template->draftKey = 'syn_draft_'.$this->id.'_forum_'.$forumId;
        $this->addDraftScript();
        // Optionale Umfrage nur anbieten, wenn das Mitglied dazu berechtigt ist.
        $this->Template->canCreatePoll = $this->canCreatePoll($forumId);

        if ($this->Template->canCreatePoll) {
            $this->addPollScript();
        }
    }

    /**
     * Blendet die Umfrage-Felder nur ein, wenn "Umfrage hinzufuegen" aktiv ist
     * (progressive Verbesserung - ohne JavaScript bleiben die Felder sichtbar).
     */
    private function addPollScript(): void
    {
        $GLOBALS['TL_BODY']['synapsis_poll'] = '<script>document.addEventListener("DOMContentLoaded",function(){'
            .'document.querySelectorAll(".synapsis-pollform").forEach(function(fs){'
            .'var cb=fs.querySelector(".synapsis-pollform__enable"),f=fs.querySelector(".synapsis-pollform__fields");'
            .'if(!cb||!f)return;var u=function(){f.style.display=cb.checked?"":"none";};u();cb.addEventListener("change",u);'
            .'});});</script>'
        ;
    }

    // -------------------------------------------------------------------------
    // Forensuche
    // -------------------------------------------------------------------------

    /**
     * Durchsucht Themen (Titel) und Beitraege (Text) innerhalb dieses
     * Startpunkts und stellt die Trefferliste zusammen.
     */
    private function compileSearch(): void
    {
        $term = trim((string) Input::get('q'));

        $breadcrumb = $this->buildBreadcrumb(0);
        $breadcrumb[] = ['title' => $GLOBALS['TL_LANG']['MSC']['synapsisSearch'] ?? 'Suche', 'url' => ''];

        $this->Template->breadcrumb = $breadcrumb;
        $this->Template->query = $term;
        $this->Template->searchAction = $this->pageUrl([]);
        $this->Template->items = [];
        $this->Template->tooShort = false;

        // Zu kurze Eingabe: Hinweis nur, wenn ueberhaupt etwas eingegeben wurde.
        if (mb_strlen($term) < 2) {
            $this->Template->tooShort = '' !== $term;

            return;
        }

        $forumIds = $this->readableForumIds();
        $placeholders = implode(',', array_fill(0, count($forumIds), '?'));
        $like = '%'.$this->escapeLike($term).'%';

        // Themen, deren Titel passt, die einen passenden Beitrag enthalten ODER
        // deren Umfrage (Frage bzw. eine Antwortmoeglichkeit) passt.
        $sql = 'SELECT DISTINCT t.id, t.title, t.pid, t.date'
            .' FROM tl_synapsis_topic t'
            .' LEFT JOIN tl_synapsis_post p ON p.pid = t.id AND p.published = ?'
            .' WHERE t.published = ? AND t.pid IN ('.$placeholders.')'
            .' AND ('
            .'t.title LIKE ?'
            .' OR p.text LIKE ?'
            .' OR EXISTS (SELECT 1 FROM tl_synapsis_poll pl WHERE pl.pid = t.id AND pl.question LIKE ?)'
            .' OR EXISTS (SELECT 1 FROM tl_synapsis_poll pl2'
            .' INNER JOIN tl_synapsis_poll_option po ON po.pid = pl2.id'
            .' WHERE pl2.pid = t.id AND po.label LIKE ?)'
            .')'
            .' ORDER BY t.date DESC';

        $rows = Database::getInstance()
            ->prepare($sql)
            ->limit(100)
            ->execute(...array_merge(['1', '1'], $forumIds, [$like, $like, $like, $like]))
        ;

        $items = [];

        while ($rows->next()) {
            $items[] = [
                'title' => $this->wordFilter()->filterText((string) $rows->title),
                'url' => $this->pageUrl(['topic' => (int) $rows->id]),
                'forumTitle' => $this->forumTitle((int) $rows->pid),
                'dateFormatted' => $this->formatDate((int) $rows->date),
            ];
        }

        $this->Template->items = $items;
    }

    /**
     * Maskiert die LIKE-Sonderzeichen % _ und den Backslash, damit die
     * Sucheingabe woertlich (nicht als Muster) verwendet wird.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    // -------------------------------------------------------------------------
    // Mitglieder-Unteransichten (untere Navigationsbox)
    // -------------------------------------------------------------------------

    /**
     * Baut die gewaehlte Mitglieder-Unteransicht auf.
     */
    private function compilePanel(): void
    {
        $labels = [
            'notify' => $GLOBALS['TL_LANG']['MSC']['synapsisNotifications'] ?? 'Benachrichtigungen',
            'me' => $GLOBALS['TL_LANG']['MSC']['synapsisMyPosts'] ?? 'Meine Beiträge',
            'unread' => $GLOBALS['TL_LANG']['MSC']['synapsisUnread'] ?? 'Ungelesene Beiträge',
            'liked' => $GLOBALS['TL_LANG']['MSC']['synapsisLikedPosts'] ?? 'Gefällt mir',
            'subs' => $GLOBALS['TL_LANG']['MSC']['synapsisSubscriptions'] ?? 'Abonnements',
            'sig' => $GLOBALS['TL_LANG']['MSC']['synapsisSignature'] ?? 'Signatur',
            'reports' => $GLOBALS['TL_LANG']['MSC']['synapsisReports'] ?? 'Meldungen',
            'bans' => $GLOBALS['TL_LANG']['MSC']['synapsisBans'] ?? 'Sperren',
        ];

        $breadcrumb = $this->buildBreadcrumb(0);
        $breadcrumb[] = ['title' => $labels[$this->panel] ?? '', 'url' => ''];

        $this->Template->panel = $this->panel;
        $this->Template->panelTitle = $labels[$this->panel] ?? '';
        $this->Template->breadcrumb = $breadcrumb;
        $this->Template->items = [];

        switch ($this->panel) {
            case 'subs':
                $this->compilePanelSubscriptions();
                break;

            case 'sig':
                $this->compilePanelSignature();
                break;

            case 'me':
                $this->compilePanelMyPosts();
                break;

            case 'unread':
                $this->compilePanelUnread();
                break;

            case 'liked':
                $this->compilePanelLiked();
                break;

            case 'reports':
                $this->compilePanelReports();
                break;

            case 'bans':
                $this->compilePanelBans();
                break;

            case 'notify':
                $this->compilePanelNotifications();
                break;
        }
    }

    /**
     * Abonnements des Mitglieds auflisten und Abbestellungen verarbeiten.
     */
    private function compilePanelSubscriptions(): void
    {
        $memberId = (int) FrontendUser::getInstance()->id;

        if ('synapsis_unsub_'.$this->id === Input::post('FORM_SUBMIT')) {
            $topicId = (int) Input::post('topic');

            if ($topicId > 0) {
                Database::getInstance()
                    ->prepare('DELETE FROM tl_synapsis_subscription WHERE member = ? AND topic = ?')
                    ->execute($memberId, $topicId)
                ;
                $this->redirect($this->pageUrl(['panel' => 'subs']));
            }
        }

        // Nur Abonnements von Themen DIESES Startpunkts anzeigen (lesbare Foren).
        // readableForumIds() enthaelt ausschliesslich sichtbare Foren des
        // Startpunkts - so bleiben fremde Startpunkte aussen vor.
        $forumIds = $this->readableForumIds();
        $placeholders = implode(',', array_fill(0, \count($forumIds), '?'));

        $rows = Database::getInstance()
            ->prepare('SELECT t.id, t.title, t.pid FROM tl_synapsis_subscription s INNER JOIN tl_synapsis_topic t ON t.id = s.topic WHERE s.member = ? AND t.published = ? AND t.pid IN ('.$placeholders.') ORDER BY s.tstamp DESC')
            ->execute(...array_merge([$memberId, '1'], $forumIds))
        ;

        $items = [];

        while ($rows->next()) {
            $items[] = [
                'topicId' => (int) $rows->id,
                'title' => (string) $rows->title,
                'url' => $this->pageUrl(['topic' => (int) $rows->id]),
                'forumTitle' => $this->forumTitle((int) $rows->pid),
            ];
        }

        $this->Template->items = $items;
        $this->Template->unsubFormId = 'synapsis_unsub_'.$this->id;
        $this->Template->formAction = $this->pageUrl(['panel' => 'subs']);
    }

    /**
     * Signatur des Mitglieds anzeigen und speichern.
     */
    private function compilePanelSignature(): void
    {
        $memberId = (int) FrontendUser::getInstance()->id;

        if ('synapsis_sig_'.$this->id === Input::post('FORM_SUBMIT')) {
            $signature = $this->cleanSignature((string) Input::post('signature'));
            Database::getInstance()
                ->prepare('UPDATE tl_member SET signature = ? WHERE id = ?')
                ->execute($signature, $memberId)
            ;
            $this->redirect($this->pageUrl(['panel' => 'sig']));
        }

        $row = Database::getInstance()
            ->prepare('SELECT signature FROM tl_member WHERE id = ?')
            ->execute($memberId)
            ->row(true)
        ;

        // Zur Bearbeitung die von Contao maskierten Sonderzeichen (=, #, …)
        // zurueckwandeln, damit der BB-Code im Textfeld lesbar ist.
        $stored = \is_array($row) ? (string) ($row[0] ?? '') : '';
        $this->Template->signature = StringUtil::decodeEntities($stored);
        $this->Template->sigFormId = 'synapsis_sig_'.$this->id;
        $this->Template->formAction = $this->pageUrl(['panel' => 'sig']);
        $this->Template->sigMaxLength = 500;

        // Skript fuer die BB-Code-Buttons ueber dem Signaturfeld
        $this->addBBCodeScript();
    }

    /**
     * Themen auflisten, in denen das Mitglied selbst geschrieben hat.
     */
    private function compilePanelMyPosts(): void
    {
        $memberId = (int) FrontendUser::getInstance()->id;
        $forumIds = $this->readableForumIds();
        $placeholders = implode(',', array_fill(0, count($forumIds), '?'));

        $sql = 'SELECT t.id, t.title, t.pid, MAX(p.date) AS lastOwn, COUNT(*) AS ownPosts'
            .' FROM tl_synapsis_post p INNER JOIN tl_synapsis_topic t ON t.id = p.pid'
            .' WHERE p.author = ? AND p.published = ? AND t.published = ? AND t.pid IN ('.$placeholders.')'
            .' GROUP BY t.id, t.title, t.pid ORDER BY lastOwn DESC';

        $rows = Database::getInstance()
            ->prepare($sql)
            ->limit(100)
            ->execute(...array_merge([$memberId, '1', '1'], $forumIds))
        ;

        $items = [];

        while ($rows->next()) {
            $items[] = [
                'title' => (string) $rows->title,
                'url' => $this->pageUrl(['topic' => (int) $rows->id]),
                'forumTitle' => $this->forumTitle((int) $rows->pid),
                'ownPosts' => (int) $rows->ownPosts,
                'dateFormatted' => $this->formatDate((int) $rows->lastOwn),
            ];
        }

        $this->Template->items = $items;
    }

    /**
     * Fuer das Mitglied ungelesene Themen auflisten.
     */
    private function compilePanelUnread(): void
    {
        $memberId = (int) FrontendUser::getInstance()->id;
        $topicIds = $this->readTracker()->unreadTopicIds($memberId, $this->readableForumIds());

        $items = [];

        foreach ($topicIds as $topicId) {
            $topic = $this->findTopic($topicId);

            if (null === $topic) {
                continue;
            }

            $lastPost = $this->findLastPost([], $topicId);

            $items[] = [
                'title' => (string) $topic['title'],
                'url' => $this->pageUrl(['topic' => $topicId]),
                'forumTitle' => $this->forumTitle((int) $topic['pid']),
                'lastAuthor' => (string) ($lastPost['authorName'] ?? ''),
                'dateFormatted' => (string) ($lastPost['dateFormatted'] ?? ''),
            ];
        }

        $this->Template->items = $items;
    }

    /**
     * Themen auflisten, in denen das Mitglied Beitraege mit "Gefaellt mir"
     * markiert hat (auf diesen Startpunkt beschraenkt).
     */
    private function compilePanelLiked(): void
    {
        $memberId = (int) FrontendUser::getInstance()->id;
        $topicIds = $this->likeManager()->likedTopicIds($memberId, $this->readableForumIds());

        $items = [];

        foreach ($topicIds as $topicId) {
            $topic = $this->findTopic($topicId);

            if (null === $topic) {
                continue;
            }

            $items[] = [
                'title' => (string) $topic['title'],
                'url' => $this->pageUrl(['topic' => $topicId]),
                'forumTitle' => $this->forumTitle((int) $topic['pid']),
            ];
        }

        $this->Template->items = $items;
    }

    /**
     * Offene Meldungen der Foren auflisten, die der Besucher moderiert, und das
     * Erledigen (Loeschen) einer Meldung verarbeiten.
     */
    private function compilePanelReports(): void
    {
        $this->Template->items = [];

        if (!$this->canSeeReports()) {
            return;
        }

        // Erledigen verarbeiten
        if ('synapsis_resolve_'.$this->id === Input::post('FORM_SUBMIT')) {
            $reportId = (int) Input::post('report');

            if ($reportId > 0) {
                $rep = Database::getInstance()->prepare('SELECT forum FROM tl_synapsis_report WHERE id = ?')->execute($reportId)->row();

                if (!empty($rep) && $this->isTeamMember((int) $rep['forum'])) {
                    Database::getInstance()->prepare('DELETE FROM tl_synapsis_report WHERE id = ?')->execute($reportId);
                }
            }

            $this->redirect($this->pageUrl(['panel' => 'reports']));
        }

        $forumIds = $this->readableForumIds();
        $placeholders = implode(',', array_fill(0, count($forumIds), '?'));

        $rows = Database::getInstance()
            ->prepare('SELECT * FROM tl_synapsis_report WHERE forum IN ('.$placeholders.') ORDER BY tstamp DESC')
            ->limit(200)
            ->execute(...$forumIds)
        ;

        $items = [];

        while ($rows->next()) {
            $report = $rows->row();

            if (!$this->isTeamMember((int) $report['forum'])) {
                continue;
            }

            $topicRow = Database::getInstance()->prepare('SELECT title FROM tl_synapsis_topic WHERE id = ?')->execute((int) $report['topic'])->row();

            $items[] = [
                'reason' => (string) ($report['reason'] ?? ''),
                'reporter' => $this->authorLabel((int) $report['member'], ''),
                'topicTitle' => (string) ($topicRow['title'] ?? ''),
                'forumTitle' => $this->forumTitle((int) $report['forum']),
                'url' => $this->pageUrl(['topic' => (int) $report['topic']]).'#post-'.(int) $report['post'],
                'dateFormatted' => $this->formatDate((int) $report['tstamp']),
                'resolveId' => (int) $report['id'],
            ];
        }

        $this->Template->items = $items;
        $this->Template->resolveFormId = 'synapsis_resolve_'.$this->id;
        $this->Template->formAction = $this->pageUrl(['panel' => 'reports']);
    }

    /**
     * Sperren-Verwaltung: listet die gesperrten Mitglieder, ermoeglicht das
     * Freigeben (Aufheben) und das Sperren eines Mitglieds ueber seinen
     * Benutzernamen. Nur fuer Team-Mitglieder mit Sperr-Recht.
     */
    private function compilePanelBans(): void
    {
        $this->Template->items = [];
        $this->Template->canManageBans = false;
        $this->Template->banError = '';

        if (!$this->canManageBans()) {
            return;
        }

        $this->Template->canManageBans = true;

        $submit = Input::post('FORM_SUBMIT');
        $db = Database::getInstance();

        // Freigeben (Sperre aufheben).
        if ('synapsis_unban_'.$this->id === $submit) {
            $memberId = (int) Input::post('ban_member');

            if ($memberId > 0) {
                $this->banManager()->unban($memberId);
            }

            $this->redirect($this->pageUrl(['panel' => 'bans']));
        }

        // Mitglied ueber Benutzernamen sperren.
        if ('synapsis_banadd_'.$this->id === $submit) {
            $username = trim((string) Input::post('ban_username'));

            if ('' !== $username) {
                $row = $db->prepare('SELECT id FROM tl_member WHERE username = ?')->execute($username)->row();
                $memberId = empty($row) ? 0 : (int) $row['id'];

                if ($memberId > 0 && $memberId !== $this->currentAuthorId()) {
                    $reason = trim(strip_tags((string) Input::post('ban_reason', true)));
                    $this->banManager()->ban($memberId, $this->currentAuthorId(), $reason);
                    $this->Template->banError = '';
                } else {
                    // Fehlermeldung ueber den Redirect hinweg im Flash-Bereich merken.
                    $this->Template->banError = $GLOBALS['TL_LANG']['MSC']['synapsisBanUnknownMember'] ?? 'Es wurde kein Mitglied mit diesem Benutzernamen gefunden.';
                }
            }

            // Bei Erfolg neu laden; bei unbekanntem Namen die Meldung stehen lassen.
            if (empty($this->Template->banError)) {
                $this->redirect($this->pageUrl(['panel' => 'bans']));
            }
        }

        $items = [];

        foreach ($this->banManager()->all() as $ban) {
            $items[] = [
                'memberId' => $ban['member'],
                'member' => $this->authorLabel($ban['member'], ''),
                'reason' => $ban['reason'],
                'bannedBy' => $this->authorLabel($ban['bannedBy'], ''),
                'dateFormatted' => $this->formatDate($ban['tstamp']),
            ];
        }

        $this->Template->items = $items;
        $this->Template->unbanFormId = 'synapsis_unban_'.$this->id;
        $this->Template->banAddFormId = 'synapsis_banadd_'.$this->id;
        $this->Template->formAction = $this->pageUrl(['panel' => 'bans']);
    }

    /**
     * Benachrichtigungscenter: listet die persoenlichen Benachrichtigungen des
     * Mitglieds (neueste zuerst) und markiert sie beim Oeffnen als gelesen.
     */
    private function compilePanelNotifications(): void
    {
        $this->Template->items = [];

        if (!$this->isMemberLoggedIn()) {
            return;
        }

        $memberId = (int) FrontendUser::getInstance()->id;
        $db = Database::getInstance();

        // Nur Benachrichtigungen aus diesem Startpunkt (dessen Themen-Links hier aufloesen).
        $forumIds = $this->readableForumIds();
        $placeholders = implode(',', array_fill(0, \count($forumIds), '?'));

        $rows = $db
            ->prepare('SELECT * FROM tl_synapsis_notification WHERE member = ? AND forum IN ('.$placeholders.') ORDER BY tstamp DESC, id DESC')
            ->limit(100)
            ->execute(...array_merge([$memberId], $forumIds))
            ->fetchAllAssoc()
        ;

        $labels = [
            'reply' => $GLOBALS['TL_LANG']['MSC']['synapsisNotifyReply'] ?? '%s hat auf dein Thema geantwortet.',
            'quote' => $GLOBALS['TL_LANG']['MSC']['synapsisNotifyQuote'] ?? '%s hat deinen Beitrag zitiert.',
            'mention' => $GLOBALS['TL_LANG']['MSC']['synapsisNotifyMention'] ?? '%s hat dich erwähnt.',
            'report' => $GLOBALS['TL_LANG']['MSC']['synapsisNotifyReport'] ?? '%s hat einen Beitrag gemeldet.',
        ];

        $items = [];

        foreach ($rows as $row) {
            $type = (string) $row['type'];
            $from = $this->authorLabel((int) $row['fromMember'], '');
            $topicRow = $db->prepare('SELECT title FROM tl_synapsis_topic WHERE id = ?')->execute((int) $row['topic'])->row();

            $items[] = [
                'text' => sprintf($labels[$type] ?? '%s', $from),
                'topicTitle' => (string) ($topicRow['title'] ?? ''),
                'url' => $this->pageUrl(['topic' => (int) $row['topic']]).'#post-'.(int) $row['post'],
                'dateFormatted' => $this->formatDate((int) $row['tstamp']),
                'isRead' => '1' === (string) $row['isRead'],
            ];
        }

        $this->Template->items = $items;

        // Beim Ansehen die angezeigten (dieses Startpunkts) als gelesen markieren.
        $db->prepare("UPDATE tl_synapsis_notification SET isRead = '1' WHERE member = ? AND isRead = '' AND forum IN (".$placeholders.')')
            ->execute(...array_merge([$memberId], $forumIds))
        ;
    }

    /**
     * Zahl der ungelesenen Benachrichtigungen des angemeldeten Mitglieds
     * (0, wenn nicht angemeldet).
     */
    private function unreadNotificationCount(): int
    {
        if (!$this->isMemberLoggedIn()) {
            return 0;
        }

        // Zaehler nur fuer diesen Startpunkt (konsistent mit dem Center).
        $forumIds = $this->readableForumIds();
        $placeholders = implode(',', array_fill(0, \count($forumIds), '?'));

        return (int) Database::getInstance()
            ->prepare("SELECT COUNT(*) FROM tl_synapsis_notification WHERE member = ? AND isRead = '' AND forum IN (".$placeholders.')')
            ->execute(...array_merge([(int) FrontendUser::getInstance()->id], $forumIds))
            ->row(true)[0]
        ;
    }

    /**
     * Legt eine persoenliche Benachrichtigung an. Selbstbenachrichtigung und
     * ungueltige Empfaenger werden uebersprungen; Doppelungen desselben
     * Ereignisses (gleicher Empfaenger, Typ und Beitrag) werden vermieden.
     */
    private function createNotification(int $recipient, string $type, int $topicId, int $postId, int $forumId, int $fromMember): void
    {
        if ($recipient <= 0 || $recipient === $fromMember) {
            return;
        }

        $db = Database::getInstance();

        $exists = $db
            ->prepare('SELECT id FROM tl_synapsis_notification WHERE member = ? AND type = ? AND post = ?')
            ->execute($recipient, $type, $postId)
            ->numRows
        ;

        if ($exists) {
            return;
        }

        $db->prepare('INSERT INTO tl_synapsis_notification %s')
            ->set([
                'tstamp' => time(),
                'member' => $recipient,
                'fromMember' => $fromMember,
                'type' => $type,
                'topic' => $topicId,
                'post' => $postId,
                'forum' => $forumId,
                'isRead' => '',
            ])
            ->execute()
        ;
    }

    /**
     * Titel eines Forums (leer, wenn nicht gefunden).
     */
    private function forumTitle(int $forumId): string
    {
        $row = Database::getInstance()
            ->prepare('SELECT title FROM tl_synapsis_forum WHERE id = ?')
            ->execute($forumId)
            ->row(true)
        ;

        return \is_array($row) ? (string) ($row[0] ?? '') : '';
    }

    /**
     * Bereinigt eine Signatur: reiner Text, gekuerzt auf 500 Zeichen.
     */
    private function cleanSignature(string $text): string
    {
        $text = trim(strip_tags($text));

        if (mb_strlen($text) > 500) {
            $text = mb_substr($text, 0, 500);
        }

        return $text;
    }

    /**
     * Signatur eines Mitglieds (leer bei Gaesten oder ohne Signatur).
     */
    private function memberSignature(int $memberId): string
    {
        if ($memberId <= 0) {
            return '';
        }

        $row = Database::getInstance()
            ->prepare('SELECT signature FROM tl_member WHERE id = ?')
            ->execute($memberId)
            ->row(true)
        ;

        return \is_array($row) ? (string) ($row[0] ?? '') : '';
    }

    // -------------------------------------------------------------------------
    // Formularverarbeitung
    // -------------------------------------------------------------------------

    /**
     * Legt ein neues Thema mitsamt erstem Beitrag an und leitet darauf um.
     */
    private function handleTopicSubmission(): void
    {
        if ('synapsis_topic_'.$this->id !== Input::post('FORM_SUBMIT')) {
            return;
        }

        if ($this->activeForum['closed'] || !$this->canWrite((int) $this->activeForum['id'])) {
            return;
        }

        $title = trim((string) Input::post('title'));
        $text = $this->cleanText((string) Input::postHtml('text', true));

        if ('' === $title || '' === strip_tags($text)) {
            $this->Template->formError = $GLOBALS['TL_LANG']['MSC']['synapsisEmptyFields'] ?? 'Bitte Titel und Text ausfuellen.';

            return;
        }

        $memberId = $this->currentAuthorId();
        $authorName = $this->memberUsername($memberId);
        $now = time();

        $topicId = (int) Database::getInstance()
            ->prepare('INSERT INTO tl_synapsis_topic %s')
            ->set([
                'pid' => (int) $this->activeForum['id'],
                'tstamp' => $now,
                'title' => $title,
                'alias' => $this->uniqueAlias('tl_synapsis_topic', $title),
                'author' => $memberId,
                'authorName' => $authorName,
                'date' => $now,
                'published' => '1',
            ])
            ->execute()
            ->insertId
        ;

        $firstPostId = $this->insertPost($topicId, $memberId, $authorName, $text, $now);

        // Optionale Umfrage anlegen - nur wenn das Mitglied hier dazu berechtigt
        // ist (das Recht wird im Baum vererbt).
        if (Input::post('poll_enable') && $this->canCreatePoll((int) $this->activeForum['id'])) {
            $question = trim((string) Input::post('poll_question', true));
            $multiple = 'multiple' === Input::post('poll_type');
            $options = preg_split('/\r\n|\r|\n/', (string) Input::post('poll_options', true)) ?: [];
            $closeDate = $this->parseCloseDate((string) Input::post('poll_close'));
            $hideResults = (bool) Input::post('poll_hide');

            $this->pollManager()->create($topicId, $question, $multiple, $options, $closeDate, $hideResults);
        }

        // E-Mail an Forum-Abonnenten/Team und Erwaehnungs-Benachrichtigungen
        $this->afterNewTopic($topicId, (int) $this->activeForum['id'], $memberId, $title, $firstPostId, $text);

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

        if ($this->activeTopic['locked'] || $this->activeForum['closed'] || !$this->canWrite((int) $this->activeForum['id'])) {
            return;
        }

        $text = $this->cleanText((string) Input::postHtml('text', true));

        if ('' === strip_tags($text)) {
            $this->Template->formError = $GLOBALS['TL_LANG']['MSC']['synapsisEmptyText'] ?? 'Bitte einen Text eingeben.';

            return;
        }

        $now = time();
        $memberId = $this->currentAuthorId();
        $authorName = $this->memberUsername($memberId);
        $topicId = (int) $this->activeTopic['id'];
        $postId = $this->insertPost($topicId, $memberId, $authorName, $text, $now);

        // Thema als aktualisiert markieren
        Database::getInstance()
            ->prepare('UPDATE tl_synapsis_topic SET tstamp = ? WHERE id = ?')
            ->execute($now, $topicId)
        ;

        // Abonnenten benachrichtigen (ausser dem Verfasser)
        $this->notifySubscribers($topicId, $memberId);

        // Persoenliche Benachrichtigungen (Erwaehnung/Zitat/Antwort) und Team-E-Mail
        $quotePostId = (int) Input::post('quote_post');
        $this->afterReply($postId, $topicId, (int) $this->activeForum['id'], $memberId, $text, $quotePostId);

        $this->redirect($this->pageUrl(['topic' => $topicId, 'page_p'.$this->id => $this->lastPostPage()]));
    }

    /**
     * Schaltet das Abonnement des aktiven Themas fuer das angemeldete Mitglied
     * um (an/aus) und laedt die Themenansicht neu.
     */
    private function handleSubscription(): void
    {
        if ('synapsis_sub_'.$this->id !== Input::post('FORM_SUBMIT')) {
            return;
        }

        if (!$this->isMemberLoggedIn()) {
            return;
        }

        $memberId = (int) FrontendUser::getInstance()->id;
        $topicId = (int) $this->activeTopic['id'];

        if ($this->isSubscribed($topicId)) {
            Database::getInstance()
                ->prepare('DELETE FROM tl_synapsis_subscription WHERE member = ? AND topic = ?')
                ->execute($memberId, $topicId)
            ;
        } else {
            Database::getInstance()
                ->prepare('INSERT INTO tl_synapsis_subscription %s')
                ->set(['member' => $memberId, 'topic' => $topicId, 'tstamp' => time()])
                ->execute()
            ;
        }

        $this->redirect($this->pageUrl(['topic' => $topicId]));
    }

    /**
     * Verarbeitet das Setzen/Entfernen einer "Gefaellt mir"-Markierung und laedt
     * die Themenansicht auf derselben Beitragsseite neu.
     */
    private function handleLike(): void
    {
        if ('synapsis_like_'.$this->id !== Input::post('FORM_SUBMIT')) {
            return;
        }

        if (!$this->isMemberLoggedIn()) {
            return;
        }

        $postId = (int) Input::post('post');

        if ($postId > 0) {
            $this->likeManager()->toggle((int) FrontendUser::getInstance()->id, $postId);
        }

        $this->redirect($this->pageUrl([
            'topic' => (int) $this->activeTopic['id'],
            'page_p'.$this->id => $this->getCurrentPage(),
        ]));
    }

    /**
     * Verarbeitet eine Stimmabgabe zur Umfrage des aktiven Themas.
     */
    private function handleVote(): void
    {
        if ('synapsis_poll_'.$this->id !== Input::post('FORM_SUBMIT')) {
            return;
        }

        if (!$this->isMemberLoggedIn()) {
            return;
        }

        $poll = $this->pollManager()->findByTopic((int) $this->activeTopic['id']);

        if (null !== $poll) {
            $raw = Input::post('poll_choice');
            $optionIds = \is_array($raw) ? array_map('intval', $raw) : [(int) $raw];

            $this->pollManager()->vote((int) $poll['id'], (int) FrontendUser::getInstance()->id, $optionIds);
        }

        $this->redirect($this->pageUrl(['topic' => (int) $this->activeTopic['id']]));
    }

    /**
     * Schaltet die Anheftung (sticky) des aktiven Themas um - nur fuer
     * Administratoren bzw. berechtigte Moderatoren.
     */
    private function handlePin(): void
    {
        if ('synapsis_pin_'.$this->id !== Input::post('FORM_SUBMIT')) {
            return;
        }

        if (!$this->canPin((int) $this->activeForum['id'])) {
            return;
        }

        $topicId = (int) $this->activeTopic['id'];
        $new = $this->activeTopic['sticky'] ? '' : '1';

        Database::getInstance()
            ->prepare('UPDATE tl_synapsis_topic SET sticky = ? WHERE id = ?')
            ->execute($new, $topicId)
        ;

        $this->redirect($this->pageUrl(['topic' => $topicId]));
    }

    /**
     * Schaltet die Sperre (locked) des aktiven Themas um - nur fuer
     * Administratoren bzw. berechtigte Moderatoren.
     */
    private function handleLock(): void
    {
        if ('synapsis_lock_'.$this->id !== Input::post('FORM_SUBMIT')) {
            return;
        }

        if (!$this->canModerate((int) $this->activeForum['id'], 'lock')) {
            return;
        }

        $topicId = (int) $this->activeTopic['id'];
        $new = $this->activeTopic['locked'] ? '' : '1';

        Database::getInstance()
            ->prepare('UPDATE tl_synapsis_topic SET locked = ? WHERE id = ?')
            ->execute($new, $topicId)
        ;

        $this->redirect($this->pageUrl(['topic' => $topicId]));
    }

    /**
     * Verschiebt das aktive Thema in ein anderes (lesbares) Forum desselben
     * Startpunkts - nur fuer Administratoren bzw. berechtigte Moderatoren.
     */
    private function handleMove(): void
    {
        if ('synapsis_move_'.$this->id !== Input::post('FORM_SUBMIT')) {
            return;
        }

        $currentForum = (int) $this->activeForum['id'];

        if (!$this->canModerate($currentForum, 'move')) {
            return;
        }

        $topicId = (int) $this->activeTopic['id'];
        $target = (int) Input::post('move_target');

        // Ziel muss ein lesbares Forum dieses Startpunkts sein (nicht das aktuelle).
        if ($target > 0 && $target !== $currentForum && \in_array($target, $this->readableForumIds(), true)) {
            Database::getInstance()
                ->prepare('UPDATE tl_synapsis_topic SET pid = ? WHERE id = ?')
                ->execute($target, $topicId)
            ;
            $this->redirect($this->pageUrl(['topic' => $topicId]));
        }

        $this->redirect($this->pageUrl(['topic' => $topicId]));
    }

    /**
     * Massen-Moderation: wendet eine Aktion (Loeschen, Schliessen/Oeffnen,
     * Verschieben) auf mehrere ausgewaehlte Themen des aktuellen Forums an.
     * Jede Aktion wird gegen die passende Berechtigung geprueft; es werden nur
     * Themen dieses Forums beruecksichtigt.
     */
    private function handleBulkModeration(): void
    {
        if ('synapsis_bulk_'.$this->id !== Input::post('FORM_SUBMIT')) {
            return;
        }

        $forumId = (int) $this->activeForum['id'];
        $action = (string) Input::post('bulk_action');
        $topicIds = array_values(array_filter(array_map('intval', (array) Input::post('bulk_topics'))));

        // Aktion -> benoetigte Berechtigung (Loeschen wie das Bearbeiten fremder
        // Beitraege, das bereits das Loeschen ganzer Themen erlaubt).
        $capabilityMap = [
            'delete' => 'edit',
            'lock' => 'lock',
            'unlock' => 'lock',
            'move' => 'move',
        ];

        if (!isset($capabilityMap[$action]) || [] === $topicIds || !$this->canModerate($forumId, $capabilityMap[$action])) {
            $this->redirect($this->pageUrl(['forum' => $forumId]));
        }

        $db = Database::getInstance();

        // Nur Themen beruecksichtigen, die wirklich in diesem Forum liegen.
        $valid = [];

        foreach ($topicIds as $topicId) {
            $row = $db->prepare('SELECT id FROM tl_synapsis_topic WHERE id = ? AND pid = ?')->execute($topicId, $forumId)->row();

            if (!empty($row)) {
                $valid[] = $topicId;
            }
        }

        if ([] === $valid) {
            $this->redirect($this->pageUrl(['forum' => $forumId]));
        }

        $placeholders = implode(',', array_fill(0, \count($valid), '?'));

        if ('delete' === $action) {
            foreach ($valid as $topicId) {
                $this->deleteTopicCompletely($topicId);
            }
        } elseif ('lock' === $action || 'unlock' === $action) {
            $db->prepare('UPDATE tl_synapsis_topic SET locked = ? WHERE id IN ('.$placeholders.')')
                ->execute('lock' === $action ? '1' : '', ...$valid)
            ;
        } elseif ('move' === $action) {
            $target = (int) Input::post('bulk_target');

            // Ziel muss ein anderes lesbares Forum desselben Startpunkts sein.
            if ($target > 0 && $target !== $forumId && \in_array($target, $this->readableForumIds(), true)) {
                $db->prepare('UPDATE tl_synapsis_topic SET pid = ? WHERE id IN ('.$placeholders.')')
                    ->execute($target, ...$valid)
                ;
            }
        }

        $this->redirect($this->pageUrl(['forum' => $forumId]));
    }

    /**
     * Kleiner Sicherheits-Dialog fuer die Massen-Moderation: verlangt eine
     * Auswahl und laesst das Loeschen ausdruecklich bestaetigen (ohne
     * JavaScript bleibt die Aktion normal absendbar).
     */
    private function addBulkScript(): void
    {
        $confirmDelete = $GLOBALS['TL_LANG']['MSC']['synapsisBulkDeleteConfirm'] ?? 'Die ausgewählten Themen wirklich unwiderruflich löschen?';
        $confirmApply = $GLOBALS['TL_LANG']['MSC']['synapsisBulkApplyConfirm'] ?? 'Aktion auf die ausgewählten Themen anwenden?';
        $needSelection = $GLOBALS['TL_LANG']['MSC']['synapsisBulkNeedSelection'] ?? 'Bitte mindestens ein Thema auswählen.';

        $GLOBALS['TL_BODY']['synapsis_bulk'] = '<script>document.addEventListener("DOMContentLoaded",function(){'
            .'document.querySelectorAll(".synapsis-bulkform").forEach(function(f){'
            .'f.addEventListener("submit",function(e){'
            .'var a=f.querySelector("[name=bulk_action]").value;'
            .'var n=f.querySelectorAll("input[name=\'bulk_topics[]\']:checked").length;'
            .'if(!a){e.preventDefault();return;}'
            .'if(n===0){e.preventDefault();alert('.json_encode($needSelection).');return;}'
            .'var m=("delete"===a)?'.json_encode($confirmDelete).':'.json_encode($confirmApply).';'
            .'if(!confirm(m)){e.preventDefault();}'
            .'});});});</script>'
        ;
    }

    /**
     * Liste der moeglichen Verschiebe-Ziele (lesbare Foren des Startpunkts ohne
     * das aktuelle).
     *
     * @return array<int, array{id:int, title:string}>
     */
    private function moveTargets(int $excludeForumId): array
    {
        $targets = [];

        foreach ($this->readableForumIds() as $id) {
            if ($id === $excludeForumId || $id <= 0) {
                continue;
            }

            $targets[] = ['id' => $id, 'title' => $this->forumTitle($id)];
        }

        return $targets;
    }

    /**
     * Darf das angemeldete Mitglied diesen Beitrag bearbeiten oder loeschen?
     * Berechtigte Moderatoren/Administratoren immer; der Verfasser nur, solange
     * das Thema offen und das Forum nicht geschlossen ist.
     *
     * @param array<string, mixed> $post
     */
    private function canModifyPost(array $post): bool
    {
        $memberId = $this->currentAuthorId();

        if ($memberId <= 0) {
            return false;
        }

        if ($this->canModerate((int) $this->activeForum['id'], 'edit')) {
            return true;
        }

        if ((int) $post['author'] !== $memberId) {
            return false;
        }

        return !$this->activeTopic['locked'] && !$this->activeForum['closed'];
    }

    /**
     * Laedt einen Beitrag des aktiven Themas (oder null).
     *
     * @return array<string, mixed>|null
     */
    private function findPostInTopic(int $postId): ?array
    {
        $row = Database::getInstance()
            ->prepare('SELECT * FROM tl_synapsis_post WHERE id = ? AND pid = ?')
            ->execute($postId, (int) $this->activeTopic['id'])
            ->row()
        ;

        return empty($row) ? null : $row;
    }

    /**
     * Speichert die Bearbeitung eines Beitrags (vermerkt Zeitpunkt und Bearbeiter).
     */
    private function handleEdit(): void
    {
        if ('synapsis_edit_'.$this->id !== Input::post('FORM_SUBMIT')) {
            return;
        }

        $postId = (int) Input::post('post');
        $post = $this->findPostInTopic($postId);

        if (null === $post || !$this->canModifyPost($post)) {
            return;
        }

        $text = $this->cleanText((string) Input::postHtml('text', true));

        if ('' === strip_tags($text)) {
            return;
        }

        Database::getInstance()
            ->prepare('UPDATE tl_synapsis_post SET text = ?, editedAt = ?, editedBy = ? WHERE id = ?')
            ->execute($text, time(), $this->currentAuthorId(), $postId)
        ;

        $this->redirect($this->pageUrl(['topic' => (int) $this->activeTopic['id']]));
    }

    /**
     * Loescht einen Beitrag. Bleibt das Thema danach leer, wird es vollstaendig
     * entfernt (samt Umfrage, Abonnements und Lesestaenden).
     */
    private function handleDelete(): void
    {
        if ('synapsis_delete_'.$this->id !== Input::post('FORM_SUBMIT')) {
            return;
        }

        $postId = (int) Input::post('post');
        $post = $this->findPostInTopic($postId);

        if (null === $post || !$this->canModifyPost($post)) {
            return;
        }

        $topicId = (int) $this->activeTopic['id'];
        $db = Database::getInstance();

        $db->prepare('DELETE FROM tl_synapsis_like WHERE post = ?')->execute($postId);
        $db->prepare('DELETE FROM tl_synapsis_report WHERE post = ?')->execute($postId);
        $db->prepare('DELETE FROM tl_synapsis_notification WHERE post = ?')->execute($postId);
        $db->prepare('DELETE FROM tl_synapsis_post WHERE id = ?')->execute($postId);

        $remaining = (int) $db->prepare('SELECT COUNT(*) FROM tl_synapsis_post WHERE pid = ?')
            ->execute($topicId)
            ->row(true)[0]
        ;

        if (0 === $remaining) {
            $this->deleteTopicCompletely($topicId);
            $this->redirect($this->pageUrl(['forum' => (int) $this->activeForum['id']]));
        }

        $this->redirect($this->pageUrl(['topic' => $topicId]));
    }

    /**
     * Entfernt ein Thema mit allen abhaengigen Daten (Umfrage samt Optionen und
     * Stimmen, Abonnements, Lesestaende).
     */
    private function deleteTopicCompletely(int $topicId): void
    {
        $db = Database::getInstance();

        $polls = $db->prepare('SELECT id FROM tl_synapsis_poll WHERE pid = ?')->execute($topicId)->fetchAllAssoc();

        foreach ($polls as $poll) {
            $pollId = (int) $poll['id'];
            $db->prepare('DELETE FROM tl_synapsis_poll_option WHERE pid = ?')->execute($pollId);
            $db->prepare('DELETE FROM tl_synapsis_poll_vote WHERE poll = ?')->execute($pollId);
            $db->prepare('DELETE FROM tl_synapsis_poll WHERE id = ?')->execute($pollId);
        }

        $db->prepare('DELETE FROM tl_synapsis_subscription WHERE topic = ?')->execute($topicId);
        $db->prepare('DELETE FROM tl_synapsis_read WHERE topic = ?')->execute($topicId);
        $db->prepare('DELETE FROM tl_synapsis_report WHERE topic = ?')->execute($topicId);
        $db->prepare('DELETE FROM tl_synapsis_notification WHERE topic = ?')->execute($topicId);
        $db->prepare('DELETE FROM tl_synapsis_topic WHERE id = ?')->execute($topicId);
    }

    /**
     * Verarbeitet eine Meldung eines Beitrags durch ein Mitglied.
     */
    private function handleReport(): void
    {
        if ('synapsis_report_'.$this->id !== Input::post('FORM_SUBMIT')) {
            return;
        }

        $memberId = $this->currentAuthorId();

        if ($memberId <= 0) {
            return;
        }

        $postId = (int) Input::post('post');
        $post = $this->findPostInTopic($postId);

        if (null === $post || (int) $post['author'] === $memberId) {
            return;
        }

        $reason = trim(strip_tags((string) Input::post('reason', true)));
        $db = Database::getInstance();

        // Doppelte Meldung desselben Mitglieds vermeiden.
        $exists = $db->prepare('SELECT id FROM tl_synapsis_report WHERE member = ? AND post = ?')
            ->execute($memberId, $postId)
            ->numRows
        ;

        if (!$exists) {
            $topicId = (int) $this->activeTopic['id'];
            $forumId = (int) $this->activeForum['id'];

            $db->prepare('INSERT INTO tl_synapsis_report %s')
                ->set([
                    'tstamp' => time(),
                    'post' => $postId,
                    'topic' => $topicId,
                    'forum' => $forumId,
                    'member' => $memberId,
                    'reason' => mb_substr($reason, 0, 1000),
                ])
                ->execute()
            ;

            // Team (Admins/Moderatoren) im Benachrichtigungscenter informieren.
            foreach ($this->teamMemberIds($forumId, true, true) as $teamId) {
                $this->createNotification($teamId, 'report', $topicId, $postId, $forumId, $memberId);
            }
        }

        $this->redirect($this->pageUrl(['topic' => (int) $this->activeTopic['id']]));
    }

    /**
     * Sperrt ein Mitglied aus der Themenansicht heraus (Schaltflaeche in der
     * Beitrags-Fusszeile). Nur fuer Team-Mitglieder mit Sperr-Recht; das eigene
     * Konto kann nicht gesperrt werden.
     */
    private function handleBan(): void
    {
        if ('synapsis_ban_'.$this->id !== Input::post('FORM_SUBMIT')) {
            return;
        }

        $topicId = (int) $this->activeTopic['id'];

        if ($this->canManageBans()) {
            $memberId = (int) Input::post('ban_member');

            if ($memberId > 0 && $memberId !== $this->currentAuthorId()) {
                $reason = trim(strip_tags((string) Input::post('ban_reason', true)));
                $this->banManager()->ban($memberId, $this->currentAuthorId(), $reason);
            }
        }

        $this->redirect($this->pageUrl(['topic' => $topicId]));
    }

    /**
     * Darf der Besucher irgendwo in diesem Startpunkt moderieren (und damit
     * Meldungen sehen)? Ergebnis wird zwischengespeichert.
     */
    private function canSeeReports(): bool
    {
        if (null !== $this->canSeeReportsCache) {
            return $this->canSeeReportsCache;
        }

        $result = false;

        if ($this->isMemberLoggedIn()) {
            foreach ($this->readableForumIds() as $forumId) {
                if ($this->isTeamMember($forumId)) {
                    $result = true;
                    break;
                }
            }
        }

        return $this->canSeeReportsCache = $result;
    }

    /**
     * Darf der Besucher irgendwo in diesem Startpunkt Mitglieder sperren (und
     * damit die Sperren-Verwaltung sehen)? Ergebnis wird zwischengespeichert.
     */
    private function canManageBans(): bool
    {
        if (null !== $this->canManageBansCache) {
            return $this->canManageBansCache;
        }

        $result = false;

        if ($this->isMemberLoggedIn()) {
            foreach ($this->readableForumIds() as $forumId) {
                if ($this->canModerate($forumId, 'ban')) {
                    $result = true;
                    break;
                }
            }
        }

        return $this->canManageBansCache = $result;
    }

    /**
     * Wandelt die Enddatum-Eingabe in einen Zeitstempel um. Aus dem Feld
     * `datetime-local` kommt "YYYY-MM-DDTHH:MM" - dann wird die angegebene
     * Uhrzeit verwendet. Fehlt die Uhrzeit (nur "YYYY-MM-DD"), gilt das Ende des
     * Tages (23:59:59). Leere/ungueltige Eingabe liefert 0.
     */
    private function parseCloseDate(string $value): int
    {
        $value = trim($value);

        if ('' === $value) {
            return 0;
        }

        // datetime-local liefert "YYYY-MM-DDTHH:MM" - das T durch ein Leerzeichen
        // ersetzen, damit strtotime es sicher versteht.
        $value = str_replace('T', ' ', $value);

        // Enthaelt die Eingabe eine Uhrzeit? Dann diese verwenden, sonst Tagesende.
        if (preg_match('/^\d{4}-\d{2}-\d{2}[ ]\d{1,2}:\d{2}/', $value)) {
            $timestamp = strtotime($value);
        } else {
            $timestamp = strtotime(substr($value, 0, 10).' 23:59:59');
        }

        return false !== $timestamp ? (int) $timestamp : 0;
    }

    /**
     * Stellt die Anzeigedaten der Umfrage eines Themas zusammen (oder null).
     *
     * Ergebnisse werden gezeigt, sobald das Mitglied abgestimmt hat oder nicht
     * abstimmen kann (Gast). Prozente beziehen sich auf die Zahl der Abstimmenden.
     *
     * @return array<string, mixed>|null
     */
    private function buildPoll(int $topicId): ?array
    {
        $poll = $this->pollManager()->findByTopic($topicId);

        if (null === $poll) {
            return null;
        }

        $pollId = (int) $poll['id'];
        $memberId = $this->currentAuthorId();
        $total = $this->pollManager()->totalVoters($pollId);
        $options = $this->pollManager()->options($pollId);

        foreach ($options as &$option) {
            $option['percent'] = $total > 0 ? (int) round($option['votes'] * 100 / $total) : 0;
        }
        unset($option);

        $ended = $this->pollManager()->hasEnded($poll);
        $hideResults = '1' === (string) $poll['hideResults'];
        $hasVoted = $this->pollManager()->hasVoted($pollId, $memberId);

        // Abstimmen nur vor dem Ende, angemeldet und noch nicht abgestimmt.
        $canVote = !$ended && $memberId > 0 && !$hasVoted;

        // Ergebnisse: nach Ende immer; davor nur, wenn nicht "erst nach Ende"
        // und der Nutzer nicht mehr abstimmen kann (bereits abgestimmt/Gast).
        if ($ended) {
            $showResults = true;
            $pending = false;
        } elseif ($canVote) {
            $showResults = false;
            $pending = false;
        } else {
            $showResults = !$hideResults;
            $pending = $hideResults;
        }

        $closeDate = (int) ($poll['closeDate'] ?? 0);

        return [
            'id' => $pollId,
            'question' => (string) $poll['question'],
            'multiple' => '1' === (string) $poll['multiple'],
            'options' => $options,
            'total' => $total,
            'canVote' => $canVote,
            'showResults' => $showResults,
            'pending' => $pending,
            'ended' => $ended,
            'closeLabel' => $closeDate > 0 ? $this->formatDate($closeDate) : '',
            'formId' => 'synapsis_poll_'.$this->id,
            'action' => $this->pageUrl(['topic' => $topicId]),
        ];
    }

    /**
     * Fuegt einen Beitrag ein und verarbeitet optionale Dateianhaenge.
     */
    private function insertPost(int $topicId, int $memberId, string $authorName, string $text, int $timestamp): int
    {
        $attachments = $this->synapsis_allowUploads ? $this->handleUploads() : null;

        return (int) Database::getInstance()
            ->prepare('INSERT INTO tl_synapsis_post %s')
            ->set([
                'pid' => $topicId,
                'tstamp' => $timestamp,
                'author' => $memberId,
                'authorName' => $authorName,
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
        $forum['hasUnread'] = $this->forumHasUnread($forumIds);
        // Vererbtes, im Backend eingestelltes Lucide-Icon als fertiges Inline-SVG
        // (kein Icon-Name im Markup, damit keine fremde Icon-Schrift der Seite
        // eingreift). Geschlossene Foren werden zusaetzlich per CSS-Klasse
        // "is-locked" gedaempft dargestellt.
        $forum['iconSvg'] = LucideIcons::svg($this->resolveForumIcon($forumId));
        // Moderatoren-Namen nur ermitteln, wenn am Startpunkt aktiviert.
        $forum['moderators'] = $this->showModerators() ? $this->moderatorNames($forumId) : [];

        return $forum;
    }

    /**
     * Ermittelt den Icon-Namen eines Forums per Vererbung: das Forum selbst,
     * sonst die naechste uebergeordnete Ebene (Kategorie, dann Startpunkt), die
     * ein Icon gesetzt hat; sonst das Standard-Icon.
     */
    private function resolveForumIcon(int $forumId): string
    {
        $currentId = $forumId;
        $guard = 0;

        while ($currentId > 0 && $guard < 100) {
            $row = Database::getInstance()
                ->prepare('SELECT id, pid, forumIcon FROM tl_synapsis_forum WHERE id = ?')
                ->execute($currentId)
                ->row()
            ;

            if (empty($row)) {
                break;
            }

            if ('' !== (string) $row['forumIcon']) {
                return (string) $row['forumIcon'];
            }

            if ((int) $row['id'] === $this->rootId) {
                break;
            }

            $currentId = (int) $row['pid'];
            ++$guard;
        }

        return LucideIcons::DEFAULT;
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
        $topic['title'] = $this->wordFilter()->filterText((string) ($topic['title'] ?? ''));
        $topic['authorName'] = $this->authorLabel((int) $topic['author'], (string) ($topic['authorName'] ?? ''));
        $topic['authorAvatar'] = $this->avatar((int) $topic['author']);
        $topic['dateFormatted'] = $this->formatDate((int) $topic['date']);
        $topic['replyCount'] = max(0, (int) Database::getInstance()
            ->prepare('SELECT COUNT(*) FROM tl_synapsis_post WHERE pid = ? AND published = ?')
            ->execute($topicId, '1')
            ->row(true)[0] - 1)
        ;
        $lastPost = $this->findLastPost([], $topicId);
        $topic['lastPost'] = $lastPost;

        // Ungelesen-Markierung (nur fuer angemeldete Mitglieder)
        $latestTs = (int) ($lastPost['timestamp'] ?? $topic['date'] ?? 0);
        $topic['unread'] = $this->isTopicUnread($topicId, $latestTs);

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

        $post['authorName'] = $this->authorLabel($authorId, (string) ($post['authorName'] ?? ''));
        $post['authorAvatar'] = $this->avatar($authorId);
        $post['authorUrl'] = $this->profileUrl($authorId);
        $post['authorRank'] = $this->memberRank($authorId);
        // Signatur mit sicherem BB-Code als HTML (leer bleibt leer).
        $signature = $this->memberSignature($authorId);
        $post['signature'] = '' !== $signature ? BBCode::toHtml($signature) : '';

        // "Gefaellt mir"
        $postId = (int) $post['id'];
        $memberId = $this->currentAuthorId();
        $likerIds = $this->likeManager()->likerIds($postId);
        $post['likeCount'] = \count($likerIds);
        $post['likers'] = $this->memberNames($likerIds);
        $post['likedByMe'] = \in_array($memberId, $likerIds, true);
        // Liken nur fuer angemeldete Mitglieder und nicht den eigenen Beitrag
        $post['canLike'] = $memberId > 0 && $memberId !== $authorId;
        // Beitragszahl NUR innerhalb dieses Startpunkts (nicht ueber andere
        // Startpunkte hinweg).
        $post['authorPostCount'] = $this->authorPostCountInRoot($authorId);
        $post['dateFormatted'] = $this->formatDate((int) $post['date']);

        // Bearbeiten/Loeschen und Hinweis auf letzte Aenderung
        $editedAt = (int) ($post['editedAt'] ?? 0);
        $post['editedLabel'] = $editedAt > 0
            ? sprintf(
                $GLOBALS['TL_LANG']['MSC']['synapsisEditedBy'] ?? 'Zuletzt bearbeitet von %s am %s',
                $this->authorLabel((int) ($post['editedBy'] ?? 0), ''),
                $this->formatDate($editedAt)
            )
            : '';
        $post['canModify'] = $this->canModifyPost($post);
        $post['editUrl'] = $this->pageUrl(['topic' => (int) $post['pid'], 'edit' => (int) $post['id']]);
        $post['quoteUrl'] = $this->pageUrl(['topic' => (int) $post['pid'], 'quote' => (int) $post['id']]);
        // Permalink (Direktlink zum Beitrag samt Anker)
        $post['permalinkUrl'] = $this->pageUrl(['topic' => (int) $post['pid']]).'#post-'.(int) $post['id'];
        // Melden: angemeldete Mitglieder, aber nicht den eigenen Beitrag
        $post['canReport'] = $this->currentAuthorId() > 0 && $authorId !== $this->currentAuthorId();
        $post['reportUrl'] = $this->pageUrl(['topic' => (int) $post['pid'], 'report' => (int) $post['id']]);
        // Sperren: nur fuer Team-Mitglieder mit Sperr-Recht, echtes Mitglied als
        // Verfasser, nicht das eigene Konto und noch nicht gesperrt.
        $post['canBan'] = $authorId > 0
            && $authorId !== $this->currentAuthorId()
            && $this->canManageBans()
            && !$this->banManager()->isBanned($authorId);
        $post['attachmentList'] = $this->renderAttachments($post['attachments'] ?? null);

        // Wortfilter auf den (HTML-)Beitragstext anwenden (nur Textteile).
        if ($this->wordFilter()->isActive()) {
            $post['text'] = $this->wordFilter()->filterHtml((string) ($post['text'] ?? ''));
        }

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
        $forumIds = $this->readableForumIds();

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
                'name' => $this->authorLabel((int) $topPosters->author, ''),
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
        $external = null;

        // Optional: Avatar aus terminal42/contao-avatar verwenden, wenn das
        // Bundle installiert ist. Schlaegt das fehl, greift der Lucide-Fallback.
        if ($memberId > 0 && InstalledVersions::isInstalled('terminal42/contao-avatar')) {
            try {
                $external = $this->externalAvatar($memberId);
            } catch (\Throwable $e) {
                $external = null;
            }
        }

        return AvatarResolver::render($memberId, $external);
    }

    /**
     * Loest den Avatar-Insert-Tag von terminal42/contao-avatar auf (Bild-URL
     * oder Bild-Markup). Versionsabhaengig, da Contao 5 keinen
     * Controller::replaceInsertTags mehr hat.
     */
    private function externalAvatar(int $memberId): ?string
    {
        $tag = '{{avatar::member::'.$memberId.'::80x80xcrop}}';

        if (SchachbulleContaoSynapsisBundle::isContao5()) {
            return System::getContainer()->get('contao.insert_tag.parser')->replaceInline($tag);
        }

        return \Contao\Controller::replaceInsertTags($tag);
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
     * IDs aller Foren unter dem Startpunkt, die der aktuelle Besucher lesen
     * darf. So gelangen keine Themen/Beitraege aus gesperrten Foren in die
     * "Neueste Themen"-Liste oder die Statistik.
     *
     * @return array<int>
     */
    private function readableForumIds(): array
    {
        // Pro Anfrage stabil (haengt nur an Startpunkt und Besucher) und wird an
        // mehreren Stellen gebraucht - daher einmalig zwischenspeichern.
        if (null !== $this->readableForumIdsCache) {
            return $this->readableForumIdsCache;
        }

        $ids = [];

        foreach ($this->collectForumIds($this->rootId) as $id) {
            if (0 !== $id && $this->isVisible($id)) {
                $ids[] = $id;
            }
        }

        return $this->readableForumIdsCache = ([] === $ids ? [0] : $ids);
    }

    /**
     * Beitragszahl eines Autors NUR innerhalb dieses Startpunkts (in lesbaren
     * Foren). So bleibt der Zaehler auf den Startpunkt begrenzt.
     */
    private function authorPostCountInRoot(int $authorId): int
    {
        $forumIds = $this->readableForumIds();
        $placeholders = implode(',', array_fill(0, count($forumIds), '?'));

        return (int) Database::getInstance()
            ->prepare('SELECT COUNT(*) FROM tl_synapsis_post p INNER JOIN tl_synapsis_topic t ON t.id = p.pid WHERE p.author = ? AND p.published = ? AND t.published = ? AND t.pid IN ('.$placeholders.')')
            ->execute(...array_merge([$authorId, '1', '1'], $forumIds))
            ->row(true)[0]
        ;
    }

    /**
     * Neueste Themen der fuer den Besucher lesbaren Foren.
     *
     * @return array<array<string, mixed>>
     */
    private function findNewestTopics(int $limit): array
    {
        $forumIds = $this->readableForumIds();
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
                ->prepare('SELECT p.date, p.author, p.authorName FROM tl_synapsis_post p WHERE p.pid = ? AND p.published = ? ORDER BY p.date DESC')
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
                ->prepare('SELECT p.date, p.author, p.authorName, t.title, t.id AS topicId FROM tl_synapsis_post p INNER JOIN tl_synapsis_topic t ON t.id = p.pid WHERE t.pid IN ('.$placeholders.') AND p.published = ? AND t.published = ? ORDER BY p.date DESC')
                ->limit(1)
                ->execute(...array_merge($forumIds, ['1', '1']))
                ->row()
            ;
        }

        if (empty($row)) {
            return null;
        }

        return [
            'authorName' => $this->authorLabel((int) $row['author'], (string) ($row['authorName'] ?? '')),
            'dateFormatted' => $this->formatDate((int) $row['date']),
            'timestamp' => (int) $row['date'],
            'title' => $row['title'] ?? '',
            'url' => isset($row['topicId']) ? $this->pageUrl(['topic' => (int) $row['topicId']]) : '',
        ];
    }

    /**
     * Baut die Kette vom Knoten bis zum Startpunkt dieses Moduls.
     *
     * @return array<array<string, mixed>> Leeres Array bei kaputter Kette
     */
    private function buildChain(int $forumId): array
    {
        $chain = [];
        $currentId = $forumId;
        $guard = 0;

        while ($currentId > 0 && $guard < 100) {
            $node = Database::getInstance()
                ->prepare('SELECT id, pid, published, protected, groups, guestRead, guestWrite, pollGroups, pollMembers, adminGroups, adminMembers, modGroups, modMembers FROM tl_synapsis_forum WHERE id = ?')
                ->execute($currentId)
                ->row()
            ;

            if (empty($node)) {
                return [];
            }

            $chain[] = $node;

            // Nur bis zum Startpunkt dieses Moduls aufsteigen
            if ((int) $node['id'] === $this->rootId) {
                break;
            }

            $currentId = (int) $node['pid'];
            ++$guard;
        }

        return $chain;
    }

    /**
     * Prueft den Lesezugriff auf ein Forum (Sichtbarkeit inkl. Vererbung).
     */
    private function isVisible(int $forumId): bool
    {
        return $this->access->canRead(
            $this->buildChain($forumId),
            !$this->isMemberLoggedIn(),
            $this->getMemberGroupIds()
        );
    }

    /**
     * Prueft den Schreibzugriff auf ein Forum (ohne die closed/locked-Pruefung,
     * die die aufrufenden Stellen selbst ergaenzen).
     */
    private function canWrite(int $forumId): bool
    {
        // Fuer das Forum gesperrte Mitglieder duerfen nirgends schreiben.
        if ($this->isCurrentMemberBanned()) {
            return false;
        }

        return $this->access->canWrite(
            $this->buildChain($forumId),
            !$this->isMemberLoggedIn(),
            $this->getMemberGroupIds()
        );
    }

    /**
     * Darf das angemeldete Mitglied in diesem Forum eine Umfrage erstellen?
     * Das Recht wird im Baum vererbt (siehe PollAccess). Gaeste nie.
     */
    private function canCreatePoll(int $forumId): bool
    {
        if (!$this->isMemberLoggedIn()) {
            return false;
        }

        return $this->pollAccess->canCreate(
            $this->buildChain($forumId),
            $this->getMemberGroupIds(),
            (int) FrontendUser::getInstance()->id
        );
    }

    /**
     * Ist das angemeldete Mitglied Administrator oder Moderator dieses Forums?
     * (Vererbte Rollen; siehe RoleAccess.)
     */
    private function isTeamMember(int $forumId): bool
    {
        if (!$this->isMemberLoggedIn()) {
            return false;
        }

        $chain = $this->buildChain($forumId);
        $groups = $this->getMemberGroupIds();
        $memberId = (int) FrontendUser::getInstance()->id;

        return $this->roleAccess->isAdmin($chain, $groups, $memberId)
            || $this->roleAccess->isModerator($chain, $groups, $memberId);
    }

    /**
     * Darf das angemeldete Mitglied in diesem Forum eine Moderationsaktion
     * ausfuehren? Administratoren duerfen immer alles; Moderatoren nur, wenn die
     * zugehoerige globale Einstellung aktiv ist.
     *
     * @param string $capability pin|lock|move|edit
     */
    private function canModerate(int $forumId, string $capability): bool
    {
        if (!$this->isMemberLoggedIn()) {
            return false;
        }

        $chain = $this->buildChain($forumId);
        $groups = $this->getMemberGroupIds();
        $memberId = (int) FrontendUser::getInstance()->id;

        if ($this->roleAccess->isAdmin($chain, $groups, $memberId)) {
            return true;
        }

        if (!$this->roleAccess->isModerator($chain, $groups, $memberId)) {
            return false;
        }

        $map = [
            'pin' => 'modCanPin',
            'lock' => 'modCanLock',
            'move' => 'modCanMove',
            'edit' => 'modCanEditPosts',
            'ban' => 'modCanBan',
        ];

        if (!isset($map[$capability])) {
            return false;
        }

        $settings = $this->forumSettings();

        // Sperren ist fuer Moderatoren standardmaessig AUS (Default ''), die
        // uebrigen Rechte standardmaessig AN (Default '1').
        $default = 'ban' === $capability ? '' : '1';

        return '1' === (string) ($settings[$map[$capability]] ?? $default);
    }

    /**
     * Darf das angemeldete Mitglied Themen in diesem Forum anpinnen?
     */
    private function canPin(int $forumId): bool
    {
        return $this->canModerate($forumId, 'pin');
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

            // Foren verlinken auf ihre Themenliste, Kategorien auf ihre
            // Einzelansicht - so lassen sich beide separat aufrufen.
            if ('forum' === $node['type']) {
                $url = $this->pageUrl(['forum' => (int) $node['id']]);
            } elseif ('category' === $node['type']) {
                $url = $this->pageUrl(['category' => (int) $node['id']]);
            } else {
                $url = '';
            }

            array_unshift($items, [
                'title' => (string) $node['title'],
                'url' => $url,
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
     * Vermerkt einen Themenaufruf in der Sitzung und meldet, ob dies der erste
     * Aufruf in dieser Sitzung war (nur dann wird gezaehlt).
     *
     * Bewusst ueber die Sitzung statt ueber IP-Adressen: kein personenbezogenes
     * Datum, keine Aufbewahrungsfrist. Ein Reload zaehlt so nicht mit; ein neuer
     * Besuch (neue Sitzung) zaehlt wieder einmal.
     */
    private function registerView(int $topicId): bool
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if (null === $request || !$request->hasSession()) {
            // Ohne Sitzung wie bisher zaehlen, statt gar nicht.
            return true;
        }

        $session = $request->getSession();
        $viewed = $session->get('synapsis_viewed', []);

        if (\in_array($topicId, $viewed, true)) {
            return false;
        }

        $viewed[] = $topicId;

        // Liste begrenzen, damit die Sitzung nicht unbegrenzt waechst.
        if (\count($viewed) > 300) {
            $viewed = \array_slice($viewed, -300);
        }

        $session->set('synapsis_viewed', $viewed);

        return true;
    }

    /**
     * Lesestand-Verwaltung (Tabelle tl_synapsis_read), lazy erzeugt.
     */
    private function readTracker(): ReadTracker
    {
        if (null === $this->readTracker) {
            $this->readTracker = new ReadTracker(System::getContainer()->get('database_connection'));
        }

        return $this->readTracker;
    }

    /**
     * "Gefaellt mir"-Verwaltung (Tabelle tl_synapsis_like), lazy erzeugt.
     */
    private function likeManager(): LikeManager
    {
        if (null === $this->likeManager) {
            $this->likeManager = new LikeManager(System::getContainer()->get('database_connection'));
        }

        return $this->likeManager;
    }

    /**
     * Sperren-Verwaltung (Tabelle tl_synapsis_ban), lazy erzeugt.
     */
    private function banManager(): BanManager
    {
        if (null === $this->banManager) {
            $this->banManager = new BanManager(System::getContainer()->get('database_connection'));
        }

        return $this->banManager;
    }

    /**
     * Ist das aktuell angemeldete Mitglied fuer das Forum gesperrt? (Gaeste nie.)
     */
    private function isCurrentMemberBanned(): bool
    {
        return $this->isMemberLoggedIn()
            && $this->banManager()->isBanned((int) FrontendUser::getInstance()->id);
    }

    /**
     * Umfragen-Verwaltung (Tabellen tl_synapsis_poll*), lazy erzeugt.
     */
    private function pollManager(): PollManager
    {
        if (null === $this->pollManager) {
            $this->pollManager = new PollManager(System::getContainer()->get('database_connection'));
        }

        return $this->pollManager;
    }

    /**
     * Markiert das Thema fuer das angemeldete Mitglied als gelesen.
     */
    private function markTopicRead(int $topicId): void
    {
        $this->readTracker()->markRead($this->currentAuthorId(), $topicId);
    }

    /**
     * Ungelesen fuer das angemeldete Mitglied? $latestTs = Datum des neuesten
     * Beitrags im Thema.
     */
    private function isTopicUnread(int $topicId, int $latestTs): bool
    {
        return $this->readTracker()->isUnread($this->currentAuthorId(), $topicId, $latestTs);
    }

    /**
     * Enthaelt einer der Foren (inkl. Unterforen) ein ungelesenes Thema?
     *
     * @param array<int> $forumIds
     */
    private function forumHasUnread(array $forumIds): bool
    {
        return $this->readTracker()->forumHasUnread($this->currentAuthorId(), $forumIds);
    }

    /**
     * Prueft, ob ein Frontend-Mitglied angemeldet ist.
     */
    private function isMemberLoggedIn(): bool
    {
        return System::getContainer()->get('contao.security.token_checker')->hasFrontendUser();
    }

    /**
     * Autor-ID des aktuellen Beitrags: Mitglieds-ID bei Anmeldung, sonst 0
     * (Gast).
     */
    private function currentAuthorId(): int
    {
        return $this->isMemberLoggedIn() ? (int) FrontendUser::getInstance()->id : 0;
    }

    /**
     * Mitgliedergruppen des angemeldeten Besuchers (bei Gaesten leer).
     *
     * Gaeste steuert nicht die Gruppenzugehoerigkeit, sondern die Flags
     * guestRead/guestWrite (siehe ForumAccess).
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
     * Prueft, ob das angemeldete Mitglied das Thema abonniert hat.
     */
    private function isSubscribed(int $topicId): bool
    {
        if (!$this->isMemberLoggedIn()) {
            return false;
        }

        return (bool) Database::getInstance()
            ->prepare('SELECT id FROM tl_synapsis_subscription WHERE member = ? AND topic = ?')
            ->execute((int) FrontendUser::getInstance()->id, $topicId)
            ->numRows
        ;
    }

    /**
     * Benachrichtigt die Abonnenten eines Themas per E-Mail ueber eine neue
     * Antwort - mit Ausnahme des Verfassers.
     */
    private function notifySubscribers(int $topicId, int $excludeMemberId): void
    {
        $settings = $this->forumSettings();

        // Benachrichtigungen koennen global abgeschaltet werden.
        if ('1' !== (string) ($settings['notifyEnabled'] ?? '1')) {
            return;
        }

        $subscribers = Database::getInstance()
            ->prepare('SELECT m.email, m.firstname, m.lastname FROM tl_synapsis_subscription s INNER JOIN tl_member m ON m.id = s.member WHERE s.topic = ? AND s.member != ?')
            ->execute($topicId, $excludeMemberId)
            ->fetchAllAssoc()
        ;

        if (empty($subscribers)) {
            return;
        }

        $title = (string) $this->activeTopic['title'];
        $url = $this->absoluteUrl(['topic' => $topicId]);

        $subjectTpl = '' !== (string) ($settings['notifySubject'] ?? '') ? (string) $settings['notifySubject'] : 'Neue Antwort im Thema "##topic##"';
        $bodyTpl = '' !== (string) ($settings['notifyBody'] ?? '') ? (string) $settings['notifyBody'] : "Hallo ##name##,\n\nim Thema \"##topic##\" wurde eine neue Antwort verfasst.\n\n##url##\n";
        $senderName = (string) ($settings['senderName'] ?? '');
        $senderEmail = (string) ($settings['senderEmail'] ?? '');

        foreach ($subscribers as $subscriber) {
            if ('' === (string) $subscriber['email']) {
                continue;
            }

            $tokens = [
                'topic' => $title,
                'name' => trim(($subscriber['firstname'] ?? '').' '.($subscriber['lastname'] ?? '')),
                'url' => $url,
            ];

            try {
                $email = new Email();

                if ('' !== $senderEmail) {
                    $email->from = $senderEmail;
                }

                if ('' !== $senderName) {
                    $email->fromName = $senderName;
                }

                $email->subject = NotificationTemplate::render($subjectTpl, $tokens);
                $email->text = NotificationTemplate::render($bodyTpl, $tokens);
                $email->sendTo($subscriber['email']);
            } catch (\Exception $e) {
                // Einzelne fehlgeschlagene Zustellung darf den Beitrag nicht verhindern
            }
        }
    }

    /**
     * Prueft, ob das angemeldete Mitglied das ganze Forum abonniert hat.
     */
    private function isForumSubscribed(int $forumId): bool
    {
        if (!$this->isMemberLoggedIn()) {
            return false;
        }

        return (bool) Database::getInstance()
            ->prepare('SELECT id FROM tl_synapsis_forum_sub WHERE member = ? AND forum = ?')
            ->execute((int) FrontendUser::getInstance()->id, $forumId)
            ->numRows
        ;
    }

    /**
     * Schaltet das Abonnement des aktiven Forums fuer das angemeldete Mitglied
     * um (an/aus) und laedt die Themenliste neu. Abonnenten erhalten eine
     * E-Mail, sobald in diesem Forum ein neues Thema erstellt wird.
     */
    private function handleForumSubscription(): void
    {
        if ('synapsis_fsub_'.$this->id !== Input::post('FORM_SUBMIT')) {
            return;
        }

        if (!$this->isMemberLoggedIn()) {
            return;
        }

        $memberId = (int) FrontendUser::getInstance()->id;
        $forumId = (int) $this->activeForum['id'];

        if ($this->isForumSubscribed($forumId)) {
            Database::getInstance()
                ->prepare('DELETE FROM tl_synapsis_forum_sub WHERE member = ? AND forum = ?')
                ->execute($memberId, $forumId)
            ;
        } else {
            Database::getInstance()
                ->prepare('INSERT INTO tl_synapsis_forum_sub %s')
                ->set(['member' => $memberId, 'forum' => $forumId, 'tstamp' => time()])
                ->execute()
            ;
        }

        $this->redirect($this->pageUrl(['forum' => $forumId]));
    }

    /**
     * Benachrichtigt die Abonnenten eines Forums per E-Mail ueber ein neues
     * Thema - mit Ausnahme des Verfassers.
     */
    private function notifyForumSubscribers(int $forumId, int $topicId, string $title, int $excludeMemberId): void
    {
        $settings = $this->forumSettings();

        if ('1' !== (string) ($settings['notifyEnabled'] ?? '1')) {
            return;
        }

        $subscribers = Database::getInstance()
            ->prepare('SELECT m.email, m.firstname, m.lastname FROM tl_synapsis_forum_sub s INNER JOIN tl_member m ON m.id = s.member WHERE s.forum = ? AND s.member != ?')
            ->execute($forumId, $excludeMemberId)
            ->fetchAllAssoc()
        ;

        if (empty($subscribers)) {
            return;
        }

        $forumTitle = $this->forumTitle($forumId);
        $url = $this->absoluteUrl(['topic' => $topicId]);
        $subjectTpl = $GLOBALS['TL_LANG']['MSC']['synapsisNewTopicSubject'] ?? 'Neues Thema im Forum "##forum##"';
        $bodyTpl = $GLOBALS['TL_LANG']['MSC']['synapsisNewTopicBody'] ?? "Hallo ##name##,\n\nim Forum \"##forum##\" wurde ein neues Thema erstellt: \"##topic##\".\n\n##url##\n";

        foreach ($subscribers as $subscriber) {
            $tokens = [
                'forum' => $forumTitle,
                'topic' => $title,
                'name' => trim(($subscriber['firstname'] ?? '').' '.($subscriber['lastname'] ?? '')),
                'url' => $url,
            ];

            $this->sendMail(
                (string) $subscriber['email'],
                NotificationTemplate::render($subjectTpl, $tokens),
                NotificationTemplate::render($bodyTpl, $tokens),
                $settings
            );
        }
    }

    /**
     * Benachrichtigt das Team (Administratoren und/oder Moderatoren) eines Forums
     * per E-Mail ueber einen neuen Beitrag. Umfang und Vorlage stammen aus den
     * globalen Foreneinstellungen.
     *
     * @param string $event topic (neues Thema) oder reply (Antwort)
     */
    private function notifyTeam(int $forumId, string $event, int $topicId, string $title, string $authorName): void
    {
        $settings = $this->forumSettings();

        if ('1' !== (string) ($settings['notifyEnabled'] ?? '1')) {
            return;
        }

        $wantAdmins = '1' === (string) ($settings['teamNotifyAdmins'] ?? '');
        $wantMods = '1' === (string) ($settings['teamNotifyMods'] ?? '');

        if (!$wantAdmins && !$wantMods) {
            return;
        }

        $on = (string) ($settings['teamNotifyOn'] ?? 'both');

        if ('both' !== $on && $on !== $event) {
            return;
        }

        $ids = array_diff($this->teamMemberIds($forumId, $wantAdmins, $wantMods), [$this->currentAuthorId()]);

        if ([] === $ids) {
            return;
        }

        $subjectTpl = '' !== (string) ($settings['teamSubject'] ?? '') ? (string) $settings['teamSubject'] : 'Forum: neuer Beitrag im Thema "##topic##"';
        $bodyTpl = '' !== (string) ($settings['teamBody'] ?? '') ? (string) $settings['teamBody'] : "Im Forum \"##forum##\" hat ##author## im Thema \"##topic##\" geschrieben.\n\n##url##\n";

        $tokens = [
            'forum' => $this->forumTitle($forumId),
            'topic' => $title,
            'author' => $authorName,
            'url' => $this->absoluteUrl(['topic' => $topicId]),
        ];

        $subject = NotificationTemplate::render($subjectTpl, $tokens);
        $body = NotificationTemplate::render($bodyTpl, $tokens);

        $placeholders = implode(',', array_fill(0, \count($ids), '?'));
        $recipients = Database::getInstance()
            ->prepare("SELECT email FROM tl_member WHERE id IN ($placeholders) AND email != '' AND disable = ''")
            ->execute(...array_values($ids))
            ->fetchAllAssoc()
        ;

        foreach ($recipients as $recipient) {
            $this->sendMail((string) $recipient['email'], $subject, $body, $settings);
        }
    }

    /**
     * Ermittelt die Mitglieder-IDs, die in einem Forum Administrator und/oder
     * Moderator sind (inklusive vererbter Rechte aus der Kette). Gruppenrechte
     * werden ueber die Gruppenzugehoerigkeit der Mitglieder aufgeloest.
     *
     * @return array<int>
     */
    private function teamMemberIds(int $forumId, bool $wantAdmins, bool $wantMods): array
    {
        $chain = $this->buildChain($forumId);

        if ([] === $chain) {
            return [];
        }

        $groupIds = [];
        $memberIds = [];

        foreach ($chain as $node) {
            if ($wantAdmins) {
                $groupIds = array_merge($groupIds, $this->deserializeIds($node['adminGroups'] ?? null));
                $memberIds = array_merge($memberIds, $this->deserializeIds($node['adminMembers'] ?? null));
            }

            if ($wantMods) {
                $groupIds = array_merge($groupIds, $this->deserializeIds($node['modGroups'] ?? null));
                $memberIds = array_merge($memberIds, $this->deserializeIds($node['modMembers'] ?? null));
            }
        }

        $groupIds = array_values(array_unique(array_filter($groupIds)));

        // Mitgliedergruppen -> einzelne Mitglieder aufloesen.
        if ([] !== $groupIds) {
            $candidates = Database::getInstance()
                ->prepare("SELECT id, groups FROM tl_member WHERE groups IS NOT NULL AND groups != ''")
                ->execute()
                ->fetchAllAssoc()
            ;

            foreach ($candidates as $candidate) {
                $memberGroups = array_map('intval', StringUtil::deserialize($candidate['groups'], true));

                if ([] !== array_intersect($memberGroups, $groupIds)) {
                    $memberIds[] = (int) $candidate['id'];
                }
            }
        }

        return array_values(array_unique(array_filter(array_map('intval', $memberIds))));
    }

    /**
     * Ist am Startpunkt eingestellt, dass die Moderatoren-Namen im Frontend
     * angezeigt werden sollen? (Ergebnis wird zwischengespeichert.)
     */
    private function showModerators(): bool
    {
        if (null !== $this->showModeratorsCache) {
            return $this->showModeratorsCache;
        }

        $value = (string) Database::getInstance()
            ->prepare('SELECT showModerators FROM tl_synapsis_forum WHERE id = ?')
            ->execute($this->rootId)
            ->row(true)[0]
        ;

        return $this->showModeratorsCache = ('1' === $value);
    }

    /**
     * Liefert die Anzeigenamen der Moderatoren eines Forums (vererbt aus der
     * Kette; als Gruppe gewaehlte Moderatoren werden zu Einzelnamen aufgeloest).
     *
     * @return array<string>
     */
    private function moderatorNames(int $forumId): array
    {
        $ids = $this->teamMemberIds($forumId, false, true);

        if ([] === $ids) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, \count($ids), '?'));
        $rows = Database::getInstance()
            ->prepare("SELECT firstname, lastname, username FROM tl_member WHERE id IN ($placeholders) ORDER BY lastname, firstname, username")
            ->execute(...$ids)
            ->fetchAllAssoc()
        ;

        $names = [];

        foreach ($rows as $row) {
            $name = trim(($row['firstname'] ?? '').' '.($row['lastname'] ?? ''));
            $names[] = '' !== $name ? $name : (string) $row['username'];
        }

        return $names;
    }

    /**
     * Fuehrt einen (evtl. serialisierten) Wert in eine Liste von Integer-IDs
     * ueber - fuer die Rollenfelder der Forenkette.
     *
     * @param mixed $value
     *
     * @return array<int>
     */
    private function deserializeIds($value): array
    {
        $array = StringUtil::deserialize($value, true);

        return array_values(array_filter(array_map('intval', $array)));
    }

    /**
     * Erkennt @Benutzername-Erwaehnungen im Beitragstext und liefert die
     * zugehoerigen Mitglieds-IDs (hoechstens zehn, um Missbrauch zu begrenzen).
     *
     * @return array<int>
     */
    private function detectMentions(string $text): array
    {
        $plain = strip_tags($text);

        if (!preg_match_all('/@([A-Za-z0-9._\x{00C0}-\x{024F}-]{2,64})/u', $plain, $matches)) {
            return [];
        }

        $names = array_values(array_unique($matches[1]));
        $names = \array_slice($names, 0, 10);

        if ([] === $names) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, \count($names), '?'));
        $rows = Database::getInstance()
            ->prepare("SELECT id FROM tl_member WHERE username IN ($placeholders) AND username != ''")
            ->execute(...$names)
            ->fetchAllAssoc()
        ;

        return array_map('intval', array_column($rows, 'id'));
    }

    /**
     * Nachbereitung einer Antwort: persoenliche Benachrichtigungen (Erwaehnung,
     * Zitat, Antwort auf das eigene Thema) und E-Mail an das Team.
     */
    private function afterReply(int $postId, int $topicId, int $forumId, int $authorId, string $text, int $quotePostId): void
    {
        $notified = [$authorId => true];

        // @Erwaehnungen im Text
        foreach ($this->detectMentions($text) as $mentionId) {
            if (isset($notified[$mentionId])) {
                continue;
            }

            $this->createNotification($mentionId, 'mention', $topicId, $postId, $forumId, $authorId);
            $notified[$mentionId] = true;
        }

        // Zitierter Beitrag -> dessen Autor benachrichtigen
        if ($quotePostId > 0) {
            $quoted = $this->findPostInTopic($quotePostId);

            if (null !== $quoted) {
                $quotedAuthor = (int) $quoted['author'];

                if ($quotedAuthor > 0 && !isset($notified[$quotedAuthor])) {
                    $this->createNotification($quotedAuthor, 'quote', $topicId, $postId, $forumId, $authorId);
                    $notified[$quotedAuthor] = true;
                }
            }
        }

        // Antwort auf das eigene Thema -> Themenersteller benachrichtigen
        $topicAuthor = (int) ($this->activeTopic['author'] ?? 0);

        if ($topicAuthor > 0 && !isset($notified[$topicAuthor])) {
            $this->createNotification($topicAuthor, 'reply', $topicId, $postId, $forumId, $authorId);
        }

        $this->notifyTeam($forumId, 'reply', $topicId, (string) $this->activeTopic['title'], $this->memberUsername($authorId));
    }

    /**
     * Nachbereitung eines neuen Themas: E-Mail an Forum-Abonnenten und Team,
     * persoenliche Erwaehnungs-Benachrichtigungen aus dem ersten Beitrag.
     */
    private function afterNewTopic(int $topicId, int $forumId, int $authorId, string $title, int $firstPostId, string $text): void
    {
        $this->notifyForumSubscribers($forumId, $topicId, $title, $authorId);

        foreach ($this->detectMentions($text) as $mentionId) {
            $this->createNotification($mentionId, 'mention', $topicId, $firstPostId, $forumId, $authorId);
        }

        $this->notifyTeam($forumId, 'topic', $topicId, $title, $this->memberUsername($authorId));
    }

    /**
     * Versendet eine Forum-E-Mail mit den Absenderangaben aus den
     * Foreneinstellungen. Fehlerhafte Einzelzustellungen werden verschluckt.
     *
     * @param array<string, mixed> $settings
     */
    private function sendMail(string $to, string $subject, string $body, array $settings): void
    {
        if ('' === $to) {
            return;
        }

        try {
            $email = new Email();
            $senderEmail = (string) ($settings['senderEmail'] ?? '');
            $senderName = (string) ($settings['senderName'] ?? '');

            if ('' !== $senderEmail) {
                $email->from = $senderEmail;
            }

            if ('' !== $senderName) {
                $email->fromName = $senderName;
            }

            $email->subject = $subject;
            $email->text = $body;
            $email->sendTo($to);
        } catch (\Exception $e) {
            // Einzelne fehlgeschlagene Zustellung darf den Beitrag nicht verhindern
        }
    }

    /**
     * Globale Foreneinstellungen (einzelner Datensatz id=1). Leeres Array, wenn
     * noch nichts gespeichert wurde - dann gelten die Standardwerte.
     *
     * @return array<string, mixed>
     */
    private function forumSettings(): array
    {
        $row = Database::getInstance()
            ->prepare('SELECT * FROM tl_synapsis_settings WHERE id = 1')
            ->execute()
            ->row()
        ;

        return \is_array($row) ? $row : [];
    }

    /**
     * Baut eine absolute URL auf der aktuellen Seite (fuer E-Mails).
     *
     * @param array<string, int|string> $params
     */
    private function absoluteUrl(array $params): string
    {
        $relative = $this->pageUrl($params);

        if (preg_match('#^https?://#', $relative)) {
            return $relative;
        }

        return Environment::get('base').ltrim($relative, '/');
    }

    /**
     * Liefert den anzuzeigenden Autornamen.
     *
     * Existiert das Mitglied noch, wird sein aktueller Name gezeigt. Bei Gaesten
     * (author=0) oder geloeschten Konten wird der gespeicherte Benutzername als
     * „Gast (Name)" ausgegeben (siehe AuthorLabel).
     */
    private function authorLabel(int $memberId, string $storedName): string
    {
        $guestWord = $GLOBALS['TL_LANG']['MSC']['synapsisGuest'] ?? 'Gast';

        return AuthorLabel::format($this->liveMemberName($memberId), $storedName, $guestWord);
    }

    /**
     * Aktueller Anzeigename eines noch existierenden Mitglieds, sonst null
     * (Gast oder geloeschtes Konto).
     */
    private function liveMemberName(int $memberId): ?string
    {
        if ($memberId <= 0) {
            return null;
        }

        $member = Database::getInstance()
            ->prepare('SELECT firstname, lastname, username FROM tl_member WHERE id = ?')
            ->execute($memberId)
            ->row()
        ;

        if (empty($member)) {
            return null;
        }

        $name = trim(($member['firstname'] ?? '').' '.($member['lastname'] ?? ''));

        return '' !== $name ? $name : (string) $member['username'];
    }

    /**
     * Benutzername eines Mitglieds als Momentaufnahme fuer die Speicherung
     * (leer bei Gaesten oder unbekannter ID).
     */
    private function memberUsername(int $memberId): string
    {
        if ($memberId <= 0) {
            return '';
        }

        $row = Database::getInstance()
            ->prepare('SELECT username FROM tl_member WHERE id = ?')
            ->execute($memberId)
            ->row(true)
        ;

        return \is_array($row) ? (string) ($row[0] ?? '') : '';
    }

    /**
     * Anzeigenamen mehrerer Mitglieder (fuer die "Gefaellt mir"-Liste). Nicht
     * mehr existierende Mitglieder werden ausgelassen.
     *
     * @param array<int> $ids
     *
     * @return array<string>
     */
    private function memberNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($i) => $i > 0)));

        if ([] === $ids) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, \count($ids), '?'));

        $rows = Database::getInstance()
            ->prepare('SELECT firstname, lastname, username FROM tl_member WHERE id IN ('.$placeholders.')')
            ->execute(...$ids)
            ->fetchAllAssoc()
        ;

        $names = [];

        foreach ($rows as $row) {
            $name = trim(($row['firstname'] ?? '').' '.($row['lastname'] ?? ''));
            $names[] = '' !== $name ? $name : (string) ($row['username'] ?? '');
        }

        return $names;
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
        // Smiley-Leiste: klassische Forensmilies als Unicode-Emoji. Funktioniert
        // sowohl mit dem TinyMCE-Editor (Emoji-Auswahl gibt es zusaetzlich als
        // Toolbar-Button) als auch mit einer einfachen Textarea.
        $this->Template->emoticons = ['🙂', '😀', '😉', '😍', '😎', '😲', '😢', '😡', '👍', '👎', '❤️', '😂'];
        $this->addSmileyScript();

        if (!$this->synapsis_editor) {
            $this->Template->editor = false;

            return;
        }

        $this->Template->editor = true;

        // Contao liefert TinyMCE 5 unter assets/tinymce4 mit; von dort laden wir
        // den Editor. Der Base-Pfad zeigt auf das js-Verzeichnis, damit Plugins
        // und Skins gefunden werden. Er MUSS absolut sein - im Vorschau-Modus
        // (preview.php) wuerde ein relativer Pfad sonst zu
        // "preview.php/assets/..." aufgeloest und die Skins/Plugins per 404
        // fehlschlagen.
        $base = \Contao\Controller::addStaticUrlTo('assets/tinymce4/js');

        if (!preg_match('#^https?://#', $base)) {
            $base = Environment::get('base').ltrim($base, '/');
        }

        $GLOBALS['TL_JAVASCRIPT']['synapsis_tinymce'] = 'assets/tinymce4/js/tinymce.min.js|static';

        // Versions-Schutz: nur mit TinyMCE 4+ initialisieren. Ist auf der Seite
        // (etwa durch ein anderes Forum-Bundle) ein aeltes TinyMCE 3 aktiv,
        // wird nicht initialisiert, statt eine fehlerhafte Einbindung zu
        // erzeugen.
        $GLOBALS['TL_BODY']['synapsis_tinymce_init'] = '<script>document.addEventListener("DOMContentLoaded",function(){'
            .'if(typeof tinymce==="undefined"||parseInt(tinymce.majorVersion,10)<4)return;'
            .'tinymce.init({'
            .'selector:"textarea.synapsis-editor",'
            .'license_key:"gpl",'
            .'base_url:"'.$base.'",'
            .'suffix:".min",'
            .'menubar:false,'
            .'height:260,'
            .'plugins:"lists link image",'
            .'toolbar:"bold italic | bullist numlist | link image",'
            .'branding:false'
            .'});});</script>'
        ;
    }

    /**
     * Bindet das Entwurf-Skript ein: der Text des Antwort-/Neues-Thema-Feldes
     * wird lokal im Browser (localStorage) zwischengespeichert und kann nach
     * einem Verlassen der Seite wiederhergestellt werden. Beim Absenden wird der
     * Entwurf verworfen. Funktioniert mit dem TinyMCE-Editor und einer einfachen
     * Textarea (kein Server, keine Tabelle).
     */
    private function addDraftScript(): void
    {
        $found = $GLOBALS['TL_LANG']['MSC']['synapsisDraftFound'] ?? 'Gespeicherter Entwurf gefunden.';
        $restore = $GLOBALS['TL_LANG']['MSC']['synapsisDraftRestore'] ?? 'Wiederherstellen';
        $discard = $GLOBALS['TL_LANG']['MSC']['synapsisDraftDiscard'] ?? 'Verwerfen';

        $GLOBALS['TL_BODY']['synapsis_draft'] = '<script>document.addEventListener("DOMContentLoaded",function(){'
            .'var ta=document.querySelector("textarea[data-draft]");if(!ta||!window.localStorage)return;'
            .'var key=ta.getAttribute("data-draft-key"),form=ta.closest("form");'
            .'function plain(v){return (v||"").replace(/<[^>]*>/g," ").replace(/&[a-z#0-9]+;/gi," ").trim();}'
            .'function read(){if(window.tinymce){try{tinymce.triggerSave();}catch(e){}}return ta.value;}'
            .'function write(v){if(window.tinymce&&tinymce.editors){for(var i=0;i<tinymce.editors.length;i++){if(tinymce.editors[i].targetElm===ta){tinymce.editors[i].setContent(v);return;}}}ta.value=v;}'
            .'try{var raw=localStorage.getItem(key);if(raw){var d=JSON.parse(raw);if(d&&plain(d.v)){'
            .'var bar=document.createElement("div");bar.className="synapsis-draftbar";'
            .'var span=document.createElement("span");span.textContent='.json_encode($found).'+(d.t?" ("+new Date(d.t).toLocaleString()+")":"");bar.appendChild(span);bar.appendChild(document.createTextNode(" "));'
            .'var b1=document.createElement("button");b1.type="button";b1.className="synapsis-button synapsis-button--secondary";b1.textContent='.json_encode($restore).';'
            .'var b2=document.createElement("button");b2.type="button";b2.className="synapsis-button";b2.textContent='.json_encode($discard).';'
            .'bar.appendChild(b1);bar.appendChild(document.createTextNode(" "));bar.appendChild(b2);'
            .'ta.parentNode.insertBefore(bar,ta);'
            .'b1.addEventListener("click",function(){write(d.v);bar.parentNode.removeChild(bar);});'
            .'b2.addEventListener("click",function(){localStorage.removeItem(key);bar.parentNode.removeChild(bar);});'
            .'}}}catch(e){}'
            .'function save(){var v=read();if(plain(v)){localStorage.setItem(key,JSON.stringify({t:Date.now(),v:v}));}}'
            .'ta.addEventListener("input",save);var iv=setInterval(save,5000);'
            .'if(form)form.addEventListener("submit",function(){clearInterval(iv);localStorage.removeItem(key);});'
            .'});</script>'
        ;
    }

    /**
     * Bindet einmalig das Skript ein, das einen Klick auf die Smiley-Leiste
     * verarbeitet: Der Smiley wird in den aktiven TinyMCE-Editor eingefuegt,
     * andernfalls an der Cursorposition in die einfache Textarea.
     */
    private function addSmileyScript(): void
    {
        $GLOBALS['TL_BODY']['synapsis_smiley'] = '<script>document.addEventListener("click",function(e){'
            .'var b=e.target.closest&&e.target.closest(".synapsis-smiley");if(!b)return;e.preventDefault();'
            .'var emoji=b.getAttribute("data-emoji");var form=b.closest("form");if(!form)return;'
            .'var ta=form.querySelector("textarea");if(!ta)return;'
            .'if(window.tinymce&&ta.id){var ed=tinymce.get(ta.id);if(ed&&!ed.isHidden()){ed.insertContent(emoji);return;}}'
            .'var s=ta.selectionStart||0,en=ta.selectionEnd||0;'
            .'ta.value=ta.value.slice(0,s)+emoji+ta.value.slice(en);'
            .'ta.selectionStart=ta.selectionEnd=s+emoji.length;ta.focus();'
            .'});</script>'
        ;
    }

    /**
     * Bindet einmalig das Skript ein, das die BB-Code-Buttons der Signatur
     * verarbeitet: Der markierte Text im zugehoerigen Textfeld wird mit den
     * BB-Code-Marken umschlossen (data-open/data-close).
     */
    private function addBBCodeScript(): void
    {
        $GLOBALS['TL_BODY']['synapsis_bbcode'] = '<script>document.addEventListener("click",function(e){'
            .'var b=e.target.closest&&e.target.closest(".synapsis-bb");if(!b)return;e.preventDefault();'
            .'var open=b.getAttribute("data-open"),close=b.getAttribute("data-close");'
            .'var form=b.closest("form");if(!form)return;var ta=form.querySelector("textarea");if(!ta)return;'
            .'var s=ta.selectionStart||0,en=ta.selectionEnd||0,sel=ta.value.slice(s,en);'
            .'ta.value=ta.value.slice(0,s)+open+sel+close+ta.value.slice(en);'
            .'ta.selectionStart=ta.selectionEnd=s+open.length+sel.length;ta.focus();'
            .'});</script>'
        ;
    }
}
