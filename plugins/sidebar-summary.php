<?php

if ( defined( 'ENABLE_SB_SUMMARY' ) && ENABLE_SB_SUMMARY )
	add_action('menu-below', 'sidebar_summary');

function sidebar_summary() {

	if ( !empty($_GET['t']) )
		return;

	global $db;

	// using NOW() in a query defeats the MySQL query cache.  Fudge that slightly by using an approximate date for "24 hours ago"
	// with a 5 minute resolution - effectively a 5 minute query cache lifetime

	$now = strftime(MYSQL_STRFTIME_FORMAT, (floor( time() / 300 ) * 300));
	$since = strftime(MYSQL_STRFTIME_FORMAT, (floor( time() / 300 ) * 300) - 86400 );

	$today = $db->get_col( $db->prepare(
		"SELECT count(thread_id) FROM $db->threads WHERE dt >= %s AND state = 'closed' ".
		"UNION SELECT count(thread_id) FROM $db->threads WHERE dt >= %s AND state = 'open'",
		$since, $since
	) );
	$closed_today = $today[0];
	$opened_today = $today[1];

	echo '<ul class="sidebar-summary">';
	echo "<li>Opened today: ".number_format($opened_today)."</li>";
	echo "<li>Closed today: ".number_format($closed_today)."</li>";
	if ( $opened_today > 0 || $closed_today > 0 ) {
		$slip = $closed_today - $opened_today;
		if ( $slip > 0 )
			echo '<li>Gain: <span class="good">'.number_format($slip).'</span></li>';
		elseif ( $slip < 0 )
			echo '<li>Slip: <span class="bad">'.number_format($slip).'</span></li>';
	}

	echo '</ul>';

	echo '<ul class="sidebar-summary">';

	$open = $db->get_results(
		"SELECT COUNT(thread_id) AS num, 0 as mm FROM $db->threads WHERE state = 'open' AND messages < 2 ".
		"UNION SELECT COUNT(thread_id) AS num ,1 as mm FROM $db->threads WHERE state = 'open' AND messages > 1"
	);

	$open_new = $open[0]->num;
	$open_answered = $open[1]->num;
	$open_total = $open_answered + $open_new;

	echo '<li>Open: '.number_format( $open_total ).'</li>';
	echo '<li>New: '.number_format( $open_new ).'</li>';
	echo '<li>In progress: '.number_format( $open_answered ).'</li>';

	echo '</ul>';
	$tags = array();
	$thread_ids = $db->get_col( "SELECT `thread_id` FROM $db->threads WHERE `state`='open'" );
	if ( !empty( $thread_ids ) )
		$tag_slugs = $db->get_results( "SELECT `tag_slug` FROM $db->tags WHERE `thread_id` IN(" . implode( ',', $thread_ids ) . ")" );
	if ( !empty( $tag_slugs ) ) {
		foreach( $tag_slugs as $row ) {
			$slug = $row->tag_slug;
			if ( empty( $tags[$slug] ) )
				$tags[$slug] = 0;
			$tags[$slug]++;
		}
		arsort( $tags, SORT_NUMERIC );
	}
	if ( !empty( $tags ) ) {
		echo '<ul class="sidebar-summary">';
		echo '<li>By tag:</li>';
		echo '<ul>';
		foreach ( $tags as $slug => $count )
			echo '<li><a href="index.php?tag='.htmlspecialchars($slug).'">' . htmlspecialchars($slug) . '</a>: '. $count . '</li>';
		echo '</ul>';
		echo '</li>';
		echo '</ul>';
	}
}