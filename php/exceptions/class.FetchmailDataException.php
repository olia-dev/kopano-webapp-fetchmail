<?php


require_once(BASE_PATH . 'server/includes/exceptions/class.ZarafaException.php');

/**
 * Defines an Exception that gets thrown when any of the data send by the client is empty or incorrect.
 * The displayMessage parameter should then be displayed for the client. 
 */
class FetchmailDataException extends ZarafaException {
	
}


?>