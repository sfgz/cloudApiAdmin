<?php
namespace Drg\CloudApi\Services;

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

class CsvService {

	/**
	 * Property settings
	 *
	 * @var array
	 */
	Public $settings = NULL;

	/**
	 * Property fileDescription
	 *
	 * @var array
	 */
	Public $fileDescription = NULL;

	/**
	 * Property maximalRows
	 *
	 * @var int
	 */
	private $maximalRows = 10000;
	
	/**
	 * __construct
	 *
	 * @param array $settings optional
	 * @return  void
	 */
	Public function __construct( $settings = array() ) {
			$this->settings = $settings;
	}
	
	/**
	 * csvFile2array
	 * - on upload we have to transform from user-given charsetFile and delimiterFile to sys-charsetOut
	 * - on download we have to transform from sys-charsetFile and sys- delimiterFile to user-requested charsetOut
	 * 
	 * @param string $filename 
	 * @param string $delimiterFile 
	 * @param string $charsetFile 
	 * @param array $charsetOut 
	 * @return array
	 */
	public function csvFile2array( $filename , $delimiterFile = '' , $charsetFile = '' , $charsetOut = '' ) {
			if( empty($delimiterFile) ) $delimiterFile = $this->settings['sys_csv_delimiter'];
			if( empty($charsetFile) ) $charsetFile = $this->settings['sys_csv_charset'];
			if( empty($charsetOut) ) $charsetOut = $this->settings['sys_csv_charset'];
			
			$outData = array();
			$row = 1;
			if (($handle = fopen($filename, "r")) !== FALSE) {
				while (($data = fgetcsv($handle, $this->maximalRows , $delimiterFile )) !== FALSE) {
					$fieldnames =  $data ;
					break;
				}
				while (($data = fgetcsv($handle, $this->maximalRows , $delimiterFile )) !== FALSE) {
					foreach($fieldnames as $i => $nam) {
						$outData[$row][$nam] = isset($data[$i]) ? iconv( $charsetFile , $charsetOut , $data[$i] ) : '';
					}
					$row++;
				}
				fclose($handle);
			}
// 			if($filename  == '/home/httpd/vhosts/medienformfarbe.ch/sfgz/tools/cloudApiAdmin/data/cloud_sfgz/local/delete/delete_list.csv' )print_r($outData);
			return $outData;
	}
	
	/**
	 * arrayToCsvString
	 * 
	 * @param array $aTable 
	 * @param string $delimiter optional
	 * @param string $enclosure optional 
	 * @param boolean $noHeadrow optional default is FALSE
	 * @return string
	 */
	public function arrayToCsvString($aTable , $delimiter = '' , $enclosure = '' , $noHeadrow = FALSE) {
			if( empty($delimiter) ) $delimiter = $this->settings['sys_csv_delimiter'];
			if( !empty($enclosure) ) $enclosure = html_entity_decode($enclosure);
			
			$headRow = array_shift($aTable);
			array_unshift( $aTable , $headRow );
			if( !is_array($headRow) ) return '##LL:no_data##';
			$csvOut = $noHeadrow ? '' : $enclosure . implode( $enclosure.$delimiter.$enclosure , array_keys($headRow) ) . $enclosure . "\n";
			foreach( $aTable as $idx => $row){
				$csvOut .= $enclosure . implode( $enclosure.$delimiter.$enclosure , $row ). $enclosure . "\n";
			}
			return $csvOut;
	}

	/**
	 * downloadAsCsv
	 * 
	 * @param string $strOut 
	 * @param string $filename
	 * @return void
	 */
	public function downloadAsCsv($strOut, $filename = 'verarbeitet.csv') {
		header('Content-Type: application/csv');
		header('Content-Disposition: attachment; filename="'.$filename.'";');
		echo $strOut;
		die;
	}

	/**
	* analyse_file
	* 
	* from Ashley, http://php.net/manual/de/function.fgetcsv.php
	* 
	* Example Usage:
    * $Array = analyse_file('/www/files/file.csv', 10);
    *
    * example usable parts
    * $Array['charset']['value'] => ISO-8859-15
    * $Array['delimiter']['value'] => ,
    * $Array['linebreak']['value'] => \r\n
    * 
	* @param string $file 
	* @param integer $capture_limit_in_kb
	* @return array 
	*/
	function analyse_file($file, $capture_limit_in_kb = 10) {
		// capture starting memory usage
		$output['peak_mem']['start']    = memory_get_peak_usage(true);

		// log the limit how much of the file was sampled (in Kb)
		$output['read_kb']                 = $capture_limit_in_kb;
	
		// read in file
		$fh = fopen($file, 'r');
			$contents = fread($fh, ($capture_limit_in_kb * 1024)); // in KB
		fclose($fh);
	
		// specify allowed field delimiters
		$delimiters = array(
			'comma'     => ',',
			'semicolon' => ';',
			'tab'         => "\t",
			'pipe'         => '|',
			'colon'     => ':'
		);
	
		// specify allowed line endings
		$linebreaks = array(
			'rn'         => "\r\n",
			'n'         => "\n",
			'r'         => "\r",
			'nr'         => "\n\r"
		);
	
		// loop and count each line ending instance
		foreach ($linebreaks as $key => $value) {
			$line_result[$key] = substr_count($contents, $value);
		}
	
		// sort by largest array value
		asort($line_result);
	
		// log to output array
		$output['linebreak']['results']     = $line_result;
		$output['linebreak']['count']     = end($line_result);
		$output['linebreak']['key']         = key($line_result);
		$output['linebreak']['value']     = $linebreaks[$output['linebreak']['key']];
		$lines = explode($output['linebreak']['value'], $contents);
	
		// remove last line of array, as this maybe incomplete?
		array_pop($lines);
	
		// create a string from the legal lines
		$complete_lines = implode(' ', $lines);
	
		// log statistics to output array
		$output['lines']['count']     = count($lines);
		$output['lines']['length']     = strlen($complete_lines);
	
		// loop and count each delimiter instance
		foreach ($delimiters as $delimiter_key => $delimiter) {
			$delimiter_result[$delimiter_key] = substr_count($complete_lines, $delimiter);
		}
	
		// sort by largest array value
		asort($delimiter_result);
	
		// log statistics to output array with largest counts as the value
		$output['delimiter']['results']     = $delimiter_result;
		$output['delimiter']['count']         = end($delimiter_result);
		$output['delimiter']['key']         = key($delimiter_result);
		$output['delimiter']['value']         = $delimiters[$output['delimiter']['key']];

		$output['charset']['list'] = 'utf-8,iso-8859-15,iso-8859-1,windows-1251,utf-16';
		$charsetlist = explode( ',' , $output['charset']['list'] );
		foreach ($charsetlist as $item) {
			if( strtolower(mb_detect_encoding( $complete_lines , $item , true)) == strtolower($item) ){ // first test ok
				$sample = iconv($item, $item, $complete_lines);
				if (md5($sample) == md5($complete_lines)) { // second test ok
					$output['charset']['value'] =  $item;
					break;
				}
			}
		}
	
		// capture ending memory usage
		$output['peak_mem']['end'] = memory_get_peak_usage(true);
		
		$this->fileDescription = array( 'charset'=>$output['charset']['value'] , 'delimiter'=>$output['delimiter']['value'] , 'linebreak'=>$output['linebreak']['value']  );
		return $this->fileDescription;
	}
	
	/**
	 * file2arrays
	 * we have to transform from user-given charsetFile and delimiterFile to sys-charsetOut
	 * 
	 * @param string $filePathName 
	 * @return array
	 */
	public function file2arrays( $filePathName ) {
			$fileExtension = strtolower( pathinfo( $filePathName , PATHINFO_EXTENSION ) );
			$aAvaiableExtensions = array_flip(explode( ',' , $this->avaiableExtensions() ));
			if( !isset( $aAvaiableExtensions[$fileExtension] ) ) {
				$this->debug['file2arrays'] = '##LL:file## &laquo;'.basename( $filePathName ).'&raquo; <strong>##LL:forbidden_extension##</strong>: &laquo;<b>' . $fileExtension . '</b>&raquo;&nbsp;' ;
				return false;
			}

			if(  $fileExtension == 'csv' ) {
					$AnalysedFile = $this->analyse_file($filePathName);
					$convertedArrayFromFile = $this->csvFile2array( $filePathName , $AnalysedFile['delimiter'] , $AnalysedFile['charset'] , $this->settings['sys_csv_charset'] );
					$aDebug = array();
					if( $AnalysedFile['delimiter'] != $this->settings['sys_csv_delimiter'] ) $aDebug[] = ' replaced [' .$AnalysedFile['delimiter'].'] with ['.$this->settings['sys_csv_delimiter'].']';
					if( strtolower($AnalysedFile['charset']) != strtolower($this->settings['sys_csv_charset']) )   $aDebug[] = ' converted &laquo;'.$AnalysedFile['charset'].'&raquo; to &laquo;'.$this->settings['sys_csv_charset'].'&raquo;';
					$this->debug['file2arrays'] = implode( ' and ' , $aDebug ) ;
					$workSheets =  array( pathinfo( $filePathName , PATHINFO_FILENAME ) => $convertedArrayFromFile );
					return $workSheets;
			}
			
			if(  $fileExtension == 'xls' ) {
					$workSheets = $this->xlsFile2Arrays( $filePathName );
			}else{
					$workSheets = $this->spreadsheetFile2arrays( $filePathName );
			}
			$this->debug['file2arrays'] = ' Read-worksheets(' . count($workSheets) . ')-> done.';
			return $workSheets;
	}
	
	/**
	 * spreadsheetFile2arrays
	 * 
	 * @param string $filePathName
	 * @return string
	 */
	public function spreadsheetFile2arrays( $filePathName ) {
			$class = $this->isClassAvaiable('SpreadsheetService');
			if( empty($class) ) {
					$this->debug['spreadsheetFile2arrays'] = ' Class &laquo;SpreadsheetService&raquo; not found!';
					return false;
			}
			
			$spreadsheetService = new $class();
			$arraysFromSpreadsheetFile = $spreadsheetService->spreadsheetToArray( $filePathName );
			return $arraysFromSpreadsheetFile;
	}
	
	/**
	 * xlsFile2Arrays
	 * 
	 * @param string $filePathName
	 * @param boolean $prependHeadrow
	 * @return array
	 */
	public function xlsFile2Arrays($filePathName , $prependHeadrow = FALSE ) {
			if( !file_exists(SCR_DIR . $this->settings['service_spreadsheet_excel_reader']) ) return false;
			require_once( SCR_DIR . $this->settings['service_spreadsheet_excel_reader'] );
			$spoutRs = array();
			$data = new \Spreadsheet_Excel_Reader($filePathName);
			foreach( $data->sheets as $sNr => $objSheet){
				$sheetname = urlencode( str_replace( '.csv' , '' , $data->boundsheets[$sNr]['name'] ) );
				foreach( $objSheet['cells'] as $rNr => $row){
					if( !isset($fieldnames[$sNr]) ) {// first row (titles)
						$fieldnames[$sNr] = $row;
						if( count($row) < $objSheet['numCols'] ){
							for( $emptyCols = count($row) ; $emptyCols <= $objSheet['numCols'] ; ++$emptyCols) { 
									$name = strtolower(( (($emptyCols-1)/26>=1)?chr(($emptyCols-1)/26+64):'') . chr(($emptyCols-1)%26+65));
									$fieldnames[$sNr][$emptyCols . '_' . $name] = $name; 
							}
						}
						if(!$prependHeadrow) continue;
					}
					foreach($fieldnames[$sNr] as $fix=>$fld) $spoutRs[$sheetname][$rNr][$fld] = isset($row[$fix]) ? utf8_encode($row[$fix]) : '';
				}
			}
			return $spoutRs;
	}
	
	
	/**
	 * downloadCsvFileAsSpreadsheet
	 * 
	 * @param string $filename 
	 * @param string $type 
	 * @return void
	 */
	public function downloadCsvFileAsSpreadsheet( $filePathname , $type = '' ) {
			if( strtolower( pathinfo( $filePathname , PATHINFO_EXTENSION ) ) != 'csv') {
					$this->debug['downloadCsvFileAsSpreadsheet'] = 'This method can only read csv files';
					return FALSE;
			}
			
			// read the csv file
			$spoutRs = $this->csvFile2array( $filePathname , $this->settings['sys_csv_delimiter'] , $this->settings['sys_csv_charset'] , $this->settings['sys_csv_charset'] );
			
			if( empty($type) ) $type = $this->settings['download_format'];
			
			return $this->downloadArrayAsSpreadsheet( $spoutRs , pathinfo( $filePathname , PATHINFO_FILENAME ) . '.' . $type ) ;
	}
	
	/**
	 * downloadArrayAsSpreadsheet
	 * 
	 * @param array $spoutRs 
	 * @param string $filename 
	 * @param boolean $noHeadrow optional default is FALSE
	 * @return void
	 */
	public function downloadArrayAsSpreadsheet( $spoutRs , $filename , $noHeadrow = FALSE ) {
			$testTab = $spoutRs;
			$testRow = array_shift($testTab);
			$testField = array_shift($testRow);

			$type = strpos( $filename , '.' ) == false ? $this->settings['download_format'] : strtolower(pathinfo( $filename , PATHINFO_EXTENSION ));
			if( $type != 'csv' ){
				$class = $this->isClassAvaiable('SpreadsheetService');
				if( empty($class)) {
						$this->debug['downloadCsvFileAsSpreadsheet'] = ' Class &laquo;SpreadsheetService&raquo; not found!';
						$type = 'csv';
				}
			}
			
			if( $type == 'csv' ) {
					if( is_array($testField) ){
						$tempSputRs = $spoutRs;
						$spoutRs = array();
						foreach( $tempSputRs as $sheet => $shtRs ){
								foreach( $shtRs as $ix => $rows ) $spoutRs[] = $rows;
						}
					}
					$encodedString = $this->arrayToCsvString( $spoutRs , $this->settings['download_csv_delimiter'] , $this->settings['download_csv_enclosure'] , $noHeadrow);
					$decodedString = $this->settings['sys_csv_charset'] == $this->settings['download_csv_charset'] ? $encodedString : iconv(  $this->settings['sys_csv_charset'] , $this->settings['download_csv_charset'] , $encodedString );
					$this->downloadAsCsv( $decodedString , pathinfo( $filename , PATHINFO_FILENAME ) . '.csv' ); 
					exit();
			}
			
			if( is_array($testField) ){
				$tempSputRs = $spoutRs;
				$spoutRs = array();
				foreach( $tempSputRs as $sheet => $shtRs ) {
					if( is_array($shtRs) ){
						$headrow = array();
						foreach( $shtRs as $ix => $row ){
 							if( !is_array($row) ) {
								unset($spoutRs[$sheet][$ix]);
								continue;
 							}
							foreach( $row as $fld => $cnt ) if( !empty($fld) ) $headrow[$fld] = $fld;
						}
						if( count($shtRs) ) $spoutRs[$sheet] = $shtRs;
						if( $noHeadrow == FALSE && count($headrow) ) array_unshift( $spoutRs[$sheet] , $headrow);
					}
				}
			}else{
				// add headrows with column-names
				$headrow = array();
				foreach( $spoutRs as $ix => $row ){
					foreach( $row as $fld => $cnt ) if( !empty($fld) ) $headrow[$fld] = $fld;
				}
				if( $noHeadrow == FALSE ) array_unshift( $spoutRs , $headrow);
			}
			$spreadsheetService = new $class();
			$spreadsheetService->arrayToSpreadSheet( $spoutRs,  pathinfo( $filename , PATHINFO_BASENAME ) );
		//	if( count($spoutRs) ) 	exit();
	}
	
	/**
	 * avaiableExtensions
	 * 
	 * @param string $asArray default is FALSE, return as string
	 * @param boolean $downloadmode avaiable for download
	 * @return void
	 */
	public function avaiableExtensions( $asArray = 'string' , $downloadmode = FALSE ) {
			
			$aExtensions[] = 'csv';
			
			if( $downloadmode == FALSE && $this->isClassAvaiable('Spreadsheet_Excel_Reader') ) $aExtensions[] = 'xls';
			
			if( $this->isClassAvaiable('SpreadsheetService') ) $aExtensions[] = 'xlsx,ods';
			
			$strAvaiableExtensions = implode( ',' , $aExtensions );
			
			if( strtolower($asArray) != 'array' ) return $strAvaiableExtensions;
			
			$aAvaiableExtensions = array_flip( explode( ',' , $strAvaiableExtensions ) );
			foreach( array_keys($aAvaiableExtensions) as $ext ) { $aAvaiableExtensions[$ext] = $ext; }
			return $aAvaiableExtensions;
	}
	
	/**
	 * avaiableDownloadExtensions
	 * 
	 * @param string $asArray default is FALSE, return as string
	 * @return void
	 */
	public function avaiableDownloadExtensions( $asArray = 'string' ) {
			return $this->avaiableExtensions( $asArray , TRUE );
	}
	
	/**
	 * isClassAvaiable
	 * 
	 * @param string $searchedClass
	 * @return void
	 */
	public function isClassAvaiable( $searchedClass ) {
		if( !isset( $this->settings['enable_service_' . strtolower($searchedClass)]) || empty( $this->settings['enable_service_' . strtolower($searchedClass)]) ) return false;
		return $this->isClassExisting( $searchedClass );
	}
	
	/**
	 * isClassExisting
	 * 
	 * @param string $searchedClass
	 * @return void
	 */
	public function isClassExisting( $searchedClass ) {
		// possibly it exists a filename to the script, detect if it exists
		// e.g.  $searchedClass = 'Spreadsheet_Excel_Reader' and $this->settings['service_spreadsheet_excel_reader'] = Classes/Contributed/excel_reader27.php:
		if( isset( $this->settings['service_' . strtolower($searchedClass)]) && file_exists( SCR_DIR . $this->settings['service_' . strtolower($searchedClass)] ) ) {
			return $searchedClass;
		}
		
		// otherwise perhaps we have a registered classname
		$allClasses = get_declared_classes();
		foreach($allClasses as $class){
			$aClassFragments = explode( '\\' , $class );
			$classname = array_pop($aClassFragments);
			if( strtolower($searchedClass) == strtolower($classname) ){
				return $class;
			}
		}
		return false;
	}
	
}

?>
