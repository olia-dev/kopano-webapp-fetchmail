<?php


require_once(BASE_PATH . 'server/includes/exceptions/class.ZarafaException.php');

/**
 * Defines an Exception that gets thrown when anything goes wrong in the communication with the driver.
 * The displayMessage parameter should then be displayed for the client. 
 */
class FetchmailDriverException extends ZarafaException {
	
}


?>