<?php

namespace MediaWiki\Extension\SimpleCalendar;

use Parser;
use Title;

class Setup {

	/**
	 * @param Parser $parser
	 * @return void
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'calendar', __CLASS__ . '::render' );
	}

	/**
	 * Expands the "calendar" magic word to a table of all the individual month tables
	 *
	 * @param Parser $parser
	 * @return array
	 */
	public static function render( $parser ) {
		$parser->getOutput()->updateCacheExpiry( 0 );
		$parser->getOutput()->addModules( [ 'ext.simplecalendar' ] );

		// Retrieve args
		$argv = [];
		foreach ( func_get_args() as $arg ) {
			if ( !is_object( $arg ) ) {
				if ( preg_match( '/^(.+?)\s*=\s*(.+)$/', $arg, $match ) ) {
					$argv[$match[1]] = $match[2];
				}
			}
		}

		// Set options to defaults or specified values
		$f  =
			$argv['format'] ??
			( strtoupper( substr( PHP_OS, 0, 3 ) ) == 'WIN' ? 'j F Y' : 'j F Y' );
		$df = $argv['dayformat'] ?? false;
		$p  = isset( $argv['title'] ) ? $argv['title'] . '/' : '';
		$q  = isset( $argv['query'] ) ? $argv['query'] . '&action=edit' : 'action=edit';
		$y  = $argv['year'] ?? date( 'Y' );

		// If a month is specified, return only that month's table
		if ( isset( $argv['month'] ) ) {
			$m = $argv['month'];
			$table = self::renderMonth( date( 'm', strtotime( "$y-$m-01" ) ), $y, $p, $q, $f, $df );
		}

		// Otherwise start month at 1 and build the main container table
		// phpcs:ignore MediaWiki.ControlStructures.IfElseStructure.SpaceBeforeElse
		else {
			$m = 1;
			$table = "<table class=\"calendar\"><tr>";
			for ( $rows = 3; $rows--; $table .= "</tr><tr>" ) {
				for ( $cols = 0; $cols < 4; $cols++ ) {
					$table .= "<td>\n" . self::renderMonth( $m++, $y, $p, $q, $f, $df ) . "\n</td>";
				}
			}
			$table .= "</tr></table>\n";
		}

		return [ $table, 'isHTML' => true, 'noparse' => true ];
	}

	/**
	 * Return a calendar table of the passed month and year
	 *
	 * @return string
	 */
	private static function renderMonth( $m, $y, $prefix, $query, $format, $dayformat ) {
		$thisDay = date( 'd' );
		$thisMonth = date( 'n' );
		$thisYear = date( 'Y' );
		$d = date( 'w', $ts = mktime( 0, 0, 0, $m, 1, $y ) );
		if ( empty( $d ) ) {
			$d = 7;
		}
		$month = wfMessage( strtolower( date( 'F', $ts ) ) )->text();
		$days = [];
		foreach ( [ 'M', 'T', 'W', 'T', 'F', 'S', 'S' ] as $i => $day ) {
			$days[] = $dayformat ? wfMessage( date( $dayformat, mktime( 0, 0, 0, 2, $i, 2000 ) ) )->text() : $day;
		}
		$table = "\n<table border class=\"month\">\n\t<tr class=\"heading\"><th colspan=\"7\">$month</th></tr>\n";
		$table .= "\t<tr class=\"dow\"><th>" . implode( '</th><th>', $days ) . "</th></tr>\n";
		$table .= "\t<tr>\n";
		if ( $d > 0 ) {
			$table .= "\t\t" . str_repeat( "<td>&nbsp;</td>", $d - 1 ) . "\n";
		}
		for ( $i = $day = $d; $day < 32; $i++ ) {
			$day = $i - $d + 1;
			if ( $day < 29 || checkdate( $m, $day, $y ) ) {
				if ( $i % 7 == 1 ) {
					$table .= "\n\t</tr>\n\t<tr>\n";
				}
				$t = ( $day == $thisDay && $m == $thisMonth && $y == $thisYear ) ? '  today' : '';
				$ttext = $prefix . trim( date( $format, mktime( 0, 0, 0, $m, $day, $y ) ) );
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
		$last = date( "t", $ts );
		if ( empty( $d ) ) {
			$last = 31;
		}
		$iClose = 0;
		while ( ( $d + $last + $iClose ) % 7 !== 1 ) {
			$iClose++;
			$table .= "\t\t<td>&nbsp;</td>\n";
		}
		$table .= "\n\t</tr>\n</table>";
		return $table;
	}
}
