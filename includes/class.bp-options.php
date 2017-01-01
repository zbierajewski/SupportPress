<?php

require_once('backpress/interface.bp-options.php');

/* a stub options class that provides static default values.  to use it in a simple application, derive a child class named BP_Options:

include_once('class.bp-options.php');
class BP_Options extends BP_Options_Stub {
}

*/

class BP_Options_Stub implements BP_Options_Interface {

	private static $_defaults = array(
		'application_id'     => 1,
		'aplication_uri'     => '',
		'cron_uri'           => '',
		'cron_check'         => '',
		'wp_http_version'    => '1.0',
		'hash_function_name' => 'md5',
		'language_locale'    => '',
		'language_directory' => '',
		'charset'            => 'UTF-8',
		'gmt_offset'         => 0,
		'timezone_string'    => 'GMT',
	);

	function prefix() {
		return '';
	}

	function get( $o ) {
		if ( array_key_exists( $o, self::$_defaults ) )
			return self::$_defaults[ $o ];
		return false;
	}

	function add( $o, $v ) {
		if ( !array_key_exists( $o, self::$_defaults ) )
			self::$_defaults[ $o ] = $v;
	}

	function update( $o, $v ) {
		self::$_defaults[ $o ] = $v;
	}

	function delete( $o ) {
		unset( self::$_defaults[ $o ] );
	}
}