<?php
namespace Drg\CloudApi;
if (!class_exists('Drg\CloudApi\core', false)) die( 'Die Datei "'.__FILE__.'" muss von Klasse "core" aus aufgerufen werden.' );

/**
*
* Class ModelService
* Provides a database abstraction library 
* for file-based, not-relational database 
* 
* The database abstraction layer
*  may be done by ModelViewHelper 
*  or by own Controller [tablename]Controller.php
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
	
	$script = 'Classes/Core/core.php';
	if( !file_exists(SCR_DIR . $script) ) die( 'modelBase.php #42: datei ' . SCR_DIR . $script . ' nicht vorhanden!' );
	require_once( SCR_DIR . $script );

class modelBase extends core {

	/**
	 * Property tablename
	 *
	 * @var string
	 */
	Public $tablename = 'auth_users';

	/**
	 * Property store_global
	 *
	 * @var boolean
	 */
	Public $store_global = FALSE; // 'false' is default. May be overwritten by settings['store_global.' . $this->tablename] in __construct()

	/**
	 * Property indexfield
	 *
	 * @var string
	 */
	Public $indexfield = 'user';

	/**
	 * Property properties
	 *
	 * @var array
	 */
	Public $properties = array(
		'user' 	=>	array(
			'type' => 'string',
			'locked' => '1'
		),
		'pass' 	=>	array(
			'type' => 'pass1way'
		)
	);


	/**
	 * Property lockedRecordsets
	 * used to prevent users from deleting themself
	 *
	 * @var array
	 */
	Protected $lockedRecordsets = array();

	/**
	 * Property lastNewIndex
	 *
	 * @var string
	 */
	public $lastNewIndex = '';

	/**
	 * Property pointer
	 *
	 * @var string
	 */
	Protected $pointer = '';

	/**
	 * Property table_filepath
	 *
	 * @var string
	 */
	Protected $table_filepath = '';

	/**
	 * Property recordsets
	 *
	 * @var array
	 */
	Protected $recordsets = array();

	/**
	 * __construct
	 *
	 * @param array $settings optional
	 * @return  void
	 */
	Public function __construct( $settings = array() ) {
		parent::__construct( $settings );
		if( count($settings) ) $this->settings = $settings;
		if( !isset($this->settings['scrDir']) && defined(SCR_DIR) ) $this->settings['scrDir'] = SCR_DIR;
		if( !isset($this->settings['scrDir']) ) $this->settings['scrDir'] = dirname(dirname(dirname(__FILE__))) . '/' ;
		$this->setTableFilepath( isset($settings['store_global.' . $this->tablename ]) ? $settings['store_global.' . $this->tablename ] : $this->store_global );
		
		$this->startCryptService();
	}

	/**
	 * setTableFilepath
	 *
	 * @param boolean $store_global optional, default = false
	 * @return  void
	 */
	Public function setTableFilepath( $store_global = false ) {
		if( $this->store_global != $store_global ) $this->store_global = $store_global;
		$this->table_filepath = ( $store_global ? DATA_DIR : $this->settings['dataDir'] )  . $this->tablename . '.json' ;
	}

	/**
	 * initiate
	 *
	 * @param array $lockRecordsets optional
	 * @return  void
	 */
	Public function initiate( $lockRecordsets = array() ) {
		$this->loadFile();
		if( $lockRecordsets && count($lockRecordsets) ) foreach( $lockRecordsets as $index => $onlyLockFields ) $this->lockRecord($index,$onlyLockFields);
		return $this->setRequestRecords();
	}

	/**
	 * persist the array recordsets
	 * writes to file and encode fields if afored
	 *
	 * @param array $recordsets optional
	 * @return  void
	 */
	Protected function persist( $recordsets = array() ){
		if( count($recordsets) ) $this->recordsets = $recordsets;
		if( !count( $this->recordsets ) ) {
			if( file_exists($this->table_filepath) ) unlink($this->table_filepath);
			return $this->rapport( 0 , '##LL:notthing_to## ##LL:persist##, ##LL:delete## ##LL:file## "'.basename($this->table_filepath).'". ' );
		}
		if(  !is_dir(dirname($this->table_filepath)) &&  is_dir(dirname(dirname($this->table_filepath))) ){
				mkdir( dirname($this->table_filepath) , 0777 , true );
		}
		if( basename(trim($this->table_filepath,'/')) != trim($this->table_filepath,'/') && is_dir(dirname($this->table_filepath)) ) {
			if( !file_exists(dirname($this->table_filepath)) || !is_writable(dirname($this->table_filepath)) ) return $this->rapport( 0 , 'not writable: "'.dirname($this->table_filepath).'". ' );
			$this->fileHandlerService->writeCompressedFile( $this->table_filepath , $this->recordsets );
		}
		if( !file_exists($this->table_filepath) ) return $this->rapport( 0 , count( $this->recordsets ) . ' records NOT saved: isDir '.dirname($this->table_filepath).'?('.(is_dir(dirname($this->table_filepath))).') && equal?('.(basename(trim($this->table_filepath,'/')) != trim($this->table_filepath,'/')).')'  , 'persist' );
		return $this->rapport( file_exists($this->table_filepath) , count( $this->recordsets ) . ' records '.( file_exists($this->table_filepath) ? 'saved' : '<b>not</b> saved' ).' to ' . $this->table_filepath , 'persist' );
	}

	/**
	 * getLastIndex
	 *
	 * @param string $fieldname optional deafault is indexfield
	 * @return  string
	 */
	Public function getLastIndex( $fieldname = '' ){
		if( empty($fieldname) ) $fieldname = $this->indexfield;
		$lastUid = array(0);
		foreach( $this->recordsets as $index => $row ) {
			$lastUid[$index] = $row[ $fieldname ];
		}
		return max($lastUid);
	}

	/**
	 * getRecordsets all from array recordsets
	 *
	 * @param string $fieldname optional deafault is 'sort' if exists, else indexfield
	 * @param boolean $ascendent optional deafault is true (ASC)
	 * @return  boolean
	 */
	Public function sortRecordsets( $fieldname = 'sort' , $ascendent = TRUE ){
			if( empty($fieldname) || !isset($this->properties[$fieldname]) ) $fieldname = $this->indexfield ;
			if( !isset($this->properties[$fieldname]) ) return false; // error, should not happen
			$sortRs = array();
			$outRecordsets = array();
			ksort($this->recordsets);
			foreach( $this->recordsets as $index => $row ) {
				if( isset( $row[$fieldname] ) ) $sortRs[ $row[$fieldname] ][ $index ] = $row;
			}
			if( $ascendent ){ ksort($sortRs); }else{ krsort($sortRs); }
			foreach( $sortRs as $sortFieldContent => $grpRow ) {
				foreach( $grpRow as $index => $row ) $outRecordsets[ $index ] = $row;
			}
			if( count($this->recordsets) === count($outRecordsets) ) $this->recordsets = $outRecordsets;
			return true;
	}

	/**
	 * filterRecordsets
	 *
	 * @param string $aFilter
	 * @return  boolean
	 */
	Public function filterRecordsets( $aFilter ){
			if( !is_array( $aFilter ) || !count( $aFilter ) )  return false;
			
			$constrain = 'OR';
			if( isset( $aFilter['constrain'] ) ){
				$constrain = $aFilter['constrain'];
				unset($aFilter['constrain']);
			}
			
			$iFilters = $constrain == 'AND' ? count( $aFilter ) : 1;
			
			$outRecordsets = array();
			$s = array( '>' => '' , '<' => '' , '=' => '' );
			foreach( $this->recordsets as $index => $row ){
					$iResults = 0;
					foreach( $aFilter as $field => $condition ){
						if( !isset($row[ $field ]) ||  '' == $row[ $field ] ) continue; // new empty recordset
						$condValue = str_replace( array_keys($s) , $s , $condition );
						if( '' == $condValue ) continue; 
						$enclosedCondition = str_replace( $condValue , '"'.$condValue.'"' , $condition );
						eval( '$result = "'. $row[ $field ] . '"' . $enclosedCondition . ';' );
						$iResults += $result ? 1 : 0 ;
					}
					if( $iResults >= $iFilters ){
						$outRecordsets[$index] = $row;
					}
			}
			$this->recordsets = $outRecordsets;
			return true;
	}

	/**
	 * getRecordsets all from array recordsets
	 *
	 * @param array $aFilter optional field => '==10' or '>=10'
	 * @param boolean $noDecoding optional, default is false, so passwords will be displayed in cleartext. never used.
	 * @return  array
	 */
	Public function getRecordsets( $aFilter = array() , $noDecoding = false ){
		if( !count($this->recordsets) ) $this->loadFile();
		if( !count($this->recordsets) ) return array();
		
		$this->sortRecordsets();
		
		$this->filterRecordsets($aFilter);
		
		if( $noDecoding ) return $this->recordsets;
		
		$outRecordsets = array();
		foreach( array_keys( $this->recordsets ) as $index ) $outRecordsets[$index] = $this->getRecordset( $index );
		return $outRecordsets;
	}


	/**
	 * setRecordset into array recordsets
	 *
	 * @param array $namVal incoming array from request with name-value pairs
	 * @param string $index
	 * @param boolean $noEncoding optional, default is false
	 * @return  boolean
	 */
	public function setRecordset( $namVal , $index , $noEncoding = false ){
 		// create new recordset if the variable $namVal[ $this->indexfield ] is set - the UID is not editable, so it has to be a new rs
 		if( isset($namVal[ $this->indexfield ]) ){ // usually we dont send the indexfield over namVal because namVal changes values
			
			// tried to add new recordset without any index 
			if( empty($namVal[ $this->indexfield ]) ) return $this->rapport( 0 , '##LL:index_empty##!' , '##LL:not_created##' );
			
			// tried to add new recordset with index that already exists
			if( isset( $this->recordsets[ $namVal[ $this->indexfield ] ])) return $this->rapport( 0 ,'##LL:index_exists##! ' , '##LL:not_created## ');
			
			// create new recordset: change index from 'new'-fieldname to index-fieldcontent
			$index = $namVal[ $this->indexfield ];
 		}
		if( empty($index) ) return false;
		
 		// create new recorset is done in method setPointer() when $this->recordsets[$index] is not set
 		$this->setPointer( $index );
 		
 		// update recordset if affored
 		$results = 0;
 		foreach( $namVal as $propName => $propVal )  $results += $this->setProp( $propName , $propVal , $noEncoding );
		
		// persist if at least one field changed (return greater than 0)
		return count($namVal) == $results;
	}

	/**
	 * getRecordset from array recordsets
	 *
	 * @param string $index
	 * @return  array
	 */
	Public function getRecordset( $index ){
		if( !isset( $this->recordsets[$index] ) ) return $this->rapport( false , 'getRecordset: ##LL:no_record_for## id [' . $index . ']. ' );
		$this->setPointer( $index );
		$recordset = array();
		foreach( array_keys( $this->recordsets[$this->pointer] ) as $property ){
				$recordset[$property] = $this->getProp( $property );
		}
		return $recordset;
	}

	/**
	 * setPointer
	 *
	 * @param string $index 
	 * @return  string
	 */
	Private function setPointer( $index ){
		if( empty($index) ) return false;
		if( !isset($this->recordsets[$index]) ) $this->lastNewIndex = $index;
		$this->pointer = $index;
		return true;
	}

	/**
	 * isProp
	 * tests if content of property (recordset) is equal as value
	 *
	 * @param string $property 
	 * @param string $value 
	 * @return  boolean
	 */
	Private function isProp( $property , $value ){
			if( !isset($this->recordsets[$this->pointer][$property]) ) return false;

			$encodedValue = $this->recordsets[$this->pointer][$property];
			
			if( !isset($this->properties[$property]['type']) ) return $encodedValue == $value;
			
			switch($this->properties[$property]['type']){
				case 'pass1way':
						return password_verify( $value , $encodedValue );
				break;
				case 'pass2way':
						return $this->decodeField( $property , $encodedValue ) == $value;
				break;
			}
			
			return $encodedValue == $value;
	}

	/**
	 * setProp into array recordsets
	 *
	 * @param string $property 
	 * @param string $value 
	 * @param boolean $noEncoding optional, default is false
	 * @return  boolean
	 */
	Private function setProp( $property , $value , $noEncoding = false ){
		// abort if no settings for this property found
		if( !isset($this->properties[$property]) ) return 0;
		
		if(  $this->isRecordLocked($this->pointer) == 'fields' && isset($this->properties[$property]['locked'])) return $this->rapport( 0 , 'field is locked' , 'not_set_for:'.$property.'('.$this->pointer.')' );

		// return 1 if nothing to change for property
		if( $noEncoding == false && isset($this->recordsets[$this->pointer][$property]) ){
			if( $this->isProp( $property , $value ) ) return 1;
		}
		
		// abort if validation failed. E.g. if incoming value is empty, but empty not allowed
		if( $this->validateField( $property , $value ) != true ) return 0;

		$encodedValue = $noEncoding ? $value : $this->encodeField( $property , $value );

		if( isset($this->recordsets[$this->pointer][$property]) &&  $this->recordsets[$this->pointer][$property] == $encodedValue ) return 0;
		// set changed property value
		$this->recordsets[$this->pointer][$property] = $encodedValue;
		
		// define and return notification
		$actionText = $this->indexfield == $property ? 'added! ' : '##LL:changed## &rarr; "'.$value.'" ';
		return $this->rapport( 1 , $actionText , 'set_for:'.$this->pointer.'_prop:'.$property.'' );
	}

	/**
	 * getProp from array recordsets
	 *
	 * @param string $property 
	 * @return  string
	 */
	Private function getProp( $property ){
		if( !isset($this->recordsets[$this->pointer][$property]) ) $this->rapport( false , 'getProp: ##LL:no_record_for## property [' . $property . '] id [' . $this->pointer . ']. ' );
		// decode field-values here
		return $this->decodeField( $property , $this->recordsets[$this->pointer][$property] );
	}

	/**
	 * encodeField
	 *
	 * @param string $property
	 * @param string $cleanValue
	 * @return  string
	 */
	Private function encodeField( $property , $cleanValue  ){
			$type = !isset($this->properties[$property]['type']) ? 'string' : $this->properties[$property]['type'];
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
	 * decodeField
	 *
	 * @param string $property
	 * @param string $encodedValue
	 * @return  string
	 */
	Private function decodeField( $property , $encodedValue ){
			$type = !isset($this->properties[$property]['type']) ? 'string' : $this->properties[$property]['type'];
			switch($type){
				case 'pass2way':
						return $this->cryptService->decrypt($encodedValue) ;
				break;
				case 'pass1way':
				default:
						return $encodedValue;
			}
			return $encodedValue;
	}
	
	/**
	 * validateField
	 *
	 * @param string $property
	 * @param string $cleanValue
	 * @return  string
	 */
	Private function validateField( $property , $cleanValue  ){
			if( !isset($this->properties[$property]['validation']) ) return true;
			$validations = explode( ',' , $this->properties[$property]['validation'] );
			$viewBase = new \Drg\CloudApi\viewBase( $this->settings );
			foreach($validations as $typeCode ) {
					$aTc = explode( '_' , $typeCode );
					$validatType = $aTc[0];
					$sr = array( '%1'=>$property , '%2'=>isset( $aTc[1]) ? $aTc[1] : 0 );
					switch($validatType){
						case 'unique':
								$encodedValue = $this->encodeField( $property , $cleanValue );
								if( $this->isValid_unique( $property , $encodedValue ) == false ) return $this->rapport( false , str_replace( array_keys($sr) , $sr , $viewBase->getLabel('validate.unique') ) . '! ' , 'validateField-'.$property );;
						break;
						case 'cronField':
								if( !empty($cleanValue) ) $aCfields = explode( ' ' , $cleanValue );
								if( isset($aCfields) && count($aCfields) != 5 ) return $this->rapport( false , str_replace( array_keys($sr) , $sr , $viewBase->getLabel('validate.cronField') ) . '! ' , 'validateField-'.$property );
						break;
						case 'minChar':
								if( strlen($cleanValue) < $aTc[1] ) return $this->rapport( false , str_replace( array_keys($sr) , $sr , $viewBase->getLabel('validate.minChar') ) . '! ' , 'validateField-'.$property );
						break;
						case 'maxChar':
								if( strlen($cleanValue) > $aTc[1] ) return $this->rapport( false , str_replace( array_keys($sr) , $sr , $viewBase->getLabel('validate.maxChar') ) . '! ' , 'validateField-'.$property );
						break;
						case 'numeric':
								if( !is_numeric($cleanValue) ) return $this->rapport( false , str_replace( array_keys($sr) , $sr , $viewBase->getLabel('validate.numeric') ) . '! ' , 'validateField-'.$property );
						break;
						case 'notEmpty':
								if( !empty($cleanValue) ) break;// not null nor 0 nor ''
								$isNumeric = in_array( 'numeric' , $validations ); // text-field may contain the value 0 (zero), numerical not
								if( '' == $cleanValue || $isNumeric ) return $this->rapport( false , str_replace( array_keys($sr) , $sr , $viewBase->getLabel('validate.notEmpty') ) . '! ' , 'validateField-'.$property );
						break;
					}
			}
			return true ;
	}

	/**
	 * isValid_unique
	 * validation method
	 *
	 * @param string $property 
	 * @param string $encodedValue 
	 * @return  string
	 */
	Private function isValid_unique( $property , $encodedValue ){
			if( !isset($this->recordsets[$this->pointer][$property]) ) return true;
			$otherRss = $this->recordsets;
			unset( $otherRss[$this->pointer] ); // dont compare with own recordset (this case should be exempted before)
			foreach( $otherRss as $ix => $rs ){
				if($rs[$property] == $encodedValue) return false;
			}
			return true;
	}

	/**
	 * lockRecord
	 *
	 * @return  void
	 */
	Public function lockRecord( $index , $onlyLockedFields = true ){
		$this->lockedRecordsets[$index] = $onlyLockedFields ? 'fields' : 'record';
		return $this->lockedRecordsets[$index];
	}

	/**
	 * unlockRecord
	 *
	 * @return  void
	 */
	Public function unlockRecord( $index ){
		if( isset($this->lockedRecordsets[$index]) ) unset($this->lockedRecordsets[$index]);
		return true;
	}

	/**
	 * isRecordLocked
	 *
	 * @return  void
	 */
	Public function isRecordLocked( $index ){
		if( isset($this->lockedRecordsets[$index]) ) return $this->lockedRecordsets[$index];
		return false;
	}

	/**
	 * setRequestRecords
	 *
	 * @return  void
	 */
	Private function setRequestRecords(){
		if( isset( $this->settings['req']['chk_' . $this->tablename] )  ){
				foreach( $this->settings['req']['chk_' . $this->tablename] as $ix => $aRow){
						foreach( $aRow as $fieldname => $content ) if( !isset($this->settings['req'][$this->tablename][$ix][$fieldname]) ) $this->settings['req'][$this->tablename][$ix][$fieldname] = 0;
				}
		}
		if( isset($this->settings['req']['delete'][$this->tablename]) ){
				return $this->setRequestRecord_deleteAction( $this->settings['req']['delete'][$this->tablename]  );
				
		}elseif( isset($this->settings['req']['ok']['save']) &&  isset( $this->settings['req'][$this->tablename] )  ){
				return $this->setRequestRecord_updateAction( $this->settings['req'][$this->tablename]  );
				
		}
		return false;
	}

	/**
	 * setRequestRecord_updateAction
	 *
	 * @param array $arrRecordsetsToUpdate multi-dim-array, array-names = indizes
	 * @return  boolean
	 */
	Protected function setRequestRecord_updateAction( $arrRecordsetsToUpdate ){
				$aFailed = array();
				$counter = 0;
				foreach( $arrRecordsetsToUpdate as $index => $row){
					if( $this->isRecordLocked($index) == 'record' ) continue;
					if( $this->setRecordset($row ,$index) ){ 
						$this->persist();
						++$counter;
					}else{
						if( isset($this->recordsets[$this->lastNewIndex]) ) unset($this->recordsets[$this->lastNewIndex]);
						$aFailed[$index] = $index; 
					}
				}
				
				if( $counter ) $this->rapport( true , $counter . ' ##LL:recordsets.'.($counter == 1 ? 1 : 0).'## ##LL:stored## ', 'setRequestRecords1');
				if( count($aFailed) ){
					return $this->rapport( 0 , count($aFailed) . ' ##LL:recordsets.'.(count($aFailed) == 1 ? 1 : 0).'## <b>failed</b> - rewind, not stored: [ '.implode(', ' , $aFailed).' ]', 'setRequestRecords');
				}elseif( $counter ) {
					return true;
				}
				
				return $this->rapport( 0 , '##LL:notthing_to## ##LL:store## ', 'setRequestRecords');
	}

	/**
	 * setRequestRecord_deleteAction
	 *
	 * @param array $arrIndizesToDelete multi-dim-array, array-names = indizes
	 * @return  boolean
	 */
	Protected function setRequestRecord_deleteAction( $arrIndizesToDelete ){
			$counter = 0;
			foreach( array_keys($arrIndizesToDelete) as $index ){
					if(isset( $this->recordsets[$index] )){
							if( $this->isRecordLocked($index) ) continue;
							$counter += 1;
							unset( $this->recordsets[$index] );
					}
			}
			if($counter) $this->persist();

			return $this->rapport( $counter > 0 ? 1 : 0 , '##LL:deleted##: '.$counter . ' ' , 'setRequestRecords');
	}

	/**
	 * loadFile into array recordsets
	 * read from file and decode fields if afored
	 *
	 * @return  void
	 */
	Private function loadFile(){
		if( !file_exists($this->table_filepath) && isset($this->settings['req']['ok']['default'])){
			$this->rapport( false , 'file ' . $this->table_filepath . ' not found, try to create default table', 'modelBase->loadFile-550' );
			$this->createDefaultTable( $this->settings['cryptedPasswordInDefaultTables'] );
		}
		if( !file_exists($this->table_filepath) ) return $this->rapport( false , '##LL:file## [' . $this->table_filepath . '] ##LL:not_exist##!' , 'modelBase->loadFile-553' );
		// read file into recordsets without decoding
		$propertieValues = $this->fileHandlerService->readCompressedFile( $this->table_filepath );
		foreach( $propertieValues as $idx => $recordset ){
			if( !is_array($recordset) ) continue;
			foreach( $recordset as $property => $value ){
				if( isset($this->properties[$property]) ) {
					$this->recordsets[$idx][$property] =  $value ;
				}
			}
		}
		return true;
	}

	/**
	 * createDefaultTable
	 *
	 * @param boolean $noEncoding optional, default is true
	 * @return  void
	 */
	Private function createDefaultTable($noEncoding = true){
 			$default_table_filepath = $this->settings['scrDir'] . $this->settings['default_data_filedir'] . $this->tablename . '.php';
			
			$conUsr = $this->fileHandlerService->readDefaultFile( $this->settings['scrDir'] . $this->settings['default_additional_filedir']  . $this->tablename . '.php' );
			
			if( ( !isset($conUsr) || !is_array($conUsr) || !count($conUsr) ) && method_exists( $this , 'getDefaultRows' ) ){
				$conUsr = $this->getDefaultRows();
			}
			if( ( !isset($conUsr) || !is_array($conUsr) || !count($conUsr) )){
				 return $this->rapport( false , '##LL:file## [default-table] ##LL:not_exist## : ' . $default_table_filepath . '.', 'createDefaultTable' );
			}
			$counter = 0;
			if(is_array($conUsr)){
				foreach( $conUsr as $index => $row){
					$counter += $this->setRecordset( $row , $index , $noEncoding ) ? 1 :0;
				}
			}
			if( $counter > 0 ) {
				$this->persist();
				return file_exists($this->table_filepath) ? $this->rapport( 1 , '' . count($conUsr) . ' ##LL:recordsets## ##LL:created##.', 'createDefaultTable' ) : $this->rapport( 0 , '##LL:file## ['.$this->table_filepath.'] ##LL:not_exist## 0 ##LL:recordsets## ##LL:created##.', 'createDefaultTable' );
			}
			return $this->rapport( false , 'NO ##LL:recordsets## created.', 'createDefaultTable' );
	}

	/**
	 * getDefaultRows_example
	 *
	 * @return array
	 */
	Public function getDefaultRows_example(){
			return array( 'user'=>'jon.doe' , 'pass'=>'$0m3-1w4y-cRyP73dV4Lyou' );
	}

	/**
	 * getPrependButtons
	 *
	 * @return  string
	 */
	Public function getPrependButtons( ){
		return '';
	}

	/**
	 * getAppendButtons
	 *
	 * @return  string
	 */
	Public function getAppendButtons( ){
		return '';
	}

	/**
	 * getRowButtons
	 *
	 * @param string $ix
	 * @return  string
	 */
	Public function getRowButtons( $ix ){
		$del = '<input type="submit" name="delete['.$this->tablename.']['.$ix.']" value="##LL:files.delete##" onclick="return window.confirm(\'Index: '.$ix.'\n##LL:files.delete##?\');" class="small" />';
		return $del;
	}

	/**
	 * executeAction
	 *
	 * @param string $key optional
	 * @return void
	 */
	Public function executeAction( $key = '' ){
	}

	/**
	 * cronAction
	 * action called by cron-daemon. 
	 * it may be called repeatingly 
	 *
	 * @param Drg\CloudApi\cli_boot $cli_boot
	 * @return void
	 */
	Public function cronAction( $cli_boot ){
			if( !isset( $this->properties['crontime'] ) ) return false;
			
			$allRs = $this->getRecordsets();
			if( !count($allRs) ) return false;
			
			$executed = 0;
			foreach( $allRs as $key => $RS ){
				if( !empty($RS['crontime']) && $cli_boot->isTimeToRun( $RS['crontime'] ) ) {
					++$executed;
					$this->executeAction( $RS[ $this->indexfield ] );
				}
			}
			return $executed;
	}

}

?>
