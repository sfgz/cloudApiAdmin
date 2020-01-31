<?php
namespace Drg\CloudApi;
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
 * controllerBase
 *  contains view with view->objects etc.
 *  contains FileHandler-mechanism
 *  contains csvService
 *  
 *  expects settings over autoloader 
 * 
 *  extended by JobsEditorUtility
 *              ActionsController
 *              ConfigurationController
 * 
 */

 /**
*/
class controllerBase extends \Drg\CloudApi\core {

	/**
	 * Property allControllersObjects
	 *
	 * @var array
	 */
	Public $allControllersObjects = array();

	/**
	 * Property actionDefault
	 *
	 * @var string
	 */
	Public $actionDefault = 'welcome';

	/**
	 * Property accessRules
	 *
	 * @var array
	 */
	Protected $accessRules = array(
		'welcome' => NULL,
		'notes' => NULL,
	);

	/**
	 * Property subActions
	 * used to set button as active while a subaction is selected
	 * in here only as example, not used
	 *
	 * @var array
	 */
	Protected $subActions = array(
		'welcome' => array('welcome','notes') ,
	);

	/**
	 * Property disabledActions
	 *
	 * @var array
	 */
	Public $disabledActions = array();

	/**
	 * Property fallbackPartial
	 *
	 * @var string
	 */
	Public $fallbackPartial = '';

	/**
	 * view
	 *
	 * @var \Drg\CloudApi\view
	 */
	Public $view = NULL;

	/**
	 * csvService
	 *
	 * @var \Drg\CloudApi\Services\CsvService
	 */
	Public $csvService = NULL;

	/**
	 * __construct
	 *
	 * @param array $settings optional
	 * @return  void
	 */
	public function __construct( $settings = array() ) {
		parent::__construct( $settings );
		
		// tear up view
		$this->view = new \Drg\CloudApi\view( $this->settings );// in view: $this->objects = new htmlObjects();
		
		// set controllersPartial
		$aFullClassName = explode( '\\' , get_class( $this ) );
		$controller = str_replace( 'Controller' , '' ,  array_pop( $aFullClassName ) );
		if( file_exists( SCR_DIR . 'Private/Partials/' . $controller . 'Toolbar.html' ) ) {
			$this->view->controllersPartial = $controller . 'Toolbar';
		}elseif( !empty($this->fallbackPartial) ){
			$this->view->controllersPartial = $this->fallbackPartial;
		}
		
		// tear up csv service
		$this->csvService = new \Drg\CloudApi\Services\CsvService( $this->settings );
		
		// read acl-rules from settings and overwrite models accessRules 
		foreach( $this->accessRules as $action => $iRule ){
				if( isset($this->settings['acl_'.$action.'Action']) ){
					$this->accessRules[$action] = $this->settings['acl_'.$action.'Action'] ;
				}
		}
		
	}

	/**
	 * initiate
	 *
	 * @return  void
	 */
	public function initiate() {
		$conRs = $this->checkConnection($this->settings);
		$this->assignConnectionDetails($conRs);
		$this->assignCopyleftNote();
		
		// FIXME: hack to eliminate pdf-related settings if pdf-test failed
		//if( !isset($this->allControllersObjects['documents']) || empty($this->allControllersObjects['documents']) ) {
		if( false == $this->csvService->isClassAvaiable('DocumentsController') ) {
				if( isset($this->settings['acl_pdfCategory']) ) unset($this->settings['acl_pdfCategory']);
				if( isset($this->settings['original']['acl_pdfCategory']) ) unset($this->settings['original']['acl_pdfCategory']);
				$varsInCat = array_keys( $this->settings['categories'] , 'pdf' );
				if(count($varsInCat)){
					foreach($varsInCat as $var) if( isset($this->settings['categories'][$var]) ) unset($this->settings['categories'][$var]);
				}
		}
	}

	/**
	 * getAccessRule
	 *
	 * @param string $key
	 * @return  void
	 */
	public function getAccessRule($key) {
		return isset($this->accessRules[$key]) ? $this->accessRules[$key] : FALSE;
	}

	/**
	 * setAccessRule
	 *
	 * @param string $key
	 * @param string $value
	 * @return  void
	 */
	public function setAccessRule( $key , $value ) {
		$this->accessRules[$key]  = $value ;
	}

	/**
	 * getAuthorisedActions
	 *
	 * @param string $authGrade grade of authorisation integer between 0 and 99
	 * @return string
	 */
	Public function getAuthorisedActions( $authGrade ) {
			$possibleActions = array();
			$allActions = array();
			foreach( $this->accessRules as $act => $iRule ){
				if( $authGrade >= $iRule ) $possibleActions[$act] = $act;
				$allActions[$act] = $iRule;
			}
			if( !isset( $possibleActions[ $this->actionDefault ] ) ){
				if( count($possibleActions) ) {
					$this->actionDefault = $this->getFirstArrayKey($possibleActions);
				}else{
					$this->actionDefault = '';
				}
			}
			return $allActions;
	}

	/**
	 * checkConnection
	 *
	 * @param array $settings
	 * @return array settings, reduced to actual connection
	 */
	Protected function checkConnection($settings) {
		if( isset($settings['connection_url']) && isset($settings['connection_prot']) && isset($settings['connection_user']) && isset($settings['connection_pass']) ){
			return $settings;
		}
		
		$this->debug['#132 controllerBase->checkConnection'] = '##LL:def_for_connection:## {' . $cName . '} ##LL:not_complete##.';
		
		return false;
		
	}

	/**
	 * assignCopyleftNote
	 *
	 * @return void 
	 */
	Protected function assignCopyleftNote() {
		
		$copyRightString = ' &copy; ' . date('Y') . ' by d.rueegg  <a title="read licence" target="_blank" href="##LL:gnu_url##">GNU GPL</a>';
		
		$COPYLEFT = $this->assignCopyleftNote_getInfoLink() . $copyRightString;
		
		if( $this->authService->isLoggedIn ) $COPYLEFT .=  ' | ##LL:logged_in_as##: '.$this->authService->username . ' ['. $this->authService->getAuthUsersRecordset('group') . ']';
		
		$this->view->assign( 'COPYLEFT' , $COPYLEFT);
	}

	/**
	 * assignCopyleftNote_getInfoLink
	 *
	 * @return void 
	 */
	Protected function assignCopyleftNote_getInfoLink() {
		$isDownloadFile = file_exists(dirname($_SERVER['SCRIPT_FILENAME']).'/'.'cloudApiAdmin.'.$this->settings['version'].'.zip') ? TRUE : FALSE ;
		$infoTitle = $isDownloadFile ? 'read notes &amp; download' : 'read notes';
		$infoLabel = $isDownloadFile ? 'Info &amp; Download' : 'Info';
		$helpAtag = '<a title="' . $infoTitle . '" target="_self" href="?act='.$this->allControllersObjects['notes']->actionDefault.'">';
		$appName = 'CloudApiAdmin '.$this->settings['version'];
		
		$infoLink = '';
		if( $this->authService->isLoggedIn ){
			if( !$this->settings['hide_info_notes'] ) {
				$infoLink.= $helpAtag;
				$infoLink.= $appName;
				$infoLink.= '</a> ';
			}else{
				$infoLink.= $appName;
			}
		}else{
			if( !$this->settings['hide_info_notes'] ) {
				$infoLink.= '<b>';
				$infoLink.= $helpAtag;
				$infoLink.= $infoLabel;
				$infoLink.= '</a>';
				$infoLink.= '</b> | ';
			}
			$infoLink.= $appName;
			$infoLink.= '<br />';
		}
		return $infoLink;
	}

	/**
	 * assignConnectionDetails
	 *
	 * @param array $connectionSettings
	 * @return void 
	 */
	Protected function assignConnectionDetails($connectionSettings) {
		if( is_array($connectionSettings) ){
			$aUrl = explode( '/' , $this->settings['connection_url'] );
			$url = $this->settings['connection_prot'] .'://'. $aUrl[0];
			$link = ' <a target="_blank" href="'.$url.'">'.$this->settings['connection_url'].'</a>';
			$compare_autorun =  $this->settings['enable_autostart'] && $this->settings['enable_autostart'] <= $this->authService->getAuthUsersRecordset('group') ? '' : 'hidden';
		}else{
			$link= '##LL:connection##: ##LL:no_connection##';
			$compare_autorun =   'hidden';
		}
		$dataDir = '' . basename($this->settings['dataDir']);
		$this->view->assign( 'connection_name' , $dataDir . ' | ' . $link );
		
		$this->view->assign( 'compare_autorun_hide_class' , $compare_autorun );
	}

	/**
	 * getAdditionalCss
	 *
	 * @param array $cssClass optional own additional css options
	 * @return void 
	 */
	Public function getAdditionalCss( $cssClass = array() ) {
		$publicHref = trim( substr( SCR_DIR , strlen(dirname($_SERVER['SCRIPT_FILENAME'])) ) , '/' ) . '/Public/';
		$cssClass['BODY.logged_0'] = !empty( $this->settings['bgimage_login'] ) ? "\t\tBODY.logged_0 { background-attachment:fixed;height:100%;background-image: url('".$publicHref."BgImg/".$this->settings['bgimage_login']."'); -webkit-background-size: cover;-moz-background-size: cover;-o-background-size: cover;background-size: cover; }\n" : '';
		$cssClass['BODY.logged_1'] = !empty( $this->settings['bgimage'] ) ? "\t\tBODY.logged_1 { background-attachment:fixed;height:100%;background-image: url('".$publicHref."BgImg/".$this->settings['bgimage']."'); -webkit-background-size: cover;-moz-background-size: cover;-o-background-size: cover;background-size: cover; }\n" : '';
		$authUser = $this->authService->getAuthUsersRecordset();
        
        $aViewClasses = [];
		foreach( $this->view->registeredClass as $viewHelper ) {
			$viewCssClass =  $this->view->$viewHelper->getContainer( 'CSS' );
 			if( $viewCssClass ) $aViewClasses[] = implode( "\n" , $viewCssClass );
		}
 		$cssClass['css'] =  implode( "\n" , $aViewClasses );
		
		if( !isset($authUser['group']) ) $authUser['group'] = 0;
		
		$actionsButton = array( $this->view->action => $this->view->action );
		foreach( $this->subActions as $button => $aActions){
				foreach( $aActions as $act ) {
					if( method_exists( $this , $button . 'Action' ) ) $actionsButton[$act] = $button;
				}
		}
		$activeActionButton = $actionsButton[$this->view->action];
		$this->setMenueStatus();
		
		foreach( $this->allControllersObjects as $controllername => $objCtrl ){
				foreach( $objCtrl->accessRules as $action => $iRule ){
						// is button avaiable for the user?
						$objCtrl->setMenueStatus();
						if( $authUser['group'] >= $objCtrl->accessRules[$action] && ($this->settings['enable_sql'] || $action!= 'database') ) {
							if(isset( $this->disabledActions[$action]) || isset($objCtrl->disabledActions[$action]) ){
								$cssClass[$action] = "\t\t." . $action . "Action {pointer-events: none;color:#888;opacity:0.8;}\n";
							}elseif( $activeActionButton == $action  ){
									$additionalCss = "cursor:url('".$publicHref."Img/cursor_reload.png'),pointer;font-style:italic;text-decoration:underline;";
									$cssClass[$action] ="\t\t." . $action . "Action {display:inline !important;" . $additionalCss . "}\n";
							}else{
									$additionalCss = '';
									$cssClass[$action] ="\t\t." . $action . "Action {display:inline !important;" . $additionalCss . "}\n";
							}
						}else{
							$cssClass[$action] = "\t\t." . $action . "Action {display:none !important;}\n";
						}
				}
		}
		
		// FIXME: hack to eliminate documents-button if pdf-test failed
		if( false == $this->csvService->isClassAvaiable('DocumentsController') ) {
				$cssClass['documents'] = "\t\t." . "documentsAction {display:none !important;}\n";
		}
		
		return $cssClass;
	}

    /**
     * helper setMenueStatus
     *
     * @return void
     */
    public function setMenueStatus() {
			if( isset($this->disabledActions['welcome']) ) unset( $this->disabledActions['welcome'] );
	}

    /**
     * action welcomeAction
     *  this action has got a own View, 
     *  see script/Private/View/welcome.html
     *
     * @return void
     */
    public function welcomeAction() {
			//if( $this->authService->isLoggedIn ) return 'viewnotes';
			// protect Loginform against script-intrusions-by-adding-fields
			if(isset($this->settings['req']['login_button'] )){
				$allReplacements = array_flip(explode( ',' , 'user,pwd,act,newact,permalogin,PHPSESSID,login_button,login,loginApiAdminIndex' ));
				foreach( $this->settings['req'] as $isset => $setArr ){
					if( substr( $isset , 0 , 1 ) == '_' ) continue; // eg. '_ga' or '_utma'. Google-analytics-variables are allowed.
					if( substr( $isset , 0 , strlen('loginApiAdmin') ) == 'loginApiAdmin' ) continue; // part of cookiename
					if( !isset($allReplacements[$isset]) ) die( 'this input-field is forbidden: ' . $isset . '. Pleas reload browser and try it again.' );
				}
			}

			$text = '<i>##LL:next_start## <span id="refreshwatch">'.$this->settings['loginform_lifetime_s'].'</span> ##LL:seconds##.</i>';
			$text = '';

			$this->view->assign( 'is_permalogin_enabled' , $this->settings['login_life_period_h'] ? 1 : 0 );
			$this->view->assign( 'permalogin' , isset($this->settings['req']['permalogin']) && !empty($this->settings['req']['permalogin']) ? 'checked="1"' : '' );
			$this->view->assign( 'user' , isset($this->settings['req']['user']) ? $this->settings['req']['user'] : '' );
			$this->view->assign( 'salt' , $this->authService->getSalt() );
			$this->view->assign( 'pepper' , $this->authService->getPepper() );
			
			$this->view->append( 'FOOTER' , $this->view->javaScript->countdown( 'refreshwatch' ) );
			$this->view->append( 'HEAD' , '<meta http-equiv="refresh" content="'. $this->settings['loginform_lifetime_s'] .'; url='.$this->settings['url'].'" />' );
			$this->view->append( 'logintimeout' , $text );
	}


}


?>
