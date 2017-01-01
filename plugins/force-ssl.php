<?php

if ( defined( 'FORCE_SSL' ) && FORCE_SSL && isset( $_SERVER["SERVER_PORT"] ) ) {
	$is_using_ssl = false;
	if ( $_SERVER["SERVER_PORT"] == 443 )
		$is_using_ssl = true;
	if ( !$is_using_ssl && isset( $_SERVER["HTTPS"] )  ) {
		if ( $_SERVER["HTTPS"] == '1' || strtolower( $_SERVER["HTTPS"] == 'on' ) )
			$is_using_ssl = true;
	}
	if ( !$is_using_ssl ) {
		ob_end_clean();
		header( sprintf(
			'Location: https://%s%s',
			$_SERVER['HTTP_HOST'],
			$_SERVER['REQUEST_URI']
		) );
		die();
	}
}
