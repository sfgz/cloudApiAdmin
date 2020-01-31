<?php
namespace Drg\CloudApi\Services;
if( !defined('SCR_DIR') ) die( basename(__FILE__).' #3: die Konstante SCR_DIR ist nicht definiert, das Skript wurde nicht korrekt gestartet.' );
/***************************************************************
 *
 *  nextcloud API description see
 *  https://docs.nextcloud.com/server/15/developer_manual/client_apis/index.html
 *  and
 *  https://docs.nextcloud.com/server/15/developer_manual/client_apis/OCS/index.html
 *  
 *  ********* 
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

	$aScriptsToInclude = array( 
		'Classes/Services/FileHandlerService.php',
		'Classes/Services/XmlService.php' 
	);
	
	foreach($aScriptsToInclude as $script){
		if( !file_exists(SCR_DIR . $script) ) die( basename(__FILE__) . ' #34: datei ' . SCR_DIR . $script . ' nicht vorhanden!' );
		require_once( SCR_DIR . $script );
	}

/**
 * Class ConnectorService
 */

class ConnectorService {
	
	/**
	 * apiCalls
	 *
	 * @var int
	 */
	public $apiCalls = 0;
	
	/**
	 * cloudProt
	 *
	 * @var string
	 */
	public $cloudProt = 'https://';
	
	/**
	 * cloudUrl
	 *
	 * @var string
	 */
	public $cloudUrl = 'cloud.mydomain.com';
	
	/**
	 * cloudApiPaths
	 *
	 * @var array
	 */
	Private $cloudApiPaths = array( 
			'cloud'   => '/ocs/v1.php/cloud' , 
			'files'   => 'remote.php/dav/files/##USERNAME##' , 
			'sharing' => 'ocs/v1.php/apps/files_sharing/api/v1' 
	);
	
	/**
	 * debug
	 *
	 * @var array
	 */
	Public $debug = array();

	/**
	 * cloudInfoFolder
	 *
	 * @var string
	 */
	public $cloudInfoFolder = 'zBcloudApiAdmin';
	
	/**
	 * cloudUsername
	 *
	 * @var string
	 */
	public $cloudUsername = 'zBadmin';

	/**
	 * cloudPassword
	 *
	 * @var string
	 */
	public $cloudPassword = 'zB:P455w0rd';

	/**
	 * xmClass
	 * 
	 * @var \Drg\CloudApi\Services\XmlService
	 */
	public $xmClass = NULL;

	/**
	 * __construct
	 *
	 * @return  void
	 */
	public function __construct( $settings ) {
			$this->settings = $settings;
			$this->xmClass = new \Drg\CloudApi\Services\XmlService();
			$this->fileHandlerService = new \Drg\CloudApi\Services\FileHandlerService();
	}

	/**
	* initiate
	*
	* @return void
	*/
	public function initiate() {
     }
    
    /**
     * prepareConnection
     * tearUp
     *
     * @return array
     */
    Public function prepareConnection(){
			if( !isset($this->settings['connection_pass']) || empty($this->settings['connection_pass']) ) {
					echo 'settings not complete, loading cli_boot...';
					if (!class_exists('\Drg\CloudApi\cli_boot', false)){
						if( empty(SCR_DIR) ) die('ConnectorService #416 Hauptpfad nicht definiert!');
						if( !file_exists(SCR_DIR . 'Classes/Core/cli_boot.php') ) die('ConnectorService #122 Datei '.SCR_DIR . 'Core/boot.php'.' nicht gefunden!');
						include_once( SCR_DIR . 'Classes/Core/cli_boot.php' );
					}
					$objCloudApiAdmin = new \Drg\CloudApi\cli_boot();
					$this->settings = $objCloudApiAdmin->settings;
			}

			if(  !isset($this->settings['connection_pass']) || empty($this->settings['connection_pass']) ) die( 'Verbindungsdaten zu Cloud nicht gefunden.' );
			$this->cloudUsername = $this->settings['connection_user'];
			$this->cloudPassword = $this->settings['connection_pass'];
			$this->cloudProt = str_replace( ';//' , '' , $this->settings['connection_prot'] ) . '://';
			$this->cloudUrl = $this->settings['connection_url'];
			$this->cloudInfoFolder = $this->settings['connection_folder'];
    }
    
    /**
     * readCloudDataFromQuery
     * calls API to get data an write it to a file
     * or reads the file if call is already done
     * transforms xml-data to array
     *
     * @param string $query URI-part behind URL
     * @return array
     */
    Public function readCloudDataFromQuery( $query ){
		$query = trim( $query , '/' ) ;
		// if there is already a file with same filename like the query, show cached data
		$repVar = array( '%20'=>'-' , '/'=>'_' );
		$filename = rtrim($this->settings['dataDir'] . $this->settings['processing'],'/') . '/' .  str_replace( array_keys($repVar) , $repVar , $query ) . '.xml';
		if( !is_dir( dirname($filename) ) ) return FALSE;
		
		if( file_exists( $filename ) ){
			$xmlData = file_get_contents( $filename );
		}
		if( !isset($xmlData) ){
			$xmlData = $this->execApi( 'GET' , $query , '' , 'cloud' );
			$aError = $this->ocsData2array($xmlData , 'META' );
			if( $aError['STATUS'] == 'failure' ) {
					$this->debug['answerFromApiCall-failure']= 'APIs answer: ' . $aError['MESSAGE'] . ' in ' . $query;
					return FALSE;
			}
			if( !empty($xmlData) && !isset( $this->debug['execApiError:' . $query ] ) ) {
				if( file_exists( $filename ) && !is_writable( $filename ) ){
						$this->debug['not_writable-'.basename($filename)] =  basename($filename) ;
				}else{
						file_put_contents( $filename , $xmlData );
				}
			}else{
				$this->debug['apiCallReturnedZero'] = $query;
			}
		}
		$aDataFromApiCall = $this->ocsData2array($xmlData , 'DATA' );
		if( is_array($aDataFromApiCall) && count($aDataFromApiCall) ) return $aDataFromApiCall;

		$aError = $this->ocsData2array($xmlData , 'META' );
		if( $aError['STATUS'] == 'failure' ) {
				$this->debug['answerFromApiCall-failure']= 'APIs answer: ' . $aError['MESSAGE'] . ' in ' . $query;
		}
		return FALSE;
	}
    
    /**
     * execApi
     * executes the API calls
     *
     * @param string $method CUSTOMREQUEST [ POST | PUT | DELETE ]
     * @param string $query uri-part
     * @param string $data POSTFIELDS optional
     * @param string $apiPathId optional default = cloud [ cloud | files | sharing ]
     * @return array
     */
    Private function execApi( $method , $query , $data = '' , $apiPathId = 'cloud' ){
		 $queryWithoutWhitespace = str_replace( ' ' , '%20' ,$query);
		 $aHeaderdata = array( 
			'OCS-APIRequest: true' , 
			'Content-Type: application/x-www-form-urlencoded'
		);

//		$url = $this->cloudProt . $this->cloudUsername . ':' . $this->cloudPassword . '@'; //old
		$url = $this->cloudProt ; 
		$url.= trim( $this->cloudUrl , '/' ) . '/'; 
		$url.= trim( str_replace( '##USERNAME##' , $this->cloudUsername , $this->cloudApiPaths[ $apiPathId ]) , '/' ) . '/'; 
		$url.= trim( $queryWithoutWhitespace , '/' );
		
		$curl = curl_init(  $url  );
		curl_setopt($curl, CURLOPT_HTTPHEADER, $aHeaderdata );
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method );
		curl_setopt($curl, CURLOPT_USERPWD, $this->cloudUsername.':'.$this->cloudPassword );//new
		if( !empty($data) ) curl_setopt($curl, CURLOPT_POSTFIELDS, $data );
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$error = curl_error($curl);
		$result = curl_exec($curl);
		curl_close($curl);
		$this->apiCalls += 1;
		
		// Api call error handling. Hint: it is not an error if the result is null
		if( $error ) $this->debug[ 'execApiError:' . $query ] = $error;
		
		return $result;
    }
    
    /**
     * ocsData2array
     * returns a multidimensional array 
     * amount of dimensions depending on input
     * 
     * if there is only one element, it contains a string, 
     * otherwise element contains array of strings.
     *
     * @param string $xmlData  as string
     * @param string $varToReturn META or DATA
     * @return array
     */
    Private function ocsData2array( $xmlData , $varToReturn = 'META' ){
	    $att = $this->xmClass->XMLtoArray( $xmlData );
	    if( !isset($att['OCS']) ) return;
	    if( !isset($att['OCS'][$varToReturn]) ) return;
	    return is_array($att['OCS'][$varToReturn]) ? $att['OCS'][$varToReturn] : array();
    }
    
    /**
     * apiCreateUser
     * executes the API call
     *
     * @param string $user 
     * @param string $password
     * @return array
     */
    Public function apiCreateUser( $user , $password ){
		$createResult = $this->execApi( 'POST' , '/users' , 'userid='  . $user .  '&password=' . $password );
		$result = $this->ocsData2array( $createResult , 'META' );
		return $result;
    }
    
    /**
     * apiUpdateUserField
     * executes the API call
     *
     * @param string $user 
     * @param string $field e.g. email or quota
     * @param string $value new value to set
     * @return array
     */
    Public function apiUpdateUserField( $user , $field , $value ){
		$createResult = $this->execApi( 'PUT' , '/users/' . $user , 'key='.$field.'&value=' . $value );
		$result = $this->ocsData2array( $createResult , 'META' );
		return $result;
    }
   
    /**
     * apiCreateGroup
     * executes the API call
     *
     * @param string $group
     * @return array
     */
    Public function apiCreateGroup( $group ){
		$createResult = $this->execApi( 'POST' , '/groups' , 'groupid='.$group );
		$result = $this->ocsData2array( $createResult , 'META' );
		return $result;
    }
    
    /**
     * apiAppendUserToGroup
     * executes the API call
     *
     * @param string $user 
     * @param string $$group
     * @return array
     */
    Public function apiAppendUserToGroup( $user , $group ){
		$createResult = $this->execApi( 'POST' , '/users/' . $user . '/groups' , 'groupid='  . $group );
		$result = $this->ocsData2array( $createResult , 'META' );
		return $result;
    }
    
    /**
     * apiRemoveUserFromGroup
     * executes the API call
     *
     * @param string $user 
     * @param string $group
     * @return array
     */
    Public function apiRemoveUserFromGroup( $user , $group ){
		$createResult = $this->execApi( 'DELETE' , '/users/' . $user . '/groups' , 'groupid='  . $group );
		$result = $this->ocsData2array( $createResult , 'META' );
		return $result;
    }
    
    /**
     * apiDeleteUser
     * executes the API call
     *
     * @param string $user 
     * @return array
     */
    Public function apiDeleteUser( $user ){
		$createResult = $this->execApi( 'DELETE' , '/users/' . $user );
		$result = $this->ocsData2array( $createResult , 'META' );
		return $result;
    }
    
    /**
     * apiDeleteUser
     * executes the API call
     *
     * @param string $group 
     * @return array
     */
    Public function apiDeleteGroup( $group ){
		$createResult = $this->execApi( 'DELETE' , 'groups/' .  $group  );
		$result = $this->ocsData2array( $createResult , 'META' );
		return $result;
    }
    
    /**
     * apiCreateFolder
     *
     * @param string $cloudDocumentPath e.g tmp
     * @return array
     */
    Public function apiCreateFolder( $cloudDocumentPath = '' ){
			if( empty($cloudDocumentPath) ) $cloudDocumentPath = $this->cloudInfoFolder;
			$xmlData= $this->execApi( 'PROPFIND' , $cloudDocumentPath , '' , 'files' );
			$att = $this->xmClass->XMLtoArray( $xmlData );
			if( isset($att['D:MULTISTATUS']) && is_array($att['D:MULTISTATUS']['D:RESPONSE'])) return FALSE;
			// create distant folder if it does not exist.
			$result = $this->execApi( 'MKCOL' , $cloudDocumentPath , '' , 'files' );
			return $result;
    }
    /**
     * apiClearFolder
     *
     * @param array $whitelist prevent array-names from deletion eg. whitelist[Class12A] = 1 or whitelist[Class12A.pdf] = 1
     * @return array
     */
    Public function apiClearFolder( $whitelist = array() ){
			// find all files in DIR and delete them
			$xmlData= $this->execApi( 'PROPFIND' , $this->cloudInfoFolder , '' , 'files' );
			$att = $this->xmClass->XMLtoArray( $xmlData );
			if(is_array($att['D:MULTISTATUS']['D:RESPONSE'])){
				foreach($att['D:MULTISTATUS']['D:RESPONSE'] as $attributes){
					if( !isset($attributes['D:HREF']) ) continue;
					if( trim( $attributes['D:HREF'] , '/' ) == trim( $this->cloudApiPaths['files'].'/'.$this->cloudInfoFolder , '/' ) ) continue;
					$filename = rawurldecode($attributes['D:HREF']);
					if( isset( $whitelist[ basename($filename) ]) ) continue;
					if( isset( $whitelist[ basename($filename , '.pdf') ]) ) continue;
					$this->execApi( 'DELETE' , $this->cloudInfoFolder . '/' . basename($attributes['D:HREF']) , '' , 'files' );
				}
			}
			return true;
    }
    
    /**
     * apiPutFileToCloud
     *
     * @param string $filename 
     * @param string $filecontent 
     * @return array
     */
    Public function apiPutFileToCloud( $filename , $filecontent ){
			
			// upload file
			$result = $this->execApi( 'PUT' , $this->cloudInfoFolder . '/' . $filename , $filecontent , 'files' );
			
			return $result;
    }
    
    /**
     * apiShareFile
     *
     * @param string $cloudFile e.g fileToShare.txt
     * @param string $shareWith groupname or username must be encoded with rawurlencode eg. Cls17%20A instead of Cls17 A
     * @param string $shareType [ 0=user | 1=group | 3=publicLink ] optional, default is 1 (group)
     * @param string $permissions [ 1=read | 2=update | 4=create | 8=delete | 16=share | 31=all ] optional, default is 1 (read)
     * @return array
     */
    Public function apiShareFile( $cloudFile , $shareWith , $shareType = 1 , $permissions = 1 ){
			$result = $this->execApi( 'POST' , '/shares' , 'path='.$this->cloudInfoFolder . '/' . $cloudFile . '&shareType='.$shareType . '&shareWith='.$shareWith . '&permissions=' . $permissions , 'sharing' );
			return $result;
    }
    
}
