<?php
namespace Drg\CloudApi\Utility;
if( !defined('SCR_DIR') ) die( basename(__FILE__).' #3: die Konstante SCR_DIR ist nicht definiert, das Skript wurde nicht korrekt gestartet.' );
/***************************************************************
 *
 *  CliTasksUtility
 *  called by 
 *  - ActionsController (4x)
 *  - cli_boot (from command-line or cron)
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

 // hint: this class can do the full work-in-one by a chain-reaction.
 // call method documents_cloud() effects an export_cloud() before, wich effects an import_cloud() before.

class CliTasksUtility  extends \Drg\CloudApi\controllerBase {

	/**
	 * reset_cloud
	 * remover
	 * called by cli_boot.php on line 149
	 *
	 * @return boolean $completed
	 */
	public function reset_cloud(){
			$this->debug[ 'delete_xml_csv_json' ] = 'xml+csv+json+logfile deleted: '.$this->fileHandlerService->cleanDir( $this->settings['dataDir'] . 'api/' , 1 , 'xml,csv,json,txt' );
			return true;
	}

	/**
	 * import_cloud
	 * called by cli_boot.php on line 149
	 * used to run from command line
	 * calls reporter and do_import_cloud
	 *
	 * @return boolean $completed
	 */
	public function import_cloud(){
			$readCloudUtility = new \Drg\CloudApi\Utility\ReadCloudUtility( $this->settings );
			$readCloudUtility->connectorService->apiCalls = 1;
			$this->do_import_cloud( $readCloudUtility , $this->settings['exectimeout'] );
			if( !file_exists( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv') ) {
				$fullfilled = 0;
			}else{
				$fullfilled = 1;
			}
			$aOkText = array( 'not completed' , 'completed' );
			$this->reporter( 'import_cloud (' . $aOkText[$fullfilled] . ')' );
			return $fullfilled;
	}

	/**
	 * export_cloud
	 * called by cli_boot.php on line 149
	 * used to run from command line
	 * calls reporter and do_export_cloud
	 *
	 * @return boolean $completed
	 */
	public function export_cloud(){
			$updateCloudUtility = new \Drg\CloudApi\Utility\UpdateCloudUtility($this->settings);
			if( !file_exists( rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv') ) {
				$aCloudUsersAndAttributes = $this->import_cloud();
				return (isset($aCloudUsersAndAttributes['fullfilled']) && $aCloudUsersAndAttributes['fullfilled']);
			}
			$completed = $this->do_export_cloud( $updateCloudUtility , $this->settings['exectimeout'] );
			$aOkText = array( 'not completed' , 'completed' );
			$this->reporter( 'export_cloud (' . $aOkText[$completed] . ')' );
			
			return $completed;
	}

	/**
	 * documents_cloud
	 * called by cli_boot.php on line 149
	 * used to run from command line
	 *
	 * @return boolean $completed
	 */
	public function documents_cloud(){
			$completed = $this->export_cloud();
			if(!$completed) return false;
			
			$groupPdfUtility = new \Drg\CloudApi\Utility\GroupPdfUtility($this->settings);
			$jobFile = rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'groupPdfJob.json';
			
			if(!file_exists($jobFile)) {
				$completed = $groupPdfUtility->startFirstCronjob( $this->settings['exectimeout'] );
			}else{
				$completed = $groupPdfUtility->startAsCronjob( $this->settings['exectimeout'] );
			}
			$aOkText = array( 'not completed' , 'completed' );
			$this->reporter( 'documents_cloud (' . $aOkText[$completed] . ')' );
			return $completed;
	}

	/**
	 * do_import_cloud
	 * called by import_cloud
	 * and by ActionController
	 *
     * @param \Drg\CloudApi\Utility\ReadCloudUtility $readCloudUtility
     * @param string $actTimeout
	 * @return array $aCloudUsersAndAttributes
	 */
	public function do_import_cloud( $readCloudUtility , $actTimeout ){
			$readCloudUtility->connectorService->prepareConnection();
			// get first time data
 			if( $this->settings['download_details'] ){
				$aCloudUsersAndAttributes = $readCloudUtility->getCloudData( $actTimeout  );
 			}else{
				$aCloudUsersAndAttributes = $readCloudUtility->getCloudGroupData( $actTimeout  );
			}
			if( isset($readCloudUtility->connectorService->debug) && count($readCloudUtility->connectorService->debug) ) {
				foreach($readCloudUtility->connectorService->debug as $title => $message ){
						if( substr( $title , 0 , strlen('not_writable-') ) == 'not_writable-' ){
							$filesError[] = $message; 
						}else{ 
							$this->debug['ReadCloudUtility->connectorService->getCloudData:'.$title] = $message; 
						}
				}
				if( isset($filesError) ) $this->debug['ReadCloudUtility->connectorService->getCloudData'] = 'Not a real problem, but some jobs could not be done by this process, maybe cron-daemon is at work? Missing permission for following files: ' . implode( ', ' , $filesError ) . '. ';
			}

			// create chk_*.json to select all specified checkboxes on very first call after import
			if(
				isset($aCloudUsersAndAttributes['fullfilled']) && $aCloudUsersAndAttributes['fullfilled']
			) {
				$createCronjobsUtility = new \Drg\CloudApi\Utility\CreateJobsUtility( $this->settings );
				$createCronjobsUtility->CreateChecklists();

				$this->debug['CreateChecklists'] = $createCronjobsUtility->debug['CreateChecklists'];
			}
 			return $aCloudUsersAndAttributes;
	}

	/**
	 * do_export_cloud
	 * called by export_cloud
	 * and by ActionController
	 *
     * @param \Drg\CloudApi\Utility\UpdateCloudUtility $updateCloudUtility
     * @param string $actTimeout
	 * @return  boolean $completed
	 */
	public function do_export_cloud($updateCloudUtility , $actTimeout){
			$updateCloudUtility->connectorService->prepareConnection();
			
			$completed = $updateCloudUtility->UpdateCloud(  $actTimeout  );
			
			if( $this->settings['debug'] >=1  && isset( $updateCloudUtility->debug )  && count( $updateCloudUtility->debug ) ){
				foreach($updateCloudUtility->debug  as $errKey => $debug ) $this->debug[$errKey] = $debug;
			}
			return $completed;
	}

	/**
	 * reporter
	 *
	 * @param string $input 
	 * @return  void
	 */
	private function reporter( $input ){
		$oldFilecontent = !file_exists($this->settings['dataDir'] . '/log.txt') ? '' : file_get_contents( $this->settings['dataDir'] . '/log.txt' );
		$aFilecontent = explode( "\n" , $oldFilecontent );

		array_unshift( $aFilecontent , date('d.m.y H:i:s') . " executed: " . static::class . "->" . $input  );

		if( count($aFilecontent) > $this->settings['max_logfile_lines'] ) $aFilecontent = array_slice( $aFilecontent , 0 , $this->settings['max_logfile_lines'] );

		file_put_contents( $this->settings['dataDir'] . '/log.txt' ,  implode( "\n" , $aFilecontent ) );
		
		return $input;
	}
}

?>
