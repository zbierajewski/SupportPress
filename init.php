<?php

if ( !empty($_GET['queries']) )
	define('SAVEQUERIES', true);

require_once( 'base-init.php' );

// Upgrade the db?
if ( !defined('IS_INSTALLING') && !defined( 'SP_NO_AUTOMATIC_SCHEMA_UPGRADES' ) )
	sp_upgrade();

// the installer kicks off here, so anything needed for installation has to happen above
if ( defined('IS_INSTALLING') && IS_INSTALLING )
	require_once('installer.php');

// default roles
create_role( 'supportpressadmin', 'Administrator' );
create_role( 'supporter', 'Supporter' );


if ( !defined('AUTH_COOKIE') )
	define('AUTH_COOKIE', 'sp');
if ( !defined('LOGGED_IN_COOKIE') )
	define('LOGGED_IN_COOKIE', 'sp_logged_in');
if ( !defined('COOKIEPATH') )
	define('COOKIEPATH', '/' );
if ( !defined('COOKIE_DOMAIN') )
	define('COOKIE_DOMAIN', false);
if ( !defined('SP_VERSION') )
	define('SP_VERSION', '1.0.2');

if ( !defined( 'WP_AUTH_COOKIE_VERSION' ) )
	define( 'WP_AUTH_COOKIE_VERSION', 1 ); // change to 2 for wp 2.8

$wp_users_object = new WP_Users( $db );

$cookies['auth'][] = array(
	'domain' => COOKIE_DOMAIN,
	'path' => COOKIEPATH,
	'name' => AUTH_COOKIE
);
$cookies['logged_in'][] = array(
	'domain' => COOKIE_DOMAIN,
	'path' => COOKIEPATH,
	'name' => LOGGED_IN_COOKIE
);

$wp_auth_object = new WP_Auth( $db, $wp_users_object, $cookies );

$current_user = $wp_auth_object->get_current_user();

if ( !@constant( 'SP_IS_LOGIN' ) && ( !$current_user || (!$current_user->has_cap( 'supporter' ) && !$current_user->has_cap('supportpressadmin')) ) ) {
	$path = $_SERVER['REQUEST_URI'];
	wp_redirect("$site_url/login.php?redirect_to=" . urlencode($site_domain . $path ) );
	exit();
}

if ( !is_ssl() && substr($site_url, 0, 5) == 'https' ) {
	wp_redirect( $site_url );
	exit;
}

// It's safe to show errors now that we're logged in
$db->show_errors();
