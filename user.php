<?php
include_once( 'init.php' );

// Is this an admin only page?
if ( !$current_user->has_cap('supportpressadmin') ) {
	wp_redirect( $site_url );
	exit;
}

include( 'header.php' );

$message = null;

if (  $current_user->has_cap( 'supportpressadmin' ) && ! empty( $_POST['add'] ) ) {
	$user = get_user( $_POST['new_email'], array('by' => 'email') );
	if ( !$user )
		$user = get_user( $_POST['new_username'], array('by' => 'login') );

	// Are we promoting an existing WP user to supportpress, or creating a new user record?
	if ( !empty($user->ID) ) {
		$user->set_role( $_POST['new_role'] );
		$message = "User $user->user_login ($user->user_email) promoted to {$_POST['new_role']}";
		wp_cache_delete( 'support_user_ids', 'supportpress' );
	} else {
		$r = $wp_users_object->new_user( array(
			'user_login' => $_POST['new_username'],
			'user_email' => $_POST['new_email'],
			) );

		if ( !is_wp_error($r) ) {

			$user = get_user( $r['user_login'], array('by' => 'login') );
			$user->set_role( $_POST['new_role'] );

			$body = <<<EOF
Your new SupportPress login details:

Username: {$r['user_login']}
Password: {$r['plain_pass']}

EOF;
			wp_mail( $r['user_email'], 'SupportPress Login', $body );

			$message = "Login details were sent to {$r['user_email']}";
			wp_cache_delete( 'support_user_ids', 'supportpress' );
		} else {
			$message = $r->get_error_message();
		}
	}
}

if ( !empty( $_POST['remove-users'] ) && $_POST['remove-users'] == 'Remove' && !empty( $_POST['user_ids'] ) ) {
  if (count($_POST['user_ids']) == 1) {
  	$message .= "Removing user: ";
	} else {
  	$message .= "Removing users: ";  	
	}

	foreach ( (array) $_POST['user_ids'] as $user_id ) {
		$user_id = (int) $user_id;

		$user = get_user( $user_id );

		// if we don't have a valid user skip to the next one
		if ( !$user ) {
			continue;
		}

		// If we are using a shared user table just remove the role
		if ( isset( $user_table_prefix ) ) {
			$user->set_role( '' );
		} else {
			$local_wp_users = new WP_Users( $db );
			$local_wp_users->delete_user( $user_id );
		}

		$message .= $user->user_login . ' ';
	}
}

if ( $message ) {
	echo '<div id="message">' . esc_html( $message ) . '</div>';
}

?>

<table cellspacing='8'>
<thead>
<tr class="tablehead">
<th><input type="checkbox" name="checkall" value="none" id="checkall" /></th>
<th>Username</th>
<th>Email</th>
<th>Name</th>
<th>Registered</th>
<th>Role</th>
</tr>
</thead>
<tbody>

<form method="POST">
<?php

$support_users = get_support_user_ids();

foreach ( (array)$support_users as $u ) {
	$user = get_user($u);

	echo "<tr>";
	// can't remove self
	if ( ( $u == $current_user->ID ) || ! $current_user->has_cap( 'supportpressadmin' ) )
		echo '<td>&nbsp;</td>';
	else
		echo "<td><input type='checkbox' name='user_ids[]' value='$u' class='mcheck' id='mcheck$u' /></td>";

	echo "<td><a href='user-edit.php?uid=$user->ID'>$user->user_login</a></td>";
	echo "<td>$user->user_email</td>";
	echo "<td>$user->display_name</td>";
	echo "<td>$user->user_registered</td>";
	echo "<td>". join(',', (array)$user->roles ) . "</td>";
	echo "</tr>";
}
?>
</tbody>
<tfoot>
<tr>
<td colspan="2">
<label>With checked:</label>
<input type="submit" name="remove-users" value="Remove" class="enablewhenselected" />
</form>
</td>
</tr>
</tfoot>
</table>
<?php if ( $current_user->has_cap( 'supportpressadmin' ) ) : ?>
	<form method="POST">
	<p>Add new user:<br />
	<label>Username: <input type="text" name="new_username" /></label><br />
	<label>Email: <input type="text" name="new_email" /></label><br />
	<label>Role: <select name="new_role">
	<option value="supporter">Supporter</option>
	<option value="supportpressadmin">Administrator</option>
	</select></label><br />
	<input type="submit" name="add" value="Add" />
	</p>
	</form>
<?php endif; ?>

<?php
include( 'footer.php' );