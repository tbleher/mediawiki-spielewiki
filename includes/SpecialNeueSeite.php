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
use MediaWiki\Request\FauxRequest;

class SpecialNeueSeite extends SpecialSpielBearbeitenBase {
	public function __construct() {
		parent::__construct( 'NeueSeite', 'createpage' );
		$this->sp->GruppenHeader = true;
		$this->sp->InhaltHeader = true;
	}

	public function execute( $par ) {
		global $wgRequest;
		$this->initPars( $par );
		$this->setHeaders();
		
		if ( $this->userCanExecute( $this->getUser() ) ) {
			$wgRequest->setVal('action', 'edit');
			$this->readValues();
			$this->output( true );
		} else {
			$this->displayRestrictionError();
		}
	}

	function initPars( $par ) {
		global $wgRequest;
		// initialize vars
		$this->Preview = $wgRequest->wasPosted();
		$par = preg_replace( '/_/', ' ', $par );
		$this->Spielname = isset( $par ) ? $par : '';
		$this->sp->Beschreibung = $this->msg( 'neueseite-anfangsbeschreibung' )->plain();
		if( $this->getUser()->isRegistered() ) {
			$this->sp->Autor = $this->getUser()->getRealName();
			if ($this->sp->Autor == '')
				$this->sp->Autor = $this->getUser()->getName();
		}
	}

	function trySave() {
		global $wgOut, $wgRequest;
		wfDebug( "Trying to save new page (Spezial:Neues Spiel)\n");
		$this->Titel = $this->createTitleObj( $this->Spielname );
		if ( is_null( $this->Titel ) )
			return false;

		$text = $this->sp->createWikiText();
		$req = new FauxRequest(array(
			'action'     => 'edit',
			'title'      => $this->Titel->getPrefixedText(),
			'text'       => $text,
			'createonly' => 1,
		), true);
		$req->setVal( 'token', $this->getUser()->getEditToken( '', $req ) );
		#$req->setVal( 'token', $this->getUser()->getEditToken() );
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
		$data = $api->getResult()->getResultData();
		return $data;
	}
}

