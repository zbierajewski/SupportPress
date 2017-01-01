<?php

/* HTML and form procesing functions */

// this is like sprintf(), except the arguments are all escaped with htmlspecialchars.
// $fmt is intentionally not escaped so that tags may be included there
// unsafe data should always go in the second and subsequent arguments, not in $fmt itself.
function html_message($fmt /*, ... */) {
	$args = func_get_args();
	array_map( 'esc_html', $args );
	$args[0] = $fmt;
	$out = call_user_func_array('sprintf', $args);
	if ( !$out )
		return $msg;
	return $out;
}

// same arguments as html_message();
function status_message(/*...*/) {
	global $sp_user_messages;

	$args = func_get_args();
	@$sp_user_messages[] = array( 'status', call_user_func_array( 'html_message', $args ) );
}

// same arguments as html_message();
function error_message(/*...*/) {
	global $sp_user_messages;

	$args = func_get_args();
	@$sp_user_messages[] = array( 'error', call_user_func_array( 'html_message', $args ) );
}

function get_user_messages() {
	global $sp_user_messages;

	$out = array();
	foreach ( (array)$sp_user_messages as $m ) {
		$out[] = '<p class="' . esc_attr( $m[0] ) . '">' . $m[1] . '</p>';
	}

	return $out;
}