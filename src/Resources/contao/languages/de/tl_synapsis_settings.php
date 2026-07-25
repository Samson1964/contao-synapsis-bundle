<?php

declare(strict_types=1);

/*
 * Dieses Bundle stellt das Forum "Synapsis" fuer Contao 4.13 und Contao 5 bereit.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Backend-Beschriftungen der globalen Foreneinstellungen.
 */

// Legenden
$GLOBALS['TL_LANG']['tl_synapsis_settings']['notify_legend']     = 'E-Mail bei neuer Antwort';
$GLOBALS['TL_LANG']['tl_synapsis_settings']['team_legend']       = 'Benachrichtigung an das Team';
$GLOBALS['TL_LANG']['tl_synapsis_settings']['sender_legend']     = 'Absender (optional)';
$GLOBALS['TL_LANG']['tl_synapsis_settings']['moderators_legend'] = 'Rechte der Moderatoren';

// Felder (Moderatoren)
$GLOBALS['TL_LANG']['tl_synapsis_settings']['modCanPin'] = array(
    'Moderatoren dürfen Themen anpinnen',
    'Ist dies aktiv, dürfen Moderatoren Themen oben anpinnen. Administratoren dürfen das immer.',
);
$GLOBALS['TL_LANG']['tl_synapsis_settings']['modCanLock'] = array(
    'Moderatoren dürfen Themen schließen',
    'Ist dies aktiv, dürfen Moderatoren Themen schließen und wieder öffnen.',
);
$GLOBALS['TL_LANG']['tl_synapsis_settings']['modCanMove'] = array(
    'Moderatoren dürfen Themen verschieben',
    'Ist dies aktiv, dürfen Moderatoren Themen in ein anderes Forum verschieben.',
);
$GLOBALS['TL_LANG']['tl_synapsis_settings']['modCanEditPosts'] = array(
    'Moderatoren dürfen fremde Beiträge bearbeiten/löschen',
    'Ist dies aktiv, dürfen Moderatoren auch Beiträge anderer bearbeiten oder löschen.',
);

// Felder (Team-Benachrichtigung)
$GLOBALS['TL_LANG']['tl_synapsis_settings']['teamNotifyAdmins'] = array(
    'Administratoren benachrichtigen',
    'Administratoren des betroffenen Forums per E-Mail über neue Beiträge informieren.',
);
$GLOBALS['TL_LANG']['tl_synapsis_settings']['teamNotifyMods'] = array(
    'Moderatoren benachrichtigen',
    'Moderatoren des betroffenen Forums per E-Mail über neue Beiträge informieren.',
);
$GLOBALS['TL_LANG']['tl_synapsis_settings']['teamNotifyOn'] = array(
    'Wann benachrichtigen',
    'Bei neuen Themen, bei jeder Antwort oder bei beidem.',
);
$GLOBALS['TL_LANG']['tl_synapsis_settings']['teamSubject'] = array(
    'Betreff-Vorlage (Team)',
    'Platzhalter: ##forum##, ##topic##, ##author##, ##url##.',
);
$GLOBALS['TL_LANG']['tl_synapsis_settings']['teamBody'] = array(
    'Text-Vorlage (Team)',
    'Platzhalter: ##forum##, ##topic##, ##author##, ##url##.',
);
$GLOBALS['TL_LANG']['tl_synapsis_settings']['teamNotifyOnRef'] = array(
    'topic' => 'Nur bei neuen Themen',
    'reply' => 'Nur bei Antworten',
    'both'  => 'Bei neuen Themen und Antworten',
);

// Felder
$GLOBALS['TL_LANG']['tl_synapsis_settings']['notifyEnabled'] = array(
    'Benachrichtigungen aktiv',
    'Abonnenten eines Themas per E-Mail benachrichtigen, wenn eine neue Antwort verfasst wird.',
);
$GLOBALS['TL_LANG']['tl_synapsis_settings']['notifySubject'] = array(
    'Betreff-Vorlage',
    'Platzhalter: ##topic## (Thementitel), ##name## (Empfänger), ##url## (Adresse des Themas).',
);
$GLOBALS['TL_LANG']['tl_synapsis_settings']['notifyBody'] = array(
    'Text-Vorlage',
    'Platzhalter: ##topic##, ##name##, ##url##.',
);
$GLOBALS['TL_LANG']['tl_synapsis_settings']['senderName'] = array(
    'Absendername',
    'Leer lassen, um den Standard-Absender von Contao zu verwenden.',
);
$GLOBALS['TL_LANG']['tl_synapsis_settings']['senderEmail'] = array(
    'Absender-E-Mail',
    'Leer lassen, um den Standard-Absender von Contao zu verwenden.',
);
