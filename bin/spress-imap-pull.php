#!/usr/local/bin/php -d display_errors=true
<?php

require_once dirname( __FILE__ ) . '/../base-init.php';


if ( !function_exists( 'imap_open' ) ) {
  die( 'IMAP extension not added to php.ini' );
}


if ( !defined( 'IMAP_PORT' ) )
	define( 'IMAP_PORT', '143' );

define( 'IMAP_ACCOUNT_STRING', '{' . IMAP_HOST . ':' . IMAP_PORT . '/imap/ssl/novalidate-cert}' . IMAP_MAILBOX );

$opts = getopt( 'vtr:' );
define( 'OPT_TEST', isset( $opts['t'] ) );

if ( isset( $opts['r'] ) )
	define( 'OPT_RESYNC', intval( $opts['r'] ) );
else
	define( 'OPT_RESYNC', '' );

$verbose = FALSE;
if ( isset( $opts['v'] ) )
	$verbose = TRUE;

$mbox = imap_open( IMAP_ACCOUNT_STRING, IMAP_USER, IMAP_PASSWORD );
if ( !$mbox )
	die( "COULD NOT OPEN MAILBOX!\r\n" );

$boxinfo = imap_check( $mbox );

if ( !is_object( $boxinfo ) || ! isset( $boxinfo->Nmsgs ) )
	die( "COULD NOT GET MAILBOX INFO\r\n" );

if ( $boxinfo->Driver != 'imap' )
	die( "THIS SCRIPT HAS ONLY BEEN TESTED WITH IMAP MAILBOXES\r\n" );

if ( OPT_TEST )
	die( "CONNECTION CONFIRMED\r\n" );

if ( $boxinfo->Nmsgs < 1 )
	die( "NO NEW MESSAGES\r\n" );

if ( $verbose )
	echo "Fetching $boxinfo->Mailbox\r\n$boxinfo->Nmsgs messages, $boxinfo->Recent recent\r\n";

// Helps clean up the mailbox, especially if a desktop client is interracting with it also
imap_expunge( $mbox );

// IMAP UID of the last message fetched
$last_uid = $db->get_var( "SELECT max(uid) FROM $db->messages" );
if ( $last_uid < 1 )
	$last_uid = 1;

// fetch all messages since (and including) the last known. Skip the first since it's a dupe.
$messages = imap_fetch_overview( $mbox, "{$last_uid}:*", FT_UID );
if ( $messages && $messages[0]->uid == $last_uid )
	unset( $messages[0] );

if ( $verbose )
	echo count( $messages ) . " new messages\r\n";

if ( OPT_RESYNC > 0 )
	$messages = array_slice( array_values( $messages ), -OPT_RESYNC );

foreach( (array)$messages as $message ) {

	// Detect a disconnect...
	if ( !imap_ping( $mbox ) )
			die( "REMOTE IMAP SERVER HAS GONE AWAY\r\n" );
	// Fetch this message, fix the line endings, this makes a 1:1 copy of the message in ram
	// that, in my tests, matches the filesystem copy of the message on the imap server
	// ( tested with debian / postfix / dovecot )
	$r = processmail(
		str_replace(
			"\r\n",
			"\n",
			imap_fetchheader( $mbox, $message->uid, FT_UID | FT_INTERNAL ) . imap_body( $mbox, $message->uid, FT_UID )
		)
		, $message->uid
	);
	if ( $r) {
		if ( $verbose )
			echo '.';
		imap_setflag_full( $mbox, $message->uid, '\SEEN', ST_UID );
	}
}

if ( $verbose )
	echo "\r\n";

#imap_expunge( $mbox );

function processmail( $email, $uid ) {

	global $db, $verbose;
	if ( trim( substr( $email, 0, 100 ) ) == '' )
		die( "GOT EMPTY MESSAGE?!\r\n" );

	$email = apply_filters( 'pre_processmail', $email );
	if ( empty( $email ) )
		return false;

	$emaillines = explode("\n", $email);
	$spam = $has_attachment = false;
	$headers = $email = $message = $subject = $date = $from = $address = $name = $spamhits = $message_hash = $cc = '';

	// Memory Usage optimization
	if ( $verbose )
		echo "Processing new message: " . md5( $email ) . "\n";

	while( null !== ( $line = array_shift( $emaillines ) ) ) {
		$email .= $line;
		if ( count( $emaillines ) )
			$email .= "\n";
		if ('' == $line) { // first blank line
			$is_message = true;
			$message = implode( "\n", $emaillines );
			$emaillines = '';
			$headers = trim( $email );
			$email .= $message;
			break;
		}
		$peek = strtolower( array_shift( explode( ":", $line ) ) );
		switch( $peek ) {
			case 'subject':
				if ( $verbose )
					echo "\t$line\n";
				if ( !preg_match('/^Subject: (.*)/i', $line, $data ) )
					break;
				$subject = $data[1];
				break;
			case 'date':
				if ( $verbose )
					echo "\t$line\n";
				if ( !preg_match('/^Date: (.*)/i', $line, $data) )
					break;
				if ( isset( $data[1] ) )
					$date = $data[1];
				break;
			case 'from':
				if ( $verbose )
					echo "\t$line\n";
				if ( !preg_match('/^From: (.*)/i', $line, $data) )
					break;
				$from = $data[1];
				preg_match( "/([a-z0-9]+(?:[+_\\.-][a-z0-9]+)*@(?:[a-z0-9]+([\.-][a-z0-9]+)*)+\\.[a-z]{2,})/i" , $from, $data );
				if ( isset( $data[1] ) )
					$address = $data[1];
				$name = trim( preg_replace( '|(.+)<.*>|', '$1', $from ) );
				$name = trim( $name, '"' );
				break;
			case 'x-spam-status':
				if ( $verbose )
					echo "\t$line\n";
				if ( !preg_match('/^X-Spam-Status: .*? hits=([^ ]*)/i', $line, $data) )
					break;
				$spamhits = $data[1];
				break;
			case 'in-reply-to':
				if ( $verbose )
					echo "\t$line\n";
				if ( !preg_match('/^In-Reply-To: <([a-z0-9]{32})/i', $line, $data) )
					break;
				$message_hash = $data[1];
				break;
			case 'x-spam-flag':
				if ( $verbose )
					echo "\t$line\n";
				if ( !preg_match('/^X-Spam-Flag: YES/i', $line, $data) )
					break;
				$spam = true;
				break;
			case 'content-type':
				if ( $verbose )
					echo "\t$line\n";
				// Check for attachment
				if ( !strpos( $line, 'multipart/alternative' ) && !strpos($line, 'text/plain') && !strpos( $line, 'text/html' ) ) {
					$has_attachment = true;
					break;
				}
			case 'cc':
				if ( $verbose )
					echo "\t$line\n";
				if ( !preg_match('/^Cc: (.*)/i', $line, $data ) )
					break;
				$cc = $data[1];
			case 'to':
				if ( $verbose )
					echo "\t$line\n";
				if ( !preg_match('/^To: (.*)/i', $line, $data ) )
					break;
				$to = $data[1];
		}
	}

	// Memory Usage optimization
	unset( $emaillines );

	if ( $spam )
		return;

	$email_length = strlen( $email );
	$claimed_date = gmdate( 'Y-m-d H:i:s', strtotime( $date ) );
	$hash = md5( $claimed_date . $email . $message );

	// Memory Usage optimization
	unset( $email );

	$subject = addslashes( $subject );
	$sender = addslashes( $from );

	$name = addslashes( $name );
	$address = addslashes( $address );

	$dt = gmdate( 'Y-m-d H:i:s' );


	// First we need to find the thread
	$thread_id = 0;
	if ( $message_hash ) {
		$row = $db->get_row( $db->prepare( "SELECT * FROM $db->messages WHERE hash = %s", $message_hash ) );
		if ( $row )
			$thread_id = $row->thread_id;
	}

	if ( !$thread_id ) {
		$clean_sub = preg_replace( '#\b[a-z]{2,3}:\s*#i', '', $subject );
		$row = $db->get_row( $db->prepare(
			"SELECT * FROM $db->threads WHERE email = %s AND subject LIKE (%s) ORDER BY dt DESC", $address, '%' . $clean_sub . '%'
		) );
		if ( $row )
			$thread_id = $row->thread_id;
	}

	if ( !$thread_id ) {
		// we can't find it, so make a new thread
		$result = $db->insert( $db->threads, array( 'hash' => $hash, 'dt' => $dt, 'email' => $address, 'subject' => $subject ) );
		$created_thread_id = $db->insert_id;
		$thread_id = $db->insert_id;
	}

	// Message already exists?
	if ( $db->get_var( $db->prepare( "SELECT message_id FROM $db->messages WHERE hash = %s", $hash ) ) )
		return false;

	$res = $db->insert( $db->messages, array( 'hash' => $hash, 'thread_id' => $thread_id, 'dt' => $dt, 'email' => $address, 'email_to' => $to, 'content' => $message, 'uid' => $uid ) );
	if ( $res ) {
		
		$count = $db->get_var( $db->prepare( "SELECT count(*) FROM $db->messages WHERE thread_id = %d", $thread_id ) );
		$db->update( $db->threads, array( 'messages' => $count, 'state' => 'open', 'dt' => $dt ), array( 'thread_id' => $thread_id ) );

		$message_id = $db->insert_id;

		if ( mb_strlen( $cc ) > 0 ) {
			$db->insert( $db->messagemeta, array( 'message_id' => $message_id, 'meta_key' => 'cc', 'meta_value' => $cc ) );
		}

		update_metadata( 'message', $message_id, 'headertext', $headers );

		if ( true === $has_attachment ) {
			$db->update( $db->threads, array( 'has_attachment' => 1 ), array( 'thread_id' => $thread_id ) );
		}

		// tags are a single CSV string: blue, green, red
		$tags = (string) apply_filters(
			'imap_pull_thread_tags',
			'',
			array(
				'thread_id'		=> $thread_id,
				'thread_count'	=> $count,
				'from'			=> $address,
				'subject'		=> $subject,
				'body'			=> $message
			)
		);

		if ( $tags != '' )
			add_tags( $thread_id, $tags );

		if ( empty( $created_thread_id ) )
			do_action( 'processmail_thread_updated', $thread_id, $message_id, $address );
		else
			do_action( 'processmail_thread_created', $thread_id, $message_id, $address );

		return true;
	}
}