<?php

include( 'init.php' );


$thread_id = (int) $_POST['thread_id'];

$thread = get_thread( $thread_id );

if ( !$thread )
	die('Thread not found.');

$reply = $_POST['message_reply'];

$dt = gmdate( 'Y-m-d H:i:s' );

$hash = md5( $dt . $thread->email . $reply );

$reply_db = addslashes( $reply );

$email = "support+{$current_user->user_login}@{$email_domain}";

$to_email = @trim($_POST['to_email']);
$cc = @trim($_POST['cc']);
$bcc =  @trim($_POST['bcc']);


$db->insert( $db->messages, array( 'hash' => $hash, 'thread_id' => $thread_id, 'dt' => $dt, 'email' => $email, 'email_to' => $to_email, 'from_user_id' => $current_user->ID, 'content' => $reply ) );

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

$tags = @trim($_POST['tags']);
if ( $tags ) {
	add_tags( $thread_id, $tags );
}

$count = $db->get_var( $db->prepare("SELECT COUNT(*) FROM $db->messages WHERE thread_id = %s", $thread_id) );
$db->update( $db->threads, array( 'messages' => $count, 'state' => $status, 'dt' => $dt ), array( 'thread_id' => $thread_id ) );

$name = "$current_user->first_name $current_user->last_name";
if ( !trim($name) )
	$name = $current_user->user_login;

$reply = str_replace(array("\r\n", "\r"), "\n", $reply); // cross-platform newlines
$reply = preg_replace("/\n\n+/", "\n\n", $reply); // take care of duplicates

if ( !defined( 'DISABLE_AUTO_SIGNATURE' ) || !DISABLE_AUTO_SIGNATURE )
	$reply .= "\n\n-- \n$name";

$reply_to_id = intval( $db->get_var( $db->prepare(
	"SELECT `message_id` FROM $db->messages WHERE `thread_id`=%d AND `message_type`='support' AND `email` LIKE 'support+%%@$email_domain' ORDER BY `message_id` ASC LIMIT 1", $thread_id
) ) );
$mail_message_id = "Message-ID: <$thread->hash.$thread_id.$db->insert_id@$email_domain>";
$mail_references = "References: <$thread->hash@$email_domain>";
$mail_inreplyto  = "In-Reply-To: <$thread->hash.$thread_id.$reply_to_id@$email_domain>";

wp_mail( $to_email, 'Re: ' . $thread->subject, $reply,  "From: $name <$support_email>\nReply-to: $support_email\n$mail_message_id\n$mail_references\n$mail_inreplyto\ncc: $cc\nbcc:$bcc", $outgoing_attachment );

do_action( 'user_thread_reply', $thread_id, $thread->email );

ticket_redirect( $status, $thread_id, $current_user->onclose );