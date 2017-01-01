</div> <!-- frame -->
</div> <!-- wrap -->
<?php
if ( isset( $_GET['t'] ) )
	include_once( 'sidebar-thread.php' );
?>
<div class='footer'>
<?php
$run_time = microtime(true) - $init_time;
echo '<!-- Runtime: ' . sprintf('%0.5f', $run_time) . ' -->';
if ( SAVEQUERIES ) {
	echo "<pre>";
	foreach ( $db->queries as $q )
		printf("%0.2f ms: %s\n", $q[1] * 1000, $q[0]);
	echo '</pre>';
	echo "<p>Runtime: $run_time</p>";
	if ( isset( $db->query_time ) ) {
		echo '<!-- Querytime: ' . sprintf('%0.5f', $db->query_time) . ' -->';
	}
}
?>
</div>
</body>
</html>