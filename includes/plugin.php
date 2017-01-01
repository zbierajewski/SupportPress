<?php

// plugin loader and support functions

function sp_load_plugins($plugindir) {
	$dir = realpath($plugindir);
	if ( !$dir || !is_dir($dir) )
		return false;

	$plugins = glob("{$dir}/[a-zA-Z9-0]*.php");
	foreach ( $plugins as $p )
		require_once( $p );
}