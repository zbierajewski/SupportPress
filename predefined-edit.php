<?php


$referer = ''; 
if ( isset( $_SERVER['HTTP_REFERER'] ) ) { 
	$referer = $_SERVER['HTTP_REFERER']; 
} 
 	
 	
$js = "
<script type='text/javascript'>
$(document).ready(function(){
$('.inlinereply').click( function() { $('#' + this.name ).toggle(); $('#' + this.name + ' .widetext')[0].focus(); } );
$('p').click( function() { $('.lastclicked').attr( 'value', this.parentNode.id ); } );
});
</script>
";

?>
<?php

include_once('init.php');


$name = @trim($_POST['name']);
$message = @trim($_POST['message']);
$tags = @trim($_POST['tags']);
$id = ( !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : '' );

if ( $_POST ) {
	if ( !trim($name) )
		error_message('Missing title.');
	elseif ( !trim($message) )
		error_message('Missing message.');
	else {
		$result = store_predefined_message( $name, $message, $tags, $id );
		if ( $result ) {
			$id = $result;
			status_message( 'Message saved.  <a href="%s">click to edit</a>.', 'predefined-edit.php?id='.intval($id) );
			$name = $message = $tags = $id = null;
		} else {
			error_message( 'Error saving message: %s', $db->last_error );
		}

	}
} elseif ( !empty($_REQUEST['id']) ) {
	$row = $db->get_row( $db->prepare("SELECT * FROM $db->predefined_messages WHERE id=%d", $_REQUEST['id']) );
	if ( $row ) {
		$name = $row->name;
		$message = $row->message;
		$tags = $row->tag;
	} else {
		$name = $message = $tag;
		error_message( 'No such predefined message %s', $_REQUEST['id'] );
	}
} else {
	// defaults for a blank form
	$name = $message = $tag = '';
}

include( 'header.php' );

if ( $id )
	$title = 'Edit a predefined message';
else
	$title = 'Create a new predefined message';
?>
<h2><?php echo esc_html( $title ); ?></h2>

<div class='message-new'>
<form action="" method="post">

<?php echo join("\n", get_user_messages()); ?>

<p>Title:<br />
<input type="text" size="100" name="name" value="<?php echo esc_attr( $name ); ?>" class="title reply replying" /></p>
<p>Tag (optional):<br />
<input type="text" size="100" name="tags" value="<?php echo esc_attr( $tags ); ?>" class="title reply replying" /></p>
<p>Message:<br />
<textarea name="message" class="widetext reply" style="height: 20em;">
<?php
echo esc_textarea( $message );
?>
</textarea></p>
<p class="submit">

<input type="submit" name="submit" value="Save" />
<input type="hidden" name="from" value="<?php echo esc_url( $referer ); ?>" />
<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>" />
</p>
</form>
</div>

<div>
<h3>All predefined messages:</h3>
<ul>
<?php
$rows = $db->get_results("SELECT * FROM $db->predefined_messages ORDER BY name ASC,id ASC");
foreach ( $rows as $row ) {
	?>
	<li><a href="?id=<?php echo intval($row->id); ?>"><?php echo esc_html( $row->name ); ?></a></li>
	<?php
}
?>
</ul>
</div>

<?php
include( 'footer.php' );