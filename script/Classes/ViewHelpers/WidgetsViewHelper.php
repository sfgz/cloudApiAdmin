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
 * ObjectsViewHelper
 *  contains htmlObjects
 * 
 */

class WidgetsViewHelper extends \Drg\CloudApi\obj {

    /**
     * htmUploadFolderSelector
     *
     * @param array $settings
     * @return void
     */
    public function htmUploadFolderSelector( $settings ) {
		$preselectValue = isset($settings['dir']) ? $settings['dir'] : 'users';
		$options = array( 'users'=>'users' );
		if( $settings['use_quota_list'] ) $options['quota']='quota' ;
		if( $settings['use_delete_list'] ) $options['delete']='delete' ;
		if( count($options) > 1){
				$arguments = array( 'title' => $this->getLabel('choose_dir.title') , 'label' => $this->getLabel('choose_dir.value') ) ;
				$folderSelector = $this->objSelect( 'dir' , $options , $preselectValue , '' , $arguments ) . '&nbsp;';
		}else{
				$folderSelector = '<input type="hidden" name="dir" value="'.array_shift($options).'" />';
		}
		return $folderSelector ; 
		
	}


    /**
     * htmTimeoutSelector
     *
     * @param array $settings
     * @param string $buttonText
     * @param string $buttonName
     * @return void
     */
    public function htmTimeoutSelector( $settings , $buttonText , $buttonName = 'do[ok]') {
		$name = 'timeout';
		$options = explode( ',' , $settings['options']['exectimeout']['value'] );
		// special for timeout: restrict max timeout to max_execution_time as set in php.ini
		foreach($options as $i=>$opt) if( $opt > ini_get('max_execution_time') ) unset($options[$i]);
		
		$preselectValue = $settings['actualTimeout'];
		$additionalText = ' Sek.';
		$arguments = array( 'title' => $this->getLabel('execution_time.title') , 'label' => $this->getLabel('execution_time.label') ) ;

  		$this->assign( 'timeout' , $settings['actualTimeout'] );
  		
		$button =  $this->objSelect( $name , $options , $preselectValue , $additionalText , $arguments );
		$button .= ' <label>';
		$button .= ' '.$this->getLabel('interval').' in '.$this->getLabel('seconds').':';
		$button .= '<input type="text" name="refresh" value="'.$settings['refresh'].'" onkeypress="if( event.keyCode == 13 ){alert(\''.str_replace( '%1' , $buttonText , $this->getLabel('pdf_create_hint_use_button')).'\');return false;}" size="2" /> ';
		$button .= '</label>';
		$button .= '<input type="submit" name="'.$buttonName.'" value="'.$buttonText.'" />';
		return $button;
	}

    /**
     * htmlTableWithPager UNUSED   use objTablePager() direct
     * 
     * @param array $data
     * @param string $fieldlist
     * @return string
     */
    public function htmlTableWithPager( $data , $fieldlist = '' ) { 
		return $this->objTablePager( $data , $fieldlist );
	}

    /**
     * htmlChecklist
     * 
     * @param array $data
     * @param string $fieldgroupname how should checkboxes be named
     * @param string $columns default = 5
     * @param array $checkData default is empty array
     * @return string
     */
    public function htmlChecklist( $data , $fieldgroupname , $columns = 5 , $checkData = array() ) {
		if( !is_array($data) || !count($data) ) return '##LL:no_data##';
		// split data in parts to create to rows
		$rowsPerColumn = ceil(count($data) / $columns);
		for( $rowNr = 0 ; $rowNr <= ceil( count($data)/$rowsPerColumn ) ; ++$rowNr ){
				$colNr[$rowNr]=array_slice( $data , ($rowNr * $rowsPerColumn) , $rowsPerColumn );
				if(!count($colNr[$rowNr])) unset($colNr[$rowNr]);
		}
		$outString = '';
		foreach( $colNr as $nr => $partlyData ) {
			$outString .= "<div style=\"float:left;width:auto;padding:0 5px 15px 0;\">\n";
			foreach( $partlyData as $group => $usersRows ){ 
				$cssId = strtolower(str_replace(' ' , '_' , $group));
				$htmlId = rawurlencode($group) ;
				$htmlName = $fieldgroupname . '[' . rawurlencode($group) . ']';
				$js = 'var x = document.getElementsByClassName(\'members\');var i;';
				$js .= 'for (i = 0; i < x.length; i++) {if( x[i].id != \''.$cssId.'\'){x[i].style.display = \'none\';}} ;';
				$js .= 'if(document.getElementById(\''.$cssId.'\').style.display == \'none\'){';
				$js .= 'document.getElementById(\''.$cssId.'\').style.display = \'block\';';
				$js .= '}else{';
				$js .= 'document.getElementById(\''.$cssId.'\').style.display = \'none\';';
				$js .= '};';
				$onclick = ' onclick="'.$js.'"';
				$outString .=  '<p style="white-space:nowrap;margin:0px 8px 4px 0;" >';
				$outString .= '<label>' . $this->objCheckbox( $htmlName , isset($checkData[$htmlId]) , array('class'=>$fieldgroupname) ) . $group . ': </label>';
				$outString .=  '<span title="'.$group.': ##LL:memberclick.title##" style="cursor:pointer;" ' . $onclick .'> (' . count($partlyData[$group]) . ' ##LL:members_short##.)</span>';
				$outString .=  '</p>'."\n";
				
				if( !count($usersRows) ) continue;
				$outString .=  '<div id="'.$cssId.'" class="members clickbox" style="width:20em;display:none;">'."\n";
				$users = array();
				foreach($usersRows as $cell) $users[] = str_replace( ' ' , '&nbsp;' , $cell );
				$outString .=  implode( ', ' , $users );
				$outString .=  "</div>\n";
			}
			$outString .= "</div>\n";
		}
		return $outString."<div style=\"clear:left;\"> </div>\n";
	}

    /**
     * htmlFileTable
     * 
     * @param array $aFiles
     * @param array $settings
     * @return string
     */
    public function htmlFileTable( $aFiles , $settings ) {
			if( !is_array($aFiles) ) return;
			$aFileList = array();
			$aDirFileList = array();
			
			$fullTableConf = $settings['table_conf'];
			
			if( isset($settings['req']['act']) && $settings['req']['act'] ) '&amp;act=' . $settings['req']['act'];
			foreach( $aFiles as $filename => $shortname ) {
				$shortPath = substr( $filename, strlen($settings['dataDir']) );
				$fileShortname = pathinfo($shortname,PATHINFO_FILENAME);
				$dirShortname = pathinfo(dirname($filename),PATHINFO_FILENAME);
				if( $dirShortname == 'delete' && ( empty($settings['use_delete_list']) ) ) continue;
				if( $dirShortname == 'quota' && ( empty($settings['use_quota_list']) ) ) continue;
				$link2dir = '&amp;dir='.$dirShortname;
				$aFileList[$filename] = array(
						'filetype' => $settings['download_format'],
						'url' => $link2dir,
						'dirname'=>$dirShortname,
						'filename'=>pathinfo($shortname,PATHINFO_BASENAME),
						'pathname'=>$shortPath
				);
				$aFileList[$filename]['deleteLink'] = '<a title="' . $this->getLabel( 'delete_file' ). '" class="small" href="?delete='.pathinfo($shortname,PATHINFO_BASENAME).$link2dir.'" onclick="return window.confirm(\'##LL:file##: '.$fileShortname.'.csv\n##LL:files.delete##?\');" >'.$this->getLabel( 'files.delete' ).'...</a>';
// 				$aFileList[$filename]['renameLink'] = '<a title="##LL:renamefile##..." id="renbut_'.pathinfo($shortname,PATHINFO_FILENAME).'" href="?" class="small" onClick="document.getElementById( \'okrenbut_'.pathinfo($shortname,PATHINFO_FILENAME).'\' ).classList.remove(\'hidden\');document.getElementById( \'renbut_'.pathinfo($shortname,PATHINFO_FILENAME).'\' ).classList.add(\'hidden\');document.getElementById( \'rename_'.pathinfo($shortname,PATHINFO_FILENAME).'\' ).classList.remove(\'hidden\');return false;" >##LL:rename##...</a>';
// 				$aFileList[$filename]['renameLink'].= '<input type="submit" title="##LL:renamefile##" id="okrenbut_'.pathinfo($shortname,PATHINFO_FILENAME).'" class="small hidden" name="ok[rename]['.pathinfo($shortname,PATHINFO_FILENAME).']" value="##LL:rename##" />';
// 				$aFileList[$filename]['renameLink'].= '<input type="text" class="hidden" name="rename['.$dirShortname.'/'.pathinfo($shortname,PATHINFO_FILENAME).']" id="rename_'.pathinfo($shortname,PATHINFO_FILENAME).'" value="'.pathinfo($shortname,PATHINFO_FILENAME).'">';
 				$aFileList[$filename]['renameLink'] = '';

				if( $settings['allowTableEditor'] && isset($settings['table_conf'][$fileShortname]) ){
					$aFileList[$filename]['editLink'] = '<a title="' . $this->getLabel( 'newact.tables.title' ). '" class="small" href="?act=tableeditor&amp;tab='.$fileShortname.'" >##LL:edit##</a>';
				}elseif( $settings['allowTableEditor'] ){
					$aFileList[$filename]['editLink'] = '<a title="' . $this->getLabel( 'newact.tables.title' ). '" class="small" href="?act=tableeditor&amp;tab=default" >##LL:edit_default##</a>';
 					$aFileList[$filename]['editLink'] .= ' <a class="small" href="?act=tableeditor&amp;tab=default&amp;tablename='.$fileShortname.'&amp;dupliz=1" >##LL:new##</a>';
				}
				$aDirFileList[$dirShortname][$filename] = $aFileList[$filename];
			}
			if( !count($aFileList) ) return;
			return $this->objFileTable($aFileList);
	}

	/**
     * action htmlEditTable_editForm
     *
     * @param array $Fieldnames
     * @param string $actualTablename
     * @param array $defaultTables
     * @return string
     */
    public function htmlEditTable_editForm( $Fieldnames , $actualTablename, $defaultTables = array( 'default' , 'group_quota' , 'delete_list' )) {
			$isUsertable = array_search( $actualTablename , $defaultTables ) ? FALSE : TRUE;
			$isSysTable = ( $defaultTables[ array_search( $actualTablename , $defaultTables )] == $actualTablename ) ? 1 : 0;
			
			$buttons ='';
			$page ='';
			$titleField ='';
			$formTable ='';
			
			// filename edit field
			$FilenameFieldOptions = array('id'=> 'users_table_file' ,'style'=>'width:22em;','title'=> '##LL:filename##' , 'placeholder'=>'##LL:filename##','list'=>'csvfileslist','label'=>'<b>##LL:title##&nbsp;</b><span style="font-size:85%;font-weight:normal;">('.($isSysTable ? 'system' : 'user').'-table)</span>&nbsp;');
			if($isSysTable) {
				$FilenameFieldOptions['disabled'] = 'disabled';
				$titleField .= '<input type="hidden" name="tablename" value="'.$actualTablename.'" > ';
				$fieldname = 'hid_tablename';
			}else{
				$fieldname = 'tablename';
			}
			$titleField .= '<div style="width:auto;"> ';
			$titleField .= $this->objText( $fieldname , $actualTablename , $FilenameFieldOptions );
			$titleField .= '&nbsp;<label for="users_table_file" style="font-size:85%;font-weight:normal;">##LL:filename## </label>';
			$titleField .= '</div> ';
			
		
			// formfields to edit table
			$paramFieldsConf = array(
				'FIELDS' => array(
					'size' => '250px',
					'list' => 'fieldslist'
				) , 
				'CHAR' => array(
					'size' => '150px'
				) , 
				'INDEX' => array(
					'size' => '25px'
				) , 
			);
			
			$this->tableFunctions = New \Drg\CloudApi\Utility\TableFunctionsUtility( $this->settings );
			foreach( $this->tableFunctions->afforedFields as $functionname => $afforedFields ){
					$aFldlist = explode( ',' , trim($afforedFields) );
					foreach( $aFldlist as $field ) $paramFieldsConf[$field]['depending'][] = $functionname;
			}
			$methodsList = $this->tableFunctions->getMethodsList();
			sort($methodsList);
			array_unshift( $methodsList , '(##LL:none##)' );
			
			// **mapping** 
			$formTable .= '<table border="0" cellspacing="3"> ';
			$formTable .= '<tr><th>##LL:config.fieldname##</th><th>##LL:foreign_field##</th><th>##LL:function##</th><th> </th></tr>';
			foreach($Fieldnames as $fieldname => $content){
				$formTable .= '<tr>';
				$formTable .= '<td><label for="text_' .$fieldname . '">' .$fieldname . '</label> </td>';
				$formTable .= '<td>' . $this->objText( 'table_conf[mapping]['.$fieldname . '.FIELD]' ,  isset($content['FIELD']) ? $content['FIELD'] : '' , array('id'=>'text_' .$fieldname . '','list'=>'fieldslist','title'=> '##LL:foreign_field##' , 'placeholder'=>'##LL:foreign_field##') ).' </td>';
				$formTable .= '<td>' . $this->objSelect( 'table_conf[mapping]['.$fieldname . '.FUNCTION]' , $methodsList , isset($content['FUNCTION']) ? $content['FUNCTION'] : '' , array('id'=>$fieldname . '.FUNCTION','onchange'=>'toggleFunctionRelatedElements(\''.$fieldname.'\')') ).' </td>';
				$formTable .= '<td style="white-space:nowrap;">';
				foreach($paramFieldsConf as $pField => $aFldConf ){
						$value = isset($content['PARAM'][$pField]) ? $content['PARAM'][$pField] : '';
						$function = isset($content['FUNCTION']) ? $content['FUNCTION'] : '';
						$fieldOptions = array( 
							'title'=> 'Field: ' . $pField . ' ', 
							'placeholder'=>$pField , 
							'class' => 'funct_'. implode( ' funct_' , $aFldConf['depending'] ) .' param_of_'.$fieldname , 
							'style'=>'width:'.$aFldConf['size'].';'
						);
						if( isset($aFldConf['list']) ) $fieldOptions['list'] = $aFldConf['list'];
						$formTable .= '' .$this->objText( 'table_conf[mapping]['.$fieldname . '.PARAM.'.$pField.']' ,  $value , $fieldOptions ) . ' ';
				}
				$formTable .= '</td>';
				$formTable .= '</tr>';
			}
			$formTable .= '</table> ';
			
			// buttons
			if( 'new' == $actualTablename ) {
				$buttons .= ' <a href="?act=tableeditor&tab='.$actualTablename.'" class="small">create</a>';
			}elseif( isset($this->settings['req']['tablename']) && !isset($this->settings['req']['table_conf']['mapping']) ){
				$buttons .= ' <input type="submit" name="save" value="##LL:create##" />';
			}else{
				$buttons .= ' <input type="submit" name="delask" value="'.($isSysTable ? '##LL:reset##' : '##LL:files.delete##').'..." />';
				if($isUsertable) $buttons .= ' <input type="submit" name="dupliz" value="dupliz" />';
				$buttons .= ' <input type="submit" name="save" value="ok" />';
			}

			$fullTableConf = $this->settings['table_conf'];
			$tableSettings = isset($fullTableConf[$actualTablename]) ? $fullTableConf[$actualTablename] : array();
			$samplesLines = array();
			if(isset($tableSettings['samples'])) {
					foreach( $tableSettings['samples'] as $ix=>$row ){
						if(is_array($row))$samplesLines[] = implode( $this->settings['sys_csv_delimiter'] , $row );
					}
			}
			$encodedText = htmlspecialchars(stripslashes(implode("\n",$samplesLines)));
			$sampleButton = empty($encodedText) ? '' : ' &nbsp; ##LL:load_default_data##: <input class="csv" type="submit" name="samples['.$actualTablename.']" value="'.$actualTablename.'.csv" />';
			$samplesTextarea = '<div style="padding:0 0 5px 0"><label>##LL:source_sample##: <br /><textarea rows="10" cols="80" name="table_conf[samples]">'.$encodedText.'</textarea></label></div> ';
			
			$page .= $titleField;
			$page .= $formTable;
			$page .= '<div style="padding:5px 0">'.$buttons.''.$sampleButton.'</div>';
			$page .= $samplesTextarea;
			
			return $page;
	}

    /**
     * htmlEditTable_tablesSelector
     * return a select html-element with files/tables defined in local(!) table_conf
     * only search for setting-files in data-folders, not in Config/default-folders
     * 
     * @param string $actualTablename
     * @return string
     */
    public function htmlEditTable_tablesSelector( $actualTablename ) {
			$configFile = $this->settings['dataDir']  . 'table_conf.json';
			if( file_exists($configFile) ){
					$aResult = $this->fileHandlerService->readCompressedFile( $configFile );
// 					$aResult = json_decode( file_get_contents( $configFile ) , true );
// 					if( !is_array($aResult) || !count($aResult) ) $aResult = unserialize( file_get_contents( $configFile ) );
			}
			$settings = isset($aResult) && is_array($aResult) && count($aResult) ? $aResult : $this->settings;
			if( !isset($settings['table_conf']) ) return false;
			ksort($settings['table_conf']);
			$selOpt = array_keys($settings['table_conf']);
			$fileSelector = $this->objSelect( 'tab2view' , $selOpt , $actualTablename , array('style'=>'max-width:320px;','size'=>count($selOpt),'onchange'=>'if( this.value == \'select...\' ){location = \'?act=tableeditor\';}else{ location = \'?act=tableeditor&tab=\' + this.value;}') );
			if( isset($this->settings['req']['tab']) && !empty($this->settings['req']['tab']) ) $fileSelector .='<p><a class="small" href="?act=tableeditor">##LL:abort##</a></p>';
			return $fileSelector;
	}

    /**
     * htmlImageBox
     * 
     * @param string $imageFile
     * @param integer $thumbnailDim
     * @param boolean $isDimHeigt optional default is width (FALSE) [ false: $thumbnailDim = width | true: $thumbnailDim = height ]
     * @param string $inputFilename optional name of input-filed for file-upload. default is 'userfile'
     * @param string $additionalUrlQuery optional like 'opt1=&amp;opt2=123'
     * @return string
     */
    public function htmlImageBox( $imageFile , $thumbnailDim , $isDimHeigt = FALSE , $inputFilename = 'userfile' , $additionalUrlQuery = '' ) {
			if(isset($this->settings['req']['delete'])){
				// handle deletion
				$imageFolder = dirname($imageFile) . '/';
				if( file_exists( $imageFolder . $this->settings['req']['delete'] ) && $imageFile == $imageFolder . $this->settings['req']['delete'] ){
						unlink( $imageFolder . $this->settings['req']['delete'] );
				}
			}
			if( !file_exists($imageFile) || !is_file($imageFile) ) {
				$uploadEntry = '  <div title="##LL:pdf_file_formats_text##: (gif ##LL:format_server_depending##), jpg, jpeg, png." style="width:auto;float:left;margin:0;padding:0;">';
				$uploadEntry .= '    <p><label>##LL:upload_file##:&nbsp;<input name="' . $inputFilename . '" id="' . str_replace( '[' , '_' , str_replace( ']' , '' , $inputFilename)) . '" type="file" /></label></p>';
				$uploadEntry .= '  </div>';
				return $uploadEntry;
			}
			
			$aDimNames = array( FALSE=>'width' , TRUE=>'height' );
			$dimnam  = $aDimNames[$isDimHeigt];
			$dimnam2 = $isDimHeigt ? $aDimNames[FALSE] : $aDimNames[TRUE];

			$shortname = basename($imageFile);
			list($d['width'], $d['height'], $type, $attr) = getimagesize($imageFile);
			if(empty($d[ $dimnam ])){
				$tmbDim = $thumbnailDim;
				$tmbDimensionText = $dimnam.'="'.$tmbDim.'px"';
			}else{
				$tmbDim =  $d[ $dimnam ] > $thumbnailDim  ? $thumbnailDim : $d[ $dimnam ] ;
				$tmbDim2 =  round( $d[ $dimnam2 ] * $tmbDim / $d[ $dimnam ] , 0 );
				$tmbDimensionText = $dimnam.'="'.$tmbDim.'px"';
				$tmbDimensionText .= ' ' . $dimnam2 . '="' . $tmbDim2 .'px"';
			}
			
			$baseHref =  trim( substr( SCR_DIR , strlen(dirname($_SERVER['SCRIPT_FILENAME'])) ) , '/').'/';
			$act = $this->settings['req']['act'] ? '&amp;act=' . $this->settings['req']['act'] : '';
			$aLinkPart = '<a title="##LL:files.delete##..." href="?delete='.$shortname.'&amp;dir='.pathinfo(dirname($imageFile),PATHINFO_FILENAME).$act.$additionalUrlQuery.'" onclick="return window.confirm(\'Datei: '.$shortname.'\n##LL:files.delete##?\');" >';
			
			if( !empty($additionalUrlQuery) && strpos( ' ' . $additionalUrlQuery , '&' ) != 1 ) $additionalUrlQuery = '&amp;' . $additionalUrlQuery;
			
			$pdfImageBox = '<div title="##LL:pdf_file_formats_text##: (gif ##LL:format_server_depending##), jpg, jpeg, png." style="width:auto;float:left;margin:0;padding:3px;margin-top:8px;">';
			$pdfImageBox .= '<div style="margin:0 5px;padding:5px;border:1px solid #aaa;">';
			$pdfImageBox .= $aLinkPart;
			$pdfImageBox .= '<img style="vertical-align:top;" '.$tmbDimensionText.' alt="'.$shortname.'" src="'.$baseHref.'Public/Img/logo.php?p='.$imageFile.'" />';
			$pdfImageBox .= '</a>';
			$pdfImageBox .= ( $tmbDim > 100 && empty($setDim) ) || ( $tmbDim > 75 && !empty($setDim) ) ? '<br />' : '&nbsp;';
			$pdfImageBox .= $aLinkPart. $shortname.' ';
			$pdfImageBox .= '</a>';
			if( !empty($d['height']) ) {
				$pdfImageBox .= $d['width'] . 'x'. $d['height'] . ' px ';
			}
			$pdfImageBox .= '</div>';
			$pdfImageBox .= '</div>';
			return $pdfImageBox;
	}

}


?>
