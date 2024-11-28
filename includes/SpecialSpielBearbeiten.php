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

namespace MediaWiki\Extension\NeueSeite;

use ApiMain;
use MediaWiki\MediaWikiServices;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use ContentHandler;
use DifferenceEngine;

/*
TODO
	Handling, falls man alte Versionen einer Seite bearbeitet!
	Bug: Andi v. Casimir als "Autoren" erkannt; besseres Handling???
	Gruppenheader? Wenn es ihn nicht gibt kann man zwar Werte eingeben, es wird aber keiner eingefügt...
*/

class SpecialSpielBearbeiten extends SpecialSpielBearbeitenBase {
	public function __construct() {
		parent::__construct( 'SpielBearbeiten', 'edit' );
	}

	public function execute( $par ) {
		global $wgRequest, $wgOut;
		$this->Titel = $this->createTitleObj( $par );
		if( is_null( $this->Titel ) ) {
			$wgOut->addHTML("Nothing to see here, move along!");
			return;
		}
		if( !$this->Titel->exists() ) {
			$t = Title::newFromText( 'Spezial:Neues_Spiel/'.$this->Titel->getPrefixedDBkey() );
			$wgOut->redirect( $t->getFullURL() );
			return;
		}
		$permManager = MediaWikiServices::getInstance()->getPermissionManager();
		if( !$permManager->userCan('edit', $this->getUser(), $this->Titel) ) {
			// Wenn der Benutzer die Seite nicht bearbeiten kann schicken wir ihn zur normalen Edit-Seite;
			// dort bekommt er den Grund + den Quelltext angezeigt
			$wgOut->redirect( $this->Titel->getEditURL() );
			return;
		}
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
			return;
		}

		$this->Preview = $wgRequest->wasPosted();
		$wgRequest->setVal('action', 'edit');
		$this->Spielname = $this->Titel->getPrefixedText();
		if ( $wgRequest->wasPosted() ) {
			$this->readValues();
		} else {
			$revisionLookup = MediaWikiServices::getInstance()->getRevisionLookup();
			$rev = $revisionLookup->getRevisionByTitle( $this->Titel );
			$content = $rev->getContent(SlotRecord::MAIN, RevisionRecord::FOR_THIS_USER, $this->getUser());
			$text = ContentHandler::getContentText($content);
			$rv = $this->sp->parsePage( $text );
			if( !$rv ) {
				$wgOut->redirect( $this->Titel->getEditURL() );
				return;
			}
		}
		global $wgHooks, $egSPTitel;
		$egSPTitel = $this->Titel->getLocalURL();
		$wgHooks['SkinTemplateBuildContentActionUrlsAfterSpecialPage'][] = 'wfSpielBearbeitenAddLinksHook';
		$this->setHeaders();
		$this->output( false );

	}

	function generateDiff(){
// TODO: Möglicherweise könnte man die auch vereinfachen...
		$newtext = $this->sp->createWikiText();
//                $newtext = $this->mArticle->preSaveTransform( $newtext );
		$de = new DifferenceEngine( $this->Titel );
		if( is_null( $this->sp->OriginalText ) ) {
			$revisionLookup = MediaWikiServices::getInstance()->getRevisionLookup();
			$rev = $revisionLookup->getRevisionByTitle( $this->Titel );
			$content = $rev->getContent(SlotRecord::MAIN, RevisionRecord::FOR_THIS_USER, $this->getUser());
			$this->sp->OriginalText = ContentHandler::getContentText($content);
		}
		$oldContent = ContentHandler::makeContent( $this->sp->OriginalText, $this->Titel );
		$newContent = ContentHandler::makeContent( $newtext, $this->Titel );
		$de->setContent( $oldContent, $newContent );
		$oldtitle = wfMessage( 'currentrev' )->parse();
		$newtitle = wfMessage( 'yourtext' )->parse();
		$difftext = $de->getDiff( $oldtitle, $newtitle );
		$de->showDiffStyle();

                return '<div id="wikiDiff">' . $difftext . '</div>';
	}

	function trySave() {
		global $wgOut, $wgRequest;
		if ( !$this->Titel->exists() )
			return false;
		$text = $this->sp->createWikiText();

		$req = new FauxRequest(array(
			'action'  => 'edit',
			'title'   => $this->Titel->getPrefixedText(),
			'text'    => $text,
			'summary' => $this->EditSummary
		), true);
		$req->setVal( 'token', $this->getUser()->getEditToken( '', $req ) );
		#$req->setVal( 'token', $wgUser->getEditToken() );
		$captchaid = $wgRequest->getVal( 'recaptcha_challenge_field', $wgRequest->getVal( 'wpCaptchaId' ) );
                $req->setVal( 'wpCaptchaId', $captchid );
                $req->setVal( 'captchaid', $captchid );
		$captchaword = $wgRequest->getVal( 'recaptcha_response_field', $wgRequest->getVal( 'wpCaptchaWord' ) ); 
                $req->setVal( 'wpCaptchaWord', $captchaword );
                $req->setVal( 'captchaword', $captchaword );
		wfDebug("captchaid=$captchaid, captchaword=$captchaword\n");

		$api = new ApiMain($req, true);
		$api->execute();
		wfDebug("Completed API-Save\n");
		// we only reach this point if Api doesn't throw an exception
		return $api->getResult()->getResultData();
	}
}
