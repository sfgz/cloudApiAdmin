<?php
namespace Drg\CloudApi\ViewHelpers;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2017 Daniel Rueegg <colormixture@verarbeitung.ch>
 *
 *  All rights reserved
 *
 *  This script is
 *  free software; you can redistribute it and/or modify
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
 * JavaScriptViewHelper
 *  contains javascript functions
 * 
 */

class JavaScriptViewHelper extends \Drg\CloudApi\obj {

    /**
     * countdown
     *
     * @param string $elementId
     * @return string
     */
    public function countdown( $elementId ) {
			return '
			<script type="text/javascript">
			/*<![CDATA[*/
				window.onload = countdown("'.$elementId.'");
			/*]]>*/
			</script>';
	}

    /**
     * unused_toggleCronrelatedElements
     *
     * @return string
     */
    public function unused_toggleCronrelatedElements() {
			return '
			<script type="text/javascript">
			/*<![CDATA[*/
				window.onload = toggleCronrelatedElements();
			/*]]>*/
			</script>';
	}

    /**
     * addStarterFunction
     *
     * @param string $functionName
     * @return string
     */
    public function addStarterFunction( $functionName ) {
			return '
			<script type="text/javascript">
			/*<![CDATA[*/
				window.onload = ' . $functionName .  '();
			/*]]>*/
			</script>';
	}

    /**
     * toggleCategorySelectors
     *
     * @param string $formname
     * @return string
     */
    public function toggleCategorySelectors( $formname = '') {
			return '
			<script type="text/javascript">
			/*<![CDATA[*/
				function toggleCategorySelectors( object ) {
					divel = document.getElementsByClassName("radiobehavior");
					for (var el = 0; el < divel.length; el++) { 
							if( divel[el].name == object.attributes["name"].value ){
								divel[el].checked = true;
							}else{
								divel[el].checked = false;
							}
					}
					document.getElementById("'.$formname.'").submit();
				}
			/*]]>*/
			</script>';
	}

    /**
     * toggleFunctionRelatedElements
     *
     * @param array $aElements
     * @return string
     */
    public function toggleFunctionRelatedElements( $aElements ) {
			$prcList = '';
			foreach( $aElements as $element ){
				$prcList .= 'window.onload = toggleFunctionRelatedElements("'.$element.'");';
			}
			$jsText = '
			<script type="text/javascript">
			/*<![CDATA[*/
				'.$prcList.'
			/*]]>*/
			</script>';
			return $jsText;
	}


}


?>
