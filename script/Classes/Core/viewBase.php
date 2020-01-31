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
 * viewBase
 *  contains assign-mechanism
 *  expects settings over autoloader
 *   
 * extended by view
 * extended by obj
 * 
 */

class viewBase {

	/**
	 * Property settings
	 *
	 * @var array
	 */
	Public $settings = NULL;

	/**
	 * Property container
	 *
	 * @var array
	 */
	Public $container = NULL;

	/**
	 * Property labels
	 *
	 * @var array
	 */
	Protected $labels = array();
	
	/**
	 * __construct
	 *
	 * @param array $settings 
	 * @return  void
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		$this->initiate();
	}

	/**
	 * initiate
	 * called from __construct
	 *
	 * @return  void
	 */
	public function initiate() {
	}

    /**
     * append
     *
	 * @param string $varName
	 * @param mixed $varContent
     * @return void
     */
    public function append( $varName , $varContent ) {
		return $this->assign( $varName , $varContent , 'append' );
	}

    /**
     * prepend
     *
	 * @param string $varName
	 * @param mixed $varContent
     * @return void
     */
    public function prepend( $varName , $varContent ) {
		return $this->assign( $varName , $varContent , 'prepend' );
	}

    /**
     * assign
     *
	 * @param string $varName
	 * @param mixed $varContent
	 * @param string $append empty for overwride or 'append' or 'prepend'
     * @return void
     */
    public function assign( $varName , $varContent , $append = '' ) {
		if( is_array($varContent) ){
		// incoming value is an array. $append is everytime false in this case. (unusual case)
			if( isset($this->container[$varName]) && !is_array($this->container[$varName]) ){
			// if container is string define container as 2-dim array and insert string as first value
				$presetValue = $this->container[$varName];
				unset($this->container[$varName]);
				$this->container[$varName][ 'presetValue' ] = $presetValue;
			}
			// append all values from incoming array to array container
			foreach( $varContent as $nam => $val ) $this->container[$varName][$nam] = $val;

		}else{
		// incoming value is a string
			if( isset($this->container[$varName]) && $append ){
			// if container exists and the option "append" is set, 
					if( !is_array($this->container[$varName]) ){
						// if container is defined as string 
						// then define container as 2-dim array 
						// and insert the given string as first value
						$presetValue = $this->container[$varName];
						unset($this->container[$varName]);
						$this->container[$varName][ 'presetValue' ] = $presetValue;
					}
					// append incoming string to array container
					if( $append == 'prepend' ){
						array_unshift($this->container[$varName],$varContent);
					}else{
						$this->container[$varName][] = $varContent;
					}
			}else{
				// dont append: overwrite
				$this->container[$varName] = $varContent;
			
			}

		}
	}

    /**
     * getContainerAsPattern
     *
     * @param $replace
     * @return array
     */
    public function getContainerAsPattern( $replace = array() ) {
		if( !is_array($this->container) ) return $replace;
		foreach($this->container as $varName => $varContent ) {
				if( is_array($varContent) ){
					$replace['##'.$varName.'##'] = '';
					foreach( $varContent as $nam => $val ) $replace['##'.$varName.'##'] .= $val . "\n";
				}else{
					$replace['##'.$varName.'##'] = $varContent;
				}
		}
		return $replace;
	}

    /**
     * getContainerAsString
     * 
     * @param string $glue optional string between array-values
     * @return string
     */
    public function getContainerAsString( $glue = '<br />' ) {
			
		if( !is_array($this->container) ) return;
		
		foreach($this->container as $varName => $varContent ) {
				if( is_array($varContent) ){
					$rows = array();
					foreach( $varContent as $nam => $val ) $rows[] = implode( $glue , $val );
					$page = implode( $glue . "\n" , $rows );
				}else{
					$page = implode( $glue . "\n" , $varContent );
				}
		}
		// clear container
		$this->container = array();
		
		return $page;
	}

    /**
     * getContainer 
     * returns the container as 2-dimensional array
     * if container is only a 1-dim array then the method creates a second dimension
     *
     * @param string $variableName
     * @return array
     */
    Public function getContainer( $variableName = '' ) {
		$arrContainer = array();
		if( !is_array($this->container) ) return $arrContainer;
		foreach($this->container as $varName => $varContent ) {
				if( is_array($varContent) ){
					$arrContainer[$varName] = $varContent;
				}else{
					$arrContainer[$varName][] = $varContent;
				}
		}
		if( $variableName ){ return isset($arrContainer[$variableName]) ? $arrContainer[$variableName] : FALSE; }
		return $arrContainer;
	}
	
    /**
     * getLabel
     * 
     * @param string $text
     * @param string $fallback optional, default fallback is $text
     * @return string
     */
    public function getLabel( $text , $fallback = 'FALLBACK' ) {
		if( !is_array( $this->settings['labels'][$this->settings['language']] ) ) {
			$aPossLabs = array_keys( $this->settings['labels'] );
			$newLang = $aPossLabs[0];
			if( isset( $this->settings['labels'][$newLang] ) ) $aActiveLabels = $this->settings['labels'][$newLang];
		}else{
			$aActiveLabels = $this->settings['labels'][$this->settings['language']];
		}
		if( is_array($aActiveLabels) ) {
			if( isset( $aActiveLabels[$text] ) ) return $aActiveLabels[$text];
		}
		if( isset($this->labels[$text]) ) return $this->labels[$text];
		if( $fallback != 'FALLBACK' ) return $fallback; // return an empty value is possible, if affored
		return $text;
	}

}


?>
