<?php
require_once(BASE_PATH . PATH_PLUGIN_DIR . "/fetchmail/php/exceptions/class.FetchmailDriverException.php");
require_once(BASE_PATH . PATH_PLUGIN_DIR . "/fetchmail/php/exceptions/class.FetchmailDataException.php");
/**
 * To save the user accounts via mysql use this class.
 * If you want to implement a different driver use this as a parent class.
 */
class MysqlDatabaseDriver {

	private $db_host = '';
	private $db_port = '';
	private $db_database = '';
	private $db_table = 'fetchmail';
	private $db_username = '';
	private $db_password = '';
	
	private $db_connection = null;
	
	/**
	 * Constructs a new MysqlDatabaseDriver and trys to open a connection to the database.
	 * Also creates the table in the Database if it doesnt exist.
	 * @param String $db_host Database Host (ex: localhost).
	 * @param String $db_port Database Port (ex: 3306).
	 * @param String $db_database Database to connect.
	 * @param String $db_table Database table to use.
	 * @param String $db_username Database username (ex: kopano).
	 * @param String $db_password Database password (ex: password).
	 * @throws FetchmailDriverException
	 */
	function __construct($db_host,$db_port,$db_database,$db_table,$db_username,$db_password)
	{
		$this->db_host = $db_host;
		$this->db_port = $db_port;
		$this->db_database = $db_database;
		$this->db_table = $db_table;
		$this->db_username = $db_username;
		$this->db_password = $db_password;
		
		$this->db_connection = new mysqli("p:" . $this->db_host, $this->db_username, $this->db_password, $this->db_database, $this->db_port);
		if($this->db_connection->connect_error)
			throw new FetchmailDriverException('Cannot connect to Database. Please contact your Administrator.', 0, null, dgettext('plugin_fetchmail', dgettext('plugin_fetchmail', 'Cannot connect to Database. Please contact your Administrator.')));
		
		//check if our table exists, if not create it
		if(!$this->checkIfFetchmailTableExists(true))
			throw new FetchmailDriverException('Cannot connect to Database. Please contact your Administrator.', 0, null, dgettext('plugin_fetchmail', dgettext('plugin_fetchmail', 'Cannot connect to Database. Please contact your Administrator.')));
	}
	
	/**
	 * Checks if the "fetchmail" table in the Database exists and creates it if $create = true.
	 * @param String create[optional] if true, creates the table
	 */
	private function checkIfFetchmailTableExists($create = false)
	{
		$con = $this->getDatabaseConnection();
		if($stmt = $con->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?"))
		{
			if($stmt->bind_param("ss", $this->db_database, $this->db_table))
			{
				$stmt->execute();
				$result = $stmt->get_result();
				if($result->fetch_row()[0] == 0)
				{
					$stmt->close();
					if($create)
						return $this->createFetchmailTable();
					return false;
				}
				$stmt->close();
				return true;
			}
		}		
		return false;
			
	}
	
	/**
	 * Reads the sql/createTable.sql and executes it.
	 */
	private function createFetchmailTable()
	{
		$stmt = file_get_contents(BASE_PATH . PATH_PLUGIN_DIR . "/fetchmail/php/sql/createTable.sql");
		if($stmt === FALSE)
			throw new FetchmailDriverException('Cannot read the sql/createTable.sql file.', 0, null, dgettext('plugin_fetchmail', dgettext('plugin_fetchmail', 'Cannot connect to Database. Please contact your Administrator.')));
		//replace tbl_name in the sql script with $db_table
		$stmt = str_replace("tbl_name", $this->db_table, $stmt);
		$con = $this->getDatabaseConnection();
		if($prep_stmt = $con->prepare($stmt))
		{
			$prep_stmt->execute();
			$prep_stmt->close();
	
		}
		if(!$this->checkIfFetchmailTableExists(false))
			throw new FetchmailDriverException('Cannot create the \''.$this->db_table.'\' table.', 0, null, dgettext('plugin_fetchmail', dgettext('plugin_fetchmail', 'Cannot connect to Database. Please contact your Administrator.')));
		
			
		return true;
	}
	
	/**
	 * Returns a list of existing fetchmail accounts that belong to $kopano_uid.
	 * The structure of the return array is:
	 * "entryid" -> value
	 * "kopano_uid" -> value
	 * "kopano_mail" -> value
	 * "src_server" -> value
	 * "src_port" -> value
	 * "src_protocol" -> value
	 * "src_polling_type" ->
	 * "src_user" -> value
	 * "src_password" -> value of src_user
	 * "polling_freq" -> value
	 * "last_polling" -> value as unix_timestamp
	 * "last_update" -> value as unix_timestamp
	 * "last_status_code" -> value
	 * "last_log_message" -> value
	 * @param String $kopano_uid Kopano UID of the user.
	 * @return Array array of fetchmail accounts.
	 */
	public function getListOfFetchmailAccounts($kopano_uid)
	{
		if(!isset($kopano_uid) || is_null($kopano_uid))
			throw new FetchmailDriverException('No kopano_uid has been specified.', 0, null, dgettext('plugin_fetchmail', dgettext('plugin_fetchmail', 'Cannot connect to Database. Please contact your Administrator.')));
			
		$items = array();
	
		$con = $this->getDatabaseConnection();
		// id needs to be saved as entryid for the javascript store handling
		// last_polling is converted to unix_timestamp to easier import it in javascript
		if($stmt = $con->prepare("SELECT id as entryid,kopano_uid,kopano_mail,src_server,src_port,src_protocol,src_polling_type,src_user,polling_freq,unix_timestamp(last_polling) as last_polling,unix_timestamp(last_update) as last_update,last_status_code,last_log_message FROM " . $this->db_table . " WHERE kopano_uid = ?"))
		{
			$stmt->bind_param("s", $kopano_uid);
			$stmt->execute();
			$result = $stmt->get_result();
				
			while ($row = $result->fetch_assoc()) {
				//dont convert anymore, the js does this now!
				//$row['src_protocol'] = $this->convertProtocolConstant($row['src_protocol']);
				//$row['src_polling_type'] = $this->convertPollingTypeConstant($row['src_polling_type']);
				array_push($items, array('props' => $row));
			}
			$result->free();
			$stmt->close();
		}
		return $items;
	}
	
	/**
	 * Returns a List of all existing Fetchmail Accounts.
	 * The List includes the src_password (encrypted).
	 * @return Array array of fetchmail accounts.
	 */
	public function getListOfAllFetchmailAccounts()
	{
		$items = array();
		
		$con = $this->getDatabaseConnection();
		// id needs to be saved as entryid for consistency ;-)
		if($stmt = $con->prepare("SELECT id as entryid,kopano_uid,kopano_mail,src_server,src_port,src_protocol,src_polling_type,src_user,src_password,polling_freq,unix_timestamp(last_polling) as last_polling,unix_timestamp(last_update) as last_update,last_status_code,last_log_message FROM " . $this->db_table))
		{
			$stmt->execute();
			$result = $stmt->get_result();
		
			while ($row = $result->fetch_assoc()) {
				array_push($items, array('props' => $row));
			}
			$result->free();
			$stmt->close();
		}
		return $items;
	}
	
	/**
	 * Function to get a List of Fetchmail accounts where the Last Polling Time is behind the Polling Frequency.
	 * @return array Array of Fetchmail accounts to be polled.
	 */
	public function getListOfFetchmailAccountsToPoll()
	{
		$items = array();
		$con = $this->getDatabaseConnection();
		
		if($stmt = $con->prepare("SELECT id as entryid,kopano_mail,src_server,src_port,src_protocol,src_polling_type,src_user,src_password FROM " . $this->db_table . " WHERE (SUBTIME(current_timestamp,polling_freq) > last_polling) OR last_polling IS NULL"))
		{
			$stmt->execute();
			$result = $stmt->get_result();
			while ($row = $result->fetch_assoc())
			{
				array_push($items,$row);
			}
			$result->free();
			$stmt->close();
		}
		return $items;
	}
	
	/**
	 * Saves or modifys the account in the database.
	 * See getListOfFetchmailAccounts for an overview how the array should look.
	 * @param Array $data Array with the account information.
	 * @return boolean|String TRUE if the operation succeeded, otherwise the error message as string.
	 */
	public function saveModifyAccount($data)
	{
		if(!isset($data) || is_null($data))
			return false;
		
		$con = $this->getDatabaseConnection();
		
		//make sure the time value for polling_frequency is in the right format
		$pol = $this->getDateFromTimeString($data['polling_freq']);
		if($pol === FALSE)
			throw new FetchmailDataException('polling_freq', 0, null, dgettext('plugin_fetchmail', dgettext('plugin_fetchmail', "Wrong value for Polling Frequency. Has to be in minutes.")));
		$data['polling_freq'] = $pol;
		if($data['entryid'] == '')
		{
			if($stmt = $con->prepare("INSERT INTO " .$this->db_table."(kopano_uid,kopano_mail,src_server,src_port,src_protocol,src_polling_type,src_user,src_password,polling_freq) VALUES(?,?,?,?,?,?,?,?,?)"))
			{
				$stmt->bind_param("sssiiisss",$data['kopano_uid'],$data['kopano_mail'],$data['src_server'],$data['src_port'],$data['src_protocol'],$data['src_polling_type'],$data['src_user'],$data['src_password'],$data['polling_freq']);
				if(!$stmt->execute())
					return $stmt->error_list[0]['error'];
				$stmt->close();
				return true;
			}
			return false;
		}
		else 
		{
			if($stmt = $con->prepare("UPDATE ".$this->db_table." SET kopano_uid = ?,
					kopano_mail = ?,
					src_server = ?,
					src_port = ?,
					src_protocol = ?,
					src_polling_type = ?,
					src_user = ?,
					polling_freq = ?
					WHERE id = ?"))
			{
				$stmt->bind_param("sssiiissi",$data['kopano_uid'],$data['kopano_mail'],$data['src_server'],$data['src_port'],$data['src_protocol'],$data['src_polling_type'],$data['src_user'],$data['polling_freq'],$data['entryid']);
				if(!$stmt->execute())
					return $stmt->error_list[0]['error'];
				$stmt->close();
				//do the password update seperately, because we only need it if user submitted one
				if(!empty($data['src_password']))
				{
					if($stmt_pw = $con->prepare("UPDATE ".$this->db_table." SET src_password = ? WHERE id = ?"))
					{
						$stmt_pw->bind_param("si",$data['src_password'],$data['entryid']);
						if(!$stmt_pw->execute())
							return $stmt_pw->error_list[0]['error'];
						$stmt_pw->close();
					} else
						return false;
				}
				return true; 
			}
			return false;
		}	
		return false;
	}
	
	/**
	 * Deletes an Account from the database.
	 * @param String $id ID of the account in the database.
	 * @return boolean|String TRUE if the operation succeeded, otherwise the error message as string.
	 */
	public function deleteAccount($id) 
	{
		if(!isset($id) || empty($id))
			return false;
		
		$con = $this->getDatabaseConnection();
		
		if($stmt = $con->prepare("DELETE FROM ".$this->db_table." WHERE id = ?"))
		{
			$stmt->bind_param("s",$id);
			if(!$stmt->execute())
				return $stmt->error_list[0]['error'];
			if($stmt->affected_rows == 0)
				return "Something went wrong. Please contact your Administrator.";
			$stmt->close();
			return true;
		}
		return false;
	}
	
	/**
	 * Immediately polls the account by setting the last_poll date in the time farther back than the polling_frequency.
	 * @param String $id ID of the account in the database.
	 * @return boolean|String TRUE if operation succeded, otherwise the error message.
	 */
	public function pollNow($id)
	{
		if(!isset($id) || empty($id))
			return false;
		
		$con = $this->getDatabaseConnection();
		
		if($stmt = $con->prepare("UPDATE ".$this->db_table. " SET last_polling = SUBTIME(last_polling,(2*polling_freq)) WHERE id = ?"))
		{
			$stmt->bind_param("s",$id);
			if(!$stmt->execute())
				return $stmt->error_list[0]['error'];
			if($stmt->affected_rows == 0)
				return "Something went wrong. Please contact your Administrator.";
			$stmt->close();
			return true;
				
		}
		return false;
	}
	
	/**
	 * Updates the Fetchmail account table entry with $id with the given $status_code and $log_message.
	 * Also sets the last_polling timestamp to current_timestamp.
	 * @param integer $id ID of the Fetchmail account.
	 * @param integer $status_code Status code returned from the fetchmail process.
	 * @param String $log_message Log message from the fetchmail process.
	 * @return boolean|String Returns TRUE if sucessfull otherwise the Errror Message.
	 * @throws FetchmailDataException
	 */
	public function updateFetchmailAccountStatus($id,$status_code,$log_message)
	{
		if(!isset($id,$status_code,$log_message) || empty($id))
			throw new FetchmailDataException("Cannot update the status informations. Wrong parameters given.");
		
		$con = $this->getDatabaseConnection();
		
		if($stmt = $con->prepare("UPDATE ".$this->db_table. " SET last_status_code = ?, last_log_message = ?, last_polling = CURRENT_TIMESTAMP WHERE id = ?"))
		{
			$stmt->bind_param("isi",$status_code,$log_message,$id);
			if(!$stmt->execute())
				return $stmt->error_list[0]['error'];
			if($stmt->affected_rows == 0)
				return "Something went wrong. Please contact your Administrator.";
			$stmt->close();
			return true;
		}
		return false;
	}
	
	/**
	 * Connects to the Database, if already connected only returns the Database Connection.
	 * @return Returns the DB Connection
	 */
	private function getDatabaseConnection()
	{
		if(!is_null($this->db_connection) && $this->db_connection->ping())
			return $this->db_connection;
	
		$this->db_connection = new mysqli("p:" . $this->db_host, $this->db_username, $this->db_password, $this->db_database, $this->db_port);
		if(!$this->db_connection->connect_error)
			return $this->db_connection;
		else
			throw new FetchmailDriverException($this->db_connection->connect_error, 0, null, dgettext('plugin_fetchmail', dgettext('plugin_fetchmail', 'Cannot connect to Database. Please contact your Administrator.')));
			
	
	}
	
	/**
	 * Converts the $time variable into the correct format for the mysql database.
	 * @param String $time Can be in the format: "mm" or "hh:mm:ss".
	 * @return boolean|string FALSE if $time was an incorrect value otherwise "hh:mm:ss".
	 */
	private function getDateFromTimeString($time)
	{
		if(empty($time))
			return false;
		//mysql expects the time as "hh:mm:ss" so take apart the string and build that.
		//important: if the string is already in the right format, make sure to copy the informations
		$hour = 0;
		$minute = 0;
		$second = 0;
		$pol = explode(":",$time);
		//test if all the values are numeric
		foreach ($pol as $p) {
			if(!is_numeric($p))
				return false;
		}		
		//if the array size is only "1" then the user submitted only a single number as minutes.
		if(sizeof($pol) < 2)
			$minute = $pol[0];
		else {
			$hour = $pol[0];
			$minute = $pol[1];
			$second = $pol[2];
		}
		return date("H:i:s",mktime($hour,$minute,$second));
	}
	

}

?>