<?php

include( 'init.php' );

$user_ids = array_filter( (array)$_POST['notify'] );
$notify_message = @$_POST['notify-message'];
$thread_id = @intval($_POST['thread_id']);

$thread = get_thread( $thread_id );

if ( !$thread )
	die('Thread not found.');

$dt = gmdate( 'Y-m-d H:i:s' );

$hash = md5( $dt . $thread->email . $notify_message );

$to_users = array();
$user_tags = array();
if ( $user_ids  ) {
	foreach ( $user_ids as $user_id ) {
		$user = get_user($user_id);
		if ( $user ) {
			$to_users[] = $user->display_name.' <'.$user->user_email.'>';
			$user_tags[] = $user->user_login;
		}
	}
}

if ( empty($to_users) )
	die('No recipients');

$name = $current_user->display_name;

$message = preg_replace("/\n\n+/", "\n\n", $notify_message); // take care of duplicates
$to_email = join(', ', $to_users);
$thread_url = get_thread_url($thread_id);

if ( $thread_url )
	$message .= "\n\nPlease see this SupportPress thread:\n\n$thread_url";

$message .= "\n\n-- \n$name";

wp_mail( $to_email, 'SupportPress notification', $message,  "From: $name <$support_email>\nReply-to: $name <$support_email>\nMessage-ID: <{$hash}.$thread_id@{$email_domain}>\nReferences: <$hash@{$email_domain}>\nIn-Reply-To: <$hash@{$email_domain}>");

if ( $user_tags ) {
	$current_tags = get_thread_tags( $thread_id );

	foreach ( $user_tags as $tag ) {
		$tag = trim( strtolower($tag) );
		if ( $tag = sanitize_title( $tag ) ) {
			if ( !in_array( $tag, $current_tags ) )
				$db->insert( $db->tags, array( 'thread_id' => $thread_id, 'tag_slug' => $tag ) );
		}
	}
}


$r = $db->insert( $db->messages, array( 'hash' => $hash, 'thread_id' => $thread_id, 'dt' => $dt, 'email' => "support+$current_user->user_login@{$email_domain}", 'content' => "Note sent to {$to_email}:\n\n{$message}", 'message_type' => 'note' ) );
if ( !$r ) {
	var_dump($db->last_query, $db->last_error); die;
}

if ( 'closed' == $thread->status )
	header( 'Location: ' . $_POST['from'] );
else
	header( 'Location: thread.php?t=' . $thread_id );