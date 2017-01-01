#!/usr/local/bin/php -d display_errors=true
<?php

define( 'SP_DIR', dirname( dirname(__FILE__) ) );
require_once( SP_DIR . '/base-init.php' );

$wp_users_object = new WP_Users( $db );



// get a list of user nicknames
$user_names = array();
$user_ids = get_support_user_ids();
foreach ($user_ids as $user_id ) {
	$user = get_user($user_id);
	$message = null;
	$done = array();

	// a list of tickets tagged with this user's nickname
	$tickets = $db->get_results( $db->prepare("SELECT * FROM $db->threads INNER JOIN $db->tags USING(thread_id) WHERE state='open' AND tag_slug = %s", $user->user_login) );

	foreach ( $tickets as $ticket ) {
		$first = $db->get_row( $db->prepare("SELECT * FROM $db->messages WHERE thread_id = %d ORDER BY message_id ASC LIMIT 1", $ticket->thread_id) );
		$last = $db->get_row( $db->prepare("SELECT * FROM $db->messages WHERE thread_id = %d AND message_type='support' ORDER BY message_id DESC LIMIT 1", $ticket->thread_id) );
		$message .= "{$ticket->subject}\n";
		$message .= "ticket from $ticket->email opened " . short_time_diff( $first->dt )." ago\n";
		$message .= "last reply was $last->email " . short_time_diff( $last->dt )." ago\n";
		$message .= get_thread_url( $ticket->thread_id ) . "\n";
		$message .= "\n\n";

		$done[] = $ticket->thread_id;

	}

	// now a list of threads that user has replied to
	$tickets = $db->get_col( $db->prepare("SELECT distinct {$db->threads}.thread_id FROM $db->threads INNER JOIN $db->messages USING(thread_id) WHERE state='open' AND message_type='support' AND from_user_id = %d", $user_id) );
	foreach ( $tickets as $thread_id ) {
		if ( in_array($thread_id, $done) )
			continue;
		$ticket = $db->get_row( $db->prepare("SELECT * FROM $db->threads WHERE thread_id = %d", $thread_id) );
		$first = $db->get_row( $db->prepare("SELECT * FROM $db->messages WHERE thread_id = %d ORDER BY message_id ASC LIMIT 1", $ticket->thread_id) );
		$last = $db->get_row( $db->prepare("SELECT * FROM $db->messages WHERE thread_id = %d AND message_type='support' ORDER BY message_id DESC LIMIT 1", $ticket->thread_id) );
		if ( $last->from_user_id == $user_id )
			continue;

		$message .= "{$ticket->subject}\n";
		$message .= "ticket from $ticket->email opened " . short_time_diff( $first->dt )." ago\n";
		$message .= "last reply was $last->email " . short_time_diff( $last->dt )." ago\n";
		$message .= get_thread_url( $ticket->thread_id ) . "\n";
		$message .= "\n\n";
	}


	if ( $message )
		wp_mail( $user->user_email, 'SupportPress reminder', "The following tickets tagged for your attention are still open:\n\n$message",  "From: SupportPress <$support_email>" );

}