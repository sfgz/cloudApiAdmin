<?php
namespace Drg\CloudApi\Controller;
if (!class_exists('Drg\CloudApi\boot', false)) die( basename(__FILE__) . ': Die Datei "'.__FILE__.'" muss von Klasse "boot" aus aufgerufen werden.' );
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
 * ActionsController
 * 
 * Modi:
 * - Quota ab Gruppen-csv-file wenn keine Quota angegeben
 * - loeschen ab Loeschen-csv-file (read-from-cloud not affored OR check-deleted not used)
 * - nicht vergleichen (Loeschen-csv-file erforderlich, sonst werden user nie geloescht)
 * -- nicht vergleichen und lesen: dont-read-from-cloud NOT IMPLEMENTED!
 * -- nicht vergleichen, nur zum anschauen lesen NOT IMPLEMENTED!
 * Optionen:
 * - Mehrere verschiedene Cloud-API Verbindungen moeglich
 * - Felder-Zuweisung ab Konfigurations-Datei entsprechend dem Dateinamen der hochgeladenen Tabellen
 * - Muster-Tabellen in Konfigurations-Datei definierbar
 *
 */

/**
*/
class ActionsController extends \Drg\CloudApi\controllerBase {

	/**
	 * Property actionDefault
	 *
	 * @var string
	 */
	Public $actionDefault = 'dateien';

	/**
	 * Property accessRules
	 *
	 * @var array
	 */
	Protected $accessRules = array(
		'welcome' => 0,
		'dateien' => 1  ,
		'clouduser' => 3 ,
		'viewcloud' => 3 ,
		'vergleich' => 3 ,
		'export' => 7,
	);

	/**
	 * Property subActions
	 * used to set button as active while a subaction is selected
	 *
	 * @var array
	 */
	Protected $subActions = array(
		'dateien' => array('dateien','database') ,
		'clouduser' => array('clouduser','viewcloud','vergleich') ,
	);

	/**
	 * initiate
	 *
	 * @return  void
	 */
	public function initiate() {
		parent::initiate();
		$this->view->assign( 'autostart_check' , isset($this->settings['req']['autostart']) && $this->settings['req']['autostart'] ? ' checked="1" ' : '' );
		$this->view->assign( 'refresh' , isset($this->settings['req']['refresh']) && $this->settings['req']['refresh'] ? $this->settings['req']['refresh'] :$this->settings['refresh'] );
		$this->settings['actualTimeout'] = !isset($this->settings['req']['timeout']) || empty($this->settings['req']['timeout']) ? $this->settings['exectimeout'] : $this->settings['req']['timeout'];
		if( $this->settings['actualTimeout'] < 0.1 ) $this->settings['actualTimeout'] = 1;
		$this->view->assign( 'timeout' , $this->settings['actualTimeout'] );
		$this->view->assign( 'configAction' , 'settings_cat_sync' );
		$this->readCloudUtility = new \Drg\CloudApi\Utility\ReadCloudUtility( $this->settings );
		if ( isset($this->settings['req']['dwndiff'])  ) {return $this->downloadDifference();}
	}

    /**
     * helper setMenueStatus
     *
     * @return void
     */
    public function setMenueStatus() {
		if( !file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv') ){
			$this->disabledActions['viewcloud'] = 999;
			$this->disabledActions['vergleich'] = 999;
			$this->disabledActions['export'] = 999;
		}else{
			if( isset($this->disabledActions['viewcloud']) ) unset( $this->disabledActions['viewcloud']);
			if( isset($this->disabledActions['vergleich']) ) unset( $this->disabledActions['vergleich']);
 			if( isset($this->disabledActions['export']) ) unset( $this->disabledActions['export']);
		}
	}

    /**
     * action dateienAction
     * file handling
     *
     * @return void
     */
    public function dateienAction() {

		$this->view->assign( 'configAction' , 'settings_cat_output' );
		
		$authUser = $this->authService->getAuthUsersRecordset();
		$minAffored4TableEditor = $this->allControllersObjects['configuration']->getAccessRule('tableeditor');
		$allowTableEditor  = ( strlen($minAffored4TableEditor) && isset($authUser['group']) && $authUser['group'] >= $minAffored4TableEditor ) ? TRUE : FALSE;
		
		$minAffored4SqlEditor = $this->allControllersObjects['configuration']->getAccessRule('database');
		$allowSqlEditor  = ( strlen($minAffored4SqlEditor) && isset($authUser['group']) && $authUser['group'] >= $minAffored4SqlEditor ) ? TRUE : FALSE;
		
		$allowedExtensions = $this->csvService->avaiableExtensions();
		
		// form-details for fileupload
		$formSettings = array(
			'dir' => isset($this->settings['req']['dir']) ? $this->settings['req']['dir'] : 'users' , 
			'filename'=>'userfile' , 
			'use_quota_list' => $this->settings['use_quota_list'] , 
			'use_delete_list' => $this->settings['use_delete_list']
		);
		
		// START EXECUTE jobs depending on input
		$page = '';
		if ( isset($this->settings['req']['dwnfil']) ){
			// download calculated list of local users mentioned to upload to cloud
			$dataUtility = new \Drg\CloudApi\Utility\DataUtility($this->settings);
			$sumDB = $dataUtility->readSubstractedLocalUsersFiles($this->settings['req']['calcFile']);
			return $this->csvService->downloadArrayAsSpreadsheet( $sumDB , 'uploadToCloud.' . $this->settings['download_format'] ); 
		
		}elseif(isset($this->settings['req']['dwn']) && isset($this->settings['req']['dir']) ){
			// download raw csv file
			$shortname = $this->settings['req']['dwn'];
			if( 'csv' != strtolower( pathinfo( $shortname , PATHINFO_EXTENSION ) ) ) $shortname = pathinfo( $shortname , PATHINFO_FILENAME ) . '.csv';
			$filename = $this->settings['dataDir'] . 'local/' . $this->settings['req']['dir'] . '/' . $shortname ;
			return $this->csvService->downloadCsvFileAsSpreadsheet($filename);
		
		}elseif(isset($this->settings['req']['ok']) && isset($this->settings['req']['dir']) ){
			// view raw csv file
			$shortname = $this->getFirstArrayKey($this->settings['req']['ok']);
			if( 'csv' != strtolower( pathinfo( $shortname , PATHINFO_EXTENSION ) ) ) $shortname = pathinfo( $shortname , PATHINFO_FILENAME ) . '.csv';
			$filename = $this->settings['dataDir'] . 'local/' . $this->settings['req']['dir'] . '/' . $shortname ;
			if( file_exists($filename) ){
				$arrToView = $this->csvService->csvFile2array( $filename );
                $page .= '<h2 style="margin-top:0;">' .$shortname.'</h2>' . $this->view->widgets->htmlTableWithPager( $arrToView );
			}
		
		}elseif(isset($this->settings['req']['ok'])  && isset($this->settings['req']['ok']['rename'])  && isset($this->settings['req']['rename']) ){
			foreach( $this->settings['req']['rename'] as $shortname => $newShortName ){
					$newname = $this->settings['dataDir'] . 'local/'  .  dirname($shortname) . '/' . $newShortName . '.csv';
					$filename = $this->settings['dataDir'] . 'local/'  .$shortname . '.csv' ;
					if( !file_exists($filename) ) continue;
					if( file_exists($newname) ) continue;
					
					$this->debug[ 'renamed:' . basename($shortname) . '=>' . $newShortName] =  ( rename( $filename , $newname ) ? 'OK' : 'failed'). ', ';
			}
			
		}elseif(isset($this->settings['req']['delete']) && isset($this->settings['req']['dir']) ){
			// delete csv file
			$filename = $this->settings['dataDir'] . 'local/' . $this->settings['req']['dir'] . '/' . $this->settings['req']['delete'] ;
			if( file_exists($filename) ) unlink($filename);
		
		}elseif(isset($this->settings['req']['dir']) ){
			// handle uploads of csv-files
			// if name like group_quota.csv or delete_list.csv then choose related folder regardless of folder-choise
			// if users-folder is choosen dont rename. [DELETE:then operator can rename the csv-file (not the extension!)]
			// if other than user-folder is choosen then rename the csv-file depending on choosen folder: group_quota.csv or delete_list.csv
			
			// handle uploads of spreadsheets: 
			// if name of file (or worksheet[DELETE: or new filename]) is like group_quota or delete_list then choose related folder for the first resulting file, regardless of folder-choise
			// if user-folder is choosen dont rename. [DELETE: and a new filename given, first resulting file gets the new filename, for more sheets the worksheets name gives the file the name. ]
			// if other than user-folder is choosen then rename the first resulting csv-file depending on the choosen folder: group_quota.csv or delete_list.csv
			// if more than one worksheets existing, the worksheets name gives the file the name.
			$aFileToFolders = array(
					pathinfo( $this->settings['table_conf']['group_quota']['force_filename'] , PATHINFO_FILENAME )=>'quota',
					pathinfo( $this->settings['table_conf']['delete_list']['force_filename'] , PATHINFO_FILENAME )=>'delete',
			);
			$aFolderFile = array_flip($aFileToFolders);
			$newFilename = isset($aFolderFile[$this->settings['req']['dir']]) ? $aFolderFile[$this->settings['req']['dir']] : '';
			
			
			$uploadedFile = $this->fileHandlerService->handleUpload( $this->settings['dataDir'] . 'local/' , $formSettings['filename'] , $newFilename , $allowedExtensions );
			if($uploadedFile){
				if( isset($this->fileHandlerService->debug['filehandler-handleUpload']) ) unset($this->fileHandlerService->debug['filehandler-handleUpload']);
				$spoutRs = $this->csvService->file2arrays($this->settings['dataDir'] . 'local/' . $uploadedFile);
				$fileBase = pathinfo( $uploadedFile , PATHINFO_FILENAME );
				
				foreach( $spoutRs as $sheet => $convertedArrayFromFile ){
							$formattedStringFromArray = $this->csvService->arrayToCsvString( $convertedArrayFromFile , $this->settings['sys_csv_delimiter'] , $this->settings['sys_csv_enclosure'] );

							if( isset($aFileToFolders[$sheet]) ) {
                                // folder given by sheetname
                                $sheetGivenFolder = $aFileToFolders[$sheet] . '/' . $sheet . '.csv' ;
                                
							}elseif(isset($aFileToFolders[$fileBase]) && !isset($spoutRs[$fileBase])){
                                // folder given by filename
                                $sheetGivenFolder = $aFileToFolders[$fileBase] . '/' . $fileBase . '.csv' ;
                                
							}else{
                                // folder given by manually choose from selector
                                $file = isset( $aFolderFile[$formSettings['dir']] ) ? $aFolderFile[$formSettings['dir']] : $fileBase;
                                $sheetGivenFolder   = $formSettings['dir'] . '/' . $file . '.csv';
							}
							
							$bytesWritten = file_put_contents( $this->settings['dataDir'] . 'local/'  .  $sheetGivenFolder , $formattedStringFromArray );
							$size = $bytesWritten < 1024 ? number_format($bytesWritten , 0 , '' , "'") . ' B' : number_format($bytesWritten/1024 , 0 , '' , "'") . ' KiB';
							$displayIfDebugMin = $bytesWritten ? 2 : 1;
							if( $this->settings['debug'] >= $displayIfDebugMin ) $this->debug['dateienAction-postUpload-'.$sheet] =  $sheetGivenFolder . ' done('.($bytesWritten ? $size : 'failed' ).') ';
				}
				unlink($this->settings['dataDir'] . 'local/' . $uploadedFile);
			}
		}
		
		if( isset($this->settings['req']['ok']['viewfil']) ){
			// view calculated list of local users mentioned to upload to cloud
			// store checkbox values in files-list !
			$dataUtility = new \Drg\CloudApi\Utility\DataUtility($this->settings);
 			$page = '<h2 style="margin-top:0;">uploadToCloud.csv</h2>';
 			$reqCalcFile = $dataUtility->readSubstractedLocalUsersFiles( isset($this->settings['req']['calcFile']) ? $this->settings['req']['calcFile'] : array());
 			$page .= $this->view->widgets->htmlTableWithPager( $reqCalcFile );
		
		}elseif( isset($this->settings['req']['samples']) ){
			// create sample of selected csv-table
			$installerService = new \Drg\CloudApi\Services\InstallerService($this->settings);
			$table = $this->getFirstArrayKey($this->settings['req']['samples']);
 			$installerService->createSampleFiles( $table );
		}
		
		$this->view->assign( 'page' , $page );
		// END EXECUTE jobs depending on input
		
		// OUTPUT //
		// array for button(s) to create example-files (display for each possible and not existing file a button)
		if( $this->settings['create_default_files'] ){
			$potentialSampleTables = array();
			foreach( $this->settings['table_conf'] as $tablename => $qSet ){
					if( !isset($qSet['samples']) || count($qSet['samples']) <= 1 ) continue;
					if( !isset($qSet['samples'][0]) || !is_array($qSet['samples'][0]) ) continue;
					if( !isset($qSet['location']) ) $qSet['location'] = $this->settings['localusers'];
					if( !$this->settings['use_quota_list'] && strpos($qSet['location'] , 'quota') ) continue;
					if( !$this->settings['use_delete_list'] && strpos($qSet['location'] , 'delete') ) continue;
					$filePath = $this->settings['dataDir'] . $qSet['location'] . $tablename . '.csv';
					if( !file_exists($filePath) ) $potentialSampleTables[$tablename] = ' <input class="csv" type="submit" name="samples['.$tablename.']" value="'.$tablename.'.csv" /> ';
			}
		}
		// create arra< for sql-request files
		if( $this->settings['enable_sql'] && $this->settings['display_sql'] ){
				$this->view->models->model = new \Drg\CloudApi\Models\SqlconnectModel( $this->settings );
				$this->view->models->model->initiate();
				if( isset($this->settings['req']) && isset($this->settings['req']['execute']) && is_array($this->settings['req']['execute']) ){
						$key = $this->getFirstArrayKey($this->settings['req']['execute']);
						$this->view->models->model->executeAction( $key );
						$result = implode( ', ' , $this->view->models->model->debug );
						if( $this->settings['debug'] >=2 ) $this->debug['databaseAction sql-query-result'] = $key . ':' . $result;
				}
				$modelsRecordsets = $this->view->models->model->getRecordsets();
		}
		
		// create sources div and checkboxes
		$pageTitle = '<H1>##LL:userfiles.title##</H1>';

		$buttNames = array('upload');
		if( $this->settings['create_default_files'] && isset( $potentialSampleTables ) && count( $potentialSampleTables )) $buttNames[] = 'sample';
		if( $this->settings['enable_sql'] && $this->settings['display_sql'] && ( $allowSqlEditor || is_array($modelsRecordsets) && count($modelsRecordsets) ) ) $buttNames[] = 'sql';
		
		$divIsSelected = array();
		// set default if no source selected
		if( count( $buttNames ) > 1  ){
			foreach( $buttNames as $bt ) {
					if( isset($this->settings['req']['settings']['source_'.$bt])) {
						$divIsSelected[$bt] = $this->settings['req']['settings']['source_'.$bt];
					}else{
						$divIsSelected[$bt] = $this->settings['source_'.$bt];
					}
			}
		}
		if(!count($divIsSelected)){
			$divIsSelected[$buttNames[0]] = 1;
			$this->settings['req']['settings']['source_'.$buttNames[0]] = $buttNames[0];
		}
		// checkboxes for display-options
		if( count( $buttNames ) > 1  ){
			$pageTitle .= '<H2>'.ucFirst($this->view->widgets->getLabel('sources')).'</H2>';
			$pageTitle .= '<div style="padding-bottom:3px;">'.ucFirst($this->view->widgets->getLabel('several_sources')).': &nbsp;  ';
			foreach( $buttNames as $bt ) {
					$js = 'var selObj = document.getElementById( \'div_'.$bt.'\' );if(this.checked){selObj.classList.remove(\'fadingOut\');selObj.classList.add(\'fadingIn\');}else{selObj.classList.remove(\'fadingIn\');selObj.classList.add(\'fadingOut\');};';
					$name = 'settings[source_'.$bt.']';
					$preselectValue = $divIsSelected[$bt];
					$arguments = [ 'onChange' => $js , 'slider'   => 'round slider' , 'label'    => $this->view->widgets->getLabel('source_'.$bt) ];
					$pageTitle .= $this->view->widgets->objCheckbox( $name , $preselectValue , $arguments ) . ' &nbsp; ' ;
			}
			$pageTitle .= '</div>';
		}else{
			$pageTitle .= '<H2>'.ucFirst($this->view->widgets->getLabel('source_upload')).'</H2>';
		}
		
		// create upload-formular
		$uploadButton = '<div id="div_upload" class="' . ( count( $buttNames ) > 1 ? 'bordertop' : '' ) .' ' . ( $divIsSelected['upload'] ? 'fadingIn' : 'fadingOut' ) .'" >';
		$uploadButton .= $allowedExtensions . ' ' . $this->view->widgets->objFileUpload( $formSettings['filename'] );
		$uploadButton .= $this->view->widgets->htmUploadFolderSelector( $formSettings ) ;
		$uploadButton .= '</div>' ;
		
		// button(s) to create example-files (display for each possible and not existing file a button)
		$samplesButtons = '';
		if( isset( $potentialSampleTables ) && count( $potentialSampleTables ) ) {
				$samplesButtons .= '<div id="div_sample" class="bordertop ' . ( $divIsSelected['sample'] ? 'fadingIn' : 'fadingOut'  ) .'" style="">';
				$samplesButtons .= '##LL:load_default_data##: ';
				$samplesButtons .= implode( '&nbsp;' , $potentialSampleTables ) . ' &nbsp; ';
				if( $allowTableEditor ) $samplesButtons .= ' <input class="small" type="submit" name="newact[tableeditor]" title="##LL:newact.tables.title##" value="&rarr; ##LL:newact.tables.label##..." /> &nbsp; ';
				$samplesButtons .= ' </div>';
		}
		
		// button(s) to create fresh files from sql-statement (display for each possible and not existing file a button)
		$database = '';
		if( $this->settings['enable_sql'] && $this->settings['display_sql'] ){
 				if( is_array($modelsRecordsets) && count($modelsRecordsets) ){
						foreach( $modelsRecordsets as $key => $row){
							$tablename = empty($row['filename']) ? $key : $row['filename'];
							$database .= '&nbsp; <input class="csv" type="submit" name="execute['.$key.']" value="'.$tablename.'.csv" /> ';
						}
				}
 				if( $allowSqlEditor ) $database .= '&nbsp; <input class="small" type="submit" name="newact[database]" value="&rarr; ##LL:models.name.sql_querys##..." />';
 				if( !empty($database) ) $database = '<div id="div_sql" class="bordertop ' . ( $divIsSelected['sql'] ? 'fadingIn' : 'fadingOut'  ) .'" style="">##LL:file_from_sql## ' . $database . ' </div>';
		}
		
		$uploadButton .= $samplesButtons . $database;
		// hold the timeout value for other actions
		$uploadButton .= '<input type="hidden" name="timeout" value="##timeout##" />';
		
		$this->view->assign( 'text' , $pageTitle . '<div style="padding-top:5px;">'.$uploadButton . '</div>' );
		
		// create Fileslist or a message if no files
		$sFilesList = '';
		$sFilesList .= '<div style="width:auto;margin-top:0px;border:0px solid #aaa;border-top:2px solid #000;padding:5px;">';
		$sFilesList .= '<h2>##LL:newact.dateien.value##</h2>';
		// read directories
		$aDirs = $this->fileHandlerService->getDir( $this->settings['dataDir'] . 'local/' , 8 );
		$settings = $this->settings;
		$settings['allowTableEditor'] = $allowTableEditor;
		$aAllowedExtensions = array_flip( explode( ',' , $allowedExtensions ) );
		if( !isset( $aAllowedExtensions[ $settings['download_format'] ] ) ) $settings['download_format'] = 'csv';
		if( isset($aDirs['fil']) && is_array($aDirs['fil']) ){
			$aCsvFiles = array();
			foreach( $aDirs['fil'] as $filename => $shortname ) if( isset( $aAllowedExtensions[pathinfo($filename,PATHINFO_EXTENSION)] ) ) $aCsvFiles[$filename] = $shortname;
			if( count($aCsvFiles) ){
				ksort($aCsvFiles);
				// menue: create list with links for output
				$filesList = $this->view->widgets->htmlFileTable( $aCsvFiles , $settings );
				if($filesList){
				$sFilesList .= $filesList;
				$sFilesList .= '<p style="margin:16px 0 10px 0;">';
				$sFilesList .= '##LL:calculated_data## &rarr; ';
				$sFilesList .= ' <input class="" type="submit" name="ok[viewfil]" value="##LL:files.display##" /> ';
				$sFilesList .= '<input class="" type="submit" name="dwnfil" value="##LL:files.download##" />';
				$sFilesList .= '</p> ';
				}else{
					$sFilesList .= '(##LL:none##)'; 
				}
			}else{ 
				$sFilesList .= '(##LL:none##)'; 
			}
		}else{ 
			$sFilesList .= '(##LL:none##)'; 
		}
		$sFilesList .= '</div>';
		
		$this->view->assign( 'button' , $sFilesList );
		
		return true;
	}

    /**
     * action clouduserAction
     * downloads informations from cloud
     *
     * @return void
     */
    public function clouduserAction() {
		
		$URL =  isset($_SERVER['REQUEST_SCHEME']) && !empty($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] :'http';
		$URL .=  '://' . $_SERVER['HTTP_HOST'] . '' . $_SERVER['PHP_SELF'];
		
		$text = '<h1>'. ($this->settings['download_details'] == 1 ? '##LL:detailled## ' : '').'##LL:create_userlist##</h1>';
		$button = '';
		
		$deleteButton = '';
		$realDeleteButton = ' <p>##LL:call_all_data.label##: <input title="##LL:call_all_data.title##" type="submit" name="real_delete" value="##LL:call_all_data.value##!" /></p>' ;
		
		if( isset($this->settings['req']['delete']) && $this->settings['req']['delete'] ) {
			$result = $this->fileHandlerService->cleanDir( $this->settings['dataDir'] . 'api/' , 1 , 'csv,json' );
			if( $this->settings['debug'] >=2 ) $this->debug[ 'delete_csv' ] = 'csv+json deleted: '.$result;
			$text .= '<p>##LL:deleted##</p>';
			$deleteButton = $realDeleteButton;
			
		}elseif( isset($this->settings['req']['real_delete']) && $this->settings['req']['real_delete'] ) {
 			$result = $this->fileHandlerService->cleanDir( $this->settings['dataDir'] . 'api/' , 1 , 'xml,csv,json,txt' );
 			if( $this->settings['debug'] >=2 ) $this->debug[ 'delete_xml_csv' ] = 'xml+csv+json+logfile deleted: '.$result;
			$text .= '<p>##LL:deleted_all##</p>';

		}elseif( file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv') ){
			// go to next step: vergleich stop refresh if not autostart!
			if( !isset($this->settings['req']['autostart']) || empty($this->settings['req']['autostart']) ){
				if( isset($this->settings['req']['do']['ok']) ) unset($this->settings['req']['do']['ok']);
			}
			return (  $this->settings['edit_joblist'] == 0 ) ? 'viewcloud' : 'vergleich';
		
		}elseif(
			(isset($this->settings['req']['do']['ok']) || (isset($this->settings['req']['autostart']) && $this->settings['req']['autostart'])) && 
			!file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv')
		){
			$this->readCloudUtility->connectorService->apiCalls = 1;
			$settings = $this->settings;
			$settings['exectimeout'] = $settings['actualTimeout'];
			$cliTasksUtility = new \Drg\CloudApi\Utility\CliTasksUtility( $settings );
			$aCloudUsersAndAttributes = $cliTasksUtility->do_import_cloud( $this->readCloudUtility , $this->settings['actualTimeout'] );

			$nextActionIfFullfilled = (isset($this->settings['req']['autostart']) && $this->settings['req']['autostart']) ? 'export' : 'vergleich';
			
			if( $aCloudUsersAndAttributes['status']['totalPercentage'] == 100 ) return $nextActionIfFullfilled;
			
			$strFulfilled = array( false=>'##LL:called.partly##' , true=>'##LL:called.completely##');
			$text .= '<p>' . $strFulfilled[ $aCloudUsersAndAttributes['status']['totalPercentage'] == 100 ] .': '. floor( $aCloudUsersAndAttributes['status']['totalPercentage'] ) .'% <br />##LL:duration_sec##: '. (round($aCloudUsersAndAttributes['status']['elapsedTime'] , 6 )) .'</p>';
			$deleteButton = ' <input title="##LL:call_all_data.title##" type="submit" name="delete" value="##LL:rebuild_list##..." />';
			if( $this->settings['debug'] >=1  && is_array( $cliTasksUtility->debug ) && count( $cliTasksUtility->debug ) ){
				foreach($cliTasksUtility->debug  as $errKey => $debug ) {
					$this->debug[$errKey] = $debug;
				}
			}
			if( !is_array($aCloudUsersAndAttributes) ) {
 				$this->view->assign( 'configAction' , 'settings_cat_connection' );
				$text .= '<p><strong>CONNECTION ERROR</strong></p><p><strong> ##LL:check_configuration##.</strong></p>';
			}
		}else{
			$text .= '<p>##LL:import_not_done##.</p>';
			$deleteButton = $realDeleteButton;
		
		}
		
		$tmpSetting = $this->settings;
		$actRefresh = isset($this->settings['req']['refresh']) && !empty($this->settings['req']['refresh']) ? $this->settings['req']['refresh']: 15;
		$tmpSetting['refresh'] = $actRefresh;
		$button = $this->view->widgets->htmTimeoutSelector( $tmpSetting , '##LL:call##' );
		$button .= $deleteButton;
		if(
			(!isset($aCloudUsersAndAttributes) || is_array($aCloudUsersAndAttributes) ) && 
			isset($this->settings['req']['do']['ok']) || 
			isset($this->settings['req']['real_delete']) || 
			(isset($this->settings['req']['autostart']) && $this->settings['req']['autostart']) 
		){
			if(
				isset($aCloudUsersAndAttributes['fullfilled']) && $aCloudUsersAndAttributes['fullfilled'] &&
				isset($this->settings['req']['autostart']) && $this->settings['req']['autostart']
			){
				$nextAction = 'export&autostart=1';
			}else{
				$nextAction = 'clouduser';
				if( isset($this->settings['req']['autostart']) && $this->settings['req']['autostart'] ) {
					$nextAction .= '&autostart=1';
				}
			}
			$text .= '<p><i>##LL:next_start## <span id="refreshwatch">'.$actRefresh.'</span> ##LL:seconds##.</i><p>';
			$js = $this->view->javaScript->countdown( 'refreshwatch' );
			$this->view->append( 'FOOTER' , $js );
			$reload = '<meta http-equiv="refresh" content="'.$actRefresh.'; url='.$URL.'?act=' . $nextAction . '&do[ok]=ok&timeout='.$this->settings['actualTimeout'].'&refresh='.$actRefresh.'" />';
			$this->view->append( 'HEAD' , $reload );
		}
		
		$this->view->assign( 'text' , $text );
		$this->view->assign( 'button' , $button );
		
		if( $this->settings['debug'] >=1  && is_array( $this->readCloudUtility->debug )  && count( $this->readCloudUtility->debug ) ){
			foreach($this->readCloudUtility->debug  as $errKey => $debug ) $this->debug[$errKey] = $debug;
		}
		return true;
	}

    /**
     * action viewcloudAction
     * Used to show cloud-Data without edit-links
     * 
     * Redirected to this method if called action 'clouduserAction'
     * while settings-variables 
     *  - 'download_details' AND
     *  - 'edit_joblist' 
     * are disabled
     *
     * @return void
     */
    public function viewcloudAction() {
		
		// download list of useraccounts stored in cloud
		if ( isset($this->settings['req']['dwn']) && file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv') ){
			return $this->csvService->downloadCsvFileAsSpreadsheet( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv' );
		}

		// execute DELETE-actions
		if ( isset($this->settings['req']['real_delete']) && $this->settings['req']['real_delete'] ) {
			$this->debug[ 'viewcloudAction->delete_xml_csv' ] = 'xml+csv+json+logfile deleted: '.$this->fileHandlerService->cleanDir( $this->settings['dataDir'] . 'api/' , 1 , 'xml,csv,json,txt' );
			unset($this->settings['req']['real_delete']);
		}elseif( !isset($this->settings['req']['autostart']) || empty($this->settings['req']['autostart'])  ){
			$this->debug[ 'viewcloudAction->delete_csv' ] = 'csv+json deleted: '.$this->fileHandlerService->cleanDir( $this->settings['dataDir'] . 'api/' , 1 , 'csv,json' );
		}
		
		// after deletion clouduser import
		if ( isset($this->settings['req']['viewcloudtimeout']) && $this->settings['req']['viewcloudtimeout'] >= 0.01 ) $this->settings['viewcloudtimeout'] = $this->settings['req']['viewcloudtimeout'];
		$this->readCloudUtility->connectorService->apiCalls = 1;
		$cliTasksUtility = new \Drg\CloudApi\Utility\CliTasksUtility( $this->settings );
		$aCloudUsersAndAttributes = $cliTasksUtility->do_import_cloud( $this->readCloudUtility , $this->settings['viewcloudtimeout'] );

		// try once again if clouduser import not complete
		if( $aCloudUsersAndAttributes['status']['totalPercentage'] < 100 ) {
			$this->debug['viewcloudAction->do_import_cloud'] = $aCloudUsersAndAttributes['status']['totalPercentage'].'% in ' . $this->settings['viewcloudtimeout'] . ' Sekunden, starte einen erneuten Versuch. ';
			$aCloudUsersAndAttributes = $cliTasksUtility->do_import_cloud( $this->readCloudUtility , $this->settings['viewcloudtimeout'] );
			if( $aCloudUsersAndAttributes['status']['totalPercentage'] < 100 ) {
				$this->debug['viewcloudAction->retry_do_import_cloud'] = $aCloudUsersAndAttributes['status']['totalPercentage'].'%, wechsle zu Aktion Clouduser, versuche autostart... ';
				$URL =  isset($_SERVER['REQUEST_SCHEME']) && !empty($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] :'http';
				$URL .=  '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
				$reload = '<meta http-equiv="refresh" content="'.$this->settings['viewcloudtimeout'].'; url='.$URL.'?act=clouduser&do[ok]=ok&timeout='.$this->settings['actualTimeout'].'&refresh='.$this->settings['viewcloudtimeout'].'" />';
				$this->view->append( 'HEAD' , $reload );
				return  'clouduser';
			}
		}
		if(isset($this->settings['req']['autostart']) && $this->settings['req']['autostart']) return 'export' ;

		$arrToView = $this->readCloudUtility->readFromFile_CloudUsersAndAttributes();
		$fldsGrps = $this->settings['download_details'] ? 'ENABLED,ID,DISPLAYNAME,EMAIL,FREE,USED,TOTAL,RELATIVE,QUOTA' : 'ID,DISPLAYNAME';
		$groupAmount = $this->settings['group_amount'];
		for( $z=1 ; $z<=$groupAmount ; ++$z ){
				$fldsGrps .= ',grp_' . $z ;
		}
		if ( isset($this->settings['req']['do']['viewDifference'])) {
 			$page = '<h2> ##LL:difference##.'.$this->settings['download_format'].'  </h2>' . $this->reportDifference();
		}else{
			$page = ' ' . $this->view->widgets->htmlTableWithPager( $arrToView , $fldsGrps );
		}
		$text = '<h1>##LL:clouduser_list_title##</h1>';
		$text .= '<h2>UserAttributes.'.$this->settings['download_format'].'</h2>';
		$text .= '<input class="" type="submit" name="dwn" value="Download" /> ';
		$text .= ' <input class="" title="##LL:call_all_data.title##" type="submit" name="real_delete" value="##LL:rebuild_list##" />';
 		$cloudUserButtons = '';
		$cloudUserButtons .= '  <label>##LL:difference## ##LL:calculated_data## ';
		$cloudUserButtons .= '<input class="" type="submit" name="do[viewDifference]" value="##LL:display##" /></label> ';
		$cloudUserButtons .= '<input class="" type="submit" name="dwndiff" value="##LL:files.download##" /> ';
// 		$text .=$cloudUserButtons;
		
		$this->view->assign( 'text' , $text );
		$this->view->assign( 'page' , $page );

	}

    /**
     * action vergleichAction
     * Used to control, which Data should be updated in cloud
     * 
     * Redirected to this method if called action 'clouduserAction' (import)
     * while file userAttributes.csv exists but not shoud be deleted right now
     *
     * @return void
     */
    public function vergleichAction() {
		// delete-button pressed, execute DELETE-actions and go to clouduser to import
		if ( isset($this->settings['req']['delete']) && $this->settings['req']['delete'] ) {
			return 'clouduser';
		}

		// if not imported all, go to clouduser to import
		if( !file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv') ) {
			return 'clouduser';
		}
		
		// if user dont want to edit job-lists then evaluate viewcloudAction
		if ( $this->settings['edit_joblist'] == 0 ) {
			return 'viewcloud';
		}

		// download list of useraccounts stored in cloud
		if ( isset($this->settings['req']['dwn']) && file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv') ){
			return $this->csvService->downloadCsvFileAsSpreadsheet( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv' );
		}

		$text = '';
		$button = '';
		$page = '';
		
		$cloudUsers = $this->readCloudUtility->readFromFile_CloudUsersAndAttributes();
		
		// COMPARE starts here
		$sumDB = $this->readCloudUtility->readLocalUsersFiles( $this->settings['localusers'] , TRUE );

		$jobsEditorUtility = new \Drg\CloudApi\Utility\JobsEditorUtility($this->settings);
		
		$db = $jobsEditorUtility->compareDatabaseWithClouddata( $sumDB , $cloudUsers );
		
        $xlsButton = '<a href="?act=vergleich&xls[comparedLists]" class="xlsx" >comparedLists.xlsx</a> (alles) ';
        
		if ( isset($this->settings['req']['ok']) && $this->settings['req']['ok'] ) {
            // update editorJobs with button Speichern	eg $this->settings['req']['ok'][UsersMaybeObsolete]
			$aOks = array_keys($this->settings['req']['ok']);
			if( $aOks ) {
					$listName = array_pop($aOks);
					$jobsEditorUtility->updateJoblist($listName);
                    $strCounter = isset($db[$listName]) ? count($db[$listName]) : 0;
                    if( $strCounter ) $page .= '<p>' . $xlsButton . '<br /><a href="?act=vergleich&xls['.$listName.']" class="xlsx" >'.$listName.'.xlsx</a> ('.$strCounter.' Zeilen) </p>';
					$page .= $jobsEditorUtility->JobsEditor( $db , $listName );
			}
			
		}elseif ( isset($this->settings['req']['do']['viewCloudUsers'])) {
            // view clouduser if button clicked
			$fields = $this->settings['download_details'] ? 'ENABLED,ID,DISPLAYNAME,EMAIL,FREE,USED,TOTAL,RELATIVE,QUOTA' : 'ID,DISPLAYNAME';
			for( $z=1 ; $z<=$this->settings['group_amount'] ; ++$z ) $fields .= ',grp_' . $z ;
			$page .= '<h2> UserAttributes.'.$this->settings['download_format'].'  </h2>' .$this->view->widgets->htmlTableWithPager( $cloudUsers , $fields );
		}elseif ( isset($this->settings['req']['do']['viewDifference'])) {
            // view differences if button clicked
 			$page .= '<h2> ##LL:difference##.'.$this->settings['download_format'].'  </h2>' . $this->reportDifference();
		}else{
            $page .= '<p>' . $xlsButton . '</p>';
		}

		$editLinks = '<div style="float:left;width:auto;">';
		$editLinks.= $jobsEditorUtility->getActionLinks( $db );
		$editLinks.= '</div>';
		
		$pagetitle = '<h1>##LL:newact.clouduser.value##</h1>';
		
		$cloudUserButtons = '<div style="float:left;width:auto;margin-left:20px;">';
		
		$cloudUserButtons .= '<h3 style="margin:3px 0 8px 0;" title="'. ($this->settings['download_details'] == 1 ? '##LL:detailled_clouduser_list_title##' : '##LL:clouduser_list_title##').' &larr; Import">'. ($this->settings['download_details'] == 1 ? '##LL:detailled_clouduser_list_title##' : '##LL:clouduser_list_title##').'</h3>';
		$cloudUserButtons .= '<p style="margin:0;">';
		$cloudUserButtons .= '<input class="" type="submit" name="do[viewCloudUsers]" value="##LL:display##" /> ';
		$cloudUserButtons .= '<input class="" type="submit" name="dwn" value="##LL:files.download##" /> ';
		$cloudUserButtons .= '<input class="" title="##LL:call_all_data.title##" type="submit" name="delete" value="##LL:rebuild_list##..." /> ';
		$cloudUserButtons .= '</p>';
		$cloudUserButtons .= '</div>';
		if( $this->downloadDifference( TRUE ) ){
			$cloudUserButtons .= '<div style="float:left;width:auto;margin-left:20px;">';
			$cloudUserButtons .= '<h3 style="margin:3px 0 8px 0;" title="##LL:difference## &rarr; Export ">##LL:difference##<span style="font-size:100%;font-weight:normal;"> ##LL:calculated_data##</span></h3>';
			$cloudUserButtons .= '<p style="margin:0;"> ';
			$cloudUserButtons .= '<input class="" type="submit" name="do[viewDifference]" value="##LL:display##" /> ';
			$cloudUserButtons .= '<input class="" type="submit" name="dwndiff" value="##LL:files.download##" /> ';
			$cloudUserButtons .= '</p>';
			$cloudUserButtons .= '</div>';
		}


		$this->view->assign( 'text' , $pagetitle );
		$this->view->assign( 'button' , $editLinks . $cloudUserButtons .'<div style="clear:left;"></div>' );
		$this->view->assign( 'page' ,$page );
		
		if( $this->settings['debug'] >=1  && is_array( $jobsEditorUtility->debug ) && count( $jobsEditorUtility->debug ) ){
			foreach($jobsEditorUtility->debug  as $errKey => $debug ) $this->debug[$errKey] = $debug;
		}

		if ( isset($this->settings['req']['xls']) ){
            $aFlatTable = [];
			$aXls = array_keys($this->settings['req']['xls']);
			$tabName = array_shift($aXls);
            foreach( $db as $listType => $pDb ){
                foreach( $pDb as $ix => $row ){
                    foreach( $row as $iF => $value ){
                        if( is_array($value) && count($value) ){
                            asort($value);
                            foreach($value as $nm => $ky) {
                                $aFlatTable[$listType][$ix][$ky] = $nm;
                            }
                        }else{
                            $aFlatTable[$listType][$ix][$iF] = $value;
                        }
                    }
                }
            }
            if( isset($db[$tabName]) ){
                $this->csvService->downloadArrayAsSpreadsheet( $aFlatTable[$tabName] ,  $tabName.'.xlsx' );
			}else{
                $this->csvService->downloadArrayAsSpreadsheet( $aFlatTable ,  'comparedLists.xlsx' );
			}
		}
		return true;
	}

    /**
     * action exportAction
     * export data to cloud or delete users and/or groups in cloud
     *
     * @return void
     */
    public function exportAction() {
		
		// delete-button pressed, execute DELETE-actions and go to clouduser to import
		if (  !$this->settings['edit_joblist'] && isset($this->settings['req']['delete']) && $this->settings['req']['delete'] ) {
			return 'clouduser';
		}

		if( !file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv') ) {
			return 'clouduser';
		}
		
		$text = '<H1 style="margin-bottom:0;">##LL:export_title##</H1>';
  		$pg = '';
		$button = '';
		$reload = '';
		
		$URL =  isset($_SERVER['REQUEST_SCHEME']) && !empty($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] :'http';
		$URL .=  '://' . $_SERVER['HTTP_HOST'] . '' . $_SERVER['PHP_SELF'];
		
		$actRefresh = isset($this->settings['req']['refresh']) ? $this->settings['req']['refresh']: 15;

		if ( $this->settings['edit_joblist']==0 && !isset($this->settings['req']['do']['ok']) && !isset($this->settings['req']['autostart']) ) {
			// if no details and no edit then refresh the view by deleting check-lists json and calclated lists csv
			$this->debug[ 'delete_csv_json' ] = 'csv+json deleted: '.$this->fileHandlerService->cleanDir( $this->settings['dataDir'] . 'api/' , 1 , 'csv,json' );
			// run API-download once to start the rebuild-process
			$this->readCloudUtility->connectorService->apiCalls = 1;
			$cliTasksUtility = new \Drg\CloudApi\Utility\CliTasksUtility( $this->settings );
			$aCloudUsersAndAttributes = $cliTasksUtility->do_import_cloud( $this->readCloudUtility , 10 );
			// if after one call the percentage is not on 100% then try once again
			if( $aCloudUsersAndAttributes['status']['totalPercentage'] < 100 ) {
				$this->debug['exportAction_tryToImport_10sec'] = $aCloudUsersAndAttributes['status']['totalPercentage'].'%, retry. ';
				$aCloudUsersAndAttributes = $cliTasksUtility->do_import_cloud( $this->readCloudUtility , 10 );
				// if after the second call the percentage is not on 100% then switch to import-Action for manual import
				if( $aCloudUsersAndAttributes['status']['totalPercentage'] < 100 ) {
					$this->debug['exportAction_tryToImport_20sec'] = $aCloudUsersAndAttributes['status']['totalPercentage'].'%, goto clouduser. ';
					return  'clouduser';
				}
			}
		} 
		// the complete cloud-data is now avaiable
		// read files: read checklists, local and cloud-data from files, then compare checked data from cloud with local data
		$createJobsUtility = new \Drg\CloudApi\Utility\CreateJobsUtility( $this->settings );
		$destructCronjobsUtility = new \Drg\CloudApi\Utility\UpdateCloudUtility($this->settings);
		
		$db = $createJobsUtility->GetUpdateList();
		$is = array(
			'newUser' => isset($db['newUser']) && count($db['newUser']),
			'deleteUser' => isset($db['deleteUser']) && count($db['deleteUser']),
			'newGroup' => isset($db['newGroup']) && count($db['newGroup']),
			'deleteGroup' => isset($db['deleteGroup']) && count($db['deleteGroup']),
			'updateUserInfo' => isset($db['updateUserInfo']) && count($db['updateUserInfo']),
		);

		// timeout selector, autostart-header, EXPORT Action
		if( !$is['newGroup'] &&  !$is['updateUserInfo'] && !$is['deleteUser'] && !$is['deleteGroup'] && !$is['newUser']) {
			// nothing to export (anymore). If autostart is enabled then go to next step: documents else display message
			if( isset($this->settings['req']['autostart']) && $this->settings['req']['autostart'] ) {
					return 'documents';
			}
			// ... or display message 'nothing to do'
			$text .= '<h2>##LL:nothing_to_export.title##</h2>##LL:nothing_to_export.text##.';
			
		}else{
			if( !isset($this->settings['req']['do']['ok']) && ( !isset($this->settings['req']['autostart']) || !$this->settings['req']['autostart'] ) ) {
				// not startet, set on idle
				$text .= 'Cloud-Update <i>##LL:not_started##</i>';
			}else{
				// export action
				$autostart = isset($this->settings['req']['autostart']) && $this->settings['req']['autostart'] ? '&autostart=1' : '' ;
				$cliTasksUtility = new \Drg\CloudApi\Utility\CliTasksUtility( $this->settings );
				$result = $cliTasksUtility->do_export_cloud( $destructCronjobsUtility , $this->settings['actualTimeout'] );
				$text .= $result ? '<p>##LL:successfull##.</p>' : '<p>Export ##LL:interrupted##!</p>';
				$text .= 'Cloud-Update  <i>##LL:called_on## ' . date('H:i:s') .'</i>';
				if( $result == false || count($db) ){
					// read files again after export
					$db = $createJobsUtility->GetUpdateList();
					$is = array(
						'newUser' => isset($db['newUser']) && count($db['newUser']),
						'deleteUser' => isset($db['deleteUser']) && count($db['deleteUser']),
						'newGroup' => isset($db['newGroup']) && count($db['newGroup']),
						'deleteGroup' => isset($db['deleteGroup']) && count($db['deleteGroup']),
						'updateUserInfo' => isset($db['updateUserInfo']) && count($db['updateUserInfo']),
					);
					$text .= '<p><i>##LL:next_start## <span id="refreshwatch">'.$actRefresh.'</span> ##LL:seconds##.</i></p>';
					$js = $this->view->javaScript->countdown( 'refreshwatch' );
					$this->view->append( 'FOOTER' , $js );
					$maxrows = isset($this->settings['req']['maxrows']) ? '&maxrows='.$this->settings['req']['maxrows']:'';
					$reload = '<meta http-equiv="refresh" content="'.$actRefresh.'; url='.$URL.'?act=export&do[ok]=ok&timeout='.$this->settings['actualTimeout'].'&refresh='.$actRefresh.$maxrows.$autostart.'" />';
				}else{
					// export job done or nothin to do. If autostart is enabled then got to next step: documents, else disable autostart.
					if( isset($this->settings['req']['autostart']) && $this->settings['req']['autostart'] ) {
						return 'documents';
					}
					$this->settings['req']['autostart'] = 0;
				}

				if( $result == true && $this->settings['delete_apidata_after_export'] ){
						$autostart = isset($this->settings['req']['autostart']) && $this->settings['req']['autostart'] ? '&autostart=1' : '' ;
						$maxrows = isset($this->settings['req']['maxrows']) ? '&maxrows='.$this->settings['req']['maxrows']:'';
						$this->debug[ 'delete_xml_csv' ] = '##LL:successfull##, xml+csv+json+logfile ##LL:deleted##: '.$this->fileHandlerService->cleanDir( $this->settings['dataDir'] . 'api/' , 3 , 'xml,csv,json,txt' );
						$reload = '<meta http-equiv="refresh" content="'.$actRefresh.'; url='.$URL.'?act=clouduser&delete=ok&timeout='.$this->settings['actualTimeout'].'&refresh='.$actRefresh.$maxrows.$autostart.'" />';
				}
			}
			
			$settings = $this->settings;
			$settings['refresh'] = $actRefresh;
			$button .= $this->view->widgets->htmTimeoutSelector( $settings , 'Start' );
			$button .= 	' &nbsp; <label>##LL:difference##: <input class="" type="submit" name="dwndiff" value="##LL:files.download##" /> </label>';
		}
		
		// fill page After Export
		// detect largest table
		if( isset($db['updateUserInfo']) ) $counter['updateUserInfo'] = count($db['updateUserInfo']);
		if( isset($db['deleteUser']) ) $counter['deleteUser'] = count($db['deleteUser']);
		if( isset($db['newUser']) ) $counter['newUser'] = count($db['newUser']);

		// show each job-table on page
		if( $is['newGroup'] ) $pg .= '<h2>'.$this->view->widgets->getLabel('title.newGroup').'</h2>' . $this->view->widgets->objViewArrayContents( $db['newGroup'] );
		if( $is['deleteGroup'] ) $pg .= '<h2>'.$this->view->widgets->getLabel('title.deleteGroup').'</h2>' . $this->view->widgets->objViewArrayContents( $db['deleteGroup'] );
		if( $is['updateUserInfo'] ) $pg .= '<h2>'.$this->view->widgets->getLabel('title.updateUserInfo').'</h2>' . $this->view->widgets->htmlTableWithPager( $db['updateUserInfo']  );
		if( $is['newUser'] ) $pg .= '<h2>'.$this->view->widgets->getLabel('title.newUser').'</h2>' .  $this->view->widgets->htmlTableWithPager( $db['newUser']  );
		if( $is['deleteUser'] ) $pg .= '<h2>'.$this->view->widgets->getLabel('title.deleteUser').'</h2>' .  $this->view->widgets->htmlTableWithPager( $db['deleteUser']  );

		$this->view->append( 'HEAD' , $reload );
		$this->view->assign( 'refresh' , $actRefresh );
		$this->view->assign( 'text' ,  $text  );
		$this->view->assign( 'button' , $button );
		
		
		$this->view->assign( 'page' , $pg );
		$this->view->assign( 'configAction' , 'settings_cat_cronn' );
		
		// fill debugger
		if( $this->settings['debug'] >=2  && is_array( $createJobsUtility->debug )  && count( $createJobsUtility->debug ) ){
			foreach($createJobsUtility->debug  as $errKey => $debug ) $this->debug[$errKey] = $debug;
		}
	}

    /**
     * downloadDifference
     *
     * @param boolean $testIfPossible optional. returns boolean if true. default false
     * @return string
     */
    private function downloadDifference( $testIfPossible = FALSE ) {
		$aActionIcon = array( '&ndash;' => '-' , '&rarr;' => '>' , '&nbsp;' => ' '  );
			$createJobsUtility = new \Drg\CloudApi\Utility\CreateJobsUtility( $this->settings );
			$reportDb = $createJobsUtility->getDifference();
			if( empty($reportDb) ) return FALSE;
			if( $testIfPossible  ) return TRUE;
			
			$workBook = array();
			foreach( array( 'userlists' , 'grouplists' ) as $listName ) {
				if( !isset($reportDb[$listName]) ) continue;
				foreach( $reportDb[$listName] as $title=>$actionRow) {
						foreach( $reportDb['fieldlist'] as $fld ) {if( count($workBook) && $this->settings['download_format'] == 'csv') $workBook[$title]['titlerow_0'][$fld] = '' ;$workBook[$title]['titlerow_1'][$fld] = html_entity_decode($this->view->widgets->getLabel('title.' . $title , $title)) . ':' ;break;}
						if( $listName == 'userlists' ) {
							foreach( $reportDb['fieldlist'] as $fld ) $workBook[$title]['titlerow_2'][$fld] = $fld ;
						}
						foreach( $actionRow as $username => $tab) {
								foreach( $tab as $fld => $cnt)  $workBook[$title][$username][$fld] = str_replace( array_keys($aActionIcon) , $aActionIcon , $cnt );
						}
				}
			}
			return $this->csvService->downloadArrayAsSpreadsheet( ($workBook) , $this->view->widgets->getLabel('difference' , 'difference') . '.' . $this->settings['download_format'] , TRUE);
	}

    /**
     * reportDifference
     *
     * @return string
     */
    private function reportDifference() {
		$createJobsUtility = new \Drg\CloudApi\Utility\CreateJobsUtility( $this->settings );
		$reportDb = $createJobsUtility->getDifference();
		$liste = '';
		if( !isset($reportDb['userlists']) && !isset($reportDb['grouplists']) ) return false;
		if( isset($reportDb['grouplists']) ){foreach( $reportDb['grouplists'] as $title=>$actionRow) {
				foreach( $actionRow as $tab ) $contentList[$tab['ID']] = $tab['ID'];
				$liste .= '<h2>'.$this->view->widgets->getLabel('title.' . $title , $title).'</h2>' . $this->view->widgets->objViewArrayContents( $contentList );
		}}
		if( isset($reportDb['userlists']) ){foreach( $reportDb['userlists'] as $title=>$actionRow) {
				$liste .= '<h2>'.$this->view->widgets->getLabel('title.' . $title , $title).'</h2>' . $this->view->widgets->htmlTableWithPager( $actionRow );
		}}
		
		return $liste;
	}

}


?>
