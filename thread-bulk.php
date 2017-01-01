<?php

// Update the tags for a thread

include( 'init.php' );

$thread_ids = array_filter($_POST['thread_ids']);

if ( $_POST['status-close'] ) {
	switch( $_POST['status-close'] ) {
		case 'Close':
			$state = "closed";
			break;
		case 'Tickle':
			$state = "tickle";
			break;
		case 'Spam':
			$state = "spam";
			break;
		default:
			$state = "open";
			break;
	}

	foreach ( $thread_ids as $thread_id ) {
		$r = $db->update( $db->threads, compact( 'state' ), compact( 'thread_id' ) );
	}
}

if ( isset( $_POST['from'] ) )
	header( 'Location: ' . $_POST['from'] );
else
	header( 'Location: ' . $_SERVER['HTTP_REFERER'] );