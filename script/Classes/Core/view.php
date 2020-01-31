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
 * view
 *  contains render-mechanism
 *  contains objects with different classes from viewHelper\
 *  
 *  contains assign-mechanism
 *  expects settings over constructor
 *   
 *  implemented by controllerCore as controllerCore->view
 * 
 */

class view extends \Drg\CloudApi\viewBase {

	/**
	 * Property action
	 *
	 * @var string
	 */
	Public $action = '';

	/**
	 * Property controllersPartial
	 *
	 * @var string
	 */
	Public $controllersPartial = '';

	/**
	 * Property registeredClass
	 *
	 * @var array
	 */
	Public $registeredClass = NULL;

	/**
	 * Property objects
	 *
	 * @var \Drg\CloudApi\ViewHelpers\WidgetsViewHelper
	 */
	Public $objects = NULL;

	/**
	 * initiate
	 * called from __construct
	 *
	 * starts ViewHelper and appends them as object to the view eg.  view->objects
	 * at the end the view will collect the search-patterns from view->widgets->containers 
	 * to render the table with them.
	 * 
	 * $replace['##Abc##'] = view->containers['Abc'] 
	 * $replace['##XYZ##'] = view->widgets->containers['XYZ'] 
	 *
	 * @return  void
	 */
	public function initiate() {
		$allClasses = get_declared_classes();
		if( !isset($allClasses) || !count($allClasses) ) return;
		foreach($allClasses as $class){
			$aClassFragments = explode( '\\' , $class );
			$classname = array_pop($aClassFragments);
			$patternPos = strpos( $classname , 'ViewHelper' );
			if( !$patternPos ) continue;
			$newClassName = lcfirst( substr( $classname , 0 , $patternPos ) );
			if( isset($this->registeredClass[ $newClassName]) ) continue;
 			$this->$newClassName = new $class( $this->settings );
 			// e.g. $this->objects
 			$this->registeredClass[$newClassName] = $newClassName;
		}
	}

    /**
     * renderPage
     *
     * $param array $replace optional additional data
     * @return array
     */
    public function renderPage( $replace = array() ) {
		foreach( $this->registeredClass as $viewHelper ) {
		
		$cssarr =  $this->$viewHelper->getContainer( 'CSS' );
		}
    
			$replace =  $this->getContainerAndHelpersContainerPattern($replace);
			$replace =  $this->getServerPattern($replace);
			$replace =  $this->getSettingsPattern($replace);
			$replace =  $this->getLanguagePattern($replace);
			
			$replace['##toolbar##'] = $this->renderPartial( $this->controllersPartial , $replace );
			
			$template = $this->getActionTemplate();
			
			$layout = $this->getLayout( $this->action , 'general' );
			
			$replace['##BODY##'] = str_replace( array_keys( $replace ) , $replace , $template );
			
			$templateWrappedInLayout = str_replace( array_keys( $replace ) , $replace , $layout );
			return $templateWrappedInLayout;
	}

    /**
     * getSettingsPattern
     *
     * @param $replace
     * @return array
     */
    public function getSettingsPattern( $replace = array() ) {
			foreach( $this->settings as $settingName => $settingValue ) {
				if( is_array($settingValue) ) continue;
				$replace['##settings.'.$settingName.'##'] = $settingValue;
			}
			return $replace;
	}

    /**
     * getServerPattern
     *
     * @param $replace
     * @return array
     */
    public function getServerPattern( $replace = array() ) {
			$replace['##SERVER_NAME##'] = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
			$replace['##PHP_SELF##'] = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
			$replace['##MAX_EXECUTION_TIME##'] = ini_get('max_execution_time');
			return $replace;
	}

    /**
     * getLanguagePattern
     *
     * @param $replace
     * @return array
     */
    public function getLanguagePattern( $replace = array() ) {
		if( !is_array( $this->settings['labels'][$this->settings['language']] ) ) {
			$aPossLabs = array_keys( $this->settings['labels'] );
			$newLang = $aPossLabs[0];
			if( isset( $this->settings['labels'][$newLang] ) ) $aActiveLabels = $this->settings['labels'][$newLang];
		}else{
			$aActiveLabels = $this->settings['labels'][$this->settings['language']];
		}
		if( !is_array($aActiveLabels) ) return $replace;
		
		foreach( $aActiveLabels as $key => $label ){
			$replace[ '##LL:' . $key . '##' ] = str_replace( array_keys( $replace ) , $replace , $label );
		}
		return $replace;
	}

    /**
     * getContainerAndHelpersContainerPattern
     *
     * @param $replace
     * @return array
     */
    public function getContainerAndHelpersContainerPattern( $replace = array() ) {
		$replace =  $this->getContainerAsPattern( $replace );
		foreach( $this->registeredClass as $viewHelper ) $replace =  $this->$viewHelper->getContainerAsPattern( $replace );
		return $replace;
	}

    /**
     * getContainerAndHelpersContainerPattern
     *
     * @param $replace
     * @return array
     */
    public function getContainerAndHelpersContainerNames( $replace = array() ) {
		$replace =  $this->getContainerAsPattern( $replace );
		foreach( $this->registeredClass as $viewHelper ) $replace =  $this->$viewHelper->getContainerAsPattern( $replace );
		return $replace;
	}

    /**
     * renderPartial
     *
     * $param string $partial
     * $param array $replace
     * @return array
     */
    public function renderPartial( $partial , $replace = array() ) {
			$replace =  $this->getContainerAndHelpersContainerPattern($replace);
			$rawTemplate = $this->getPartial( $partial );
			return empty($rawTemplate) ? '' : str_replace( array_keys( $replace ) , $replace , $rawTemplate );
	}


    /**
     * getPartial
     *
     * @param $partial
     * @return void
     */
    public function getPartial( $partial ) {
		$file = SCR_DIR . 'Private/Partials/' . $partial . '.html';
		if( !file_exists($file) ) die( 'view.php getPartial #105: partial [ Private/Partials/' . $partial . '.html ] nicht gefunden.' );
		return file_get_contents( $file );
	}

    /**
     * getLayout
     *
     * @param $layout
     * @param $fallback
     * @return void
     */
    public function getLayout( $layout , $fallback = 'general' ) {
		$file = SCR_DIR . 'Private/Layouts/' . $layout . '.html';
		if( !file_exists($file) ) $file = SCR_DIR . 'Private/Layouts/' . $fallback . '.html';
		if( !file_exists($file) ) die( 'view.php getLayout #116: layout [ Private/Layouts/' . $layout . '.html ] und [ ' . $fallback . '.html ] nicht gefunden.' );
		return file_get_contents( $file );
	}

    /**
     * getActionTemplate
     *
     * @return void
     */
    public function getActionTemplate() {
		$layout = file_exists( SCR_DIR . 'Private/View/' . $this->action . '.html' ) ? $this->action : 'default' ;
		$file = SCR_DIR . 'Private/View/' . $layout . '.html';
		if( !file_exists($file) ) die( 'view.php getActionTemplate #128: view [ Private/View/' . $layout . '.html ] nicht gefunden.' );
		return file_get_contents( $file );
	}
	

}


?>
