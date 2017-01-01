<?php

if ( !defined( 'ENABLE_KISSMETRICS' ) || !ENABLE_KISSMETRICS )
	return false;

/*
|---------------------------------------------------------------
| Setup
|---------------------------------------------------------------
*/
// You should be set after adding your API Key below.  
// If you use something other than the users email in KissMetrics, 
// you'll want to modify the identity function in the WPCOM_Kissmetrics class below.

define('WPCOM_KISSMETRICS_API_KEY', '');

/*
|---------------------------------------------------------------
| Custom SP event calls
| NO need to change anything below unless you know what yo're doing
|---------------------------------------------------------------
*/

// Customer replies to existing thread
function sp_km_customer_thread_reply( $thread_id, $message_id, $email ) {
	$props = array(
		'Thread ID' => $thread_id
	);
	
	kissmetrics_record_event( $email, "Support ticket reply", $props );
}
add_action( 'processmail_thread_updated', 'sp_km_customer_thread_reply', 10, 2 );

// Customer opens new thread
function sp_km_new_thread( $thread_id, $message_id, $email ) {
	$props = array(
		'Thread ID' => $thread_id
	);
	
	kissmetrics_record_event( $email, "Support ticket reply", $props );
}
add_action( 'processmail_thread_created', 'sp_km_new_thread', 10, 2 );

// User (employee) replies to thread
function sp_km_user_thread_reply( $thread_id, $email ) {
	$props = array(
		'Thread ID' => $thread_id
	);
	
	kissmetrics_record_event( $email, "Support ticket reply", $props );
}
add_action( 'user_thread_reply', 'sp_km_user_thread_reply', 10, 2 );

/*
|---------------------------------------------------------------
| KissMetrics functions
|---------------------------------------------------------------
*/

/**
 * Helper function: Record events
 * Indeded to be used instead of accessing WPCOM_Kissmetrics directly
 */
function kissmetrics_record_event( $identity, $event, $properties = array(), $api_key = WPCOM_KISSMETRICS_API_KEY ) {
	WPCOM_Kissmetrics::init( $api_key );
	WPCOM_Kissmetrics::identify( $identity );
	WPCOM_Kissmetrics::record( $event, $properties );
}

/**
 * Forked from https://github.com/kissmetrics/KISSmetrics/blob/master/km.php
 */
class WPCOM_Kissmetrics {
	static $id             = null;
	static $key            = null;
	static $time           = null;
	static $queued_queries = array();

	static $query_type_mapping = array(
		'record' => 'e', // Record events
		'set'    => 's', // Set properties
		'alias'  => 'a', // Alias user Id's
	);

	/**
	 * Setup Kissmetrics API key and timestamp for the data to be recorded
	 * Uses 'kissmetrics_api_key' fitler
	 *
	 * This is required before making any queries
	 */
	static function init( $key = null, $time = null ) {
		// Make sure Kissmetrics has not been disabled
		if ( !function_exists( 'kissmetrics_is_enabled' ) || !kissmetrics_is_enabled() ) {
			return;
		}

		self::$key  = apply_filters( 'kissmetrics_api_key', $key );
		self::$time = ( is_int( $time ) ) ? $time : time();
	}

	/**
	 * Identify the user we to record data about
	 * This is required before recording events or properties
	 *
	 * On WordPress.com we use usernames for identifiers
	 * If you pass an email or user_id it will get automatically converted to a username
	 */
	static function identify( $id ) {
		if ( ! $id )
			return;

		self::$id = $id;
	}

	/**
	 * Record an event for the identified user
	 *
	 * $action Unique name for the event being recorded
	 * $properties Metadata about the event being recorded, default empty
	 * $prefix_properties Whether or not to prefix property names with event name,
	 *   default true to avoid conflicting property names
	 */
	static function record( $action, $props = array(), $prefix_properties = true ) {
		if ( ! self::is_initialized_and_identified() )
			return;

		if ( $prefix_properties )
			$props = self::prefix_properties( $props, $action );

		// _n is the Kissmetrics API property for event name
		$data = array_merge( $props, array( '_n' => $action ) );

		self::generate_query( self::$query_type_mapping['record'], $data );
	}

	/**
	 * Set properties about the identified user
	 *
	 * $properties Array of properties and values with named indices
	 */
	static function set( $properties = array() ) {
		if ( ! self::is_initialized_and_identified() )
			return;

		if ( ! $properties || ! is_array( $properties ) )
			return;

		// Arrays should not be 0-indexed because indices are used as property names
		if ( array_key_exists( 0, $properties ) )
			return;

		self::generate_query( self::$query_type_mapping['set'], $properties );
	}

	/**
	 * Alias a new user identiy to another, combines both users' data in Kissmetrics
	 *
	 * Argument order does not matter
	 */
	static function alias( $name, $alias_to ) {
		if ( $name == $alias_to )
			return;

		if ( ! self::is_initialized() )
			return;

		$array = array(
			'_p' => $name,
			'_n' => $alias_to,
		);

		self::generate_query( self::$query_type_mapping['alias'], $array, false );
	}

	/**
	 * Used to avoid conflicting like-named properties of different events
	 */
	static function prefix_properties( $props = array(), $prefix = '' ) {
		foreach ( $props as $key => $value ) {
			unset( $props[ $key ] );
			$props["{$prefix} | {$key}"] = $value;
		}

		return $props;
	}

	/**
	 * Clear API key and identity
	 * Runs automatically after each query
	 */
	static protected function reset() {
		self::$id  = null;
		self::$key = null;
	}

	/**
	 * Check to make sure both the API key and User ID are set, boolean
	 */
	static protected function is_initialized_and_identified() {
		return self::is_initialized() && self::$id;
	}

	/**
	 * Check that an API key is set, boolean
	 */
	static protected function is_initialized() {
		return (bool) self::$key;
	}

	/**
	 * Create the query string we're going to use to request the Kissmetrics API
	 */
	static protected function generate_query( $type, $data ) {

		$data['_k'] = self::$key;  // API key
		$data['_t'] = self::$time; // Timestamp
		$data['_d'] = 1;           // Force Kissmetrics to use the time value we pass
		$data['_p'] = self::$id;   // User identity

		$query = '/' . $type . '?' . http_build_query( $data, '', '&' );

		// Encode spaces as %20 instead of +
		// PHP 5.4 supports a fourth argument (enc_type) to do this more gracefully
		// See: http://php.net/manual/en/function.http-build-query.php
		$query = str_replace( '+', '%20', $query );

		self::queue_query( $query );
		self::reset();
		do_action( 'kissmetrics_generate_query', array_search( $type, self::$query_type_mapping ), $data );
	}

	/**
	 * Add the query to a queue and set the queue to be processed on shutdown
	 */
	static protected function queue_query( $query ) {
		self::$queued_queries[] = $query;
		add_action( 'shutdown', array( __CLASS__, 'send_queued_queries' ) );
	}

	/**
	 * Process the queued queries
	 */
	static function send_queued_queries() {
		foreach ( (array) self::$queued_queries as $query )
			self::send_query( $query );

		// Kill the queue after its processed in case this gets called multiple times
		self::$queued_queries = array();
	}

	/**
	 * Make an HTTP request to the Kissmetrics API
	 */
	static protected function send_query( $query ) {
		$request_url = 'http://trk.kissmetrics.com:80' . $query;
		wp_remote_get( $request_url, array(
			'timeout'  => 1,
		) );
	}
}