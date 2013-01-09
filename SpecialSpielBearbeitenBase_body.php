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

abstract class SpecialSpielBearbeitenBase extends SpecialPage {
	protected $Titel;

	public function __construct( $pagename, $restriction ) {
		$this->sp = new SpieleParser();
		parent::__construct( $pagename, $restriction );
	}

	/* Tests if the parameter passed is a valid title, and returns it. On invalid titles, NULL is returned.
	 */
	function createTitleObj( $par ) {
		if( is_null( $par ) ) {
			return NULL;
		}
		$title = Title::newFromURL( $par );
		if( is_null( $title ) || $title->getNamespace() == NS_SPECIAL )
			return NULL;
		return $title;
	}

	function readValues() {
		global $wgRequest;
		if ( $wgRequest->wasPosted() ) {
			$sp = $this->sp;
			$this->Spielname             = $wgRequest->getText( 'wpPageName',    $this->Spielname );
			$sp->Kurzbeschreibung        = $wgRequest->getText( 'wpSummary',     $sp->Kurzbeschreibung );
			$sp->Kurzbeschreibung_before = $wgRequest->getText( 'wpKurzbeschreibungBefore', $sp->Kurzbeschreibung_before );
			$sp->Kurzbeschreibung_after  = $wgRequest->getText( 'wpKurzbeschreibungAfter',  $sp->Kurzbeschreibung_after );
			$sp->Beschreibung            = $wgRequest->getText( 'wpTextbox1',    $sp->Beschreibung );
			$sp->Dauer_Text              = $wgRequest->getText( 'wpDauerText',   $sp->Dauer_Text );
			$sp->Aufwand_Text            = $wgRequest->getText( 'wpAufwandText', $sp->Aufwand_Text );
			$sp->Material                = $wgRequest->getText( 'wpMaterial',    $sp->Material );
			$sp->Kommentare              = $wgRequest->getText( 'wpKommentare',  $sp->Kommentare );
			$sp->Autor                   = $wgRequest->getText( 'wpName',        $sp->Autor );
			$sp->Gruppe_after            = $wgRequest->getText( 'wpGruppeAfter', $sp->Gruppe_after );
			$this->EditSummary           = $wgRequest->getText( 'wpEditSummary', $this->EditSummary );
			$sp->Kategorien              = $wgRequest->getArray( 'wpOptions',    $sp->Kategorien );
			$sp->NLT                     = $wgRequest->getIntOrNull( 'wpNLT' );
			$sp->MinTN                   = $wgRequest->getIntOrNull( 'wpMinTN' );
			$sp->MaxTN                   = $wgRequest->getIntOrNull( 'wpMaxTN' );
			$sp->Aufwand                 = $wgRequest->getVal( 'wpVorbereitungsaufwand' );
			$sp->InhaltHeader            = $wgRequest->getVal( 'wpInhaltHeader' );
			$sp->GruppenHeader           = $wgRequest->getVal( 'wpGruppenHeader' );
			$sp->Dauer                   = $wgRequest->getIntOrNull( 'wpDauer' );
			$this->Preview               = $wgRequest->getCheck( 'wpPreview' );
			$this->Save                  = $wgRequest->getCheck( 'wpSave' );
			$this->Diff                  = $wgRequest->getCheck( 'wpDiff' );
		}
		$this->PreviewReason = '';
	}

	// Hilfsfunktion - ist eine Kategorie selektiert?
	function catsel($cat) {
		return Xml::check( 'wpOptions[]', in_array($cat, $this->sp->Kategorien), array( 'value' => $cat ) ) 
		. "<a href='/wiki/Kategorie:$cat'>";
	}

	/* expects fully escaped values */
	private function tableRow( $col1, $col2 = NULL ) {
		static $color = 2;
		if ( ++$color == 3 ) $color = 1;
		if( is_null( $col2 ) )
			return "<tr class='row{$color}'><td colspan='2'>{$col1}</td></tr>\n";
		return "<tr class='row{$color}'><td valign='top'>{$col1}</td><td>{$col2}</td></tr>\n";
	}

	/* Takes a file name from the wiki and returns a link to a thumbnail (16x16);
	 * Filename must be valid!
	 */
	private function thumblink( $file ){
		$title = Title::makeTitle( NS_IMAGE, $file );
		if( !is_object( $title ) ) {
			return "<span class='error'>ERROR in __METHOD__!\n</span>"; // This shouldn't happen
		}
		$image = wfFindFile( $title );
		if( isset( $image ) && is_object( $image ) && $image->exists() ) {
			$thumbnail = $image->transform( array( 'width' => 16 ) );
                	return $thumbnail->toHtml( array( 'alt' => '', 'file-link' => false, 'valign' => 'top' ) ) . ' ';
		}
		return "<span class='error'>Couldn't find file ".htmlspecialchars($file)."!\n</span>";
	}

	// wird in abgeleiteten Klassen überschrieben
	function generateDiff(){
		return '';
	}
	
	function output( $newpage ) {
		global $wgOut, $wgUser, $wgEnableJS2system;
		$sp = $this->sp;

		// Hack, damit in der erweiterten Toolbar der Usability-Initiative die richtigen Icons angezeigt werden
		$wgOut->includeJQuery();
		$wgOut->addScript( "<script type='text/javascript'>jQuery(document).ready(function(){jQuery( 'body' ).addClass('ns-subject').addClass('ns-0');});</script>");

		if( $this->Save ){
			try {
				// dirty hack for ConfirmEdit
				define('MW_API', true);
				$data = $this->trySave();
				if( $data !== false ) {
					if ($data['edit']['result'] !== 'Failure') {
						#wfDebug(print_r($data, true));
						wfDebug("Successfully saved page\n");
						$wgOut->redirect( $this->Titel->getFullURL() );
						return;
					}

					if ($data['edit']['spamblacklist'] != '') {
						$spamurl = $data['edit']['spamblacklist'];
						$this->PreviewReason = "Die Seite enthaelt die Spam-Url ``{$spamurl}''";
					} else if ($data['edit']['captcha']['type'] != '') {
						$this->PreviewReason = 'NeedsCaptcha';
					} else {
						wfDebug(print_r($data, true));
						$this->PreviewReason = "Unbekannter Fehler";
					}
				}
			} catch (UsageException $e) {
				wfDebug("Caught UsageException from API save: $e\n");
				$this->PreviewReason = $e->getMessage();

			} catch (Exception $e) {
				wfDebug("Caught exception from API save: $e\n");
				$this->PreviewReason = $e->getMessage();
			}
			$this->Preview = true;
		}

		global $wgHooks;
		$wgHooks['MonoBookTemplateToolboxEnd'][] = 'wfExtensionAddEditHelp';

		if( !$newpage && $this->Diff ){ // diff gibts nur bei !$newpage
			$wgOut->addHTML( $this->generateDiff() );
		}

		if( $newpage ){
			$wgOut->setPageTitle(wfMsg( 'neueseite-titel' ) . ($this->Spielname != '' ? ' - '.$this->Spielname : ''));
		} else {
			$wgOut->setPageTitle( wfMsg( 'editing', $this->Titel->getPrefixedText() ) );
		}
		$postURL = htmlspecialchars( SpecialPage::getTitleFor( $this->mName, $newpage ? NULL : $this->Spielname )->getFullURL() );
		$wgOut->addHTML( "<form id='editform' name='editform' method='post' action=\"$postURL\" enctype='multipart/form-data'>" );

		// TODO: preview-reason muss noch angepasst werden	
		if ( $this->Preview ) {
			global $wgTitle;
			if ( is_null($wgTitle) ) {
				// this should not happen, but does anyway if ApiMain throws an exception
				$wgTitle = $this->getTitle();
			}
			$previewhead = '<h2>' . htmlspecialchars( wfMsg( 'preview' ) ) . "</h2>\n";
			if ($this->PreviewReason != '') {
				if( $this->PreviewReason === 'NeedsCaptcha' ) {
					ConfirmEditHooks::getInstance()->editCallback( $wgOut );
				} else {
					$previewhead .= "<div class='previewnote'>";
					$previewhead .= "<p>Die Seite konnte nicht gespeichert werden! Grund: " 
						     . htmlspecialchars($this->PreviewReason);

					$previewhead .= "</p><p><strong><a href='".htmlspecialchars(Title::makeTitle(NS_PROJECT, "Feedback")->getLocalURL())."'>Bitte schreib kurz, falls etwas nicht richtig funktioniert!</a></strong></p>";
					$previewhead .= "</div>\n";
				}
			} else {
				$previewhead .= "<div class='previewnote'>";
				$previewhead .= $wgOut->parse( wfMsg( ($this->Save ? 'articleexists' : 'previewnote') ) );
				$previewhead .= "</div>\n";
			}
			$wgOut->parserOptions()->setEditSection( false );
			$wgOut->addHTML( "<div id='wikiPreview'>$previewhead\n" );
			$wgOut->addWikiText( $sp->createWikiText() );
			$wgOut->addHTML( "<br style='clear:both;' />\n</div><hr />\n" );
		}

		$toolbar = $wgUser->getOption('showtoolbar') ? EditPage::getEditToolbar() : '';
		//$wgOut->addScriptFile( 'edit.js' );
		$wgOut->addModules("ext.wikiEditor.toolbar");
		$wgOut->addModules("ext.wikiEditor.dialogs");

		$wgOut->addWikiText( wfMsg( $newpage ? 'neueseite-einleitung' : 'spielbearbeiten-einleitung' ) );
		$out = <<<HERE
			<table border="0" cellpadding="5" cellspacing="0">
			<tbody>
HERE;
		if( $newpage ) {
			$out .= $this->tableRow( $this->thumblink('Info.png') . 'Name des Spiels:', 
				Xml::input( 'wpPageName', 60, $this->Spielname, array( 'maxlength' => 200, 'style' => 'width: 100%;' ) ) );
		}

		$out .= $this->tableRow( ($newpage ? '' : $this->thumblink('Info.png')) .'Zusammenfassung:', 
			Xml::input( 'wpSummary', 60, $sp->Kurzbeschreibung, array( 'maxlength' => 230, 'style' => 'width: 100%;') ) .
			'<br /><small>Die Zusammenfassung taucht in der Spieleliste auf und soll dem Leser zusammen ' .
			'mit dem Titel einen ersten Eindruck geben, worum es im Spiel geht. <b>Unbedingt ausf&uuml;llen!</b></small>' );

		if( !$newpage ){
			$out .= $this->tableRow( 'Einleitungstext:',
				Xml::tags( 'textarea', array( 'id' => 'wpKurzbeschreibungBefore', 
					'name' => 'wpKurzbeschreibungBefore', 'rows' => 2, 'cols' => 80 ),
                                htmlspecialchars( $sp->Kurzbeschreibung_before ) ) . 
				'<small>Der Text in diesem Feld wird vor dem Inhaltsverzeichnis angezeigt. 
					<em>Dieses Feld sollte normalerweise leer bleiben</em>.</small>' );
			$out .= $this->tableRow( 'Zusätzliche Beschreibung:',
				Xml::tags( 'textarea', array( 'id' => 'wpKurzbeschreibungAfter', 
					'name' => 'wpKurzbeschreibungAfter', 'rows' => 2, 'cols' => 80 ),
                                htmlspecialchars( $sp->Kurzbeschreibung_after ) ) . 
				'<small>Der Text in diesem Feld wird nach der Kurzbeschreibung angezeigt. 
					<em>Dieses Feld sollte normalerweise leer bleiben</em>.</small>' );
		}

		$out .= $this->tableRow( $this->thumblink('Gruppe.png') . 'Gruppengr&ouml;&szlig;e:', 
			'Mindestens '. Xml::input( 'wpMinTN', 3, $sp->MinTN ) . ' und h&ouml;chstens ' .
			Xml::input( 'wpMaxTN', 3, $sp->MaxTN ) . ' Teilnehmer' .
			'<br /><small>Im Zweifelsfall ein oder beide Felder leer lassen ' .
			' (falls man das Spiel mit beliebig kleinen bzw. gro&szlig;en Gruppen spielen kann)</small>' );

		$out .= $this->tableRow( 'Mindestanzahl Leiter:', 
			Xml::input( 'wpNLT', 3, $sp->NLT ) );

		$out .= $this->tableRow( 'Zusätzliche Infos zur Gruppengröße:', 
			Xml::tags( 'textarea', array( 'id' => 'wpGruppeAfter', 'name' => 'wpGruppeAfter', 'rows' => 2, cols => '80' ), 
				htmlspecialchars( $sp->Gruppe_after ) ) . "<!-- Dieses Feld kann meistens leer bleiben. -->" );

		$out .= $this->tableRow( $this->thumblink('Clock.png') . 'Vorbereitungsaufwand:',
			Xml::tags( 'select', array( 'size' => 1, 'name' => 'wpVorbereitungsaufwand' ),
				Xml::option( 'Unbekannt',          -1, $sp->Aufwand == -1 ) .
				Xml::option( 'Keiner',              0, $sp->Aufwand == 0 ) .
				Xml::option( 'Gering (< 15 min)',   1, $sp->Aufwand == 1 ) .
				Xml::option( 'Normal (< 1 Stunde)', 2, $sp->Aufwand == 2 ) .
				Xml::option( 'Hoch (> 1 Stunde)',   3, $sp->Aufwand == 3 ) ) );

		$out .= $this->tableRow( 'Zusätzliche Infos zum Vorbereitungsaufwand:',
				Xml::input( 'wpAufwandText', 60, $sp->Aufwand_Text, array( 'style' => 'width: 100%;' ) ) .
				'<br /><small>Feld kann leer bleiben</small>' );

		$out .= $this->tableRow( 'Dauer des Spiels:', Xml::input( 'wpDauer', 3, $sp->Dauer ) .
			' min. <small>(Bitte nur eine Zahl eingeben, die der typischen Spieldauer grob entspricht; ansonsten leer lassen)</small>' );

		$out .= $this->tableRow( 'Zusätzliche Infos zur Dauer:',
				Xml::input( 'wpDauerText', 60, $sp->Dauer_Text, array( 'style' => 'width: 100%;' ) ) .
				'<br /><small>Feld kann leer bleiben</small>' );

		$out .= $this->tableRow( 'Autor:', Xml::input( 'wpName', 30, $sp->Autor  ) );

		$out .= $this->tableRow( $this->thumblink('Buch.png') . 
			'Beschreibung: <br /><small>(Hilfe: <a href="http://spiele.j-crew.de/wiki/SpieleWiki:Editierhilfe"' .
			' target="_blank">Wie formatiere ich den Text in diesem Feld?</a>)</small><br />' . $toolbar . 
			Xml::tags( 'textarea', array( 'id' => 'wpTextbox1', 'name' => 'wpTextbox1', 'rows' => 25, 'cols' => 80 ), 
				htmlspecialchars( $sp->Beschreibung ) ) );

		$out .= $this->tableRow( $this->thumblink('Utilities.png') . 'Material:<br />' .
			Xml::tags( 'textarea', array( 'name' => 'wpMaterial', 'rows' => 10, 'cols' => 40 ), 
				htmlspecialchars( $sp->Material ) ) );

		if( $sp->Kommentare != '' )
			$out .= $this->tableRow( $this->thumblink('Comments.png') . 'Kommentare:<br />' .
				Xml::tags( 'textarea', array( 'name' => 'wpKommentare', 'rows' => 10, 'cols' => 40 ), 
					htmlspecialchars( $sp->Kommentare ) ) );

		$out .= $this->tableRow( 'Geeignet f&uuml;r:',
			$this->catsel("6-8").   '6-8   j&auml;hrige Kinder</a><br />' .
			$this->catsel("9-12M"). '9-12  j&auml;hrige M&auml;dchen</a><br />' .
			$this->catsel("9-12J"). '9-12  j&auml;hrige Jungs</a><br />' .
			$this->catsel("13-15M").'13-15 j&auml;hrige M&auml;dchen</a><br />' .
			$this->catsel("13-15J").'13-15 j&auml;hrige Jungs</a><br />' .
			$this->catsel("15+").   '&Auml;ltere Jugendliche</a>' );

		$out .= $this->tableRow( 'Art des Spiels:', 
			'<ul style="padding: 0px; list-style-type: none;">'.
			'<li><a href="/wiki/Kategorie:Spiele_drinnen">'.$this->thumblink('Mini-Spiele drinnen.png').'Spiel im Gruppenraum</a>
			    <ul>
			      <li>'.$this->catsel("Spielenachmittag").'Spielenachmittag</a></li>
			      <li>'.$this->catsel("Quiz").            'Quiz</a></li>
			      <li>'.$this->catsel("Spezialprogramm"). 'Spezialprogramm</a></li>
			      <li>'.$this->catsel("Rollenspiel").     'Rollenspiel</a></li>
			      <li>'.$this->catsel("Kurzspiele_drinnen"). 'Kurzspiel drinnen</a></li>
			      <li>'.$this->catsel("Spiele_drinnen").  'anderes Spiel drinnen</a> (weder Spielenachmittag noch Quiz)</li>
			    </ul>
			  </li>
			  <li><a href="/wiki/Kategorie:Spiele_draussen">'.$this->thumblink('Mini-Spiele draussen.png').'Spiele drau&szlig;en</a>
			    <ul>
			      <li>'.$this->catsel("Gelaendespiel").  'Gel&auml;ndespiel</a></li>
			      <li>'.$this->catsel("Stadtspiel").     'Stadtspiel</a></li>
			      <li>'.$this->catsel("Nachtspiel").     'Nachtspiel</a></li>
			      <li>'.$this->catsel("Kurzspiele_draussen"). 'Kurzspiel draußen</a></li>
			      <li>'.$this->catsel("Spiele_draussen").'anderes Spiel drau&szlig;en</a> (weder Stadt- noch Gel&auml;ndespiel)</li>
			    </ul>
			  </li>
			  <li>'.$this->catsel("Eingescannte_Spiele").'Eingescannte Spiele</a></li>
			  <li>Anderes (kein Spiel)
			    <ul>
			      <li>'.$this->catsel("Basteln").$this->thumblink('Mini-Basteln.png').'Basteln</a></li>
			      <li>'.$this->catsel("Geschichten").$this->thumblink('Mini-Geschichten.png').'Geschichten</a></li>
			      <li>'.$this->catsel("Lebensbilder").$this->thumblink('Mini-Lebensbilder.png').'Lebensbilder</a></li>
			      <li>'.$this->catsel("Andachtsreihen").$this->thumblink('Mini-Andachtsreihen.png').'Andachtsreihen</a></li>
			      <li>'.$this->catsel("Tipps_und_Material").$this->thumblink('Mini-Tipps und Material.png').'Tipps und Material</a></li>
			    </ul></li>
			</ul>' );
		$out .= '</tbody></table><hr />' .
			Html::hidden( 'wpGruppenHeader', $sp->GruppenHeader ) . Html::hidden( 'wpInhaltHeader', $sp->InhaltHeader );

		if( !$newpage ){
			$out .= '<p>Bearbeitungskommentar (optional): ' . 
				Xml::input( 'wpEditSummary', 60, $this->EditSummary, array( 'maxlength' => 230, 'style' => 'width: 100%;') ) . 
				'<br /><small>(Hier kannst Du eine Zusammenfassung deiner Änderungen an der Seite angeben; 
				dieser Kommentar wird in der <a href="'.htmlspecialchars($this->Titel->getLocalURL('action=history')).'" target="_blank"
				>Versionsgeschichte</a> gespeichert)</small></p>';
		}
		$out .= '<div class="editButtons">';
		# von EditPage::getEditButtons
		$buttons = array();
                $temp = array(
			'id'        => 'wpSave',
			'name'      => 'wpSave',
			'type'      => 'submit',
		#	'tabindex'  => ++$tabindex,
			'value'     => 'Spiel speichern', # wfMsg('savearticle'),
			'accesskey' => wfMsg('accesskey-save'),
			'title'     => ($newpage ? 'Neues Spiel erstellen' : wfMsg( 'tooltip-save' ) ).' ['.wfMsg( 'accesskey-save' ).']',
                );
		$buttons['save'] = Xml::element('input', $temp, '');
		$temp = array(
			'id'        => 'wpPreview',
			'name'      => 'wpPreview',
			'type'      => 'submit',
		#	'tabindex'  => $tabindex,
			'value'     => wfMsg('showpreview'),
			'accesskey' => wfMsg('accesskey-preview'),
			'title'     => wfMsg( 'tooltip-preview' ).' ['.wfMsg( 'accesskey-preview' ).']',
		);
		$buttons['preview'] = Xml::element('input', $temp, '');

		if( !$newpage ){
			$temp = array(
				'id'        => 'wpDiff',
				'name'      => 'wpDiff',
				'type'      => 'submit',
			#	'tabindex'  => ++$tabindex,
				'value'     => wfMsg('showdiff'),
				'accesskey' => wfMsg('accesskey-diff'),
				'title'     => wfMsg( 'tooltip-diff' ).' ['.wfMsg( 'accesskey-diff' ).']',
			);
			$buttons['diff'] = Xml::element('input', $temp, '');
		}

		$tabindex = 0;
		# WARNING: This will break if any extension needs $editpage in this hook!
		wfRunHooks( 'EditPageBeforeEditButtons', array( NULL, &$buttons, &$tabindex ) );
		$out .= implode( $buttons, "\n" );
		$out .= Html::hidden( "wpEditToken", $wgUser->getEditToken() );

		$sk = $wgUser->getSkin();
		$canceltitle = $newpage ? Title::newMainPage() : $this->Titel;
		$cancel = Linker::linkKnown( $canceltitle,
                                wfMsgExt('cancel', array('parseinline')) );
		$edithelpurl = Skin::makeInternalOrExternalUrl( wfMsgForContent( 'edithelppage' ));
		$edithelp = '<a target="helpwindow" href="'.$edithelpurl.'">'.
			htmlspecialchars( wfMsg( 'edithelp' ) ).'</a> '.
			htmlspecialchars( wfMsg( 'newwindow' ) );
		$normaledit = $newpage ? '' : ' | <a href="'.htmlspecialchars($this->Titel->getLocalURL('action=edit')).'">Spiel als Ganzes bearbeiten (ohne Formular)</a>';
        	$out .= " <span class='editHelp'>{$cancel} | {$edithelp}{$normaledit}</span></div></form>";

		$out .= "<p><a href='".htmlspecialchars(Title::makeTitle(NS_PROJECT, "Feedback")->getLocalURL())."'>Bitte Feedback falls etwas nicht richtig funktioniert!</a></p>";

		$wgOut->addHTML( $out );
	}
}
