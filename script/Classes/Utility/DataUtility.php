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
 require_once( SCR_DIR . 'Classes/Services/ConnectorService.php' );
 require_once( 'TransformTablesUtility.php' );

/**
 * Class DataUtility
 */

class DataUtility extends \Drg\CloudApi\controllerBase {

	/**
	 * connectorService
	 *
	 * @var \Drg\CloudApi\Services\ConnectorService
	 */
	Public $connectorService = NULL;

	/**
	 * transformTablesUtility
	 *
	 * @var \Drg\CloudApi\Utility\TransformTablesUtility
	 */
	Public $transformTablesUtility = NULL;

	/**
	 * __construct
	 *
	 * @param array $settings optional
	 * @return  void
	 */
	public function __construct( $settings = array() ) {
		parent::__construct( $settings );
		$this->initiate(); 
	}

	/**
	 * initiate
	 *
	 * @return  void
	 */
	public function initiate() {
		$this->connectorService = new \Drg\CloudApi\Services\ConnectorService( $this->settings );
		$this->transformTablesUtility = new \Drg\CloudApi\Utility\TransformTablesUtility( $this->settings );
	}

    /**
     * helper substractFile
     *
     * @param array $fullData
     * @param array $deleteData
     * @param string $keyName
     * @return array
     */
    public function substractFile( $fullData , $deleteData , $keyName = 'ID' ) {
		foreach( $deleteData as $dIx => $deleRow ) {
			if( !isset($deleRow[$keyName]) ) break;
			$toDelete[ $deleRow[$keyName] ] = $deleRow[$keyName];
		}
		foreach( $fullData as $dIx => $fullRow ) {
			if( !isset($fullRow[$keyName]) ) break;
			if( isset($toDelete[ $fullRow[$keyName] ]) ) unset($fullData[$dIx]);
		}
		return $fullData;
	}

    /**
     * helper readSubstractedLocalUsersFiles
     *
     * @param array $aChecklist optional default is empty = all
     * @return array
     */
    public function readSubstractedLocalUsersFiles( $aChecklist =  array() ) {
			$fullData = $this->readLocalUsersFiles( $this->settings['localusers'] , FALSE , $aChecklist ); 
			if( $this->settings['use_delete_list'] != 1 ) return $fullData;
			$deleteData = $this->readLocalUsersFiles( $this->settings['deletefile'] , TRUE );
			$sumDB = $this->substractFile( $fullData , $deleteData , 'ID' );
			return $sumDB;
	}

    /**
     * helper readLocalUsersFiles
     *
     * @param string $pathPattern = 'local/users'
     * @param boolean $preventReadQuotaFromGroup
     * @param array $aChecklist optional default is empty = all
     * @return array
     */
    public function readLocalUsersFiles( $pathPattern = '' , $preventReadQuotaFromGroup = TRUE , $aChecklist =  array() ) {
			$pathPattern = rtrim(  (empty($pathPattern) ? $this->settings['localusers'] : $pathPattern) , '/' );
			$aFilesList = $this->getLocalUsersFileNames( $pathPattern , $aChecklist ); 
// 			echo '<b>dataUtility</b> readLocalUsersFiles ' . $pathPattern.'<br>';
			$db = $this->readAndTransformCsvFiles( $aFilesList , $preventReadQuotaFromGroup);
// 			echo 'dataUtility  readLocalUsersFiles count: ' . count($db) . '<br>';
// 			echo 'readAndTransformCsvFiles( ' . implode(';',$aFilesList).' , ' . $preventReadQuotaFromGroup . ' )<br>';
			return $db;
	}

    /**
     * helper getLocalUsersFileNames
     *
     * @param string $pathPattern = 'local/users'
     * @param array $aChecklist optional default is empty = all
     * @return array
     */
    public function getLocalUsersFileNames( $pathPattern = '' , $aChecklist =  array() ) {
			$pathPattern = rtrim(  (empty($pathPattern) ? $this->settings['localusers'] : $pathPattern) , '/' );
			$aDirs =  $this->fileHandlerService->getDir( $this->settings['dataDir'] . rtrim( $pathPattern , '/' ) , 0 , 1 );
			if( !is_array($aDirs['fil']) ) return array();
			if( !count($aChecklist) ) $aChecklist = $aDirs['fil'];
			$aFilesList = array();
			foreach( array_keys($aDirs['fil']) as $filename ) {
				if( 'csv' != pathinfo( $filename , PATHINFO_EXTENSION ) ) continue;
				if( !isset($aChecklist[$filename]) && !isset($aChecklist[pathinfo( $filename , PATHINFO_BASENAME )] )) continue;
				$aFilesList[$filename]=$filename;
			}
			return $aFilesList;
	}

    /**
     * helper readAndTransformCsvFiles
     *
     * @param array $aFilesList
     * @param boolean $preventReadQuotaFromGroup
     * @return array
     */
    public function readAndTransformCsvFiles( $aFilesList , $preventReadQuotaFromGroup = TRUE ) {
			
			$settings =  $this->settings ;
			if($preventReadQuotaFromGroup) $settings['increase_personal_quota_to_group'] = 0;
			$this->transformTablesUtility->readSettings($settings);
// 			echo 'dataUtility readAndTransformCsvFiles count: ('.count($deleteDb).')<br>';
// 			print_r($aFilesList);
			$sumDB = array();
			$mainfields = array( 'ID' , 'DISPLAYNAME' , 'EMAIL' , 'QUOTA' );
			foreach( $aFilesList as $filename ) {
				$this->transformTablesUtility->transformTable_setData( $filename );
// 				echo '<p>' . $filename .  ' </p>';

				$deciffredTable = $this->transformTablesUtility->transformTable();
// 				echo '<p>dataUtility readAndTransformCsvFiles count: ' . count($deciffredTable) .  '</p>';
				foreach($deciffredTable as $ix => $deciffredRow) {
					$index = isset($deciffredRow['ID']) ? $deciffredRow['ID'] : $ix;
					// maybe a user is in 2 or more tables... dont overwrite existing values on further loops with same index
					if( !isset($sumDB[$index]) ){
							$sumDB[$index] = $deciffredRow;
					}else{  // detect empty fields in existing recordset an fill them
							foreach( $mainfields as $FLD){
									if( isset($deciffredRow[$FLD]) && (!isset($sumDB[$index][$FLD]) || empty($sumDB[$index][$FLD]) ) ) {
											$sumDB[$index][$FLD] = $deciffredRow[$FLD];
									}
							}
							// detect groups to append and insert them in empty group-fields
							for( $n=1 ; $n<=$this->settings['group_amount'] ; ++$n){
								if( isset($deciffredRow['grp_' . $n]) && !empty($deciffredRow['grp_' . $n]) ){
									for( $en=1 ; $en<=$this->settings['group_amount'] ; ++$en){
										if( !isset($sumDB[$index]['grp_' . $en]) || empty($sumDB[$index]['grp_' . $en]) ) {
												$sumDB[$index]['grp_' . $en] = $deciffredRow['grp_' . $n]; 
												break;
										}
									}
								}
							}
					}
					if( !empty($this->settings['orphan_groupname']) && ( !isset($sumDB[$index]['grp_1']) || empty($sumDB[$index]['grp_1']) ) ) $sumDB[$index]['grp_1'] = $this->settings['orphan_groupname'];
				}
			}
			ksort($sumDB); 
			if( !file_exists($this->settings['dataDir'] . 'local/quota/'.$this->settings['table_conf']['group_quota']['force_filename']) || empty($this->settings['use_quota_list']) || $preventReadQuotaFromGroup ) return $sumDB;
			// detect quota by groups
			foreach($sumDB as $ix=>$sumRow){
				$sumDB[$ix]['QUOTA'] = $this->transformTablesUtility->tableFunctions->callUserMethod( 'getQuotaFromGroups' , 'QUOTA' , $sumRow , array('FIELDS'=>'grp_1,grp_2,grp_3,grp_4,grp_5') ); 
			}
			return $sumDB;
	}
    
    /**
     * readFromFile_CloudGroups
     *
     * @return array
     */
    Public function readFromFile_CloudGroups(){
			if( !file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'groups.csv') ) return;
			$rowsGroupAttributes = file( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'groups.csv' );
			$existingGroups = array();
			$sHeadline = array_shift($rowsGroupAttributes);
			foreach( $rowsGroupAttributes as $groupname ){ 
				$cleanGroupName = trim( trim($groupname) , '"');
				$existingGroups[trim($cleanGroupName)] = trim($cleanGroupName); 
			}
			return $existingGroups;
	}
    
    /**
     * writeToFile_CloudGroups
     * used by destructCronJobs (update cloud)
     *
     * @param array $data
     * @return void
     */
    Public function writeToFile_CloudGroups( $data = array() ){
			if(file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'groups.csv')) unlink(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'groups.csv');
			if( !count($data) ) return;
			$z = 0;
			foreach( $data as $grp ) {
				if( $grp == 'group' ) continue;
				++$z;
				$aCsv[$z]['group'] = trim($grp);
			}
			$csvData = $this->csvService->arrayToCsvString(  $aCsv  , $this->settings['sys_csv_delimiter'] , $this->settings['sys_csv_enclosure']  );
			if( empty($csvData) ) return;
			file_put_contents( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'groups.csv' , $csvData );
    }
    
    /**
     * readFromFile_CloudUsersAndAttributes
     *
     * @return array
     */
    Public function readFromFile_CloudUsersAndAttributes(){
			if( !file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv') ) return;
			$rowsUserAttributes = file( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv' );
			$aCloudUserAttributes = array();
			$sHeadline = array_shift($rowsUserAttributes);
			$headline = explode( ';', trim($sHeadline));
			$fieldNumbers = array_flip($headline);
			if( !isset( $fieldNumbers['ID'] ) ) return;
			foreach( $rowsUserAttributes as $row ) {
				$line = explode( ';', trim($row) );
				if( !isset($line[ $fieldNumbers['ID'] ]) ) continue;
				$username = $line[ $fieldNumbers['ID'] ];
				foreach($line as $cellId=>$cell) {
					//if( !empty($cell) ) 
					$aCloudUserAttributes[$username][$headline[$cellId]] = $cell;
				}
			}
			return $aCloudUserAttributes;
    }
    
    /**
     * writeToFile_CloudUsersAndAttributes
     * used by destructCronJobs (update cloud)
     *
     * @param array $data
     * @return void
     */
    Public function writeToFile_CloudUsersAndAttributes( $data = array() ){
			if(file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv')) unlink(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv');
			if( !count($data) ) return;
			$aOutput = array();
			// determine filednames in whole array (not only in first row, that causes lost fields)
			foreach( $data as $ix => $row ) {
				if( !is_array($row) ) continue;
				foreach( array_keys($row) as $fld){ 
					$firstRow[$fld] = $fld;
				}
			}
			
			$aOutput[] = implode( ';' , $firstRow );
			foreach( $data as $ix => $row ) {
				if( !is_array($row) ){
					$aOutput[] = $row;
				}else{
					$outRow = array();
					foreach($firstRow as $fld => $c) {
						if( !isset($row[$fld]) ){
							$outRow[] = '';
						}elseif( is_array($row[$fld]) ){
							$outRow[] = implode( ', ' , $row[$fld] );
						}else{
							$outRow[] = $row[$fld];
						}
					}
					$aOutput[] = implode( ';' , $outRow );
				}
			}
			$csvData = implode( "\n" , $aOutput ) . "\n";
			if( empty($csvData) ) return;
			file_put_contents( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv' , $csvData );
    }

    /**
     * helper readJsonFiles
     *
     * @return array
     */
    public function readJsonFiles() {
			$aDirs =  $this->fileHandlerService->getDir( $this->settings['dataDir'] , 2 );
			$jsonDB = array();
			foreach( array_keys($aDirs['fil']) as $filename ) {
				if( !file_exists( $filename ) ) continue;
				if( 'json' != pathinfo( $filename , PATHINFO_EXTENSION ) ) continue;
				$shortname = pathinfo( $filename , PATHINFO_FILENAME );
				$aSelectionTypes = explode( '_' , $shortname );
				$jsonDB[$aSelectionTypes[0]][$shortname] = json_decode( file_get_contents( $filename ) , true );
			}
			return $jsonDB;
	}
	
}
