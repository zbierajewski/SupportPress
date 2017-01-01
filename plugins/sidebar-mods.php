<?php

if ( ! defined( 'ENABLE_SIDEBAR_MODS' ) || ! ENABLE_SIDEBAR_MODS )
	return;

/**
 * This is a test plugin to demonstrate how to use the hooks found in sidebar-thread.php
 * that allow the customization of the information and actions available in the sidebar when managing tickets.
 * This is meant to be a base plugin that can be extended for your needs
 * (Un)comment filters as needed
 */

/**
 * disable the entire sidebar
 */
// add_filter( 'disable_thread_right_sidebar', '__return_true' );

/**
 * add a widget to the top of the sidebar
 */
add_action( 'sidebar_thread_top', 'sp_sidebar_plugin_sidebar_thread_top', 10, 2 );
function sp_sidebar_plugin_sidebar_thread_top( $thread_id, $status ) {
	$thread = get_thread( $thread_id );
	if ( $thread->has_attachments ) {
		echo __( 'This ticket has attachment(s)' );
	} else {
		echo __( 'This ticket has no attachments' );
	}
	echo '<div class="hline"></div>';
}

/**
 * disable individual widgets in the sidebar
 */
// add_filter( 'disable_thread_right_sidebar_ticket_info', '__return_true' );
// add_filter( 'disable_thread_right_sidebar_ticket_management', '__return_true' );
// add_filter( 'disable_thread_right_sidebar_ticket_tags', '__return_true' );
// add_filter( 'disable_thread_right_sidebar_ticket_notes', '__return_true' );