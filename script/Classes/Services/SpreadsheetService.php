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
 
	$spoutFilpath = SCR_DIR . 'Classes/Contributed/Spout/Autoloader/autoload.php';
	if( !file_exists($spoutFilpath) ) return false;
	
	use Box\Spout\Reader\ReaderFactory;
	use Box\Spout\Writer\WriterFactory;
	use Box\Spout\Common\Type;

	require_once( $spoutFilpath ); 

if( file_exists($spoutFilpath) ) {class SpreadsheetService {
		
		/**
		* arrayToSpreadSheet
		* 
		* @param array $spoutRs
		* @param string $filePathname 
		* @return void
		*/
		public function arrayToSpreadSheet( $spoutRs, $filePathname ) {
				$type = pathinfo( $filePathname , PATHINFO_EXTENSION );
				
				switch( $type ){
					case 'ods' : $writer = WriterFactory::create(Type::ODS); break;
					case 'xlsx' : $writer = WriterFactory::create(Type::XLSX); break;
					default : return false; break;
				}
				$writer->setShouldCreateNewSheetsAutomatically(false);

				$writer->openToBrowser( pathinfo( $filePathname , PATHINFO_FILENAME ).'.'. $type); // stream data directly to the browser
				
				if( !is_array($spoutRs) ) return false;
				$testTab = $spoutRs;
				$testRow = array_shift($testTab);
				if( !is_array($testRow) ) return false;
				$testField = array_shift($testRow);
				if( is_array($testField) ){
					foreach( $spoutRs as $sheet => $shtRs ){
						$writer->addRows( $shtRs ); // add multiple rows at a time
						$writer->getCurrentSheet()->setName( $sheet );
						$writer->addNewSheetAndMakeItCurrent();
					}
				}else{
					$writer->addRows( $spoutRs ); // add multiple rows at a time
					$writer->getCurrentSheet()->setName(pathinfo( $filePathname , PATHINFO_FILENAME ));
				}
				
				$writer->close();
				exit();
		}
		
		/**
		* spreasheetToArray
		* 
		* @param string $filePathName
		* @param boolean $prependHeadrow
		* @return array
		*/
		public function spreadsheetToArray( $filePathName , $prependHeadrow = FALSE ) {
				$uploadedFileExtension = strtolower( pathinfo( $filePathName , PATHINFO_EXTENSION ) );

				switch( $uploadedFileExtension ){
					case 'ods' : $reader = ReaderFactory::create(Type::ODS); break;
					case 'xlsx' : $reader = ReaderFactory::create(Type::XLSX); break;
					default : return false; break;
				}
				
				$spoutRs = array();
				$reader->open($filePathName);
				foreach ($reader->getSheetIterator() as $sNr => $sheet) {
					$sheetname = urlencode( str_replace( '.csv' , '' , $sheet->getName() ) );
					foreach ($sheet->getRowIterator() as $rNr => $row) {
						if( !isset($fieldnames[$sNr]) ) {$fieldnames[$sNr] = $row; if(!$prependHeadrow) continue;}
						foreach($fieldnames[$sNr] as $fix=>$fld) $spoutRs[$sheetname][$rNr][$fld] = $row[$fix];
					}
				}
				$reader->close();
				return $spoutRs;
		}
		
}}
?>
