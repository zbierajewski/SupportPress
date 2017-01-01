<?php

require_once dirname( __FILE__ ) . '/../base-init.php';

$rows = $db->get_results( "SELECT thread_id,messages,state FROM $db->threads" );
foreach( $rows as $row ) {
	$actual = $db->get_var( $db->prepare( "SELECT count(thread_id) FROM $db->messages WHERE thread_id = %d AND message_type='support'", $row->thread_id ) );
	if ( $row->messages < $actual ) {
		echo "$row->thread_id ($row->state) $row->messages != $actual\n";
		$db->update( $db->threads, array( 'messages' => $actual, 'state' => 'open' ), array( 'thread_id' => $row->thread_id ) );
	}
}