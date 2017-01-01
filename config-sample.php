<?php

// ** MySQL settings ** //
define('DB_NAME', 'putyourdbnamehere');    // The name of the database
define('DB_USER', 'usernamehere');     // Your MySQL username
define('DB_PASSWORD', 'yourpasswordhere'); // ...and password
define('DB_HOST', 'localhost');    // 99% chance you won't need to change this value


// IMAP settings - uncomment and set if you plan to use bin/spress-imap-pull.php
// sample settings are valid for Gmail, just fill in IMAP_USER and IMAP_PASSWORD
#define('IMAP_HOST', 'imap.gmail.com');
#define('IMAP_PORT', '993');
#define('IMAP_USER', 'xxx@xxx.xxx');
#define('IMAP_PASSWORD', 'xxx');
#define('IMAP_MAILBOX', 'INBOX');

// SMTP settings - uncomment and set if you want to use an exteral SMTP server to send email
// sample settings are valid for Gmail, just fill in IMAP_USER and IMAP_PASSWORD
#define('SMTP_HOST', 'ssl://smtp.gmail.com');
#define('SMTP_PORT', '465');
#define('SMTP_USER', 'xxx@xxx.xxx');
#define('SMTP_PASSWORD', 'xxx');

// base URL of the support administration site
$site_domain = 'https://support.example.com';
// base path of the support administration site
$site_path = '/sp';
$site_url = $site_domain . $site_path;

// admin email - used for occasional administrative notices
$admin_email = 'nobody@example.com';
// support email - this is used as the From address when replying to tickets, it should usually match the SMTP_USER
$support_email = 'support@example.com';

// used as the sender domain in email replies
$email_domain = 'example.com';

// You can have multiple installations in one database if you give each a unique prefix
$table_prefix  = 'support_';   // Only numbers, letters, and underscores please!

// Uncomment and set as required if you want to share user logins with WordPress or bbPress.
#$user_table_prefix = 'wp_';

// Leave this line intact until installation is complete.
// Comment it out by adding a # at the beginning of the next line when instructed by the installer.
define( 'IS_INSTALLING', true );

// Uncomment the plugins you would like to activate in SupportPress
#define( 'ENABLE_CUSTOM_CSS', true );
#define( 'ENABLE_CUSTOM_JS', true );
#define( 'ENABLE_FAUXLDERS', true );
#define( 'ENABLE_SB_HISTORY', true );
#define( 'ENABLE_SB_SUMMARY', true );
#define( 'ENABLE_SIDEBAR_MODS', true );

/* That's all, stop editing! */ 

if ( !defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname(__FILE__) ); 
}

if ( !defined( 'BACKPRESS_PATH' ) ) {
	define( 'BACKPRESS_PATH', ABSPATH . '/includes/backpress/' );
}
