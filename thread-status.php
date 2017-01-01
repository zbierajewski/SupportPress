<?php
// Update the status for a thread
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

$state = $_POST['status'];
$state = preg_replace( '|[^a-z]|', '', $state );
$db->update( $db->threads, compact( 'state' ), compact( 'thread_id' ) );

do_action( 'thread-status-update', $thread, $state );

ticket_redirect( $state, $thread_id, $current_user->onclose );