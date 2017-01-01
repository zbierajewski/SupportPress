<?php

function get_next_open_thread_id() {
	global $db;
	$results = $db->get_var( "SELECT thread_id FROM $db->threads WHERE state='open' ORDER BY RAND() LIMIT 1" );

	return isset( $results ) ? $results : "";
}

function get_support_user_ids() {
	global $db;

	$user_ids = wp_cache_get( 'support_user_ids', 'supportpress' );
	if ( $user_ids !== false )
		return $user_ids;

	$user_ids = $db->get_col( $db->prepare("SELECT user_id FROM {$db->usermeta} WHERE meta_key=%s", "{$db->prefix}capabilities") );
	wp_cache_set( 'support_user_ids', $user_ids, 'supportpress', 900 ); // Cache for upto 15 minutes as we never delete this
	return $user_ids;
}

function ticket_redirect( $status, $thread_id, $onclose ) {

	global $site_url;

	$thread_id = (int) $thread_id;
	$redirect_url = add_query_arg( array( 'updated' => 'status', 't' => $thread_id ), $site_url . '/thread.php' );

	if ( 'nextticket' == $onclose ) {

		$next_thread_id = apply_filters( 'ticket_redirect_get_next_open_thread_id', get_next_open_thread_id() );

		if ( ! empty( $next_thread_id ) )
			$redirect_url = add_query_arg( array( 't' => $next_thread_id ), $site_url . '/thread.php' );

	}

	if ( isset( $_POST['from'] ) && 'closed' != $status )
		$redirect_url = wp_get_referer();

	if ( isset( $_POST['is_ajax'] ) ) {
		echo json_encode( array(
			'is_error' => false,
			'redirect_url' => $redirect_url
		) );
		exit;
	}

	wp_redirect( esc_url( $redirect_url, null, 'wp_redirect' ) );
	exit;

}

function update_message_count( $thread_id ) {
	global $db;

	$count = $db->get_var( $db->prepare("SELECT count(*) FROM $db->messages WHERE thread_id = %d", $thread_id) );
	$db->update( $db->threads, array( 'messages' => $count ), array( 'thread_id' => $thread_id ) );
}

// return an absolute URL for viewing the given thread.
// this doesn't verify that $thread_id exists, merely that it is a number.
function get_thread_url( $thread_id ) {
	global $site_url;

	if ( $id = intval($thread_id) )
		return $site_url . '/thread.php?t=' . $id;

	return false;
}