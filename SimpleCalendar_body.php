<?php
class SimpleCalendar {

	/**
	 * Called when the extension is first loaded
	 */
	public static function onLoad() {
		global $wgExtensionFunctions;
		$wgExtensionFunctions[] = __CLASS__ . '::setup';
	}

	public static function setup() {
		global $wgParser, $wgOut, $wgResourceModules, $wgExtensionAssetsPath, $IP, $wgAutoloadClasses;

		// Register the parser-function
		$wgParser->setFunctionHook( 'calendar', __CLASS__ . '::render' );

		// This gets the remote path even if it's a symlink (MW1.25+)
		$path = $wgExtensionAssetsPath . str_replace( "$IP/extensions", '', dirname( $wgAutoloadClasses[__CLASS__] ) );
		$wgResourceModules['ext.simplecalendar']['remoteExtPath'] = $path;
		$wgOut->addModules( 'ext.simplecalendar' );
	}

	/**
	 * Expands the "calendar" magic word to a table of all the individual month tables
	 */
	public static function render( $parser ) {
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
		$f  = isset( $argv['format'] )    ? $argv['format']                 : ( strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' ? '%#d %B %Y' : '%e %B %Y' );
		$df = isset( $argv['dayformat'] ) ? $argv['dayformat']              : false;
		$p  = isset( $argv['title'] )     ? $argv['title'] . '/'            : '';
		$q  = isset( $argv['query'] )     ? $argv['query'] . '&action=edit' : 'action=edit';
		$y  = isset( $argv['year'] )      ? $argv['year']                   : date( 'Y' );

		// If a month is specified, return only that month's table
		if ( isset( $argv['month'] ) ) {
			$m = $argv['month'];
			$table = self::renderMonth( strftime( '%m', strtotime( "$y-$m-01" ) ), $y, $p, $q, $f, $df );
		}

		// Otherwise start month at 1 and build the main container table
		else {
			$m = 1;
			$table = "<table class=\"calendar\"><tr>";
			for ( $rows = 3; $rows--; $table .= "</tr><tr>" ) {
				for ( $cols = 0; $cols < 4; $cols++ ) {
					$table .= "<td>\n" . self::renderMonth( $m++ , $y, $p, $q, $f, $df ) . "\n</td>";
				}
			}
			$table .= "</tr></table>\n";
		}

		return array( $table, 'isHTML' => true, 'noparse' => true );
	}

	/**
	 * Return a calendar table of the passed month and year
	 */
	private static function renderMonth( $m, $y, $prefix, $query, $format, $dayformat ) {
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
					$url = "Bad title: \"$ttext\" (using format \"$format\")";
				}
				$table .= "\t\t<td class='$class$t'><a href=\"$url\">$day</a></td>\n";
			}
		}
		$table .= "\n\t</tr>\n</table>";
		return $table;
	}
}
