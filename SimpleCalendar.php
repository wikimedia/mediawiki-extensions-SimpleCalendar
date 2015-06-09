<?php
/**
 * MediaWiki SimpleCalendar Extension
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @licence GNU General Public Licence 2.0 or later
 */

define( 'SIMPLECALENDAR_VERSION', '1.2.8, 2015-06-06' );

$wgExtensionCredits['parserhook'][] = array(
	'name' => 'Simple Calendar',
	'author' => '[http://www.organicdesign.co.nz/aran Aran Dunkley]',
	'description' => 'A simple calendar extension',
	'url' => 'http://www.mediawiki.org/wiki/Extension:Simple_Calendar',
	'version' => SIMPLECALENDAR_VERSION
);

class SimpleCalendar {

	function __construct() {
		global $wgExtensionFunctions;
		$wgExtensionFunctions[] = array( $this, 'setup' );
		Hooks::register( 'LanguageGetMagic', $this );
	}

	public function onLanguageGetMagic( &$magicWords, $langCode = 0 ) {
		$magicWords['calendar'] = array( 0, 'calendar' );
		return true;
	}

	public function setup() {
		global $wgParser;
		$wgParser->setFunctionHook( 'calendar', array( $this, 'render' ) );
		return true;
	}

	/**
	 * Expands the "calendar" magic word to a table of all the individual month tables
	 */
	public function render( $parser ) {
		$parser->disableCache();

		// Retrieve args
		$argv = array();
		foreach ( func_get_args() as $arg ) {
			if ( !is_object( $arg ) ) {
				if ( preg_match( '/^(.+?)\s*=\s*(.+)$/', $arg, $match ) ) {
					$argv[$match[1]] = $match[2];
				}
			}
		}

		// Set options to defaults or specified values
		$f  = isset( $argv['format'] )    ? $argv['format']                 : '%e %B %Y';
		$df = isset( $argv['dayformat'] ) ? $argv['dayformat']              : false;
		$p  = isset( $argv['title'] )     ? $argv['title'] . '/'            : '';
		$q  = isset( $argv['query'] )     ? $argv['query'] . '&action=edit' : 'action=edit';
		$y  = isset( $argv['year'] )      ? $argv['year']                   : date( 'Y' );

		// If a month is specified, return only that month's table
		if ( isset( $argv['month'] ) ) {
			$m = $argv['month'];
			$table = $this->renderMonth( strftime( '%m', strtotime( "$y-$m-01" ) ), $y, $p, $q, $f, $df );
		}

		// Otherwise start month at 1 and build the main container table
		else {
			$m = 1;
			$table = "<table class=\"calendar\"><tr>";
			for ( $rows = 3; $rows--; $table .= "</tr><tr>" ) {
				for ( $cols = 0; $cols < 4; $cols++ ) {
					$table .= "<td>\n" . $this->renderMonth( $m++ , $y, $p, $q, $f, $df ) . "\n</td>";
				}
			}
			$table .= "</tr></table>\n";
		}

		return array( $table, 'isHTML' => true, 'noparse' => true );
	}

	/**
	 * Return a calendar table of the passed month and year
	 */
	private function renderMonth( $m, $y, $prefix = '', $query = '', $format = '%e %B %Y', $dayformat = false ) {
		$thisDay = date( 'd' );
		$thisMonth = date( 'n' );
		$thisYear = date( 'Y' );
		if ( !$d = date( 'w', $ts = mktime( 0, 0, 0, $m, 1, $y ) ) ) {
			$d = 7;
		}
		$month = wfMessage( strtolower( strftime( '%B', $ts ) ) )->text();
		$days = array();
		foreach ( array( 'M', 'T', 'W', 'T', 'F', 'S', 'S' ) as $i => $day ) {
			$days[] = $dayformat ? wfMessage( strftime( $dayformat, mktime( 0, 0, 0, 2, $i, 2000 ) ) )->text() : $day;
		}
		$table = "\n<table border class=\"month\">\n\t<tr class=\"heading\"><th colspan=\"7\">$month</th></tr>\n";
		$table .= "\t<tr class=\"dow\"><th>" . implode( '</th><th>', $days ) . "</th></tr>\n";
		$table .= "\t<tr>\n";
		if ( $d > 1 ) {
			$table .= "\t\t" . str_repeat( "<td>&nbsp;</td>", $d - 1 ) . "\n";
		}
		for ( $i = $day = $d; $day < 32; $i++ ) {
			$day = $i - $d + 1;
			if ( $day < 29 or checkdate( $m, $day, $y ) ) {
				if ( $i % 7 == 1 ) {
					$table .= "\n\t</tr>\n\t<tr>\n";
				}
				$t = ( $day == $thisDay && $m == $thisMonth && $y == $thisYear ) ? '  today' : '';
				$ttext = $prefix . trim( strftime( $format, mktime( 0, 0, 0, $m, $day, $y ) ) );
				$title = Title::newFromText( $ttext );
				if ( is_object( $title ) ) {
					$class = $title->exists() ? 'day-active' : 'day-empty';
					$url = $title->getLocalURL( $title->exists() ? '' : $query );
				} else {
					$url = "Bad title: \"$ttext\"";
				}
				$table .= "\t\t<td class='$class$t'><a href=\"$url\">$day</a></td>\n";
			}
		}
		$table .= "\n\t</tr>\n</table>";
		return $table;
	}
}

new SimpleCalendar();
