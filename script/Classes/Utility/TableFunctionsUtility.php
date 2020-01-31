<?php
namespace Drg\CloudApi\Utility;

/**
 * TableFunctionsUtility
 *  called by TransformTablesUtility
 *  instantiates CsvService
 *  
 *  first the script looks up for methods starting with user_ (eg. user_gibWert) 
 *  if there is no such method, it will look for the method without prefix (eg. gibWert)
 *  otherwise it looks for a method with the prefix sys_ (eg sys_gibWert)
 * 
 */

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
 
	$scriptname = 'Classes/Core/tablefunctionsBase.php';
	if( !file_exists(SCR_DIR . $scriptname) ) die( 'boot.php #17: datei ' . SCR_DIR . $scriptname . ' nicht vorhanden!' );
	require_once( SCR_DIR . $scriptname );

class TableFunctionsUtility extends \Drg\CloudApi\tablefunctionsBase  {

	/**
	 * Property afforedFields
	 *
	 * @var array
	 */
	Public $afforedFields = array(
		'EXTRACT' => 'FIELDS,CHAR,INDEX',
		'CONCAT' => 'FIELDS,CHAR',
		'VALUE' => 'CHAR',
		'APPEND' => 'CHAR',
		'gibWert' => 'FIELDS',
	);
	
	/**
	 * sys_VALUE
	 * inserts a given value from field 'CHAR'
	 *
	 * @param string $fieldName in this function unused but affored because it is a user-function
	 * @param array  $aUserDataRow
	 * @param array  $aParameters 
	 * @return string
	 */
	Protected function sys_VALUE( $fieldName , $aUserDataRow , $aParameters = array() ) {
			if( !isset($aParameters['FIELD']) ){
					if(isset($aUserDataRow[$fieldName])) {
							$aParameters['FIELD'] = $fieldName;
					}elseif( isset($aParameters['FIELDS']) && isset($aUserDataRow[$aParameters['FIELDS']]) ){
							$aParameters['FIELD'] = $aParameters['FIELDS'];
					}
			}
			$char = isset($aParameters['CHAR']) ? $aParameters['CHAR'] : '';
			$personalValue = isset($aParameters['FIELD']) && isset($aUserDataRow[$aParameters['FIELD']]) ? $aUserDataRow[$aParameters['FIELD']] : '';
 			if( !empty($personalValue) && empty($this->settings['increase_personal_quota_to_group']) ) return $personalValue;
			if( $this->string2byte($personalValue) > $this->string2byte($char) ) return $personalValue;
			return $char;
	}
	
	/**
	 * sys_EXTRACT
	 *
	 * @param string $fieldName
	 * @param array  $aUserDataRow
	 * @param array  $aParameters optional
	 * @return string
	 */
	Protected function sys_EXTRACT( $fieldName , $aUserDataRow , $aParameters = array() ) {
			if( !isset($aParameters['FIELD']) && isset($aUserDataRow[$fieldName]) ) $aParameters['FIELD'] = $fieldName;
			if( isset($aParameters['FIELD']) && !empty($aUserDataRow[$aParameters['FIELD']]) ) return $aUserDataRow[$aParameters['FIELD']];;
			if( !isset($aParameters['CHAR']) || empty($aParameters['CHAR']) ) $aParameters['CHAR'] = ' ';
			if( !isset($aParameters['INDEX']) || empty($aParameters['INDEX']) ) $aParameters['INDEX'] = 0;
			if( !isset($aParameters['FIELDS']) || !isset($aUserDataRow[$aParameters['FIELDS']]) ){
					if(isset($aUserDataRow[$fieldName])) {
							$aParameters['FIELDS'] = $fieldName;
					}elseif( isset($aUserDataRow[$aParameters['FIELD']]) ){
							$aParameters['FIELDS'] = $aParameters['FIELD'];
					}else{
							return false;
					}
			}
			$atomsFromUsername = explode( $aParameters['CHAR'] , $aUserDataRow[$aParameters['FIELDS']] );
			$newFieldValue = $atomsFromUsername[$aParameters['INDEX']];
			return $newFieldValue;
	}

	/**
	 * sys_APPEND
	 *
	 * @param string $fieldName in this function unused but affored because it is a user-function
	 * @param array  $aUserDataRow
	 * @param array  $aParameters optional
	 * @return string
	 */
	Protected function sys_APPEND( $fieldName , $aUserDataRow , $aParameters = array() ) {
			if( !isset($aParameters['FIELD']) && isset($aUserDataRow[$fieldName]) ){
					$aParameters['FIELD'] = $fieldName;
			}
			$char = isset($aParameters['CHAR']) ? $aParameters['CHAR'] : '';
			$personalValue = isset($aParameters['FIELD']) && isset($aUserDataRow[$aParameters['FIELD']]) ? $aUserDataRow[$aParameters['FIELD']] : '';
			$newFieldValue = $personalValue . $char ;
			return $newFieldValue;
	}

	/**
	 * sys_CONCAT
	 *
	 * @param string $fieldName in this function unused but affored because it is a user-function
	 * @param array  $aUserDataRow
	 * @param array  $aParameters optional
	 * @return string
	 */
	Protected function sys_CONCAT( $fieldName , $aUserDataRow , $aParameters = array() ) {
			if( !isset($aParameters['FIELD']) && isset($aUserDataRow[$fieldName]) ) $aParameters['FIELD'] = $fieldName;
			if( isset($aParameters['FIELD']) && !empty($aUserDataRow[$aParameters['FIELD']]) ) return $aUserDataRow[$aParameters['FIELD']];;
			if( !isset($aParameters['CHAR']) ) $aParameters['CHAR'] = '';
			if( empty($aParameters['CHAR']) ) $aParameters['CHAR'] = ' ';
			if( !isset($aParameters['FIELDS']) ) return false;
			$aFieldNames = explode( ',' , $aParameters['FIELDS'] );
			$aNamePart = array();
			foreach($aFieldNames as $field){
				if( !isset($aUserDataRow[$field]) ) continue;
				if( empty($aUserDataRow[$field]) ) continue; 
				$aNamePart[] = $aUserDataRow[$field] ;
			}
			$newFieldValue = count($aNamePart) ? implode( $aParameters['CHAR'] , $aNamePart ) : '';
			return $newFieldValue;
	}

	/**
	 * user_gibWert
	 * dies ist eine Beispiel-Funktion fuer User-Funktionen
	 * die User-Funktionen werden von der Klasse TransformTablesUtility aus aufgerufen 
	 * 
	 * diese Funktion ist ungenutzt, sie gibt die hoechste Quote der in Spalten grp_1 bis grp_5 eingetragenen Gruppen wieder
	 * sie kann folgendermassen in table_def verwendet werden: 
	 * $file_settings['Lehrpersonen']['mapping']['QUOTA.FUNCTION'] = 'userFunct_gibWert';
	 * $file_settings['Lehrpersonen']['mapping']['QUOTA'] = '0 B'; // DEFAULT-wert
	 *
	 * @param string $fieldName not used in this method
	 * @param array  $aUserDataRow 
	 * @param array  $aParameters optional
	 * @return string
	 
	Protected function user_gibWert( $fieldName , $aUserDataRow , $aParameters = array() ) {
 			$aParameters['FIELD'] = 'quota';
			$aParameters['FIELDS'] = 'grp_1,grp_2,grp_3,grp_4,grp_5';
			$aParameters['VALUES_FUNCTION'] = 'group2quota';
			$aParameters['SORTVALUES_FUNCTION'] = 'string2byte';
			$aPossibleValues = $this->getPossibleValuesFromFieldlist( 'QUOTA' , $aUserDataRow , $aParameters );
			
			$newFieldValue = array_pop($aPossibleValues);
			
			return $newFieldValue;
	}
	*/
	
	/**
	 * minimalQuotaFromGroups
	 * FIXME UNUSED
	 *
	 * @param string $fieldName e.g. QUOTA
	 * @param array  $aUserDataRow
	 * @param array  $aParameters optional
	 * @return string
	 */
// 	Protected function minimalQuotaFromGroups( $fieldName , $aUserDataRow , $aParameters = array() ) {
//  			$personalQuota = isset($aUserDataRow[$fieldName]) ? $aUserDataRow[$fieldName] : '';
//  			$groupList =  isset( $aParameters['FIELDS'] ) ? $aParameters['FIELDS'] : 'grp_1,grp_2,grp_3,grp_4,grp_5';
// 			$aPossibleValues = $this->getPossibleValuesFromFieldlist( $personalQuota , $groupList , $aUserDataRow , $this->settings['increase_personal_quota_to_group']); 
// 			$newFieldValue = array_shift($aPossibleValues);
// 			
// 			return $newFieldValue;
// 	}
	
	

}
