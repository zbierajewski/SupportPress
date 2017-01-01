<?php

if ( function_exists( 'add_action' ) )
	add_action( 'sp_head', 'sp_viewing_head' );
function sp_viewing_head() {
	global $current_user;
	if ( empty( $current_user ) || empty( $current_user->ID ) )
		return;
	echo "<script type='text/javascript'>\n";
	echo "sp_current_user_login=" . json_encode($current_user->user_login) . "\n";
	if ( !empty( $_GET['t'] ) ) {
		sp_viewing_record_user_view( $current_user->user_login, $_GET['t'] );
		echo "sp_current_thread_id=" . json_encode( $_GET['t'] ) . "\n";
	} else {
		sp_viewing_record_user_view( $current_user->user_login, -1 );
		echo "sp_current_thread_id=0\n";
	}
	echo "function sp_update_viewing() {
		jQuery.getJSON(
			'includes/viewing.php?ajax=viewing&viewing-action=check&viewing-user=' + sp_current_user_login + '&thread-id=' + sp_current_thread_id,
			function( data ) {
				if ( !data.length || '" . $current_user->user_login . "' === data[0] ) {
					$('div.viewing-notice').remove();
					return;
				}
				var html='<div class=\"viewing-notice\">';
				for ( var i in data ) {
					if ( data[i] == sp_current_user_login ) {
						continue;
					}
					html = html + '<span class=\"viewing-viewer\">' + data[i] + ' is viewing this thread</span><br/>';
				}
				html = html + '</div>';
				$('div.viewing-notice').remove();
				$('div.wrap:first').prepend( html );
			}
		);
	}\n";
	if ( !empty( $_GET['t'] ) ) {
		echo '$(document).ready( function() {
			sp_update_viewing();
			setInterval( \'sp_update_viewing();\', 10000 );
		} );';
	}
	echo "</script>\n";
}

function sp_viewing_data_dir() {
	return dirname( dirname( __FILE__ ) ) . '/viewerdata';
}

function sp_viewing_record_user_view( $who, $id ) {
	$whofile = sp_viewing_data_dir() . "/" . md5( $who );
	if ( !file_exists( $whofile ) ) 
		return false; 	
	if ( !empty( $id ) )
		return file_put_contents( $whofile, sprintf( "%s\t%d\t%s", time(), $id, $who ) );
}

function sp_viewing_who_is_viewing( $id ) {
	$who = array();
	foreach( glob( sp_viewing_data_dir() . "/*" ) as $file ) {
		$file = explode( "\t", file_get_contents( $file ) );
		if ( $file[1] != $id )
			continue;
		if ( ( time() - $file[0] ) < 600 )
			$who[] = $file[2];
	}
	return $who;
}

if ( !empty( $_GET['ajax'] ) && $_GET['ajax'] == 'viewing' && !empty( $_GET['viewing-action'] ) ) {
	header( 'Content-Type: text/plain' );
	switch( $_GET['viewing-action'] ) {
		case 'check':
			echo json_encode( sp_viewing_who_is_viewing( intval( $_GET['thread-id'] ) ) );
			if ( !empty( $_GET['viewing-user'] ) && !empty( $_GET['thread-id'] ) )
				sp_viewing_record_user_view( $_GET['viewing-user'], $_GET['thread-id'] );
			break;
	}
	die();
}