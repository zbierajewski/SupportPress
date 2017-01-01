<?php

// miscellaneous helper functions

// return the time since the last reset - handy for crude profiling
function time_since( $reset = false ) {
	static $last = 0;

	$since = microtime(true) - $last;
	if ( $reset )
		$last = microtime(true);
	return $since;
}

// similar to human_time_diff(), only a bit fuzzier
function fuzzy_time_diff( $from, $to = null ) {

	if ( empty( $to ) ) {
		$to = strtotime( gmdate( 'Y-m-d H:i:s' ) );
	}

	$from = strtotime( $from );

	$diff = abs( $to - $from );

	if ( $diff < 60 )
		return "a few seconds ago";
	elseif ( $diff < 600 )
		return "a few minutes ago";
	elseif ( $diff < 3100 )
		return sprintf( "about %d minutes ago", round( ( $diff / 900 ), 0, PHP_ROUND_HALF_UP ) * 15 ); // round to 15 minutes
	elseif ( $diff < 5400 )
		return "about an hour ago";
	elseif ( $diff < 20 * 3600 )
		return sprintf( "%d hours ago", round( ( $diff / 3600 ), 0, PHP_ROUND_HALF_UP ) );
	elseif ( $diff < 48 * 3600 )
		return "about a day ago";
	elseif ( $diff < 604800 )
		return sprintf( "%d day%s ago", $s=round($diff / 86400), ($s==1) ? '' : 's' );
	elseif ( $diff < 2592000 )
		return sprintf( "%d week%s ago", $s=round($diff / 604800), ($s==1) ? '' : 's' );
	elseif ( $diff < 31536000 )
		return sprintf( "%d month%s ago", $s=round($diff / 2592000), ($s==1) ? '' : 's' );
	else
		return sprintf( "more than %d year%s ago", $s=round($diff / 31536000), ($s==1) ? '' : 's' );

}

function short_time_diff( $from, $to = null ) {

	if ( empty( $to ) ) {
		$to = strtotime( gmdate( 'Y-m-d H:i:s' ) );
	}

	$from = strtotime( $from );

	$diff = abs( $to - $from );

	if ( $diff < 60 )
		return sprintf("%ds", $diff);
	elseif ( $diff < 3600 )
		return sprintf("%dm", $diff / 60);
	elseif ( $diff < 86400 )
		return sprintf("%dh", $diff / 3600);
	else
		return sprintf("%dd", $diff / 86400);
}