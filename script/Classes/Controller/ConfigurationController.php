<?php
namespace Drg\CloudApi\Controller;
if (!class_exists('Drg\CloudApi\boot', false)) die( basename(__FILE__) . ': Die Datei "'.__FILE__.'" muss von Klasse "boot" aus aufgerufen werden.' );
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
 * ConfigurationController
 * 
 */
class ConfigurationController extends \Drg\CloudApi\controllerBase {

	/**
	 * Property actionDefault
	 *
	 * @var string
	 */
	Public $actionDefault = 'settings';

	/**
	 * Property authUser
	 *
	 * @var array
	 */
	Private $authUser = NULL;

	/**
	 * Property accessRules
	 *
	 * @var array
	 */
	Protected $accessRules = array(
		'settings' => 1 ,
		'tableeditor' => 2,
		'database' => 2,
		'profile' => 1,
		'users' => 11,
		'install' => 11,
		// alias-functions: allow for all, acces restriction are ruled by methods
		'settings_cat_sync' => FALSE,
		'settings_cat_pdf' => FALSE,
		'settings_cat_cronn' => FALSE,
		'settings_cat_output' => FALSE,
		'settings_cat_connection' => FALSE,
	);

	/**
	 * Property subActions
	 * used to set button as active while a subaction is selected
	 *
	 * @var array
	 */
	Protected $subActions = array(
		'profile' => array('profile' , 'users' ) ,
	);

	/**
	 * Property categoriesAccessRules
	 *
	 * @var array
	 */
	Public $categoriesAccessRules = array();

	/**
	 * initiate
	 *
	 * @return  void
	 */
	public function initiate() {
		parent::initiate();
		$this->readCloudUtility = new \Drg\CloudApi\Utility\ReadCloudUtility( $this->settings );
		foreach( $this->settings['original'] as $sName => $set ){
				if( strpos(  $sName , 'acl_' ) === 0 && strpos( $sName , 'Category' ) && isset($this->settings[$sName]) ){
					$this->categoriesAccessRules[ substr( $sName , 4 , strlen($sName)-strlen('act_Category') ) ] = $this->settings[$sName];
				}
		}
		$this->authUser = $this->authService->getAuthUsersRecordset();
		$userButton =  $this->authUser['group'] >= $this->accessRules['users'] ? '##LL:user##' : '##LL:profile##';
		$this->view->assign( 'user_button' , $userButton );
		
	}

    /**
     * action settingsAction
     *
     * @return void
     */
    public function settingsAction() {
			
			if( $this->authUser['group'] <1 )return 'welcome';
			
			$authScale = $this->categoriesAccessRules;
			$outArr = $this->getSettingsAsObjList();

			$rules= $authScale;
			asort($rules);
			$aKeysList = array_keys($rules);
			$highestCat = array_pop( $aKeysList );
			
  			$this->view->append( 'FOOTER' , $this->view->javaScript->toggleCategorySelectors( 'api_admin_form' ) );
			
			// special for timeout: restrict max timeout to max_execution_time as set in php.ini
			foreach($outArr['exectimeout']['options'] as $i=>$opt) if( $opt > ini_get('max_execution_time') ) unset($outArr['exectimeout']['options'][$i]);
   
			// create checkboxes to display/hide categories
			$categories = isset($this->settings['req']['cat']) && is_array($this->settings['req']['cat']) ? $this->settings['req']['cat'] : array();
			$autoselect = isset($this->settings['req']['chk_cat']) || isset($this->settings['req']['cat']) ? FALSE : TRUE;
			$checkboxes = ucFirst( $this->view->getLabel( 'categories' ) ) . ': &nbsp; ';
			$objSet = array(
				'slider'=>'slider round', 
				'label'=>'function', 
				'onChange'=> $this->settings['multiselect_settings_categories'] ? 'form.submit();' : 'toggleCategorySelectors( this );'
			);
			// detect if there is a local stored object with defined format in settings for each category,
			// otherwise dont display checkbutton
			foreach( array_keys( $outArr ) as $setName ){
					if( isset($this->settings['globales'][$setName]) && $this->settings['globales'][$setName] == 'global') continue;
					if( !isset( $this->settings['format'][$setName] ) ) continue;
					if( !isset( $this->settings['categories'][$setName] ) ) continue;
					if( $authScale[ $this->settings['categories'][$setName] ] > $this->authUser['group']) continue;
					$elementsInCategory[$this->settings['categories'][$setName]][] = $setName;
			}
			foreach( $this->categoriesAccessRules as $category => $rouleNr ) {
				if( !isset($elementsInCategory[$category]) || !count($elementsInCategory[$category]) ) continue;
				$objSet['label'] = ucFirst( $this->view->getLabel( $category ) );
 				if( $autoselect ) {
					$categories[$category] = 1;
					$autoselect = isset($this->settings['req']['all_cat']) ? TRUE : FALSE; // select first or all following
 				}
				$checkboxes .= $this->view->widgets->objCheckbox( 'cat['.$category.']' , isset($categories[$category])  , $objSet ) . ' &nbsp; ' ;
			}
			
			// append checked categories to URL
			$additionalUrlQuery = count($categories) ? '&amp;cat[' . implode( ']=1&amp;cat[' , array_keys($categories) ) . ']=1&amp;chk_cat=1' : '';
			
			// display selector to change dataDir if more than 1 directory and change directory is allowed for this user
			if( 
				isset($outArr['default_dir']['options']) && count($outArr['default_dir']['options']) > 1 && 
				$authScale[$this->settings['categories']['change_data_dir']] <= $this->authUser['group'] 
			) {
				$onChange = 'window.location.href=\''.$this->getUrl().'?act='.$this->view->action.'&ok[save]=1&settings[default_dir]=\' + this.options[this.selectedIndex].value';
				$changeDataDirButton =  '<label title="##LL:change_data_dir##. ##LL:change_data_dir.title##">##LL:change_data_dir## ##LL:for_document## <u>'.basename($_SERVER['PHP_SELF']).'</u>: ' . $this->view->widgets->objSelect( 'settings[default_dir]' , $outArr['default_dir']['options'] , $outArr['default_dir']['value'] , '' , array( 'onChange'=>$onChange ) );
				$changeDataDirButton .=  ' (##LL:for_user## \'' . $this->authUser['user'].'\') </label><hr />';
			}else{
				$changeDataDirButton = '<hr />';
			}
			
			$clrOutArr = array();
			foreach( $outArr as $nam => $objDef ){
				if( $nam == 'default_dir') continue; // for this variable we have created a separate input field above
				if( isset($this->settings['globales'][$nam]) && $this->settings['globales'][$nam] == 'global') continue;
				if( $this->authUser['group'] < $authScale[$highestCat] ){
						if( !isset( $this->settings['categories'][$nam] ) ) continue;
						if( $authScale[ $this->settings['categories'][$nam] ] > $this->authUser['group']) continue;
				}
				if( !isset($this->settings['categories'][$nam]) ) continue;
				$categoryOfThisVar = $this->settings['categories'][$nam];
				if(  !count($categories) || !isset($categories[$categoryOfThisVar]) ) continue;
				$clrOutArr[$nam] = $objDef;
			}
			
			$page = '';
			$page .= '<h3>##LL:newact.settings.value## <span style="font-weight:normal"> (##LL:local## '.basename($this->settings['dataDir']).')</span></h3>';
			$page .= $changeDataDirButton ;
			$page .= $checkboxes;
			
 			if(  !count($clrOutArr) ){
				$page .= '  <hr /> <p>##LL:please_select_cat## | <a href="?newact='.$this->view->action.'&amp;all_cat=1">##LL:show_all##</a></p>';
				$this->view->append( 'text' , $page );
				return;
 			}
			
			$saveButton = '';
 			if( file_exists($this->settings['dataDir']) && (!isset($this->settings['req']['uninstall']) || empty($this->settings['req']['uninstall']) ) ){
 				$saveButton.= ' <p><input type="submit" name="ok[save]" value="##LL:save##" /></p> ';
 			}
			
			$page .= $saveButton;
			
			$page .= '<table class="datatable" border="0" cellpadding="5" cellspacing="0">';
			$page .= "\n";
			$page .= '<tr>';
			$page .= '<th class="borderbottom">##LL:config.description##</th>';
			$page .= '<th class="borderbottom">##LL:config.value##</th>';
			$page .= '<th class="borderbottom">##LL:config.fieldname##</th>';
			$page .= '<th class="borderbottom">##LL:config.condition##</th>';
			$page .= '<th class="borderbottom">##LL:category##</th>';
			$page .= '</tr>';
			$page .= "\n";
			foreach( $clrOutArr as $nam => $objDef ){
				
				$objName = 'settings['.$nam.']';
				
				$objSet = array(
					'id'=>'settings_'.$nam , 
					'class'=>$objDef['rowCss'], 
					'onkeypress'=>'if( event.keyCode == 13 ){alert(\'##LL:save_hint_onclick##\');return false;}'
				);
 				if($objDef['objDis']) $objSet['disabled'] = $objDef['objDis'];
				
				$page .= '<tr class="'.$objDef['rowCss'].'" title="'.$nam.'">';
				
				$page .= '<td class="'.trim($objDef['css']).' borderbottom" ><label for="settings_'.$nam.'">' . $objDef['label'] . '.</label></td>';
				
				$page .= '<td class="borderbottom">';
				if( 'text' == $objDef['type'] ){
					$page .= $this->view->widgets->objText( $objName , $objDef['value'] , $objSet );
					
				}elseif( 'text_' == substr( $objDef['type'] ,  0 , 5) ){
					$objSet['class'] = trim( trim($objSet['class']) . ' ' . $objDef['type'] ) ;
					$page .= $this->view->widgets->objText( $objName , $objDef['value'] , $objSet );
					
				}elseif( 'select' == $objDef['type'] ){
					if( $objDef['options'] == FALSE ){
						$page .= '<input type="text" disabled="1" style="width:2em;text-align:center;" value="'.$objDef['value'].'" />';
					}else{
						if( isset($objDef['onchange']) && $objDef['onchange']) {
							$objSet['onchange'] = $objDef['onchange'] . '()';
							$this->view->append( 'FOOTER' , $this->view->javaScript->addStarterFunction( $objDef['onchange'] ) );
						}
						$page .= $this->view->widgets->objSelect( $objName , $objDef['options'] , $objDef['value'] , '' , $objSet );
					}
					
				}elseif( 'label' == $objDef['type'] ){
					$page .= '<b>' . $objDef['value'] . '</b>';
					
				}elseif( 'file' == $objDef['type'] ){
 					$imageDirname = $this->settings['dataDir'] . dirname( $this->settings['pdf_options_Logofile'] ) . '/';
					$logoFilename = pathinfo( $this->settings['pdf_options_Logofile'] , PATHINFO_FILENAME ) ;
					$imageFile = $imageDirname . $this->fileHandlerService->handleSingleUpload( $imageDirname , $logoFilename , $logoFilename , 'gif,jpg,jpeg,png' );
					$page .= $this->view->widgets->htmlImageBox( $imageFile , 25 , 0 , $logoFilename , $additionalUrlQuery );

				}elseif( 'button' == $objDef['type'] ){
					$page .= '<input type="submit" name="newact[' . $objDef['value'] . ']" value="##LL:button.' . $objDef['value'] . '##" />';
					
				}elseif( 'check' == $objDef['type'] ){
					$objSet['onclick'] = 'toggleFormElements( \''.$nam.'\' , this.checked );';
					$page .= $this->view->widgets->objCheckbox( $objName , $objDef['value'] , $objSet );
					
				}elseif( 'pass2way' == $objDef['type'] ){
					$objSet['class'] = trim( trim($objSet['class']) . ' ' . $objDef['type'] ) ;
					$objSet['title'] = 'encoded: ' . $objDef['value'] ;
					$page .= $this->view->widgets->objText( $objName , $objDef['value'] , $objSet );
					
				}
				$page .= '</td>';
				
				$page .= '<td class="'.$objDef['rowCss'].' borderbottom nowrap" style="font-size:smaller;" ><label for="settings_'.$nam.'">'.$nam.'</label></td>';
				$page .= '<td class="'.trim($objDef['rowCss']).' borderbottom nowrap" style="font-size:smaller;" >'.$objDef['depends'].'</td>';
				$page .= '<td class="'.trim($objDef['rowCss']).' borderbottom nowrap" style="font-size:smaller;">'.$this->view->widgets->getLabel( $this->settings['categories'][$nam] , ucFirst($this->settings['categories'][$nam]) ).' </td>';
				
				$page .= '</tr>';
				$page .= "\n";
			}
			$page .= "\n";
			$page .= '</table>';
			$page .= "\n";
 			$page .= $saveButton;
			
 			$this->view->append( 'text' , $page );
	}

    /**
     * getSettingsAsObjList
     *
     * @return array
     */
    public function getSettingsAsObjList() {
			$typ = $this->settings['format'];
			$lab = $this->settings['labels'][$this->settings['language']];
			$opt = $this->settings['options'];
			$dep = $this->settings['depends'];

			$outArr = array();
			foreach( $this->settings['original'] as $mainfield => $originalContent ){
				$mainContent = $this->settings[$mainfield];
				if( !isset($typ[ $mainfield ]) ) continue;
				if( is_array($mainContent) )  continue;
				if( isset($this->settings['static'][$mainfield]) )  continue; // $typ[ $mainfield ] = 'label';
				$outArr[ $mainfield ]['css'] = '';
				$outArr[ $mainfield ]['rowCss'] = '';
				$outArr[ $mainfield ]['objDis'] = '';
				$outArr[ $mainfield ]['depends'] = '';
				if( isset( $dep[$mainfield] ) ){
					if( isset($this->settings[ $dep[$mainfield][0] ]) ){
						// is parent dependent on other parent?
						$parent = $dep[$mainfield][0];
						if( isset( $dep[$parent] ) && isset($this->settings[ $dep[$parent][0] ]) ){
							if( $this->settings[ $dep[$parent][0] ] != $dep[$parent][1] ){
								if($dep[$parent][1] === '*'){}else{
								$outArr[ $mainfield ]['objDis'] = 'disabled';
								}
							}
							$outArr[ $mainfield ]['rowCss'] = $dep[$parent][0].'_'.$dep[$parent][1] ;
						}
						if( $this->settings[ $dep[$mainfield][0] ] != $dep[$mainfield][1] ){
							if($dep[$mainfield][1] === '*'){}else{
								$outArr[ $mainfield ]['objDis'] = 'disabled';
							}
						}
						$outArr[ $mainfield ]['rowCss'] = trim( $outArr[ $mainfield ]['rowCss'] . ' ' . $dep[$mainfield][0].'_'.$dep[$mainfield][1] );
						$outArr[ $mainfield ]['rowCss'] = trim( $outArr[ $mainfield ]['rowCss'] . ' ' . $outArr[ $mainfield ]['objDis'] );
						$outArr[ $mainfield ]['css'] .= ' indented';
					}
					$outArr[ $mainfield ]['depends'] = '(' . $dep[$mainfield][0].'='.$this->settings['depends'][$mainfield][1] . ')';
				}
				$outArr[ $mainfield ]['label'] = isset($lab[ $mainfield ]) ? $lab[ $mainfield ] : $mainfield;
				$outArr[ $mainfield ]['type'] = $typ[ $mainfield ];
				$outArr[ $mainfield ]['value'] = $mainContent;
				
				$possibleClasses = array(
					'viewhelper'=>$this->view->widgets,
					'csvService'=>$this->csvService
				);
				
				if( $typ[ $mainfield ] == 'select' ){
					if( !isset( $opt[ $mainfield ] ) ) continue;
					$optionSource = $opt[ $mainfield ]['source'];
					
					if( $optionSource == 'array' ){
						$options = $opt[ $mainfield ]['value'];
						
					}elseif( $optionSource == 'text' ){
						if( !isset($opt[ $mainfield ]['value']) ) continue;
						$options = explode( ',' , $opt[ $mainfield ]['value'] );
						
					}elseif( isset($possibleClasses[$optionSource]) ){
						if( !isset($opt[ $mainfield ]['proc_name']) ) continue;
						$method = $opt[ $mainfield ]['proc_name'];
						//if( !method_exists( $possibleClasses[$optionSource] , $method ) ) continue;
						if( isset($opt[ $mainfield ]['proc_options']) ){  // call method with (static) options
							if( $opt[ $mainfield ]['proc_options'] == 'authUserGroup' ){
								$opt[ $mainfield ]['proc_options'] = $this->authUser['group']; 
							}
						}
						$options = $this->view->widgets->helper_getSelectOptionsFromMethod( $method , $opt[ $mainfield ]['proc_options'] , $possibleClasses[$optionSource] );
						// if a higher group than the own membership is selected then disable the options-field
						if( !in_array($mainContent,$options) ){
								if( $opt[ $mainfield ]['proc_options'] == 'authUserGroup' ){ 
									$options = NULL;
								}else{
									$options = array( $mainContent => $mainContent );
								}
						}
					
					}else{ // missconfiguration?
						$options = $mainContent;
						
					}
// 					if( count($options) <= 1)  $options = NULL; // disables the file-type selector if only csv is avaiable. Problem if other value is selected
					$outArr[ $mainfield ]['options'] = $options ;
					if( $options && isset( $opt[ $mainfield ]['onchange'] ) ) $outArr[ $mainfield ]['onchange'] = $opt[ $mainfield ]['onchange'];
				}else{ // every object else than select: text, label
				}
			}
			return $outArr;
	}

    /**
     * action tableeditorAction
     *
     * @return void
     */
    public function tableeditorAction() {
			
			$editForm ='';
			$fileSelector ='';
			$defaultTables = array( 'default' , 'group_quota' , 'delete_list' );
			
			$additionalTableConfFile = $this->settings['scrDir'] . $this->settings['default_additional_filedir'] . basename($this->settings['table_conf_filepath']);
			
			$tableConfFromFile = $this->fileHandlerService->readDefaultFile( $additionalTableConfFile );
			if( isset($tableConfFromFile['table_conf']) && is_array($tableConfFromFile['table_conf']) ) $tableConfFromFile = $tableConfFromFile['table_conf'];
			
			$fullTableConf = $this->settings['table_conf'];
			ksort($fullTableConf);
			$actualTablename = isset($this->settings['req']['tab']) && !empty($this->settings['req']['tab']) ? $this->settings['req']['tab'] : '';
			
			$persist = 0;
			// dupliz or new from file (duplicate from outside)
			if( isset($this->settings['req']['dupliz']) && isset($this->settings['req']['tablename']) ){
				if( isset($this->settings['req']['table_conf']) ) $this->settings['req']['tablename'] = ucFirst($this->settings['req']['tablename'] . '_copy');
				$this->settings['req']['save'] = 1;
				$fullTableConf[ $this->settings['req']['tablename'] ] = isset($fullTableConf[$actualTablename]) ? $fullTableConf[$actualTablename] : $fullTableConf['default'];
				$actualTablename = $this->settings['req']['tablename'];
				// fill form with new values (save=1) without persisting - if duplicate from outside
				
			// change tablename
			}elseif( isset($this->settings['req']['tablename']) && isset($this->settings['req']['save']) && $this->settings['req']['tablename'] != $actualTablename ){
					$fullTableConf[ $this->settings['req']['tablename'] ] = isset($fullTableConf[$actualTablename]) ? $fullTableConf[$actualTablename] : $fullTableConf['default'];
 					unset($fullTableConf[$actualTablename]);
					$actualTablename = $this->settings['req']['tablename'];
					$persist = 1;
			}
			
			$isSysTable = ( $defaultTables[ array_search( $actualTablename , $defaultTables )] == $actualTablename ) ? 1 : 0;
			$index = array_search( $actualTablename , $defaultTables );
			$type = $isSysTable ? $defaultTables[$index] : $defaultTables[0];

			$Fieldnames = $this->tableeditor_getFieldnames( $type );
			
			if( isset($this->settings['req']['table_conf']) && isset($this->settings['req']['save']) ){
					if(isset( $this->settings['req']['table_conf']['samples'])){
							if(isset($fullTableConf[$actualTablename]['samples'])) unset($fullTableConf[$actualTablename]['samples']);
							$sampleLines = explode( "\n" , trim($this->settings['req']['table_conf']['samples'] ,"\n"));
							if( !empty($this->settings['req']['table_conf']['samples']) && count($sampleLines) > 1){
									foreach( $sampleLines as $ix => $line ){
										$aLine = explode( $this->settings['sys_csv_delimiter'] , $line );
										$aCleanLine = array();
										foreach($aLine as $field){ $aCleanLine[] = trim($field); }
										$fullTableConf[$actualTablename]['samples'][] = $aCleanLine;
									}
							}
					}
					
					foreach( $this->settings['req']['table_conf']['mapping'] as $ix => $row ) {
							$arrParts = explode( '.' , $ix );
							$realIndex = ( $arrParts[1] == 'FIELD' ) ? $arrParts[0] : $ix ;
							if( $arrParts[1] == 'FUNCTION'  && $row == '('.$this->view->widgets->getLabel('none').')' ) $row = '';
							$fullTableConf[$actualTablename]['mapping'][$realIndex] = $row;
					}
					$persist = 1;
			}
			
			if( isset($this->settings['req']['delete']) ){
				if( $isSysTable ){ // reset if this is a systable 
						if( isset($tableConfFromFile[$actualTablename]) ) $fullTableConf[$actualTablename] = $tableConfFromFile[$actualTablename];
						$persist = 1;
				}else{ // delete
					if(isset($fullTableConf[$actualTablename])){
						unset($fullTableConf[$actualTablename]);
						$actualTablename = '';
						$persist = 1;
					}
				}
			}elseif( isset($this->settings['req']['samples']) ){
				$installerService = new \Drg\CloudApi\Services\InstallerService($this->settings);
				$table = $this->getFirstArrayKey($this->settings['req']['samples']);
				$installerService->createSampleFiles( $table );
				$fileFolder = isset($fullTableConf[$actualTablename]['location']) ? basename($fullTableConf[$actualTablename]['location']) : 'users' ;
				$this->debug['tableeditAction-samples'] = '##LL:file## ##LL:created## <a href="?act=dateien&ok['.$table.']='.$table.'&dir='.$fileFolder.'">local/'.$fileFolder.'/'.$table.'.csv</a>';
			}
			
			// persist here
			if( $persist ){
				$path =  $this->settings['store_global.table_conf'] ? DATA_DIR : $this->settings['dataDir'] ;
				$filename = basename( $this->settings['table_conf_filepath'] , '.php' ) . '.json';
				$this->fileHandlerService->writeCompressedFile( $path  . $filename , array( 'table_conf'=>$fullTableConf ) );
				$this->view->widgets->settings['table_conf'] = $fullTableConf;
			}
			
			// create output from here on
			
			$deleteButton = '';
			if( isset($this->settings['req']['delask']) ){
				$delText = ( $defaultTables[ array_search( $actualTablename , $defaultTables )] == $actualTablename ) ? '##LL:reset##' : '##LL:files.delete##';
				$deleteButton = '<p>';
				$deleteButton .= '##LL:newact.tables.value## definition &laquo;<b>'.$actualTablename.'&raquo;</b> ';
				$deleteButton .= ' <input type="submit" name="delete" value="'.$delText.'" />';
				$deleteButton .= ' <input type="submit" name="abort" value="'.ucFirst($this->view->widgets->getLabel('abort')).'" />';
				$deleteButton .= '</p>';
				
 			}elseif( isset($this->settings['req']['delete']) && !$isSysTable ){
				if( isset($this->settings['req']['delete']) ) $deleteButton = '<p>'.$this->settings['req']['tab'] . ' ##LL:deleted##.</p>';
			
			}elseif( isset($this->settings['req']['tab']) && !empty($this->settings['req']['tab']) ){
			
				if( isset($this->settings['req']['delete']) ) $deleteButton = '<p>'.$this->settings['req']['tab'] . ' ##LL:resetted##.</p>';
				
				if( isset($fullTableConf[$actualTablename]) && isset($fullTableConf[$actualTablename]['mapping'])  ){
					$Fieldnames = $this->tableeditor_getFieldnames( $type , $fullTableConf[$actualTablename]['mapping'] );
				}

				$allFiles = $this->fileHandlerService->getDir( $this->settings['dataDir'] . dirname( $this->settings['localusers'] ) . '/' , 3 );
				foreach( $allFiles['fil'] as $pathFile => $filename ){
						if( trim($actualTablename) == trim(basename($filename,'.csv')) && file_exists($pathFile) ){
							$arrToView = $this->csvService->csvFile2array( $pathFile );
							$titleArr = array_keys(array_shift($arrToView));
							break;
						}
				}
				
				$editForm .= $this->view->widgets->htmlEditTable_editForm($Fieldnames,$actualTablename, $defaultTables);
				
				$datalist = '<datalist id="csvfileslist">';
				foreach( $allFiles['fil'] as $pathFile => $filename ) $datalist.= '<option value="' . basename($filename,'.csv') . '" >';
				$datalist.= '</datalist> ';
				
				$editForm .= $datalist;
				
				if( isset($titleArr) ) $editForm .= '<datalist id="fieldslist"><option value="' . implode( '"><option value="' , $titleArr ) . '"></datalist> ';

				$js = $this->view->javaScript->toggleFunctionRelatedElements( array_keys( $Fieldnames ) );
				$this->view->append( 'FOOTER' , $js );
				
			}
			
			if( isset($titleArr) ) $editForm .= '<br />'.implode( '; ' , $titleArr );
			$editForm .= '<input type="hidden" name="tab" value="'.$actualTablename.'" />';
			
			$fileSelector = $this->view->widgets->htmlEditTable_tablesSelector($actualTablename);
			if( isset($titleArr) ) {
				$fileFolder = isset($fullTableConf[$actualTablename]['location']) ? basename($fullTableConf[$actualTablename]['location']) : 'users' ;
				$fileSelector .= '<div>';
				$fileSelector .= '<b title="##LL:fieldnames## ##LL:from## ##LL:file## '.$actualTablename.'.csv">';
				$fileSelector .= ''.ucFirst($this->view->widgets->getLabel('fieldnames')).'';
				$fileSelector .= '</b>';
				$fileSelector .= '<hr />';
				$fileSelector .= implode( '<br />' , $titleArr );
				$fileSelector .= '<hr />';
				$fileSelector .= '<i>';
				$fileSelector .= '##LL:from## ##LL:file## <br />';
				$fileSelector .= '<a href="?act=dateien&ok['.$actualTablename.']='.$actualTablename.'&dir='.$fileFolder.'">'.$actualTablename.'.csv</a>';
				$fileSelector .= '</i>';
				$fileSelector .= '</div> ';
			}
			$page = '<h3>##LL:newact.tables.value## <span style="font-weight:normal"> ('.( $this->settings['store_global.table_conf'] ? '##LL:global##' : '##LL:local## '.basename($this->settings['dataDir']).'' ).')</span></h3><div style="float:left;width:auto;margin-right:20px;">'. $fileSelector .'</div>';
			$page .= '<div class="table_conf" style="margin-top:5px;float:left;">'.$deleteButton.$editForm.'</div><div style="clear:left;"> </div>';
			
			$this->view->assign( 'page' , '<div>' . $page . '</div>' );
	}

	/**
     * tableeditor_getFieldnames
     * helper for tableeditorAction
     *
     * @param string $type [ default | group_quota | delete_list ]
     * @param array $data
     * @return array
     */
    private function tableeditor_getFieldnames( $type , $rawMapping = array() ) {
			$this->transformTablesUtility = new \Drg\CloudApi\Utility\TransformTablesUtility( $this->settings );
			if( !isset( $rawMapping ) ) return false;
			$givenMapping =$this->transformTablesUtility->getTableMappingAsFields( $rawMapping );
			$mapping = $givenMapping;
			switch($type){
					case 'default':
						$mapping = array('ID'=>!isset($givenMapping['ID']) ? '' : $givenMapping['ID'],'DISPLAYNAME'=>!isset($givenMapping['DISPLAYNAME']) ? '' : $givenMapping['DISPLAYNAME'],'EMAIL'=>!isset($givenMapping['EMAIL']) ? '' : $givenMapping['EMAIL'],'QUOTA'=>!isset($givenMapping['QUOTA']) ? '' : $givenMapping['QUOTA']);
						for( $n=1 ; $n <= $this->settings['group_amount'] ; ++$n){ $mapping['grp_' . $n] = isset($givenMapping['grp_' . $n]) ? $givenMapping['grp_' . $n] : ''; }
					break;
					case 'group_quota':
						$mapping = array('ID'=>!isset($givenMapping['ID']) ? '' : $givenMapping['ID'],'QUOTA'=>!isset($givenMapping['QUOTA']) ? '' : $givenMapping['QUOTA']);
					break;
					case 'delete_list':
						$mapping = array('ID'=>!isset($givenMapping['ID']) ? '' : $givenMapping['ID']);
					break;
			}
			return $mapping;
	}

    /**
     * action installAction
     *
     * @return void
     */
    public function installAction() {

			$this->view->append( 'text' , '<h3>Installation<span style="font-weight:normal"> (##LL:global##) </span></h3>' );
			// the 'page' variable has been assigned by boot->initiate() on line 142. It contains the message from installerService->status
			
			$servicesSection = '';
			$servicesSection.= '<div style="padding:5px 10px;margin:2px 0;border:1px solid #AAA;">';
			$servicesSection.= '<h4>' . ucFirst( $this->view->widgets->getLabel( 'contributed_services' ) ) . '</h4>';
			$servicesSection.= $this->installAction_services();
			$servicesSection.= ' <input style="margin:5px 0;" type="submit" name="ok[save]" value="##LL:save##" />';
			$servicesSection.= '</div>';
			 
 			if( !file_exists($this->settings['dataDir']) ){
				// installation running?
				if( $this->settings['edit_directories_manually'] && isset($this->settings['req']['uninstall']) && !empty($this->settings['req']['uninstall']) ) {
						$LL = $this->settings['labels'][$this->settings['language']];
						$statusText = '<p> <b>' . $LL['directory'] . ' ' . $LL['removed'] . '</b>. </p>';
						$statusText.= '<p><a href="?uninstall=0&amp;controller[Configuration]=1">'.$LL['continue'].' ... </a> </p>';
						$statusText.= '<p><i>'.ucFirst($LL['hint']).':</i> '.ucFirst($LL['installerService.optionEditManuallySlowsDown']).': </p>';
						$statusText.= '<pre>array( ... \'edit_directories_manually\' => \'0\', ... )</pre> ';
						$this->view->append( 'text' , $statusText );
				}else{
						// add dummy variable in case of there is any checkbox selected
						$servicesSection .= '<input type="hidden" name="settings[viewcloudtimeout]" value="'.$this->settings['viewcloudtimeout'].'" />';
						$this->view->append( 'text' , $servicesSection );
				}
				return;
			}
			
			if( isset($this->settings['static']['default_dir']) ) unset($this->settings['static']['default_dir']);

			$directoriesSection = '';
			$reinstallSection = '';
			
			
			// hint: default_dir options are generated from method viewhelper->getDirsInDataDir()
			$newDirectory = isset($this->settings['req']['settings']) && isset($this->settings['req']['settings']['newDirectory']) ? $this->settings['req']['settings']['newDirectory'] : ''; 
			$outArr = $this->getSettingsAsObjList();

			$directoriesSection = '<div style="min-height:3em;padding:5px 10px;margin:2px 0;border:1px solid #AAA;">';
			$directoriesSection.= '<h4>'. ucFirst( $this->view->widgets->getLabel( 'dataDirs_title' ) ) . '</h4>';
			
			if( count( $outArr['default_dir']['options'] ) < 1 ){
			$directoriesSection.= '<p style="margin:0.5em 0;">';
			$directoriesSection.= ' '.ucFirst($this->view->widgets->getLabel('active_data_dir')).' <b>&laquo;<u>'.$this->settings['default_dir'].'</u>&raquo;</b>';
			$directoriesSection.= ' '.ucFirst( $this->view->widgets->getLabel( 'directory_autorisation' ) ).' &laquo;'.$this->settings['directory_autorisation'].'&raquo;';
			$directoriesSection.= '</p>';
			
			}else{
					$directoriesSection.= '<p style="margin:0.5em 0;border-top:0px solid #ddd;padding-top:5px;">';
					$directoriesSection.= '<b>##LL:change_data_dir##</b> ##LL:for_document## <u>'.basename($_SERVER['PHP_SELF']).'</u> (##LL:for_user## \'' . $this->authUser['user'].'\'):';
					$directoriesSection.= '</p>';
			
					asort($outArr['default_dir']['options']);
					foreach($outArr['default_dir']['options'] as $opt) {
							if($this->settings['default_dir'] == $opt) {
									$dirSectRow[$opt]= '<tr>';
									$dirSectRow[$opt].= '<td style="padding:4px 1px 1px 0;">';
									$dirSectRow[$opt].= '##LL:directory##  <b>&laquo;<u>'.$opt.'</u>&raquo;</b>';
									$dirSectRow[$opt].= '</td>';
									$dirSectRow[$opt].= '<td style="padding:4px 0px 1px 2px;" colspan="2"> ';
									$dirSectRow[$opt].= ''.ucFirst($this->view->widgets->getLabel('active_data_dir')).' '.ucFirst( $this->view->widgets->getLabel( 'directory_autorisation' ) ).' &laquo;'.$this->settings['directory_autorisation'].'&raquo;</td>';
									$dirSectRow[$opt].= '</tr>';
									continue;
							}
							$dirSectRow[$opt]= '<tr>';
							$dirSectRow[$opt].= '<tr>';
							$dirSectRow[$opt].= '<td style="padding:2px 5px 2px 0;">';
							$dirSectRow[$opt].= ' ##LL:directory## &laquo;<a title="'.ucFirst($this->view->getLabel('change_data_dir')).': '.$opt.'" class="black" href="?settings[default_dir]='.$opt.'&act='.$this->view->action.'&ok[save]=1">'.$opt.'</a>&raquo;';
							$dirSectRow[$opt].= '</td>';
							$dirSectRow[$opt].= '<td style="padding:2px 0;" title="'.ucFirst($this->view->getLabel('change_data_dir')).': '.$opt.'"> ';
							$dirSectRow[$opt].= ' <a class="small" href="?settings[default_dir]='.$opt.'&act='.$this->view->action.'&ok[save]=1">&larr;</a>';
							$dirSectRow[$opt].= ' <a title="'.ucFirst($this->view->getLabel('files.delete')).': '.$opt.'" class="small" href="?ok[remove]='.$opt.'&act=install" onclick="return window.confirm(\''.ucFirst($this->view->getLabel('files.delete')).'?\n##LL:directory##: '.$opt.'\');">'.ucFirst($this->view->getLabel('files.delete')).'</a> ';
							$dirSectRow[$opt].= '</td>';
							$dirSectRow[$opt].= '<td>';
							$dirSectRow[$opt].= '</td>';
							$dirSectRow[$opt].= '</tr>';
					}
					if( count($dirSectRow) ) $directoriesSection.= "\n\t<table border=\"0\" >\n\t\t" . implode( "\n\t\t" ,$dirSectRow ) . "\n\t</table>\n";
			}
			$directoriesSection.= '<p style="margin:2px 0;border-top:1px solid #ddd;padding-top:5px;">';
			$directoriesSection.= ' <label>##LL:new_directory_label## <input type="text" id="newdirectory" name="settings[newDirectory]" value="'.$newDirectory.'" /> </label>';
			$directoriesSection.= ' <input style="margin:5px 0;" type="submit" name="ok[create]" value="##LL:new_directory##"  onclick="var dirname=document.getElementById(\'newdirectory\').value; if( dirname == \'\'){return false}else{return window.confirm(\'##LL:new_directory_hint## \n'.ucFirst($this->view->getLabel('new_directory')).': \' + dirname);}" />';
			$directoriesSection.= '</p>';
			$directoriesSection.= '</div>';
			
			$reinstallSection.= '<div style="padding:5px 10px;margin:2px 0;border:1px solid #AAA;">';
			$reinstallSection.= '<h4>'. ucFirst( $this->view->widgets->getLabel( 'reinstall_value' ) ) . '</h4>';
			$reinstallSection.= '<table class="nopad"><tr><td>';
			$reinstallSection.= '<input type="submit" name="uninstall[1]" value="'.$this->view->widgets->getLabel( 'reinstall_value' ) . '" onclick="return window.confirm(\'##LL:caution## \n##LL:reinstall_description##! \n##LL:reinstall_hint##\');" /> ';
			$reinstallSection.= '</td><td>';
			$reinstallSection.= '<i>'.$this->view->widgets->getLabel( 'reinstall_description' ) . ', ##LL:as_defined_in##  &laquo;' . basename($_SERVER['PHP_SELF']) . '&raquo; </i>';
			$reinstallSection.= '</td></tr></table>';
			$reinstallSection.= '</div>';
			
			
			$this->view->assign( 'button' ,  $directoriesSection . $reinstallSection . $servicesSection);
    }

    /**
     * action installAction_services
     *
     * @return void
     */
    public function installAction_services() {
			$servicesSection = '';
			$aServicenames = array( 'documentscontroller','spreadsheetservice','spreadsheet_excel_reader' );
			
			$servicesSection.= '<table cellspacing="5" cellpadding="0" class="0datatable">';
			$servicesSection.= '<tr>';
			$servicesSection.= '<th>'.ucFirst( $this->view->widgets->getLabel( 'contributed_service' ) ).'</th>';
			$servicesSection.= '<th>'.ucFirst( $this->view->widgets->getLabel( 'contributed_details' ) ).'</th>';
			$servicesSection.= '<th>'.ucFirst( $this->view->widgets->getLabel( 'contributed_author' ) ).'</th>';
			$servicesSection.= '<th>'.ucFirst( $this->view->widgets->getLabel( 'contributed_website' ) ).'</th>';
			$servicesSection.= '<th>'.ucFirst( $this->view->widgets->getLabel( 'license' ) ).'</th>';
			$servicesSection.= '</tr>';
			foreach( $aServicenames as $srv ){
					$service = 'enable_service_' . $srv;
					$isExisting = $this->csvService->isClassExisting( $srv );
					$objSet = array( 'label' => ucFirst( $this->view->widgets->getLabel( $service ) ) , 'slider'=>'slider round' );
					if( empty($isExisting) ) {
							$objSetValue = 0;
							$objSet['disabled'] = 1;
							$tabRowClass = 'class="disabled"';
					}else{
							$objSetValue = $this->settings[$service];
							$tabRowClass = '';
					}
					
					$servicesSection.= '<tr '.$tabRowClass.'>';
					$servicesSection.= '<td class="nowrap" title="##LL:enable_service_'. $this->settings[$service].'##">';
					$servicesSection.= ' ' . $this->view->widgets->objCheckbox( 'settings['.$service.']' , $objSetValue , $objSet ) . '';
					$servicesSection.= '</td>';
					$servicesSection.= '<td>';
					$servicesSection.= ' <label for="settings_'.$service.'" title="##LL:enable_service_'. $this->settings[$service].'##">';
					$servicesSection.= ' ' . ucFirst( $this->view->widgets->getLabel( 'enable_service_details_' . $srv ) ) . '';
					$servicesSection.= '</label>';
					$servicesSection.= '</td>';
					$servicesSection.= '<td>';
					$servicesSection.= ' <label for="settings_'.$service.'" title="##LL:enable_service_'. $this->settings[$service].'##">';
					$servicesSection.= ' ' . ucFirst( $this->view->widgets->getLabel( 'license_author_' . $srv ) ) . '';
					$servicesSection.= '</label>';
					$servicesSection.= '</td>';
					$servicesSection.= '<td>';
					$servicesSection.= ' <a target="_blank" href="##LL:enable_service_url_'.$srv.'##">';
					$servicesSection.= ' ' . ( $this->view->widgets->getLabel( 'enable_service_name_' . $srv ) ) . '';
					$servicesSection.= '</a>';
					$servicesSection.= '</td>';
					$servicesSection.= '<td>';
					$servicesSection.= ' <a target="_blank" href="##LL:license_url_'.$srv.'##">';
					$servicesSection.= ' ' . ucFirst( $this->view->widgets->getLabel( 'license_' . $srv ) ) . '';
					$servicesSection.= '</a>';
					$servicesSection.= '</td>';
					$servicesSection.= '</tr>';
			}
			$servicesSection.= '</table>';
			return $servicesSection;
	}

    /**
     * action databaseAction
     * file handling
     *
     * @return void
     */
    public function databaseAction() {
			// initiate model and assign
// 			$this->view->models->viewoptions['index_editable'] = TRUE;
 			$this->view->assign( 'button' , $this->view->models->htmlModelEditor( 'Sqlconnect' ) );
 			
			// no action, only display table
			if( !isset($this->settings['req']) || !isset($this->settings['req']['execute']) ) {
				if( is_array($this->view->models->model->debug) && count($this->view->models->model->debug) ) $this->debug['databaseAction sql-query-result'] = implode( ', ' , $this->view->models->model->debug );
				return false;
			}
 			
 			// get the selected recordset and execute action as defined in models method model->executeAction()
			$modeltable = $this->getFirstArrayKey($this->settings['req']['execute']);
			$key = $this->getFirstArrayKey($this->settings['req']['execute'][$modeltable]);
			$this->view->models->model->executeAction( $key );
			if( is_array($this->view->models->model->debug) && count($this->view->models->model->debug) ) $this->debug['databaseAction sql-execute-result'] = implode( ', ' , $this->view->models->model->debug );
			
			return true;
	}

    /**
     * action profileAction
     * can be an action but also a alias-action for users
     *
     * @return void
     */
    public function profileAction() {
			// redirect if user action is readable for this user
 			if( $this->authUser['group'] >= $this->accessRules['users'] ) return 'users';
			
			// display page
			$pgtitle = '<h3 style="margin-top:0;">'.ucfirst($this->view->getLabel('profile')).'';
			$pgtitle.= '<span style="font-weight:normal"> ('. trim( $this->view->getLabel('local') . ' ' . basename($this->settings['dataDir']) ) .')</span>';
			$pgtitle.= '</h3>';
			
			$aFilter = array( 'user'=>'=='.$this->authUser['user'] );
			$lockedRecords = array( $this->authUser['user'] => true );
			$aOptions = array( 'title'=>FALSE , 'addrecords' => FALSE )  ;
			$this->view->models->viewoptions['option_defaults']['table'] = FALSE;
			$page =  $this->view->models->htmlModelEditor( 'users' , $aFilter , $lockedRecords , $aOptions );
			$this->view->assign( 'page' , $pgtitle . $page);
			
			// debugger
			if( is_array($this->view->models->model->debug) && count($this->view->models->model->debug) ) {foreach($this->view->models->model->debug as $title=>$msg) $this->debug[ 'profileAction->authUserModel->' . $title ] = $msg;}
	}

    /**
     * action usersAction
     *
     * @return void
     */
    public function usersAction() {
			$aFilter = array( 'group' => '<='.$this->authUser['group'] );
			$lockedRecords = array( $this->authUser['user'] => true );
			$aOptions = array( 'title'=>FALSE , 'hints'=>FALSE ) ;
			$page =  $this->view->models->htmlModelEditor( 'users' , $aFilter , $lockedRecords , $aOptions );
			$page.= $this->users_aclOptions($this->authUser['group']);

			$title = $this->view->models->obj_HtmlActualModelTitle();
			$title .= '<p style="margin:0;padding:0;"> ##LL:edit_user_grade_hint##. <br /> <i> ##LL:edit_cell_hint## </i> </p>';
			
			// assign page and debugger
			$this->view->assign( 'page' , $title .$page );
			if( isset($this->view->models->model->debug) && count($this->view->models->model->debug) ) {foreach($this->view->models->model->debug as $title=>$msg) $this->debug[ 'usersAction->authUserModel->' . $title ] = $msg;}
    }

    /**
     * users_aclOptions
     *
     * @param int $editorsUserGroup optional, default = 99 (full-admin-rights)
     * @return string
     */
    public function users_aclOptions( $editorsUserGroup = 99) {
			$allControllers = $this->allControllersObjects;
			for( $list = array( 'actions' , 'documents' , 'notes' , 'configuration' ) , $z = 0 ; $z < count($list) ; ++$z ){
				$controller = $list[$z];
				if( !isset($allControllers[$controller]) || empty($allControllers[$controller]) ) continue;
				$allActions = $allControllers[$controller]->getAuthorisedActions(99);
				$aclRule[$controller]['title']  = ucFirst( $controller ).'Controller';
				$aclRule[$controller]['suffix']  = 'Action';
				foreach( $allActions as $action => $ruleNr ){
						$aclRule[$controller]['data'][ $action ]  = $ruleNr ;
				}
			}
			
			$aclRule['Category']['title']  = ucFirst( $this->view->widgets->getLabel( 'categories' ) );
			$aclRule['Category']['suffix']  = 'Category';
			foreach( $this->categoriesAccessRules as $category => $ruleNr ) {
				$aclRule['Category']['data'][$category]  = $ruleNr ;
			}
			// crate list for options in select input-field
			$rulesList = explode( ',' , $this->settings['acl_rules_list']);
			foreach($rulesList as $ix => $ruleNr ){
				if( $ruleNr > $editorsUserGroup ){
						$rulesList = array_slice( $rulesList , 0 , $ix );
						break;
				}
			}
			$selectorOptions = array( 'title'=>ucFirst( $this->view->widgets->getLabel( 'allow_acces_from_group' ) ));
			$accesRoulesSection = '<div style="width:auto;float:left;padding:0;margin-top:25px;">';
			$accesRoulesSection.= '<h3>'. ucFirst( $this->view->widgets->getLabel( 'access_control' ) ) . ' <span style="font-weight:normal">(Global)</span></h3>';
			$accesRoulesSection.= '<div style="border:1px solid #888;padding:5px 0 5px 5px">';
			foreach($aclRule  as $controller => $aSections ){
				$divStyle = 'width:auto;float:left;margin:0 15px 0 0;';
				if( $controller == 'configuration' ) $divStyle .= 'padding-left:15px;border-left:1px dotted #aaa;' ;
				$accesRoulesSection .= '<div style="'.$divStyle.'"> ' ;
				$accesRoulesSection .= '<h5>'. $aSections['title'].'</h5> ' ;
				$accesRoulesSection.= '<table>';
				foreach( $aSections['data'] as $action => $ruleNr ){
						if( !isset($this->settings['acl_' . $action . $aSections['suffix'] ]) )  continue;
						$label = $this->view->widgets->getLabel( 'newact.' . $action . '.value' , $action );
						if( $action == $label ) $label = $this->view->widgets->getLabel( $action , $action );
						if( $action == $label ) $label = ucFirst( $action );
						$selectorsName = 'acl_' . $action . $aSections['suffix'];
						$selectedOption = $this->settings[$selectorsName];
						$individualRulesList = $rulesList;
						if( !in_array( $selectedOption , $individualRulesList ) ) $individualRulesList[] = $selectedOption;
						sort($individualRulesList);
						$accesRoulesSection .= '<tr title="' . $selectorsName . '"><td> ' .$label .' </td><td style="text-align:center;"> ';
						if($selectedOption > $editorsUserGroup){
							$accesRoulesSection .= '<input type="text" disabled="1" style="width:2em;text-align:center;" value="'.$selectedOption.'" />' ;
						}else{
							$accesRoulesSection .= $this->view->widgets->objSelect( 'settings[' . $selectorsName . ']' , $individualRulesList , $selectedOption , $selectorOptions );
						}
						$accesRoulesSection .= ' </td></tr> ' ;
				}
				$accesRoulesSection.= '</table>';
				$accesRoulesSection.= '</div>';
			}
			$accesRoulesSection .= '<div style="clear:left;"> </div></div>';
			$accesRoulesSection.= '<p style="margin-top:10px;"><input type="submit" name="ok[save]" value="##LL:save##" /></p>';
			$accesRoulesSection .= '</div>' ;
			$accesRoulesSection .= '<div style="clear:left;"> </div>';
			return $accesRoulesSection;
	}

    /**
     * action settings_cat_output
     *
     * @return void
     */
    public function settings_cat_outputAction() {
		if( $this->authUser['group'] >= $this->categoriesAccessRules['output'] ) $this->settings['req']['cat']['output'] = 'output';
		if( !isset($this->settings['req']['cat']) || !count($this->settings['req']['cat']) ) $this->setLowestCategory() ;
		return 'settings';
    }

    /**
     * action settings_cat_cronn
     *
     * @return void
     */
    public function settings_cat_cronnAction() {
		if( $this->authUser['group'] >= $this->categoriesAccessRules['connection'] ) $this->settings['req']['cat']['connection'] = 'connection';
		if( $this->authUser['group'] >= $this->categoriesAccessRules['cron'] ) $this->settings['req']['cat']['cron'] = 'cron';
		if( !isset($this->settings['req']['cat']) || !count($this->settings['req']['cat']) ) $this->setLowestCategory() ;
		return 'settings';
    }

    /**
     * action settings_cat_sync
     *
     * @return void
     */
    public function settings_cat_syncAction() {
		if( $this->authUser['group'] < $this->categoriesAccessRules['syncronisation'] ){
			if( $this->authUser['group'] >= $this->categoriesAccessRules['connection'] ) $this->settings['req']['cat']['connection'] = 'connection';
		}else{
			$this->settings['req']['cat']['syncronisation'] = 'syncronisation';
		}
		if( !isset($this->settings['req']['cat']) || !count($this->settings['req']['cat']) ) $this->setLowestCategory() ;
		return 'settings';
    }

    /**
     * action settings_cat_connection
     *
     * @return void
     */
    public function settings_cat_connectionAction() {
		if( $this->authUser['group'] >= $this->categoriesAccessRules['connection'] ) $this->settings['req']['cat']['connection'] = 'connection';
		if( !isset($this->settings['req']['cat']) || !count($this->settings['req']['cat']) ) $this->setLowestCategory() ;
		return 'settings';
	}

    /**
     * action settings_cat_pdf
     *
     * @return void
     */
    public function settings_cat_pdfAction() {
		if( $this->authUser['group'] >= $this->categoriesAccessRules['pdf'] ) $this->settings['req']['cat']['pdf'] = 'pdf';
		if( !isset($this->settings['req']['cat']) || !count($this->settings['req']['cat']) ) $this->setLowestCategory() ;
		return 'settings';
	}

    /**
     * helper getLowestCategory get category with lowest accesGrade
     *
     * @return void
     */
    public function setLowestCategory() {
		$rules = $this->categoriesAccessRules;
		asort($rules);
		$sortRulesKeys = array_keys($rules);
		$lowestCat = array_shift( $sortRulesKeys );
		$this->settings['req']['cat'][$lowestCat] = $lowestCat;
		return $lowestCat;
    }

}


?>
