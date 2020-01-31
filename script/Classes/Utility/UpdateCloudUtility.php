<?php
namespace Drg\CloudApi\Utility;

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
	
require_once( 'DataUtility.php' );

/**
 * Class UpdateCloudUtility
 * write changes from export to csv and chk_*.json
 *
 */

class UpdateCloudUtility extends \Drg\CloudApi\Utility\DataUtility {
    
    /**
     * UpdateCloud
     *
     * @param int $exectime seconds
     * @return array 
     */
    public function UpdateCloud( $exectime ) {
		$runtime = array();
		$runtime[0] = microtime(true); // actual time in seconds
		
		$outDB = array();
		$createJobsUtility = new \Drg\CloudApi\Utility\CreateJobsUtility( $this->settings );

		$text = '';
		$log = array();
		$result = true;
		$fullfilled = 1;
		
		$runtime[1] = microtime(true) - $runtime[0]; // runtime in seconds
		
		$rawDb = $createJobsUtility->GetUpdateList();
		if( !count($rawDb) ) return true; // nothing to do
		
		$existingUsers = $this->readFromFile_CloudUsersAndAttributes();
		$existingGroups = $this->readFromFile_CloudGroups();
		$json = $this->readJsonFiles();
		
		// newGroup
		if( isset( $rawDb['newGroup'] ) ){
			foreach( $rawDb['newGroup'] as $ix => $group ){
					$fullfilled = 0;
					$totalRuntime = $runtime[ count($runtime) -1 ];
					if( $totalRuntime > $exectime ) break;
					$fullfilled = 1;
					
					$existingGroups[$group] = $group;
					$this->writeToFile_CloudGroups( $existingGroups ); 
					$outDB['newGroup'][$group] = $group;
					$result = $this->newGroup( $group );
					$log[] = 'newGroup:'.$group.'('.$result.') ';
					$runtime[count($runtime)] = microtime(true) - $runtime[0]; // total runtime in seconds
			}
		}
		
		// newUser
		if( $result && isset( $rawDb['newUser'] ) ){
			foreach( $rawDb['newUser'] as $username => $userRow ){
					$fullfilled = 0;
					$totalRuntime = $runtime[ count($runtime) -1 ];
					if( $totalRuntime > $exectime ) break;
					$fullfilled = 1;
					
					if( isset( $json['chk']['chk_UsersMissedJob'][$username]) ) unset( $json['chk']['chk_UsersMissedJob'][$username]);
					if( file_exists( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_UsersMissedJob.json' ) && !is_writable( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_UsersMissedJob.json' ) ){
							$this->report( 'File <b>' . basename(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_UsersMissedJob.json') . '</b> is not writable, maybe a cron-daemon is at work! UpdateCloudUtility->userChanges() #103<br />');
					}else{
							file_put_contents( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_UsersMissedJob.json' , json_encode(  $json['chk']['chk_UsersMissedJob'] ) );
					}
					$outDB['newUser'][$username] = $userRow;
					$result = $this->newUser( $userRow );
					$log[] = 'newUser:'.$username.'('.$result.') ';
					$existingUsers[$username] = $userRow;
					array_unshift( $existingUsers[$username] , array('ENABLED' => 'true') );
					if( $result ) $this->writeToFile_CloudUsersAndAttributes( $existingUsers ); 
					$runtime[count($runtime)] = microtime(true) - $runtime[0]; // total runtime in seconds
			}
		}
		
		// userChanges
		$totalRuntime = $runtime[ count($runtime) -1 ];
		if( $result && $totalRuntime <= $exectime && isset( $rawDb['userChanges'] ) ) {
				foreach( $rawDb['userChanges'] as $username => $userRow ){
					$fullfilled = 0;
					$totalRuntime = $runtime[ count($runtime) -1 ];
					if( $totalRuntime > $exectime ) break;
					$fullfilled = 1;
					if( !isset($existingUsers[$username]) )  continue; // a deleted user dont exists here
					$aResult = $this->userChanges( $username , $userRow , $existingUsers[$username] );
					$result = count($aResult) ? true : false;
					$log[] = 'userChanges:'.$username.'('.$result.')';
					$runtime[count($runtime)] = microtime(true) - $runtime[0]; // total runtime in seconds
					$existingUsers[$username] = $aResult;
					$this->writeToFile_CloudUsersAndAttributes( $existingUsers ); 
					$outDB['userChanges'][$username] = $userRow;
				}
		}
		
		// deleteUser
		$totalRuntime = $runtime[ count($runtime) -1 ];
		if( $result && $totalRuntime <= $exectime && isset( $rawDb['deleteUser'] ) ) {
				//$usersToDelete = $json['chk']['chk_UsersMaybeObsoleteJob'];
				foreach( $rawDb['deleteUser'] as $username => $userRow ){
					$fullfilled = 0;
					$totalRuntime = $runtime[ count($runtime) -1 ];
					if( $totalRuntime > $exectime ) break;
					$fullfilled = 1;
					
					if( isset($json['chk']['chk_UsersMaybeObsoleteJob'][$username]) ) unset($json['chk']['chk_UsersMaybeObsoleteJob'][$username]);
					if( file_exists( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_UsersMaybeObsoleteJob.json' ) && !is_writable( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_UsersMaybeObsoleteJob.json' ) ){
							$this->report( 'File <b>' . basename(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_UsersMaybeObsoleteJob.json') . '</b> is not writable, maybe a cron-daemon is at work! UpdateCloudUtility->updateCloud() #147<br />');
					}else{
							file_put_contents( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_UsersMaybeObsoleteJob.json' , json_encode( $json['chk']['chk_UsersMaybeObsoleteJob'] ) );
					}
					if( isset($existingUsers[$username]) ) unset( $existingUsers[$username] );
					$this->writeToFile_CloudUsersAndAttributes( $existingUsers ); 
					$result = $this->deleteUser( $username );
					$log[] = 'deleteUser:'.$username.'('.$result.') ';
					$runtime[count($runtime)] = microtime(true) - $runtime[0]; // total runtime in seconds
					if( $result ) $outDB['deleteUser'][$username] = $userRow;
				}
		}
		
		// deleteGroup
		$totalRuntime = $runtime[ count($runtime) -1 ];
		if( $result && $totalRuntime <= $exectime && isset( $rawDb['deleteGroup'] ) ) {
				foreach( $rawDb['deleteGroup'] as $groupname ){
					$fullfilled = 0;
					$totalRuntime = $runtime[ count($runtime) -1 ];
					if( $totalRuntime > $exectime ) break;
					$fullfilled = 1;
					
					if( isset($existingGroups[$groupname]) ){
						unset($existingGroups[$groupname]);
						$this->writeToFile_CloudGroups( $existingGroups ); 
					}elseif( !count($existingGroups) ){ 
						$this->writeToFile_CloudGroups( $existingGroups ); 
					}
					$result = $this->deleteGroup( $groupname );
					$log[] = 'deleteGroup:'.$groupname.'('.$result.') ';
					$runtime[count($runtime)] = microtime(true) - $runtime[0]; // total runtime in seconds
					if( $result ) $outDB['deleteGroup'][$groupname] = $groupname;
					$result = true;
				}
		}

		if( count($log) && $this->settings['debug']>=2 ) $this->report( "" . implode( "<br />" , $log )."" );
		
		$completed = $fullfilled && $result ? 1 : 0;
		
		return $completed;
	}

    /**
     * newUser
     *
     * @param array $userRow
     * @return bool 
     */
    public function newUser( $userRow ) {
    
		$PasswordForNewUser = $this->createPasswordForNewUser( $userRow['ID']  );
		$results = $this->connectorService->apiCreateUser( $userRow['ID'] , $PasswordForNewUser );
		if( $results['STATUSCODE'] != 100 && $results['STATUSCODE'] != 102 ) return $this->report( 'apiCreateUser:'.$userRow['ID'].' failed:'.$results['STATUSCODE'] );
		
		if( !isset($userRow['EMAIL']) ) {
			$results = 0;
			$this->debug[] = 'apiUpdateUserField:'.$userRow['ID'].' :EMAIL:empty. failed:'.$results['STATUSCODE'] ;
		}else{
			$results = $this->connectorService->apiUpdateUserField( $userRow['ID'] , 'email' , $userRow['EMAIL'] );
			if( $results['STATUSCODE'] != 100 ) $this->debug[] = 'apiUpdateUserField:'.$userRow['ID'].' :EMAIL:'.$userRow['EMAIL'].' failed:'.$results['STATUSCODE'] ;
		}
		
		$results = $this->connectorService->apiUpdateUserField( $userRow['ID'] , 'quota' , is_numeric( $userRow['QUOTA'] ) ? $userRow['QUOTA'].'GB' : $userRow['QUOTA'] );
		if( $results['STATUSCODE'] != 100 ) $this->debug[] = 'apiUpdateUserField:'.$userRow['ID'].' :QUOTA:'.$userRow['QUOTA'].' failed:'.$results['STATUSCODE'] ;
		
		// with Nextcloud 12 display is depriciated, use displayname
		$results = $this->connectorService->apiUpdateUserField( $userRow['ID'] , 'display' , $userRow['DISPLAYNAME'] );
		if( $results['STATUSCODE'] != 100 )  $this->debug[] = 'apiUpdateUserField:'.$userRow['ID'].' :DISPLAYNAME:'.$userRow['DISPLAYNAME'].' failed:'.$results['STATUSCODE'] ;
		
		// groups like grp_1 ... grp_5 ( $this->settings['group_amount'] )
		foreach( $userRow as $fld => $grp ){
			if( strpos( ' ' . $fld , 'grp_' ) != 1 ) continue;
			if( empty($grp) ) continue;
			
			$results = $this->connectorService->apiAppendUserToGroup( $userRow['ID'] , $grp );
			if( $results['STATUSCODE'] != 100 ) $this->debug[] = 'apiAppendUserToGroup:'.$userRow['ID'].' :'.$grp;
			
		}
		return TRUE;
    }


    /**
     * createPasswordForNewUser
     *
     * @param string $username
     * @return string 
     */
    public function createPasswordForNewUser( $username ) {
			if( empty($username) ) return false;

			$crypt_settings = array(
					'numb_range'=> array( 'min'=>1001 , 'max'=>9999 ),
					'special_chars' => array( '$' , '£' , '#' , '@' , '%' ),
					'separers' => array( '.' , ':' , '-' , '_' , '+' )
			);
			
			$spcNr = rand( 0 , count($crypt_settings['special_chars'])-1 );
			$spcChr = $crypt_settings['special_chars'][$spcNr];
			
			$sepNr = rand( 0 , count($crypt_settings['separers'])-1 );
			$sepChr = $crypt_settings['separers'][$sepNr];
			
			$num = rand( $crypt_settings['numb_range']['min'] , $crypt_settings['numb_range']['max'] );
			
			$uPArt = explode( '.' , $username );
			if( count($uPArt) == 2 ){
				$endLastName = substr( $uPArt[1] , strlen($uPArt[1])-2 , 2 );
			}else{
				$endLastName = $username;
			}
			$startOfFirstname = substr( $uPArt[0] , 0 , 2 );
			
			return ucFirst( $endLastName ) . $spcChr . ucFirst( $startOfFirstname ) . $sepChr . $num;
    }

    /**
     * userChanges
     *
     * @param string $username
     * @param array $aParam
     * @param array $origRow
     * @return array  $origRow
     */
    public function userChanges( $username , $aParam , $origRow ) { 
		$json = $this->readJsonFiles();
			foreach( $aParam as $fieldname => $content ){
				
				if( 'appendToGroup' == $content ){
					$origRow = $this->update_appendToGroup( $username , $fieldname , $origRow );
					if(isset($json['chk']['chk_GroupMissedJob'][$fieldname.'_._'.$username])) unset($json['chk']['chk_GroupMissedJob'][$fieldname.'_._'.$username]);
					
				}elseif( 'removeFromGroup' == $content ){
					$origRow = $this->update_removeFromGroup( $username , $fieldname , $origRow );
					if(isset($json['chk']['chk_GroupMaybeObsoleteJob'][$fieldname.'_._'.$username])) unset($json['chk']['chk_GroupMaybeObsoleteJob'][$fieldname.'_._'.$username]);
					
				}else{ // eg. if('CHANGED_QUOTA' == $fieldname) $origRow = $this->update_changed_quota( $username , $content , $origRow );
					$method = 'update_' . strtolower($fieldname);
					if( method_exists( $this , $method ) ){
						$origRow = $this->$method( $username , $content , $origRow );
					}
				}
			}
			if( file_exists( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_GroupMaybeObsoleteJob.json' ) && !is_writable( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_GroupMaybeObsoleteJob.json' ) ){
					$this->report( 'File <b>' . basename(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_GroupMaybeObsoleteJob.json') . '</b> is not writable, maybe a cron-daemon is at work! UpdateCloudUtility->userChanges() #286<br />');
			}else{
					file_put_contents( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_GroupMaybeObsoleteJob.json' , isset($json['chk']['chk_GroupMaybeObsoleteJob']) ? json_encode( $json['chk']['chk_GroupMaybeObsoleteJob'] ) : '' );
			}
			
			if( file_exists( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_GroupMissedJob.json' ) && !is_writable( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_GroupMissedJob.json' ) ){
					$this->report( 'File <b>' . basename(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_GroupMissedJob.json') . '</b> is not writable, maybe a cron-daemon is at work! UpdateCloudUtility->userChanges() #292<br />');
			}else{
					file_put_contents( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'chk_GroupMissedJob.json' , isset($json['chk']['chk_GroupMissedJob']) ? json_encode( $json['chk']['chk_GroupMissedJob'] ) : '' );
 			}
			return $origRow;
    }

    /**
     * update_changed_quota
     *
     * @param string $username
     * @param string $value
     * @param array $origRow
     * @return array  $origRow
     */
    public function update_changed_quota( $username , $value , $origRow ) {
		$stringQuota = is_numeric( $value ) ? $value.'GB' : $value;
		$results = $this->connectorService->apiUpdateUserField( $username , 'quota' , $stringQuota );
		if( $results['STATUSCODE'] != 100 ) return $this->report( 'userChanges:QUOTA:'.$value.' failed:'.$results['STATUSCODE'] );
		$origRow['QUOTA'] = $stringQuota;
		return $origRow;
    }

    /**
     * update_appendToGroup
     *
     * @param string $username
     * @param string $value
     * @param array $origRow
     * @return array  $origRow
     */
    public function update_appendToGroup( $username , $value , $origRow ) {
		$results = $this->connectorService->apiAppendUserToGroup( $username , $value );
		if( $results['STATUSCODE'] != 100 )  $this->report( 'appendToGroup:'.$value.' failed:'.$results['STATUSCODE'].' for:'.$username );
		$setGroup=array();
		for( $f = 1 ; $f <= $this->settings['group_amount'] ; ++$f ){
			if( isset($origRow['grp_'.$f]) && !empty($origRow['grp_'.$f]) ) {
				$setGroup[$origRow['grp_'.$f]] = $origRow['grp_'.$f];
			}
		}
		$setGroup=array($value=>$value);
		$f = 1;
		foreach( $setGroup as $grp ){
			$origRow['grp_'.$f] = $grp;
			++$f;
		}
		for( $f2 = $f ; $f2 <= $this->settings['group_amount'] ; ++$f2 ){
			$origRow['grp_'.$f2] = '';
		}
		return $origRow;
    }

    /**
     * update_removeFromGroup
     *
     * @param string $username
     * @param string $value
     * @param array $origRow
     * @return array  $origRow
     */
    public function update_removeFromGroup( $username , $value , $origRow ) { 
		$results = $this->connectorService->apiRemoveUserFromGroup( $username , $value );
		if( $results['STATUSCODE'] != 100 ) $this->report( 'removeFromGroup:'.$value.' failed:'.$results['STATUSCODE'].' for:'.$username );

		$setGroup=array();
		for( $f = 1 ; $f <= $this->settings['group_amount'] ; ++$f ){
			if( isset($origRow['grp_'.$f]) && !empty($origRow['grp_'.$f]) ) {
				$setGroup[$origRow['grp_'.$f]] = $origRow['grp_'.$f];
			}
		}
		if(isset($setGroup[$value])) unset($setGroup[$value]);
		$f = 1;
		foreach( $setGroup as $grp ){
			$origRow['grp_'.$f] = $grp;
			++$f;
		}
		for( $f2 = $f ; $f2 <= $this->settings['group_amount'] ; ++$f2 ){
			$origRow['grp_'.$f2] = '';
		}
		return $origRow;
    }

    /**
     * deleteUser
     *
     * @param string $username
     * @return bool 
     */
    public function deleteUser( $username ) {
			$result = $this->connectorService->apiDeleteUser( $username );
			$repVar = array( '%20'=>'-' , '/'=>'_' );
			$filename = rtrim($this->settings['dataDir'] . $this->settings['processing'],'/') . '/' .  str_replace( array_keys($repVar) , $repVar , 'users_'.$username ) . '.xml';
			if(file_exists($filename))unlink($filename);
			if( 102 != $result['STATUSCODE'] && 100 != $result['STATUSCODE'] ) return $this->report( 'deleteUser failed:'.$username.':'.$result['STATUSCODE'] );
			return true;
    }

    /**
     * newGroup
     *
     * @param string $group
     * @return bool 
     */
    public function newGroup( $group ) {
		$result = $this->connectorService->apiCreateGroup($group );
		if( 102 != $result['STATUSCODE'] && 100 != $result['STATUSCODE'] ) return $this->report( 'newGroup failed:'.$group.':'.$result['STATUSCODE'] );
		return true;
    }

    /**
     * deleteGroup
     *
     * @param string $group
     * @return bool 
     */
    public function deleteGroup( $group ) {
			$result = $this->connectorService->apiDeleteGroup( $group );
			if( 102 != $result['STATUSCODE'] && 100 != $result['STATUSCODE'] ) return $this->report( 'deleteGroup failed:'.$group.':'.$result['STATUSCODE'] );
			return $this->report( 'deleteGroup ok:'.$group.':'.$result['STATUSCODE'] , true);
    }

    /**
     * report
     *
     * @param string $message
     * @param bool $returnValue
     * @return bool 
     */
    public function report( $message , $returnValue = FALSE ) {
		$counter = count($this->debug)+1;
		$this->debug[ 'UpdateCloudUtility:' . $counter ] = $message;
		return $returnValue;
    }
    
}
