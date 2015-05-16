<?php
/**
 * MediaWiki SimpleCalendar Extension
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @licence GNU General Public Licence 2.0 or later
 */

define( 'SIMPLECALENDAR_VERSION','1.2.5, 2015-05-16' );

$wgExtensionCredits['parserhook'][] = array(
	'name'        => 'Simple Calendar',
	'author'      => '[http://www.organicdesign.co.nz/aran Aran Dunkley]',
	'description' => 'A simple calendar extension',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:Simple_Calendar',
	'version'     => SIMPLECALENDAR_VERSION
);

class SimpleCalendar {

	function __construct() {
		global $wgExtensionFunctions;
		$wgExtensionFunctions[]	= 'SimpleCalendar::setup';
		Hooks::register( 'LanguageGetMagic', $this );
	}

	public function onLanguageGetMagic( &$magicWords, $langCode = 0 ) {
		$magicWords['calendar'] = array( 0,'calendar' );
		return true;
	}

	public static function setup() {
		global $wgParser;
		$wgParser->setFunctionHook( 'calendar', 'SimpleCalendar::render' );
		return true;
	}

	/**
	 * Expands the "calendar" magic word to a table of all the individual month tables
	 */
	public static function render( $parser ) {
		$parser->mOutput->mCacheTime = -1;
		$argv = array();
		foreach( func_get_args() as $arg ) if( !is_object( $arg ) ) {
			if( preg_match( '/^(.+?)\\s*=\\s*(.+)$/', $arg, $match ) ) $argv[$match[1]] = $match[2];
		}
		if( isset( $argv['format'] ) )    $f = $argv['format']; else $f = '%e %B %Y';
		if( isset( $argv['dayformat'] ) ) $df = $argv['dayformat']; else $df = false;
		if( isset( $argv['title'] ) )     $p = $argv['title'] . '/'; else $p = '';
		if( isset( $argv['query'] ) )     $q = $argv['query'] . '&action=edit'; else $q = 'action=edit';
		if( isset( $argv['year'] ) )      $y = $argv['year']; else $y = date( 'Y' );
		if( isset( $argv['month'] ) ) {
			$m = $argv['month'];
			return self::renderMonth( strftime( '%m', strtotime( "$y-$m-01" ) ), $y, $p, $q, $f, $df );
		} else $m = 1;
		$table = "<table class=\"calendar\"><tr>\n";
		for( $rows = 3; $rows--; $table .= "</tr>\n<tr>" )
			for( $cols = 0; $cols < 4; $cols++ )
				$table .= '<td>' . self::renderMonth( $m++, $y, $p, $q, $f, $df ) . "</td>\n";
		$table .= "\n</tr></table>\n";
		return array( $table, 'isHTML' => true, 'noparse' => true );
	}

	/**
	 * Return a calendar table of the passed month and year
	 */
	static function renderMonth( $m, $y, $prefix = '', $query = '', $format = '%e %B %Y', $dayformat = false ) {
		$thisDay   = date( 'd' );
		$thisMonth = date( 'n' );
		$thisYear  = date( 'Y' );
		if( !$d = date( 'w', $ts = mktime( 0, 0, 0, $m, 1, $y ) ) ) $d = 7;
		$month = wfMessage( strtolower( strftime( '%B', $ts ) ) )->text();
		$days = array();
		foreach( array( 'M', 'T', 'W', 'T', 'F', 'S', 'S' ) as $i => $day )
			$days[] = $dayformat ? wfMessage( strftime( $dayformat, mktime( 0, 0, 0, 2, $i, 2000 ) ) )->text() : $day;
		$table = "\n<table border class=\"month\"\n<tr class=\"heading\"><th colspan=\"7\">$month</td></tr>\n";
		$table .= '<tr class="dow"><th>' . implode( '</th><th>', $days ) . "</th></tr>";
		$table .= "</tr>\n";
		if( $d > 1 ) $table .= str_repeat( "<td>&nbsp;</td>", $d - 1 );
		for( $i = $day = $d; $day < 32; $i++ ) {
			$day = $i - $d + 1;
			if( $day < 29 or checkdate( $m, $day, $y ) ) {
				if( $i % 7 == 1 ) $table .= "\n</tr><tr>\n";
				$t = ( $day == $thisDay && $m == $thisMonth && $y == $thisYear ) ? '  today' : '';
				$ttext = $prefix . trim( strftime( $format, mktime( 0, 0, 0, $m, $day, $y ) ) );
				$title = Title::newFromText( $ttext );
				if( is_object( $title ) ) {
					$class = $title->exists() ? 'day-active' : 'day-empty';
					$url = $title->getLocalURL( $title->exists() ? '' : $query );
				} else $url = $ttext;
				$table .= "<td class='$class$t'><a href=\"$url\">$day</a></td>\n";
			}
		}
		$table .= "\n</table>";
		return $table;
	}
}

new SimpleCalendar();
