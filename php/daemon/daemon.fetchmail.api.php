<?php

/**
 * API to call PHP functions from the kopano_fetchmail.pl script.
 * 
 * Usage:
 * 
 * 	php daemon.fetchmail.api.php <options> <commands>
 * 
 * 	Options:
 * 		--base-path <path> 							Path to the kopano-webapp installation (default: /usr/share/kopano-webapp).
 * 		--plugin-dir <directory>					Name of the plugins directory (default: plugins). 
 * 	
 * 	Commands:
 * 		--list										Lists all Fetchmail accounts to poll.
 * 		--update <id> <status_code> <log_message> 	Updates a Fetchmail account entry with the given status_code and log_message.
 * 													The log_message is expected as base64 encoded.
 * 
 */

//parse command line arguments.
if(!isset($argv) || is_null($argv) || empty ($argv))
	die("Empty command line arguments.\n");

if($key_path = array_search("--base-path", $argv))
	$base_path = $argv[++$key_path];
if($key_plugin = array_search("--plugin-dir", $argv))
	$plugin_directory = $argv[++$key_plugin];

//test if base_path and plugin_dir are set
if(isset($base_path) && !is_null($base_path) && !empty($base_path))
	define('BASE_PATH', $base_path . "/");
else
	die("--base-path cannot be empty.\n");
if(isset($plugin_directory) && !is_null($plugin_directory) && !empty($plugin_directory))
	define('PATH_PLUGIN_DIR' , $plugin_directory );
else
	die("--plugin-dir cannot be empty.\n");


//set an exception handler and disable error reporting otherwise.
error_reporting(0);
function exception_handler($exception) {
	die($exception->getMessage()."\n");
}
set_exception_handler('exception_handler');

//look for commands and execute function associated.
if(array_search("--list",$argv))
	listFetchmailAccountsToPoll();
else if($key = array_search("--update",$argv)) {
	//the next 3 values should be the id, status_code and log_message to write to the db.
	$id = $argv[++$key];
	$status_code = $argv[++$key];
	$log_message = base64_decode($argv[++$key]);
	if($ret = updateFetchmailAccountStatus($id,$status_code,$log_message))
		print "0\n";
	else
		die("Wrong parameters for command --update.\nError Message: ".$ret."\n");
}



/**
 * Prints a list of all Fetchmail Accounts that should be polled to stdout.
 * Format of output:
 * <key>:::<value>|||<key>:::<value>||| ... |||<key>:::<value>
 */
function listFetchmailAccountsToPoll()
{
	require_once (BASE_PATH . PATH_PLUGIN_DIR . "/fetchmail/config.php");
	require_once (BASE_PATH . PATH_PLUGIN_DIR . "/fetchmail/php/class.pluginfetchmailmodule.php");
	
	$fetchmailModule = new PluginFetchmailModule(null, null);
	
	foreach($fetchmailModule->getListOfFetchmailAccountsToPoll() as $account)
	{
		//modify entry, to switch the polling_type and src_protocol to the value fetchmail needs.
		//also add an entry if ssl is needed
		if($account['src_protocol'] == PluginFetchmailModule::FETCHMAIL_PROTOCOL_IMAPS || $account['src_protocol'] == PluginFetchmailModule::FETCHMAIL_PROTOCOL_POP3S)
			$account['ssl'] = '1';
		else
			$account['ssl'] = '0';
		$account['src_protocol'] = $fetchmailModule->convertProtocolConstant($account['src_protocol']);
		$account['src_polling_type'] = $fetchmailModule->convertPollingTypeConstant($account['src_polling_type']);
		//dont forget to decrypt the password... but still keep it base64 encoded. had problems otherwise with ultra complex passwords.
		$account['src_password'] = base64_encode($fetchmailModule->decryptPassword($account['src_password']));
		
		$line = "";
		foreach(array_keys($account) as $key)
			$line .= $key . "|" . $account[$key] . ",";
		$line = substr($line, 0, -1);
		if(!empty($line))
			print $line . "\n";
	}
}

/**
 * Updates the Fetchmail account table entry with $id with the given $status_code and $log_message
 * @param integer $id ID of the Fetchmail account.
 * @param integer $status_code Status code returned from the fetchmail process.
 * @param String $log_message Log message from the fetchmail process.
 */
function updateFetchmailAccountStatus($id,$status_code,$log_message) {
	if(!isset($id,$status_code,$log_message) || empty($id))
		return false;

	require_once (BASE_PATH . PATH_PLUGIN_DIR . "/fetchmail/config.php");
	require_once (BASE_PATH . PATH_PLUGIN_DIR . "/fetchmail/php/class.pluginfetchmailmodule.php");
	
	$fetchmailModule = new PluginFetchmailModule(null, null);
	
	return $fetchmailModule->updateFetchmailAccountStatus($id, $status_code, $log_message);
}

?>
