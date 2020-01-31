<?php
namespace Drg\CloudApi\Services;
if (!class_exists('Drg\CloudApi\core', false)) die( basename(__FILE__) . ': Die Datei "'.__FILE__.'" muss von Klasse "core" aus aufgerufen werden.' );

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Daniel Rueegg <daten@verarbeitung.ch>
 *
 *  All rights reserved
 *
 *  This script is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class CryptService
 */

class CryptService {

	/**
	* encrypt_method
	*
	* @var string
	*/
	protected $encrypt_method = 'AES-256-CBC';

	/**
	* hashed_key
	*
	* @var string
	*/
	protected $hashed_key = '';

	/**
	* hashed_iv
	*
	* @var string
	*/
	protected $hashed_iv = '';
	
	/**
	* __construct
	* Be carefully: changing the key passed as parameter of CryptService( $clear_key ) corruptes stored passwords. 
	*
	 * @param array $clear_key
	* @return void
	*/
	public function __construct(  $clear_key ) {
		if( !empty($clear_key) ){
			// hash
			$this->hashed_key = hash('sha256', $clear_key );
			// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
			$this->hashed_iv = substr(hash('sha256', $this->encrypt_method ), 0, 16);
		}
	}
    
	/**
	* encrypt
	*
	* @param string $string: string to encrypt
	* @return void
	*/
	public function encrypt( $string ){

		$uncodedoutput = openssl_encrypt($string, $this->encrypt_method, $this->hashed_key, 0, $this->hashed_iv);
		$output = base64_encode($uncodedoutput);

		return $output;
	}
    
	/**
	* decrypt
	*
	* @param string $string: string to decrypt
	* @return void
	*/
	public function decrypt( $string ){

		$output = openssl_decrypt(base64_decode($string), $this->encrypt_method, $this->hashed_key, 0, $this->hashed_iv);

		return $output;
	}

}

