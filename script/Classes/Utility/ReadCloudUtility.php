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
 * Class ReadCloudUtility
 */

class ReadCloudUtility extends \Drg\CloudApi\Utility\DataUtility  {
	
    /**
     * getCloudGroupData
     * not used yet, 
     * but somthing similar was implemented in the crashed script
     *
     * @param int $exectime
     * @return array
     */
    Public function getCloudGroupData( $exectime ){
			$aCloudGroupsAndUsers = $this->readCloudGroupsAndUsers( $exectime );
			if( !is_array($aCloudGroupsAndUsers['time']) ) return false;
			$groupTimeElapsedSec = array_pop( $aCloudGroupsAndUsers['time'] );

			$cloudUsers = array();
			$groupPercentage = count($aCloudGroupsAndUsers['userAttributes']) && count($aCloudGroupsAndUsers['users']) ?  round(100 * count($aCloudGroupsAndUsers['userAttributes']) / count($aCloudGroupsAndUsers['users']) , 0 ) : 0;
			
			// FIXME read delete-list
			$groupAmount = $this->settings['group_amount'];
			if( isset($aCloudGroupsAndUsers['userAttributes']) ){
				foreach( $aCloudGroupsAndUsers['userAttributes'] as $groupname => $groupcontent){
					if( !isset( $groupcontent['USERS'] ) ) continue;
					if( !isset( $groupcontent['USERS']['ELEMENT'] ) ) continue;
					if( !is_array( $groupcontent['USERS']['ELEMENT'] ) ) {
						$username = $groupcontent['USERS']['ELEMENT'];
						unset($groupcontent['USERS']['ELEMENT']);
						$groupcontent['USERS']['ELEMENT'][0] = $username;
					}
					foreach($groupcontent['USERS']['ELEMENT'] as $username){
						if(empty($username))continue;
						$cloudUsers[$username]['ID'] = $username;
						$aNames = explode( '.' , str_replace( '_' , '.' , $username) );
						$namesList = array();
						foreach( $aNames as $nampart ) $namesList[] = ucFirst($nampart);
						$cloudUsers[$username]['DISPLAYNAME'] = implode( ' ' , $namesList );
						for( $z=1 ; $z <= $groupAmount ; ++$z ){
							if( isset($cloudUsers[$username]['grp_'.$z]) ) continue;
							$cloudUsers[$username]['grp_'.$z] = $groupname;
							break;
						}
					}
					
				}
			}
			if( $aCloudGroupsAndUsers['fullfilled'] ){
				$csvData = $this->usersArrayToCsv( $cloudUsers );
				if(file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv')) unlink(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv');
				file_put_contents( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv' , $csvData );
			}

			$cloudUsers['status']['elapsedTime'] = $groupTimeElapsedSec;
			$cloudUsers['status']['totalPercentage'] = $groupPercentage;
			$cloudUsers['fullfilled'] = $aCloudGroupsAndUsers['fullfilled'];
			return $cloudUsers;
    }
	
    /**
     * getCloudData
     *
     * @param int $exectime
     * @return array
     */
    Public function getCloudData( $exectime ){
			$aCloudGroupsAndUsers = $this->readCloudGroupsAndUsers( $exectime );
			if( !is_array($aCloudGroupsAndUsers['time']) ) return false;
			$groupTimeElapsedSec = array_pop( $aCloudGroupsAndUsers['time'] );
			
			$aCloudUsersAndAttributes = $this->readCloudUsersAndAttributes( $exectime - $groupTimeElapsedSec );
			$timeElapsedSec = array_pop( $aCloudUsersAndAttributes['time'] ) + $groupTimeElapsedSec;
			
			$grpsProporz = count($aCloudGroupsAndUsers['users']) ? count($aCloudUsersAndAttributes['users']) / count($aCloudGroupsAndUsers['users']) : 1;
			$groupPercentage = count($aCloudGroupsAndUsers['userAttributes']) && count($aCloudGroupsAndUsers['users']) ?  round(100 * count($aCloudGroupsAndUsers['userAttributes']) / count($aCloudGroupsAndUsers['users']) , 0 ) : 0;
			$usersPercentage = count($aCloudUsersAndAttributes['userAttributes']) && count($aCloudUsersAndAttributes['users'])?  round(100 * count($aCloudUsersAndAttributes['userAttributes']) / count($aCloudUsersAndAttributes['users']) , 0 ) : 0;

			$totalPercentage = ( $groupPercentage + ($usersPercentage * $grpsProporz) ) / (1+$grpsProporz);
			$aCloudUsersAndAttributes['status']['elapsedTime'] = $timeElapsedSec;
			$aCloudUsersAndAttributes['status']['totalPercentage'] = $totalPercentage;
			return $aCloudUsersAndAttributes;
	}
    
    /**
     * readCloudGroupsAndUsers
     *
     * @param int $exectime
     * @return array
     */
    Private function readCloudGroupsAndUsers( $exectime ){
		$table = array();
		$runtime = array();
		$runtime[0] = microtime(true); // actual time in seconds
		$groups = $this->connectorService->readCloudDataFromQuery( '/groups' );
		if( is_array($groups) && count($groups) ) {
				$equalizedExistingGroups = $this->equalizeGroupsArray($groups);
				$fullfilled = 0;
				$runtime[1] = microtime(true) - $runtime[0]; // runtime in seconds
				if( is_array($equalizedExistingGroups) && count($equalizedExistingGroups) ){
					foreach($equalizedExistingGroups as $groupName){
							if( empty( $groupName ) ) continue;
							$fullfilled = 0;
							$totalRuntime = $runtime[ count($runtime) -1 ];
							$replName = str_replace( ' ', '%20', $groupName);
							if( $totalRuntime > $exectime ) break;
							$resultat = $this->connectorService->readCloudDataFromQuery( '/groups/'. $replName );
							$table[$groupName] = $resultat ;
							$runtime[count($runtime)] = microtime(true) - $runtime[0]; // total runtime until now in seconds
							$fullfilled = 1;
					}
				}
				// if fullfilled, then write file for comparison with cloudQuotaUtility values
				if( $this->connectorService->apiCalls && $fullfilled && file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/') ) {
					$redimedGroup = $this->redimGroupArray( $table );
					$jsonData = json_encode( $redimedGroup );
					if( !empty($jsonData) ) {
						$this->writeToFile_CloudGroups( $equalizedExistingGroups ); 
						if(file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'groupAttributes.json')) unlink(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'groupAttributes.json');
						file_put_contents( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'groupAttributes.json' , $jsonData );
					}
				}
		}else{ return FALSE;
				$equalizedExistingGroups = array();
				$fullfilled = 0;
				$runtime[1] = microtime(true) - $runtime[0]; // runtime in seconds
		}
		return array( 'users'=>$equalizedExistingGroups , 'userAttributes'=>$table , 'time'=>$runtime , 'fullfilled'=>$fullfilled );
    }
    
    /**
     * readCloudUsersAndAttributes
     *
     * @param int $exectime
     * @return array
     */
    Private function readCloudUsersAndAttributes( $exectime ){
			$runtime[0] = microtime(true); // actual time in seconds

			$users = $this->connectorService->readCloudDataFromQuery( '/users' );
			$runtime[1] = microtime(true) - $runtime[0]; // runtime in seconds

			$fullfilled = 0;
			$table = array();
			$enrichedData = array();
			$userArr = array();
			if( isset($users['USERS']) ){
				$userArr = (!isset($users['USERS']['ELEMENT']) || !is_array($users['USERS']['ELEMENT'])) ? $users['USERS'] : $users['USERS']['ELEMENT'];
				if( is_array($userArr) ){
					foreach($userArr as $usrId){
							$fullfilled = 0;
							$totalRuntime = $runtime[ count($runtime) -1 ];
							if( $totalRuntime > $exectime ) break;
							$fullfilled = 1;

							$table[$usrId] = $this->connectorService->readCloudDataFromQuery( '/users/'.$usrId.''  );
							if( isset( $table[$usrId]['GROUPS']['ELEMENT'] ) && !is_array($table[$usrId]['GROUPS']['ELEMENT']) ) {
								$usersSingleGroup =  $table[$usrId]['GROUPS']['ELEMENT'];
								unset($table[$usrId]['GROUPS']['ELEMENT']);
								$table[$usrId]['GROUPS']['ELEMENT'] = array( $usersSingleGroup );
							}
							$runtime[count($runtime)] = microtime(true) - $runtime[0]; // total runtime in seconds
					}
				}
			}
			
			// if fullfilled, then write file for comparison with cloudQuotaUtility values
			if( $this->connectorService->apiCalls && $fullfilled && file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'groupAttributes.json') ) {
							foreach( $table as $username => $userrow ){
							}
				$jsonData = json_decode( file_get_contents(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'groupAttributes.json') , true );
				$enrichedData = $this->appendGroupsAndIDToUser( $table , $jsonData );
				$csvData = $this->usersArrayToCsv( $enrichedData );
				if( !empty($csvData) ) {
					if(file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv')) unlink(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv');
					file_put_contents( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv' , $csvData );
 		//			unlink(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'groupAttributes.json');// FIXME unused? userAttributes.csv contains same data with more details (on less diskspace)
 					$this->connectorService->apiCalls = 0;
				}
			}else{
				$enrichedData = $table;
			}
			
			return array( 'users'=>$userArr , 'userAttributes'=>$enrichedData , 'time'=>$runtime , 'fullfilled'=>$fullfilled );
    }
    
    /**
     * equalizeGroupsArray
     *
     * @param array $groups
     * @return array
     */
    Public function equalizeGroupsArray( $groups ){
		if( !isset($groups['GROUPS']) ) return;
		return !isset($groups['GROUPS']['ELEMENT']) || !is_array($groups['GROUPS']['ELEMENT']) ? $groups['GROUPS'] : $groups['GROUPS']['ELEMENT'];
    }

	/**
	 * redimClouduserArray
	 * 
	 * @param array $aTable
	 * @return string
	 */
	Public function redimClouduserArray( $aTable ) {
			$aOut = array();
			foreach( $aTable as $username => $usersRow){
					if(!$usersRow) continue;
					foreach( $usersRow as $fieldname => $fieldContent){
							if( is_array( $fieldContent ) ){
								foreach( $fieldContent as $firstIx => $firstSubrow ){
										if( is_array($firstSubrow) ){
											foreach( $firstSubrow as $subIx => $subRow ){
													$aOut[$username][$subIx] = $this->getReducedSizeIfFieldname($subRow , $subIx , 'QUOTA' );
											}
										}else{
											$aOut[$username][$firstIx] =  $this->getReducedSizeIfFieldname( $firstSubrow , $firstIx , 'QUOTA' );
										}
								}
							}else{
								$aOut[$username][$fieldname] = $this->getReducedSizeIfFieldname( $fieldContent , $fieldname , 'QUOTA' );
							}
					}
			}
			return $aOut;
    }

	/**
	 * redimGroupArray
	 * 
	 * @param array $aTable
	 * @return string
	 */
	Private function redimGroupArray( $aTable ) {
			$aOut = array();
			foreach( $aTable as $groupName => $usersRow){
					if(!$usersRow) continue;
					foreach( $usersRow as $possibleContent){
						if( is_array($possibleContent) ){
							foreach( $possibleContent as $secondPossible) {
								if( is_array($secondPossible) ){
									foreach( $secondPossible as $thirdPossible) {
											$aOut[$thirdPossible]['username']=$thirdPossible ;
											$aOut[$thirdPossible]['group'][$groupName] = $groupName;
											}
								}else{
									$aOut[$secondPossible]['username']= $secondPossible;
									$aOut[$secondPossible]['group'][$groupName] = $groupName;
								}
							}
						}else{
							$aOut[$possibleContent]['username']= $possibleContent ;
							$aOut[$possibleContent]['group'][$groupName] = $groupName;
						}
					}
			}
			return $aOut;
    }

	/**
	 * usersArrayToCsv
	 * 
	 * @param array $aTable
	 * @return string
	 */
	Public function usersArrayToCsv( $aTable ) {
			$c = $this->settings['sys_csv_delimiter'];
			$t = $this->settings['sys_csv_enclosure'];
			/// evaluate amount of fields
			// $titlerow = [
			// 'Q' => [ 'FREE'=>'FREE' , 'USED'=>'USED' , 'TOTAL'=>'TOTAL' , 'RELATIVE'=>'RELATIVE' , 'QUOTA'=>'QUOTA' ] ,
			// 'F' => [ 'ENABLED'=>'ENABLED' , 'STORAGELOCATION'=>'STORAGELOCATION' , 'LASTLOGIN'=>'LASTLOGIN' , 'BACKEND'=>'BACKEND' , 'EMAIL'=>'EMAIL' , 'DISPLAYNAME'=>'DISPLAYNAME'  , 'LANGUAGE'=>'LANGUAGE'  , 'LOCALE'=>'LOCALE'  , 'BACKENDCAPABILITIES'=>'BACKENDCAPABILITIES'  ] ,
			// 'G' => []
			// ];
			$titlerow = [];
			foreach( $aTable as $row ){
					if( !is_array($row) ) continue;
					foreach( $row as $fld => $cnt){
						if( $fld == 'QUOTA' ){
							if( is_array($cnt) ){
								foreach( $cnt as $subFld => $subCnt){
									$titlerow['Q'][$subFld] = $subFld;
								}
							}
						}elseif( $fld == 'GROUPS' ){
							if( is_array($cnt['ELEMENT']) ){
								for( $z=1 ; $z<=count($cnt['ELEMENT']) ; ++$z){
									$titlerow['G']['grp_'.$z] = $z;
								}
							}else{
								$titlerow['G']['grp_1'] = 1;
							}
						}elseif( !isset($titlerow['F'][$fld]) ){
							$titlerow['F'][$fld] = $fld;
						}
					}
			}

			$csvOut = !isset($titlerow['F']) || !is_array($titlerow['F']) ? '' : implode( $c , $titlerow['F'] ) . $c;
			if( isset($titlerow['Q']) && is_array($titlerow['Q'])  ) $csvOut .= implode( $c , array_keys($titlerow['Q']) ) . $c;
			if( isset($titlerow['G']) && is_array($titlerow['G']) ) $csvOut .= implode( $c , array_keys($titlerow['G']) ) ;
			$csvOut .= "\n";
			
			$rawArray = array();
			foreach( $aTable as $idx => $row){
				if( isset($titlerow['F']) ){
					foreach( $titlerow['F'] as $fld ) {
						if( isset($row[$fld]) ){
							$rawArray[$idx][$fld] = is_array($row[$fld]) ? implode( ',' , $row[$fld] ) : $row[$fld];
						}else{
							$rawArray[$idx][$fld] = '';
						}
					}
				}
				if( isset($row['QUOTA']) ){
					foreach( $titlerow['Q'] as $subFld ){
						$subCnt = isset($row['QUOTA'][$subFld]) ? $row['QUOTA'][$subFld] : '';
 						$rawArray[$idx][$subFld] = $this->getReducedSizeIfFieldname( $subCnt , $subFld , 'QUOTA,FREE,TOTAL');
					}
				}else{
					if( isset($titlerow['Q']) && is_array($titlerow['Q']) )foreach( $titlerow['Q'] as $subFld => $subCnt ) $rawArray[$idx][$subFld] = '';
				}
				if( isset($row['GROUPS']) ){
					if( is_array($row['GROUPS']['ELEMENT']) ){
						foreach( $row['GROUPS']['ELEMENT'] as $i => $subCnt ) $rawArray[$idx]['grp_'.($i+1)] = $subCnt;
						$iGrp = count($row['GROUPS']['ELEMENT'])+1;
					}else{
						$rawArray[$idx]['grp_1'] = $row['GROUPS']['ELEMENT'];
						$iGrp = 1;
					}
					for( $z = $iGrp+1 ; $z <= count($titlerow['G']) ; ++$z) $rawArray[$idx]['grp_'.$z] = '';
				}else{
					if( isset($titlerow['G']) && is_array($titlerow['G']) ){foreach( $titlerow['G'] as $subFld => $subCnt ) $rawArray[$idx][$subFld] = '';}
				}
				if( isset($rawArray[$idx]) ) $csvOut .= implode( $c , $rawArray[$idx] ) . "\n";
			}
			return $csvOut;
	}


	/**
	 * getReducedSizeIfFieldname
	 * 
	 * @param string $byte
	 * @param string $fieldname
	 * @param string $match eg. QUOTA or [ FREE | USED | TOTAL | RELATIVE ]
	 * @return string
	 */
	Private function getReducedSizeIfFieldname( $byte , $fieldname , $match = '') {
			$aMatches = !empty($match) ? array_flip(explode( ',' , $match )) : array();
			if( ( empty($match) || isset($aMatches[$fieldname]) ) && is_numeric($byte) ){
				return $this->getReducedSize($byte);
			}else{
				return $byte;
			}
	}

	/**
	 * getReducedSize
	 * reduces big integer values to smaller values with text-suffix like MB, KB etc.
	 * 
	 * @param string $byte
	 * @return string
	 */
	Private function getReducedSize( $byte ) {
			$stellen = array( 1073741824=>'GB' , 1048576=>'MB' , 1024=>'KB' , 1=>'B' );
			foreach($stellen as $zahl => $suffix ){
				if( $byte >= $zahl ) return round( $byte / $zahl , 3 ) . ' ' .$suffix;
			}
			return $byte;
	}

	/**
	 * getByteFromReducedSize
	 * transforms string with text-suffix like MB, KB to integer value
	 * returns input on fail
	 * 
	 * @param string $mixedSize
	 * @return string
	 */
	Public function getByteFromReducedSize( $mixedSize ) {
			$stellen = array( 1073741824=>'GB' , 1048576=>'MB' , 1024=>'KB' , 0=>'B' );
			foreach($stellen as $zahl => $suffix ){
				if( strpos( $mixedSize , $suffix ) ) return $zahl * trim( str_replace( $suffix , '' , $mixedSize ) );
			}
			return $mixedSize;
	}
	
	/**
	 * appendGroupsAndIDToUser
	 * since Nextcloud 11.05 the ID is given
	 * before we need it from calling-query 
	 * so we use the user-id (username) as table index
	 * 
	 * @param array $aTable
	 * @param array $aGroups
	 * @return string
	 */
	Private function appendGroupsAndIDToUser( $aTable , $aGroups ) {
			$oTable = array();
			foreach( $aTable as $userIx => $row){
					if( !isset($row['ID']) ) $row['ID'] = $userIx;
					$username =  $row['ID'];
					$gIx = 1;
					$oTable[$username] = $row;
					if( !empty($this->settings['orphan_groupname']) && !isset($aGroups[$username]['group']) ) { // allow download of users with no group mebership - to delete them
							$oTable[$username]['GROUPS']['ELEMENT'][0] = $this->settings['orphan_groupname'];
							continue;
					}
					if(is_array($aGroups[$username]['group'])){
							foreach( $aGroups[$username]['group'] as $grp){
								$idOfExisting = array_search( $grp , $oTable[$username]['GROUPS']['ELEMENT'] );
								if( $oTable[$username]['GROUPS']['ELEMENT'][$idOfExisting] == $grp ) continue;
								$oTable[$username]['GROUPS']['ELEMENT'][$gIx] = $grp;
								++$gIx;
							}
					}
			}
			return $oTable;
    }
   
}
