<?php
namespace Drg\CloudApi;

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
 * tablefunctionsBase
 *  needs CsvService
 *  extended by TableFunctionsUtility
 *  - TableFunctionsUtility used by TransformTablesUtility
 *  
 * 
 */

class tablefunctionsBase extends \Drg\CloudApi\core  {

	/**
	 * Property groupQuta
	 * array-part 'pattern' contains values like 'KLS*'
	 * array-part 'data' is filled with values like 'KLS17 A'
	 *
	 * @var array
	 */
	Public $groupQuta = array('pattern'=>array(),'data'=>array());

	/**
	 * Property reflectedMethods
	 *
	 * @var array
	 */
	Private $reflectedMethods = array();

	/**
	 * Property afforedFields
	 *
	 * @var array
	 */
	Public $afforedFields = array(
		'getQuotaFromGroups' => 'FIELDS',
	);
	
	/**
	 * __construct
	 *
	 * @param array $settings optional
	 * @return  void
	 */
	Public function __construct( $settings = array() ) {
		// initiating class, start parent constructor first
		parent::__construct( $settings );
		
		// instantiate csvService
		$this->csvService = new \Drg\CloudApi\Services\CsvService($this->settings);
		
		if( !is_array($this->settings['table_conf']['group_quota']) ) return false;
		
		$pathTo_quota_csv = $this->settings['dataDir'] . $this->settings['table_conf']['group_quota']['location']  . $this->settings['table_conf']['group_quota']['force_filename'] ;
		if( !file_exists( $pathTo_quota_csv ) ) return false;
		
		// reads quota-table with parameters from settings['table_conf']['group_quota']
		// stores quota-table in the array $this->groupQuta['pattern']
		$this->groupQuta['pattern'] = $this->initiate_readPatternTable( $pathTo_quota_csv , $this->settings['table_conf']['group_quota'] );
		
	}
	
	/**
	 * initiate_readPatternTable
	 * used in extension of this class: TableFunctionsUtility.php
	 * 
	 * @param string $pathTo_quota_csv 
	 * @param array $quotaTabConf 
	 * @return array
	 */
	Protected function initiate_readPatternTable( $pathTo_quota_csv , $quotaTabConf ) {
		// read table with quota and corresponding groups with search-pattern
		$tempPattern = $this->csvService->csvFile2array( $pathTo_quota_csv  );
		if( !count($tempPattern) ) return false;
		
		// perhaps the table contains other than original fieldnames 'gruppe' and 'quota'
		$changeRowName = array();
		if( !isset($quotaTabConf['mapping']) ) return $tempPattern;
		
		// detect if a row in table from file 'group_quota.csv' changed the name eg QUOTA => quota 
		foreach( $quotaTabConf['mapping'] as $sysRowname => $filRowname ){
			if( $sysRowname != $filRowname ) $changeRowName[$filRowname] = $sysRowname;
		}
		if( !count( $changeRowName ) ) return $tempPattern;
		
		foreach( $tempPattern as $ix => $row ){
			foreach( $row as $fieldname => $cell ) {
				// if $fieldname is exactly 'gruppe' or 'quota' dont do nothing
				if( !isset($changeRowName[$fieldname]) ) continue; // row is not mapped, leave it as it is.
				$sysRowname = $changeRowName[$fieldname] ;
				$tempPattern[$ix][$sysRowname] = $cell;
			}
		}
		return $tempPattern;
	}


	/**
	 * getQuotaFromGroupWithSearchpattern
	 * expects a string with Group/Schoolclass-Name e.g. 'Kls17 A'
	 * compares it with the table from group_quota.csv wich contains value-pairs like Kl* = '31 GB'
	 * stores found results in the array $this->groupQuta['data'] for further search actions
	 * returns a string with Quota-Value e.g. '31 GB'
	 * 
	 * @param string $fullValue string with Group/Schoolclass-Name
	 * @return string
	 */
	Public function getQuotaFromGroupWithSearchpattern( $fullValue ) {
			// if data has already determined, return value from array
			if( isset($this->groupQuta['data'][$fullValue]) && !empty($this->groupQuta['data'][$fullValue]) ) return $this->groupQuta['data'][$fullValue];
			
			// determine data
			foreach( $this->groupQuta['pattern'] as $directive ){ 
 				if( !isset($directive['ID']) || !isset($directive['QUOTA']) ) continue;;
				$pattern = $directive['ID'];
				if( $pattern == $this->settings['table_conf']['group_quota']['fallback_option_name'] ) $this->groupQuta['data']['default'] = $directive['QUOTA'];
				$posAsterisk = strpos( ' ' . $pattern , '*' );
				if( $posAsterisk ){
					// there is an * on start, in middle or end
					$cleanPat = trim( $pattern , '*' );
					$strLenMinusPatLen = strlen($fullValue) - strlen($cleanPat)+1 ;
					if( $posAsterisk == 1 ){ // * is on start *DEF
						if( strpos( ' ' . $fullValue , $cleanPat ) == $strLenMinusPatLen ) $this->groupQuta['data'][$fullValue] = $directive['QUOTA'];
					}elseif( $posAsterisk == strlen($pattern) ){ // * is on  end ABC*
						if( strpos( ' ' . $fullValue , $cleanPat ) == 1 ) $this->groupQuta['data'][$fullValue] = $directive['QUOTA'];
					}else{ // * is in middle
						$aPats = explode( '*' , $pattern );
						if(
							strpos( ' ' . $fullValue , $aPats[0] ) == 1 &&
							strpos( ' ' . $fullValue , $aPats[1] ) == strlen($fullValue) - strlen($aPats[1]) +1 
						) {$this->groupQuta['data'][$fullValue] = $directive['QUOTA'];}
					}
				}else{
					// entire string has to match
					if( $fullValue == $directive['ID'] ) {
						$this->groupQuta['data'][$fullValue] = $directive['QUOTA'];
					}
				}
			}
			
			// if now data has determined, return value from array
			if( isset($this->groupQuta['data'][$fullValue]) && !empty($this->groupQuta['data'][$fullValue]) ) return $this->groupQuta['data'][$fullValue];
			
			// if no data return empty string
			return isset($this->groupQuta['data']['default']) ? $this->groupQuta['data']['default']:'';//$fullValue;
	}

	/**
	 * getMethodsList
	 * 
	 * @param mixed $class_name mandatory
	 * @return array
	 */
	public function getMethodsList( $class_name = '' ){
			$methodsList =  get_class_methods( empty($class_name) ? $this : $class_name );
			$aAfforedKeys = array('fieldName','aUserDataRow','aParameters');
			$existingMethod = array();
			$sr = array( 'user_'=>'' , 'sys_'=>'' );
			foreach($methodsList as $functName ){
						if( ' __construct' == $functName ) continue;
						$classMethod = new \ReflectionMethod( empty($class_name) ? $this : $class_name , $functName );
						$oClassParam = $classMethod->getParameters();
						foreach( $oClassParam as $ix => $aClassParam) {
							foreach( $aClassParam as $nam ) {
								$existingMethod[str_replace(array_keys($sr),$sr,$functName)][$nam]=str_replace(array_keys($sr),$sr,$functName);
							}
						}
						
			}
			foreach( $existingMethod as $functName => $aClassParam) {
					foreach( $aAfforedKeys as $nam ) {
						if( !isset($aClassParam[$nam]) ) unset($existingMethod[$functName]);
					}
			}
		return array_keys($existingMethod);
	}

	/**
	 * callUserMethod
	 * 
	 * @param string $functionName mandatory
	 * @param string $strArgument mandatory
	 * @param array $arrArgument1 optional
	 * @param array $arrArgument2 optional
	 * @return string
	 */
	public function callUserMethod( $functionName , $strArgument , $arrArgument1 = array() , $arrArgument2 = array() ){
			if( empty($functionName) ) return false;
			// methods with names that start different than user_ or sys_ have only one parameter
			
			$aPrefixes = array( 'user_' , '' , 'sys_' );
			foreach($aPrefixes as $prefix ){
				$functName = trim( $prefix . $functionName );
				if( 
					method_exists( $this , $functName ) 
				){
					if( !isset($this->reflectedMethods[$functName]) ){
						$classMethod = new \ReflectionMethod( $this , $functName );
						$aClassParam = $classMethod->getParameters();
						$this->reflectedMethods[$functName] = count($aClassParam);
					}
					if( $this->reflectedMethods[$functName] == 1 ) return $this->$functName( $strArgument  );
					if( $this->reflectedMethods[$functName] == 2 ) return $this->$functName( $strArgument , $arrArgument1  );
					return $this->$functName( $strArgument , $arrArgument1 , $arrArgument2 );
				}
			}
			return false;
	}
	
	/**
	 * getPossibleValuesFromFieldlist
	 * returns array with values from given fieldlist
	 *
	 * @param string $personalQuota
	 * @param string $groupList comma separed list with groups eg. grp_1,grp_2
	 * @param array  $aUserDataRow
	 * @param boolean $increase_personal_quota_to_group
	 * @return array
	 */
	Public function getPossibleValuesFromFieldlist( $personalQuota , $groupList , $aUserDataRow , $increase_personal_quota_to_group = true ) {
			$aPossibleValues = array();
			
			if( !empty($personalQuota) ){
				$determinedFieldName = $this->string2byte( $personalQuota );
				$aPossibleValues[$determinedFieldName] = $personalQuota;
				if($increase_personal_quota_to_group == false) return $aPossibleValues;
			}
			
			$aGroupNames = explode( ',' , $groupList );
			foreach( $aGroupNames as $groupname ){
					if( !isset($aUserDataRow[$groupname]) || empty($aUserDataRow[$groupname]) ) continue;
					$determinedFieldValue = $this->getQuotaFromGroupWithSearchpattern($aUserDataRow[$groupname]);
					$determinedFieldName = $this->string2byte($determinedFieldValue);
					$aPossibleValues[$determinedFieldName] = $determinedFieldValue;
			}
			ksort($aPossibleValues);
			return $aPossibleValues;
	}

	/**
	 * string2byte
	 * expects a text formatted string like '30 GB' or '10 MB'
	 * returns the integer value converted in bytes like 32212254720 or 10485760
	 * or the incoming string
	 * 
	 * @param string $mixedSize
	 * @return string
	 */
	Public function string2byte( $mixedSize ) {
			if( empty($mixedSize) ) return '';
			$stellen = array( 1073741824=>'GB' , 1048576=>'MB' , 1024=>'KB' , 0=>'B' );
			foreach($stellen as $zahl => $suffix ){
				if( strpos( $mixedSize , $suffix ) ) return $zahl * trim( str_replace( $suffix , '' , $mixedSize ) );
			}
			// fallback
			return $mixedSize;
	}

	/**
	 * byte2string
	 * expects a integer value in bytes like 32212254720 or 10485760
	 * returns the converted text formatted string like '30 GB' or '10 MB'
	 * or the incoming string
	 * 
	 * @param string $integer
	 * @param string $dec optional
	 * @return string
	 */
	Public function byte2string( $integer , $dec=1 ) {
			if( empty($integer) ) return '';
			$stellen = array( 1073741824=>'GB' , 1048576=>'MB' , 1024=>'KB' , 0=>'B' );
			foreach($stellen as $zahl => $suffix ){
				if( $integer >= $zahl ) return round( $integer/$zahl , $dec ) . ' ' . $suffix;
			}
			// fallback
			return $integer;
	}
	
	/**
	 * getQuotaFromGroups
	 * called by \Drg\CloudApi\Utility\DataUtility->readAndTransformCsvFiles() in DataUtility.php on line 175
	 * 
	 * to exclude this method from user-mesthods
	 * the param fieldName is written with uppercase FieldName
	 *
	 * @param string $FieldName e.g. QUOTA
	 * @param array  $aUserDataRow
	 * @param array  $aParameters optional
	 * @return string
	 */
	Protected function getQuotaFromGroups( $FieldName , $aUserDataRow , $aParameters = array() ) {
 			$personalQuota = isset($aUserDataRow[$FieldName]) ? $aUserDataRow[$FieldName] : '';
 			$groupList =  isset( $aParameters['FIELDS'] ) ? $aParameters['FIELDS'] : 'grp_1,grp_2,grp_3,grp_4,grp_5';
			$aPossibleValues = $this->getPossibleValuesFromFieldlist( $personalQuota , $groupList , $aUserDataRow , $this->settings['increase_personal_quota_to_group']); 
			$newFieldValue = array_pop($aPossibleValues);
			
			return $newFieldValue;
	}

}
