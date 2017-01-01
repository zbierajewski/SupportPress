<?php
include('init.php');
auth_redirect();

$message_id = @$_GET['message_id'];
$i = @$_GET['i'];
$download = !empty($_GET['dl']);

if ( !empty($message_id) && isset($i) ) {
	$msg = $db->get_row( $db->prepare("SELECT * FROM $db->messages WHERE message_id=%d", $message_id) );
	if ( $msg ) {
		include_once('includes/mime.php');
		$parts = mime_split($msg->content);
		$att = $parts[$i];
		if ( $att ) {
			$content_type = $att->get_type();
			$filename = intval($message_id).'-'.intval($i);
			if ( preg_match( '/;\s*name="([^"]+)"/', $att->content_type, $m ) )
				$filename .= '-'.preg_replace('/[^\w.]/', '', $m[1]);

			if ( $content_type == 'application/octet-stream' ) {
				// try to use a slightly more helpful content type
				$filename_parts = pathinfo($filename);
				$ext = preg_replace('/[^\w]/', '', $filename_parts['extension']);
				if ( $ext )
					$content_type = 'application/' . strtolower( $ext );
			}

			#var_dump($filename_parts, $content_type);die;

			header('Content-type: '. $content_type);
			if ( $download )
				header('Content-Disposition: attachment; filename="'.$filename.'"');
			else
				header('Content-Disposition: inline; filename="'.$filename.'"');

			die($att->content);
		}

	}
}

header('Status: 404 Not Found');
die('Not found.');