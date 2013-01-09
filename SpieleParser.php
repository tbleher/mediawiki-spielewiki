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
/*
TODO:
	Vorlage:Zeitaufwand
*/
class SpieleParser {
	public function __construct() {
		$this->Kurzbeschreibung_before = '';
		$this->Kurzbeschreibung = '';
		$this->Kurzbeschreibung_after = '';
		$this->Material = '* ';
		$this->Kategorien = array();
		$this->GruppenHeader = false;
		$this->Gruppe_after = '';
		$this->NLT = ''; // Zahl der Leiter
		$this->MinTN = '';
		$this->MaxTN = '';
		$this->Aufwand = -1;
		$this->Aufwand_Text = '';
		$this->Dauer = '';
		$this->Dauer_Text = '';
		$this->Beschreibung = '';
		$this->Autor = '';
		$this->Kommentare = '';
		$this->EditSummary = '';
		$this->OriginalText = NULL;
	}

	private function internalStoreMatches( $matches ) {
		$this->matches = &$matches;
		return '';
	}
	function parsePage( $text ) {
		$this->OriginalText = $text;

		if( preg_match( '/^#REDIRECT /', $text ) )
			return false;

		/* Sigh - das ist irgendwie in PHP noch viel uneleganter als in Perl */
		$text = preg_replace_callback( ',^(.*?\\n)?== \\[\\[bild:info\\.png\|<nowiki(?:/|></nowiki)>\\]\\] Kurzbeschreibung ==\\n?
\\{\\{Kurzbeschreibung\\|([^}]*?)\\}\\}([^{}]*?)(\\n\\{\\{Vorbereitungsaufwand\\|-?\\d*(?:\\|[^}]*)?\\}\\})?(\\{\\{Dauer(?:\\|\\d*(?:\\|[^}]*)?)?\\}\\})?\\n?(?=\\n==),si',
			array( $this, 'internalStoreMatches' ), $text, -1, $count );

		if( $count == 0 )
			return false;

		$this->Kurzbeschreibung_before = $this->matches[1];
		$this->Kurzbeschreibung        = $this->matches[2];
		$this->Kurzbeschreibung_after  = $this->matches[3];
		$t = preg_match( ',\\|(.*)\\}\\},', $this->matches[4], $m ) ? $m[1] : '-1';
		$a = explode( '|', $t, 2 );
		$this->Aufwand = $a[0];
		$this->Aufwand_Text = $a[1];
		$t = preg_match( ',\\|(.*)\\}\\},', $this->matches[5], $m ) ? $m[1] : '';
		$a = explode( '|', $t, 2 );
		$this->Dauer = $a[0];
		$this->Dauer_Text = $a[1];

		$text = preg_replace_callback( ',\\n== \\[\\[bild:personen.png\\|<nowiki(?:/|></nowiki)>\\]\\] Gruppengrößen ==
(\\{\\{Personenzahl(?:\\|(?:minLeiter=\\d*|minTeilnehmer=\\d*|maxTeilnehmer=\\d*))*\\}\\})(.*?)(?=\\n==),si',
			array( $this, 'internalStoreMatches' ), $text, -1, $count );
		if( $count > 0 ) {
			$this->GruppenHeader = true;
			$this->NLT = preg_match( ',\\|minLeiter=(\\d+),', $this->matches[1], $m ) ? $m[1] : '';
			$this->MinTN = preg_match( ',\\|minTeilnehmer=(\\d+),', $this->matches[1], $m ) ? $m[1] : '';
			$this->MaxTN = preg_match( ',\\|maxTeilnehmer=(\\d+),', $this->matches[1], $m ) ? $m[1] : '';
			$this->Gruppe_after = $this->matches[2];
		}


		$text = preg_replace( ',^\n*== \\[\\[bild:buch\\.png\\|<nowiki(?:/|></nowiki)>\\]\\] Inhalt ==\n,si', '', $text, 1, $count );
		$this->InhaltHeader = ( $count > 0 );
		
		$this->Kategorien = array();
		// TODO: This doesn't handle all cases (eg category in <nowiki>-tags)
		// TODO: Nicht alle Kategorien werden auch in der Auswahl angezeigt!
		foreach( explode( '|', 
			'6-8|9-12J|9-12M|13-15J|13-15M|15+|Basteln|Quiz|Spezialprogramm|Rollenspiel|Tipps_und_Material|Spiele_draussen|' .
			'Spiele_drinnen|Spielenachmittag|Stadtspiel|Kurzspiele|Kurzspiele_drinnen|Kurzspiele_draussen|' .
			'Gelaendespiel|Nachtspiel|Andachtsreihen|Geschichten|Lebensbilder|Eingescannte_Spiele' ) as $cat ) {
			$re = preg_replace( array( ',\\+,', ',_,' ), array( '\\+', '[_ ]' ), $cat );
			$text = preg_replace( ",\\[\\[Kategorie:$re\\]\\],", '', $text, 1, $count );
			if( $count > 0 )
				$this->Kategorien[] = $cat;
		}
				
		$text = preg_replace_callback( ',\\n== \[\[bild:comments\\.png\\|<nowiki(?:/|></nowiki)>\\]\\] Kommentare ==\\n(.*),si',
			array( $this, 'internalStoreMatches' ), $text, -1, $count );
		if( $count > 0 )
			$this->Kommentare = $this->matches[1];

		// TODO: Müssen wir uns merken, ob es einer oder mehrere Autoren waren? Oder machen wir das automatisch wie Spezial:Neues_Spiel?
		// Wir sind konservativ und matchen Autor nur, wenn es ganz am Ende des übriggebliebenen Textes steht
		$text = preg_replace_callback( ',\\n\\{\\{Autor(?:en)?\\|(.*?)\\}\\}\s*$,si',
			array( $this, 'internalStoreMatches' ), $text, -1, $count );
		if( $count > 0 )
			$this->Autor = $this->matches[1];
	
		$text = preg_replace_callback( ',\\n== \[\[bild:utilities\\.png\\|<nowiki(?:/|></nowiki)>\\]\\] Material ==\\n(.*),si',
			array( $this, 'internalStoreMatches' ), $text, -1, $count );
		if( $count > 0 )
			$this->Material = $this->matches[1];

		// was übrig bleibt ist Beschreibung
		$this->Beschreibung = $text;

		return true;
	}

	function createWikiText() {
		// siehe EditPage.php:getPreviewText()

		$autorzahl = count(explode(" ", $this->Autor));
		# "Thomas Bleher" ist ein Name, "Thomas und Leo" sind zwei
		$autor = ($autorzahl > 2) ? 'Autoren' : 'Autor';

		if (preg_match("/[^\\s*]/", $this->Material) > 0) { // Material besteht nicht nur aus Leerzeichen und Sternen...
			$material = wfMsg( 'neueseite-material-ueberschrift' ). "\n" . $this->Material;
		} else {
			$material = '';
		}

		$wpGame = '';
		if( $this->Kurzbeschreibung_before != '' ){
			// Zeilenenden scheinen mit Textareas irgendwie schwierig zu sein... Wir strippen alles am Ende und fügen dafür noch ein Newline ein
			$wpGame = rtrim( $this->Kurzbeschreibung_before ) . "\n";
		}
		$wpGame .=
"== [[bild:info.png|<nowiki/>]] Kurzbeschreibung ==
{{Kurzbeschreibung|{$this->Kurzbeschreibung}}}
";
		if( $this->Kurzbeschreibung_after != '' ){
			$wpGame .= rtrim( $this->Kurzbeschreibung_after ) . "\n";
		}

		$wpGame .= "\n{{Vorbereitungsaufwand|{$this->Aufwand}";
		if( $this->Aufwand_Text != '' )
			$wpGame .= '|'.$this->Aufwand_Text;
		$wpGame .= "}}{{Dauer|{$this->Dauer}";
		if( $this->Dauer_Text != '' )
			$wpGame .= '|'.$this->Dauer_Text;
		$wpGame .= "}}\n";
		if( $this->GruppenHeader || $this->NLT != '' || $this->MinTN != '' || $this->MaxTN != '' || $this->Gruppe_after != '' ){
			$wpGame .= "== [[bild:personen.png|<nowiki/>]] Gruppengrößen ==
{{Personenzahl|minLeiter={$this->NLT}|minTeilnehmer={$this->MinTN}|maxTeilnehmer={$this->MaxTN}}}
{$this->Gruppe_after}
";
		}
		if( $this->InhaltHeader ) {
			$wpGame .= "== [[bild:buch.png|<nowiki/>]] Inhalt ==\n";
		}
		$wpGame .= 
"{$this->Beschreibung}
{$material}
{{{$autor}|{$this->Autor}}}
";

		if (count($this->Kategorien)!= 0) {
			foreach ($this->Kategorien as $wpA) $wpGame .= "[[Kategorie:{$wpA}]]";
		}
		if ($this->Kommentare != ''){
			$wpGame .= "\n\n== [[bild:Comments.png|<nowiki/>]] Kommentare ==\n\n" . ltrim( $this->Kommentare );
		}
		return $wpGame;
	}
}
