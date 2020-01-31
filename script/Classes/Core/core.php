<?php
namespace Drg\CloudApi;
if( !defined('SCR_DIR') ) die( basename(__FILE__) . ' #3: die Konstante SCR_DIR ist nicht definiert, das Skript wurde nicht korrekt gestartet.' );

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2017 Daniel Rueegg <colormixture@verarbeitung.ch>
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
 * core
 *  reads settings from file, can get settings over constructor
 *  instanciates class fileHandlerService
 * 
 */
	$aScriptsToInclude = array( 
		'Classes/Services/FileHandlerService.php',
		'Classes/Services/CryptService.php' 
	);
	
	foreach($aScriptsToInclude as $script){
		if( !file_exists(SCR_DIR . $script) ) die( basename(__FILE__) . ' #41: datei ' . SCR_DIR . $script . ' nicht vorhanden!' );
		require_once( SCR_DIR . $script );
	}
 	

 /**
*/
class core {

	/**
	 * Property settings
	 *
	 * @var array
	 */
	Public $settings = NULL;

	/**
	 * Property debug
	 *
	 * @var array
	 */
	Public $debug = NULL;

	/**
	 * fileHandlerService
	 *
	 * @var \Drg\CloudApi\Services\FileHandlerService
     * @inject
	 */
	Public $fileHandlerService = NULL;

	/**
	 * Property cryptService
	 *
	 * @var \Drg\CloudApi\Services\CryptService
	 */
	Protected $cryptService = NULL;

	/**
	 * __construct
	 *
	 * @param array $settings optional
	 * @return  void
	 */
	public function __construct( $settings = array() ) {
		$this->fileHandlerService = new \Drg\CloudApi\Services\FileHandlerService();
		$this->readSettings( $settings );
	}

	/**
	 * startCryptService
	 * 
	 * not startet automatically by core but by extensions of core/...
	 * Be carefully: changing the key passed as parameter of CryptService( $clear_key ) corruptes stored passwords. 
	 * Reinstall is affored, by following steps:
	 * 1. change settings.php: Set the original-value 'cryptedPasswordInDefaultTables' to 0
	 * 2. reinstall. Therefore you can delete the folder ./data, it will be recreated by the install-script 
	 *    wich gets started if data-folder does not exist. The server needs writing-rights for the parent folder.
	 * 3. in settings.php: Reset the original-value 'cryptedPasswordInDefaultTables' to 1
	 *
	 * @return  void
	 */
	Protected function startCryptService() {
		$this->cryptService = new \Drg\CloudApi\Services\CryptService( 'cloudApiAdmin-PasswordToCryptService' );
	}

	/**
	 * readSettings
	 * called from __construct()
	 *
	 * @param array $settings
	 * @return  void
	 */
	public function readSettings( $settings ) {
		if( count($settings) ) $this->settings = $settings;
	}

	/**
	 * getUrl
	 *
	 * @param boolean $cleanUrl if true then the method returns always a folder, default is false (file or folder)
	 * @return  string The last requested URL (URL to the requested folder or php-site eg. index.php)
	 */
	public function getUrl( $cleanUrl = false ){
		// if called from commandline, there is no $_SERVER-array avaiable
		if( !isset($_SERVER['SERVER_NAME']) ) return false;
		
		$aUriQuery = explode( '?' , $_SERVER['REQUEST_URI'] );
		
		//$this->settings['query'] = explode( '&' , $aUriQuery[1] );
		
		if( $cleanUrl && strpos( $aUriQuery[0] , '.php' ) ) $aUriQuery[0] = dirname($aUriQuery[0]);
		
		$URL = sprintf(
			"%s://%s%s",
			isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
			$_SERVER['SERVER_NAME'],
			$aUriQuery[0]
		);
		return $URL;
	}

	/**
	 * getFirstArrayKey
	 * returns the first key from incoming array if value is not empty
	 * if the array contains only empty values then it returns the last key
	 *
	 * @param array $multiDimArray 
	 * @param boolean $getLast default is false. On true the method returns the last key instead of the first 
	 * @return string
	 */
	public function getFirstArrayKey( $multiDimArray , $getLast = false) {
		if( !is_array($multiDimArray) ) return $multiDimArray;
		
		$arrNames = array_keys( $multiDimArray );
		$value = $getLast ? array_pop( $arrNames ) : array_shift( $arrNames );
		if( $value || !count($arrNames) ) return $value;
		
		$multiDimArray = array_flip($arrNames);
		return $this->getFirstArrayKey( $multiDimArray , $getLast);
	}

	/**
	 * rapport
	 * inserts a record in the the debug-array and returns the desired return-value
	 *
	 * @param string $returnValue usually boolean, but integer fits aswell
	 * @param string $logMessage
	 * @param string $logTitle optional
	 * @return  string
	 */
	Protected function rapport( $returnValue , $logMessage , $logTitle = '' ){
		if( empty($logTitle) ) $logTitle = count($this->debug) ;
		$this->debug[$logTitle] = $logMessage ;
		return $returnValue;
	}

	/**
	 * setErrorOutputState
	 * sets the error-output level
	 * depending on users debug-variable, stored in the file session_user_startfile.json
	 *
	 * @param string $debugState
	 * @return void
	 */
	public function setErrorOutputState( $debugState = '' ) {
			if( $debugState === '' ) return false;
			
			if( $debugState == 0 ){
				error_reporting( 0 );
				ini_set("display_errors", 0);
			}elseif( $debugState == 1){
				error_reporting( E_ERROR );
				ini_set("display_errors", 0);
			}elseif( $debugState > 1){
				error_reporting( E_ALL );
				ini_set("display_errors", 1);
			}
			return true;
	}

}

?>
