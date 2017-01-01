<?php

function get_thread( $id ) {

	global $db;

	$id = (int) $id;

	$thread = $db->get_row( $db->prepare( "SELECT * FROM $db->threads WHERE thread_id = %d", $id ) );
	if ( empty( $thread ) )
		return false;
	$thread->cc = '';
	$thread->bcc = '';

	$latest_message = $db->get_row( $db->prepare( "SELECT message_id, email_to FROM $db->messages WHERE thread_id = %d ORDER BY message_id DESC LIMIT 1", $id ) );

	if ( $latest_message ) {

		$message_id = (int) $latest_message->message_id;

		$thread->cc = $db->get_var( $db->prepare( "SELECT meta_value FROM $db->messagemeta WHERE message_id = %d AND meta_key = 'cc'", $message_id ) );
		$thread->bcc = $db->get_var( $db->prepare( "SELECT meta_value FROM $db->messagemeta WHERE message_id = %d AND meta_key = 'bcc'", $message_id ) );

	}

	return $thread;
}

function thread_tags( $id ) {
	$id = (int) $id;
	$tags = get_thread_tags( $id );
	if ( !$tags )
		return;

	$out = array();
	foreach ( $tags as $tag )
		$out[] = "<a href='index.php?tag=$tag'>$tag</a>";

	$out = join( ', ', $out );
	return $out;
}

function multi_thread_tags( $ids ) {
	if ( !$ids )
		return array();
	$ids = array_map( 'intval', $ids );
	$all_tags = get_thread_tags( $ids );
	if ( !$all_tags ) {
		return array();
	}

	$rval = array();
	foreach ( (array) $all_tags as $thread_id => $tags ) {
		$out = array();
		foreach ( (array) $tags as $tag ) {
			$tag = esc_html( $tag );
			$out[] = "<a href='index.php?tag=$tag'>{$tag}</a>";
		}

		$rval[$thread_id] = join( ', ', $out );
	}

	return $rval;
}

function thread_tags_form( $id ) {
	$id = (int) $id;
	$tags = get_thread_tags( $id );
	if ( !$tags )
		return;

	$out = join( ', ', $tags );
	return $out;
}

function get_thread_tags( $id ) {
	global $db;

	$tags = '';

	if ( is_array( $id ) ) {
		$ids = implode( ', ', array_map( 'intval', $id ) );
		$all_tags = $db->get_results( "SELECT thread_id, tag_slug FROM $db->tags WHERE thread_id IN ( $ids )", ARRAY_A );
		foreach ( (array) $all_tags as $t ) {
			$tags[$t['thread_id']][] = $t['tag_slug'];
		}
	} else {
		$tags = $db->get_col( $db->prepare("SELECT tag_slug FROM $db->tags WHERE thread_id = %d", $id) );
	}

	return $tags;
}

function add_tags( $id, $str ) {
	global $db;

	$id = (int) $id;

	$current_tags = get_thread_tags( $id );

	$new_tags = explode(',', $str);
	$clean_new = array();
	foreach ( $new_tags as $tag ) {
		$tag = trim( $tag );
		if ( !$tag = sanitize_title( $tag ) )
			die('funky');
		if ( !in_array( $tag, $current_tags ) )
			$db->insert( $db->tags, array( 'thread_id' => $id, 'tag_slug' => $tag ) );
		$clean_new[] = $tag;
	}
}

function update_tags( $id, $str ) {
	global $db;

	$id = (int) $id;

	# special condition short cut, when all the tags have been removed
	# the regular update process fails for this condition
	if ( $str == '' ) {
		$db->query( $db->prepare("DELETE FROM {$db->tags} WHERE thread_id = %d", $id) );
		return;
	}

	$current_tags = get_thread_tags( $id );

	$new_tags = explode(',', $str);
	$clean_new = array();
	foreach ( $new_tags as $tag ) {
		$tag = trim( $tag );
		if ( !$tag = sanitize_title( $tag ) )
			die('funky');
		if ( !in_array( $tag, $current_tags ) )
			$db->insert( $db->tags, array( 'thread_id' => $id, 'tag_slug' => $tag ) );
		$clean_new[] = $tag;
	}

	// Axe what was removed
	$removed = array_diff( $current_tags, $clean_new );
	foreach ( $removed as $tag )
		$db->query( $db->prepare("DELETE FROM $db->tags WHERE thread_id = %d AND tag_slug = %s", $id, $tag) );
}

function message_meat( $m ) {
	$lines = explode( "\n", $m );

	$no_quotes = '';
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( empty( $line ) )
			continue;
		if ( $line{0} == '>' )
			continue;
		if ( strpos( $line, 'wrote:' ) )
			continue;
		$no_quotes .= "$line ";
	}
	$no_quotes = wordwrap( $no_quotes, 100, "---split---" );
	$no_quotes = explode( '---split---', $no_quotes );
	return $no_quotes[0];
}

function get_predefined_names( ) {
	global $db;

	$rows = $db->get_results( "SELECT id, name FROM $db->predefined_messages ORDER BY name ASC" );
	$out = array();
	foreach ( (array)$rows as $row )
		$out[ $row->id ] = $row->name;
	return $out;
}

// add or update a predefined message. will update if $id is set, insert otherwise.
// returns the ID of the message or false on error.
function store_predefined_message( $name, $message, $tags, $id=null ) {
	global $db;
	if ( $id > 0 ) {
		if ( $db->update( $db->predefined_messages, array( 'name' => $name, 'message' => $message, 'tag' => $tags ), array( 'id' => $id ) ) )
			return $id;
		else
			return false;
	} else {
		if ( $db->insert( $db->predefined_messages, array( 'name' => $name, 'message' => $message, 'tag' => $tags ) ) )
			return $db->insert_id;
		else
			return false;
	}
}
