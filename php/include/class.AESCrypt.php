<?php


/**
 * Used to encrypt and decrypt the password stored in the database.
 */
class AESCrypt {
	
	private $aes_algo;
	private $hash_algo;
	
	private $required_iv_length;
	
	/**
	 * Constructs a AESCrypt using the standard values 'aes-256-ctr' and 'sha256' hash. 
	 * @param string $aes_algo AES Algorithm.
	 * @param string $hash_algo Hash Algorithm for the key.
	 */
	function __construct($aes_algo = 'aes-256-ctr', $hash_algo = 'sha256')
	{
		$this->aes_algo = $aes_algo;
		$this->hash_algo = $hash_algo;		
		
		//first test if the machine supports the chosen algo(s).
		if(!in_array($this->aes_algo,openssl_get_cipher_methods(true)))
			throw new Exception("The encryption algorithm: '".$this->aes_algo."' is not supported on this machine.");
		if(!in_array($this->hash_algo,openssl_get_md_methods(true)))
			throw new Exception("The hashing algorithm: '".$this->hash_algo."' is not supported on this machine.");
		
		//set the required length for our IV
		$this->required_iv_length = openssl_cipher_iv_length($this->aes_algo);
	}
	
	/**
	 * Encrypts the given input with the chosen encryption algorithm.
	 * @param string $input The input to encrypt.
	 * @param string $key The key with which to encrypt the input.
	 */
	public function encrypt($input, $key)
	{
		//generate our IV
		$iv = openssl_random_pseudo_bytes($this->required_iv_length);
		
		//hash our key
		$key = openssl_digest($key, $this->hash_algo, true);

		//encrypt the input
		$output = openssl_encrypt($input, $this->aes_algo, $key, OPENSSL_RAW_DATA, $iv);
		
		//test if everything worked
		if($output === FALSE)
			throw new Exception("Encryption failed. Error Message: ".openssl_error_string());
		
		//encode the output and IV in base64 for easier storage
		$output = base64_encode($output);
		$iv = base64_encode($iv);
		
		//append our IV to the output seperated by an ":"
		$output .= ":".$iv;
		
		return $output;		
	}
	
	/**
	 * Decrypts the given input with the chosen encryption algorithm.
	 * @param string $input The input to decrypt.
	 * @param string $key The key with which to decrypt the input.
	 */
	public function decrypt($input, $key)
	{
		//input should the the actual encrypted and the IV seperated by an ":"
		$ar = explode(":", $input);
		
		//test if the array size is exactly 2 otherwise the input was something false
		if(count($ar) != 2)
			throw new Exception("Decryption failed. The Password input was malformed."); 
		
		//the password and IV where base64 encoded
		$output = base64_decode($ar[0]);
		$iv = base64_decode($ar[1]);
		
		//hash the key
		$key = openssl_digest($key, $this->hash_algo, true);
		
		//now decrypt
		$output = openssl_decrypt($output, $this->aes_algo, $key, OPENSSL_RAW_DATA, $iv);
		
		//test if everything succeded
		if($output === FALSE)
			throw new Exception("Decryption failed. Error Message: ".openssl_error_string());
		
		return $output;
	}
	
}



?>
