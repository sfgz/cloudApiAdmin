<?php
namespace Drg\CloudApi\Controller;
if (!class_exists('Drg\CloudApi\boot', false)) die( 'Die Datei "'.__FILE__.'" muss von Klasse "boot" aus aufgerufen werden.' );
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
 * DocumentsController
 * 
 *
 */
 
/**
*/

if (file_exists(SCR_DIR . 'Classes/Contributed/fpdf.php')){
class DocumentsController extends \Drg\CloudApi\controllerBase {

	/**
	 * Property actionDefault
	 *
	 * @var string
	 */
	Public $actionDefault = 'documents';

	/**
	 * Property fallbackPartial
	 *
	 * @var string
	 */
	Public $fallbackPartial = 'ActionsToolbar';

	/**
	 * Property accessRules
	 *
	 * @var array
	 */
	Protected $accessRules = array(
		'documents' => 5,
	);

	/**
	 * initiate
	 *
	 * @return  void
	 */
	public function initiate() {
		parent::initiate();
		$this->view->assign( 'autostart_check' , isset($this->settings['req']['autostart']) && $this->settings['req']['autostart'] ? ' checked="1" ' : '' );
		$this->view->assign( 'refresh' , isset($this->settings['req']['refresh']) && $this->settings['req']['refresh'] ? $this->settings['req']['refresh'] :$this->settings['refresh'] );
		$this->settings['actualTimeout'] = !isset($this->settings['req']['timeout']) || empty($this->settings['req']['timeout']) ? $this->settings['exectimeout'] : $this->settings['req']['timeout'];
		if( $this->settings['actualTimeout'] < 0.1 ) $this->settings['actualTimeout'] = 1;
		$this->view->assign( 'timeout' , $this->settings['actualTimeout'] );
		
		$this->view->assign( 'configAction' , 'settings_cat_pdf' );
	}

    /**
     * helper setMenueStatus
     *
     * @return void
     */
    public function setMenueStatus() {
		if( !file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv') ){
				$this->disabledActions['documents'] = 999;
		}elseif( isset($this->disabledActions['welcome']) ) {
				unset( $this->disabledActions['documents'] );
		}
	}

    /**
     * action documentsAction
     * Used to show cloud-Data 
     * 
     * Redirected to this method if called action 'clouduserAction'
     * while settings-variables 
     *  - 'download_details' AND
     *  - 'edit_joblist' 
     * are disabled
     *
     * @return void
     */
    public function documentsAction() {
		$groupPdfUtility = new \Drg\CloudApi\Utility\GroupPdfUtility($this->settings);
		// run clouduserAction (import) if there is no cloudsuser-file
		if( !file_exists(rtrim($this->settings['dataDir'] . $this->settings['cloudusers'],'/') . '/' . 'userAttributes.csv') ){
// 			$this->disabledActions['documents'] = 999;
			return 'clouduser';
		}

		$authUser = $this->authService->getAuthUsersRecordset();
		
		// set minimal page contents
		$Pagetitel = '<h1> ##LL:newact.documents.value## ##LL:newact.documents.value2## </h1>';
		$FilterBox = $this->filterBox();
		$this->view->assign( 'text' , $Pagetitel . $FilterBox );
		
		// read groups with members (usernames)
		$groupUserDB = $groupPdfUtility->getCloudGroupUsers();
		
		// abort here if there are no groups
		if( !count($groupUserDB) ) {
				$this->view->assign( 'page' , '<h2>##LL:no_data##</h2>' );
				return true;
		}

		// sort groups in alphabetical order, set refresh-time 
		ksort($groupUserDB);
		if( !isset($this->settings['refresh']) ) $this->settings['refresh'] = 15;
		$refreshTime = isset($this->settings['req']['refresh']) ? $this->settings['req']['refresh'] : $this->settings['refresh'];
		
		// calling this page at first time while autostart was checked on last page?
		if( isset($this->settings['req']['autostart']) && $this->settings['req']['autostart'] ){
			$checkAllAtStart = TRUE;
			$checkedGroups = $groupUserDB;
			foreach( $groupUserDB as $rawIx => $row ) { $checkedGroups[rawurlencode($rawIx)] = $row; }
		}else{
			$checkAllAtStart = FALSE;
			$checkedGroups = isset($this->settings['req']['groupinfo']) ? $this->settings['req']['groupinfo'] : array();
		}
		
		// action if a checkbox is checked and (a start-button is pressed or autostart) and no other button is pressed
		$pdfDocuments = array();
		$isStartAction = $checkAllAtStart || isset($this->settings['req']['ok']['create']) || isset($this->settings['req']['html_cron_active']);
		$noStartButtons = !isset($this->settings['req']['ok']['save']) && !isset($this->settings['req']['newact']);
		if( count($checkedGroups) && $isStartAction && $noStartButtons ){
				$groupPdfUtility->connectorService->prepareConnection();
				$groupPdfUtility->createMembersPdf( $checkedGroups );
				// If not called from this action by html-cron, only once at start: create folder if it does not exist, delete only obsolete files.
				if( !isset($this->settings['req']['html_cron_active']) ) $groupPdfUtility->preparePdfWork();
				if( isset($this->settings['req']['check_all']) ) unset($this->settings['req']['check_all']);
				// do the pdf work: Create new files, overwrite exisiting files and keep them as older versions
				$pdfDocuments = $groupPdfUtility->pdfWork( $this->settings['actualTimeout'] );
				// fill debugger from GroupPdfUtility if there is output
				if( $this->settings['debug'] >=1  && count( $groupPdfUtility->debug ) ){
					foreach($groupPdfUtility->debug  as $errKey => $debug ) $this->debug[$errKey] = $debug;
				}
				// reset the Checklist to prepend infinite loop
				if( count($checkedGroups) == count($pdfDocuments) ) {
					$this->debug['infinite_loop_possible'] = 'interrupted on line #132 in DocumentsController->documentsAction()'; 
					$checkedGroups = array();
				}else{
					$checkedGroups = $pdfDocuments;
				}
				// if there is still some work to do then set hidden field to restart with html-cron
				if(count($checkedGroups)) {
					$refreshLabel = ' <br /><i>##LL:next_start## <span id="refreshwatch">'.($refreshTime).'</span> ##LL:seconds##.</i> ';
					$this->view->assign( 'button' , $refreshLabel . '<input type="hidden" name="html_cron_active" value="html_cron_active" />' );
					$this->view->append( 'FOOTER' , $this->view->javaScript->countdown( 'refreshwatch' ) );
				}elseif( isset($this->settings['req']['html_cron_active']) ){
					$this->debug['html_cron_finished'] = '##LL:html_cron_finished##.'  ;
				}
		}

		// checkboxes for action-options
		$settings = $this->settings;
		$settings['refresh'] = $refreshTime;
		$okOptionsCheckboxes = $this->uploadOptions($authUser);
		if( count($pdfDocuments) ){
				$okOptionsCheckboxes .= '<p>';
				$okOptionsCheckboxes .= '<strong>##LL:html_cron_running##, ' . count($pdfDocuments) . ' ##LL:html_cron_remaining##.</strong>';
				$okOptionsCheckboxes .= ' <br /><i>##LL:next_start## <span id="refreshwatch">'.($refreshTime).'</span> ##LL:seconds##.</i> ';
				$okOptionsCheckboxes .= '(##LL:execution_time.label##: '.$this->settings['actualTimeout'].' ##LL:seconds##)';
				$okOptionsCheckboxes .= '</p>';
		}
		$okOptionsCheckboxes .= $this->view->widgets->htmTimeoutSelector( $settings , '##LL:button.create_pdf.text##' , 'ok[create]' );
		// timeout selector
		$this->view->append( 'FOOTER' , $this->view->javaScript->countdown( 'refreshwatch' ) );

		// hinttext if affored
		$hinttext = '';
		if( false == $this->settings['download_details']){
				$category = $this->settings['categories']['download_details'];
				$buttonText = '<span title="##LL:download_details##">download_details</span>';
				$variAccessRoule = isset($this->settings[ 'acl_' . $category . 'Category' ]) ? $this->settings['acl_' . $category . 'Category'] : 999;
				$butToSettings = !isset($authUser['group']) || ( $authUser['group'] < $variAccessRoule ) ? '&laquo;'.$buttonText.'&raquo;' : '<a class="small" href="?controller=configuration&amp;cat['.$category.']='.$category.'&amp;act=settings#settings_download_details">'.$buttonText.'</a>';
				$hinttext =  '<p style="margin:0;">##LL:the_option## '.$butToSettings.' ##LL:download_details_off_text##.</p>';
		}
		// action button
		$buttonSubmit = '<p> <input type="submit" name="ok[create]" value="##LL:button.create_pdf.text##" /> ##LL:button.create_pdf.label##. </p>';
		
		// create the body
		$columns = floor(sqrt(count($groupUserDB)));
		$this->view->widgets->settings = $this->settings;
		// checkbox to de/select all
		$body = '<p>' . $this->view->widgets->objCheckbox( 'check_all' , isset($this->settings['req']['check_all']) , array( 'label'=>'##LL:select_all##' , 'onclick'=>'checkAll( \'groupinfo\' , this.checked );' ) ) . '</p> ';
		// Checklist 'groupinfo' for groups , one checkbox for each group 
		$body .= $this->view->widgets->htmlChecklist( $groupUserDB , 'groupinfo' , $columns , $checkedGroups );
		
		// if there are more than 20 recordsets in a row then display the buttons on top too but checkbox only once!
		$page = '<h2>' . count($groupUserDB) . ' ##LL:groups##</h2>';
		$page .= $hinttext;
		if( count($groupUserDB) / $columns > 20 ) {
				$page .= $okOptionsCheckboxes;
				$page .= $body;
				$page .= $buttonSubmit;
		}else{
				$page .= $body;
				$page .= $okOptionsCheckboxes;
		}
		$this->view->assign( 'page' , $page  );
		
	}

    /**
     * helper uploadOptions
     *
     * @param \Drg\CloudApi\Service\AuthService $authUser
     * @return string
     */
    public function uploadOptions($authUser) {
		$category = $this->settings['categories']['connection_folder'];
		// checkbox for settings delete-unused-pdf-documents
		$okOptionsCheckboxes = '<p>';
		$okOptionsCheckboxes.= '<label>';
		$okOptionsCheckboxes.= $this->view->widgets->objCheckbox( 'settings[pdf_clear_dir_on_start]' ,  $this->settings['pdf_clear_dir_on_start'] );
		$okOptionsCheckboxes.= ' ##LL:pdf_clear_dir_on_start##:';
		$okOptionsCheckboxes.= '</label> ';
		if( !isset($authUser['group']) || ( $authUser['group'] < $this->settings['acl_' . $category . 'Category'] ) ){
			$okOptionsCheckboxes.= '<span title="edit in: ##LL:configuration##/##LL:newact.settings.value##/##LL:newact.info.value##/folder" >';
			$okOptionsCheckboxes.= '&laquo;'.$this->settings['connection_folder'].'&raquo;';
			$okOptionsCheckboxes.= '</span>';
		}else{
			$okOptionsCheckboxes.= '<span title="edit in: ##LL:configuration##/##LL:newact.settings.value##/##LL:newact.info.value##/folder" >';
			$okOptionsCheckboxes.= '<a class="small" href="?controller=configuration&amp;cat['.$category.']='.$category.'&amp;act=settings#settings_connection_folder">'.$this->settings['connection_folder'];
			$okOptionsCheckboxes.= '</a>';
			$okOptionsCheckboxes.= '</span>';
		}
		$okOptionsCheckboxes.= '.</p>';
		
		// checkbox to share new pdf-documents
		$okOptionsCheckboxes.= '<p><label>';
		$okOptionsCheckboxes.= $this->view->widgets->objCheckbox( 'settings[pdf_share_on_upload]' ,  $this->settings['pdf_share_on_upload'] );
		$okOptionsCheckboxes.= ' ##LL:button.share_pdf.text##: ##LL:button.share_pdf.label##.</label></p>';
		return $okOptionsCheckboxes;
	}

    /**
     * helper filterBox
     *
     * @return string
     */
    public function filterBox() {

		$tfOptions = array( 'label'=>'##LL:filter_group##: ' , 'id'=>'settings_exec_document_group_filters' , 'placeholder'=>'Filter: A* , 17 , ...' );
		$textField = $this->view->widgets->objText( 'settings[exec_document_group_filters]' , $this->settings['exec_document_group_filters'] , $tfOptions );
		
		$filterBox = '<p>';
		$filterBox .= $textField . ' ';
		$filterBox .= ' <input type="submit" name="ok[save]" value="##LL:search##" />';
		
		return $filterBox;
	}


}
}

?>
