<?php

$referer = ''; 
if ( isset( $_SERVER['HTTP_REFERER'] ) ) { 
	$referer = $_SERVER['HTTP_REFERER']; 
} 

$js = '<script src="js/thread.js?v=1.0.1" type="text/javascript"></script>';

?>
<?php
include( 'header.php' );

$tags = ( ! empty( $_POST['tags'] ) ) ? trim( strip_tags( $_POST['tags'] ) ) : '';
$to_email = ( ! empty( $_POST['to_email'] ) ) ? trim( strip_tags( $_POST['to_email'] ) ) : '';
$to_name = ( ! empty( $_POST['to_name'] ) ) ? trim( strip_tags( $_POST['to_name'] ) ) : '';
$subject = ( ! empty( $_POST['subject'] ) ) ? trim( strip_tags( $_POST['subject'] ) ) : '';
$message = ( ! empty( $_POST['message'] ) ) ? trim( $_POST['message'] ) : '';
$cc = ( ! empty( $_POST['cc'] ) ) ? trim( strip_tags( $_POST['cc'] ) ) : '';
$bcc = ( ! empty( $_POST['bcc'] ) ) ? trim( strip_tags( $_POST['bcc'] ) ) : '';

if ( ! empty( $current_user->data->signature ) )
	$message .= "\n" . $current_user->data->signature;

// defaults for a blank form
$tags = empty( $tags ) ? 'support' : '';
$subject = empty( $subject ) ? 'Support' : '';
?>

<h2>Create a new support ticket</h2>

<div class="message-new">
<form action="thread-create.php" method="post" enctype="multipart/form-data" onsubmit="return validate_upload(this);">

<p>
  Send to email address:<br />
  <input type="text" size="100" name="to_email" value="<?php echo esc_attr( $to_email ); ?>" /><br />
  <span id="email_error"></span>
  <a href="#" class="cc-toggle">Add CC or BCC</a>
</p>
<div class="cc-field" style="display: none;">
  <p>
    Cc:<br />
    <input type="text" size="100" name="cc" value="<?php echo esc_attr( $cc ); ?>" /><br />
    <span id="email_error"></span>
  </p>
  <p>
    Bcc:<br />
    <input type="text" size="100" name="bcc" value="<?php echo esc_attr( $bcc ); ?>" /><br />
    <span id="email_error"></span>
  </p>
</div>
<p>Subject:<br />
<input type="text" size="100" name="subject" value="<?php echo esc_attr( $subject ); ?>" class="title reply replying" /></p>
<select class="predefined_message">
<option value="">Predefined Reply</option>
<?php
	$predefined = get_predefined_names();
	foreach ( $predefined as $id => $name )
		echo html_message('<option value="%d">%s</option>', $id, $name);
?>
</select>
<p class="attachment-box">Attachment:<br />
	<input type="file" name="attachment" id="attachment" />
</p>
<textarea name="message" class="widetext reply replying" id="message" style="height: 20em;">
<?php echo esc_textarea( $message ); ?>
</textarea></p>

<p>Tags:
<input type="text" size="50" name="tags" value="<?php echo esc_attr( $tags ); ?>"  class="tag padme reply replying" />
</p>

<p class="submit">

<input type="submit" name="send" value="Send" />
<input type="submit" name="sendtickle" value="Send and Tickle" />
<input type="submit" name="sendclose" value="Send and Close" />
<input type="hidden" name="from" value="<?php echo esc_url( $referer ); ?>" />
</p>
</form>

</div>

<?php
include( 'footer.php' );