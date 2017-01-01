<?php
include('init.php');
auth_redirect();

$message_id = @$_GET['message_id'];
$i = @$_GET['i'];

if ( !empty($message_id) && isset($i) ) {
	$msg = $db->get_row( $db->prepare("SELECT * FROM $db->messages WHERE message_id=%d", $message_id) );
	if ( $msg ) {
		include_once('includes/mime.php');
		$parts = mime_split($msg->content);
		$img = $parts[$i];
		if ( $img && $img->is_type('image') ) {
			header('Content-type: '. $img->content_type);
			die($img->content);
		}

	}
}

header('Status: 404 Not Found');
die('Not found.');