<?php
include_once( 'init.php' );

// Prevents non-admin from editing other users settings
if ( !$current_user->has_cap( 'supportpressadmin' ) && $current_user->ID != $_GET['uid'] ) {
	wp_redirect( $site_url );
	exit;
}

include( 'header.php' );

$uid = (int) $_GET['uid'];
$this_user = get_user( $uid );
$message = '';

if ( isset( $_POST['save'] ) ) {

	$valid_roles = array( 'supporter', 'supportpressadmin' );
	$valid_onclose = array( 'inbox', 'nextticket' );
	$update_meta_flag = true;

	$display_name = isset( $_POST['displayname'] ) ? strip_tags( $_POST['displayname'] ) : '';
	$user_email   = isset( $_POST['email'] ) ? $_POST['email'] : '';
	$user_role    = in_array( $_POST['role'], $valid_roles ) ? $_POST['role'] : '';
	$meta_onclose = in_array( $_POST['onclose'], $valid_onclose ) ? $_POST['onclose'] : '';
	$signature = ( ! empty( $_POST['signature'] ) ) ? strip_tags( $_POST['signature'] ) : '';

	// Update on close setting
	if ( ! empty( $meta_onclose ) && isset( $this_user->onclose ) && $this_user->onclose != $meta_onclose ) {
		if ( update_usermeta( $uid, 'onclose', $meta_onclose ) )
			$message .= 'Ticket closed setting saved. ';
		else
			$message .= 'Error saving ticket closed setting. ';
	}

	// Update signature
	if ( ! empty( $signature ) && isset( $this_user->signature ) && $this_user->signature != $signature ) {
		if ( update_usermeta( $uid, 'signature', $signature ) )
			$message .= 'Signature updated. ';
		else
			$message .= 'Error saving signature. ';
	}

	// Update role
	if ( ! empty( $user_role ) && ! $this_user->has_cap( $user_role ) ) {
		// quick hack to prevent accidental lockouts. See:
		// http://supportpress.trac.wordpress.org/ticket/64
		if ( $current_user->ID == $this_user->ID ) {
			$message .= 'Error changing role. Demoting yourself is unhealthy!';
		}
		else {
			$this_user->set_role( $user_role );
			$message .= 'User role updated. ';
		}
	}

	// Validation
	if ( empty( $display_name ) ) {
		$message .= 'Display name is required. ';
		$update_meta_flag = false;
	}

	if ( empty( $user_email ) || ! is_email( $user_email ) ) {
		$message .= 'Email is required. ';
		$update_meta_flag = false;
	}

	// Update settings
	if ( ( $display_name != $this_user->display_name || $user_email != $this_user->user_email ) && $update_meta_flag ) {

		$update = array( 'display_name' => $display_name, 'user_email' => $user_email, 'plain_pass' => '' ); 

		if ( !empty( $_POST['password1'] ) && !empty( $_POST['password2'] ) && $_POST['password1'] == $_POST['password2'] )
			$update['user_pass'] = $_POST['password1'];

		$update_user = $wp_users_object->update_user( $uid, $update );

		if ( $update_user ) {
			$message .= 'User settings saved. ';
		} else {
			$message .= 'Error saving message: ' . $db->last_error . ' ';
		}

	}
}

if ( $message ) {
	echo '<div id="message">' . esc_html( $message ) . '</div>';
}

$updated_user = get_user( $uid );
?>

<form id="update-user" method="POST">
	<h2>Edit user:</h2>

	<p>
		<label>Username:</label>&nbsp;
		<?php echo esc_html( $updated_user->user_login ); ?>
	</p>
	<p>
		<label for="password1">Password:</label>
		<input type="password" name="password1" id="password1" value=""/>
	</p>
	<p>
		<label for="password2">Confirm Password:</label>
		<input type="password" name="password2" value=""/>
	</p>
	<p>
		<label id="displayname">Display Name:</label>
		<input type="text" name="displayname" id="displayname" value="<?php echo esc_html( $updated_user->display_name ); ?>" />
	</p>
	<p>
		<label for="email">Email:</label>
		<input type="text" name="email" id="email" value="<?php echo esc_html( $updated_user->user_email ); ?>" />
	</p>
	<p>
		<label for="role">Role:</label>
		<select name="role" id="role">
			<option value="supporter" <?php if ( !$updated_user->has_cap( 'supportpressadmin' ) ) echo 'selected="selected"'; ?>>Supporter</option>
			<option value="supportpressadmin" <?php if ( $updated_user->has_cap( 'supportpressadmin' ) ) echo 'selected="selected"'; ?>>Administrator</option>
		</select>
	</p>
	<p>
		<label for="onclose">After closing a ticket:</label>
		<select name="onclose" id="onclose">
			<option value="inbox" <?php if ( isset( $updated_user->onclose ) && 'inbox' == $updated_user->onclose ) 'selected="selected"'; ?>>return to the inbox</option>
			<option value="nextticket" <?php if ( isset( $updated_user->onclose ) && 'nextticket' == $updated_user->onclose ) 'selected="selected"'; ?>>continue to next available ticket</option>
		</select>
	</p>

	<p>
		<label for="signature">Signature:</label>
		<textarea name="signature" id="signature"><?php
			if ( isset( $updated_user->signature ) )
				echo esc_textarea( $updated_user->signature ); ?></textarea>
	</p>

	<input type="submit" name="save" value="Save" />

</form>

<?php
include( 'footer.php' );