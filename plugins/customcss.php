<?php

if ( !defined( 'ENABLE_CUSTOM_CSS' ) || !ENABLE_CUSTOM_CSS )
	return false;

function set_custom_css( $new_css ) {
	// Note using threadmeta for thread id 1 here is a hack since there is not, yet, an options table...
	global $wp_object_cache;
	if ( empty( $wp_object_cache ) )
		wp_cache_init();
	// Thank you SP for $meta_value = stripslashes_deep($meta_value); in includes/wp-meta.php
	update_metadata( 'thread', 1, 'custom_css', chr(7).chr(9).base64_encode( $new_css ) );
	// Thank you SP for inconsistent cache invalidation
	wp_cache_delete( 1, 'thread_meta' );
}

function get_custom_css() {
	global $wp_object_cache;
	if ( empty( $wp_object_cache ) )
		wp_cache_init();
	// Thank you SP for inconsistent cache invalidation
	wp_cache_delete( 1, 'thread_meta' );
	$css = get_metadata( 'thread', 1, 'custom_css', true );
	// Thank you SP for $meta_value = stripslashes_deep($meta_value); in includes/wp-meta.php
	if ( is_string( $css ) && substr( $css, 0, 2 ) == chr(7).chr(9) )
		$css = base64_decode( substr( $css, 2 ) );
	return $css;
}

add_action( 'menu-below', 'custom_css_menu' );
function custom_css_menu() {
	echo '<ul class="menu"><li><a href="#" onClick="editcustomcss(); return false;">Edit Custom CSS</a></li></ul>';
}

add_action( 'sp_head', 'custom_css_head' );
function custom_css_head() {
	printf( "\n<style type='text/css'>\n%s\n</style>\n", get_custom_css() );
	?>
	<script type='text/javascript'>
		function editcustomcss() {
			window.open ("?editcustomcss", 'editcss', 'width=400,height=550');
		}
	</script>
	<?php
}

if ( isset( $_GET['editcustomcss'] ) ) {
	$saved = 0;
	if ( isset( $_POST['newcustomcss'] ) ) {
		set_custom_css( $_POST['newcustomcss'] );
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
<?php if ( $saved == 1 ) { echo '<h3>CSS Saved ('.date('r').')</h3>'; } ?> 
<form method="POST">
<textarea name="newcustomcss"><?php echo htmlentities( get_custom_css() ); ?></textarea><br/>
<input type="submit">
<a href="javascript:window.close();">Close This Window</a>
</form>
</body>
</html>
<?php
die();
}