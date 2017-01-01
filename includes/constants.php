<?php

// use these to avoid repeated function_exists/extension_loaded calls
define('HAS_EXT_IMAP', extension_loaded('imap'));
define('HAS_EXT_MBSTRING', extension_loaded('mbstring'));
define('HAS_EXT_ICONV', defined('ICONV_VERSION'));

// assorted useful stuff
define('MYSQL_STRFTIME_FORMAT', '%Y-%m-%d %H:%M:%S');