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
 * ModelsViewHelper
 *  contains htmlObjects
 * 
 */

class ModelsViewHelper extends \Drg\CloudApi\obj {

	/**
	 * model
	 *
	 * @var \Drg\CloudApi\modelBase
	 */
	public $model = NULL;

	/**
	 * Property properties
	 *
	 * @var array
	 */
	Private $properties = array();

	/**
	 * Property viewoptions
	 *
	 * @var array
	 */
	Public $viewoptions = array( 
			'index_editable' => FALSE ,
			'option_defaults' => array(
				'table'=>TRUE,
				'title'=>TRUE,
				'hints'=>TRUE,
				'addrecords'=>TRUE,
				'labels'=>TRUE,
				'okbutton'=>TRUE,
				'fields'=>FALSE,
				'restrict'=>FALSE,// group of active user or false for all
			),
	);

    /**
     * htmlModelEditor
     * 
     * @param string  $modelname classname without trailing 'Model' eg 'user'. Firs-case-insensitive, the chars 'User' would work as well. But Camelcase if affored eg. 'mySqlConnector'
     * @param array   $aFilter  optional array with OR filter-conditions where arranme is fieldname: array( 'group' => '>=5' , field2 => cond2 )
     * @param array   $lockedRecords optional To lock records means lock fields that are set as locked in model->properties
     * @param array   $aOptions optional [ addrecords=>true , title=true , hints=>true ] If addrecords = false then lockedRecords is obsolete. 
     * @return string
     */
    public function htmlModelEditor( $modelname , $aFilter = NULL , $lockedRecords = NULL , $aOptions = NULL ) {
			
			foreach( $this->viewoptions['option_defaults'] as $opt => $val ){
				if(  !isset($aOptions[$opt]) ) {$aOptions[$opt] = $val;} else { $this->viewoptions['option_defaults'][$opt] = $aOptions[$opt]; }
			}
			
			// load the model to detect parameters like tablename, indexfield, fieldnames and properties.
			$Model = str_replace( '#MODELNAME#' , ucFirst($modelname) , "\\Drg\\CloudApi\\Models\\#MODELNAME#Model") ;
			$this->model = new $Model( $this->settings );
			$this->model->initiate( $lockedRecords );
			$data = $this->model->getRecordsets( $aFilter );
			
			if(  $aOptions['table'] == TRUE ){
				$resultString =  $this->obj_htmlModelTable( $data , $aOptions );
			}else{
				$resultString =  $this->obj_htmlModelSheet( $data , $aOptions );
			}

			$title = '';
			if( $aOptions['title'] == TRUE ) $title .= $this->obj_HtmlActualModelTitle();
			if( $aOptions['hints'] == TRUE ) $title .= $this->obj_HtmlHints();
			$prependButtons =  method_exists( $this->model , 'getPrependButtons' ) ? $this->model->getPrependButtons() : ''; 
			if( !empty($prependButtons) ) $title .= '<p>' . $prependButtons . '</p>';
			
			$okButton = $aOptions['okbutton'] == TRUE ?"\t\t\t\t".'<input type="submit" name="ok[save]" value="'.$this->getLabel( 'save' ).'" title="##LL:save##" />':'';
			if( count($data) > 5 ) $title .= '<p>' . $okButton . ' ' . count($data) . ' ##LL:rows##</p>';

			if( $aOptions['addrecords'] == TRUE ){
					$okButton .= ' <input type="button" onclick="'.$this->jsToDisplayNewInputElements( 'newCell' ) . '" value="##LL:new_recordset##" />' . "\n";
					$okButton .=  method_exists( $this->model , 'getAppendButtons' ) ? $this->model->getAppendButtons() : '';
			}

			$this->append( 'CSS' , "\t\tDIV.content DIV.ModelViewHelper-htmlModelTable H3 {margin-top:0px;} \nDIV.ModelViewHelper-htmlModelTable H2 {margin-top:0;} \n\nDIV.ModelViewHelper-htmlModelTable TH {vertical-align:top;} \n" );
			return $this->wrapHtml( 'ModelViewHelper-htmlModelTable' , 'div' , $title .  $resultString . $okButton );
			
	}
	
    /**
     * obj_HtmlHints
     * 
     * @return string
     */
    Private function obj_HtmlHints() {
			return  '<p style="margin-bottom:0;font-style:italic;">##LL:edit_cell_hint##.</p>';
	}
	
    /**
     * obj_HtmlActualModelTitle
     * 
     * @return string
     */
    public function obj_HtmlActualModelTitle() {
			$title = '<h3>';
			$title .= ''.$this->getLabel('models.name.'.$this->model->tablename,$this->model->tablename).'';
			$title .= ' <span style="font-weight:normal"> ('.( $this->model->store_global ? $this->getLabel('global') : trim( $this->getLabel('local') . ' ' . basename($this->settings['dataDir']) ) ).')</span>';
			$title .= '</h3>';
			return $title;
	}
	
    /**
     * obj_htmlModelSheet
     * 
     * @param array $data
     * @param array $aOptions
     * @return string
     */
    public function obj_htmlModelSheet( $data , $aOptions = NULL  ) {
		$this->syntaxService = new \Drg\CloudApi\Services\SyntaxService();
		// get original fieldlist
		$aFieldList = array_keys( $this->model->properties );
		if( $aOptions['fields'] ){
			foreach( $aFieldList as $ix=>$fld ) { if( !isset($aOptions['fields'][$fld]) ) unset($aFieldList[$ix]) ;}
		}
		$n = "\n";
		$tt = "\t\t";
		$t = "\t";
		$loadDefaultsButton = ( !is_array($data) || !count($data) ) && $aOptions['addrecords']==TRUE ? $tt.$tt.' <input type="submit" name="ok[default]" value="##LL:load_default_data##" />'.$n : '';
		
		$table = $t.'<table style="margin:10px 0;" cellpadding="3" cellspacing="0">'.$n;
		
		if(is_array($data) && count($data) ){
			$dataKeys = array_keys( $data );
			$ix = array_shift( $dataKeys );
			$row = array_shift( $data );
			
			$rowButtons =  method_exists( $this->model , 'getRowButtons' ) ? $this->model->getRowButtons($ix) : '<input type="submit" name="delete['.$this->model->tablename.']['.$ix.']" value="##LL:files.delete##" onclick="return window.confirm(\'Index: '.$ix.'\n##LL:files.delete##?\');" class="small" />';

			
			foreach( $aFieldList as $fld ){
				if( $this->model->indexfield == $fld ) {
					$table .= $tt.'<tr><!-- data row for id #'.$ix.' -->'.$n;
					if($aOptions['labels']) $table .= $tt.$t.'<th title="'. $this->getLabel('model.'.$this->model->tablename.'.'. $fld . '.title' , $fld) .'">'. $this->getLabel('model.'.$this->model->tablename.'.'. $fld , $fld)  .'</th>'.$n;
					$table .= $tt.$t.'<td title="index, '.$this->getLabel( 'not_editable' ).'" style="cursor:pointer;" onclick="'.$this->jsToDisplayEditInputElements($ix).'"><i>'.$ix.'</i></td>'.$n;
					$table .= $tt.'</tr>'.$n;
					continue;
				}
				$value = isset($row[$fld]) ? $row[$fld]: '';
				$table .= $tt.'<tr><!-- data row for id #'.$ix.' -->'.$n;
				
				if($aOptions['labels']) $table .= $tt.$t.'<th title="'. $this->getLabel('model.'.$this->model->tablename.'.'. $fld . '.title' , $fld) .'"><label for="'.$ix.'_'.$fld.'" onclick="'.$this->jsToDisplayOneEditInputElement($ix.'_'.$fld).'">'. $this->getLabel('model.'.$this->model->tablename.'.'. $fld , $fld)  .'</label></th>'.$n;
				
				$table .= $tt.$t.'<td title="'.$this->getLabel('model.'.$this->model->tablename.'.'. $fld , $fld) .' index:'.$ix.' '. $this->getLabel('model.'.$this->model->tablename.'.'. $fld . '.title' , '') .'">';
				
				switch( $this->model->properties[$fld]['type'] ){
					case 'pass1way':
						$labelText = ( empty($value) ? '(empty)' : $value );
					break;
					case 'pass2way':
						$labelText = ( empty($value) ? '(empty)' : '****************' );
					break;
					case 'textarea':
							$labelText = $this->syntaxService->wikiToHtml($value);
					break;
					case 'checkbox':
							$labelText = '';
					break;
					default:
						$labelText = $value === '' ? '(empty)' :  str_replace( "\n" , "<BR />\n" , $value );
					break;
				}
				
				if( $this->model->isRecordLocked($ix) == 'record' || ($this->model->isRecordLocked($ix) == 'fields' && isset($this->model->properties[$fld]['locked']))
				){
						$table .= $tt . $tt . '<span>'.$labelText.'</span>';
				}else{
						$table .= $this->obj_editOnClickField( $value , $ix , $fld , 'cls_'.$ix ).$n;
						// visible label, cklick it to make the hidden edit-element editable
						if( isset( $aOptions['fields'][$fld] ) && !empty($aOptions['fields'][$fld]) ){
								$char = $aOptions['fields'][$fld]; 
								$size =  '';
						}elseif( 'textarea' == $this->model->properties[$fld]['type'] ){
								$char = 'DIV' ; 
								$size = isset($this->model->properties[$fld]['size']) ? ' style="max-width:'.$this->model->properties[$fld]['size'] . ';"' : '';
						}else{ 
								$char = 'SPAN'; 
								$size =  '';
						}
						$table .= $tt . $tt . '<'.$char.' '.$size.' class="lab_'.$ix.'" id="lab_'.$ix.'_'.$fld.'" onclick="'.$this->jsToDisplayOneEditInputElement($ix.'_'.$fld).'">'.$labelText.'</'.$char.'>';
				}
				$table .= $tt.$t.'</td>'.$n;
				$table .= $tt.'</tr>'.$n;
			}
			// last cell with buttons
			if( $this->model->isRecordLocked($ix) == FALSE && $aOptions['addrecords']==TRUE ) {
				$table .= $tt.'<tr><!-- data row for id #'.$ix.' -->'.$n;
				if($aOptions['labels']) $table .= $tt.$t.'<th title="'. $this->getLabel('model.'.$this->model->tablename.'.'. $fld . '.title' , $fld) .'">'. $this->getLabel('model.'.$this->model->tablename.'.'. $fld , $fld)  .'</th>'.$n;
					$table .= $tt.$t.'<td>'.$rowButtons.'</td>'.$n;
				$table .= $tt.'</tr>'.$n;
			}
		}
		if($aOptions['addrecords']) {
			// last row with hidden fields for new recordset includes editable index-field
			foreach( $aFieldList as $fld ) {
					// default value? also if validation contains 'iterate'
					$default = '';
					if( isset($this->model->properties[$fld]['default']) ){
						$default = $this->model->properties[$fld]['default'];
					}elseif( isset($this->model->properties[$fld]['validation']) ){
						$validations = explode( ',' , $this->model->properties[$fld]['validation'] );
						if( in_array( 'iterate' , $validations ) ){
							$default = $this->model->getLastIndex( $fld ) +1;
						}
					}
					
					$table .= $tt.'<tr><!-- last row with hidden fields for new recordset -->'.$n;
					if($aOptions['labels']) $table .= $tt.$t.'<th title="'. $this->getLabel('model.'.$this->model->tablename.'.'. $fld . '.title' , $fld) .'"></th>'.$n;
					$table .= $tt.$t.'<td title="'.$this->getLabel('model.connection.'. $fld , $fld) .'">' . $this->obj_editOnClickField( $default , 'new' , $fld , 'newCell' ) .$n . $tt.$t.'</td>'.$n;
					$table .= $tt.'</tr>'.$n;
			}
		}
		$table .= $t.'</table>'.$n;
		
		return $table . $loadDefaultsButton;
	}
	
    /**
     * obj_htmlModelTable
     * 
     * @param array $data
     * @param array $aOptions
     * @return string
     */
    public function obj_htmlModelTable( $data , $aOptions = NULL ) {
		
		// get original fieldlist
		$aFieldList = array_keys( $this->model->properties );
		
		$n = "\n";
		$tt = "\t\t";
		$t = "\t";
		$loadDefaultsButton = ( !is_array($data) || !count($data) ) ? $tt.$tt.' <input type="submit" name="ok[default]" value="##LL:load_default_data##" />'.$n : '';
		
		$table = $t.'<table style="margin:10px 0;" class="modeltable" cellpadding="3" cellspacing="0">'.$n;
		
		$table .= $t.'<thead>'.$n;
		$table .= $tt.'<tr>'.$n;
		if( $this->viewoptions['index_editable'] == TRUE ) $table .= $tt.$t.'<th>&nbsp;</th>'.$n;
		foreach( $aFieldList as $fld ) $table .= $tt.$t.'<th title="'. $this->getLabel('model.'.$this->model->tablename.'.'. $fld . '.title' , $fld) .'">'. $this->getLabel('model.'.$this->model->tablename.'.'. $fld , $fld)  .'</th>'.$n;
		if($aOptions['addrecords']) $table .= $tt.$t.'<th>&nbsp;</th>'.$n;
		$table .= $tt.'</tr>'.$n;
		$table .= $t.'</thead>'.$n;
		
		$table .= $t.'<tbody>'.$n;
		if(is_array($data) && count($data) ){
			foreach( $data as $ix => $row ){

				$table .= $tt.'<tr><!-- data row for id #'.$ix.' -->'.$n;
				$table .= $tt.$t.'<td title="index, '.$this->getLabel( 'not_editable' ).'" style="cursor:pointer;" onclick="'.$this->jsToDisplayEditInputElements($ix).'">'. ( $this->viewoptions['index_editable'] ? '&rarr;' : $ix ) . '</td>'.$n;
				foreach( $aFieldList as $fld ){
 					if( $this->viewoptions['index_editable'] == FALSE && $this->model->indexfield == $fld ) continue;
					$value = isset($row[$fld]) ? $row[$fld]: '';
					$table .= $tt.$t.'<td title="'.$this->getLabel('model.'.$this->model->tablename.'.'. $fld , $fld) .' index:'.$ix.' '. $this->getLabel('model.'.$this->model->tablename.'.'. $fld . '.title' , '') .'">';
					$unbreakedLabelString = $this->getLabelValueToDisplay( $fld , $value );
					$labelText = str_replace( "\n" , "<BR />\n" , $unbreakedLabelString );
					if( $this->model->isRecordLocked($ix) == 'record' || ($this->model->isRecordLocked($ix) == 'fields' && isset($this->model->properties[$fld]['locked']))
					){
							$table .= $tt . $tt . '<span>'.$labelText.'</span>';
					}else{
							$table .= $this->obj_editOnClickField( $value , $ix , $fld , 'cls_'.$ix ).$n;
							// visible label, cklick it to make the hidden edit-element editable
							
							if( isset($this->model->properties[$fld]['size']) && 'textarea' == $this->model->properties[$fld]['type'] ){
									$char = 'DIV' ; 
									$size = isset($this->model->properties[$fld]['size']) ? ' style="max-width:'.$this->model->properties[$fld]['size'] . ';"' : '';
							}else{ 
									$char = 'SPAN'; 
									$size =  '';
							}
							if( isset($this->model->properties[$fld]['crop']) ){
								$length = $this->model->properties[$fld]['crop']['length'];
								$chars = $this->model->properties[$fld]['crop']['append'];
								if( strlen($labelText) > $length+strlen($chars) ) $labelText = substr( $labelText , 0 , $length ).$chars ;
							}
							$table .= $tt . $tt . '<'.$char.' '.$size.' class="lab_'.$ix.'" id="lab_'.$ix.'_'.$fld.'" onclick="'.$this->jsToDisplayOneEditInputElement($ix.'_'.$fld).'">'.$labelText.'</'.$char.'>';
					}
					$table .= $tt.$t.'</td>'.$n;
				}
				if( $this->model->isRecordLocked($ix) || $aOptions['addrecords']==FALSE ) {
						$table .= $tt.$t.'<td></td>'.$n;
				}else{
						$rowButtons =  method_exists( $this->model , 'getRowButtons' ) ? $this->model->getRowButtons($ix) : '<input type="submit" name="delete['.$this->model->tablename.']['.$ix.']" value="##LL:files.delete##" onclick="return window.confirm(\'Index: '.$ix.'\n##LL:files.delete##?\');" class="small" />';
						$table .= $tt.$t.'<td>'.$rowButtons.'</td>'.$n;
				}
				$table .= $tt.'</tr>'.$n;
			}
		}
		if($aOptions['addrecords']) {
			// last row with hidden fields for new recordset includes editable index-field
			$table .= $tt.'<tr><!-- last row with hidden fields for new recordset -->'.$n;
			if( $this->viewoptions['index_editable'] == TRUE ) $table .= $tt.$t.'<td></td>'.$n;
			foreach( $aFieldList as $fld ) {
					// default value? also if validation contains 'iterate'
					$default = '';
					if( isset($this->model->properties[$fld]['default']) ){
						$default = $this->model->properties[$fld]['default'];
					}elseif( isset($this->model->properties[$fld]['validation']) ){
						$validations = explode( ',' , $this->model->properties[$fld]['validation'] );
						if( in_array( 'iterate' , $validations ) ){
							$default = $this->model->getLastIndex( $fld ) +1;
						}
					}
					
					$table .= $tt.$t.'<td title="'.$this->getLabel('model.connection.'. $fld , $fld) .'">' . $this->obj_editOnClickField( $default , 'new' , $fld , 'newCell' ) .$n . $tt.$t.'</td>'.$n;
			}
			$table .= $tt.$t.'<td></td>'.$n;// empty cell because there is no need for edit-button here
			$table .= $tt.'</tr>'.$n;
		}
		$table .= $t.'</tbody>'.$n;
		$table .= $t.'</table>'.$n;
		
		return $table . $loadDefaultsButton;
		
	}
	
    /**
     * obj_editOnClickField
     * 
     * @param array $value
     * @param string $ix index
     * @param string $fieldname
     * @param string $addCssClass
     * @return string
     */
    public function obj_editOnClickField( $value , $ix , $fieldname , $addCssClass = '' ) { 
			$tt = "\n\t\t\t\t";
			
			if(isset($this->model->properties[$fieldname]['placeholder'])) $objSet['placeholder'] = $this->model->properties[$fieldname]['placeholder'];
			$objSet['id'] = $ix.'_'.$fieldname.'';
			$objSet['class'] = trim('hidden '.$addCssClass);
			$objSet['disabled'] = 'disabled';
			if(isset($this->model->properties[$fieldname]['size'])) {
				$objSet['style'] = 'width:' . $this->model->properties[$fieldname]['size'] . ';';
			}else{
				$objSet['style'] = 'width:'. (empty($value) ? 12 : round( (2/3) * strlen($value) , 1 )).'em;';
			}
			$objSet['onkeypress'] = 'if( event.keyCode == 13 ){alert(\'##LL:save_hint_onclick##\');return false;}';
			if(isset($this->model->properties[$fieldname]['options']) && is_array($this->model->properties[$fieldname]['options']) ) {
				foreach( $this->model->properties[$fieldname]['options']as $optName => $optValue ) $objSet[$optName] = $optValue;
			}

			// hidden edit-element
			switch( $this->model->properties[$fieldname]['type'] ){
				case 'select':
					$options = array('(empty)');
					// source value can be text OR array OR name-of-class OR method-in-model OR empty if method is in model and proc_name contains method-name
					$sourcetype = isset($this->model->properties[$fieldname]['source']) ? $this->model->properties[$fieldname]['source'] : '';
					// value can be list of options OR array of options
					$mixedSource = isset($this->model->properties[$fieldname]['value']) ? $this->model->properties[$fieldname]['value'] : '';
					
					if( strtolower($sourcetype) == 'array' && is_array($mixedSource) ){
						$options = $mixedSource;
					}elseif( strtolower($sourcetype) == 'text' && !empty($mixedSource) ){
						$options = explode( ',' , $mixedSource );
					}else{
						// proc_name can be name of method in other class OR  method-in-model if source is empty
						$procname = isset($this->model->properties[$fieldname]['proc_name']) ? $this->model->properties[$fieldname]['proc_name'] : '';
						// proc_options for methods in this, in models or in external classes taken from source
						$procoptions = isset($this->model->properties[$fieldname]['proc_options']) ? $this->model->properties[$fieldname]['proc_options'] : '';
						
						// 2x source contains method:
						if( empty($procname) && method_exists( $this , $sourcetype ) ) { $procname = $sourcetype;  }
						if( empty($procname) && method_exists( $this->model , $sourcetype ) ) { $procname = $sourcetype; $sourcetype = 'model'; }

						$rst = $this->viewoptions['option_defaults']['restrict'];
						if( $procoptions == 'authUserGroup' && ( !empty($rst) || $rst === 0 ) ){ $procoptions = $rst; }
						
						if( method_exists( $this , $procname ) ){ // method is in here
							$options = $this->helper_getSelectOptionsFromMethod( $procname , $procoptions , $this ) ;	
						}elseif( strtolower($sourcetype) == 'model' ){  // method is in model
							$options =  $this->helper_getSelectOptionsFromMethod( $procname , $procoptions , $this->model ) ;	
						}elseif( $sourcetype == 'viewhelper' ){ // method is in here, inherited from class viewhelper
							$options =  $this->helper_getSelectOptionsFromMethod( $procname , $procoptions , $this ) ;	
						}

						if( isset($this->model->properties[$fieldname]['append_options']) && !empty($this->model->properties[$fieldname]['append_options']) ){
							$aOptPairs = explode( ',' , $this->model->properties[$fieldname]['append_options'] );
							foreach( $aOptPairs as $sOptPair ) {$aOpt = explode( ':' , $sOptPair ); $options[$aOpt[0]]=$aOpt[1];}
						}
					}
					unset($objSet['style']);
					$editCell = $this->objSelect( $this->model->tablename . '['.$ix.']['.$fieldname.']' ,$options, $value , '' , $objSet );
				break;
				case 'pass1way':
					$editCell = $this->objText( $this->model->tablename . '['.$ix.']['.$fieldname.']' , '' , $objSet );
				break;
				case 'pass2way':
					$editCell = $this->objText( $this->model->tablename . '['.$ix.']['.$fieldname.']' , $value , $objSet );
				break;
				case 'textarea':
					$editCell = $this->objTextarea( $this->model->tablename . '['.$ix.']['.$fieldname.']' , $value , $objSet );
				break;
				case 'checkbox':
					if( $ix == 'new') return FALSE;
					if( isset( $objSet['disabled'] ) ) unset( $objSet['disabled']) ;
					// $objSet['label'] = $this->getLabel('model.' . $this->model->tablename . '.' .$fieldname , $fieldname );
					$objSet['class'] = trim($addCssClass);
					$editCell = $this->objCheckbox( $this->model->tablename . '['.$ix.']['.$fieldname.']' , $value , $objSet ) ;
				break;
				default:
					$editCell = $this->objText( $this->model->tablename . '['.$ix.']['.$fieldname.']' , $value , $objSet );
				break;
			}

			return $tt . $editCell ; 
	}
	
    /**
     * getLabelValueToDisplay
     * 
     * @param string $fieldname
     * @param string $value
     * @return string
     */
    public function getLabelValueToDisplay( $fieldname , $value ) { 
			switch( $this->model->properties[$fieldname]['type'] ){
				case 'pass1way':
					return ( empty($value) ? '(empty)' : $value );
				break;
				case 'pass2way':
					return ( empty($value) ? '(empty)' : '****************' );
				break;
				case 'textarea':
					return $value;
				break;
				case 'checkbox':
					return '';
				break;
				default:
					if( $value === '' ) return '(empty)';
				break;
			}
			return str_replace( "\n" , "<BR />\n" , $value );
    }
	
    /**
     * jsToDisplayEditInputElements
     * 
     * @param string $ix of input elements to show and labels to hide
     * @return string
     */
    public function jsToDisplayEditInputElements( $ix ) { 
			$edJs = 'var listView = document.getElementsByClassName(\'cls_'.$ix.'\');';
			$edJs.= 'for (var i = 0; i < listView.length; i++) {';
			$edJs.=   'listView[i].style.display = \'inline\';';
			$edJs.=   'listView[i].disabled=false;';
			$edJs.= '};';
			$edJs.= 'var listHide = document.getElementsByClassName(\'lab_'.$ix.'\');';
			$edJs.= 'for (var i = 0; i < listHide.length; i++) {';
			$edJs.=   'listHide[i].style.display = \'none\';';
			$edJs.= '}';
			return $edJs;
	}
	
    /**
     * jsToDisplayOneEditInputElement
     * 
     * @param string $ix of input elements to show and labels to hide
     * @return string
     */
    public function jsToDisplayOneEditInputElement( $ixfld ) { 
			$edJs = 'document.getElementById(\''.$ixfld.'\').style.display = \'inline\';';
			$edJs.= 'document.getElementById(\''.$ixfld.'\').disabled=false;';
			$edJs.= 'document.getElementById(\'lab_'.$ixfld.'\').style.display = \'none\';';
			return $edJs;
	}
	
    /**
     * jsToDisplayNewInputElements
     * 
     * @param string $cssClass to show
     * @return string
     */
    public function jsToDisplayNewInputElements( $cssClass ) { 
		return 'var list = document.getElementsByClassName(\''.$cssClass.'\');for (var i = 0; i < list.length; i++) {list[i].style.display = \'inline\';list[i].disabled=false;};';
	}
	
    /**
     * wrapHtml
     * 
     * @param string $title
     * @param string $tag
     * @param string $content
     * @return string
     */
    public function wrapHtml( $title , $tag , $content ) {
		return '<' . $tag .' class="' . $title . '"><!-- start ' . $title . ' -->' . "\n\t\t\t\t" . $content . "\n\t\t\t" . '</' . $tag .'><!-- end ' . $title . ' -->' . "\n";
	}


}


?>
