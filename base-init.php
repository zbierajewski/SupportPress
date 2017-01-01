<?php

// basic init - include core files and establish a db session only.
// this is designed to work for non-web scripts also, so authentication needs to be handled separately for web pages

if ( !defined('ABSPATH') )
	define( 'ABSPATH', dirname(__FILE__) );
if ( !defined('BACKPRESS_PATH') )
	define( 'BACKPRESS_PATH', ABSPATH . '/includes/backpress/' );

require_once( ABSPATH . '/includes/misc.php' );
time_since(true);

if ( is_file( ABSPATH . '/config.php' ) )
	include_once( ABSPATH . '/config.php' );
elseif ( is_file( dirname( dirname(__FILE__) ) . '/sp-config.php' ) )
	include_once( dirname( dirname(__FILE__) ) . '/sp-config.php' );
else
	define( 'IS_INSTALLING', true );

$error_level = error_reporting();

require_once( ABSPATH . '/includes/constants.php' );

require_once( ABSPATH . '/includes/wp-functions.php' );

// temporarily suppress deprecation warnings from backpress
if ( defined( 'E_DEPRECATED' ) )
	error_reporting( $error_level & ~E_DEPRECATED & ~E_STRICT );

require_once( BACKPRESS_PATH . '/functions.compat.php' );
require_once( BACKPRESS_PATH . '/functions.formatting.php' );
require_once( BACKPRESS_PATH . '/functions.core.php' );
require_once( BACKPRESS_PATH . '/functions.kses.php' );
require_once( BACKPRESS_PATH . '/functions.plugin-api.php' );

require_once( BACKPRESS_PATH . '/class.wp-error.php' );

//Define the full path to the database class
if ( !defined( 'SP_DATABASE_CLASS_INCLUDE' ) ) {
	define( 'SP_DATABASE_CLASS_INCLUDE', BACKPRESS_PATH . '/class.bpdb-multi.php' );
}

// Define the name of the database class
if ( !defined( 'SP_DATABASE_CLASS' ) ) {
	define( 'SP_DATABASE_CLASS', 'BPDB_Multi' );
}

// Load the database class
if ( SP_DATABASE_CLASS_INCLUDE ) {
	require_once( SP_DATABASE_CLASS_INCLUDE );
}

if ( defined('DB_HOST') ) {
	$spdb_class = SP_DATABASE_CLASS;
	$db = new $spdb_class ( array(
		'name' => DB_NAME,
		'host' => DB_HOST,
		'user' => DB_USER,
		'password' => DB_PASSWORD,
		'errors' => 'suppress',
	) );


	$db->set_prefix($table_prefix);
	// standard prefix for SupportPress tables
	$db->set_prefix(
		$table_prefix,
		array(
			'users',
			'usermeta',
			'threads',
			'threadmeta',
			'messages',
			'messagemeta',
			'tags',
			'predefined_messages',
			'attachments'
		)
	);

	$db->field_types = array(
		'thread_id' => '%d', 'from_user_id' => '%d',
		'messages' => '%d', 'message_id' => '%d',
		'uid' => '%d', 'id' => '%d',
	);

	// use wp_ for user tables
	if ( isset($user_table_prefix) )
		$db->set_prefix( $user_table_prefix, array('users', 'usermeta') );

	// optional separate config for user db
	if ( defined('USER_DB_HOST') ) {
		$db->add_db_server( 'user', array(
			'name' => USER_DB_NAME,
			'host' => USER_DB_HOST,
			'user' => USER_DB_USER,
			'password' => USER_DB_PASSWORD,
			'errors' => 'suppress',
		));

		// these two tables are in the user db
		$db->add_db_table( 'user', $db->users );
		$db->add_db_table( 'user', $db->usermeta );
	}
}

// Compatibility with WP function global object usage
global $wpdb;
$wpdb = &$db;

// Compatibility with BP function global object usage
global $bpdb;
$bpdb = &$db;

require_once( BACKPRESS_PATH . '/functions.bp-options.php' );
require_once( ABSPATH . '/includes/class.bp-options.php' );
if ( class_exists('memcache') )
	require_once( BACKPRESS_PATH . '/loader.wp-object-cache-memcached.php' );
else
	require_once( BACKPRESS_PATH . '/loader.wp-object-cache.php' );
require_once( BACKPRESS_PATH . '/class.wp-pass.php' );
require_once( BACKPRESS_PATH . '/class.bp-roles.php' );
require_once( BACKPRESS_PATH . '/class.bp-user.php' );
require_once( BACKPRESS_PATH . '/class.wp-users.php' );
require_once( BACKPRESS_PATH . '/class.wp-auth.php' );
require_once( ABSPATH . '/includes/wp-user.php' );

require_once( ABSPATH . '/includes/crud.php' );
require_once( ABSPATH . '/includes/form.php' );
require_once( ABSPATH . '/includes/support-functions.php' );
require_once( ABSPATH . '/includes/upgrade.php' );
require_once( ABSPATH . '/includes/wp-meta.php' );

// Load plugins
require_once( 'includes/plugin.php' );
sp_load_plugins( 'plugins/' );
if ( defined( 'EXTRA_PLUGINS' ) && is_dir( EXTRA_PLUGINS ) )
    sp_load_plugins( EXTRA_PLUGINS );

error_reporting( $error_level );

wp_cache_init();

add_filter( 'sanitize_title', 'sanitize_title_with_dashes' );

class BP_Options extends BP_Options_Stub {
}

// auth functions die if this isn't set
backpress_add_option( 'hash_function_name', 'sp_hash' );


function __( $str ) {
	return $str;
}

function _e( $str ) {
	echo $str;
}

function sp_hash( $s ) {
	return WP_Pass::hash_password( $s );
}