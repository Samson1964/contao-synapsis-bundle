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
$GLOBALS['TL_LANG']['tl_synapsis_settings']['sender_legend']     = 'Absender (optional)';
$GLOBALS['TL_LANG']['tl_synapsis_settings']['moderators_legend'] = 'Rechte der Moderatoren';

// Felder (Moderatoren)
$GLOBALS['TL_LANG']['tl_synapsis_settings']['modCanPin'] = array(
    'Moderatoren dürfen Themen anpinnen',
    'Ist dies aktiv, dürfen Moderatoren Themen oben anpinnen. Administratoren dürfen das immer.',
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
