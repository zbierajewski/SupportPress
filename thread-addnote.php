<?php

include( 'init.php' );

$user_ids = array_filter( (array)$_POST['notify'] );
$note = @$_POST['note'];
$thread_id = @intval($_POST['thread_id']);

$thread = get_thread( $thread_id );

if ( !$thread )
	die('Thread not found.');

$dt = gmdate( 'Y-m-d H:i:s' );

$hash = md5( $dt . $thread->email . $note );

$name = $current_user->display_name;

$message = preg_replace("/\n\n+/", "\n\n", $note); // take care of duplicates

$message .= "\n\n-- \n$name";


if ( $tags ) {
	$current_tags = get_thread_tags( $thread_id );

	foreach ( $user_tags as $tag ) {
		$tag = trim( strtolower($tag) );
		if ( $tag = sanitize_title( $tag ) ) {
			if ( !in_array( $tag, $current_tags ) )
				$db->insert( $db->tags, array( 'thread_id' => $thread_id, 'tag_slug' => $tag ) );
		}
	}
}

$db->insert( $db->messages, array( 'hash' => $hash, 'thread_id' => $thread_id, 'dt' => $dt, 'email' => "support+$current_user->user_login@{$email_domain}", 'content' => $message, 'message_type' => 'note' ) );

update_message_count( $thread_id );

if ( isset( $_POST['sendclose'] ) ) {
	$db->update( $db->threads, array( 'state' => 'closed' ), array( 'thread_id' => $thread_id ) );
	$thread->status = 'closed';
}

ticket_redirect( $thread->status, $thread_id, $current_user->onclose );