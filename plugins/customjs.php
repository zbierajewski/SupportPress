<?php

if ( !defined( 'ENABLE_CUSTOM_JS' ) || !ENABLE_CUSTOM_JS )
	return false;

function set_custom_js( $new_js ) {
	// Note using threadmeta for thread id 1 here is a hack since there is not, yet, an options table...
	global $wp_object_cache;
	if ( empty( $wp_object_cache ) )
		wp_cache_init();
	// Thank you SP for $meta_value = stripslashes_deep($meta_value); in includes/wp-meta.php
	update_metadata( 'thread', 1, 'custom_js', chr(7).chr(9).base64_encode( $new_js ) );
	// Thank you SP for inconsistent cache invalidation
	wp_cache_delete( 1, 'thread_meta' );
}

function get_custom_js() {
	global $wp_object_cache;
	if ( empty( $wp_object_cache ) )
		wp_cache_init();
	// Thank you SP for inconsistent cache invalidation
	wp_cache_delete( 1, 'thread_meta' );
	$js = get_metadata( 'thread', 1, 'custom_js', true );
	// Thank you SP for $meta_value = stripslashes_deep($meta_value); in includes/wp-meta.php
	if ( is_string( $js ) && substr( $js, 0, 2 ) == chr(7).chr(9) )
		$js = base64_decode( substr( $js, 2 ) );
	return $js;
}

add_action( 'menu-below', 'custom_js_menu' );
function custom_js_menu() {
	echo '<ul class="menu"><li><a href="#" onClick="editcustomjs(); return false;">Edit Custom JS</a></li></ul>';
}

add_action( 'sp_head', 'custom_js_head' );
function custom_js_head() {
	printf( "\n<script type='text/javascript'>\n%s\n</script>\n", get_custom_js() );
	?>
	<script type='text/javascript'>
		function editcustomjs() {
			window.open ("?editcustomjs", 'editjs', 'width=400,height=550');
		}
	</script>
	<?php
}

if ( isset( $_GET['editcustomjs'] ) ) {
	$saved = 0;
	if ( isset( $_POST['newcustomjs'] ) ) {
		set_custom_js( $_POST['newcustomjs'] );
		$saved = 1;
	}
?><html>
<head>
<style type="text/css">
	input, a { float: right; }
	a { margin-right: 15px; }
	textarea { width: 100%; height: 400px; }
	input { float: right; }
</style>
</head>
<body>
<?php if ( $saved == 1 ) { echo '<h3>JS Saved ('.date('r').')</h3>'; } ?>
<form method="POST">
<textarea name="newcustomjs"><?php echo esc_textarea( get_custom_js() ); ?></textarea><br/>
<input type="submit">
<a href="javascript:window.close();">Close This Window</a>
</form>
</body>
</html>
<?php
die();
}