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

class TransformTablesUtility extends \Drg\CloudApi\core {

	/**
	 * Property tableSettings
	 *
	 * @var array
	 */
	private $tableSettings = NULL;

	/**
	 * Property tableData
	 *
	 * @var array
	 */
	private $tableData = NULL;

	/**
	 * transformTable_setData
	 * 
	 * @param string $filename 
	 * @return array
	 */
	public function transformTable_setData( $filename ) {
			$short = pathinfo( $filename , PATHINFO_FILENAME );
			$this->tableSettings = isset($this->settings['table_conf'][$short]) ? $this->settings['table_conf'][$short] : $this->settings['table_conf']['default'];
			
			$this->tableFunctions = New \Drg\CloudApi\Utility\TableFunctionsUtility( $this->settings );
			$this->tableData = $this->tableFunctions->csvService->csvFile2array( $filename );
	}
	
	/**
	 * transformTable
	 * used to transform user data
	 *   dont pass quota-group file to this function because 
	 *   it may read the quota-group file by itself 
	 * 
	 * @return array
	 */
	public function transformTable() {
			// read file into array $this->tableData
			//$this->transformTable_setData( $filename );
			$setting = $this->tableSettings;
			if( !is_array($this->tableData) ) return false;
			
			// if there is no mapping return raw data
			if( !isset($setting['mapping']) || !is_array($setting['mapping']) ) return $this->tableData;
			
			// array to fill
			$outRows = array();
			
			// preprocess mapping: transorm name1.name2 to [name1][name2], append value 'FIELD' 
			$fieldDef = $this->getTableMappingAsFields( $setting['mapping'] );
			// if no simple value set but the param 'FIELD' is given, this value is taken as old tables-fieldname
			foreach( $fieldDef as $fieldName => $defRow ){
				if( isset($defRow['FUNCTION']) && empty($defRow['FUNCTION']) ) unset($defRow['FUNCTION']);
				if( isset($defRow['FIELD']) && empty($defRow['FIELD']) ) unset($defRow['FIELD']);
				if( isset($fieldDef[ $fieldName ]['PARAM']['FIELD']) && empty($defRow['PARAM']['FIELD']) ) unset($defRow['PARAM']['FIELD']);
			}
			foreach( $fieldDef as $fieldName => $defRow ){
				if( !isset($defRow['FUNCTION']) ) continue;
				if( isset( $defRow['FIELD'] ) ) {
					$fieldDef[ $fieldName ]['PARAM']['FIELD'] = $defRow['FIELD'];
				}elseif( isset($defRow['PARAM']['FIELDS']) ) {
					$aFieldNames = explode( ',' , $defRow['PARAM']['FIELDS'] );
					if( !empty( $aFieldNames[0] ) ) {
							$fieldDef[ $fieldName ]['PARAM']['FIELD'] = $aFieldNames[0]; // eg ['QUOTA.PARAM.FIELDS'] = 'Klasse'
							$fieldDef[ $fieldName ]['FIELD'] = $aFieldNames[0]; // eg ['QUOTA.PARAM.FIELDS'] = 'Klasse'
					}
				}
			}
			
			$allRows = $this->loopTransformRows( $fieldDef );
			
			$outDB = $this->loopShrinkRows( $allRows );
			// return flat table if no deeper values
			if( !count($outDB) && count($allRows) ) return $allRows;
			// return flatenated table
			return $outDB;
	}

    /**
     * getTableMappingAsFields
     *
     * @return array
     */
    public function getTableMappingAsFields( $tableMapping ) {
			$fieldDef = array();
			for( $n=1 ; $n <= $this->settings['group_amount'] ; ++$n){
					$aAllowedGroupfields['grp_' . $n] = 'grp_' . $n; 
			}
			
			foreach($tableMapping  as $rawFieldname => $optionValue ){
				$aNameParts = explode( '.' , $rawFieldname );
				$fieldName = $aNameParts[0];
				if( substr( $fieldName , 0 , 4 ) == 'grp_' && !isset($aAllowedGroupfields[$fieldName]) ) continue;
				if(count($aNameParts) == 1 ){
					 // if simple value is given, this value is taken as old tables-fieldname
					$fieldDef[ $fieldName ]['FIELD'] = $optionValue;
				}elseif( count($aNameParts) == 2 ){
					$fieldDef[ $fieldName ][ $aNameParts[1] ] = $optionValue; // e.g. QUOTA.FUNCTION = MAX
				}elseif(count($aNameParts) == 3){
					$fieldDef[ $fieldName ][ $aNameParts[1] ][ $aNameParts[2] ] = $optionValue; // e.g. QUOTA.PARAM.FIELDS = grp_1,grp_2,grp_3,grp_4,grp_5
				}elseif(count($aNameParts) == 4){
					$fieldDef[ $fieldName ][ $aNameParts[1] ][ $aNameParts[2] ][ $aNameParts[3] ] = $optionValue; // not used 
				}
				if( !isset($fieldDef[ $fieldName ]['FIELD']) ){$fieldDef[ $fieldName ]['FIELD'] = $optionValue;}
			}
			return $fieldDef;
    }

    /**
     * loopTransformRows
	 * duplicates are possible with different group-fields.
	 * the method appends $ix to each recordset
     *
     * @param array $tableMapping
     * @return array
     */
    public function loopTransformRows( $tableMapping ) {
			$allRows = array();
			foreach( $this->tableData as $ix => $userDataRow) {
				$line = array();
				foreach( $tableMapping as $fieldName => $defRow ){
					// INFO: the following works only, if we dont calculate quota on this place! 
					// if we would calculate quota here, and settings[increase_personal_quota_to_group] == 0 
					// then we would need only the default value instead of calculated from group
					$newFieldValue = '';
					// if function defined render value with function
					if( isset($defRow['FUNCTION']) && !empty($defRow['FUNCTION']) ){
							// run defined method
							$newFieldValue = $this->tableFunctions->callUserMethod( $defRow['FUNCTION'] , $fieldName , $userDataRow , $defRow['PARAM'] );
							// if result of function is empty then try to get default value from param-default
							if( empty($newFieldValue) && isset($defRow['PARAM']['DEFAULT']) ) $newFieldValue = $defRow['PARAM']['DEFAULT'];
					}
					// if $newFieldValue is still empty the get default value from simple definition ['FIELD'] = 'field'
					if( empty($newFieldValue) && isset($defRow['FIELD']) && !empty($defRow['FIELD']) && isset($userDataRow[ $defRow['FIELD'] ])	) {
						$newFieldValue = $userDataRow[ $defRow['FIELD'] ];
					}
					
					$line[$fieldName] = $newFieldValue;
				}
				// end of $fieldNames
				if( !isset($line['ID']) || empty($line['ID']) )$line['ID'] = $ix;
				$allRows[$line['ID']][$ix] = $line;
			}
			return $allRows;
	}

    /**
     * loopShrinkRows
     *
     * @param array $allRows
     * @return array
     */
    public function loopShrinkRows( $allRows ) {
			$outRows = array();
			// shrink duplicates by group-fields
			foreach( $allRows as $ID => $rowsGroup ){
					$firstRow = array_shift( $rowsGroup );
					if( !isset($firstRow['DISPLAYNAME']) ) continue;
					$outRows[$ID] = array(
						'ID' => $firstRow['ID'],
						'DISPLAYNAME' => $firstRow['DISPLAYNAME'],
						'EMAIL' => $firstRow['EMAIL'],
						'QUOTA' => $firstRow['QUOTA'],
					);
					array_unshift( $rowsGroup , $firstRow );
					foreach( $rowsGroup as $ix => $row ){
							for( $grpNr=1 ; $grpNr <= $this->settings['group_amount'] ; ++$grpNr){
								if( !empty( $row['grp_' . $grpNr ] ) ){
										for( $n=1 ; $n <= $this->settings['group_amount'] ; ++$n){
											if( !isset($outRows[$ID]['grp_' . $n ]) ){
												$outRows[$ID]['grp_' . $n ] = $row['grp_' . $grpNr ];
												break;
											}
										}
								}
							}
					}
			}
			return $outRows;
	}

}
