<?php
namespace Drg\CloudApi;
if( !defined('SCR_DIR') ) die( basename(__FILE__).' #3: die Konstante SCR_DIR ist nicht definiert, das Skript wurde nicht korrekt gestartet.' );
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
 * boot
 *  extends core
 *  
 *  anhand von input wird controller und action entschieden
 *  anhand von controller kann Layout [controller].html variieren
 *  anhand von controller kann Partial [Controller]Toolbar.html variieren
 *  anhand von action kann View [action].html variieren
 *  
 *  view liest viewHelpers und fuegt sie view->objectXy hinzu (viehelperLoader)
 *  stellt actionsControllern ueber view->objectXy()->getContainer 
 *  Variabeln zur Verfuegung, welche
 *  view->container ueber view->objectXy()->assign( 'timeout' , $timeout ) zugewiesen wurden
 *  
 */
	$middlepath = 'Classes/Core/';
	$aScriptsToInclude = array( 
		$middlepath . 'bootup_settings.php' , 
		$middlepath . 'modelBase.php' , 
		$middlepath . 'viewBase.php' , 
		$middlepath . 'obj.php' , 
		$middlepath . 'view.php' ,
		$middlepath . 'controllerBase.php'
	);
	foreach($aScriptsToInclude as $script){
		if( !file_exists(SCR_DIR . $script) ) die( basename(__FILE__) . ' #53: datei ' . SCR_DIR . $script . ' nicht vorhanden!' );
		require_once( SCR_DIR . $script );
	}

/**
*/
class boot extends core {

	/**
	 * Property controller
	 *
	 * @var string
	 */
	Public $controller = '';

	/**
	 * Property action
	 *
	 * @var string
	 */
	Public $action = '';

	/**
	 * runnedActions
	 *
	 * @var array
	 */
	protected $runnedActions = NULL;

	/**
	 * authService
	 *
	 * @var \Drg\CloudApi\Service\AuthService
	 */
	protected $authService = NULL;

	/**
	 * installerService
	 *
	 * @var \Drg\CloudApi\Services\InstallerService
	 */
	protected $installerService = NULL;

	/**
	 * bootup_settings
	 *
	 * @var \Drg\CloudApi\bootup_settings
	 */
	protected $bootup_settings = NULL;

	/**
	 * __construct
	 *
	 * @param array $settings optional
	 * @return  void
	 */
	public function __construct( $settings = array() ) {
		parent::__construct( $settings );
		
		// this works for cli_boot.php aswell
		$this->readScripts( $this->settings['scrDir'] . 'Classes/' );
		$this->initiate();
	}

	/**
	 * Set readSettings
	 * called from __construct()
	 * this method works for cli_boot.php aswell
	 * 
	 * @param array $settings
	 * @return  void
	 */
	public function readSettings( $settings ) {
		//  read default settings, default table_conf for csv-files and Models: UsersModel and SqlconnectModel
		$this->bootup_settings = new \Drg\CloudApi\bootup_settings( $settings );
		$this->settings = $this->bootup_settings->getSettings() ;
 		//$this->settings = $this->readLocalDataSettings( $this->settings );
	}

	/**
	 * initiate
	 *
	 * @return  void
	 */
	public function initiate() {
		
		$this->settings['req'] = $this->bootup_settings->getRequest();
		
		// if act AND newact are set, then the value of newact has precedence
		if( isset($this->settings['req']['newact']) ) $this->settings['req']['act'] = $this->getFirstArrayKey($this->settings['req']['newact']);

		// authenticates user or runs login. This method instantiates UsersModel. 
		// Recordset of logged in user can be called by $this->authService->getAuthUsersRecordset().
		$this->authService = new \Drg\CloudApi\Services\AuthService( $this->settings );
		$validateLogin = $this->authService->ValidateLogin();

		if( $validateLogin == FALSE ) {
				// not logged in
				$this->detectAction();
		}else{
				
				$this->settings = $this->bootup_settings->readSessionSettings($this->settings);
				$this->settings = $this->readLocalDataSettings( $this->settings );
				
				// reset debug-output depending on a session-variable
				$this->setErrorOutputState( $this->settings['debug'] );
				
				if( isset( $this->settings['default_dir'] ) ) $this->settings['dataDir'] = DATA_DIR . trim( $this->settings['default_dir'] , '/' ) . '/';
				
				// test if affored directories exists. otherwise it would not be possible to store data
 				$installerServiceStatusText = $this->installDirectories();
				if( $installerServiceStatusText ){
						// Installation routine running
						$this->detectAction('install');
						$this->activeController->view->assign( 'page' , $installerServiceStatusText );
					
				}else{
					
						// store changes from incoming settings if on $_REQUEST['OK'][ xxx ] the string xxx is a value in array $aPossibleOkButtons eg  $aPossibleOkButtons[0] = xxx 
						$this->settings = $this->bootup_settings->updateSettings( $this->settings , array('save','create') );
						$this->setFilePermission();
					
						// detect controller and action set incomed action or none
						$action = isset($this->settings['req']['act']) ? $this->settings['req']['act'] : '';
						$this->detectAction($action);
				}
		}

		echo $this->execute();
		die();
	}
	
    /**
     * executeAction
     * emit action and run
     *
     * @return string
     */
    public function execute() {

		// run action and set new value for 'action' if redirected
  		$this->getActionResult();
		
  		$replace['##FOOTER##'] = '' ;
		$replace['##page##'] = '' ;
		$replace['##toolbar##'] =  '';
		$replace['##text##'] = '' ;
		$replace['##button##'] = '' ;
		
		 // 'action' has to be defined AFTER definition of variables containig the pattern ##action## (eg. page, text or button)
		$replace['##controller##'] = $this->controller;
 		$replace['##action##'] = $this->action;
		$replace['##url##'] = $this->settings['url'];
		
		if( isset($this->settings['req']['ok']) && is_array($this->settings['req']['ok']) ){
			$replace['##ok##'] = $this->getFirstArrayKey($this->settings['req']['ok']) ;
		}else{
			$replace['##ok##'] = '' ;
		}

		$replace['##loggedIn##'] =  0;

		if( $this->authService->isLoggedIn ){
			$replace['##loggedIn##'] = 1;
		}

		$this->activeController->view->append( 'DEBUG' , $this->getDebuggers());
		
		$this->activeController->view->prepend( 'HEAD' , $this->getPageHead() );
		
		return $this->activeController->view->renderPage( $replace );
		
	}

	/**
	 * Set dataDir-related Settings
	 * same method in cli_boot, but different content! 
	 * changes here will not affect calls from cron or command line interface
	 *
	 * @param array $settings
	 * @return  array
	 */
	Protected function readLocalDataSettings( $settings ) {
		
 	    // change dir if affored
 	    if( isset( $settings['req']['settings']['default_dir'] ) ) {
				$settings['dataDir'] = $settings['req']['settings']['default_dir'];
				$settings['default_dir'] = $settings['req']['settings']['default_dir'];
 	    }elseif( isset($settings['default_dir']) ){
				$settings['dataDir'] = $settings['default_dir'];
 	    }
		$settings['dataDir'] = DATA_DIR . trim( str_replace( DATA_DIR , '' , $settings['dataDir'] ) , '/' ) . '/';
 	    
 	    // get data-relational settings
 	    $settings = $this->bootup_settings->readFilebasedDataSettings( $settings , $settings['dataDir'] . $settings['local_settings_filename'] , TRUE );

 	    // get data-relational table_conf
 		$filename = basename( $settings['table_conf_filepath'] , '.php' ) . '.json';
 	    $settings = $this->bootup_settings->readFilebasedDataSettings( $settings , ( $settings['store_global.table_conf'] ? DATA_DIR : $settings['dataDir'] ) . $filename );
		
		return $settings;
	}

    /**
     * readScripts
     *
     * @param string $pathToScripts
     * @return void
     */
    Protected function readScripts( $pathToScripts ) {
    
		$aDirs =  $this->fileHandlerService->getDir( $pathToScripts , 8 );
		
		if( is_array( $aDirs['fil'] ) ){
			foreach( $aDirs['fil'] as $pathFile => $entry ){
				if(pathinfo( dirname($pathFile) , PATHINFO_FILENAME ) == 'Core') continue;
				if( __FILE__ == $pathFile ) continue;
				if( 'php' != pathinfo( $pathFile , PATHINFO_EXTENSION   ) ) continue;
				require_once( $pathFile );
			}
		}
    
	}

    /**
     * setFilePermission
     * make files executable if affored
     *
     * @return void
     */
    Protected function setFilePermission() {
		// cron-daemon is disabled, nothing has to be done.
		if( empty($this->settings['exec_type']) || $this->settings['exec_type'] == 'none' )  return TRUE;
		
		// return true if PHP was built for or running on windows
		if (strncasecmp(PHP_OS, 'WIN', 3) == 0 || strtolower(PHP_SHLIB_SUFFIX) === 'dll')  return TRUE;

		$cliDispatchFile = SCR_DIR . 'Classes/Commands/cli_dispatch.phpsh';

		// test if file is executable on linux
		if( is_executable( $cliDispatchFile ) ) return TRUE;
		
		$this->debug['boot->filePermission'] = '##LL:installerService.filePermission_cron## ';
		
		$failedString = '##LL:installerService.filePermission_result_failed##<br />';
		
		if( !is_writeable($cliDispatchFile) ) {
				$success = FALSE;
		}else{
				$success = @chmod( $cliDispatchFile , 0777 );
		}
		$this->debug['boot->filePermission'] .= $success ? '##LL:installerService.filePermission_result_fixed##' : $failedString.'';

		return $success;
	}

    /**
     * installDirectories
     * create Directories if affored
     *
     * @return void
     */
    Protected function installDirectories() {
		$installerService = new \Drg\CloudApi\Services\InstallerService($this->settings);
		// create or remove subdirectory of main data-folder
		if( file_exists($this->settings['dataDir']) ){
			$newDirectory = isset($this->settings['req']['settings']) && isset($this->settings['req']['settings']['newDirectory']) ? $this->settings['req']['settings']['newDirectory'] : ''; // isset($_POST['settings']['newDirectory']) ? $_POST['settings']['newDirectory'] : '' ;
			if( isset($this->settings['req']['ok']['create']) && !empty($newDirectory)  ){
				$installerService->installDirsAction( DATA_DIR . trim( $newDirectory , '/' ) . '/' );
				return $installerService->status ;
			}elseif( isset($this->settings['req']['ok']['remove']) && !empty($this->settings['req']['ok']['remove'])  ){
				$installerService->deleteFilesInDirectory( DATA_DIR . trim( $this->settings['req']['ok']['remove'] , '/' ) . '/' );
				return $installerService->status ;
			}
		}
		
		// create or remove main data-folder
 		if($this->settings['edit_directories_manually'] && isset($this->settings['req']['uninstall'][0]) && is_array($this->settings['req']['uninstall']) ){
			$this->settings['req']['uninstall'] = 0;
 		}
		if( isset($this->settings['req']['uninstall']) && !empty($this->settings['req']['uninstall'])  ){
			$installerService->uninstallTemporaryDirsAction();
		}else{
			// default action 
			$installerService->installTemporaryDirsAction();
			// removed: if( $this->settings['create_default_files'] ) $installerService->createSampleFiles(); // creates examples of csv-files in table_conf if defined
		}
		return $installerService->status ;
	}

	/**
	 * readClasses
	 *
	 * @param string $classSuffix default is 'Controller'
	 * @return array of objects
	 */
	public function readClasses( $classSuffix = 'Controller' ) {
		$allClasses = get_declared_classes();
		$aControllers = array();
		if( !isset($allClasses) || !count($allClasses) ) return;
		foreach($allClasses as $class){
			$aClassFragments = explode( '\\' , $class );
			$classname = array_pop($aClassFragments);
			$patternPos = strpos( $classname , $classSuffix );
			if( !$patternPos ) continue;
			$newClassName = lcfirst( substr( $classname , 0 , $patternPos ) );
			if( isset($aControllers[$newClassName]) ) continue;
 			$aControllers[$newClassName] = new $class( $this->settings );
		}
 		return $aControllers;
	}
	
    /**
     * detectAction
     *
     * @param string $reqAction
     * @param string $reqController
     * @return string
     */
    Protected function detectAction( $reqAction = '' , $reqController = '' ) {
    
			// set incomed action or none
			// if act AND newact are set, then the value of newact has precedence
			$action = !empty($reqAction) ? $reqAction : (isset($this->settings['req']['act']) && !empty($this->settings['req']['act']) ? $this->settings['req']['act'] : '');

			// set income controller or none
			$controller = $reqController;
			if( empty($controller) && isset($this->settings['req']['controller']) && is_array($this->settings['req']['controller']) && count( $this->settings['req']['controller'] ) ){
				$insensitiveController = $this->getFirstArrayKey($this->settings['req']['controller']);
				$controller = lcFirst($insensitiveController);
			}

			// get authorisation grade
			$authGroup = $this->authService->getAuthUsersRecordset('group');
			
			// read controller objects and store them in array $aControllers
			$aControllers = $this->readClasses();
			
			// evaluate possible actions depending on user-group
			$actionsController = array();
			foreach($aControllers as $classname => $objClass ){
				$controllersActions = $objClass->getAuthorisedActions($authGroup);
				foreach( $controllersActions as $act => $iRule ) $actionsController[$act][$classname] = $iRule;
			}
			foreach( array_keys($actionsController) as $act ){
				asort($actionsController[$act]); // highest auth last
			}
			
			// cases
			$detected = FALSE ;
			// if controller is given, select it if there is a valid action
			if( !empty($controller) && !empty($action) && isset($aControllers[$controller]) ){
				if( isset( $actionsController[$action] ) && isset( $actionsController[$action][$controller] ) ){
					$detected = TRUE ;
				}else{
					if( $aControllers[$controller]->actionDefault && $actionsController[$aControllers[$controller]->actionDefault][$controller] <= $authGroup ){
							$action = $aControllers[$controller]->actionDefault;
							$detected = TRUE ;
					}
				}
			}
			if( $detected == FALSE ) $controller = '';
			
			// action given
			// detect if allowed, select action  and set controller 
			if( $detected == FALSE ){
					if( !empty($action) && isset($actionsController[$action]) ){
						if( empty($controller) || !isset( $actionsController[$action][$controller] ) || $actionsController[$action][$controller] > $authGroup ){
							// if controller not valid detect other controller with that action
							$loopController = ''; // reset wrong value for controller 
							foreach( $actionsController[$action]  as $cntrl => $iRule ){
								if( $iRule <= $authGroup ) $loopController = $cntrl;
							}
							// if action not allowed in any controller then detect the controller of forbidden action and its default-action
							if( empty($loopController) ){
								foreach( $actionsController[$action]  as $cntrl => $iRule ){
									// select the controller of forbidden action and its default-action
									if( isset($aControllers[$cntrl]) && $aControllers[$cntrl]->actionDefault && $actionsController[$aControllers[$cntrl]->actionDefault][$cntrl] <= $authGroup) {
										$action = $aControllers[$cntrl]->actionDefault;
										$loopController = $cntrl;
										break;
									}
								}
							}
						}else{
							// controller and action are valid
							$loopController = $controller;
						}
						// if loopController still empty, action not registred or allowed in any controller.
						if( !empty($loopController) ){
								$controller = $loopController; $detected = TRUE; 
						}else{ 
								$action = ''; 
						}
					}else{
						// if action was given then it was not found in any controller. reset wrong value for action.
						$action = ''; 
					}
			}

			if( $detected == FALSE ){
				$controller = lcFirst($this->settings['controller']);
				if( isset($aControllers[$controller]) && isset($actionsController[ $aControllers[$controller]->actionDefault ]) && $actionsController[ $aControllers[$controller]->actionDefault ][$controller]  <= $authGroup ){
					$action = $aControllers[$controller]->actionDefault; 
					$detected = TRUE;
				}else{
					// if controller was given then it has no valid action. detect any other valid action in any controller
					foreach( $aControllers  as $cntrl => $bjContrl ){
						if( isset($actionsController[ $bjContrl->actionDefault ]) && $actionsController[ $bjContrl->actionDefault ][$cntrl]  <= $authGroup ){
								$action = $bjContrl->actionDefault;
								$controller = $cntrl;
								$detected = TRUE;
								break;
						}
					}
				}
			}
			
			if( $detected == FALSE  ){
				die( 'Controller not found in boot->detectAction() #448 controller:' . $controller  );
			}
			
			$this->controller = ucFirst($controller);
			$this->activeController = $aControllers[$controller];
			$this->activeController->allControllersObjects = $aControllers;
			$this->activeController->authService = $this->authService;
			$this->activeController->initiate();
			
			// sets the activeControllers view and view->objects
			$this->setAction( lcFirst( $action ) ); 
		
			return lcFirst( $action ) ;
		
	}

    /**
     * setAction
     *
     * @param string $action
     * @return void
     */
    Protected function setAction($action) {
			$this->action = $action;
			$this->activeController->view->action = $this->action;
	}

    /**
     * getActionResult
     *
     * @param string $action
     * @return void
     */
    Protected function getActionResult( $action = '' ) {
    
		if( empty($action) ) $action = $this->action ;
		if( isset($this->runnedActions[$action]) ) {
			$this->activeController->debug[] = 'possible loop detected on:action=' . $action . '('.$this->action.')';
			return; // possible infinite-loop detected!
		}
		
		// set action as runned
		$this->setAction($action);
		$this->runnedActions[$action] = $action;
		
		// run action here
 		$method = $action . 'Action';
		$result = $this->activeController->$method() ;

		// maybe the runned action requires a further action
		if( !empty($result) && $result != 1 ){
			if(	method_exists( $this->activeController , $result . 'Action' ) ){
				// set & run the new action here by re-run own method again
				$this->setAction($result);
				$result = $this->getActionResult($result);
			}else{ // search the returned action in other controller 
				$newAction = $this->detectAction($result);
				$result = $this->getActionResult($newAction);
			}
		}

		return $result;
	}

    /**
     * getDebuggers
     *
     * @return void
     */
    Protected function getPageHead() {
    
		$publicHref =  '' . trim( substr( SCR_DIR , strlen(dirname($_SERVER['SCRIPT_FILENAME'])) ) , '/').'/Public/';
		
		$cssClass = $this->activeController->getAdditionalCss();
		
		$viewCssClass = $this->activeController->view->models->getContainer('CSS');
		$cssClass['css'] =  implode( '' , $viewCssClass );
		
		$strCssCmd = count($cssClass) ? "\t<style>\n" . implode( "" , $cssClass ) . "\t</style>" : '';
		
		$headerdata = "\t<meta charset=\"utf-8\" />\n";
		$headerdata.= "\t" . '<link rel="shortcut icon" href="' . $publicHref . 'Img/favicon.ico" type="image/x-icon">' . "\n";
		$headerdata.= "\t<title>cloudApiAdmin " . $this->action . ' - ' . basename($this->settings['dataDir']) . "</title>\n";
		$headerdata.= "\t<script src=\"".$publicHref."Scr/formhelper.js\" type=\"text/javascript\"></script>\n";
		$headerdata.= "\t<script src=\"".$publicHref."Scr/encrypt.js\" type=\"text/javascript\"></script>\n";
		$headerdata.= "\t<link rel=\"stylesheet\" href=\"".$publicHref."Css/main.css\" />\n";
		$headerdata.=  $strCssCmd;
		
		return $headerdata;
	}

    /**
     * getDebuggers
     *
     * @return void
     */
    Protected function getDebuggers() {
		$debugger = '';
		if( is_array( $this->authService->model->debug ) && $this->settings['debug'] >= 1 ){
			foreach($this->authService->model->debug  as $title => $value ) if( !empty($value) ) $this->debug['boot->authService:'.$title] = $value . '<br />';
		}
		if( is_array( $this->bootup_settings->debug ) && $this->settings['debug'] >= 2 ){
			foreach($this->bootup_settings->debug  as $title => $value ) if( !empty($value) ) $this->debug[$title] = $value . '<br />';
		}
		if( is_array( $this->activeController->fileHandlerService->debug ) && $this->settings['debug'] >= 1 ){
			foreach($this->activeController->fileHandlerService->debug  as $title => $value ) if( !empty($value) ) $this->debug[$title] = $value . '<br />';
		}
		if( is_array( $this->activeController->debug ) && $this->settings['debug'] >= 1 ){
			foreach($this->activeController->debug  as $title => $value ) if( !empty($value) ) $this->debug[$title] = $value . '<br />';
		}
		if( is_array( $this->debug ) && $this->settings['debug'] >= 1 ){
			foreach($this->debug  as $title => $value ) if( !empty($value) ) $debugger .= '<b>' . $title . '</b> => ' . $value ;
		}
		return $debugger;
	}
}
?>
