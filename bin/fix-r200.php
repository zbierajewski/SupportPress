<?php

require_once dirname( __FILE__ ) . '/../base-init.php';

$thread_id = 0;
do {
	$thread_ids = $wpdb->get_col( $wpdb->prepare( "SELECT thread_id FROM $db->threads WHERE messages > 1 AND thread_id > %s LIMIT 50", $thread_id ) );
	foreach( $thread_ids as $thread_id ) {
		echo $thread_id.chr(10);
		$wpdb->query( $wpdb->prepare(
			"UPDATE $db->threads SET dt = ( SELECT dt FROM $db->messages WHERE thread_id = $db->threads.thread_id ORDER BY message_id DESC LIMIT 1 ) WHERE thread_id = %d LIMIT 1",
			$thread_id
		) );
	}
} while( count( $thread_ids ) == 50 );