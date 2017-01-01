<?php

if ( !defined('IS_INSTALLING') ) {
	die('If you need to reinstall or repair SupportPress, please edit config.php and uncomment the IS_INSTALLING constant.');
}

$step = 0;

$incs = get_included_files();

if ( version_compare(PHP_VERSION, '5.2.0') < 0  )
	$error = 'PHP version 5.2 or greater is required (you have PHP '.PHP_VERSION.')';
elseif ( !extension_loaded('mysql') || !is_callable('mysql_connect') )
	$error = 'The PHP mysql extension is required';
elseif ( !is_callable('mail') )
	$error = 'The PHP mail() function is required';
else
	$step = 1;

if ( $step >= 1 ) {
	if ( in_array( dirname(__FILE__) . '/config.php', $incs ) )
		$step = 3;

	if ( defined('DB_NAME') && defined('DB_USER') && defined('DB_HOST') && defined('DB_PASSWORD') )
		$step = 4;

	if ( $step >= 4 ) {
		$db->show_errors = true;

		// TODO Make this Alternative DB class aware or allow for the fact that the defines could be validly invalid
		if ( @mysql_connect( DB_HOST, DB_USER, DB_PASSWORD, true ) ) {
			$step = 5;
		} else {
			$step = 3;
			$error = "Error connecting to database: ".mysql_error();
		}

	}

	if ( $step >= 5 ) {
		$schemas = require('includes/schema.php');
		foreach ( $schemas as $table => $schema ) {
			$db->hide_errors();
			if ( $db->get_row("DESCRIBE {$db->$table}") )
				continue; // table exists

			$db->show_errors();
			if ( !$db->query( sprintf($schema, $db->$table) ) && mysql_error() ) {
				$error = "Error creating table {$db->$table}: ".mysql_error();
				break;
			}
		}

		if ( empty($error) ) {
			// Only create the user tables if we are going to use them
			if ( ! isset( $user_table_prefix ) ) {
				$schemas = require('includes/backpress-schemas/wp-users.php');
				foreach ( $schemas as $table => $schema ) {
					$db->hide_errors();
					if ( $db->get_var("DESCRIBE {$db->$table}") )
						continue; // table exists

					$db->show_errors();
					if ( !$db->query( sprintf($schema, $db->$table) ) && mysql_error() ) {
						$error = "Error creating table {$db->$table}: ".mysql_error();
						break;
					}
				}
			}

			if ( empty($error) )
				$step = 6;
		}
		$db->show_errors();
	}

	if ( $step >= 6 ) {
		$wp_users_object = new WP_Users( $db );
	}


	if ( $step >= 6 && !empty($_POST['admin_email']) && !empty($_POST['admin_pass']) ) {
		if ( !is_email($_POST['admin_email']) ) {
			$error = 'Invalid email: '.$_POST['admin_email'];
		} elseif ( !$wp_users_object->get_user('supportpressadmin') ) {
			$user_email = $_POST['admin_email'];
			$user_pass = $_POST['admin_pass'];

			$r = $wp_users_object->new_user( array(
				'user_login' => 'supportpressadmin',
				'user_nicename' => 'SupportPress Administrator',
				'user_email' => $user_email,
				'user_pass' => $user_pass,
				) );

			if ( is_wp_error($r) ) {
				$error = $r->get_error_message();
			} else {
				$step = 7;
			}
		}
	} elseif ( $step >= 6 && !empty( $_POST['admin_username'] ) ) {
		$user = new BP_User( $_POST['admin_username'] );
		if ( $user->ID ) {
			// Username is valid
			$step = 7;
		} else {
			$error = 'Username not found in the shared user table';
		}
	}

	if ( $step >= 6 ) {
		$_admin_username = 'supportpressadmin';
		if ( isset( $_POST['admin_username'] ) )
			$_admin_username = $_POST['admin_username'];

		$administrator = new BP_User( $_admin_username );

		if ( $administrator->ID ) {

			if ( !$administrator->has_cap('supportpressadmin') )
				$administrator->set_role('supportpressadmin');

			// fetch it again just to make sure it's in the db now. superstition?
			$administrator = new BP_User( $_admin_username );
			if ( $administrator->has_cap('supportpressadmin') )
				$step = 8;
		}
	}

}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="en" lang="en" xml‎ns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>SupportPress Installer</title>
<link rel="stylesheet" type="text/css" href="design.css" />
</head>
<body>

<h1>SupportPress Installer</h1>

<p>Welcome to the world's lamest installer.</p>



<?php


?>

<h2>Instructions:</h2>

<?php if ( $error ) { ?>
	<p class="error"><?php echo esc_html( $error ); ?></p>
<?php } ?>

<ol>

<li<?php if ( $step > 0 ) echo ' class="step-done"'; ?>>Confirm that your server meets the minimum system requirements.</li>

<li<?php if ( $step > 1 ) echo ' class="step-done"'; ?>>Create, or establish the location of, a MySQL database.<br />You can share a database with WordPress if desired, in which case SupportPress will use the same wp_user table.</li>

<li<?php if ( $step > 2 ) echo ' class="step-done"'; ?>>Copy <code>config-sample.php</code> to <code>config.php</code></li>

<li<?php if ( $step > 3 ) echo ' class="step-done"'; ?>>Edit <code>config.php</code> and set the DB_NAME, DB_USER, DB_HOST and DB_PASSWORD constants as appropriate.
<?php if ( !strpos($site_domain, $_SERVER['HTTP_HOST']) || $site_path != dirname($_SERVER['SCRIPT_NAME']) ) { ?>
Also set these two values:<br />
<code><pre>$site_domain = 'http://<?php echo $_SERVER['HTTP_HOST']; ?>';
$site_path = '<?php dirname($_SERVER['SCRIPT_NAME']); ?>';
</pre></code>
<?php } ?>
</li>

<li<?php if ( $step > 4 ) echo ' class="step-done"'; ?>><a href="?">Refresh this page</a> and check that the database is accessible.</li>

<li<?php if ( $step > 5 ) echo ' class="step-done"'; ?>>Create the tables.</li>

<li<?php if ( $step > 6 ) echo ' class="step-done"'; ?>>Create the <code>supportpressadmin</code> user<?php if ( isset( $user_table_prefix ) ) : ?> or enter an existing username<?php endif;?>.<br />
<?php if ( $step == 6 ) { ?>
<form method="post">
	<label>Email address: <input type="text" name="admin_email" value="<?php echo isset( $_POST['admin_email'] ) ? esc_attr( $_POST['admin_email'] ) : ''; ?>" /></label><br />
	<label>Password: <input type="text" name="admin_pass" value="<?php echo isset( $_POST['admin_pass'] ) ? esc_attr( $_POST['admin_pass'] ) : ''; ?>" /></label><br />
	<input type="submit" name="add_user" value="Create" />
</form>
<?php if ( isset( $user_table_prefix ) ) : ?>
<form method="post">
	<label>Username: <input type="text" name="admin_username" value="<?php echo isset( $_POST['admin_username'] ) ? esc_attr( $_POST['admin_username'] ) : ''; ?>" /></label><br />
	<input type="submit" name="use_user" value="Use Existing" />
</form>
<?php endif; ?>
<?php } ?>

</li>

<li<?php if ( $step > 7 ) echo ' class="step-done"'; ?>>Give admin user the correct role.</li>

<li>Edit <code>config.php</code> and comment out the appropriate line, like this:<br />
<code># define( 'IS_INSTALLING', true );</code>
</li>

</ol>

</body>
</html>
<?php

// so we don't continue with init.php
exit;