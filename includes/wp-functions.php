<?php

if ( !function_exists( 'wp_mail' ) ) :
/**
 * Send mail, similar to PHP's mail
 *
 * A true return value does not automatically mean that the user received the
 * email successfully. It just only means that the method used was able to
 * process the request without any errors.
 *
 * Using the two 'wp_mail_from' and 'wp_mail_from_name' hooks allow from
 * creating a from address like 'Name <email@address.com>' when both are set. If
 * just 'wp_mail_from' is set, then just the email address will be used with no
 * name.
 *
 * The default content type is 'text/plain' which does not allow using HTML.
 * However, you can set the content type of the email by using the
 * 'wp_mail_content_type' filter.
 *
 * The default charset is based on the charset used on the blog. The charset can
 * be set using the 'wp_mail_charset' filter.
 *
 * @since 1.2.1
 * @uses apply_filters() Calls 'wp_mail' hook on an array of all of the parameters.
 * @uses apply_filters() Calls 'wp_mail_from' hook to get the from email address.
 * @uses apply_filters() Calls 'wp_mail_from_name' hook to get the from address name.
 * @uses apply_filters() Calls 'wp_mail_content_type' hook to get the email content type.
 * @uses apply_filters() Calls 'wp_mail_charset' hook to get the email charset
 * @uses do_action_ref_array() Calls 'phpmailer_init' hook on the reference to
 *		phpmailer object.
 * @uses PHPMailer
 *
 * @param string|array $to Array or comma-separated list of email addresses to send message.
 * @param string $subject Email subject
 * @param string $message Message contents
 * @param string|array $headers Optional. Additional headers.
 * @param string|array $attachments Optional. Files to attach.
 * @return bool Whether the email contents were sent successfully.
 */
function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
	// Compact the input, apply the filters, and extract them back out
	extract( apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) ) );

	if ( !is_array($attachments) )
		$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );

	global $phpmailer;

	// (Re)create it, if it's gone missing
	if ( !is_object( $phpmailer ) || !is_a( $phpmailer, 'PHPMailer' ) ) {
		require_once BACKPRESS_PATH . '/class.mailer.php';
		require_once BACKPRESS_PATH . '/class.mailer-smtp.php';
		$phpmailer = new PHPMailer( true );
	}

	// Headers
	if ( empty( $headers ) ) {
		$headers = array();
	} else {
		if ( !is_array( $headers ) ) {
			// Explode the headers out, so this function can take both
			// string headers and an array of headers.
			$tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
		} else {
			$tempheaders = $headers;
		}
		$headers = array();
		$cc = array();
		$bcc = array();

		// If it's actually got contents
		if ( !empty( $tempheaders ) ) {
			// Iterate through the raw headers
			foreach ( (array) $tempheaders as $header ) {
				if ( strpos($header, ':') === false ) {
					if ( false !== stripos( $header, 'boundary=' ) ) {
						$parts = preg_split('/boundary=/i', trim( $header ) );
						$boundary = trim( str_replace( array( "'", '"' ), '', $parts[1] ) );
					}
					continue;
				}
				// Explode them out
				list( $name, $content ) = explode( ':', trim( $header ), 2 );

				// Cleanup crew
				$name    = trim( $name    );
				$content = trim( $content );

				switch ( strtolower( $name ) ) {
					// Mainly for legacy -- process a From: header if it's there
					case 'from':
						if ( strpos($content, '<' ) !== false ) {
							// So... making my life hard again?
							$from_name = substr( $content, 0, strpos( $content, '<' ) - 1 );
							$from_name = str_replace( '"', '', $from_name );
							$from_name = trim( $from_name );

							$from_email = substr( $content, strpos( $content, '<' ) + 1 );
							$from_email = str_replace( '>', '', $from_email );
							$from_email = trim( $from_email );
						} else {
							$from_email = trim( $content );
						}
						break;
					case 'content-type':
						if ( strpos( $content, ';' ) !== false ) {
							list( $type, $charset ) = explode( ';', $content );
							$content_type = trim( $type );
							if ( false !== stripos( $charset, 'charset=' ) ) {
								$charset = trim( str_replace( array( 'charset=', '"' ), '', $charset ) );
							} elseif ( false !== stripos( $charset, 'boundary=' ) ) {
								$boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset ) );
								$charset = '';
							}
						} else {
							$content_type = trim( $content );
						}
						break;
					case 'cc':
						$cc = array_merge( (array) $cc, explode( ',', $content ) );
						break;
					case 'bcc':
						$bcc = array_merge( (array) $bcc, explode( ',', $content ) );
						break;
					case 'message-id':
						$message_id = trim( $content );
						break;
					default:
						// Add it to our grand headers array
						$headers[trim( $name )] = trim( $content );
						break;
				}
			}
		}
	}

	// Empty out the values that may be set
	$phpmailer->ClearAddresses();
	$phpmailer->ClearAllRecipients();
	$phpmailer->ClearAttachments();
	$phpmailer->ClearBCCs();
	$phpmailer->ClearCCs();
	$phpmailer->ClearCustomHeaders();
	$phpmailer->ClearReplyTos();

	// From email and name
	// If we don't have a name from the input headers
	if ( !isset( $from_name ) )
		$from_name = 'WordPress';

	/* If we don't have an email from the input headers default to wordpress@$sitename
	 * Some hosts will block outgoing mail from this address if it doesn't exist but
	 * there's no easy alternative. Defaulting to admin_email might appear to be another
	 * option but some hosts may refuse to relay mail from an unknown domain. See
	 * http://trac.wordpress.org/ticket/5007.
	 */

	if ( !isset( $from_email ) ) {
		// Get the site domain and get rid of www.
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		$from_email = 'wordpress@' . $sitename;
	}

	// Plugin authors can override the potentially troublesome default
	$phpmailer->From     = apply_filters( 'wp_mail_from'     , $from_email );
	$phpmailer->FromName = apply_filters( 'wp_mail_from_name', $from_name  );

	// Set destination addresses
	if ( !is_array( $to ) )
		$to = explode( ',', $to );

	foreach ( (array) $to as $recipient ) {
		try {
			// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
			$recipient_name = '';
			if( preg_match( '/(.*)<(.+)>/', $recipient, $matches ) ) {
				if ( count( $matches ) == 3 ) {
					$recipient_name = $matches[1];
					$recipient = $matches[2];
				}
			}
			$phpmailer->AddAddress( $recipient, $recipient_name);
		} catch ( phpmailerException $e ) {
			continue;
		}
	}

	// Set mail's subject and body
	$phpmailer->Subject = $subject;
	$phpmailer->Body    = $message;

	// Add any CC and BCC recipients
	if ( !empty( $cc ) ) {
		foreach ( (array) $cc as $recipient ) {
			try {
				// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
				$recipient_name = '';
				if( preg_match( '/(.*)<(.+)>/', $recipient, $matches ) ) {
					if ( count( $matches ) == 3 ) {
						$recipient_name = $matches[1];
						$recipient = $matches[2];
					}
				}
				$phpmailer->AddCc( $recipient, $recipient_name );
			} catch ( phpmailerException $e ) {
				continue;
			}
		}
	}

	if ( !empty( $bcc ) ) {
		foreach ( (array) $bcc as $recipient) {
			try {
				// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
				$recipient_name = '';
				if( preg_match( '/(.*)<(.+)>/', $recipient, $matches ) ) {
					if ( count( $matches ) == 3 ) {
						$recipient_name = $matches[1];
						$recipient = $matches[2];
					}
				}
				$phpmailer->AddBcc( $recipient, $recipient_name );
			} catch ( phpmailerException $e ) {
				continue;
			}
		}
	}

	if ( !empty( $message_id ) ) {
		$phpmailer->MessageID = $message_id;
	}

	// Set to use PHP's mail()
	$phpmailer->IsMail();

	// SupportPress: use STMP if configured
	if ( defined('SMTP_HOST') && SMTP_HOST ) {
			$phpmailer->IsSMTP();
			$phpmailer->Host = SMTP_HOST;
			if ( SMTP_PORT )
					$phpmailer->Host .= ':'.SMTP_PORT;
			global $email_domain;
			$phpmailer->Hostname = $email_domain;
			if ( SMTP_USER ) {
					$phpmailer->SMTPAuth = true;
					$phpmailer->Username = SMTP_USER;
					$phpmailer->Password = SMTP_PASSWORD;
			}
	}

	// Set Content-Type and charset
	// If we don't have a content-type from the input headers
	if ( !isset( $content_type ) )
		$content_type = 'text/plain';

	$content_type = apply_filters( 'wp_mail_content_type', $content_type );

	$phpmailer->ContentType = $content_type;

	// Set whether it's plaintext, depending on $content_type
	if ( 'text/html' == $content_type )
		$phpmailer->IsHTML( true );

	// If we don't have a charset from the input headers
	if ( !isset( $charset ) )
		$charset = 'utf-8';

	// Set the content-type and charset
	$phpmailer->CharSet = apply_filters( 'wp_mail_charset', $charset );

	// Set custom headers
	if ( !empty( $headers ) ) {
		foreach( (array) $headers as $name => $content ) {
			$phpmailer->AddCustomHeader( sprintf( '%1$s: %2$s', $name, $content ) );
		}

		if ( false !== stripos( $content_type, 'multipart' ) && ! empty($boundary) )
			$phpmailer->AddCustomHeader( sprintf( "Content-Type: %s;\n\t boundary=\"%s\"", $content_type, $boundary ) );
	}

	if ( !empty( $attachments ) ) {
		foreach ( $attachments as $attachment ) {
			
			if ( isset( $attachment['name'] ) ) {
				try {
					$phpmailer->AddAttachment( $attachment[ 'file' ], $attachment[ 'name' ] );
				} catch ( phpmailerException $e ) {
					continue;
				}
			}
			else {
				try {
					$phpmailer->AddAttachment($attachment);
				} catch ( phpmailerException $e ) {
					continue;
				}
			}
		}
	}

	do_action_ref_array( 'phpmailer_init', array( &$phpmailer ) );

	// Send!
	try {
		$phpmailer->Send();
	} catch ( phpmailerException $e ) {
		return false;
	}

	return true;
}
endif;
if ( !function_exists('auth_redirect') ) :
function auth_redirect() {
	global $site_url, $site_domain, $site_path, $wp_auth_object;
	// Checks if a user is logged in, if not redirects them to the login page
	if ( !$wp_auth_object->validate_auth_cookie() ) {
		nocache_headers();

		$path = $_SERVER['REQUEST_URI'];
		header("Location: $site_url/login.php?redirect_to=" . urlencode($site_domain . $path ) );
		exit();
	}
}
endif;

// Cookie safe redirect.  Works around IIS Set-Cookie bug.
// http://support.microsoft.com/kb/q176113/
if ( !function_exists('wp_redirect') ) :
function wp_redirect($location) {
	global $is_IIS;

	$location = str_replace( array("\n", "\r"), '', $location);

	if ($is_IIS)
		header("Refresh: 0;url=$location");
	else
		header("Location: $location");
}
endif;

function nocache_headers() {
	@ header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
	@ header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	@ header('Cache-Control: no-cache, must-revalidate, max-age=0');
	@ header('Pragma: no-cache');
}

if ( !function_exists('make_clickable') ) :
function make_clickable($ret) {
	$ret = ' ' . $ret;
	// in testing, using arrays here was found to be faster
	$ret = preg_replace(
		array(
			'#([\s>])([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)#is',
			'#([\s>])((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*)#is',
			'#([\s>])([a-z0-9\-_.]+)@([^,< \n\r]+)#i'),
		array(
			'$1<a href="$2" rel="nofollow">$2</a>',
			'$1<a href="http://$2" rel="nofollow">$2</a>',
			'$1<a href="mailto:$2@$3">$2@$3</a>'),$ret);
	// this one is not in an array because we need it to run last, for cleanup of accidental links within links
	$ret = preg_replace("#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i", "$1$3</a>", $ret);
	$ret = trim($ret);
	return $ret;
}
endif;

function paginate_links( $arg = '' ) {
	if ( is_array($arg) )
		$a = &$arg;
	else
		parse_str($arg, $a);

	// Defaults
	$base = '%_%'; // http://example.com/all_posts.php%_% : %_% is replaced by format (below)
	$format = '?page=%#%'; // ?page=%#% : %#% is replaced by the page number
	$total = 1;
	$current = 0;
	$show_all = false;
	$prev_next = true;
	$prev_text = __('&laquo; Previous');
	$next_text = __('Next &raquo;');
	$end_size = 1; // How many numbers on either end including the end
	$mid_size = 2; // How many numbers to either side of current not including current
	$type = 'plain';
	$add_args = false; // array of query args to aadd

	extract($a);

	// Who knows what else people pass in $args
	$total    = (int) $total;
	if ( $total < 2 )
		return;
	$current  = (int) $current;
	$end_size = 0  < (int) $end_size ? (int) $end_size : 1; // Out of bounds?  Make it the default.
	$mid_size = 0 <= (int) $mid_size ? (int) $mid_size : 2;
	$add_args = is_array($add_args) ? $add_args : false;
	$r = '';
	$page_links = array();
	$n = 0;
	$dots = false;

	if ( $prev_next && $current && 1 < $current ) :
		$link = str_replace('%_%', 2 == $current ? '' : $format, $base);
		$link = str_replace('%#%', $current - 1, $link);
		if ( $add_args )
			$link = add_query_arg( $add_args, $link );
		$page_links[] = "<a class='prev page-numbers' href='" . $link . "'>$prev_text</a>";
	endif;
	for ( $n = 1; $n <= $total; $n++ ) :
		if ( $n == $current ) :
			$page_links[] = "<span class='page-numbers current'>$n</span>";
			$dots = true;
		else :
			if ( $show_all || ( $n <= $end_size || ( $current && $n >= $current - $mid_size && $n <= $current + $mid_size ) || $n > $total - $end_size ) ) :
				$link = str_replace('%_%', 1 == $n ? '' : $format, $base);
				$link = str_replace('%#%', $n, $link);
				if ( $add_args )
					$link = add_query_arg( $add_args, $link );
				$page_links[] = "<a class='page-numbers' href='" . $link . "'>$n</a>";
				$dots = true;
			elseif ( $dots && !$show_all ) :
				$page_links[] = "<span class='page-numbers dots'>...</span>";
				$dots = false;
			endif;
		endif;
	endfor;
	if ( $prev_next && $current && ( $current < $total || -1 == $total ) ) :
		$link = str_replace('%_%', $format, $base);
		$link = str_replace('%#%', $current + 1, $link);
		if ( $add_args )
			$link = add_query_arg( $add_args, $link );
		$page_links[] = "<a class='next page-numbers' href='" . $link . "'>$next_text</a>";
	endif;
	switch ( $type ) :
		case 'array' :
			return $page_links;
			break;
		case 'list' :
			$r .= "<ul class='page-numbers'>\n\t<li>";
			$r .= join("</li>\n\t<li>", $page_links);
			$r .= "</li>\n</ul>\n";
			break;
		default :
			$r = join("\n", $page_links);
			break;
	endswitch;
	return $r;
}

if ( !function_exists('is_ssl') ) {
function is_ssl() {
	return ( isset($_SERVER['HTTPS']) && 'on' == strtolower($_SERVER['HTTPS']) ) ? true : false;
}
}

if ( !function_exists('get_avatar') ):
function get_avatar( $email, $size = '96', $default = '', $alt = false ) {
	if ( empty($email) )
		return false;

 	if ( is_ssl() )
		$host = 'https://secure.gravatar.com';
	else
		$host = 'http://www.gravatar.com';

	$md5 = md5( strtolower($email) );
	$size = intval($size);
	if ( empty($default) )
		$default = "$host/avatar/ad516503a11cd5ca435acc9bb6523536?s={$size}";
	$default = urlencode( $default );

	return "<img src='{$host}/avatar/{$md5}?s={$size}&d={$default}' class='avatar' height='{$size}' width='{$size}' />";
}
endif;