<?php
/** Enable the fetchmail plugin for all clients **/
define('PLUGIN_FETCHMAIL_USER_DEFAULT_ENABLE', false);
/** Set the AES Key to encrypt the Account Passwords in the Database. IMPORTANT: If changed all saved passwords are lost! **/
define('PLUGIN_FETCHMAIL_PASSWORDS_AES_KEY', "changethis!");
/** Define the Database Driver. At the moment only mysql **/
define('PLUGIN_FETCHMAIL_DATABASE_DRIVER', "mysql");
/** Database Host. Example localhost:3306 for mysql **/
define('PLUGIN_FETCHMAIL_DATABASE_HOST', "localhost");
/** Databse Port - default: 3306 **/
define('PLUGIN_FETCHMAIL_DATABASE_PORT', "3306");
/** Database User. Needs CREATE, INSERT, SELECT, UPDATE rights **/
define('PLUGIN_FETCHMAIL_DATABASE_USER', "kopano_fetchmail");
/** Database User Password **/
define('PLUGIN_FETCHMAIL_DATABASE_USER_PASSWORD', "password");
/** Database in which to save fetchmail configuration. Creates a table "fetchmail" **/
define('PLUGIN_FETCHMAIL_DATABASE', "kopano");
/** Prefix for the _fetchmail table **/
define('PLUGIN_FETCHMAIL_DATABASE_PREFIX', "kopano")
?>
