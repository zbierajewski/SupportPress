<?php
include('init.php');
auth_redirect();


if ( isset( $_GET['id'] ) && (int) $_GET['id'] > 0 ) {
	$message_id = (int) $_GET['id'];
}
else {
	die( 'Missing message ID' );
}

$att = $db->get_row( $db->prepare( "SELECT * FROM $db->attachments WHERE message_id = %d", $message_id ) );

if ( isset( $att ) ) {
	
	$ext = end( explode( '.', $att->filename ) );
	$filename = $att->filename;	
	$content = $att->file_content;
	
	if ( in_array( $ext, array( 'jpg', 'jpeg', 'gif', 'png' ) ) ) {
		header( "Content-type: image/$ext" );
	}
	else {
		header( "Content-type: $ext" );
	}

	//header( "Content-Disposition: attachment; filename=$filename" );
	print $content;
	
}
else {
	die( 'Attachment not found' );
}











?>