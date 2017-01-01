<?php

define( 'SP_IS_LOGIN', true );

include('init.php');
$action = @$_REQUEST['action'];
$error = '';

header('Content-Type: text/html; charset=utf-8');

switch($action) {

case 'logout':

	$wp_auth_object->clear_auth_cookie();
	nocache_headers();

	$redirect_to = $site_url . '/login.php';
	if ( isset($_REQUEST['redirect_to']) )
		$redirect_to = preg_replace('|[^a-z0-9-~+_.?#=&;,/:]|i', '', $_REQUEST['redirect_to']);

	wp_redirect($redirect_to);
	exit();

break;

case 'login' :
default:

	$user_login = '';
	$user_pass = '';
	$using_cookie = false;
	if ( !isset( $_REQUEST['redirect_to'] ) )
		$redirect_to = $site_url;
	else
		$redirect_to = $_REQUEST['redirect_to'];
	$redirect_to = preg_replace('|[^a-z0-9-~+_.?#=&;,/:]|i', '', $redirect_to);

	if ( $_POST ) {
		$user_login = $_POST['log'];
		$user_login = sanitize_user( $user_login );
		$user_pass  = $_POST['pwd'];
		$rememberme = @$_POST['rememberme'];
	} elseif ( !empty($_COOKIE) ) {
		if ( !empty($_COOKIE[AUTH_COOKIE]) )
			$using_cookie = true;
	}

	if ( $user_login && $user_pass && wp_login($user_login, $user_pass) ) {
		$user = new WP_User( $user_login );
		$wp_auth_object->set_auth_cookie( $user->ID, $rememberme ? time() + 1209600 : 0, $rememberme ? time() + 1209600 : 0, 'auth' );
		$wp_auth_object->set_auth_cookie( $user->ID, $rememberme ? time() + 1209600 : 0, $rememberme ? time() + 1209600 : 0, 'logged_in' );
		wp_redirect($redirect_to);
		exit;
	}

	if ( $using_cookie )
		$error = __('Your session has expired.');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>SupportPress &rsaquo; <?php _e('Login') ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link type="text/css" href="design.css" media="screen" rel="stylesheet" />
	<script type="text/javascript">
	function focusit() {
		document.getElementById('log').focus();
	}
	window.onload = focusit;
	</script>
</head>
<body class="login">

<div id="login">
<h1><a href="">SupportPress</a></h1>

<form name="loginform" id="loginform" action="login.php" method="post">
	<?php
	if ( $error )
		echo '<div id="login_error">'. $error . '</div>';
	?>
	<div class="details">
		<p><label><?php _e('Username:') ?><br /><input type="text" name="log" id="log" value="<?php echo wp_specialchars(stripslashes($user_login), 1); ?>" size="20" tabindex="1" /></label></p>
		<p><label><?php _e('Password:') ?><br /> <input type="password" name="pwd" id="pwd" value="" size="20" tabindex="2" /></label></p>
	</div>
	<p>
	  <label><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="3" />
	  <?php _e('Remember me'); ?></label></p>
	<p class="submit">
		<input type="submit" name="submit" id="submit" value="<?php _e('Login'); ?> &raquo;" tabindex="4" />
		<input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
	</p>
</form>

</div>

</body>
</html>
<?php

break;
} // end action switch