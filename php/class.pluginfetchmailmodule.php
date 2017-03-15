<?php


require_once(BASE_PATH . PATH_PLUGIN_DIR . "/fetchmail/php/exceptions/class.FetchmailDataException.php");
require_once(BASE_PATH . PATH_PLUGIN_DIR . "/fetchmail/php/exceptions/class.FetchmailDriverException.php");
require_once(BASE_PATH . PATH_PLUGIN_DIR . "/fetchmail/php/include/class.AESCrypt.php");
require_once(BASE_PATH . '/server/includes/modules/class.module.php');
/**
 * PluginFetchmailModule to communicate with the Database.
 */

class PluginFetchmailModule extends Module
{
	
	//the protocol/polling type is saved as a constant in the DB
	const FETCHMAIL_PROTOCOL_IMAP = 1;
	const FETCHMAIL_PROTOCOL_IMAPS = 2;
	const FETCHMAIL_PROTOCOL_POP3 = 5;
	const FETCHMAIL_PROTOCOL_POP3S = 6;
	const FETCHMAIL_POLLING_TYPE_FETCHALL = 1;
	const FETCHMAIL_POLLING_TYPE_KEEP = 5;
	
	//the protocol/polling type integer <=> string constants
	const FETCHMAIL_PROTOCOL_IMAP_STRING = "IMAP";
	//TODO legacy code that IMAP/IMAPS where seperate - delete in the future?
	const FETCHMAIL_PROTOCOL_IMAPS_STRING = "IMAP";
	const FETCHMAIL_PROTOCOL_POP3_STRING = "POP3";
	const FETCHMAIL_PROTOCOL_POP3S_STRING = "POP3";
	const FETCHMAIL_POLLING_TYPE_FETCHALL_STRING = "FETCHALL";
	const FETCHMAIL_POLLING_TYPE_KEEP_STRING = "KEEP";
	
	//success/error response constants
	const FETCHMAIL_ACCOUNT_SAVE_SUCCESS_RESPONSE = 42;
	const FETCHMAIL_ACCOUNT_SAVE_ERROR_PARAMETER_RESPONSE = 43;
	const FETCHMAIL_ACCOUNT_DELETE_SUCCESS_RESPONSE = 142;
	const FETCHMAIL_ACCOUNT_POLL_NOW_SUCCESS_RESPONSE = 1142;
	
	
	private $db_driver = '';
	private $db_host = '';
	private $db_port = '';
	private $db_database = '';	
	private $db_table = 'fetchmail';
	private $db_username = '';
	private $db_password = '';
	private $aes_key = '';
	
	private $db_connection = null;

	/**
	 * Constructs a new PluginFetchmailModule. 
	 * Can also be used outside of the kopano-webapp, if certain config parameters are set via define().
	 * @param int $id unique id.
	 * @param array $data list of all actions.
	 */
	function __construct($id, $data)
	{
		parent::__construct($id, $data);

		$this->db_driver = PLUGIN_FETCHMAIL_DATABASE_DRIVER;
		$this->db_host = PLUGIN_FETCHMAIL_DATABASE_HOST;
		$this->db_port = PLUGIN_FETCHMAIL_DATABASE_PORT;
		$this->db_username = PLUGIN_FETCHMAIL_DATABASE_USER;
		$this->db_password = PLUGIN_FETCHMAIL_DATABASE_USER_PASSWORD;
		$this->db_database = PLUGIN_FETCHMAIL_DATABASE;
		$this->db_table = PLUGIN_FETCHMAIL_DATABASE_PREFIX . "_" . $this->db_table;
		//Since the Database server is sometimes seperate from the kopano server, better store the password secure.
		//obv. the aes_key has to be saved secure on the machine the webapp is running.
		$this->aes_key = PLUGIN_FETCHMAIL_PASSWORDS_AES_KEY;

		parent::__construct($id, $data);
	}
	
	/**
	 * Executes all the actions in the $data variable.
	 */
	function execute()
	{
		foreach($this->data as $actionType => $actionData)
		{
			if(isset($actionType)) {
				try {
					switch($actionType)
					{
						case 'list':
							$items = $this->getListOfFetchmailAccounts();
							$data['page'] = array();
							$data['page']['start'] = 0;
							$data['page']['rowcount'] = count($items);
							$data['page']['totalrowcount'] = $data['page']['rowcount'];
							$data = array_merge($data, array('item' => $items));
							$this->addActionData('list', $data);
							$GLOBALS['bus']->addData($this->getResponseData());
							break;
						case 'save':
							$response = array();
							$val = false;
							//handle this exception here - it can be displayed better on client side.
							try{
								$val = $this->saveModifyAccount($this->data);
							}catch (FetchmailDataException $e) {
								$response['code'] =  self::FETCHMAIL_ACCOUNT_SAVE_ERROR_PARAMETER_RESPONSE;
								$response['message'] = $e->getDisplayMessage();
								$response['parameter'] = $e->getMessage();
							}
							if($val === TRUE)
								$response['code'] = self::FETCHMAIL_ACCOUNT_SAVE_SUCCESS_RESPONSE;
							$this->addActionData('accountsave', $response);
							$GLOBALS['bus']->addData($this->getResponseData());
							break;
						case 'delete':
							$response = array();
							$val = $this->deleteAccount($this->data);
							if($val === TRUE)
								$response['code'] = self::FETCHMAIL_ACCOUNT_DELETE_SUCCESS_RESPONSE;
							elseif ($val === FALSE)
								$response['error'] = dgettext('plugin_fetchmail', 'Something went wrong. Please contact your Administrator.');
							else
								$response['error'] = dgettext('plugin_fetchmail', $val);
							
							$this->addActionData('accountdelete', $response);
							$GLOBALS['bus']->addData($this->getResponseData());
							break;
						case 'pollnow':
							$response = array();
							$val = $this->pollNow($this->data);
							if($val === TRUE)
								$response['code'] = self::FETCHMAIL_ACCOUNT_POLL_NOW_SUCCESS_RESPONSE;
							elseif ($val === FALSE)
								$response['error'] = dgettext('plugin_fetchmail', 'Something went wrong. Please contact your Administrator.');
							else
								$response['error'] = dgettext('plugin_fetchmail', $val);
								
							$this->addActionData('accountpollnow', $response);
							$GLOBALS['bus']->addData($this->getResponseData());
							break;
						default:
							$this->handleUnknownActionType($actionType);
					}
					//the rest of the exceptions should (hopefully) not happen, so handle them via sendFeedback()
				} catch (FetchmailDataException $e) {
					$this->sendFeedback(false, parent::errorDetailsFromException($e));
				} catch (FetchmailDriverException $e) {
					$this->sendFeedback(false, parent::errorDetailsFromException($e));
				} catch (Exception $e) {
					$this->sendFeedback(false, parent::errorDetailsFromException($e));
				}
			}
		}
	}
	
	/**
	 * Opens a new DatabaseConnection depending upon the specified driver in the config.
	 * If one is already open, only returns the link.
	 * @return DatabaseConnection
	 * @throws FetchmailDriverException
	 */
	private function getDatabaseConnection()
	{
		if(!is_null($this->db_connection))
			return $this->db_connection;
		
		//If you want to implement more database drivers, add them here after writing a class that extends MysqlDatabaseDriver
		if($this->db_driver == "mysql")
		{
			require_once(BASE_PATH . PATH_PLUGIN_DIR . "/fetchmail/php/drivers/class.MysqlDatabaseDriver.php");
			$this->db_connection = new MysqlDatabaseDriver($this->db_host, $this->db_port, $this->db_database, $this->db_table, $this->db_username, $this->db_password, $this->aes_key);
		} 
		else 
			throw new FetchmailDriverException('The db_driver \''.$this->db_driver.'\' does not exist.', 0, null, dgettext('plugin_fetchmail', dgettext('plugin_fetchmail', 'Cannot connect to Database. Please contact your Administrator.')));
		
		return $this->db_connection;
	}
	/**
	 * Returns a list of existing fetchmail accounts.
	 * @return array List of fetchmail accounts.
	 */
	private function getListOfFetchmailAccounts()
	{
		return $this->getDatabaseConnection()->getListOfFetchmailAccounts($this->getKopanoUIDFromEncryptionStore());
	}
	
	/**
	 * Function to get all Fetchmail accounts were the Last Polling time is behind the Polling Frequency.
	 * @return array List of accounts to poll.
	 */
	public function getListOfFetchmailAccountsToPoll()
	{
		return $this->getDatabaseConnection()->getListOfFetchmailAccountsToPoll();
	}
	
	/**
	 * Updates the Fetchmail account table entry with $id with the given $status_code and $log_message.
	 * @param integer $id ID of the Fetchmail account.
	 * @param integer $status_code Status code returned from the fetchmail process.
	 * @param String $log_message Log message from the fetchmail process.
	 * @return boolean|String Returns TRUE if sucessfull otherwise the Errror Message.
	 */
	public function updateFetchmailAccountStatus($id,$status_code,$log_message)
	{
		return $this->getDatabaseConnection()->updateFetchmailAccountStatus($id,$status_code,$log_message);
	}
	
	/**
	 * Changes the src_password encryption in the Database.
	 * IMPORTANT: Remember to change the config.php afterwards.
	 * @param String $oldkey The old AES Encryption key.
	 * @param String $newkey The new AES Encryption key.
	 */
	public function changeAESKey($oldkey, $newkey)
	{
		$accounts = $this->getDatabaseConnection()->getListOfAllFetchmailAccounts();
		foreach ($accounts as $account){
			$account = $account['props'];
			$account['src_password'] = $this->encryptPassword($this->decryptPassword($account['src_password'],$oldkey),$newkey);
			$this->getDatabaseConnection()->saveModifyAccount($account);
		}
	}
	
	/**
	 * Saves a new Fetchmail Account or modifys an existing in the database.
	 * @param Array $data
	 * @return boolean|String TRUE if operation succeded, otherwise the error message.
	 * @throws FetchmailDataException, FetchmailDriverException
	 */
	private function saveModifyAccount($data)
	{
		if(!isset($data) || is_null($data) || empty($data['save']))
			throw new FetchmailDataException('save', 0, null, dgettext('plugin_fetchmail', "The parameter 'save' cannot be empty."));
		$account = $data['save'];
		//doesnt get this from client, has to be set on server
		$account['kopano_uid'] = $this->getKopanoUIDFromEncryptionStore();
		//test if the account is new (entryid is empty)
		$newAccount = empty($account['entryid']);
		$var = $this->testClientAccountData($account,$newAccount);
		if(!($var === TRUE))
			throw new FetchmailDataException($var, 0, null, dgettext('plugin_fetchmail', "The parameter '".$var."' cannot be empty."));
		//encrypt the password (if it exists) before submitting it to the database
		if(!empty($account['src_password']))
			$account['src_password'] = $this->encryptPassword($account['src_password']);		
		$val = $this->getDatabaseConnection()->saveModifyAccount($account);
		if(!($val === TRUE))
			throw new FetchmailDriverException('Something went wrong while saving the Account in the Database', 0, null, dgettext('plugin_fetchmail', "Something went wrong. Please contact your Administrator."));
		return true;
			
			
	}
	
	/**
	 * Deletes a Fetchmail Account from the database.
	 * @param array $data Array which has the parameter of the entry ID ('delete' -> 'id'). 
	 * @return boolean|String TRUE if operation succeded, otherwise the error message.
	 * @throws FetchmailDataException
	 */
	private function deleteAccount($data)
	{
		if(!isset($data) || is_null($data) || empty($data['delete']))
			throw new FetchmailDataException('delete', 0, null, dgettext('plugin_fetchmail', "The parameter 'delete' cannot be empty."));
		$id = $this->data['delete'];
		return $this->getDatabaseConnection()->deleteAccount($id);

	}
	
	/**
	 * Immediately polls the account by setting the last_poll date in the time farther back then the polling_frequency.
	 * @param Array $data Array which has the parameter of the entry ID ('delete' -> 'id').
	 * @return boolean|String TRUE if operation succeded, otherwise the error message.
	 * @throws FetchmailDataException
	 */
	private function pollNow($data)
	{
		if(!isset($data) || is_null($data) || empty($data['pollnow']))
			throw new FetchmailDataException('pollnow', 0, null, dgettext('plugin_fetchmail', "The parameter 'pollnow' cannot be empty."));
		$id = $this->data['pollnow'];
		return $this->getDatabaseConnection()->pollNow($id);
	}
	
	/**
	 * Encrypts a given password with the $aes_key.
	 * @param String $password Password to encrypt.
	 * @param String $key Alternative key, if empty $aes_key is used.
	 * @return boolean|String FALSE if something goes wrong, otherwise the encrypted password with an appended IV (Seperator ":").
	 */
	public function encryptPassword($password,$key=null) {
		//TODO send warning to user if aes_key is still "changethis!" ?
		$crypt = new AESCrypt();
		if(isset($key) && !empty($key))
			return $crypt->encrypt($password, $key);
		return $crypt->encrypt($password, $this->aes_key);
		
	}
	
	/**
	 * Decrypts the given password with the $aes_key.
	 * @param String $password Password to encrypt.
	 * @param String $key Alternative key, if empty $aes_key is used.
	 * @return boolean|String FALSE if something goes wrong, otherwise the decrypted password.
	 */
	public function decryptPassword($password,$key=null) {
		$crypt = new AESCrypt();
		if(isset($key) && !empty($key))
			return $crypt->decrypt($password, $key);
		return $crypt->decrypt($password, $this->aes_key);
	}
	
	/**
	 * Tests if all the required parameters are set in the account data sent by the client. 
	 * @param array $data Array with the account data.
	 * @param boolean $newAccount If this parameter is TRUE then src_password can not be empty!
	 * @return boolean|String TRUE if valid otherwise the parameter that is invalid.
	 */
	private function testClientAccountData($data = null, $newAccount = TRUE)
	{
		$parameters = ['kopano_uid','kopano_mail','src_server','src_port','src_protocol'
						,'src_polling_type','src_user','polling_freq']; 
		//entryid cannot be empty if we modify an account
		if($newAccount === FALSE && empty($data['entryid']))
			return "entryid";		
		foreach ($parameters as $p) {
			if(empty($data[$p]))
				return $p;
		}
		//test only if it's a new account
		if($newAccount === TRUE && empty($data['src_password']))
			return "src_password";
		return true;
	}
	
	/**
	 * Loads the KopanoUID from the Encryption Store.
	 * Will only work if called inside the kopano-webapp.
	 * return String The kopano UID of the User.
	 */
	private function getKopanoUIDFromEncryptionStore()
	{
		require_once(BASE_PATH . 'server/includes/core/class.encryptionstore.php');
		// Get the username from the Encryption store
		$encryptionStore = EncryptionStore::getInstance();
		return $encryptionStore->get('username');
	}
	
	/**
	 * Converts the integer or string constant of FETCHMAIL_POLLING_TYPE_* to the
	 * specific counterpart.
	 * @param string|integer $type
	 * @return string|integer Either a string or integer depending on the $type argument. returns false if $type is null.
	 */
	public function convertPollingTypeConstant($type)
	{
		if(isset($type))
		{
			if($type == self::FETCHMAIL_POLLING_TYPE_FETCHALL)
				return self::FETCHMAIL_POLLING_TYPE_FETCHALL_STRING;
			else if($type == self::FETCHMAIL_POLLING_TYPE_FETCHALL_STRING)
				return self::FETCHMAIL_POLLING_TYPE_FETCHALL;
			if($type == self::FETCHMAIL_POLLING_TYPE_KEEP)
				return self::FETCHMAIL_POLLING_TYPE_KEEP_STRING;
			else if($type == self::FETCHMAIL_POLLING_TYPE_KEEP_STRING)
				return self::FETCHMAIL_POLLING_TYPE_KEEP;
		}
		return false;
	}
	
	/**
	 * Converts the integer or string constant of FETCHMAIL_PROTOCOL_* to the
	 * specific counterpart.
	 * @param string|integer $protocol
	 * @return string|integer Either a string or integer depending on the $protocol argument. Returns false if $protocol is null.
	 */
	public function convertProtocolConstant($protocol)
	{
		if(isset($protocol))
		{
			if($protocol == self::FETCHMAIL_PROTOCOL_IMAP)
				return self::FETCHMAIL_PROTOCOL_IMAP_STRING;
			elseif($protocol == self::FETCHMAIL_PROTOCOL_IMAP_STRING)
				return self::FETCHMAIL_PROTOCOL_IMAP;
			elseif($protocol == self::FETCHMAIL_PROTOCOL_IMAPS)
				return self::FETCHMAIL_PROTOCOL_IMAPS_STRING;
			elseif($protocol == self::FETCHMAIL_PROTOCOL_IMAPS_STRING)
				return self::FETCHMAIL_PROTOCOL_IMAPS;
			elseif($protocol == self::FETCHMAIL_PROTOCOL_POP3)
				return self::FETCHMAIL_PROTOCOL_POP3_STRING;
			elseif($protocol == self::FETCHMAIL_PROTOCOL_POP3_STRING)
				return self::FETCHMAIL_PROTOCOL_POP3;
			elseif($protocol == self::FETCHMAIL_PROTOCOL_POP3S)
				return self::FETCHMAIL_PROTOCOL_POP3S_STRING;
			elseif($protocol == self::FETCHMAIL_PROTOCOL_POP3S_STRING)
				return self::FETCHMAIL_PROTOCOL_POP3S;
		}
		return false;
	}
	
};
?>
