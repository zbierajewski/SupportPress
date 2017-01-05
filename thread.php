<?php

$js = '<script src="js/thread.js?v=1.0.1" type="text/javascript"></script>';

?>
<?php
include( 'init.php' );
include( 'includes/viewing.php' );
include( 'header.php' );
include( 'includes/mime.php' );

//Oh I am so stricken.
//For I had dropped the chicken.
//It is all over my pants.
//Gee, Would this happen... Over at ants.

$thread_id = (int) $_GET['t'];
$thread = get_thread( $thread_id );
if ( empty( $thread ) )
	die( 'Thread not found.' );


$referer = '';
if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
	$referer = $_SERVER['HTTP_REFERER'];
}

function thread_reply_form ( $i, $m, $message_text, $reply_message, $thread, $thread_id ) {
	global $current_user;
	global $referer;
	?>
	<form action="thread-reply.php" enctype="multipart/form-data" method="post" class="inlinereplyform" <?php if ( $i ) { ?>style="display: none;"<?php } ?> id="<?php echo esc_attr( 'ir' . $m->message_id ); ?>" onsubmit="return validate_upload(this);">
	<?php
	static $predefined = null;
	static $predefined_options = null;
	if ( empty( $predefined ) ) {
		$predefined = get_predefined_names();
		foreach ( $predefined as $id => $name )
			$predefined_options .= html_message('<option value="%d">%s</option>', $id, $name);
	}
	$reply_message = $message_text;
	$reply_message = preg_replace( "|(-- \n.*)$|s", '', $reply_message ); // strip the sig
	$reply_message = wordwrap( $reply_message, 74, "\n" );
	$reply_message = trim( $reply_message );
	$reply_message = "> ". str_replace( "\n", "\n> ", $reply_message );
	$reply_message = stripslashes( $reply_message );
	?>

	<p>
	  Reply to:<br />
	  <input type="text" size="100" name="to_email" value="<?php echo htmlspecialchars($thread->email); ?>" /><br />
	  <span id="email_error"></span>
	  <a href="#" class="cc-toggle">Add CC or BCC</a>
	</p>
	<div class="cc-field" style="display: none;">
		<p>
		  Cc:<br />
		  <input type="text" size="100" name="cc" value="<?php echo htmlspecialchars($thread->cc); ?>" /><br />
		  <span id="email_error"></span>
		</p>
		<p>
		  Bcc:<br />
		  <input type="text" size="100" name="bcc" value="<?php echo htmlspecialchars($thread->bcc); ?>" /><br />
		  <span id="email_error"></span>
	  	</p>
  	</div>

	<select class="predefined_message">
	<option value="">Predefined Reply</option>
	<?php echo $predefined_options; ?>
	</select>

	<p class="attachment-box">Attachment:<br />
	<input type="file" name="attachment" id="attachment" />
	</p>
	<div class="message-box">
	<textarea name="message_reply" class="widetext reply replying" style="height: <?php echo substr_count( $reply_message, "\n" ) + 8; ?>em "><?php
	
		$sig = '';
		
		if ( isset( $current_user->data->signature ) ) {
			$sig = $current_user->data->signature;
		}
		echo esc_textarea( sprintf( "\n\n%s\n\n%s wrote:\n%s", $sig, $m->email, $reply_message ) );
		
	?></textarea>
	</div>
	<span class="submit right" style="margin-top: 13px;">
		<input type="hidden" name="thread_id" value="<?php echo esc_attr( $thread_id ); ?>" />
		<input type="submit" name="send" value="Send" />
		<input type="submit" name="sendtickle" value="Send and Tickle" />
		<input type="submit" name="sendclose" value="Send and Close" />
		<input type="hidden" name="from" value="<?php echo esc_url( $referer ); ?>" />
	</span>
	<p class="addtags">
	<label>Add tags:
	<input type="text" size="50" name="tags" value=""  class="tag padme reply replying" /></label>
	</form>
<?php } ?>

<h2><?php echo esc_html( stripslashes( mime_header_decode($thread->subject) ) ); ?></h2>

<?php
$messages = $db->get_results( $db->prepare( "SELECT * FROM $db->messages WHERE thread_id = %d ORDER BY message_id DESC", $thread_id ) );


if ( count( $messages ) > 0 ) :

	do_action( 'thread-above', $thread_id, $messages );

	$did_show_note = false;
	foreach ( $messages as $idx => $m ) {
		if ( $m->message_type != 'note' )
			continue;
		printf(
			"<div class='message note note-alert'><button onClick='$(\".note-%d\").toggle(); return false;'>Toggle</button> from %s %s on %s:" .
			"<div class='note-%d' style='%s'><p class='content'>%s</p></div></div>",
			$idx,
			esc_html( $m->email ),
			esc_html( fuzzy_time_diff( $m->dt ) ),
			esc_html( $m->dt ),
			$idx,
			( empty( $did_show_note ) ? '' : 'display:none;' ),
			make_clickable( nl2br( esc_html( $m->content ) ) )
		);
		$did_show_note = true;
	}

	$i = -1;
	foreach ( $messages as $m ) {
		if ( $m->message_type == 'note' )
			continue;
		$i++;

		if ( ! empty( $_GET['qqq'] ) )
			var_dump( htmlspecialchars( $m->content ) );

		$m->cc = null;
		$m->bcc = null;
		$m->attachment = null;

		$message_meta = $db->get_results( $db->prepare( "SELECT meta_key, meta_value FROM $db->messagemeta WHERE message_id = %d", $m->message_id ) );
	
		foreach( $message_meta as $meta ) {
			if ( $meta->meta_key == 'cc' )
				$m->cc = htmlspecialchars( $meta->meta_value );
			if ( $meta->meta_key == 'bcc' )
				$m->bcc = htmlspecialchars( $meta->meta_value );
		}

		$attachment_name = $db->get_var( $db->prepare( "SELECT filename FROM $db->attachments WHERE message_id = %d", $m->message_id ) );
	
		if ( isset( $attachment_name ) )
			$m->attachment = $attachment_name;


		if ( $parts = mime_split($m->content) ) {
			$part = find_first_part($parts, 'text/plain');
			$message_text = trim($part->content);
			// some autoresponders include a blank text/plain part
			if ( !$message_text ) {
				$part = find_first_part($parts, 'text/html');
				$message_text = trim( strip_tags($part->content) );
			}
		} elseif ( preg_match_all( '/=(?:20|3F|5F|0A|0D|A0|\s*)/i', $m->content, $dummy ) > 1 ) {
			$message_text = trim( quoted_printable_decode( $m->content ) );
		} else {
			$message_text = trim( $m->content );
		}
		// Allow plugins to massage the content
		$message_text = apply_filters( 'message-text', $message_text, $m );
		if ( preg_match( '/^\s*<html>/ims', $message_text ) ) {
			$message_text = preg_replace( '/<style.*<\/style>/Uims', '', $message_text );
			$message_text = preg_replace( '/=\n/m', '', $message_text );
			$message_text = preg_replace( '@</(div|p|span|td|tr|table)>@ims', "\n", $message_text );
			$message_text = preg_replace( '@<br/?>@ims', "\n", $message_text );
			$message_text = str_ireplace( '&nbsp;', ' ', $message_text  );
			$message_text = str_replace( "\r", "\n", $message_text );
			$message_text = preg_replace( "/\n\n+/ms", "\n", $message_text );
			//$message_text = strip_tags( $message_text );
		}
		$html_message = htmlspecialchars( $message_text );
		$html_message = preg_replace( "|(-- \n.*)$|s", '<span class="sig">$1</span>', $message_text );
		$html_message = nl2br( $html_message );
		$html_message = make_clickable( $html_message );
		$html_message = preg_replace( '|href="(http://[^"]+)"|', 'href="http://href.li/?$1"', $html_message );
		$html_message = stripslashes( $html_message );



		// Reply form at the very top
		if ( 0 == $i )
	 		thread_reply_form( $i, $m, $message_text, empty( $reply_message ) ? '' : $reply_message, $thread, $thread_id );

		do_action( 'message-above', $m->message_id, $m );

		$meat = htmlspecialchars( message_meat( $message_text ) );
		if ( ! $i ) {
			echo "<input class='lastclicked' value='" . esc_attr( $m->message_id ) . "' type='hidden' />";
			$the_user = $db->get_row( $db->prepare("SELECT * FROM $db->users WHERE user_email=%s LIMIT 1", $m->email) );
			if ( ! empty( $the_user ) )
				echo "<input class='user_id' value='". esc_attr( $the_user->ID ) . "' type='hidden' />";
		}

		if ( ! empty( $m->from_user_id ) ) {
			$u = get_user( $m->from_user_id, array( 'append_meta' => false ) );
			$avatar = get_avatar( $u->user_email, 48 );
			$staff_reply = "Staff reply by {$u->display_name}<br />";
		} else {
			$avatar = get_avatar( $m->email, 48 );
			$staff_reply = '';
		}

		$in = count( $messages ) - $i + 1;

		$permalink = add_query_arg( 'message', $m->message_id ) . '#m' . $m->message_id;
		$plinked = ( ! empty( $_GET['message'] ) && $_GET['message'] == $m->message_id ) ? ' highlight' : '';

		echo '<div class="'. esc_attr( 'message' . $plinked . ' ' . $m->message_type . ' ' . "n$i" . ' ' . "n-$in" ) . '" id="' . esc_attr( 'm' . $m->message_id ) . '">';

		$show_or_hide = '';
		if ( $i > 1 )
			$show_or_hide = " class='mainpart'";

		echo "<p class='avatar message-toggle'>$avatar</p>
		<p class='wrote message-toggle'>{$staff_reply}<a href='index.php?email=$m->email' class='email'>$m->email</a> wrote ".fuzzy_time_diff($m->dt)." on <a href='$permalink'>$m->dt</a>: <span class='meat'>" . esc_html( $meat ) . " &hellip;</span>
		<br/>
		<span style='font-size: 12px;'>To: {$m->email_to}";

		if( $m->cc )
			echo " Cc: {$m->cc}";

		if ( $m->bcc )
			echo " Bcc: {$m->bcc}";

		echo "</p>
		<div$show_or_hide>
		<p class='content' id='c$m->message_id'>$html_message</p>";

		if ( $m->attachment ) {
			echo "<p class='content'><strong>Attachment:</strong> <a href='view-attachment.php?id={$m->message_id}' target='_blank'>{$m->attachment}</a></p>";
		}

		if ( $m->message_type == 'support' ) { ?>
			<p class='action' <?php if ( !$i ) { ?>style="display: none;"<?php } ?>><input type='button' class='inlinereply' value='Reply' name='<?php echo esc_attr( 'ir' . $m->message_id ); ?>' /></p>
	<?php }

		if ( $i )
			thread_reply_form( $i, $m, $message_text, empty( $reply_message ) ? '' : $reply_message, $thread, $thread_id );

		if ( $parts = mime_split($m->content) ) {
			foreach ( $parts as $pi => $part ) {
				echo '<div class="hidepart">';
				if ( $part->is_type('image') ) {
					echo '<img src="message-image.php?message_id='.intval($m->message_id).'&amp;i='.intval($pi).'" />';
				} elseif ( $pi > 0 && $part->is_type('text/plain') ) {
					echo '<pre>'; echo esc_html( $part->content ); echo '</pre>';
				} else {
					echo '<p class="mime-part">MIME part: <a href="message-attachment.php?message_id='.intval($m->message_id).'&amp;i='.intval($pi).'" target="_blank">' . esc_html( $part->content_type ) . '</a></p>';
				}
				echo '</div>';
			}
		}
	
		echo '</div>'; // mainpart
		echo '</div>'; // message

		do_action('message-below', $m->message_id, $m);
	}

	do_action('thread-below', $thread_id, $messages);

endif;
?>

<form action="thread-delete.php" method="post">
<p>
<input type="hidden" name="from" value="<?php echo esc_url( $referer ); ?>" />
<input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>" />
<input type="submit" name="delete" value="Delete Thread &raquo;" />
</p>
</form>

<form action="thread-status.php" method="post" id="threadstatus" class="<?php if ( isset( $_GET['updated'] ) && 'status' == $_GET['updated'] ) echo "fade"; ?>">
<p>
<input type="hidden" name="status" value="closed" />
<input type="hidden" name="from" value="<?php echo esc_url( $referer ); ?>" />
<input type="submit" name="submit" value="Close Thread" /></p>
<input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>" />
</p>
</form>


<?php
include( 'footer.php' );