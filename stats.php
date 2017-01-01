<?php
include_once( 'init.php' );

// Is this an admin only page?
if ( !$current_user->has_cap('supportpressadmin') ) {
	wp_redirect( $site_url );
	exit;
}

include( 'header.php' );

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



	$open = $db->get_results(
		"SELECT COUNT(thread_id) AS num, 0 as mm FROM $db->threads WHERE state = 'open' AND messages < 2 ".
		"UNION SELECT COUNT(thread_id) AS num ,1 as mm FROM $db->threads WHERE state = 'open' AND messages > 1"
	);

	$open_new = $open[0]->num;
	$open_answered = $open[1]->num;
	$open_total = $open_answered + $open_new;

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
	?>
<div id="statchart">
    <div id="statchart-box">
        <h3>Totals</h3>
        <div id="stat-chart-container">
        	<div id="stats-nuggets">
                <ul>
                    <li><div><span><?php echo number_format( (int) $opened_today); ?></span> opened today</div></li>
                    <li><div><span><?php echo number_format( (int) $open_total); ?></span> currently open</div></li>
                    <li><div><span><?php echo number_format( (int) $open_new); ?></span> new threads</div></li>
                    <li><div class="last"><span><?php echo number_format( (int) $open_answered); ?></span> threads with replies</div></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div id="statchart">
    <div id="statchart-box">
        <h3>Open tags</h3>
        <div id="stat-chart-container">
        	<?php
        	if ( !empty( $tags ) ) {
				echo '<ul>';
				foreach ( $tags as $slug => $count )
					echo '<li><a href="index.php?tag='.htmlspecialchars($slug).'">' . htmlspecialchars($slug) . '</a> ('. $count . ')</li>';
				echo '</ul>';
			}?>
        </div>
    </div>
</div>

<? include( 'footer.php' );