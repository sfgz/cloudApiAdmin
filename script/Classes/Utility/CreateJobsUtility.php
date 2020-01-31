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
 * Class CreateJobsUtility
 */

class CreateJobsUtility extends \Drg\CloudApi\Utility\DataUtility {

	/**
	* compareDatabaseWithClouddata
	* 
	* @param array $dbData     array from this->readIntranetUsersAndCloudgroups()
	* @param array $cloudData  array from ReadCloudUtility-readFromFile_CloudUsersAndAttributes()
	* @return array
	*/
	public function compareDatabaseWithClouddata( $dbData , $cloudData ) {
			$responseArr = array( 'UsersMaybeObsolete'=>array() ,'UsersMissed'=>array() ,'GroupMaybeObsolete'=>array() ,'GroupMissed'=>array() , 'GroupToAdd'=>array());
			$src = array( $dbData , $cloudData );
			// sub-array for groups
			if( !is_array($dbData) || !is_array($cloudData) ) return $responseArr;
			
			foreach( $src as $tabIx => $table ){
				if(!is_array($table)) continue;
				foreach( $table as $username => $dbRow ){
					if(!is_array($dbRow)) continue;
					foreach($dbRow as $field => $content ){
						if( substr( $field , 0 , 4 ) == 'grp_' && !empty($content) ){
							$src[$tabIx][$username]['groups'][$content] = $field; 
 							unset($src[$tabIx][$username][$field]); 
						}
					}
				}
			}
			
			$attLabel = array( 'MaybeObsolete' , 'Missed' );

			foreach( $src as $tableIx => $table ){
				if(!is_array($table)) continue;
				foreach( $table as $username => $dbRow ){
						$otherTable = ( $tableIx-1 ) * ( $tableIx-1 ) ;
						if( !isset($src[$otherTable][$username]) ){
							if( count($dbRow) )$responseArr['Users'.$attLabel[$otherTable]][$username] = $dbRow;
						}else{
							if(!isset($dbRow['groups'])) continue;
							foreach($dbRow['groups'] as $groupname => $colName ){
									if(empty($groupname) ) continue;
									if( !isset($src[$otherTable][$username]['groups'][$groupname]) ){
										// set user missing or overdue in group
										if( count($src[$otherTable][$username]) ) $responseArr['Group'.$attLabel[$otherTable]][$groupname]['users'][$username] = $src[$otherTable][$username];
									}
							}
						}
				}
			}
			
			if(isset($responseArr['GroupMaybeObsolete'])){
				foreach( $responseArr['GroupMaybeObsolete'] as $groupname => $grouprow ){
					foreach( $grouprow['users'] as $username => $row ){
						$row['obsolete'] = $groupname;
						$responseArr['GroupMaybeObsolete'][$groupname . '_._' . $username] = $row;
					}
					unset($responseArr['GroupMaybeObsolete'][$groupname]);
				}
			}

			if(isset($responseArr['GroupMissed'])){
				foreach( $responseArr['GroupMissed'] as $groupname => $grouprow ){
					foreach( $grouprow['users'] as $username => $row ){
							$row['missed'] = $groupname;
							$responseArr['GroupMissed'][$groupname . '_._' . $username] = $row;
					}
					// FIXME obsolete: in JobsEditor() we need $responseArr['GroupMissed'][$groupname] , therefore rename the variable to 'GroupToAdd' before unset it
					//$responseArr['GroupToAdd'][$groupname] = $responseArr['GroupMissed'][$groupname];
					unset($responseArr['GroupMissed'][$groupname]);
				}
			}
			
			// if delete-list enabled, replace UsersMaybeObsolete with delete-list, if user is in cloud
			if( $this->settings['use_delete_list'] ){
				if( isset($responseArr['UsersMaybeObsolete']) ) unset($responseArr['UsersMaybeObsolete']);
				if( is_array($cloudData) ) {
					$deleteDb = $this->readLocalUsersFiles( 'local/delete' , TRUE );
					if( is_array($deleteDb) ){
// 						echo 'createJobsUtility count: ('.count($deleteDb).')<br>';
						foreach( array_keys($deleteDb) as $username ){
							if( isset($responseArr['UsersMissed']) && isset($responseArr['UsersMissed'][$username]) ) unset($responseArr['UsersMissed'][$username]);
							if( !isset($cloudData[$username]) ) continue;
							$responseArr['UsersMaybeObsolete'][$username] = $cloudData[$username];
						}
					}
				}
			}
			
			return $responseArr;
	}

	/**
	* CreateChecklists
	* create chk_*.json to select all specified checkboxes 
	* on very first call after import
	* 
	* @return array
	*/
	public function CreateChecklists() {
			// create chk_*.json to select all specified checkboxes on very first call after import
				$sumDB = $this->readLocalUsersFiles( $this->settings['localusers'] , FALSE );// FIXME set to true NOT WORKING!
				$cloudUsers = $this->readFromFile_CloudUsersAndAttributes();
				$db = $this->compareDatabaseWithClouddata($sumDB , $cloudUsers );

				if( !empty($this->settings['checkByDefault_UsersMissed']) && isset($db['UsersMissed']) ) $this->createJoblist($db['UsersMissed'] , 'UsersMissedJob');
 				if( !empty($this->settings['checkByDefault_GroupMissed']) && isset($db['GroupMissed']) ) $this->createJoblist( $db['GroupMissed'] , 'GroupMissedJob' );
				if( !empty($this->settings['checkByDefault_GroupMaybeObsolete']) && isset($db['GroupMaybeObsolete']) ) $this->createJoblist( $db['GroupMaybeObsolete'] , 'GroupMaybeObsoleteJob' );
				if(	(!empty($this->settings['checkByDefault_UsersMaybeObsolete']) || !empty($this->settings['use_delete_list'])) &&  isset($db['UsersMaybeObsolete']) )	$this->createJoblist( $db['UsersMaybeObsolete'] , 'UsersMaybeObsoleteJob' );

				$this->debug['CreateChecklists'] = 'Import abgeschlossen';
	}

	/**
	* createJoblist
	* used right after users-import from cloud
	* to pre-select checkboxes on first call
	* 
	* @param array $sumDB
	* @param string $filename middle part of filename
	* @return array
	*/
	public function createJoblist( $sumDB , $filename = 'UsersMissedJob' ) {
		if( !is_array($sumDB) ) return array();

		$outDB = array();
		foreach( array_keys($sumDB) as $key ) $outDB[ $key ] = $key;
		
		// dont select, if name is whitelistet
		if( file_exists( $this->settings['dataDir'].'local/whitelist_'.$filename.'.json' ) && ( $this->settings['edit_joblist'] || $this->settings['download_details'] )){
				$whitelist = json_decode( file_get_contents($this->settings['dataDir'].'local/whitelist_'.$filename.'.json') , true );
				foreach( $whitelist as $ix => $cnt ){
						if( isset($outDB[ $ix ]) ) unset($outDB[ $ix ]);
				}
		}
		
		// never delete the admin user that is used to connect with distant API
		if( $filename == 'UsersMaybeObsoleteJob' ) {
			$connectionUsername = $this->settings['connection_user'];
			if(isset($outDB[ $connectionUsername ])) unset($outDB[ $connectionUsername ]);
		}
		
		$jsonData = json_encode( $outDB );
		if( file_exists( $this->settings['dataDir'].'api/chk_'.$filename.'.json' ) && !is_writable( $this->settings['dataDir'].'api/chk_'.$filename.'.json' ) ){
				$this->debug[ 'CreateJobsUtility->createJoblist' ] =  'File <b>' . 'chk_'.$filename.'.json' . '</b> is not writable, maybe a cron-daemon is at work! CreateJobsUtility->createJoblist() #175<br />';
		}else{
				file_put_contents( $this->settings['dataDir'].'api/chk_'.$filename.'.json' , $jsonData );
		}
		return $jsonData ;
	}

	/**
	* updateJoblist
	* run by submit button, 
	* reads files and compares values with incoming checkboxes
	* 
	* @param string $onlyOneList
	* @return boolean always true
	*/
	public function updateJoblist($onlyOneList) {
			$chkTypes = array('chk' => 'api/' ,'whitelist' => 'local/' );
			if( empty($onlyOneList) ) return;
			$table = array();
			$ok =  $onlyOneList;
			$possibleChanged  = 0; 
			foreach( $chkTypes as $type => $dir ){
					$jsonFile[$type] = $this->settings['dataDir'] . $dir . $type.'_'.$ok.'Job.json';
					if( file_exists( $jsonFile[$type] ) ){
						$table[$type] = json_decode( file_get_contents( $jsonFile[$type] ) , true );
					}
			
					if( isset($this->settings['req'][$type.'_'.$ok]) ) {
						foreach($this->settings['req'][$type.'_'.$ok] as $username => $checkValue){
							$possibleChanged += 1;
							if(!strpos($username,'_hidden')) { 
								$table[$type][$username] = $checkValue; 
							}else{ 
								if( isset($table[$type][$username]) ) unset($table[$type][$username]); 
							}
						}
						if(isset($table[$type])){
							foreach($table[$type] as $username => $checkValue){
								if( 
								isset($table[$type][$username]) && 
								!isset($this->settings['req'][$type.'_'.$ok][$username])  && 
								isset($this->settings['req'][$type.'_'.$ok][$username.'_hidden']) 
								) unset($table[$type][$username]);
							}
						}
					}else{
						if( isset($table[$type]) ) $this->settings['req'][$type.'_'.$ok] = $table[$type];
					}
			}

			foreach( $chkTypes as $type => $dir ){
				if(file_exists($jsonFile[$type])) {unlink($jsonFile[$type]);}
				if( !empty($table[$type]) ) {
					$jsonFile[$type] = $this->settings['dataDir'] . $dir . $type.'_'.$ok.'Job.json';
					$jsonData = json_encode( $table[$type] );
					if( file_exists( $jsonFile[$type] ) && !is_writable( $jsonFile[$type] ) ){
							$this->debug[ 'CreateJobsUtility->updateJoblist' ] =  'File <b>' . basename($jsonFile[$type]) . '</b> is not writable, maybe a cron-daemon is at work! CreateJobsUtility->updateJoblist() #231<br />';
					}else{
							file_put_contents( $jsonFile[$type] , $jsonData );
					}
				}
			}
			if( $possibleChanged ) $this->debug[ 'Exportliste' ] = 'gespeichert ('.$onlyOneList.', '.$possibleChanged.' Werte)';
			return true;
	}

    /**
     * GetUpdateList
     * 
     * this job data is base for exportAction (view) AND UpdateCloud!
     * 
     * returned array-names:
     * newGroup = 1 dim array newGroup[ABC17 A] = ABC17 A; // $groupname = ABC17 A
     * updateUserInfo = 2 dim array updateUserInfo[$username][ $FIELD ] = value; 
     *   USED TO DISPLAY, NOT USED FOR CRON (updateUserInfo)
     * deleteUser = 2 dim array deleteUser[$username][ $FIELD ] = value;
     * deleteGroup = 1 dim array deleteGroup[ $groupname ] = $groupname;
     * newUser = 2 dim array newUser[$username][ $FIELD ] = value;
     * userChanges = 2 dim array userChanges[$username][ $groupname || CHANGED_QUOTA ] = ( appendToGroup || removeFromGroup ) || $value ;
     *   USED FOR CRON ONLY, NOT FOR DISPLAY
     *
     * @param boolean $preventReadQuotaFromGroup optional, default is TRUE
     * @return array $job
     */
    public function GetUpdateList( $preventReadQuotaFromGroup = TRUE ) {
		// get affored user from different users-files, preventReadQuotaFromGroup = TRUE
		$localUsers = $this->readLocalUsersFiles( $this->settings['localusers'] , $preventReadQuotaFromGroup );
 		$this->tableFunctions = New \Drg\CloudApi\Utility\TableFunctionsUtility( $this->settings );
		
		// get existing user from cloud
 		$cloudUsers = $this->readFromFile_CloudUsersAndAttributes();
		// get checked jobs
		$checkedJob = $this->getCheckedJobs();

		$touchedUsers = array();

 		// following is disabled by default ( settings['update_only_users_in_local_list'] = 0 )
 		if( $this->settings['update_only_users_in_local_list'] ){
			foreach( array_keys( $cloudUsers ) as $uIx ) if( !isset($localUsers[$uIx]) ) unset($cloudUsers[$uIx]);
		}
		
		// get existing groups from groups.xml 
		$existingGroups = $this->readFromFile_CloudGroups();
		
		// define array with jobs
		$job = array();
		// $job['newGroup'] determine groups to create
		$allGroups = $existingGroups;
		$afforedGroups = array();
 		if( isset( $checkedJob['chk_UsersMissedJob'] ) ){
			foreach( $checkedJob['chk_UsersMissedJob'] as $user ){
				 if( !isset( $localUsers[$user] ) )  continue; // Error, shoud not be not possible
				 if( !isset( $localUsers[$user]['grp_1'] ) || empty( $localUsers[$user]['grp_1'] ) ) continue; // prevent errors like users with no group named 1...999
				 for( $grpNr = 1 ; $grpNr <= $this->settings['group_amount'] ; ++$grpNr){
						$sGrp = 'grp_' . $grpNr;
						if( isset( $localUsers[$user][$sGrp] ) ){
								$groupname = trim($localUsers[$user][$sGrp]);
								if( empty($groupname) ) continue;
								$allGroups[$groupname] = $groupname;
								$afforedGroups[$groupname] = $groupname;
								if( !isset($existingGroups[$groupname]) ) {
									$job['newGroup'][$groupname] = $groupname;
								}
						}
				 }
			}
 		}
 		
 		// $job['appendToGroup'] group change + append
 		if( isset( $checkedJob['chk_GroupMissedJob'] ) ){
				$setGroups = array();
				// [lp-Werbetechnik.andreas.borter]
				if( is_array($allGroups) ){foreach( $allGroups as $grpNam ){
						// loop all cloud-users for each group
						if( is_array($cloudUsers) ){foreach( $cloudUsers as $user => $usrRow ){
								$setGroups = array();
								// this user is not member of this group
								if( !isset($checkedJob['chk_GroupMissedJob'][ $grpNam .'_._'. $user ]) ) continue; 
								// this user gets deleted 
								if( isset( $checkedJob['chk_UsersMaybeObsoleteJob'][$user] ) )  continue;
								// loop through group-fields grp_1 ... eg. grp_5
								for( $gNr = 1 ; $gNr <= $this->settings['group_amount'] ; ++$gNr ){
										if( isset($usrRow['grp_' . $gNr]) && !empty($usrRow['grp_' . $gNr]) ){
											$setGroups[$usrRow['grp_' . $gNr]] = $usrRow['grp_' . $gNr];
											$usrRow['grp_' . $gNr] = '';
										}
								}
								$setGroups[$grpNam] = $grpNam;
								$f = 1;
								foreach( $setGroups as $grp ){
									$usrRow['grp_' . $f] = $grp;
									++$f;
								}
								for( $gNr = $f ; $gNr <= $this->settings['group_amount'] ; ++$gNr ) $usrRow['grp_' . $gNr] = '';
								$touchedUsers[$user][$grpNam] = 'appendToGroup';
								
								$job['appendToGroup'][$grpNam][$user] = $usrRow;
								$cloudUsers[$user] = $usrRow;
								$afforedGroups[$grpNam] = $grpNam;
						}}
				}}
				// if group is not set in cloud:
				foreach( $checkedJob['chk_GroupMissedJob'] as  $grpUserNams ){
				$setUserGroups = array();
							$aGrpUsr = explode( '_._' , $grpUserNams );
							$grpNam = $aGrpUsr[0];
							$user = $aGrpUsr[1];
							if( isset( $checkedJob['chk_UsersMaybeObsoleteJob'][$user] ) )  continue;
							if( !isset($setUserGroups[$user] ) )$setUserGroups[$user] = array();
							if( !isset($allGroups[$grpNam]) ) $job['newGroup'][$grpNam] = $grpNam;
								if( !isset($localUsers[$user]) )$localUsers[$user]['ID'] = $user;
								for( $gNr = 1 ; $gNr <= $this->settings['group_amount'] ; ++$gNr ){
										if( isset($localUsers[$user]['grp_' . $gNr]) && !empty($usrRow['grp_' . $gNr]) ) continue;
										if( isset($setUserGroups[$user][$grpNam]) ) continue;
										$setUserGroups[$user][$grpNam] = $grpNam;
										$localUsers[$user]['grp_' . $gNr] = $grpNam;
										$afforedGroups[$grpNam] = $grpNam;
										$touchedUsers[$user][$grpNam] = 'appendToGroup';
										break;
								}
							$job['appendToGroup'][$grpNam][$user] = $user;
							$allGroups[$grpNam] = $grpNam;
							$afforedGroups[$grpNam] = $grpNam;
				}
 		}
 		
 		// $job['removeFromGroup'] group change + remove
 		if( isset( $checkedJob['chk_GroupMaybeObsoleteJob'] ) ){
				// [ML15 A.andreas.borter]
				if( is_array($existingGroups) ){foreach( $existingGroups as $grpNam ){  
						if( is_array($cloudUsers) ){foreach( $cloudUsers as $user => $usrRow ){
								if( !isset($checkedJob['chk_GroupMaybeObsoleteJob'][ $grpNam .'_._'. $user ]) ) continue;
								$changedCloudUser = $usrRow;
								for( $gNr = 1 ; $gNr <= $this->settings['group_amount'] ; ++$gNr ){
										if( !isset($changedCloudUser['grp_' . $gNr]) ) {
											// last field reached on $gNr-1
											break;
										}
										if( $changedCloudUser['grp_' . $gNr] != $grpNam ) {
											$coudBeGoodGroup = $changedCloudUser['grp_' . $gNr]; 
											if( isset($checkedJob['chk_GroupMaybeObsoleteJob'][ $coudBeGoodGroup .'_._'. $user ]) ) {
												unset($changedCloudUser['grp_' . $gNr]);
												break;
											}
										}else{
											unset($changedCloudUser['grp_' . $gNr]);
										}
								}
								$newFldNr = 1;
								$newGroupedCloudUser = $changedCloudUser;
								for( $gNr = 1 ; $gNr <= $this->settings['group_amount'] ; ++$gNr ){
										if( isset( $changedCloudUser['grp_' . $gNr]) ){
											if( isset( $newGroupedCloudUser['grp_' . $gNr]) ) unset($newGroupedCloudUser['grp_' . $gNr]);
											$newGroupedCloudUser['grp_' . $newFldNr] = $changedCloudUser['grp_' . $gNr];
											++$newFldNr;
										}
								}
								if( !isset( $checkedJob['chk_UsersMaybeObsoleteJob'][$user] ) ) {
									$cloudUsers[$user] = $newGroupedCloudUser;
									$touchedUsers[$user][$grpNam] = 'removeFromGroup';
									$job['removeFromGroup'][$grpNam][$user] = $newGroupedCloudUser;
								}
						}}
				}}
 		}
 		
		// $job['newUser'] users to create
 		if( isset( $checkedJob['chk_UsersMissedJob'] ) ){
			foreach( $checkedJob['chk_UsersMissedJob'] as $user ){
				 if( isset( $localUsers[$user] ) ) {
						if(isset($cloudUsers[$user])) continue;
						$job['newUser'][$user] = $localUsers[$user];
						if( ( !file_exists($this->settings['dataDir'] . 'local/quota/'.$this->settings['table_conf']['group_quota']['force_filename']) || !$this->settings['use_quota_list']) || !$this->settings['increase_personal_quota_to_group'] ) continue;
						$aComparedQuotas = array();
						foreach( $localUsers[$user] as $fldNam => $grpNam ){
							if( empty($grpNam) ) continue;
							if( substr($fldNam , 0 , 4 ) != 'grp_' ) continue;
							$arrVal = $this->tableFunctions->getQuotaFromGroupWithSearchpattern( $grpNam );
							$arrNam = $this->tableFunctions->string2byte($arrVal);
							$aComparedQuotas[$arrNam] = $arrVal;
						}
						// newQuota = MAX: get the first element of decreased sorted list
						krsort($aComparedQuotas);
						$job['newUser'][$user]['QUOTA'] = array_shift($aComparedQuotas);
				 }
			}
 		}
 		
		// $job['deleteUser'] users to delete
 		if( isset( $checkedJob['chk_UsersMaybeObsoleteJob'] ) && isset($this->settings['connection_user']) ){
				// never delete the user that is used to connect with distant API
				$connectionUsername = $this->settings['connection_user'];
				foreach( $checkedJob['chk_UsersMaybeObsoleteJob'] as $user => $usrRow ){
						if( $connectionUsername != $user && isset($cloudUsers[$user]) ){
							$job['deleteUser'][$user] = $cloudUsers[$user];
 							$cloudUsers[$user]['remove'] = 1;
						}
				}
 		}
 		
 		//  if member of no groups, set grp_1 to nogroup
 		if( !empty($this->settings['orphan_groupname']) && is_array($cloudUsers) ){foreach( $cloudUsers as $user => $userRow ){
				if(isset($userRow['remove'])) continue;
				if( isset($userRow['grp_1']) && !empty($userRow['grp_1'])  ) continue;
				$cloudUsers[$user]['grp_1'] = $this->settings['orphan_groupname'];
				$touchedUsers[$user][$this->settings['orphan_groupname']] = 'appendToGroup';
				$job['appendToGroup'][$this->settings['orphan_groupname']][$user] = $user;
				$afforedGroups[$this->settings['orphan_groupname']] = $this->settings['orphan_groupname'];
 		}}
 		
		// determine groups to remove: first get used groups, then delete those groups which are not set as used group
		if( is_array($cloudUsers) ){foreach( $cloudUsers as $user=>$userRow ){
			if(isset($userRow['remove'])) continue;
			for( $gNr = 1 ; $gNr <= $this->settings['group_amount'] ; ++$gNr ){
				if(isset($userRow['grp_' . $gNr])) $afforedGroups[$userRow['grp_' . $gNr]] = $userRow['grp_' . $gNr];
			}
		}}
		if( is_array($allGroups) ){foreach( $allGroups as $existingGroup ){
			if( !isset($afforedGroups[$existingGroup] ) ) $job['deleteGroup'][$existingGroup] = $existingGroup;
		}}
		
		// determine quota - changes chk_GroupMissedJob + chk_GroupMaybeObsoleteJob
		$cloudUsers = $this->determineQuotaChanges( $cloudUsers , $localUsers );
		
		// store all changed users in $sortDB
		// changes quota and group, but not email and displayname
		$sortDB = array();
		if( is_array($cloudUsers) ){
				$mainFields = $this->settings['download_details'] ? array( 'ID' , 'DISPLAYNAME' , 'EMAIL' , 'QUOTA'  ) : array( 'ID' , 'DISPLAYNAME' , 'QUOTA'  );
				$fields = $mainFields;
				for( $z=1 ; $z<=$this->settings['group_amount'] ; ++$z ) $fields[] = 'grp_' . $z ;
						foreach( $cloudUsers as $user => $userRow ){
								if( !isset($touchedUsers[$user]) && !isset($userRow['CHANGED_QUOTA']) ) continue;
								if( isset($userRow['CHANGED_QUOTA']) && empty($userRow['QUOTA']) ) continue;
								foreach( $fields as $fld ) $sortDB[$user][$fld] = isset($userRow[$fld]) ? $userRow[$fld] : '' ;
								if( isset($userRow[ 'CHANGED_QUOTA' ]) && ! empty($userRow[ 'CHANGED_QUOTA' ]) ) {
									$touchedUsers[$user]['CHANGED_QUOTA'] = $userRow['QUOTA'];
									$sortDB[$user]['CHANGED_QUOTA'] = $userRow[ 'CHANGED_QUOTA' ]; 
								}
						}
		}
		
		// append quota-changes
		$job['updateUserInfo'] = $this->enrichUpdatableCloudusersWithEditMarker($sortDB,$touchedUsers);// USED TO DISPLAY, NOT USED FOR CRON
		$job['userChanges'] = $touchedUsers; // USED FOR CRON ONLY, NOT FOR DISPLAY

		return $job ;
	}
    /**
     * determineQuotaChanges
     * increase cloudQuota to localQuota if the new quota is higher
     *
     * @param array $cloudUsers users to revise
     * @param array $localUsers defined values for users
     * @return void
     */
    public function determineQuotaChanges( $cloudUsers , $localUsers ) {
		if( !is_array($cloudUsers) ) return false;

		$debugger = false;
// 		$this->settings['increase_personal_quota_to_group'] = true;
 		$this->tableFunctions = New \Drg\CloudApi\Utility\TableFunctionsUtility( $this->settings );

 		foreach( $cloudUsers as $user => $usrRow ){
				if( isset( $usrRow['remove'] ) ) continue;
				
				// only change if in local list 
				if( !isset($localUsers[$user]) && $this->settings['update_only_users_in_local_list'] ) {
					unset($cloudUsers[$user]);
					continue;
				}
				
				// add email if empty on cloud
				if( isset($localUsers[$user]) && isset($localUsers[$user]['EMAIL']) && empty( $usrRow['EMAIL' ] ) ) {
					$cloudUsers[$user]['EMAIL'] = $localUsers[$user]['EMAIL'];
				}
				
				$aComparedQuotas = array();
				$personalQuotaValue = '';
				$personalQuotaFieldname = 0;
				// add quota from cloud to compare list
 				if( isset($cloudUsers[$user]['QUOTA'])  && $this->settings['never_decrease_quota_set_in_cloud'] ) {
						$personalQuotaValue = $cloudUsers[$user]['QUOTA'];
						if( $debugger ) $this->debug[ 'quota_'.$user.'_cloudUsers' ] = $personalQuotaValue;
 				}
				
				// add personal quota to compare-list
				if( isset($localUsers[$user]) && isset($localUsers[$user]['QUOTA']) && $localUsers[$user]['QUOTA' ] > $personalQuotaValue ) {
					$personalQuotaValue = $localUsers[$user]['QUOTA'];
					if( $debugger ) $this->debug[ 'quota_'.$user.'_localUsers' ] = $personalQuotaValue;
				}
				
				// add group-quota to compare list
				if( (file_exists($this->settings['dataDir'] . 'local/quota/'.$this->settings['table_conf']['group_quota']['force_filename']) && $this->settings['use_quota_list']) && (empty($personalQuotaValue) || $this->settings['increase_personal_quota_to_group']) ) {
					$groupList = 'grp_1,grp_2,grp_3,grp_4,grp_5';
					$aComparedQuotas = $this->tableFunctions->getPossibleValuesFromFieldlist( $personalQuotaValue , $groupList , $usrRow , $this->settings['increase_personal_quota_to_group']); 
					// newQuota = MAX: get the first element of decreased sorted list
					krsort($aComparedQuotas);
					$personalQuotaValue = array_shift($aComparedQuotas);
				}
				
				// mark field QUOTA as changed:
 				if( isset($cloudUsers[$user]['QUOTA']) && !empty($cloudUsers[$user]['QUOTA']) ){
					// append field CHANGED_QUOTA if clouds value changed
					if( $personalQuotaValue!=$cloudUsers[$user]['QUOTA'] && !empty($personalQuotaValue) ) $cloudUsers[$user]['CHANGED_QUOTA'] = $cloudUsers[$user]['QUOTA'];
 				}else{
					// append field CHANGED_QUOTA if quota > 0 and before was no quota
 					if( ( ( $this->settings['update_quota_if_distant_empty']) && !empty($personalQuotaValue) ) ) $cloudUsers[$user]['CHANGED_QUOTA'] = '0 B';
				}
				$cloudUsers[$user]['QUOTA'] = $personalQuotaValue;
		}
		return $cloudUsers;
	}

    /**
     * enrichUpdatableCloudusersWithEditMarker
     *  used by method GetUpdateList() called in exportAction
     *  FIXME: similar to Drg\CloudApi\Utility\JobsEditorUtility->redimGroupJobData()
     *  
     *  prepends formatting text like + or - or -> if content changed
     * 
     * @param array $cloudUsers
     * @param array $changesDB
     * @return void
     */
    public function enrichUpdatableCloudusersWithEditMarker( $cloudUsers , $changesDB ) {
		$aActionIcon = array( 'removeFromGroup' => '&ndash;' , 'appendToGroup' => '+' , 'CHANGED_QUOTA' => ' &rarr; ' );
		foreach( $cloudUsers as $user => $usrRow ){
				if( !isset($changesDB[ $user ]) ) continue;
				foreach($changesDB[ $user ] as $changesName => $changesValue ){
					if( $changesName == 'CHANGED_QUOTA' ){
						$cloudUsers[$user]['QUOTA'] = $cloudUsers[$user]['CHANGED_QUOTA'].$aActionIcon[ 'CHANGED_QUOTA' ].$cloudUsers[$user]['QUOTA'];
					}else{
						if( $changesValue == 'removeFromGroup' ){
							for( $gNr = 1 ; $gNr <= $this->settings['group_amount'] ; ++$gNr ){
								$grpFld = 'grp_' . $gNr;
								if( isset($usrRow[$grpFld]) && !empty($usrRow[$grpFld]) ) continue;
								$usrRow[$grpFld] = $aActionIcon[ 'removeFromGroup' ] . '&nbsp;' . $changesName;
								$cloudUsers[$user][$grpFld] = $usrRow[$grpFld];
								break;
							}
						}else{
							for( $gNr = 1 ; $gNr <= $this->settings['group_amount'] ; ++$gNr ){
								$grpFld = 'grp_' . $gNr;
								
								if( !isset($usrRow[$grpFld]) || $changesName != $usrRow[$grpFld] ) continue;
								$shouldBeInGroup = $usrRow[$grpFld];
								if( isset( $changesDB[$user][$shouldBeInGroup] ) ){
									$cloudUsers[$user][$grpFld] = $aActionIcon[ $changesDB[$user][$shouldBeInGroup] ] . '&nbsp;'  . $cloudUsers[$user][$grpFld];
								}
							}
						}
					}
				}
				unset($cloudUsers[$user]['CHANGED_QUOTA']);
		}
		return $cloudUsers;
	}

    /**
     * getCheckedJobs
     *
     * @return void
     */
    public function getCheckedJobs() {
		$checkedJob = array();
		$jsonDB = $this->readJsonFiles();
		if( isset($jsonDB['chk']) ){
			//if( $this->settings['use_delete_list'] && isset($jsonDB['chk']['chk_UsersMaybeObsoleteJob']) ) unset($jsonDB['chk']['chk_UsersMaybeObsoleteJob']);
			foreach( $jsonDB['chk'] as $shortname => $checklist ){
					if( !is_array($checklist) || !count($checklist) ) continue;
					foreach( $checklist as $ix => $cnt ){
							$checkedJob[ $shortname ][ $ix ] = $cnt;
					}
			}
		}
		// dont select, if name is whitelistet
		if( isset($jsonDB['whitelist']) && ( $this->settings['edit_joblist'] || $this->settings['download_details'] )){
			foreach( $jsonDB['whitelist'] as $shortname => $whitelist ){
					if( !is_array($whitelist) || !count($whitelist) ) continue;
					foreach( $whitelist as $ix => $cnt ){
							if( isset($checkedJob[ $shortname ]) && isset($checkedJob[ $shortname ][ $ix ]) ) unset($checkedJob[ $shortname ][ $ix ]);
					}
			}
		}
		return $checkedJob;
	}

    /**
     * getDifference
     *
     * @return void
     */
    public function getDifference() {
		$db = $this->GetUpdateList();
		for( $outFields = array( 'ID' , 'DISPLAYNAME' , 'EMAIL' , 'QUOTA' ) , $z=1 ; $z <= $this->settings['group_amount'] ; ++$z ) $outFields[] = 'grp_' . $z;
		$outLists = array( 'userlists' => array('updateUserInfo','newUser','deleteUser') , 'grouplists' => array('newGroup','deleteGroup') );
		
		$reportDb = array();
 		foreach( $outLists['userlists'] as $title){
			if( !isset($db[$title]) || !count($db[$title]) ) continue;
			foreach( $db[$title] as $username=>$tab) {
					foreach( $outFields as $fld) $reportDb['userlists'][$title][$username][$fld] = '';
					foreach( $tab as $x=>$actionname) if( in_array( $x , $outFields ) ) $reportDb['userlists'][$title][$username][$x] = $actionname ;
			}
		}
 		foreach( $outLists['grouplists'] as $title){
				if( !isset($db[$title]) || !count($db[$title]) ) continue;
				foreach( array_keys($db[$title]) as $group) {
					foreach( $outFields as $fld) $reportDb['grouplists'][$title][$group][$fld] = '';
					$reportDb['grouplists'][$title][$group]['ID'] = $group;
				}
		}
		$amountUserlist = 0;
		$amountGrouplist = 0;
 		foreach( $outLists['userlists'] as $title) if( isset($reportDb['userlists'][$title]) ) $amountUserlist += count($reportDb['userlists'][$title]);
 		foreach( $outLists['grouplists'] as $title) if( isset($reportDb['grouplists'][$title]) ) $amountGrouplist += count($reportDb['grouplists'][$title]);
		if( !$amountUserlist && !$amountGrouplist ) return false;
		$reportDb['fieldlist'] = $outFields;
		return $reportDb;
	}

}
