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

# TODO all das verschieben
#
$wgAutoloadClasses['SpecialNeueSeite'] = dirname( __FILE__ ) . '/SpecialNeueSeite_body.php';
$wgAutoloadClasses['SpieleParser'] = dirname( __FILE__ ) . '/SpieleParser.php';
$wgAutoloadClasses['SpecialSpielBearbeiten'] = dirname( __FILE__ ) . '/SpecialSpielBearbeiten_body.php';
$wgAutoloadClasses['SpecialSpielBearbeitenBase'] = dirname( __FILE__ ) . '/SpecialSpielBearbeitenBase_body.php';
$wgSpecialPages['NeueSeite'] = 'SpecialNeueSeite';
$wgSpecialPages['SpielBearbeiten'] = 'SpecialSpielBearbeiten';
$wgExtensionMessagesFiles['NeueSeite'] = dirname( __FILE__ ) . '/SpecialNeueSeite.i18n.php';
$wgExtensionMessagesFiles['SpielBearbeiten'] = dirname( __FILE__ ) . '/SpecialNeueSeite.i18n.php';
 
$wgHooks['AlternateEdit'][] = 'wfAddHelpOnEditPage';
function wfAddHelpOnEditPage( $editpage ) {
	global $wgHooks;
	$wgHooks['MonoBookTemplateToolboxEnd'][] = 'wfExtensionAddEditHelp';
	return true;
}

function wfExtensionAddEditHelp( $skin ){
	global $wgTitle;

	echo "\n</ul></div></div>\n";
	echo '<div class="portlet" id="toolbox_help">'."\n";
	echo '<div class="pBody"><b>Bearbeitungstipps:</b><br/>Du kannst den Text einfach mit 
		<a href="http://spiele.j-crew.de/wiki/SpieleWiki:Editierhilfe" target="_blank">Wikisyntax</a> formatieren. Zum Beispiel:<ul><li>\'\'kursiv\'\' =&gt; <i>kursiv</i></li><li>\'\'\'fett\'\'\' =&gt; <b>fett</b></li></ul> 
		<a href="http://spiele.j-crew.de/wiki/SpieleWiki:Editierhilfe" target="_blank">Mehr Infos</a><ul style="display:none">'."\n";
	return true;
}

function wfSpielBearbeitenAddLinksHook( $st, &$content_actions ) {
	global $egSPTitel;
	$content_actions['nstab-special']['text'] = 'Spiel bearbeiten';
	$content_actions['nstab-main'] = array( 'text' => 'Spiel anzeigen', 'href' => $egSPTitel );
	return true;
}
