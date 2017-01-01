<?php

// Alex's lame regex-based MIME decoder.

class mime_part {
	var $content_type = 'text/plain';
	var $content_transfer_encoding = null;
	var $content = null;

	function mime_part($content, $headers) {
		if ( !empty($headers['content-type']) )
			$this->content_type = trim( strtolower($headers['content-type']) );
		if ( !empty($headers['content-transfer-encoding']) )
			$this->content_transfer_encoding = trim( strtolower($headers['content-transfer-encoding']) );

		if ( $this->content_transfer_encoding == 'base64' )
			$this->content = base64_decode($content);
		elseif ( $this->content_transfer_encoding == 'quoted-printable' )
			$this->content = quoted_printable_decode($content);
		else
			$this->content = $content;
	}

	// e.g. is_type('text/plain') or is_type('image');
	function is_type($type) {
		return 0 === strpos($this->content_type, $type);
	}

	// returns the mime type with any extra attributes stripped
	function get_type() {
		$t = explode(';', $this->content_type);
		return $t[0];
	}

}

function mime_headers($text) {
	$headers = array();
	@list($head, $tail) = explode("\n\n", $text, 2);
		// regex to parse an rfc822 style header, including multiline support
		if ( preg_match_all('#^(\S+): (.+?)(?=^\S+?:|\Z)#mis', $head, $matches, PREG_SET_ORDER) ) {
			foreach ( $matches as $m )
				$headers[ trim(strtolower($m[1])) ]  = $m[2];
		} else {
			#echo "<pre>";var_dump("no header for this part", $head);echo "</pre>";
		}
		if ( $tail )
			$headers['content'] = $tail;

	return $headers;
}

function mime_split($text, $_boundary = null) {
	$top = substr($text, 0, 500);
	$boundary = $_boundary;
	// guess the boundary if none was provided
	if ( is_null($_boundary) && preg_match('#^\s*(?:.{0,80})?\s*--(\S{6,})\s*[-\w]+: #', $top, $m) )
		$boundary = $m[1];

	if ( $boundary ) {
		$parts = explode('--'.$boundary, $text);
		#echo "<pre>";var_dump("boundary is $boundary", count($parts).' parts');echo "</pre>";
		$out = array();

		foreach ( $parts as $part ) {
			$headers = mime_headers($part);
			#echo '<pre>';var_dump($headers);echo '</pre>';
			if ( !$headers )
				continue;
			if ( !empty( $headers['content-type'] ) && strpos( $headers['content-type'], 'multipart' ) === 0 ) {
				$subparts = mime_split($headers['content']);
				$out = array_merge($out, $subparts);
			} else {
				$out[] = new mime_part($headers['content'], $headers);
			}

			#if ( preg_match('#^Content-Type:\s*multipart/alternative;\s*boundary="([^"]+)"#im', $top, $m) ) {
		}
		#var_dump("finished with ".count($out)." real parts extracted");
		return $out;
	}

	#var_dump("no boundary in this message: $top");
	#return array(new mime_part($text, array('content-type' => 'text/plain', 'content-transfer-encoding' => 'quoted-printable')));
	// use quoted-printable if it looks like it contains encoded characters.  skip otherwise to avoid false decoding.
	if ( preg_match_all('/=(?:20|3F|5F|0A|0D|A0)/i', $text, $dummy) > 1 )
		return array(new mime_part($text, array('content-type' => 'text/plain', 'content-transfer-encoding' => 'quoted-printable')));
	elseif ( preg_match('#^\s*[A-Za-z0-9+/]{64,76}[\r\n]+[A-Za-z0-9+/=]{1,76}[\r\n]+#ms', trim($text) ) && base64_decode( trim($text), true ) ) // looks like a base64 block and is just base64 content
		return array(new mime_part($text, array('content-type' => 'text/plain', 'content-transfer-encoding' => 'base64')));
	else
		return array(new mime_part($text, array('content-type' => 'text/plain')));
}

function mime_header_decode( $str ) {
	if ( HAS_EXT_IMAP ) {
		$r = imap_mime_header_decode( $str );
		$out = '';
		foreach ( $r as $e )
			$out .= $e->text;
		return $out;
	} elseif ( HAS_EXT_ICONV ) {
		$r = iconv_mime_decode( $str, ICONV_MIME_DECODE_CONTINUE_ON_ERROR );
		return $r;
	} else {
		return $str;
	}
}

// return the $i'th part of a list of mime_part objects, that matches the specified content_type
// $i is the index number, so $i=0 means find the first matching part, $i=1 is the second
function find_first_part($parts, $type='text/plain', $i=0) {
	foreach ( $parts as $part ) {
		if ( strpos( $part->content_type, $type ) === 0 && $i-- <= 0 )
			return $part;
	}
}