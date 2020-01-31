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
 * bootup_settings
 *  reads settings from file, can get settings over constructor
 *  instanciates class fileHandlerService
 * 
 */
	$aScriptsToInclude = array( 
		'Classes/Core/core.php',
	);
	
	foreach($aScriptsToInclude as $script){
		if( !file_exists(SCR_DIR . $script) ) die( basename(__FILE__) . ' #42: datei ' . SCR_DIR . $script . ' nicht vorhanden!' );
		require_once( SCR_DIR . $script );
	}
 	

 /**
*/
class bootup_settings extends core {

	/**
	 * method readSettings
	 *  called by __construct()
	 *
	 * @param array $settings
	 * @return  void
	 */
	Public function readSettings( $settings ) {
		$this->startCryptService();
		$settings = $this->readDefaultSettings( $settings );
		$settings = $this->readGlobalDataSettings( $settings );
		$settings = $this->readLanguageSettings($settings);
		$this->settings = $settings ;
	}

	/**
	 * method getSettings
	 *
	 * @return  array
	 */
	Public function getSettings() {
		return $this->settings;
	}

	/**
	 * method readFilebasedDataSettings
	 *
	 * @param array $settings
	 * @param string $filename
	 * @param boolean $decode
	 * @return  void
	 */
	Public function readFilebasedDataSettings( $settings , $filename , $decode = FALSE) {
 	    if( !file_exists( $filename ) ) return $settings;
		$aSettingsFromFile = $this->fileHandlerService->readCompressedFile( $filename );
 	    //$aSettingsFromFile = unserialize( file_get_contents( $filename ) );
 	    if( $decode ){
			$aSettingsFromFile = $this->decodeArray( $aSettingsFromFile );
 	    }
		return $this->mergeSettings( $aSettingsFromFile , $settings );
	}

	/**
	 * getRequest
	 *
	 * @return  void
	 */
	Public function getRequest() {
		$this->settings['req'] = array();
		if( is_array( $_REQUEST ) ) {
			$postVars = array();
			foreach( $_REQUEST as $fld => $cnt ) {
					if( is_array($cnt) && $fld == 'settings' ){
						foreach( $cnt as $sbFld => $value ) {
							if( isset($this->settings['store_restriction'][$sbFld]) && $this->settings['store_restriction'][$sbFld]=='POST' ) continue;
							$postVars[$fld] = $cnt;
						}
					}else{
						$postVars[$fld] = $cnt;
					}
					
			}
			if( count( $postVars ) ) $this->setRequestedVarIfNotPatterned( $postVars );
		}
		// POST-var overwrites REQUEST-var
		if( is_array( $_POST) ) $this->setRequestedVarIfNotPatterned( $_POST );
		return $this->settings['req'];
	}

	/**
	 * updateSettings
	 *
	 * @param arrray $settings
	 * @param arrray $conditions
	 * @return  void
	 */
	Public function updateSettings( $settings , $conditions = array('save') ) {
 	    $this->settings = $settings;
 	    if( !isset( $this->settings['req']['settings'] ) ) return $this->settings;
 	    // store all if a OK button is clicked, otherwise store only session-vars
 	    $storeInFile = 0;
 	    if( isset( $this->settings['req']['ok'] ) ) {
			foreach( $conditions as $cond ){ $storeInFile += isset( $this->settings['req']['ok'][$cond]) ? 1 : 0; }
 	    }
 	    
 	    // read possible incoming settings
		$newSettings = array();
		$variableType = array( 'global'=>array() , 'session'=>array() , 'local'=>array() );
		
		// look up for hidden checkbox-fields (checkboxes dont send their names if they are not checked, therefore we send hidden fields with checkboxes)
		if(isset($this->settings['req']['chk_settings'])){foreach( $this->settings['req']['chk_settings'] as $iNam => $iVal ){
			if( isset( $this->settings['req']['settings'][$iNam] ) ){
				$newSettings[$iNam] =  $this->settings['req']['settings'][$iNam];
			}else{
				$newSettings[$iNam] = 0;
			}
		}}
		
		if(isset($this->settings['req']['settings'])){foreach( $this->settings['req']['settings'] as $iNam => $iVal ){
			$newSettings[$iNam] = $iVal ;
		}}
		
		foreach( array_keys( $this->settings['original'] ) as $iNam ){
			if( !isset($newSettings[$iNam])  ) $newSettings[$iNam] = $this->settings[$iNam];//continue; // no input to this setting
			// dont save if not changed AND still default value
			
			if( empty($storeInFile) ){ // in case of empty($storeInFile) only store session-vars
				 if( !isset($this->settings['globales'][$iNam]) ) continue;
				 if( $this->settings['globales'][$iNam] != 'session') continue;
			}
			
			if( isset($this->settings['globales'][$iNam]) ){
				$variableType[$this->settings['globales'][$iNam]][$iNam] = $newSettings[$iNam];
 				$this->settings[$iNam] = $newSettings[$iNam];
			}elseif( !isset($this->settings['static'][$iNam]) && isset($this->settings['format'][$iNam]) ) { 
				// if not set in format, dont overwrite the value from default-file 
				// (eg. version-number should be taken directly from default-file) 
				$variableType['local'][$iNam] = $newSettings[$iNam];
				$this->settings[$iNam] = $newSettings[$iNam];
			}
		}
		
 	    if( isset( $this->settings['req']['settings']['default_dir'] ) ) $this->settings['dataDir'] = DATA_DIR . trim( $this->settings['req']['settings']['default_dir'] , '/' ) . '/';
		// serialize and save settings as file in data-dir
		if( $storeInFile && count($variableType['local']) ){
// 			file_put_contents( $this->settings['dataDir']  . $this->settings['local_settings_filename'] , serialize( $this->encodeArray($variableType['local']) ) );
			$this->fileHandlerService->writeCompressedFile( $this->settings['dataDir']  . $this->settings['local_settings_filename'] ,  $this->encodeArray($variableType['local']) );
			$this->rapport( '' , count( $variableType['local'] ) . ' local records updated: '.(file_exists($this->settings['dataDir']  . $this->settings['local_settings_filename']) ? 'successful' : 'failed' ).'' , 'local-updateSettings' );
		}
		
		// serialize and save settings as file in data-root
		if( $storeInFile && count($variableType['global']) ){
// 			file_put_contents( DATA_DIR . $this->settings['local_settings_filename'] , serialize( $this->encodeArray( $variableType['global']) ) );
			$this->fileHandlerService->writeCompressedFile( DATA_DIR . $this->settings['local_settings_filename'] , $this->encodeArray( $variableType['global']) );
			$this->rapport( '' , count( $variableType['global'] ) . ' global records updated: '.(file_exists(DATA_DIR . $this->settings['local_settings_filename']) ? 'successful' : 'failed' ).'' , 'global-updateSettings' );
		}
		
		// save settings as session-variable
// 		if( isset($variableType['session']) && $this->hasToStoreSettings($variableType['session'],$settings) ) {
		if( isset($variableType['session']) ) {
			$isUpdated = $this->storeSessionSettings( $variableType['session'] , $_SESSION['username'] );
			$this->rapport( '' , count( $variableType['session'] ) . ' session records updated: ' . ($isUpdated ? 'successful' : 'failed' ) , 'session-updateSettings' );
		}

		if( isset($this->settings['dataDir']) ) $this->settings['dataDir'] = DATA_DIR . trim( str_replace( DATA_DIR , '' , $this->settings['dataDir'] ) , '/' ) . '/';
		return $this->settings;
	}

	/**
	 * method readSessionSettings
	 *
	 * @param array $settings
	 * @return  void
	 */
	Public function readSessionSettings( $settings ) {
 		$phpSelfBasename = '_' . basename($_SERVER['PHP_SELF'],'.php');
		$usersFilename = DATA_DIR . str_replace( '##USERNAME##' , $_SESSION['username'] . $phpSelfBasename , $this->settings['session_settings_filename'] );
		
		if( file_exists( $usersFilename ) ) {
			$aSettingsFromFile = $this->fileHandlerService->readCompressedFile( $usersFilename );
			$settings =  $this->mergeSettings( $aSettingsFromFile , $settings );
		}
		if( isset($_SESSION['settings']) ) $settings = $this->mergeSettings( $_SESSION['settings'] , $settings );
		return $settings;
	}

	/**
	 * method readDefaultSettings
	 *
	 * @param array $settings
	 * @return  void
	 */
	Private function readDefaultSettings( $settings ) {
		$settings['scrDir'] = SCR_DIR;
 	    
 	    // get default settings and save them with name 'original'
 	    $rawSettings = include( SCR_DIR  . 'Private/Config/settings.php' );
 	    // merge original settings with incomed values
 	    $this->settings = $rawSettings;
 	    $settings = $this->mergeSettings( $settings , $this->decodeArray( $rawSettings['original'] ) );

		$addSettingsFile = SCR_DIR  . $settings['default_additional_filedir'] . 'settings.php' ;
 	    if( file_exists( $addSettingsFile ) ){
			$settings = $this->mergeSettings( $this->decodeArray( $this->includeDetect( $addSettingsFile ) ) , $settings );
 	    }
 	    // merge other setting-options (than values) with  incomed values
		$settings = $this->mergeSettings( $settings , $rawSettings );
 	    // we used $this->settings in decodeArray, now we delete it. later it gets set again
 	    unset($this->settings);
		
		$origTableConfFile = SCR_DIR . $settings['table_conf_filepath'];
 	    
 	    // get default table configurations for csv-tables
 	    if( file_exists($origTableConfFile) ){
			$defaultSettings = $this->getSettingsFromDefaultFile( $origTableConfFile );
			if( $defaultSettings ) $settings = $this->mergeSettings( $settings , $defaultSettings );
 	    }
 	    // load additional defaults
 	    $addJsonTableConfFile = SCR_DIR . $settings['default_additional_filedir'] . basename($settings['table_conf_filepath'],'.php').'.json';
 	    $additionalTableConfFile = SCR_DIR . $settings['default_additional_filedir'] . basename($settings['table_conf_filepath']);
 	    if( file_exists($addJsonTableConfFile) ){ 
// 			$aResult = json_decode( file_get_contents( $addJsonTableConfFile ) , true );
			$settings['table_conf'] = $this->mergeSettings( $this->includeDetect($addJsonTableConfFile) , $settings['table_conf'] );
 	    }elseif( file_exists($additionalTableConfFile) ){
			$settings['table_conf'] = $this->mergeSettings( include( $additionalTableConfFile ) , $settings['table_conf'] );
 	    }
 	    
 		$settings['url'] = $this->getUrl();

 	    // get the path to data of actual instance set either in settings.php or index.php as $settings['dataDir']
 	    if( isset($settings['default_dir']) ) $settings['dataDir'] = $settings['default_dir'];
		if( isset($settings['dataDir']) ) $settings['dataDir'] = DATA_DIR . trim( str_replace( DATA_DIR , '' , $settings['dataDir'] ) , '/' ) . '/';
 	    // FIXME perhaps the value of 'dataDir' changes later in method getRequest() ... for cli_boot it could be neccesary (?)
 	    
  	    return $settings;
	    
	}

	/**
	 * method readGlobalDataSettings
	 *
	 * @param array $settings
	 * @return  void
	 */
	Private function readGlobalDataSettings( $settings ) {
		return $this->readFilebasedDataSettings( $settings , DATA_DIR . $settings['local_settings_filename'] );
	}

	/**
	 * method includeDetect
	 *
	 * @param string $filename
	 * @return  array
	 */
	Private function includeDetect( $filename ) {
			if( !file_exists($filename) ) return NULL;
			if( pathinfo($filename , PATHINFO_EXTENSION) == 'php' ) return include( $filename );
			if( pathinfo($filename , PATHINFO_EXTENSION) == 'json' ) {
					//$aResult = json_decode( file_get_contents( $filename ) , true );
					//if( !is_array($aResult) ) $aResult = unserialize( file_get_contents( $filename ) ); 
					$aResult = $this->fileHandlerService->readCompressedFile( $filename );
					if( isset($aResult[ pathinfo($filename , PATHINFO_FILENAME) ]) ) return $aResult[ pathinfo($filename , PATHINFO_FILENAME) ];
					return $aResult;
			}
			return NULL;
	}

	/**
	 * Set readLanguageSettings
	 *
	 * @param array $settings
	 * @return  void
	 */
	Private function readLanguageSettings( $settings ) {
 	    // get language-settings
 	    if( !isset($settings['locallang_filedir']) || empty($settings['locallang_filedir']) ) return $settings;
 	    
		$aDirs = $this->fileHandlerService->getDir( SCR_DIR  . $settings['locallang_filedir'] , 0 , 1 );// important dive = 0 , iteration = 1 !
		foreach( $aDirs['fil'] as $fileDirname => $filename ){
			if( pathinfo( $fileDirname , PATHINFO_EXTENSION ) != 'php' ) continue;
			
			$defaultSettings = $this->getSettingsFromDefaultFile( $fileDirname );
			$settings = $this->mergeSettings( $settings , $defaultSettings );
		}
		
		$reqLang = $this->getLocalLanguage( $settings );
		if(isset($settings['labels'][$reqLang])) $settings['language'] = $reqLang ;

 	    return $settings;
	}
	
	Private function getLocalLanguage( $settings ){
		
		if( !isset($settings['labels']) || !is_array($settings['labels']) ) return false;
		
		// if browser-defined language is set + provided
		if(isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) && !empty($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
				$fullVar = explode(",", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
				$firstPart = explode("-", $fullVar[0]);
				if(array_key_exists($firstPart[0], $settings['labels'])) {
						return $firstPart[0];
				}
		}
		
		// fallback A: Return language from settings
		if( array_key_exists($settings['language'],$settings['labels']) ) return $settings['language'];
		
		// fallback B: Return first existing language from language-files
		$labKeys = array_keys($settings['labels']);
		return $labKeys[0];
	}

	/**
	 * getSettingsFromDefaultFile
	 *
	 * @param string $filePathName
	 * @return  array
	 */
	Private function getSettingsFromDefaultFile( $filePathName ) {
 	    if( !file_exists($filePathName) ) return false;
 	    $fileSettings = $this->includeDetect( $filePathName );
 	    if( !count($fileSettings) ) return false;
 	    $prefix = pathinfo( $filePathName , PATHINFO_FILENAME );
 	    $fileParts = explode( '.' , $prefix );
 	    if( count($fileParts) == 2){
			// eg. language-file: labels.de.php
			$newSettings = array( $fileParts[0] => array( $fileParts[1] => $fileSettings ) );
 	    }else{
			// eg. table-definition: table_conf.php
			$newSettings = array( $prefix => $fileSettings );
 	    }
 	    return $newSettings;
	}

	/**
	 * method decodeArray
	 *
	 * @param array $settings
	 * @return  void
	 */
	Private function decodeArray( $settings ) {
		if( isset( $this->settings['format'] ) ) $format = $this->settings['format'] ;
		if( !isset( $format ) && isset( $settings['format'] ) ) $format = $settings['format'];
		if( !isset( $format ) ) return $settings;
		if( !is_array($settings) ) return $settings;
		foreach( $settings as $nam => $aCont ){
			if( !isset($format[$nam]) ) continue;
			if( $format[$nam] != 'pass2way' ) continue;
			$settings[$nam] = $this->decodeField( $format[$nam] , $aCont ); 
		}
		return $settings;
	}

	/**
	 * decodeField
	 *
	 * @param string $type
	 * @param string $encodedValue
	 * @return  string
	 */
	Private function decodeField( $type , $encodedValue ){
			if( empty($encodedValue) ) return $encodedValue;
			switch($type){
				case 'pass2way':
						$decoded = $this->cryptService->decrypt($encodedValue);
						return empty($decoded) ? $encodedValue : $decoded;
				break;
				case 'pass1way':
				default:
						return $encodedValue;
			}
			return $encodedValue;
	}

	/**
	 * method encodeArray
	 *
	 * @param array $settings
	 * @return  void
	 */
	Private function encodeArray( $settings ) {
		if( isset( $this->settings['format'] ) ) $format = $this->settings['format'] ;
		if( !isset( $format ) && isset( $settings['format'] ) ) $format = $settings['format'];
		if( !isset( $format ) ) return $settings;
		
		foreach( $settings as $nam => $aCont ){
			if( !isset($format[$nam]) ) continue;
			if( $format[$nam] != 'pass1way' && $format[$nam] != 'pass2way' ) continue;
			$settings[$nam] = $this->encodeField( $format[$nam] , $aCont );
		}
		return $settings;
	}

	/**
	 * encodeField
	 *
	 * @param string $type
	 * @param string $cleanValue
	 * @return  string
	 */
	Private function encodeField( $type , $cleanValue  ){
			switch($type){
				case 'pass1way':
					$md5Password = md5( $cleanValue );
					return password_hash( $md5Password , PASSWORD_BCRYPT );
				break;
				case 'pass2way':
					return $this->cryptService->encrypt($cleanValue);
				break;
				default:
					return $cleanValue;
			}
			return $cleanValue;
	}

	/**
	 * mergeSettings
	 *
	 * @param array $incomeSettings
	 * @param array $overridableSettings
	 * @return  void
	 */
	Private function mergeSettings( $incomeSettings , $overridableSettings ) {
 	    if( !is_array($overridableSettings) ) return $incomeSettings;
 	    if( !is_array($incomeSettings) ) return $overridableSettings;
 	    
 	    // find field-settings
 	    if( isset($overridableSettings['format']) && is_array($overridableSettings['format']) ){
			$fieldDefinitions = $overridableSettings['format'];
 	    }elseif( isset($incomeSettings['format']) && is_array($incomeSettings['format']) ){
			$fieldDefinitions = $incomeSettings['format'];
 	    }
 	    
		foreach($overridableSettings as $name => $value ) {
			if( is_array($value) ){ // sub fields
				foreach($value as $nam => $val ){
					if(!isset($incomeSettings[$name][$nam])) $incomeSettings[$name][$nam] = $val ;
				}
			}else{ // normal fields
				if(!isset($incomeSettings[$name])) $incomeSettings[$name] = $value ;
			}
		}
		
 	    return $incomeSettings;
	}

	/**
	 * setRequestedVarIfNotPatterned
	 *
	 * @param array $request
	 * @param string $pattern optional, by default '##' to prevent empty values like var[timeout] = ##timeout##
	 * @return  void
	 */
	Private function setRequestedVarIfNotPatterned( $request , $pattern = '##' ) {
			foreach( $request as $fld => $cnt ) {
					if( !is_array($cnt) && $cnt == $pattern . $fld . $pattern ) continue;
					$this->settings['req'][$fld] = $this->protectIncomingVariables( $cnt );
			}
	}

	/**
	 * protectIncomingVariables
	 *
	 * @param string $value
	 * @return  void
	 */
	Private function protectIncomingVariables( $value ) {
			if( is_array( $value ) ){
				$outArr = array();
				foreach( $value as $nam => $arr ) $outArr[$nam] = $this->protectIncomingVariables( $arr );
				return $outArr;
			}else{
				$stripedValue = stripslashes( $value );
				$htmlValues = htmlspecialchars( $stripedValue );
				return $htmlValues;
			}
	}

	/**
	 * method hasToStoreSettings
	 *
	 * @param array $storeSettings
	 * @param array $oldSettings
	 * @return  void
	 */
	Private function hasToStoreSettings( $storeSettings , $oldSettings ) { 
		if( !count($storeSettings) ) return false;
		
		$counter = 0;
		foreach( $storeSettings as $key => $val ){
			if( 
					!isset($oldSettings[$key]) || $val != $oldSettings[$key] ||
					$key == 'default_dir'
			) {
				++$counter;
			}
		}
		
		return $counter;
	}

	/**
	 * method storeSessionSettings
	 *
	 * @param array $sessionSettings
	 * @param string $username
	 * @return  void
	 */
	Private function storeSessionSettings( $sessionSettings , $username ) {
		if( !count($sessionSettings) ) return false;
		
 		$phpSelfBasename = '_' . basename($_SERVER['PHP_SELF'],'.php');
		$usersFilename = DATA_DIR . str_replace( '##USERNAME##' , $username . $phpSelfBasename , $this->settings['session_settings_filename'] );
		file_put_contents( $usersFilename , serialize( $sessionSettings ) );
		$this->fileHandlerService->writeCompressedFile( $usersFilename , $sessionSettings );

		// returns false on error happend if file did not exist before
		return file_exists( $usersFilename );
	}

}

?>
