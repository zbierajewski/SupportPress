<?php

// This shows a sidebar box with near-real-time info about which users are currently viewing a thread.

// It also serves as an example of how to use the heartbeat system.

add_action('menu-below', 'show_thread_status');

function show_thread_status() {

	// only do this on thread pages
	if ( empty( $_GET['t'] ) )
		return;

	// this plugin requires memcache to pass messages
	if ( empty( $GLOBALS['wp_object_cache']->mc ) )
		return;

	// A container for displaying messages.  Note the sp-heartbeat class; that's required to receive the jQuery heartbeat event.
	echo <<<EOF
<h4>In this thread:</h4>
<ul class="sp-heartbeat" id="show-thread-status">
</ul>
EOF;

	// Now we use bind() on our container to receive heartbeat pings.
	echo <<<EOF
<script type="text/javascript">
$('#show-thread-status').bind('heartbeat', function(e, a) {
	console.log(a);
	var out = '';

	if ( a && 'thread_status' in a ) {
		for ( var key in a.thread_status ) {
			var activity = a.thread_status[ key ];
			var message = '';
			if ( activity == 'reply' )
				message = key + ' is replying';
			else if ( activity == 'read' )
				message = key + ' is reading';
			else
				message = key + ' is idle';
			out += ('<li class="thread-status-' + activity + '">' + message + '</li>');
		}
	} else {
		out = 'Not logged in!';
	}

	$('#show-thread-status').html( out );
});
</script>
EOF;
}

add_filter( 'heartbeat_response', 'show_thread_status_response', 10, 4 );

function show_thread_status_response( $v, $thread_id, $action, $user_id ) {

	set_current_user_thread_status( $thread_id, $action, $user_id );

	// add something to the $v array; it will be included in the json response to each heartbeat ping
	$v['thread_status'] = get_user_thread_activity( $thread_id );

	return $v;
}

// ^^ end general example of the heartbeat system.  The below code is specific to this particular plugin.

// Tell other clients what the current user is doing in this thread.
function set_current_user_thread_status( $thread_id, $action, $user_id ) {

	// use a short expiry because this will be updated every 15 seconds when the page is active.
	wp_cache_set( $user_id, $action, "thread-status-{$thread_id}", 60 );
}

function get_user_thread_activity( $thread_id ) {
	$out = array();

	$all_user_ids = get_support_user_ids();
	foreach ( $all_user_ids as $user_id ) {
		$activity = wp_cache_get( $user_id, "thread-status-{$thread_id}" );
		if ( $activity !== false ) {
			$user = get_user( $user_id );
			$out[ $user->user_login ] = $activity;
		}
	}

	return $out;
}