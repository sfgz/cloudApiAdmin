<?php
namespace Drg\CloudApi;
if( !defined('SCR_DIR') ) die( basename(__FILE__).' #3: die Konstante SCR_DIR ist nicht definiert, das Skript wurde nicht korrekt gestartet.' );
include_once( SCR_DIR .'Classes/Core/boot.php' );
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
 * Command Line Interface module dispatcher
 * tasks to run are defined in file Config/cli_tasks.php
 * 
 * dont read cli_tasks, instead read config-files from data-dirs directly
 * if only one command, then it needs data-dir as second option 
 */
class cli_boot  extends \Drg\CloudApi\boot {

	/**
	 * initiate started by constructor
	 * cancelled: work done in boot->__construct()
	 *
	 * @return  void
	 */
	public function initiate() {
			 $this->settings = $this->readLocalDataSettings( $this->settings );
	}

	/**
	 * method readLocalDataSettings
	 *  overwrides method in boot! 
	 *  changes in this method will only affect calls from cron or command line interface
	 *
	 * @param array $settings
	 * @return  array
	 */
	Protected function readLocalDataSettings( $settings ) {

 		$tableConfFilename = basename( $settings['table_conf_filepath'] , '.php' ) . '.json';
		 // get global table_conf
		 $tableConfig = empty($settings['store_global.table_conf']) ? array() : $this->bootup_settings->readFilebasedDataSettings( $settings , DATA_DIR . $tableConfFilename );

		$aDirinfo = $this->fileHandlerService->getDir( DATA_DIR );
		foreach( $aDirinfo['dir'] as $dirnam ){ 
			$dirSetting = empty($settings['store_global.table_conf']) ? $settings : $tableConfig;
 			$dirSetting['dataDir'] = $dirnam;
 			$shortDirectory = pathinfo( $dirnam , PATHINFO_BASENAME);
 			$settings['cli_tasks'][$shortDirectory] = $this->bootup_settings->readFilebasedDataSettings( $dirSetting , $dirnam . '/' . trim( $settings['local_settings_filename'] , '/' ) , TRUE);
 			if( empty($settings['store_global.table_conf']) ) { // get data-relational table_conf
					$settings['cli_tasks'][$shortDirectory] = $this->bootup_settings->readFilebasedDataSettings( $settings['cli_tasks'][$shortDirectory] , $dirnam . '/' . $tableConfFilename );
 			}
		}
		return $settings;
	}
	
	/**
	 * dispatch_rundir
	 *
	 * @param string $dirName optional but mandatory if action is given
	 * @param string $action optional
	 * @return  void
	 */
	function dispatch_rundir( $dirName = '' , $action = '' ){
		// verify settings
		if( !isset($this->settings['cli_tasks']) ){
			die("No data-folder found. Maybe on install mode?\n");
		}
		
		// verify input
		if( empty( $dirName ) || 'all' == $dirName  ){
			// loop trough all directories
			$loopDirs = $this->settings['cli_tasks'];
		}elseif( isset($this->settings['cli_tasks'][$dirName]) ){
			// create a array to 'loop' trough one dir
			$loopDirs = array( $dirName => $this->settings['cli_tasks'][$dirName] );
		}else{ // $dirName is not empty but it does not correspond to any exisiting folder
			$this->abort( "Folder '" . $dirName . "' not found. Try 'all'. Aborted [folder=" . $dirName . "].");
		}
		
		// loop trough all directories and perhaps run a command if its time to
		$now = time();
		foreach( $loopDirs as $shortDirectory => $aTask ){
			
			// forced actions
			if( empty($action) || strtolower($action) == 'models'){
 				// run action here and print the output on screen in case this script was called from command-line otherwise the echo goes to cron-daemon
				$this->rundir_force_models( $shortDirectory );
				// if forced action, then its done for this directory, otherwise  look up for further actions to do
				if( strtolower($action) == 'models' ) continue;
			}elseif( $action ){
 				// run action here and print the output on screen in case this script was called from command-line otherwise the echo goes to cron-daemon
				$this->rundir_force_action( $shortDirectory , $action );
				continue;
			}
			
			// none is selected, do nothing by cli-boot
			if( $aTask['exec_type'] == 'none' ) {
				$this->debug[ $shortDirectory . '-none' ] = 'cron disabled [folder=' . $shortDirectory . ']';
				continue;
			}
			
			// reset cloud-data
			if( !empty($aTask['exec_reset'])){
					$execTimes = $this->isCronTimeValid($aTask['exec_reset']);
					if( $execTimes === false ){
						$this->debug[ $shortDirectory . '-reset' ] = 'Cron-Time is not correct [' . $aTask['exec_reset'] . ']';
						
					}elseif($this->isTimeToRun( $execTimes , $now ) ) {
						// reset cloud-data here and print the output on screen in case this script was called from command-line otherwise the echo goes to cron-daemon
						$this->rundir_force_action( $shortDirectory , 'reset' );
					
					}else{
						$this->debug[ $shortDirectory . '-reset' ] = 'its not time to run this command';
					}
			}
			
			// if only 'reset' selected, no other exec-type follows (import,export,documents)
			if( $aTask['exec_type'] == 'reset' ) continue;
			
			if( !empty($aTask['exec_action']) ) {
					$execTimes = $this->isCronTimeValid($aTask['exec_action']);
                    if( $execTimes === false ){
						$this->debug[ $shortDirectory . '-' . $aTask['exec_type'] ] = 'Cron-Time is not correct [' . $aTask['exec_reset'] . ']';
						
					}elseif( $this->isTimeToRun( $execTimes , $now ) ) {
                        // run exec_action here and print the output on screen in case this script was called from command-line otherwise the echo goes to cron-daemon
                        $this->rundir_force_action( $shortDirectory , $aTask['exec_type'] );
                        
                    }else{
                        $this->debug[ $shortDirectory . '-' . $aTask['exec_type'] ] = 'its not time to run this command';
                    }
			}
		}
		if( count($this->debug) ) foreach($this->debug as $title=>$cnt) echo $title . " = " .$cnt . "\n";
	}

	/**
	 * rundir_force_action
	 *
	 * @param string $dirName
	 * @param string $action
	 * @return  void
	 */
	function rundir_force_action( $dirName , $action ){
		$startTime = microtime( true );
		$aTask = $this->settings['cli_tasks'][$dirName];
		$taskUtil = new \Drg\CloudApi\Utility\CliTasksUtility( $aTask );
		$cmd = $action . "_cloud";
		if( !method_exists( $taskUtil , $cmd) && method_exists( $taskUtil , $action) ) $cmd = $action;
		echo $dirName  .' = ' . $cmd . "...\n";
		if( method_exists( $taskUtil , $cmd) ) {
			$result = $taskUtil->$cmd();
			$this->debug[$cmd] =  "(".$result.")  done for dir '" . $dirName . "' in " . round( (microtime( true )-$startTime)  , 3 ) . " sec.";
		}else{
			return $this->abort("Method '" . $cmd . "' not found. Aborted [folder=" . $dirName . "] [command=" . $action . "]. ");
		}
		if( count($this->debug) ) foreach($this->debug as $title=>$cnt) echo $title . " = " . $cnt . "\n";
		if( is_array($taskUtil->debug) && count($taskUtil->debug) ) echo  implode( ". " , $taskUtil->debug ) . "\n";
	}

	/**
	 * rundir_force_models
	 *
	 * @param string $dirName
	 * @return  boolean
	 */
	function rundir_force_models( $dirName ){
		$originSettings = $this->settings;
		$this->settings = $this->settings['cli_tasks'][$dirName];
		$aObjModels =  $this->readClasses('Model');
		$this->settings = $originSettings;
		echo 'rundir_force_models for ' . $dirName . "...\n";
		if( !count($aObjModels) ) return false;
		
		echo 'seaked for cron-command in ';
		foreach( $aObjModels as $className => $objModel ){
			$res = ( !isset( $objModel->properties['crontime'] ) ) ? '-' : $objModel->cronAction( $this );
			echo '' . $className . '('.$res.') ';
		}
		echo "\n";
		return true;
	}

	/**
	 * abort
	 *
	 * @param string $text
	 * @return  void
	 */
	function abort( $text ){
		$folders = "\nTry cli_dispatch.phpsh [folder] [command]. Or run it without any parameter.\n";
		$folders .= "\nExisting folders: all, ";
		if(isset($this->settings['cli_tasks']) ) $folders .= implode( ', ' , array_keys( $this->settings['cli_tasks'] ) );
		$folders .= "\n";
		$folders .= "\nExisting commands: models, reset, import, export, documents or (none).\n\n";
		die( "Error\n" . $text . $folders ) ;
	}

	/**
	 * isCronTimeValid
	 *
	 * @param string $execTimes cron time-setting eg. '59 23 * 1-5 *' once a day monday-friday on 23:59
	 * @return  boolean
	 */
	function isCronTimeValid( $execTimes ){
		$execTimes = str_replace( '  ' , ' ' , $execTimes);
		$aTimes = explode( ' ' , $execTimes );
		
		if( count($aTimes) != 5 ) return false;
		
		return $execTimes;
	}

	/**
	 * isTimeToRun
	 * returns array if is in crontime
	 * allowed are values like [ * | * /5 | 10,15 | 10-15 | 01-59/20 ]
	 *
	 * @param string $execTimes cron time-setting eg. '59 23 * 1-5 *' once a day monday-friday on 23:59
	 * @param string $now 
	 * @return  void
	 */
	function isTimeToRun( $execTimes , $now='' ){
        // prepare cron command
		if( substr( $execTimes , 0 , 1 ) == '-' ) return;
		
		$execTimes = str_replace( '  ' , ' ' , $execTimes);
		$aTimes = explode( ' ' , $execTimes );
		if( count($aTimes) != 5 ) return false;
		
		list( $aT['i'] , $aT['H'] , $aT['d'] , $aT['m'] , $aT['w'] ) = $aTimes;
		if( $aT['w'] == 7 ) $aT['w'] = 0; // Mo-Su we want 0-6 format, not 1-7
		
		// set the time to cmpare with
		if( empty($now) ) $now = time();
		
		// analyse cron commands
		$is = array();
		foreach( $aT as $ti => $tv){
            $is[$ti] = $this->isTime_testStep( $ti , $tv , $now );
		}
		// Wenn sowohl „Tag des Monats“ (Feld 3) und „Tag der Woche“ (Feld 5) angegeben wurden (nicht *), 
		// muss nur eines der beiden Kriterien fuer das aktuelle Datum erfuellt werden
		if( $aT['d'] != '*' && $aT['w'] != '*' ){
			if( $is['d'] && !$is['w'] ) $is['w'] = 1;
			if( $is['w'] && !$is['d'] ) $is['d'] = 1;
		}
		// Delay: Verzoegerung fuer den Fall dass minute nicht ganz genau stimmt aber max 4 Minuten zuvor
		if( !$is['i'] ){
             for( $n = 1 ; $n <= 4 ; ++$n ){
                $is['i'] = $this->isTime_testStep( 'i' , $aT['i'] , $now - ( $n * 60 ) ) ? 1 : 0;
                if( $is['i'] ) break;
             }
		}
		return count($is) == array_sum($is);
	}

	/**
	 * isTimeToRun
	 * returns true if is in crontime
	 *
	 * @param string $timeIndex [ i | H | d | m | w ]
	 * @param string $patternValue [ * | * /5 | 10,15 | 10-15 | 01-59/20 ]
	 * @param int $now 
	 * @return  boolean
	 */
	function isTime_testStep( $timeIndex , $patternValue , $now ){
			// define actual value
			$actualValue = date($timeIndex,$now);
			// define condition: replace * with actual value
			$condition = trim(str_replace( '*' , $actualValue , $patternValue )) ;
			
			if( strpos($condition,'-') && strpos($condition,'/') ){
				// if - AND / then true Eg. if between 5-59/20  =>  if( now >=5 AND <=59  ) THENIF 0 == now % 20
				list( $fromToDividend , $divisor ) = explode( '/' , $condition );
				list( $from , $to ) = explode( '-' , $fromToDividend );
				$firstConditionResult  = $actualValue >= $from && $actualValue <= $to ? 1 : 0;
				$isTrue = 0 == ($actualValue % $divisor) ? $firstConditionResult : 0;
				
			}elseif( strpos($condition,'-') ){
				// if - then true if between
				list( $from , $to ) = explode( '-' , $condition );
				$isTrue = $actualValue >= $from && $actualValue <= $to ? 1 : 0;
				
			}elseif( strpos($condition,'/') ){
				// if */ then true if no rest * % 5 == 0
				list( $dividend , $divisor ) = explode( '/' , $condition );
				$isTrue = 0 == ($dividend % $divisor) ? 1 : 0;
				
			}elseif( strpos($condition,',') ){
				// if actual value is in coma-separed list eg 2,3,7
				$aConditions = array_flip( explode( ',' , trim($condition) ) );
				$isTrue = isset( $aConditions[ $actualValue ] ) ? 1 : 0;
				
			}else{
				$isTrue = $actualValue == $condition ? 1 : 0;
				
			}
            return $isTrue;
	}


}

?>
