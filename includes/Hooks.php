<?php

namespace MediaWiki\Extension\NeueSeite;

use Article;
use SkinTemplate;
use Title;

class Hooks {
	/**
	 * Changes the Edit tab to call our Special page
	 *
	 * This is attached to the MediaWiki 'SkinTemplateNavigation::Universal' hook.
	 *
	 * @param SkinTemplate $skin The skin template on which the UI is built.
	 * @param array &$links Navigation links.
	 */
	public static function onSkinTemplateNavigation( SkinTemplate $skin, array &$links ) {
		$context = $skin->getContext();
		$title = $context->getTitle();
		if( array_key_exists( 'edit', $links['views'] ) && $title->isContentPage() ) {
			$article = Article::newFromTitle( $title, $context );
		       	if( $article->isCurrent() ){
				if( $title->exists() ) {
					$links['views']['edit']['href'] = Title::newFromText( 'Spezial:Spiel_bearbeiten/'. $title->getPrefixedDBkey() )->getLocalUrl();
				} else {
					$links['views']['edit']['href'] = Title::newFromText( 'Spezial:Neues_Spiel/'. $title->getPrefixedDBkey() )->getLocalUrl();
				}
			}
		}
		return true;
	}
}

