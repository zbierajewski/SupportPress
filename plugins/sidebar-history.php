<?php

if ( defined( 'ENABLE_SB_HISTORY' ) && ENABLE_SB_HISTORY  )
	add_action('menu-below', 'sidebar_history');

function sidebar_history() {
	if ( empty($_GET['t']) )
		return;

	global $db, $email_domain;

	// there might be more than one email address because some people reply from a different email
	// also, the user didn't necessarily create the ticket
	$emails = $db->get_col( $db->prepare("SELECT email FROM $db->messages WHERE thread_id = %d and from_user_id = 0", $_GET['t']) );
	$emails = array_unique( $emails );
	$emails = preg_grep( '/support.*@' . preg_quote( $email_domain ) . '$/i', $emails, PREG_GREP_INVERT );
	if ( empty( $emails ) )
		return;
	$out = '<ul class="history menu">';
	$out .= '<h4>History:</h4>';

	$done_threads = array();


	foreach ( $emails as $email ) {
		$other_threads = $db->get_results( $db->prepare(
			"SELECT thread_id,dt,state FROM $db->threads WHERE email = %s AND thread_id != %d", $email, $_GET['t']
		) );
		if ( $other_threads ) {
			$out .= '<li><a href="user.php?e='.urlencode($email).'">'.htmlspecialchars($email).'</a>';
			$out .= '<ul>';
			foreach ( $other_threads as $thread ) {
				if ( in_array( $thread->thread_id, $done_threads ) )
					continue;
				$out .= '<li>';
				if ( $thread->state == 'open' )
					$out .= '<a href="thread.php?t='.$thread->thread_id.'"><strong>'.short_time_diff($thread->dt).' ago</strong></a></li>';
				else
					$out .= '<a href="thread.php?t='.$thread->thread_id.'">'.short_time_diff($thread->dt).' ago</a></li>';
				$done_threads[] = $thread->thread_id;
			}
			$out .= '</ul>';
		}


		$out .= '</li>';
	}

	$out .= '</ul>';
	if ( !empty( $done_threads ) )
		echo $out;

}