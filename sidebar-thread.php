<?php
if ( !isset( $_status ) )
	$_status = '';
// ability to disable the sidebar
if ( apply_filters( 'disable_thread_right_sidebar', false, $thread_id, $_status ) )
	return;

$http_referer = '';
if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
	$http_referer = $_SERVER['HTTP_REFERER'];
}
?>

	<div class="rightcol">

		<div class="frame">

			<?php do_action( 'sidebar_thread_top', $thread_id, empty( $_status ) ? '' : $_status ); ?>

			<?php if ( ! apply_filters( 'disable_thread_right_sidebar_ticket_info', false, $thread_id, $_status ) ) : ?>
			<div class="topinfo">
				Ticket #<?php echo $thread_id ?>
			</div>
			<div class="hline"></div>
			<?php endif; ?>

			<?php if ( ! apply_filters( 'disable_thread_right_sidebar_ticket_management', false, $thread_id, $_status ) ) : ?>
				<div class="toplinks">

					<!-- Ticket management buttons -->
					<?php foreach( array( 'open' => 'Open', 'closed'=> 'Close' , 'tickle' => 'Tickle' ) as $_status => $_message ) { ?>
					<?php if ( $thread->state == $_status ) continue; ?>
					<form action="thread-status.php" method="post" style="float: left;" id="statusform">
						<input type="hidden" name="thread_id" value="<?php echo esc_attr( $thread_id ); ?>" />
						<input type="hidden" name="status" value="<?php echo esc_attr( $_status ); ?>" />
						<button><?php echo $_message; ?></button>
					</form>
					<?php } ?>

					<!-- Notify button -->
					<input type="button" value="Notify" class="notify-button" style="float:left;" />

				</div>

				<!-- Notify options -->
				<form action="thread-notify.php" method="post" class="notify-users" style="display: none;">
					<?php
					$sp_users = $wpdb->get_results( "SELECT * FROM $wpdb->usermeta um JOIN $wpdb->users u ON (um.user_id=u.ID) WHERE meta_key='{$db->prefix}capabilities'" );
					if ( ! empty( $sp_users ) ) {
						foreach ( $sp_users as $user )
							echo '<label><input type="checkbox" name="notify[]" class="notify-checkbox" value="' . esc_attr( $user->user_id ) .'" />' . esc_attr( $user->user_login ) .'</label> ';
					}
					?>
					<div class="notify-toggle" style="display:none;">
						<span class="light sm">Message (seen by staff only):</span>
						<textarea class="widetext notify-message" name="notify-message" id="notify-message" style="height: 8em;"></textarea>
						<p class="submit">
							<input type="submit" name="notify-send" value="Notify &raquo;" />
							<input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>" />
						</p>
					</div>
				</form>

				<div class="hline"></div>
			<?php endif; ?>

			<?php do_action( 'sidebar_thread_middle', $thread_id, $_status ); ?>

			<?php if ( ! apply_filters( 'disable_thread_right_sidebar_ticket_tags', false, $thread_id, $_status ) ) : ?>
				<!-- Add tag without sending a response -->
				<form action="thread-tags.php" method="post" id="newtags">
					Tags:
					<input type="text" size="25" name="tags" value="<?php echo thread_tags_form( $thread->thread_id ); ?>"  class="widetext" />
					<p class="submit">
						<input type="submit" value="Update" name="submit" />
						<input type="hidden" name="thread_id" value="<?php echo esc_attr( $thread_id ); ?>" />
					</p>
				</form>
				<div class="hline"></div>
			<?php endif; ?>

			<?php if ( ! apply_filters( 'disable_thread_right_sidebar_ticket_notes', false, $thread_id, $_status ) ) : ?>
				<!-- Add note -->
				<form method="post" action="thread-addnote.php">
					Add a note:
					<textarea name="note" class="widetext" style="height: 12em;"></textarea>
					<p class="submit">
						<input type="hidden" name="thread_id" value="<?php echo esc_attr( $thread_id ); ?>" />
						<input type="submit" name="submit" value="Add Note" />
						<input type="submit" name="addclose" value="Add &amp; Close" />
						<input type="hidden" name="from" value="<?php echo esc_url( $http_referer ); ?>" />
					</p>
				</form>
			<?php endif; ?>

			<?php do_action( 'sidebar_thread_bottom', $thread_id, $_status ); ?>
		</div>
	</div>