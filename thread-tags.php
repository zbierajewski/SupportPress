<?php
// Update the tags for a thread
include( 'init.php' );
auth_redirect();

$thread_id = (int) $_POST['thread_id'];
$thread = get_thread( $thread_id );

if ( !$thread ) {
	if ( isset( $_POST['is_ajax'] ) ) {
		echo json_encode( array(
			'is_error' => TRUE,
			'error_message' => 'Failed to lookup thread'
		) );
		exit;
	}

	die('Thread not found.');
}

update_tags( $thread_id, $_POST['tags'] );
if ( isset( $_POST['is_ajax'] ) ) {
	echo json_encode( array(
		'is_error' => FALSE
	) );
	exit;
}

header( 'Location: thread.php?updated=tags&t=' . $thread_id );