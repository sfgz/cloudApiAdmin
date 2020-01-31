<?php
namespace Drg\CloudApi\Utility;

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
require_once('CreateJobsUtility.php' );

/**
 * Class JobsEditorUtility
 */

class JobsEditorUtility extends \Drg\CloudApi\Utility\CreateJobsUtility  {

	/**
	* JobsEditor
	* 
	* @param array $dbData
	* @param string $listName
	* @return string
	*/
	public function JobsEditor( $dbData , $listName = '' ) {
			if( !$listName ) return;
			
			$jobsArr = array();
			$this->view->widgets->settings = $this->settings ;
			
			$aJobsLists = '';
			$groupAmount = $this->settings['group_amount'];
			for( $grp = '' , $z=1 ; $z<=$groupAmount ; ++$z ){
					$grp .= ',grp_' . $z ;
			}
			if( isset($dbData['UsersMissed']) && (empty($listName) || $listName == 'UsersMissed') ){ 
				$jobsArr = $this->redimJobData( $dbData['UsersMissed'] );
				$jobsArr = $this->addCheckboxesToList( $jobsArr , 'ID' , $listName );
				$aJobsLists.= '<H2>##LL:create_new##</H2>'; 
				$aJobsLists.= $this->view->widgets->objTablePagerBut( $jobsArr , 'ID,DISPLAYNAME' . $grp );
				
			}elseif( isset($dbData['UsersMaybeObsolete']) && (empty($listName) || $listName == 'UsersMaybeObsolete') ){ 
				$jobsArr = $this->redimJobData( $dbData['UsersMaybeObsolete']  );
				$jobsArr = $this->addCheckboxesToList( $jobsArr , 'ID' , $listName );
				$aJobsLists.= '<H2>##LL:delete_accounts##</H2>';
				$aJobsLists.= $this->view->widgets->objTablePagerBut( $jobsArr , 'ID,DISPLAYNAME' . $grp . ',whitelist' ); 

 			}elseif( isset($dbData['GroupMissed']) && (empty($listName) || $listName == 'GroupMissed') ){ 
				$jobsArr = $this->redimGroupJobData( $dbData , 'GroupMissed');
				$jobsArr = $this->addCheckboxesToList( $jobsArr , 'missed' , $listName );
				$aJobsLists.= '<H2>##LL:missed##</H2>';
 				$aJobsLists.= $this->view->widgets->objTablePagerBut( $jobsArr , 'missed,ID,DISPLAYNAME'.$grp ); 
			
			}elseif( isset($dbData['GroupMaybeObsolete']) && (empty($listName) || $listName == 'GroupMaybeObsolete') ){ 
				$jobsArr = $this->redimGroupJobData( $dbData , 'GroupMaybeObsolete' );
				$jobsArr = $this->addCheckboxesToList( $jobsArr , 'obsolete' , $listName );
				$aJobsLists.= '<H2>##LL:obsolete##</H2>';
 				$aJobsLists.= $this->view->widgets->objTablePagerBut( $jobsArr , 'obsolete,ID,DISPLAYNAME' . $grp . ',whitelist' ); 
			
			}
 			
			return $aJobsLists;
	}

	/**
	* getActionLinks
	* add links instead of buttons to list
	* 
	* @param array $dbData
	* @return array
	*/
	public function getActionLinks( $dbData ) {
			$ok = isset($this->settings['req']['ok']) ? array_keys($this->settings['req']['ok']) : array();
			$onlyOneList = array_pop($ok);
			if(!$onlyOneList) $onlyOneList = '0';
			
			$aTables = array(
				'GroupMissed' => '##LL:missed##',
				'GroupMaybeObsolete' => '##LL:obsolete##',
				'UsersMissed' => '##LL:create_new##',
				'UsersMaybeObsolete' => '##LL:delete_accounts##'
			);
			
			$checkedJobs = $this->getCheckedJobs();
			
			$button = '';
			$button .= '<table>';
			$button .= '<tr><th>##LL:edit_export_list_title##</th><th class="integer"> Checked</th><th class="integer"> Total</th></tr>';
			foreach($aTables as $tab => $lab){
				$amount = !isset($dbData[$tab]) ? 0 : count($dbData[$tab]) ;
				$checked = 0;
				if( isset($dbData[$tab]) && is_array($dbData[$tab]) ){
					foreach( $dbData[$tab] as $tabIndex =>$tabRow) $checked += isset($checkedJobs['chk_'.$tab.'Job'][$tabIndex]) ? 1 : 0;
				}
				$possibleLink = $onlyOneList == $tab ? '&rarr; <i>'.$lab.' </i>' : '<a href="?act=vergleich&amp;ok['.$tab.']='.$tab.'">'.$lab.'</a><span style="color:transparent;"> &rarr; </span>';
				$button .= '<tr><td>'.$possibleLink.'</td><td class="integer">'. ( $amount == $checked ? '<span>'.$checked.'</span>' : '<span style="color:red;">'.$checked.'</span>') . '</td><td class="integer">'.  $amount. '</td></tr>';
			}
			$button .= '</table>';
			
			return $button;
	}

	/**
	* add checkboxes to list
	* 
	* @param array $dbData
	* @return array
	*/
	public function addCheckboxesToList( $dbData , $name='obsolete' , $onlyOneList ) {
		if( !count( $dbData ) ) return $dbData;
		$formname = $onlyOneList;
		//$isFirst = 1;
		foreach( $dbData as $tabIndex => $tabRow ){
			if( isset($dbData[$tabIndex]) && !empty($tabIndex) ){
				ksort($dbData[$tabIndex]);
				$isChecked = isset($this->settings['req']['chk_'.$formname][$tabIndex]) ? ' checked="checked"' : '';
				$isWhitelisted = isset($this->settings['req']['whitelist_'.$formname][$tabIndex]) ? ' checked="checked"' : '';
				$isDisabled = $isWhitelisted ? ' disabled="disabled"' : '';

				$dbData[$tabIndex][$name] = '<label><input class="selector" type="checkbox"'.$isDisabled.' id="chk_'.$tabIndex.'"'.$isChecked.' name="chk_'.$formname.'['.$tabIndex.']" value="'.$tabIndex.'" />'.$tabRow[$name].'</label>';
				$dbData[$tabIndex][$name] .= '<input type="hidden" name="chk_'.$formname.'['.$tabIndex.'_hidden]" value="0" />';

				$displaynme = !isset($tabRow['DISPLAYNAME']) ? implode( ', ', array_keys($tabRow) ) :  $tabRow['DISPLAYNAME'] . ( $tabRow[$name]!=$tabRow['DISPLAYNAME'] ? ' - ' . $tabRow[$name] : '' ) ;
				$dbData[$tabIndex]['whitelist'] = '<label><input type="checkbox" id="white_'.$tabIndex.'"'.$isWhitelisted.' name="whitelist_'.$formname.'['.$tabIndex.']" onclick="document.getElementById(\'chk_'.$tabIndex.'\').disabled = document.getElementById(\'white_'.$tabIndex.'\').checked;" value="'.$tabIndex.'" />' . $displaynme . '</label>';
				$dbData[$tabIndex]['whitelist'] .= '<input type="hidden" name="whitelist_'.$formname.'['.$tabIndex.'_hidden]" value="0" />';
			}
		}
		return $dbData;
	}

	/**
	* redimGroupJobData
    *  used by method JobsEditor() called in vergleichAction
	* redimensionates a 2-3 dim array to 2 dim array
	* e.g.  data[n][groups][x] to data[n][grp_x]
	* 
	* adds some formatting text ( + and - )
   *  FIXME: similar to Drg\CloudApi\Utility\CreateJobsUtility->enrichUpdatableCloudusersWithEditMarker()
	* 
	* @param array $dbData
	* @param string $function
	* @return array
	*/
	public function redimGroupJobData( $dbData , $function ) {
		$dataToList = $dbData[$function];
		$funcToAdd = $function == 'GroupMaybeObsolete' ? 'GroupMissed' : 'GroupMaybeObsolete' ;
		$dataToAdd = $dbData[$funcToAdd];
		$checkedDb = $this->getCheckedJobs();
				
		$outArr = array();
		foreach( $dataToList as $tabIndex => $tabRow ){
			foreach( $tabRow as $fld => $cell ){
				if( is_array($cell) ){
					if(strtolower($fld) == 'groups'){
						$z=0;
						$tempGroups = array();
						foreach($cell as $subFld => $subCnt){
							if( count($dataToAdd) && isset($dataToAdd[$subFld . '_._'. $tabRow['ID']]) ){
								if(!isset($checkedDb[ 'chk_' . $funcToAdd . 'Job' ][$subFld . '_._'. $tabRow['ID']])) continue;
								$sign = $funcToAdd == 'GroupMaybeObsolete' ? '<b>&ndash;</b>&nbsp;' : '<b>+</b>&nbsp;';
								$tempGroups[1][$subFld] = $sign . $subFld;
							}else{
								$tempGroups[0][$subFld] = $subFld;
							}
						}
						ksort($tempGroups);
						$z=0;
						foreach( $tempGroups as $grpRow){
							foreach( $grpRow as $fldNam => $prsRow){
								++$z;
								$outArr[$tabIndex]['grp_' . $z] = $prsRow; 
							}
						}
					}
				}else{
					$outArr[$tabIndex][$fld] = $cell ;
				}
			}
		}
		return $outArr;
	}

	/**
	* redimJobData
	* redimensionates a 2-3 dim array to 2 dim array
	* e.g.  data[n][groups][x] to data[n][grp_x]
	* 
	* adds some formatting text ( + and - )
	* 
	* @param array $dataToList
	* @return array
	*/
	public function redimJobData( $dataToList ) {
		$outArr = array();
		foreach( $dataToList as $tabIndex => $tabRow ){
			foreach( $tabRow as $fld => $cell ){
				if( is_array($cell) && strtolower($fld) == 'groups'){
					$z=0;
					foreach($cell as $subFld => $subCnt){
						++$z;
						$outArr[$tabIndex]['grp_' . $z ] = $subFld;
					}
				}else{
					$outArr[$tabIndex][$fld] = $cell ;
				}
			}
		}
		return $outArr;
	}


	
}
