<?php

$text = $_POST['text'];

$email = $_POST['email'];

$reply_message = trim( $text );
$reply_message = htmlspecialchars( $reply_message, ENT_NOQUOTES );
$reply_message = preg_replace( "|(-- \n.*)$|s", '', $reply_message ); // strip the sig
$reply_message = wordwrap( $reply_message, 74, "\n" );
$reply_message = trim( $reply_message );
$reply_message = preg_replace( "/(\A|\n)/", "$1> ", $reply_message );
$reply_message = $reply_message . "\n\n";

echo "$email wrote:\n";
echo $reply_message;