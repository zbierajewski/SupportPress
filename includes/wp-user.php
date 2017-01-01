<?php

// Backpress compat
class WP_User extends BP_User {
	function BP_User( $id, $name = '' ) {
		parent::WP_User( $id, $name );
	}
}

function get_user( $user_id, $args = null ) {
	global $wp_users_object;
	$user = $wp_users_object->get_user( $user_id, $args );
	if ( is_wp_error($user) )
		return false;
	if ( !empty($user->ID) )
		return new BP_User( $user->ID );
	return false;
}

// All the user crap imported from WP

function update_usermeta( $id, $meta_key, $meta_value ) {
	global $wp_users_object;
	$return = $wp_users_object->update_meta( compact( 'id', 'meta_key', 'meta_value' ) );
	if ( is_wp_error( $return ) )
		return false;
	return $return;
}

function delete_usermeta( $id, $meta_key, $meta_value = null ) {
	global $wp_users_object;
	$return = $wp_users_object->delete_meta( compact( 'id', 'meta_key', 'meta_value' ) );
	if ( is_wp_error( $return ) )
		return false;
	return $return;
}

if ( !function_exists('is_user_logged_in') ) :
function is_user_logged_in() {
	global $current_user;

	if ( $current_user->id == 0 )
		return false;
	return true;
}
endif;

if ( !function_exists('wp_login') ) :
function wp_login($username, $password, $already_md5 = false) {
	global $db, $error;

	if ( '' == $username )
		return false;

	if ( '' == $password ) {
		$error = __('<strong>Error</strong>: The password field is empty.');
		return false;
	}

	$user = new WP_User( $username );

	if (!$user || !$user->ID) {
		$error = __('<strong>Error</strong>: Wrong username.');
		return false;
	}

	if ( !WP_Pass::check_password( $password, $user->data->user_pass, $user->ID ) ) {
		$error = __('<strong>Error</strong>: Incorrect password.');
		$pwd = '';
		return false;
	}

	if ( !$user->has_cap( 'supporter' ) && !$user->has_cap( 'supportpressadmin' ) )
		return false;

	return true;
}
endif;

function create_role( $name, $display_name, $capabilities = array() ) {
	global $wp_roles, $db;
	if ( ! isset( $wp_roles ) )
		$wp_roles = new BP_Roles( $db );

	$wp_roles->add_role( $name, $display_name, $capabilities );
}