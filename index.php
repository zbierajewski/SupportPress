<?php include( 'header.php' ); ?>
<?php include_once( 'includes/mime.php' ); ?>

<?php
function sortable_header_link ( $type ) {

	$existing_get_vars = '';

	foreach ( $_GET as $key => $val ) {

		if ( 'by' == $key || 'order' == $key )
			continue;

		$existing_get_vars .= $key . '=' . $val . '&';
    }

	$direction = ( isset( $_GET['by'] ) && $type == $_GET['by'] && isset( $_GET['order'] ) && 'ASC' == $_GET['order'] ) ? 'DESC' : 'ASC';

	echo '?' . $existing_get_vars . 'by=' . $type . '&order=' . $direction;
}

if ( isset( $_GET['status'] ) )
	$status = preg_replace( '|[^a-z]|', '', $_GET['status'] );
else
	$status = 'open';



$status_select_options = array(
	'all' 		=> 'All Tickets',
	'new' 		=> ' - New Tickets',
	'replies' 	=> ' - With Replies',
	'tickle' 	=> ' - Tickle',
	'closed' 	=> ' - Closed',
	'spam' 	=> ' - Spam'
	);
?>


<form method="get" action="?" class="status-form">
	<input type="submit" value="Go" />
	<select id="status" class="status-select" name="status">
		<?php foreach ( $status_select_options as $key => $value ) : ?>
			<option value="<?php echo $key; ?>" <?php if ( $status == $key ) echo ' selected="selected"'; ?>><?php echo $value; ?></option>
		<?php endforeach; ?>
	</select>
	<span>View</span>
</form>

<?php do_action('list-above'); ?>

<form action="thread-bulk.php" method="post" enctype="multipart/form-data">
<table cellpadding="10">
<tr class="tablehead">
<th><input type="checkbox" name="checkall" value="none" id="checkall" /></th>
<th><a href="<?php sortable_header_link( 'email' ); ?>">From</a></th>
<th><a href="<?php sortable_header_link( 'subject' ); ?>">Subject</a></th>
<th>Tags</th>
<th><a href="<?php sortable_header_link( 'dt' ); ?>">Age</a></th>
<th>#</th>
</tr>
<?php

if ( isset( $_GET['apage'] ) )
	$page = (int) $_GET['apage'];
else
	$page = 1;
$start = $offset = ( $page - 1 ) * 20;

$where = ' WHERE 1 = 1 ';

if ( isset( $_GET['tag'] ) ) {
	$tag = sanitize_title( $_GET['tag'] );
	$ids = $db->get_col( $db->prepare("SELECT thread_id FROM $db->tags WHERE tag_slug = %s", $tag) );
	if ( empty( $ids ) )
		$ids = array( 'NULL' );
	$ids = join( ', ', $ids );
	$where .= " AND $db->threads.thread_id IN ( $ids ) ";
}

if ( isset($_GET['email']) ) {
	$where .= $db->prepare(" AND $db->threads.email = %s ", $_GET['email']);
}


if ( $status ) {

	if ( $status == 'new' ) {
		$where .= " AND state = 'open' AND messages = 1 ";
	}
	elseif ( $status == 'replies' ) {
		$where .= " AND state = 'open' AND messages > 1 ";
	}
	elseif ( $status == 'all' ) {
		$where .= " AND state = 'open' ";
	}
	else {
		$where .= " AND state = '$status' ";
	}
}

if ( isset( $_GET['mine'] ) ) {
	$where .= $db->prepare(" AND from_user_id = %d ", $current_user->ID );
}

if ( isset( $_GET['todo'] ) ) {
	if ( !empty( $_GET['subject'] ) ) {
		$search = addslashes( $_GET['subject'] );
		$where .= " AND subject LIKE ('%$search%') ";
	}

	if ( !empty( $_GET['sender'] ) ) {
		$search = addslashes( $_GET['sender'] );
		if ( !isset($_GET['email']) ) {
			$message_search_thread_ids = $db->get_col( $db->prepare("SELECT DISTINCT `thread_id` FROM $db->messages WHERE `email` = %s", $_GET['sender'] ) );
			if ( !empty( $message_search_thread_ids ) )
				$message_search_thread_ids = implode( ',', $message_search_thread_ids );
			else
				$message_search_thread_ids = "NULL";
			$where .= " AND $db->threads.thread_id IN(".$message_search_thread_ids.") ";
		} else {
			$where .= $db->prepare(" AND $db->messages.email = %s ", $_GET['sender']);
		}
	}

	if ( !empty( $_GET['q'] ) ) {

		if ( function_exists( 'filter_var' ) )
			$is_email = filter_var( $_GET['q'], FILTER_VALIDATE_EMAIL );
		else
			$is_email = false;

		if ( $is_email ) {
			$message_search_thread_ids = $db->get_col( $db->prepare("SELECT DISTINCT `thread_id` FROM $db->messages WHERE `email` = %s", $_GET['q'] ) );
			if ( !empty( $message_search_thread_ids ) )
				$message_search_thread_ids = implode( ',', $message_search_thread_ids );
			else
				$message_search_thread_ids = "NULL";
			$where .= " AND $db->threads.thread_id IN(".$message_search_thread_ids.") ";
		} else {
			if ( $status && $status != 'all' )
				$search_status = " AND state = '$status' ";
			else
				$search_status = "";

			$like = "%{$_GET['q']}%";
			$message_search_thread_ids = $db->get_col( $db->prepare( "SELECT DISTINCT t.`thread_id` FROM $db->threads t JOIN $db->messages m USING(`thread_id`) WHERE ( t.email LIKE %s OR LEFT( m.content, 10240 ) LIKE %s ) $search_status", $like, $like ) );
			if ( !empty( $message_search_thread_ids ) )
				$message_search_thread_ids = implode( ',', $message_search_thread_ids );
			else
				$message_search_thread_ids = "NULL";

			$thread_search_ids = $db->get_col( $db->prepare( "SELECT `thread_id` FROM $db->threads WHERE $db->threads.subject LIKE %s $search_status", $like ) );
			if ( !empty( $thread_search_ids ) )
				$thread_search_ids = implode( ',', $thread_search_ids );
			else
				$thread_search_ids = "NULL";

			$where .= " AND $db->threads.thread_id IN(".$message_search_thread_ids.",".$thread_search_ids.") ";
		}
	}
}

$query = "FROM $db->threads $where";
$query = apply_filters( 'index_messages_query', $query );

// Add ability to custom order by clicking column headers
if ( isset( $_GET['by'] ) && isset( $_GET['order'] ) ) {
	$query_order = "ORDER BY " . $_GET['by'] . " " . $_GET['order'];
} else {
	$query_order = "ORDER BY priority DESC, dt DESC";
	if ( $status != 'open' && $status != 'tickle' )
		$query_order = "ORDER BY dt DESC";
}

$recent = $db->get_results( "SELECT *,email as t_email $query $query_order LIMIT $offset, 20" );
if ( !empty($db->last_error) )
	var_dump($db->last_error, $db->last_query);
$recent_ids = array();
if ( !empty( $recent ) ) {
	foreach( $recent as $row )
		$recent_ids[] = $row->thread_id;
}
$i = 0;
$total = $db->get_var( "SELECT COUNT(thread_id) $query" );

do_action( 'index_thread_result_ids', $recent_ids, $total );
do_action( 'index_thread_results', $recent, $total );

$recent_thread_ids_in = implode( ',', array_merge( array('NULL'), $recent_ids ) );
$recent_thread_tag_slugs = array();
$recent_thread_tag_links = array();

$slugrows = $wpdb->get_results( "SELECT thread_id, tag_slug FROM $db->tags WHERE thread_id IN ( $recent_thread_ids_in )" );
if ( !empty( $slugrows ) ) {
	foreach( $slugrows as $row ) {
		if ( empty( $recent_thread_tag_slugs[$row->thread_id] ) ) {
			$recent_thread_tag_slugs[$row->thread_id] = array();
			$recent_thread_tag_links[$row->thread_id] = array();
		}
		$recent_thread_tag_slugs[$row->thread_id][] = $row->tag_slug;
		$recent_thread_tag_links[$row->thread_id][] = sprintf(
			"<a href='%s'>%s</a>",
			esc_attr( "index.php?tag=$row->tag_slug" ),
			esc_html( $row->tag_slug )
		);
	}
}

if ( $total > 0 ) {
	$lastcontent = array();
	$content_rows = $wpdb->get_results( "SELECT `thread_id`, `content` FROM $wpdb->messages WHERE `thread_id` IN( $recent_thread_ids_in ) GROUP BY `thread_id` ORDER BY `message_id` DESC", OBJECT_K );
}

foreach ( (array)$recent as $t ) {
	$current_status = '';
	$classes = array();
	if ( $i % 2 )
		$classes[] = 'alt';
	// NOTE: replaced some older code that used class names like 'alt-closed' and 'row-closed'
	$classes[] = $t->state;

	if ( $t->state == 'closed' ) {
		$current_status = '<strong class="closed-msg">{' . __( 'CLOSED' ). '}</strong> ';
	}
	elseif ( $t->state == 'spam' ) {
		$current_status = '<strong class="closed-msg">{' . __( 'SPAM' ). '}</strong> ';
	}

	$excerpt = '';
	$parts = mime_split( $content_rows[$t->thread_id]->content );
	if ( $part = find_first_part($parts) )
		$excerpt = substr( message_meat($part->content), 0, 300);

	$tags = '';
	if ( !empty( $recent_thread_tag_links[$t->thread_id] ) )
		$tags = implode( ', ', $recent_thread_tag_links[$t->thread_id] );

	$class_tags = '';
	if ( !empty( $recent_thread_tag_slugs[$t->thread_id] ) ) {
		foreach ( $recent_thread_tag_slugs[$t->thread_id] as $tag_name ) {
			$class_tags .= "tag-{$tag_name} ";
		}
	}

	$subject = esc_html( stripslashes( $t->subject ? mime_header_decode( $t->subject ) : '(No Subject)' ) );
	if ( empty( $subject ) ) {
		$subject = '(No Subject)';
	}

	$has_attachment = '';
	if ( $t->has_attachment )
		$has_attachment = '<span class="right"><img src="images/icon_attachment.png" title="Has attachment" width="16" height="16" /></span>';

	echo "<tr class='".($classes ? join(' ', $classes).' ' : '').$class_tags."' id='tr$t->thread_id' ";
	// this is a bit of a hack - plugins can use this action to echo additional attributes for the tr
	do_action('thread-tr-attributes', $t);

	$actual_date = date_format(date_create($t->dt), 'F j Y, h:i:sA');

	echo ">
	<td><input type='checkbox' name='thread_ids[]' value='$t->thread_id' class='mcheck' id='mcheck$t->thread_id' /></td>
	<td><img data-gravatar-hash='" . md5( strtolower( trim( $t->t_email ) ) ) . "' class='row-avatar' height='24' src='images/24px-white.gif' width='24' /><a href='index.php?q=&sender=$t->t_email&status=&todo=Search'>$t->t_email</a></td>
	<td>$has_attachment $current_status <a title='" . esc_attr( $excerpt ) . "' href='thread.php?t=$t->thread_id&amp;replies={$t->messages}'>" . $subject . "</a></td>
	<td>$tags</td>
	<td title='$actual_date'>".short_time_diff($t->dt)."</td>
	<td>$t->messages</td>
	</tr>";
	++$i;
}
?>

<tr>
<td colspan="2">
<label>With checked:</label>
<label><input type="radio" name="status-close" value="Close" class="enablewhenselected" /> Close</label>
<label><input type="radio" name="status-close" value="Tickle" class="enablewhenselected" /> Tickle</label>
<label><input type="radio" name="status-close" value="Open" class="enablewhenselected" /> Open</label>
<label><input type="radio" name="status-close" value="Spam" class="enablewhenselected" /> Spam</label>
<input type="submit" value="Submit" class="enablewhenselected" />
</td>
<td colspan="3" align="right">
<?php

$page_links = paginate_links( array(
	'base' => add_query_arg('apage', '%#%'),
	'total' => ceil($total / 20),
	'current' => $page
));

if ( $page_links )
	echo "<p class='pagenav'>$page_links</p>";
?>
</td>
</tr>

</table>
</form>

<script language="javascript">
	$(document).ready( function() {
		$( '.status-select' ).change( function() {
		  var sitepath = '<?php echo $site_path; ?>';
		
			location.href = sitepath + '/?status=' + $( this ).val();
		});
	});
</script>


<?php
do_action('list-below');
include( 'footer.php' );