<?php

if ( !defined( 'ENABLE_FAUXLDERS' ) || !ENABLE_FAUXLDERS )
	return false;

if ( count( preg_grep( '/imap.pull/', $_SERVER ) ) )
	return false;

if ( !function_exists( 'fauxlder_list' ) ) {
	function fauxlder_list() {
		global $fauxlder_list;
		if ( !is_array( $fauxlder_list ) )
			$fauxlder_list = array();
		return apply_filters( 'fauxlder_list', $fauxlder_list );
	}
}

function fauxlder_message_count( $fauxlder ) {
	global $db;
	$fauxlders = fauxlder_list();
	if ( empty( $fauxlders[$fauxlder] ) )
		return 0;
	$like = ' \'%'.$db->escape($fauxlders[$fauxlder]).'%\' ';
	return $db->get_var( "SELECT COUNT(thread_id) FROM $db->threads WHERE `state` = 'open' AND `subject` LIKE $like" );
}

add_action( 'sp_head', 'display_fauxlder_menu_items' );
function display_fauxlder_menu_items() {
	global $site_url;
	$faulders = fauxlder_list();
	$display = array();
	foreach( fauxlder_list() as $fauxlder => $like )
		$display[$fauxlder] = fauxlder_message_count($fauxlder);
	arsort( $display );
	?>
	<style>
		div.leftcol #fauxlders a { font-size: 0.8em; white-space:nowrap; }
		div.leftcol #fauxlders { margin-top: 0; margin-bottom: 0; margin-right: 0; }
		<?php if ( !empty( $_GET['fauxlder'] ) ) { ?>
		div.leftcol #fauxlders a.<?php echo $_GET['fauxlder']; ?> { font-weight: bold; color: black; }
		<?php } ?>
		div.leftcol #fauxlders a.empty { color: black; }
	</style>
	<script type='text/javascript'>
		$(document).ready(function() {
		$('div.leftcol > ul.menu:first').append( '<li><a href="<?php echo $site_url; ?>">Folders</a></li><ul class="fauxlders menu" id="fauxlders"></ul>' );
		<?php foreach( $display as $fauxlder => $number ) { if ( $number == 0 ) { $class="empty"; } else { $class="full"; } ?>
			$('#fauxlders').append(
			'<li><a class="<?php echo "$fauxlder $class"; ?>" href="<?php echo $site_url; ?>?fauxlder=<?php echo $fauxlder; ?>">(<?php echo number_format( $number ); ?>) <?php echo $faulders[$fauxlder]; ?></a></li>'
			);
		<?php } ?>
		});
		$(document).ready(function() {
			var width = $('#fauxlders').width() + parseInt($('#fauxlders').css('margin-left'));
			if ( width > $('.leftcol').width() )
				$('.leftcol').width( width );
		});
	</script>
	<?php
}

function filter_index_messages_query( $query ) {
	// Do not attempt to affect closed ticket listing
	if ( !empty( $_GET['status'] ) && $_GET['status'] == 'closed' )
		return $query;

	foreach( array( 'q', 'sender', 'status', 'tag', 'todo' ) as $excludeif ) {
		if ( !empty( $_GET[$excludeif] ) )
			return $query;
	}
	global $db;
	$fauxlders = fauxlder_list();
	if ( empty( $_GET['fauxlder'] ) ) {
		$and_not_likes = array();
		foreach( $fauxlders as $idx => $val ) {
			$and_not_likes[] = " AND $db->threads.subject NOT LIKE '%".$db->escape( $val )."%' ";
		}
		if ( !empty( $and_not_likes ) ) {
			$and_not_likes = implode( " ", $and_not_likes );
			$query = str_replace(
				" WHERE 1 = 1 ",
				" WHERE 1 = 1 $and_not_likes ",
				$query
			);
			echo '<!-- '.$query.' -->';
		}
	} else {
		$fauxlder = $_GET['fauxlder'];
		if ( !empty( $fauxlders[$fauxlder] ) ) {
			$query = str_replace(
				" WHERE 1 = 1 ",
				" WHERE 1 = 1 AND $db->threads.subject LIKE '%".$db->escape( $fauxlders[$fauxlder] )."%' ",
				$query
			);
		}
	}
	return $query;
}
add_filter( 'index_messages_query', 'filter_index_messages_query' );