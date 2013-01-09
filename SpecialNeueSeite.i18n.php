<?php
/**
 * Copyright (C) 2006-2012 Thomas Bleher, Leonhard Klein.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */
if (!defined('MEDIAWIKI')) die();

$messages = array(
// HACK: Wir definieren die Sprache als Englisch, weil 'en' auf jeden Fall vorhanden sein muss und es sich nicht lohnt, das zu duplizieren...
        'en' => array(
                'neueseite' => 'Neues Spiel',
                'neueseite-name' => 'Neues Spiel',
                'neueseite-titel' => 'Neues Spiel erstellen',
                'neueseite-einleitung' => 'Hier kannst du deine Spieldaten eingeben. Bei einem Klick auf Speichern wird automatisch eine neue "wiki"-Seite erstellt und dein Spiel eingetragen.',
		'neueseite-material-ueberschrift' => '== [[bild:utilities.png|<nowiki/>]] Material ==',
                'neueseite-grundgeruest' => 
'== [[bild:info.png|<nowiki/>]] Kurzbeschreibung ==
{{Kurzbeschreibung|<<Kurzbeschreibung>>}}

{{Vorbereitungsaufwand|<<Aufwand>>}}{{Dauer|<<Dauer>>}}
== [[bild:personen.png|<nowiki/>]] Gruppengrößen ==
{{Personenzahl|minLeiter=<<MinLeiter>>|minTeilnehmer=<<MinTeilnehmer>>|maxTeilnehmer=<<MaxTeilnehmer>>}}

== [[bild:buch.png|<nowiki/>]] Inhalt ==
<<Beschreibung>>
<<Material>>
{{<<Autortemplate>>|<<Autor>>}}
',
		'neueseite-anfangsbeschreibung' => 
'=== Geschichte ===
Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Phasellus
quis nulla. Praesent imperdiet blandit ipsum.

=== Anspiel ===
Hier kannst du ein evtl. Anspiel einbauen.

=== Spiele / Ablauf ===
* erstes Spiel

=== Spielende ===
Nur zur Erinnerung: Ein festdefiniertes Spielende macht sich meistens
gut...',

		'spielbearbeiten' => 'Spiel bearbeiten',
		'spielbearbeiten-name' => 'Spiel bearbeiten',
                'spielbearbeiten-einleitung' => '',
        )
);
