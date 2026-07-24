<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Frontend-Texte des Synapsis-Forums (Bezeichnungen, Schaltflaechen, Hinweise).
 */

// Ueberschriften und Spalten
$GLOBALS['TL_LANG']['MSC']['synapsisHome']       = 'Forum';
$GLOBALS['TL_LANG']['MSC']['synapsisForum']      = 'Forum';
$GLOBALS['TL_LANG']['MSC']['synapsisTopic']      = 'Thema';
$GLOBALS['TL_LANG']['MSC']['synapsisTopics']     = 'Themen';
$GLOBALS['TL_LANG']['MSC']['synapsisPosts']      = 'Beiträge';
$GLOBALS['TL_LANG']['MSC']['synapsisReplies']    = 'Antworten';
$GLOBALS['TL_LANG']['MSC']['synapsisViews']      = 'Ansichten';
$GLOBALS['TL_LANG']['MSC']['synapsisLastPost']   = 'Letzter Beitrag';
$GLOBALS['TL_LANG']['MSC']['synapsisLatest']     = 'Neueste Themen';
$GLOBALS['TL_LANG']['MSC']['synapsisStats']      = 'Statistiken';
$GLOBALS['TL_LANG']['MSC']['synapsisTopPosters'] = 'Aktivste Mitglieder';

// Zustaende und Hinweise
$GLOBALS['TL_LANG']['MSC']['synapsisSticky']     = 'Angeheftet';
$GLOBALS['TL_LANG']['MSC']['synapsisLocked']     = 'Geschlossen';
$GLOBALS['TL_LANG']['MSC']['synapsisClosed']     = 'Geschlossen';
$GLOBALS['TL_LANG']['MSC']['synapsisUnknown']    = 'Unbekannt';
$GLOBALS['TL_LANG']['MSC']['synapsisGuest']      = 'Gast';
$GLOBALS['TL_LANG']['MSC']['synapsisNoForums']   = 'Keine Foren vorhanden.';
$GLOBALS['TL_LANG']['MSC']['synapsisNoTopics']   = 'In diesem Forum gibt es noch keine Themen.';
$GLOBALS['TL_LANG']['MSC']['synapsisLockedNote'] = 'Dieses Thema ist geschlossen. Es sind keine Antworten mehr möglich.';
$GLOBALS['TL_LANG']['MSC']['synapsisLoginNote']  = 'Bitte melden Sie sich an, um zu antworten.';

// Schaltflaechen und Formulare
$GLOBALS['TL_LANG']['MSC']['synapsisNewTopic']     = 'Neues Thema';
$GLOBALS['TL_LANG']['MSC']['synapsisNewTopicIn']   = 'Neues Thema in';
$GLOBALS['TL_LANG']['MSC']['synapsisCreateTopic']  = 'Thema erstellen';
$GLOBALS['TL_LANG']['MSC']['synapsisReply']        = 'Antworten';
$GLOBALS['TL_LANG']['MSC']['synapsisSubmitReply']  = 'Antwort absenden';
$GLOBALS['TL_LANG']['MSC']['synapsisCancel']       = 'Abbrechen';
$GLOBALS['TL_LANG']['MSC']['synapsisTitle']        = 'Titel';
$GLOBALS['TL_LANG']['MSC']['synapsisText']         = 'Text';
$GLOBALS['TL_LANG']['MSC']['synapsisAttachment']   = 'Dateianhänge';
$GLOBALS['TL_LANG']['MSC']['synapsisBackToForum']  = 'Zur Übersicht';
$GLOBALS['TL_LANG']['MSC']['synapsisBackToTopics'] = 'Zurück zum Forum';
$GLOBALS['TL_LANG']['MSC']['synapsisEmptyFields']  = 'Bitte Titel und Text ausfüllen.';
$GLOBALS['TL_LANG']['MSC']['synapsisEmptyText']    = 'Bitte einen Text eingeben.';

// Abonnements
$GLOBALS['TL_LANG']['MSC']['synapsisSubscribe']     = 'Thema abonnieren';
$GLOBALS['TL_LANG']['MSC']['synapsisUnsubscribe']   = 'Abo beenden';
$GLOBALS['TL_LANG']['MSC']['synapsisNotifySubject'] = 'Neue Antwort im Thema "%s"';
$GLOBALS['TL_LANG']['MSC']['synapsisNotifyBody']    = "Hallo %s,\n\nim Thema \"%s\" wurde eine neue Antwort verfasst.\n\n%s\n";

// Gelesen-Markierung und Mitglieder-Bereich (Paket B)
$GLOBALS['TL_LANG']['MSC']['synapsisUnread']          = 'Ungelesene Beiträge';
$GLOBALS['TL_LANG']['MSC']['synapsisMemberArea']      = 'Mein Bereich';
$GLOBALS['TL_LANG']['MSC']['synapsisMyPosts']         = 'Meine Beiträge';
$GLOBALS['TL_LANG']['MSC']['synapsisSubscriptions']   = 'Abonnements';
$GLOBALS['TL_LANG']['MSC']['synapsisSignature']       = 'Signatur';
$GLOBALS['TL_LANG']['MSC']['synapsisSignatureLabel']  = 'Deine Signatur (BB-Code erlaubt: [b] [i] [u] [s] [url] [color])';
$GLOBALS['TL_LANG']['MSC']['synapsisSave']            = 'Speichern';
$GLOBALS['TL_LANG']['MSC']['synapsisNoSubscriptions'] = 'Du hast keine Themen abonniert.';
$GLOBALS['TL_LANG']['MSC']['synapsisNoUnread']        = 'Es gibt keine ungelesenen Beiträge.';
$GLOBALS['TL_LANG']['MSC']['synapsisNoOwnPosts']      = 'Du hast noch keine Beiträge geschrieben.';

// Anpinnen (Moderatoren/Administratoren)
$GLOBALS['TL_LANG']['MSC']['synapsisPin']    = 'Oben anpinnen';
$GLOBALS['TL_LANG']['MSC']['synapsisUnpin']  = 'Nicht mehr anpinnen';
$GLOBALS['TL_LANG']['MSC']['synapsisPinned'] = 'Angepinnt';

// Umfragen
$GLOBALS['TL_LANG']['MSC']['synapsisAddPoll']       = 'Umfrage hinzufügen';
$GLOBALS['TL_LANG']['MSC']['synapsisPollQuestion']  = 'Frage';
$GLOBALS['TL_LANG']['MSC']['synapsisPollSingle']    = 'Einfachauswahl (eine Antwort)';
$GLOBALS['TL_LANG']['MSC']['synapsisPollMultiple']  = 'Mehrfachauswahl (mehrere Antworten)';
$GLOBALS['TL_LANG']['MSC']['synapsisPollOptions']   = 'Antwortmöglichkeiten (eine pro Zeile, mindestens zwei)';
$GLOBALS['TL_LANG']['MSC']['synapsisPollVote']      = 'Abstimmen';
$GLOBALS['TL_LANG']['MSC']['synapsisPollTotal']     = '%d Teilnehmende';
$GLOBALS['TL_LANG']['MSC']['synapsisPollClose']     = 'Umfrageende (danach kann nicht mehr abgestimmt werden)';
$GLOBALS['TL_LANG']['MSC']['synapsisPollHide']      = 'Ergebnisse erst nach Umfrageende anzeigen';
$GLOBALS['TL_LANG']['MSC']['synapsisPollRunning']   = 'Abstimmen möglich bis %s.';
$GLOBALS['TL_LANG']['MSC']['synapsisPollEnded']     = 'Umfrage beendet am %s.';
$GLOBALS['TL_LANG']['MSC']['synapsisPollPending']   = 'Die Ergebnisse werden nach dem Umfrageende angezeigt.';

// Forensuche
$GLOBALS['TL_LANG']['MSC']['synapsisSearch']            = 'Suche';
$GLOBALS['TL_LANG']['MSC']['synapsisSearchPlaceholder'] = 'Im Forum suchen …';
$GLOBALS['TL_LANG']['MSC']['synapsisSearchButton']      = 'Suchen';
$GLOBALS['TL_LANG']['MSC']['synapsisSearchTooShort']    = 'Bitte mindestens 2 Zeichen eingeben.';
$GLOBALS['TL_LANG']['MSC']['synapsisSearchHint']        = 'Gib einen Suchbegriff ein.';
$GLOBALS['TL_LANG']['MSC']['synapsisNoResults']         = 'Keine Treffer für „%s".';
$GLOBALS['TL_LANG']['MSC']['synapsisResultCount']       = '%d Treffer für „%s"';

// Gefaellt mir (Paket C)
$GLOBALS['TL_LANG']['MSC']['synapsisLike']       = 'Gefällt mir';
$GLOBALS['TL_LANG']['MSC']['synapsisUnlike']     = 'Gefällt mir nicht mehr';
$GLOBALS['TL_LANG']['MSC']['synapsisLikedPosts'] = 'Gefällt mir';
$GLOBALS['TL_LANG']['MSC']['synapsisNoLiked']    = 'Du hast noch keine Beiträge mit „Gefällt mir" markiert.';
