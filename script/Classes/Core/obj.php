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
 * obj
 *  contains raw html objects
 *  expects settings over autoloader 
 *  
 *  extended by ViewHelpers
 * 
 */

class obj extends \Drg\CloudApi\viewBase {

	/**
	 * fileHandlerService
	 *
	 * @var \Drg\CloudApi\Services\FileHandlerService
     * @inject
	 */
	Public $fileHandlerService = NULL;

	/**
	 * Property pagerIsSet
	 *
	 * @var boolean
	 */
	private $pagerIsSet = false;

	/**
	 * Property tablePagerNumber
	 *
	 * @var int
	 */
	private $tablePagerNumber = 0;

	/**
	 * initiate
	 * called from __construct
	 *
	 * @return  void
	 */
	public function initiate() {
			$this->fileHandlerService = new \Drg\CloudApi\Services\FileHandlerService();
	}

    /**
     * objCheckbox
     * 
     * @param string $name
     * @param string $preselectValue
     * @param array $arguments
     * @return string
     */
    public function objCheckbox( $name , $preselectValue=false , $arguments = array() ) {
		
		if( isset($arguments['label']) ){
			$label = $arguments['label'];
			unset($arguments['label']);
		}else{
			$label = '';
		}
		
		if( !isset($arguments['id']) ) $arguments['id'] = str_replace( '[' , '_' , str_replace( ']' , '' , $name ) ) ;
		
		$sArgList = '';
		if( isset($arguments['slider']) ){
			$isSlider = TRUE;
			$sliderCss = $arguments['slider'];
			$sArgList .= ' class="radiobehavior" ';
			unset($arguments['slider']) ;
		 }else{
			$isSlider = FALSE;
		 }
		
		foreach( $arguments as $argName => $argValue ){ $sArgList .= ' '.$argName.'="'.$argValue.'"'; }

		$isSel = array( false=> '' , true=> ' checked="1"' );
		
		$newObject = '<input type="checkbox"' . $sArgList . ' ' . $isSel[$preselectValue] . ' name="' . $name . '" value="1">';
		// on request we need to notice the checkbox, even if it its empty - excepted the disabled checkboxes
		if( !isset($arguments['disabled']) && empty($isSlider) ) {
			$newObject .= '<input type="hidden" name="chk_' . $name . '" value="1">';
		}
		
		if( empty($label) && empty($isSlider) ) return $newObject;
		
		if( empty($isSlider) ) return '<label'.( isset($arguments['title']) ? ' title="'.$arguments['title'].'"' : '' ).'>' . $label . $newObject . '</label>';
		
		$slider = '<label class="switch'.( isset($arguments['disabled']) ? ' disabled' : '' ).'"'.( isset($arguments['title']) ? ' title="'.$arguments['title'].'"' : '' ).'>';
		$slider.= $newObject;
		$slider.= '<span '.( isset($arguments['onclick']) ? ' onclick="'.$arguments['onclick'] . '" ' : '' ).' class="'.( isset($arguments['class']) ? $arguments['class'] . ' ' : '' ).$sliderCss.'">';
		$slider.= '</span>';
		$slider.= '</label><input type="hidden" name="chk_' . $name . '" value="1">';
		if( !empty($label)) $slider .= '<label '.( isset($arguments['disabled']) ? ' class="disabled"' : '' ).' style="vertical-align:baseline;padding-left:4px;" for="'.$arguments['id'].'" '.( isset($arguments['title']) ? ' title="'.$arguments['title'].'"' : '' ).'>' . $label . '</label>';
		return $slider;
	}

    /**
     * objFileTable
     * 
     * @param array $arguments
     * @return string
     */
    public function objFileTable( $arguments = array() ) {
			if( !is_array($arguments) ) return;

			$isEdit = 0;
			foreach( $arguments as $filename => $fileRow ) {$isEdit += isset($fileRow['editLink']) ? 1 : 0;}
			
			$sFilesList =  "\n".'<table class="filetable" cellspacing="0" cellpadding="0">';
			$sFilesList .=  "\n".'<tr>';
			$sFilesList .=  "\n".'<th>' . $this->getLabel( 'files.path' ). '</th>';
			$sFilesList .=  "\n".'<th>' . $this->getLabel( 'files.display' ). '</th>';
			$sFilesList .=  "\n".'<th title="##LL:include## ##LL:calculated_data##"> inc </th>';
			$sFilesList .=  "\n".'<th>download</th>';
			$sFilesList .=  "\n".'<th title="' . $this->getLabel( 'delete_file' ). '">' . $this->getLabel( 'file' ). '</th>';
			if( $isEdit ) $sFilesList .=  "\n".'<th title="' . $this->getLabel( 'newact.tables.title' ). '">' . $this->getLabel( 'newact.tables.label' ). '</th>';
			$sFilesList .=  "\n".'</tr>';
			
			foreach( $arguments as $filename => $fileRow ) {
				if( $fileRow['dirname'] == 'users' ){
					$isnewCheckbox = !isset( $this->settings['req']['chk_calcFile'][$fileRow['filename']] );
					$isChecked = ( $isnewCheckbox || !isset($this->settings['req']['calcFile']) || isset( $this->settings['req']['calcFile'][$fileRow['filename']] ) ? TRUE : FALSE );
					$options = array('label'=>'','title'=>'##LL:include## ##LL:calculated_data## '.$fileRow['dirname'].'/'.$fileRow['filename'] );
					$checkBox =  $this->objCheckbox( 'calcFile[' . $fileRow['filename'].']' , $isChecked , $options );
				}else{
					$checkBox = '';
				}
				$sFilesList .=  "\n".'<tr>';
				$sFilesList .=  "\n".'<td style="padding:1px 5px 1px 0;">'.$fileRow['dirname'].'</td>';
				$sFilesList .=  "\n".'<td style="padding:1px 5px;"><a id="link_'.pathinfo($fileRow['filename'],PATHINFO_FILENAME).'" href="?ok['.$fileRow['filename'].']='.$fileRow['filename'].$fileRow['url'].'" >'.pathinfo($fileRow['filename'],PATHINFO_FILENAME).'</a></td>';
				$sFilesList .=  "\n".'<td style="padding:1px 0 1px 5px;">'. $checkBox .'</td>';
				$sFilesList .=  "\n".'<td style="padding:1px 5px;"><a title="'.basename($filename).'" class="'.$fileRow['filetype'].'" href="?dwn='.$fileRow['filename'].$fileRow['url'].'" >'.$fileRow['filetype'].'</a></td>';
				$sFilesList .=  "\n".'<td style="padding:1px 0 1px 5px;">'.$fileRow['deleteLink'].' '.$fileRow['renameLink'].'</td>';
				if( $isEdit ) $sFilesList .=  "\n".'<td style="padding:1px 0 1px 5px;">'.$fileRow['editLink'].'</td>';
				$sFilesList .=  "\n".'</tr>';
			}
			$sFilesList .=  "\n".'</table>';
			
			return $sFilesList;
	}

    /**
     * objFileUpload
     * 
     * @param string $name
     * @return string
     */
    public function objFileUpload( $name ) {
		$form = '' . $this->getLabel('upload_file') . ': 
				<input name="' . $name . '" type="file"  />
				<input type="submit" value="&uarr; '.$this->getLabel('upload').'" />
		';
		
		return $form;
	}

    /**
     * helper_getSelectOptionsFromMethod
     * 
     * @param string $method madatory procname-as-string
     * @param string $parameter optional proc-option as (static) string 
     * @param \Drg\CloudApi\obj $object optional, defailt is this. An instant of the class in wich the method is registered, as object
     * @return array
     */
    public function helper_getSelectOptionsFromMethod( $method , $parameter = NULL , $object = NULL ) {
			if( empty($object) ) $object = $this;
		
			if( !method_exists( $object , $method ) ) return FALSE;
				
			if( empty($parameter) ){ // call method without options
				$options = $object->$method();
			}else{ // call method with (static) options
				$classMethod = new \ReflectionMethod( $object , $method );
				$aClassParam = $classMethod->getParameters();
				$possibleParams = count($aClassParam);
				
				if( $possibleParams == 0 ) return $object->$method();
				if( !strpos( $parameter , ',' ) ) return $object->$method( $parameter );
				
				$aParams = explode( ',' , $parameter );
				if( $possibleParams < count($aParams) ){ // too many parameters given for this method
					$aParams = array_slice( $aParams , 0 , $possibleParams );
				}
				
				if( !count($aParams) ){ $options = $object->$method(); // not possible (?)
				}elseif( count($aParams) == 1 ){ $options = $object->$method( $aParams[0] ); // not possible (?)
				}elseif( count($aParams) == 2 ){ $options = $object->$method( $aParams[0] , $aParams[1] );
				}elseif( count($aParams) >= 3 ){ $options = $object->$method( $aParams[0] , $aParams[1] , $aParams[2] );
				}
			}
		
			return $options;
	}

    /**
     * objSelect
     * 
     * @param string $name
     * @param string $options
     * @param string $preselectValue
     * @param string $additionalText
     * @param array $arguments
     * @return string
     */
    public function objSelect( $name , $options , $preselectValue='' , $additionalText='' , $arguments = array() ) {
		if( is_array($additionalText) && !count($arguments) ){
			$arguments = $additionalText;
			$additionalText='';
		}
		$opt = '';
		$isSel = array( false=> '' , true=> ' selected="1"' );
		if( is_array($options) && count($options) ){foreach( $options as $sValue ) {
			$opt .= '<option value="'.$sValue.'"'.$isSel[ $preselectValue == $sValue ].'>'.$sValue.$additionalText.'</option>';
		}}
		
		$sArgList = '';
		if( isset($arguments['label']) ){
			$label = $arguments['label'];
			unset($arguments['label']);
		}else{
			$label = '';
		}
		foreach( $arguments as $argName => $argValue ){ $sArgList .= ' '.$argName.'="'.$argValue.'"'; }
		
		if( empty($label) ) return '<select'.$sArgList.' name="'.$name.'" >'.$opt.'</select>';

		$labelTitle = isset($arguments['title']) ? ' title="'.$arguments['title'].'"' : '' ;
		return '<label'.$labelTitle.'>'.$label.' <select'.$sArgList.' name="'.$name.'" >'.$opt.'</select></label>';
			
	}

    /**
     * objTable
     * 
     * @param array $data
     * @param string $fieldlist
     * @return string
     */
    public function objTable( $data , $fieldlist = '' , $disableLabel = false) { 
		if( !count( $data ) ) return '<p>Keine Daten</p>';
		$n = "\n";
		$tt = "\t\t";
		$t = "\t";
		$table = '';
		
		// dont translate table-headers automatically
		$translateTableHeaders = FALSE;

		if( !$disableLabel && !$this->pagerIsSet ) $table .= $t . '<b>' . count($data) . ' ' . $this->getLabel( 'recordsets.' . (count($data)==1 ? 1 : 0) ) . '</b>';
		$this->pagerIsSet = false;
		
		$aFields = array();
		if( $fieldlist ){
			$aFields = explode( ',' , $fieldlist);
		}else{
			foreach( $data as $ix => $row ){
				foreach( $row as $fld => $cnt ) $aFields[$fld] = $fld;
			}
		}
		
		$table .= $t.'<table class="datatable" border="1" cellpadding="3" cellspacing="0">'.$n;
		$table .= $t.'<thead>'.$n;
		$table .= $tt.'<tr>'.$n;
		if( $translateTableHeaders ){
			foreach( $aFields as $fld ) $table .= $tt.$t.'<th title="'.$fld.'">'. $this->getLabel( $fld )  .'</th>'.$n;
		}else{
			foreach( $aFields as $fld ) $table .= $tt.$t.'<th title="'.$this->getLabel( $fld ).'">'. $fld  .'</th>'.$n;
		}
		$table .= $tt.'</tr>'.$n;
		$table .= $t.'</thead>'.$n;
		$table .= $t.'<tbody>'.$n;
		foreach( $data as $ix => $row ){
				$table .= $tt.'<tr>'.$n;
				foreach( $aFields as $fld ){
					$table .= $tt.$t.'<td title="'.$this->getLabel( $fld ).' #'.$ix.'">';
					if( isset($row[$fld]) ) $table .= $row[$fld];
					$table .= '</td>'.$n;
				}
			$table .= $tt.'</tr>'.$n;
		}
		$table .= $t.'</tbody>'.$n;
		$table .= $t.'</table>'.$n;
		return $n.$table;
	}

    /**
     * objTablePager
     * 
     * @param array $data
     * @param string $fieldlist for table
     * @return string
     */
    public function objTablePager( $data , $fieldlist = '' ) {
		$table = $this->objTable_pager( $data , $fieldlist );
		return $table;
	}

    /**
     * objTablePagerBut
     * 
     * @param array $data
     * @param string $fieldlist for table
     * @return string
     */
    public function objTablePagerBut( $data , $fieldlist = '' ) {
		$butOk = $this->objTable_pagerButton();
		$table = $this->objTable_pager( $data , $fieldlist , $butOk );
		return $table;
	}

    /**
     * objTablePager
     * 
     * @param array $data
     * @param string $fieldlist for table
     * @param string $okButton
     * @return string
     */
    public function objTable_pager( $data , $fieldlist = '' , $okButton = '' ) {
		$this->tablePagerNumber += 1;
		
		$n = "\n";
		$t = "\t";
		$PAGER = ''.$okButton;
		$displayAmount = $t . '<b>' . count($data) . ' ' . $this->getLabel( 'recordsets.' . (count($data)==1 ? 1 : 0) ) . '</b>';
		
		if( isset($this->settings['req']['maxrows']) && isset($this->settings['req']['pagerid']) && $this->settings['req']['pagerid']==$this->tablePagerNumber ) $this->settings['maximal_rows_in_forms'] = $this->settings['req']['maxrows'];
		
		$lastPager = ceil(count($data)/$this->settings['maximal_rows_in_forms']);
		
		// $this->settings['req']['pager']   page-Nr
		// $this->settings['req']['maxrows'] amount of rows per page
		// $this->settings['req']['pagerid'] is the uid of the pager ans remains mostly 1
		$pointedPage = 1;
		if( isset($this->settings['req']['pager']) && isset($this->settings['req']['pagerid'])  && $this->settings['req']['pagerid']==$this->tablePagerNumber ){
			if( $this->settings['req']['pager'] > $lastPager ){
				$pointedPage = $lastPager;
			}elseif( !empty($this->settings['req']['pager']) ){
				$pointedPage = $this->settings['req']['pager'];
			}
		}

		$nextPager = $pointedPage >= $lastPager ? 1 : $pointedPage+1;
		$pastPager = $pointedPage <= 1 ? $lastPager : $pointedPage-1;

		// collect url query parameters
		$requiredRequest = 'act=##action##';
		if( isset( $this->settings['req']['calcFile'] ) && is_array( $this->settings['req']['calcFile'] ) ){
            foreach( $this->settings['req']['calcFile'] as $fldNam => $fldVal ){
                $requiredRequest .= '&amp;calcFile['. $fldNam .']='.$fldVal;    
            }
		}
		$requiredRequest .= '&amp;pagerid='.$this->tablePagerNumber;
		$requiredRequest .= '&amp;maxrows='.$this->settings['maximal_rows_in_forms'];
		if(isset($this->settings['req']['dir'])) $requiredRequest .= '&amp;dir='.$this->settings['req']['dir'];
		if(isset($this->settings['req']['ok'])){
			$keyWord = 'ok';
		}elseif( isset($this->settings['req']['do']) ){
			$keyWord = 'do';
		}
		if(isset($keyWord)){
			$clickedButton = $this->settings['req'][$keyWord];
			$keysList = array_keys($clickedButton);
			$okList = array_pop($keysList);
			$requiredRequest .= '&amp;'.$keyWord.'['.$okList.']='.$okList;
		}
		
		// create PAGER
		// javascript
		$rawJs = 'var selopt = this.options[this.selectedIndex].value;';
		
		$pageQuery = '&amp;pager=\' + selopt + \'&amp;maxrows='.$this->settings['maximal_rows_in_forms'].'';
		$pageJs = $rawJs.'window.location.assign(\''.$this->settings['url'] .'?'.$requiredRequest.$pageQuery.'#pg_' . $this->tablePagerNumber.'\')';
		
		$maxpgQuery = '&amp;maxrows=\' + selopt + \'&amp;pager='.$pointedPage.'';
		$maxpgJs = $rawJs.'window.location.assign(\''.$this->settings['url'] .'?'.$requiredRequest.$maxpgQuery.'#pg_' . $this->tablePagerNumber.'\')';
		
		$requiredRequest .= '#pg_' . $this->tablePagerNumber ;
		// select-options for page-number selector
		for( $selOpt = array() , $z=1 ; $z <= $lastPager ; ++$z ) $selOpt[$z] = $z;
		// assemble pager, set token 'pagerIsSet' to TRUE and return result
		if( count($data) > $this->settings['maximal_rows_in_forms'] ) {
			$requiredRequest = '&amp;' . $requiredRequest;
			if( $pointedPage > 1 ){
				$PAGER.= $t .  ' <a href="?pager=1'.$requiredRequest.'">&laquo;</a> ' . $n;
				$PAGER.= $t .  ' <a href="?pager='.$pastPager.$requiredRequest.'">&larr;</a> ' . $n;
			}else{
				$PAGER.= $t .  ' &laquo; &larr; ' . $n;
			}
			$PAGER.= $t .  ' [ ' . $this->getLabel( 'page' ) . ' ';
			$PAGER.= $this->objSelect( "pager" , $selOpt , $pointedPage , '' , array( 'title'=>$this->getLabel('goto_page') , 'onchange' => $pageJs ) );
			$PAGER.=  ' / ' . $lastPager . ' ] ';
			if( $pointedPage < $lastPager ){
				$PAGER.= $t .  ' <a href="?pager='.$nextPager.$requiredRequest.'">&rarr;</a> ' . $n;
				$PAGER.= $t .  ' <a href="?pager='.$lastPager.$requiredRequest.'">&raquo;</a> ' . $n;
			}else{
				$PAGER.= $t .  ' &rarr; &raquo; ' . $n;
			}
			
			$PAGER.=  ' | ';
			$PAGER.= $this->objSelect( "maxrows" , explode( ',' , $this->settings['options']['maximal_rows_in_forms']['value'] ) , $this->settings['maximal_rows_in_forms'] , '' , array( 'title'=>$this->getLabel('rows_per_page') ,'onchange' => $maxpgJs ));
			$PAGER.=  ' ' . $this->getLabel( 'rows_p_page' ) . ' ';
			
			$untilRecordset = $pointedPage >= $lastPager ? count($data) : ($pointedPage * $this->settings['maximal_rows_in_forms'] );
			$displayAmount = ' | ' . $displayAmount . ', ' . $this->getLabel( 'show' ) . ' ' . (($pointedPage-1) * $this->settings['maximal_rows_in_forms'] + 1) . '-' . $untilRecordset  . $n;
		}
		
		$this->pagerIsSet = TRUE;
		$table = '<p id="pg_'.$this->tablePagerNumber.'">' . $PAGER . $displayAmount  . '</p>';
		
		if( count($data) > $this->settings['maximal_rows_in_forms'] ) $data = array_slice( $data , (($pointedPage-1) * $this->settings['maximal_rows_in_forms']) , $this->settings['maximal_rows_in_forms'] , TRUE);
		$table .= $this->objTable( $data , $fieldlist );
		
		return $table;

	}

    /**
     * objTable_pagerButton
     *  create the save button for checkboxes
     *  
     * @return string
     */
    private function objTable_pagerButton( ) {
        $okButton = '<p>';
        $okButton .= '<input type="submit" name="ok[##ok##]" title="##LL:save## in ##ok##" value="##LL:save##" />';
        if( isset( $this->settings['req']['pagerid'] ) ){
                $okButton .= '<input type="hidden" name="pager" value="'.$this->settings['req']['pager'].'" />';
                $okButton .= '<input type="hidden" name="maxrows" value="'.$this->settings['req']['maxrows'].'" />';
                $okButton .= '<input type="hidden" name="pagerid" value="'.$this->settings['req']['pagerid'].'" />';
        }
        $okButton .= '<label><input type="checkbox" value="1" onclick="checkAll( \'selector\' , this.checked );" >##LL:select_all_onpage## </label> ';
        $okButton .= '</p>';
        return $okButton;
	}

    /**
     * objText
     * 
     * @param string $name
     * @param string $preselectValue
     * @param array $arguments
     * @return string
     */
    public function objText( $name , $preselectValue='' , $arguments = array() ) {
		
		if( isset($arguments['label']) ){
			$label = $arguments['label'];
			unset($arguments['label']);
		}else{
			$label = '';
		}
		
		if( !isset( $arguments['onkeypress'] ) ) $arguments['onkeypress'] = 'if( event.keyCode == 13 ){alert(\'##LL:save_hint_onclick##\');return false;}';
		
		$sArgList = '';
		foreach( $arguments as $argName => $argValue ){ $sArgList .= ' '.$argName.'="'.$argValue.'"'; }
		
		$newObject = '<input type="text"'.$sArgList.' name="'.$name.'" value="'.$preselectValue.'" />';
		
		if( empty($label) ) return $newObject;
		
		return '<label>' . $label . $newObject . '</label>';
	}

    /**
     * objTextarea
     * 
     * @param string $name
     * @param string $preselectValue
     * @param array $arguments
     * @return string
     */
    public function objTextarea( $name , $preselectValue='' , $arguments = array() ) {
		
		if( isset($arguments['label']) ){
			$label = $arguments['label'];
			unset($arguments['label']);
		}else{
			$label = '';
		}
		$arguments['onkeypress'] = '';
		$sArgList = '';
		foreach( $arguments as $argName => $argValue ){ $sArgList .= ' '.$argName.'="'.$argValue.'"'; }
		
		$newObject = '<textarea'.$sArgList.' name="'.$name.'">'.$preselectValue.'</textarea>';
		
		if( empty($label) ) return $newObject;
		
		return '<label>' . $label . $newObject . '</label>';
	}

    /**
     * objViewArrayContents
     * 
     * @param array $data
     * @param string $glue
     * @return string
     */
    public function objViewArrayContents( $data , $glue = ', ') { 
		$testTab = $data;
		$firstLine = array_shift($testTab);
		if( is_array($firstLine) ){
			$page = '';
			foreach( $data as $ix => $dataRow ) {
				$testTab = $dataRow;
				$firstLine = array_shift($testTab);
				if( !is_array($firstLine) ){
					$page .= '<p style="border:1px solid black;"><u>'.$ix.'</u><br />'. $this->objViewArrayContents($dataRow).'</p>';
				}else{
					foreach( $dataRow as $rix => $subRow ) {
						$page .= '<p style="border:1px solid red;"><u>'.$ix.'.'.$rix.'</u><br />'. $this->objViewArrayContents($subRow).'</p>';
					}
				}
			}
			return $page;
		}else{
			$titleBar = "\n\t<b>" . count($data) . " " . $this->getLabel( "recordsets." . (count($data)==1 ? 1 : 0) ) . "</b><br />";
			$body = implode( $glue , $data );
			return $titleBar . $body ;
		}
	}

    /**
     * printArray
     *
	 * @param array $arrayToView
     * @return void
     */
    public function printArray($arrayToView) {
		ob_start();
		
		print_r($arrayToView);
		
		$arrayAsString = ob_get_contents();
		
		ob_end_clean();
		
		return '<pre>' . $arrayAsString . '</pre>';
	}

    /**
     * getArrayKeys
     * 
     * @param string $arrayName
     * @return array
     */
    public function getArrayKeys( $arrayName ) {
		if( !isset($this->settings[$arrayName]) ) return $arrayName;
		if( !is_array($this->settings[$arrayName]) ) return $arrayName;
		$outArr = array();
		foreach( array_keys( $this->settings[$arrayName] ) as $key ) $outArr[ $key ] = $key;
		return $outArr;
	}
	
    /**
     * getFilesInDir
     * 
     * used by settings-editor 
     * in method \Drg\CloudApi\Controller\ConfigurationContoller->getSettingsAsObjList()
     * to create select-options
     * for field 'bgimage'
     *
     * @param string $partialPath
     * @return void
     */
    public function getFilesInDir( $partialPath ) {
		$dir = SCR_DIR . trim( $partialPath , '/' );
		$aDirInfo = array();
		if( !file_exists($dir) ) return $aDirInfo;
		$d = dir($dir);
		while (false !== ($entry = $d->read())) {
			if( '.' == $entry || '..' == $entry ) continue;
			if( !is_file( $dir . '/' . $entry ) ) continue;
			$aDirInfo[] =  $entry;
		}
		$d->close();

		return $aDirInfo;
	}
	
    /**
     * getDirsInDataDir
     * 
     * used by settings-editor 
     * in method \Drg\CloudApi\Controller\ConfigurationContoller->getSettingsAsObjList()
     * to create select-options
     * for field 'bgimage'
     *
     * @param string $$authUserGroup optional, leave empty to return all
     * @return void
     */
    public function getDirsInDataDir( $authUserGroup = NULL ) {
		if( !is_dir(DATA_DIR) ) return false;
		$d = dir( DATA_DIR );
		if( !$d ) return false;
		$aDirInfo = array();
		while (false !== ($entry = $d->read())) {
			if( '.' == $entry || '..' == $entry ) continue;
			if( !is_dir( DATA_DIR . '/' . $entry ) ) continue;
			if( $authUserGroup === NULL ) $aDirInfo[$entry] =  $entry;
			if( $authUserGroup < $this->helper_getDirsInDataDir_getAuthorisationForFolder( DATA_DIR . '/' . $entry ) ) continue;
			$aDirInfo[$entry] =  $entry;
		}
		$d->close();

		return $aDirInfo;
	}

    /**
     * helper_getDirsInDataDir_getAuthorisationForFolder
     * return a integer with ACL-rule for folder or false
     * ACL-rule is the authorisation-grade wich is affored to access directory as defined in local(!) table_conf
     * Only search for setting-files in data-folders, not in Config/default-folders
     * 
     * @param string $dataSubdirectory
     * @return string
     */
    public function helper_getDirsInDataDir_getAuthorisationForFolder( $dataSubdirectory ) {
			$filename = $dataSubdirectory . '/settings.json';
			if( !file_exists($filename) ) return FALSE;
			
			$aSettingsFromFile = $this->fileHandlerService->readCompressedFile( $filename );
/*			
			$fileContent = file_get_contents( $filename );
			// decode: try json method
			$aSettingsFromFile = json_decode( $fileContent , true );
			// decode: try serialize method
			if( !is_array($aSettingsFromFile) ) $aSettingsFromFile = unserialize( $fileContent );*/
			
			if( !isset( $aSettingsFromFile['directory_autorisation'] ) ) return FALSE;
			return $aSettingsFromFile['directory_autorisation'];
	}

    /**
     * getAclForActiveUser
     * 
     * @param string $$authUserGroup optional, default = 100 returns all rules
     * @return string
     */
    public function getAclForActiveUser( $authUserGroup = 100 ) { 
			$rulesList = explode( ',' , $this->settings['acl_rules_list']);
			sort($rulesList);
			if( $authUserGroup == 100 ) return $rulesList;
			foreach($rulesList as $ix => $ruleNr ){
				if( $ruleNr > $authUserGroup ){
						$rulesList = array_slice( $rulesList , 0 , $ix );
						break;
				}
			}
			return $rulesList;
	}
}


?>
