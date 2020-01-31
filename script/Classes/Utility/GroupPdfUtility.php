<?php
namespace Drg\CloudApi\Utility;

/***************************************************************
 *
 *  GroupDocsUtility
 *  feel free to change the contents in private property 'pdfProperties' 
 *  to your prefered values or translate it to another language than german.
 *  
 *  Hint: There is no automatic language-switch because cloudApiAdmin reads the language from browser-setttings, 
 *        but this Script may be called from command-line, so we dont know the enquirers language.
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
 * Class GroupPdfUtility
 */

class GroupPdfUtility extends \Drg\CloudApi\Utility\DataUtility {

	/**
	 * Property pdfProperties
	 * keywords DATE and GROUP get substituted in method getMemberlistsAsPdf()
	 *
	 * @var array
	 */
	Private $pdfProperties = array(
				'ImageWidth' => '120',
				'ImageTop' => '9',
				'ImageLeft' => '20',
				'TopMargin' => '30',
				'LeftMargin' => '30',
				'Title'            => 'Mitgliederliste Nextcloud-Gruppe ##GROUP##' , 
				'Keywords'         => 'Gruppe ##GROUP## Mitglieder Liste Nextcloud Owncloud Daten teilen Cloud' , 
				'Subject'          => 'Mit Mitgliedern der Gruppe ##GROUP## geteilt' , 
				'Hint_text' => 'Dieses Dokument wurde mit der Nextcloud-Gruppe __laquo__##GROUP##__raquo__ geteilt.',
				'Footertext_left' => 'Diese Liste wurde automatisch geteilt am __date_long__ Email für Rückmeldungen: cloud@sfgz.ch. ',
				'Footertext_right' => '__C__ __date_Y__ Schule für Gestaltung Zürich',
	);

	/**
	 * Property pdfDocuments
	 *
	 * @var array
	 */
	Private $pdfDocuments = array();

	/**
	 * initiate
	 *
	 * @return  void
	 */
	public function initiate (){
		$this->view = new \Drg\CloudApi\view( $this->settings );// in view: $this->objects = new htmlObjects();
		$this->connectorService = new \Drg\CloudApi\Services\ConnectorService( $this->settings );
		$this->connectorService->prepareConnection();
	}

    /**
     * startFirstCronjob
     *
     * @param string $timeout in seconds
     * @return array
     */
    public function startFirstCronjob($timeout) {
		if( !file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv') ) return false;
		
		$runtime = array();
		$runtime[0] = microtime(true); // actual time in seconds
		$runtime[1] = microtime(true) - $runtime[0]; // runtime in seconds

		$jobFile = rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'groupPdfJob.json';
		if(file_exists($jobFile)) unlink($jobFile);
		
		// read groups with members (usernames) and filter them as configured in settings and save as checklist, 
		$groupUserDB = $this->getCloudGroupUsers();
		if( !count($groupUserDB) ) return false;
		ksort($groupUserDB);
		// do the initial work: create array with pdf-contents, create folder if it does not exist. delete only obsolete files: those without new pdf.
		$pdfDocuments = $this->createMembersPdf( $groupUserDB );
		
		$this->preparePdfWork();
		// create a checklist and store it $this->pdfDocuments as cronjob-file
		echo "write pdf-files for ".count($pdfDocuments) ." groups to ".$jobFile." \n";
		file_put_contents( $jobFile , serialize( $pdfDocuments ) );

		// run usual cron-job if there is some time left ($this->settings['actualTimeout'])
		$runtime[count($runtime)] = microtime(true) - $runtime[0]; // total runtime in seconds
		$elapsedTime = $runtime[ count($runtime) -1 ];
		if ( $timeout - $elapsedTime > 0.5 )  return $this->startAsCronjob( $timeout - $elapsedTime );
		return false;
	}

    /**
     * startAsCronjob
     *
     * @param string $timeout in seconds
     * @return boolean $comleted
     */
    public function startAsCronjob($timeout) {
		$jobFile = rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'groupPdfJob.json';
		if( !file_exists($jobFile) ) return true;
		$completed = false;
		
		// if there is a checklist, read it, do work and write changes to file
		$pdfDocuments = $this->fileHandlerService->readCompressedFile( $jobFile  );
		// $pdfDocuments from cronjob-file
		if( !count($pdfDocuments) ){
				$completed = true;
		}else{
				$pdfDocuments = $this->pdfWork( $timeout , $pdfDocuments );
				// write $pdfDocuments to file if there is remaining data, otherwise delete the cronjob-file
		}
		if( !count($pdfDocuments) ){
				unlink($jobFile);
				$completed = true;
		}else{
				$this->fileHandlerService->writeCompressedFile( $jobFile , $pdfDocuments );
		}
		return $completed;
	}

    /**
     * helper getCloudGroupUsers
     *
     * @return array
     */
    public function getCloudGroupUsers() {
		// read groups and users
		$cloudUsers = $this->readFromFile_CloudUsersAndAttributes();
		$userGroups = $this->cloudUserFieldsToGroups( $cloudUsers , $this->settings['group_amount'] );
		if( !count($userGroups) ){ return false; }
		$this->groupUser = $this->filterArray( $userGroups , $this->settings['exec_document_group_filters'] );
		return $this->groupUser;
	}
	
    /**
     * helper cloudUserFieldsToGroups
     *
     * @param array $cloudUsers
     * @param string $maxFields settings['group_amount']
     * @return array
     */
    public function cloudUserFieldsToGroups( $cloudUsers , $maxFields ) {
		if(!count($cloudUsers)) return false;;
		$userGroups = array();
		ksort($cloudUsers);
		foreach($cloudUsers as $user => $userRow){
			for( $fld = 'grp_' , $z=1 ; $z <= $maxFields ; ++$z ) {
				if( !isset($userRow['grp_' . $z]) || empty($userRow['grp_' . $z]) ) continue;
				$groupname = $userRow['grp_' . $z];
				$userGroups[$groupname][$user] = $userRow['DISPLAYNAME'];
			}
		}
		return $userGroups;
	}


    /**
     * helper filterArray
     * filters the index of an array by comma-separed searchpatterns 
     *
     * @param array $fullData
     * @param string $filterlist settings['exec_document_group_filters']
     * @return array
     */
    Public function filterArray( $fullData , $filterlist ) {
		$filterMethods = $this->getFilterMethodSuffix( $filterlist );
		if( !is_array($filterMethods) || !count($filterMethods) ) return $fullData;
		foreach( array_keys($fullData) as $contentValue ){
				$filtertest = 0;
				foreach( $filterMethods as $methodSuffix => $patternsAndLength ){
						$method = 'applyFilter_' . $methodSuffix;
						$filtertest = !method_exists( $this , $method ) ? true : $this->$method( strtolower($contentValue) , $patternsAndLength ) ;
						if( $filtertest ) break;
				}
 				if( !$filtertest ) unset( $fullData[$contentValue] );
		}
		return $fullData;
    }
    
    /**
     * helper getFilterMethodSuffix
     *
     * @param array $fullData
     * @param string $filterlist settings['exec_document_group_filters']
     * @return void
     */
    Private function getFilterMethodSuffix( $filterlist ) {
		$groupFilter = explode( ',' , $filterlist );
		$filterMethods  = array();
		if( !count($groupFilter) || $groupFilter[0] == '' ) return false;
		foreach( $groupFilter as $rawFltPart ){
			$flt = trim($rawFltPart);
			$clFlt = strtolower(str_replace( '*' , '' , $flt ));
			if( empty($flt) ) continue; // eg A*,,B*
			if( $flt == '*' ) return false; // show all! eg. A*,*,B*
			if( strpos( ' ' . $flt , '*' ) == 1 ) {//  * at startposition
					$filterMethods['lastChars'][$clFlt] = strlen($clFlt);
			}elseif( strpos( ' ' . $flt , '*' ) == strlen($flt) ){ //  * at endposition
					$filterMethods['firstChars'][$clFlt] = strlen($clFlt);
			}else{ // no * at all, searchpattern can be somwere in the string
					$filterMethods['somePos'][$clFlt] = strlen($clFlt);
			}
		}
		return $filterMethods;
    }
    
    /**
     * helper applyFilter_lastChars
     * called by filterArray()
     * applyFilter_ with methodSuffix lastChars
     *
     * @param string $fullSearchString
     * @param array $patternsAndLength
     * @return boolean
     */
    Private function applyFilter_lastChars( $fullSearchString , $patternsAndLength ) {
			$searchStringLength = strlen($fullSearchString);
			foreach( $patternsAndLength as $cleanFilter => $filterLength ){
					if( substr( $fullSearchString , $searchStringLength - $filterLength ) == $cleanFilter ) return true;
			}
			return false;
	}
    
    /**
     * helper applyFilter_firstChars
     * called by filterArray()
     * applyFilter_ with methodSuffix firstChars
     *
     * @param string $fullSearchString
     * @param array $patternsAndLength
     * @return boolean
     */
    Private function applyFilter_firstChars( $fullSearchString ,$patternsAndLength ) {
			foreach( $patternsAndLength as $cleanFilter => $filterLength ){
					if( substr($fullSearchString , 0 , $filterLength ) == $cleanFilter ) return true;
			}
			return false;
	}
    
    /**
     * helper applyFilter_somePos
     * called by filterArray()
     * applyFilter_ with methodSuffix somePos
     *
     * @param string $fullSearchString
     * @param array $patternsAndLength
     * @return boolean
     */
    Private function applyFilter_somePos( $fullSearchString ,$patternsAndLength ) {
			foreach( array_keys($patternsAndLength) as $cleanFilter ){
					if( strpos( ' ' . $fullSearchString , $cleanFilter ) > 0 ) return true;
					if( is_numeric( $cleanFilter ) ){
							$numbers = preg_replace("/[^0-9]/", ' ', $fullSearchString);
							$aNumbers = explode( ' ' , trim($numbers) );
							foreach($aNumbers as $num) if( $num == $cleanFilter ) return true;
//   			  				echo $cleanFilter . ' is_numeric but != ' . $numbers . ' in ' . $fullSearchString . ' <br>';
					}
			}
			return false;
	}

    /**
     * helper createMembersPdf
     *
     * @param array $checkedGroups with html-encoded array-names
     * @param array $groupUser
     * @return array
     */
    public function createMembersPdf( $checkedGroups , $groupUser=array() ) {
				if( !count($groupUser) && count($this->groupUser) ) $groupUser = $this->groupUser;
				
				$aSuffixes =  explode( ',' , $this->settings['logosuffixes'] ) ;
				$imageFolder = $this->settings['dataDir'] . rtrim( $this->settings['pdf_options_Logofile'] , '.' ) . '.' ;
				foreach( $aSuffixes as $sfx ){
					if( file_exists($imageFolder . $sfx) && is_file($imageFolder . $sfx) ){
						$this->pdfProperties['ImagePath'] = $imageFolder . $sfx;
						break;
					}
				}
				foreach( $this->settings as $set => $val ){
					if( 1 != strpos( ' ' . $set , 'pdf_options_' ) ) continue;
					 // set only pdf_options_Option that start with uppercase
					$opt = str_replace( 'pdf_options_' , '' , $set );
					if( ucFirst($opt) == $opt ) $this->pdfProperties[$opt] = $val;
				}
				
				$pdfDocuments = array();
				foreach( array_keys($checkedGroups) as $htmlId ){
						$group = rawurldecode($htmlId);
						unset($checkedGroups[$htmlId]);
						if( !isset($groupUser[$group]) ) continue;
						ksort($groupUser[$group]);
						$title = $this->view->getLabel( 'pdf_title_members_of_group' ) . ' ' . $group;
						$subtitle = $this->view->getLabel( 'pdf_title_members' ) ;
						$pdf = new \Drg\CloudApi\Services\PdfService;
						$docuProperties = $this->pdfProperties;
						foreach($docuProperties as $prop => $str ) $docuProperties[$prop] = str_replace( '##GROUP##' , $group , $str );
						$pdf->initializePdf( $docuProperties );
						$pdf->AddPage();
						
						$hintText = $pdf->encode($docuProperties['Hint_text']);
						if( !empty($hintText) ){
							$pdf->SetFont('Helvetica','I',10);
							$pdf->Cell( 0 , 6 , $hintText , '' , 1 );
						}
						
						$pdf->SetFont('Helvetica','B',10);
						$wdt = $pdf->getStringWidth($title);
						$pdf->Cell( $wdt , 6 , $pdf->encode($title) , 'B' , 0 );
						$pdf->SetFont('Helvetica','',10);
						$pdf->Cell( 0 , 6 , ' (' . count($groupUser[$group]) . ' '. $pdf->encode($subtitle) . ')' , 'B' , 1 );
						
						$pdf->Ln(2);
						if( $this->settings['download_details'] ){
								foreach( $groupUser[$group] as $user => $username ){
										$pdf->Cell( 0 , 5 , $pdf->encode($username) . ', ' . $user , '' ,1 );
								}
						}else{
								foreach( $groupUser[$group] as $user => $username ){
										$pdf->Cell( 0 , 5 , $user , '' ,1 );
								}
						}
						
						$filename = $group ;
						$pdfDocuments[$filename] = $pdf->Output( '' , 'S' );
				}
				$this->pdfDocuments =$pdfDocuments ;
				return $this->pdfDocuments;
	}

    /**
     * helper preparePdfWork
     * create folder if it does not exist, 
     * delete only obsolete files.
     *
     * @param array $pdfDocuments
     * @return boolean
     */
    public function preparePdfWork($pdfDocuments = array()) {
			if( count($pdfDocuments) ) $this->pdfDocuments = $pdfDocuments;
			$this->connectorService->apiCreateFolder();
			if( isset($this->settings['pdf_clear_dir_on_start']) && !empty($this->settings['pdf_clear_dir_on_start']) ) {
				$this->connectorService->apiClearFolder( $this->pdfDocuments );
				// output for information about success
				$this->debug['pdf_deleted'] = ' ##LL:pdf_deleted##. ';
			}
	}

    /**
     * helper pdfWork
     * Create new pdf-files with groupmembers for each group, upload them to cloud and share:
     * - overwrite exisiting files and keep them as older versions. 
     * - Share with groupmembers if checked
     *
     * @param int $exectime seconds
     * @param array $pdfDocuments
     * @return boolean
     */
    public function pdfWork( $exectime , $pdfDocuments = array() ) {
				if( count($pdfDocuments) ) $this->pdfDocuments = $pdfDocuments;
				$runtime = array();
				$runtime[0] = microtime(true); // actual time in seconds
				$runtime[1] = microtime(true) - $runtime[0]; // runtime in seconds
				$created = 0;
				$permission = 1;// [ 1=read | 2=update | 4=create | 8=delete | 16=share | 31=all ]
				foreach( $this->pdfDocuments as $filename  => $aDocument ){
						if ( $runtime[ count($runtime) -1 ] > $exectime ) break;
						$groupname = rawurlencode($filename);
						$this->connectorService->apiPutFileToCloud( $filename.'.pdf' , $aDocument );
						if( isset($this->settings['pdf_share_on_upload']) && !empty($this->settings['pdf_share_on_upload']) ) {
							$this->connectorService->apiShareFile( $filename.'.pdf' , $groupname , $permission  );
						}
						unset($this->pdfDocuments[$filename]);
						$created+=1;
						$runtime[count($runtime)] = microtime(true) - $runtime[0]; // total runtime in seconds
				}
				
				$createdAndSharedText = $created . ' ##LL:pdf_created##';
				if( isset($this->settings['pdf_share_on_upload']) && !empty($this->settings['pdf_share_on_upload']) ) $createdAndSharedText .= ' ##LL:pdf_and_shared## ';
				$this->debug['pdf_created'] =  $createdAndSharedText . ' ##LL:on_date## ' .date('d.m.y, H:i:s'). ' Uhr.';
				return $this->pdfDocuments;
	}
	
}
