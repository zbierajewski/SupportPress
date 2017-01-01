<?php

include( 'init.php' );

$tags = @trim($_POST['tags']);
$to_email = @trim($_POST['to_email']);
$to_name = @trim($_POST['to_name']);
$subject = @trim($_POST['subject']);
$message = @trim($_POST['message']);
$cc = @trim($_POST['cc']);
$bcc =  @trim($_POST['bcc']);

$dt = gmdate( 'Y-m-d H:i:s' );

$hash = md5( $dt . $to_email . $message );

$db->insert( $db->threads, array( 'hash' => $hash, 'dt' => $dt, 'email' => $to_email, 'subject' => $subject ) );

$thread_id = $db->insert_id;

$email = "support+$current_user->user_login@{$email_domain}";

$db->insert( $db->messages, array( 'hash' => $hash, 'thread_id' => $thread_id, 'dt' => $dt, 'email' => $email, 'email_to' => $to_email, 'content' => $message ) );

$message_id = $db->insert_id;

if ( mb_strlen( $cc ) > 0 ) {
	$db->insert( $db->messagemeta, array( 'message_id' => $message_id, 'meta_key' => 'cc', 'meta_value' => $cc ) );
}

if ( mb_strlen( $bcc ) > 0 ) {
	$db->insert( $db->messagemeta, array( 'message_id' => $message_id, 'meta_key' => 'bcc', 'meta_value' => $bcc ) );
}

$outgoing_attachment = null;

if ( isset( $_FILES["attachment"] ) ) {
	$ext = end( explode( '.', $_FILES['attachment']['name'] ) );
	if( in_array( strtolower( $ext ), array( 'gif', 'pdf', 'jpg', 'jpeg', 'png', 'xls', 'doc', 'html', 'zip' ) ) ) {
		$filename = $_FILES['attachment']['name'];
		$tmp_filename  = $_FILES['attachment']['tmp_name'];
		$fp = fopen( $tmp_filename, 'r' );
		$file_content = fread( $fp, filesize( $tmp_filename ) );
		fclose( $fp );
		$db->insert( $db->attachments, array( 'message_id' => $message_id, 'filename' => $filename, 'file_content' => $file_content ) );
		$outgoing_attachment = array( array( 'file' => $tmp_filename, 'name' => $filename ) );
	}
}

// Status stuff
$status = 'open';
if ( isset( $_POST['sendtickle'] ) )
	$status = 'tickle';
if ( isset( $_POST['sendclose'] ) )
	$status = 'closed';

if ( $tags )
	add_tags( $thread_id, $tags );

$count = $db->get_var( $db->prepare("SELECT COUNT(*) FROM $db->messages WHERE thread_id = %s", $thread_id) );

$db->update( $db->threads, array( 'state' => $status, 'messages' => $count ), array( 'thread_id' => $thread_id ) );

$name = "$current_user->first_name $current_user->last_name";

$reply = str_replace(array("\r\n", "\r"), "\n", $reply); // cross-platform newlines
$reply = preg_replace("/\n\n+/", "\n\n", $reply); // take care of duplicates

$reply .= "\n\n-- \n$name";

$headers = "From: $name <$support_email>\nReply-to: $name <$support_email>\nMessage-ID: <$hash@{$email_domain}>\ncc: $cc\nbcc:$bcc";

wp_mail( $to_email, $subject, $message, $headers, $outgoing_attachment); 

if ( 'closed' == $status )
	header( 'Location: ' . $site_url );
else
	header( 'Location: thread.php?t=' . $thread_id );