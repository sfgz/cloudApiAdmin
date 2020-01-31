<?php
namespace Drg\CloudApi\Services;
if (!class_exists('Drg\CloudApi\boot', false)) die( basename(__FILE__) . ': Die Datei "'.__FILE__.'" muss von Klasse "boot" aus aufgerufen werden.' );

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

class AuthService {

	/**
	 * Property username
	 *
	 * @var string
	 */
	public $username = '';

	/**
	 * Property formLifeSeconds
	 *
	 * @var int
	 */
	Private $formLifeSeconds = 10;

	/**
	 * Property cookieLifeHowers
	 *
	 * @var int
	 */
	Private $cookieLifeHowers = 6;

	/**
	 * Property userRecordset
	 *
	 * @var array
	 */
	Private $userRecordset = FALSE;

	/**
	 * Property defaultSettings
	 *
	 * @var array
	 */
	Private $defaultSettings = array(
		'login_life_period_h' => '168',
		'loginform_lifetime_s' => '60',
		'individual_login' => 1,
		'scrDir' => 'setInConstructorMethod',
		'default_data_filedir' => 'Private/Config/',
		'language' => 'de',
		'labels' => array( 'de'=>array('login_timeout'=>'login timeout abgelaufen') , 'en'=>array('login_timeout'=>'login timeout overdued') ),
		'req' => array( array( 'user'=>'' , 'pwd'=>'' , 'permalogin'=>'' ) )
	);

	/**
	 * Property isLoggedIn
	 *
	 * @var boolean
	 */
	Public $isLoggedIn = false;

	/**
	 * Property cookiename
	 *
	 * @var string
	 */
	Private $cookiename = 'loginApiAdminIndex';

	/**
	 * Property cookieurl
	 *
	 * @var string
	 */
	Private $cookieurl = '';

	/**
	 * Property cookiepath
	 *
	 * @var string
	 */
	Private $cookiepath = '';

	/**
	 * __construct
	 *
	 * @param array $settings optional. If not set then use defaultSettings instead
	 * @return  void
	 */
	Public function __construct( $settings = array() ) {
		
		if( session_status() <= 1 ) session_start();
		
		if( !count($settings) ) {
			$this->settings = $this->defaultSettings;
			$this->settings['scrDir'] = dirname(dirname(dirname(__FILE__))) . '/';
		}else{
			$this->settings = $settings;
		}
		
		$this->cookieurl = !isset($_SERVER['SERVER_NAME']) ? '' : $_SERVER['SERVER_NAME'];
		if( isset($this->settings['login_life_period_h']) ) $this->cookieLifeHowers = $this->settings['login_life_period_h'];
		if( isset($this->settings['loginform_lifetime_s']) ) $this->formLifeSeconds = $this->settings['loginform_lifetime_s'];
		if( $this->settings['individual_login'] ) {
				// for individual login on each index-page append document-name to cookiename (DISABLED)
 				$phpSelfBasename = basename($_SERVER['PHP_SELF'],'.php');
 				$phpSelf = empty($phpSelfBasename) ? rtrim($_SERVER['PHP_SELF'],'/').'/index.php' : $_SERVER['PHP_SELF'];
 				$this->cookiename = 'loginApiAdmin' . basename($phpSelf,'.php');
				$this->cookiepath = '/';
		}else{
 				$this->cookiename = 'loginApiAdminIndex';
				$this->cookiepath = '/';
		}
	}
	
	/**
	 * ValidateLogin
	 *
	 * @param array $userRecords optional
	 * @return  boolean
	 */
	Public function ValidateLogin( $userRecords = array() ){
		$secret_word = !isset($_SERVER['SERVER_ADDR']) ? md5('cloudApiAdmin-SecretWordInAuthService') : $_SERVER['SERVER_ADDR'];

		$this->settings['req']['ok']['default'] = 1;
		$this->model = new \Drg\CloudApi\Models\UsersModel( $this->settings );
	    $this->username = '';
	  
		if( isset($this->settings['req']['logout']) ) return $this->logout();
		
		if (isset($_COOKIE[$this->cookiename])) {// PROBERTLY ALREADY LOGGED IN otherwise SET username to false
			$ca = explode(';',$_COOKIE[$this->cookiename]);
			if (md5($ca[0].$secret_word) == $ca[1] && !empty($ca[0]) ) {
				$this->username =  $ca[0];
				$userRecords = $this->model->getRecordsets();
				if( !count($userRecords) || !isset($userRecords[$this->username]) ) $userRecords = $this->getPredefinedUsers();
			}else{
				echo 'cookie does not match to username - inexistent case in AuthService->ValidateLogin() on line #136 ?'; 
				return $this->logout();
			}
		}else{
			if( !isset($this->settings['req']['user']) || empty($this->settings['req']['user']) ) return $this->logout();
			if( !isset($this->settings['req']['pwd']) || empty($this->settings['req']['pwd']) ) return $this->logout();
			$reqUser =  $this->settings['req']['user'] ;
			
			// try to find user in actual userfile or in predefined users
			if( !count( $userRecords) || !isset($userRecords[$reqUser])) $userRecords = $this->getPredefinedUsers();
			if( !isset($userRecords[$reqUser]['group']) && !empty($reqUser) ) return $this->logout();
			
			// compare passwords
 			$result = $this->validatePassword( $this->settings['req']['pwd'] , $userRecords[$reqUser]['pass'] );
			if ($result==1) {// LOG IN RIGHT NOW, SET 'COUNTDOWN'
				if( isset($this->settings['req']['permalogin']) && !empty($this->settings['req']['permalogin']) ){
					$timeout = time()+($this->cookieLifeHowers*3600); 
				}else{ 
					$timeout = 0; 
				}
				setcookie($this->cookiename , $reqUser.';'.md5($reqUser.$secret_word) , $timeout , $this->cookiepath , $this->cookieurl );
				$this->username = $reqUser;
			}
		}

		// make shure salt changed for next login
		$this->createSaltAndPepper(); 
		
		$this->isLoggedIn = empty($this->username) || !isset($userRecords[$this->username]) ? false : true ;
		$this->userRecordset = $this->isLoggedIn ? $userRecords[$this->username] : Null;
		
		if( $this->isLoggedIn ) $_SESSION['username'] = $this->username;
		return $this->isLoggedIn;

	}

	/**
	 * validatePassword
	 *
	 * @param string $reqPassword incoming clear password
	 * @param string $dbHashedMd5Pwd incoming clear password
	 * @return  boolean
	 */
	private function validatePassword( $reqPassword , $dbHashedMd5Pwd ) {
	      if( empty($reqPassword) || empty($dbHashedMd5Pwd) ) return 0;
		  // remove salt+pepper. The 'clear' input password is md5-hashed client-side by javascript with salt+pepper:
	      $salt = $this->getSalt() ;
	      if( empty( $salt ) ) echo 'AuthService() Uups... ' . $this->settings['labels'][$this->settings['language']]['login_timeout'].'.';
	      $pepper = $this->getPepper() ;
	      $sr = array( $salt , $pepper );
	      $md5Password = str_replace( $sr , '' , $reqPassword );
		  // the hashed password in $dbHashedMd5Pwd was generated by PHP function password_hash(PASSWORD_BCRYPT) while storing by modelBase()
	      if( password_verify( $md5Password , $dbHashedMd5Pwd ) ) {
				return 1;
	      } else {
				return 0;
	      }
	}

	/**
	 * logout
	 *
	 * @return  boolean
	 */
	private function logout(){// LOGOUT BY USER, CLOSE 'COUNTDOWN'
			$_SESSION = array(); // unset stored data
			setcookie($this->cookiename, '' , time() - 36000 , $this->cookiepath , $this->cookieurl);
			// change salt for next login
			$this->createSaltAndPepper(); 
			return 0;
	}

	/**
	 * getPredefinedUsers
	 * used on install
	 *
	 * @return array
	 */
	private function getPredefinedUsers() {
		$userDB = $this->model->getRecordsets();
		return $userDB;
	}

	/**
	 * getAuthUsersRecordset
	 * used by boot
	 *
	 * @param string $fieldname optional returns array with all fields if empty
	 * @return  void
	 */
	Public function getAuthUsersRecordset( $fieldname = '' ) {
		return !empty($fieldname) && isset($this->userRecordset[$fieldname]) ? $this->userRecordset[$fieldname] : $this->userRecordset;
	}

	/**
	 * getFormLifeSeconds
	 *
	 * @return string the salt
	 */
	Public function getFormLifeSeconds(){
		return $this->formLifeSeconds;
	}

	/**
	 * getSalt
	 *
	 * @return string the salt
	 */
	Public function getSalt(){
		return $_SESSION['spicetime'] + $this->formLifeSeconds < time() ? FALSE : $_SESSION['salt'];
	}

	/**
	 * getPepper
	 *
	 * @return string the salt
	 */
	Public function getPepper(){
		return $_SESSION['spicetime'] + $this->formLifeSeconds < time() ? FALSE : $_SESSION['pepper'];
	}

	/**
	 * createSaltAndPepper
	 *
	 * @return string the salt
	 */
	Private function createSaltAndPepper(){
		$_SESSION['spicetime'] = time();
		$_SESSION['salt'] = bin2hex(openssl_random_pseudo_bytes( mt_rand(8,32) ));
		$_SESSION['pepper'] = bin2hex(openssl_random_pseudo_bytes( mt_rand(8,32) ));
	}

}

?>
