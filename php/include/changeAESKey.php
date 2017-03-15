<?php

$oldkey="changethis!";
$newkey="changethis!";

define('BASE_PATH', "/usr/share/kopano-webapp/");
define('PATH_PLUGIN_DIR' , "plugins" );

require_once (BASE_PATH . PATH_PLUGIN_DIR . "/fetchmail/config.php");
require_once (BASE_PATH . PATH_PLUGIN_DIR . "/fetchmail/php/class.pluginfetchmailmodule.php");

$fetchmailModule = new PluginFetchmailModule(null, null);

$fetchmailModule->changeAESKey($oldkey, $newkey);


?>