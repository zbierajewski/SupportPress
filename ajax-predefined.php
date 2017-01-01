<?php

include('init.php');
auth_redirect();

include_once('includes/backpress/functions.shortcodes.php');

do_action( 'ajax-predefined' );

function shortcode_user($atts, $content, $tag) {
	@list($obj, $member) = explode('.', $tag, 2);
	if ( $obj == 'user' ) {
		global $the_user;
		if ( isset($the_user->$member) )
			return $the_user->$member;
		else
			return 'UNKNOWN';
	}
}

add_shortcode('user.first_name', 'shortcode_user');
add_shortcode('user.api_key', 'shortcode_user');

if ( $_REQUEST['id'] ) {
	$row = $db->get_row( $db->prepare("SELECT * FROM $db->predefined_messages WHERE id=%d", $_REQUEST['id']) );
	if ( $row ) {
		$message = $row->message;
		if ( !empty( $_REQUEST['user_id'] ) )
			$the_user = $db->get_row( $db->prepare("SELECT * FROM $db->users WHERE ID=%d", $_REQUEST['user_id']) );
		if ( !empty( $_REQUEST['thread_id'] ) )
			$the_thread_id = $_REQUEST['thread_id'];
		$message = do_shortcode( $message );
		die( json_encode( array(
			'message' => $message,
			'title'   => $row->name,
			'tag'     => $row->tag,
		) ) );
	}
}