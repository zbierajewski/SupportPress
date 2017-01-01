This is SupportPress version 1.0.


What Is SupportPress

SupportPress is a simple help desk tool. Clients can submit tickets via email and happiness engineers can respond to the tickets via a web interface.

-----------------------

Who’s behind this project

SupportPress was born at Automattic. Automattic is the parent company of Code Poet, WordPress.com, Polldaddy, Akismet, VaultPress, Gravatar, and more. We’re over 100 people working from over seventy cities, and we’re always hiring.

-----------------------

Requirements

SupportPress runs on a LAMP stack. In addition, you’ll need an IMAP mailbox and SMTP server (SupportPress is set up to use GMail by default).

-----------------------

How to Contribute

You can follow the changes to our source code in Trac. The login system uses the WordPress.org registration.

-----------------------

Source Code

The source code is available via Subversion (SVN). You can browse the source or point your SVN client to the latest trunk, where you’ll find the latest development build. If you’ve never heard of SVN, you can learn more about Subversion on Wikipedia.

-----------------------

Installation

1) Make sure you have an IMAP mailbox and SMTP server suitable for sending and receiving support emails. Gmail will work fine, as will Google Apps mail.
2) Install the contents of the sp folder and all subfolders on a web server.
3) Visit http://example.com/sp/index.php (replacing that with the correct domain and path to your sp folder of course) and follow the instructions. Make sure you uncomment and set the IMAP and SMTP constants in your config.php.
4) Log in as ‘supportpressadmin’ at http://example.com/sp/index.php and confirm that the installation worked.
5) Create a cron job to run sp/bin/spress-imap-pull.php at regular intervals.
6) Create a test thread by clicking on the New Thread link. Send it to your own email address. Check that replies appear in SupportPress when spress-imap-pull has run.

-----------------------

Plugins

customcss / customjs:  Allows adding custom CSS and JS within a sidebar item.
fauxlders: Adds a “folders” menu to the sidebar with basic folder organization.
force-ssl: Forces SSL protocol / redirects to https if http.
sidebar-history: Enables ticket history on sidebar for email address.
sidebar-summary: Gives a summary for tickets on the sidebar of SupportPress.
thread-status: This shows a sidebar box with near-real-time info about which users are currently viewing a thread.