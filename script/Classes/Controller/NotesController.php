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
 * NotesController
 * 
 *
 */
 
/**
*/

class NotesController extends \Drg\CloudApi\controllerBase {

	/**
	 * Property actionDefault
	 *
	 * @var string
	 */
	Public $actionDefault = 'editnotes';

	/**
	 * Property fallbackPartial
	 *
	 * @var string
	 */
	Public $fallbackPartial = 'ActionsToolbar';

	/**
	 * Property actualSection
	 *
	 * @var string
	 */
	protected $actualSection = '';

	/**
	 * Property actualLanguage
	 *
	 * @var string
	 */
	protected $actualLanguage = '';

	/**
	 * Property accessRules
	 *
	 * @var array
	 */
	Protected $accessRules = array(
		'viewnotes' => 0,
		'editnotes' => 1,
		'listnotes' => 5,
	);

	/**
	 * initiate
	 *
	 * @return  void
	 */
	public function initiate() {
			parent::initiate();
			
			$this->authUser = $this->authService->getAuthUsersRecordset();
			if( empty($this->authUser) ) $this->authUser['group'] = 0;

			$Model = "\\Drg\\CloudApi\\Models\\NotesModel" ;
			$this->model = new $Model( $this->settings );
			$this->model->initiate();
			
			$tocData = $this->model->getRecordsets();
			foreach( $tocData as $id => $notes ) {
				$tocLinks[ $notes['lang'] ][$notes['key']] = $notes['key'];
			}
			
			$this->actualLanguage = isset($this->settings['req']['language']) && isset($tocLinks[$this->settings['req']['language']]) ? $this->settings['req']['language'] : $this->settings['language'];
			
			// get section if selected by user 
			if( isset($this->settings['req']['sec']) ){
				$this->actualSection = $this->settings['req']['sec'];
			}elseif( isset($this->settings['req']['lastsec']) ){
				$this->actualSection = $this->settings['req']['lastsec'];
			}
			// select first enabled section of choosen language if no section is selected by user 
			if( empty($this->actualSection) || !isset($tocLinks[$this->actualLanguage][$this->actualSection]) ) {
				$this->model->initiate();
				$aFilter = array( 'constrain' => 'AND' ,  'display' => '<='.$this->authUser['group'] , 'lang' => '==='. $this->actualLanguage  );
				$tocData = $this->model->getRecordsets( $aFilter );
				$firstNote = array_shift($tocData) ;
				$this->actualSection = $firstNote['key'] ;
			}
			
			$this->view->assign( 'configAction' , 'profile' );
			
	}

    /**
     * action viewnotesAction
     *
     * @return void
     */
    public function viewnotesAction() {
			$syntaxService = new \Drg\CloudApi\Services\SyntaxService();

			// creates links of possible sections for TOC
			$this->model->initiate();
			$aFilter = array( );
			if( empty($this->authUser['group']) ) {$aFilter['display'] = '<=0';}else{ $aFilter['display'] = '<='.$this->authUser['group']; }
			$tocData = $this->model->getRecordsets( $aFilter );
			foreach( $tocData as $id => $notes ) {
				$languageLinks[$notes['lang']] = $notes['lang'] == $this->actualLanguage ? ' <i>['.ucFirst($notes['lang']) .']</i> ' : ' [<a href="?act='.$this->view->action.'&amp;sec='.$this->actualSection.'&amp;language='.$notes['lang'].'">'.ucFirst($notes['lang']).'</a>] ';
				if($notes['lang'] != $this->actualLanguage) continue;
				$noteName = $notes['key'];
				$tocLinks[$noteName] = $this->actualSection == $noteName ? '<i>'.ucFirst($notes['title']).'</i>' : '<a href="?act='.$this->view->action.'&amp;sec='.$noteName.'&amp;language='.$this->actualLanguage.'">'.ucFirst($notes['title']).'</a>';
			}
			// glue links and create navi-bar
			$page = !isset($tocLinks) || !count($tocLinks) ? '' : '<div style="margin:0.5em 0;">' . implode( ' | ' , $tocLinks ) . '</div>';
			
			// create content and assign to page
			$this->model->initiate();
 			$aFilter = array( 'constrain' => 'AND' , 'key' => '==='. $this->actualSection , 'display' => '<='.$this->authUser['group'] , 'lang' => '==='. $this->actualLanguage );
			$data = $this->model->getRecordsets( $aFilter );
			$addrecords = TRUE;
			if( $data && count($data) ){
					$singleData = array_shift( $data );
					$sOut = isset($singleData['title']) ? '<h2>' .$singleData['title'] . '</h2>' : '';
					if( isset($singleData['body']) ) $sOut .=  $syntaxService->wikiToHtml($singleData['body']);
					$page .= $sOut ? '<div style="text-align:left;margin:0 2em;">' . $sOut . '</div>' : '';
			}
			
 			$page .= "\t\t\t\t".'<p class="viewnotesAction">' . implode( ' ' , $languageLinks ) . '</p>' ;
 			$page .= "\t\t\t\t".'<input type="hidden" name="sec" value="'.$this->actualSection.'">' ;
 			$page .= "\t\t\t\t".'<input type="hidden" name="language" value="'.$this->actualLanguage.'">' ;
			$downloadLink = $this->getDownloadLink();
			$this->view->assign( 'LINK' , $downloadLink ? '<p>Download: '.$downloadLink.' </p> ' : '' );
			$this->view->assign( 'title' , '<H1>'.$this-> getTitle().'</H1>' );
			$this->view->assign( 'page' , '<div class="viewnotes">'.$page.'</div>' );
			if( !$this->authService->isLoggedIn ) $this->view->assign( 'button' , '<div style="background:#fff;border-radius:5px;margin-top:10px;padding:5px 0 10px 0;"><a href="?act=welcome">&larr; Login</a></div>' );
	}

    /**
     * action editnotesAction
     *
     * @return void
     */
    public function editnotesAction() {

			// creates links of possible sections for TOC
			$this->model->initiate();
			$aFilter = array( );
			if( empty($this->authUser['group']) ) {$aFilter['display'] = '<=0';}else{ $aFilter['display'] = '<='.$this->authUser['group']; }
			$tocData = $this->model->getRecordsets($aFilter);
			foreach( $tocData as $id => $notes ) {
				$languageLinks[$notes['lang']] = $notes['lang'] == $this->actualLanguage ? ' <i>['.ucFirst($notes['lang']) .']</i> ' : ' [<a href="?act='.$this->view->action.'&amp;sec='.$this->actualSection.'&amp;language='.$notes['lang'].'">'.ucFirst($notes['lang']).'</a>] ';
				if( $notes['lang'] == $this->actualLanguage ) $tocLinks[$notes['key']] = $this->actualSection == $notes['key'] ? '<i>'.ucFirst($notes['title']).'</i>' : '<a href="?act='.$this->view->action.'&amp;sec='.$notes['key'].'&amp;language='.$this->actualLanguage.'">'.ucFirst($notes['title']).'</a>';
			}
			if( isset($languageLinks) ) ksort( $languageLinks );
			// glue links and create navi-bar
			$page = '<H1>'.$this-> getTitle().'</H1>';
			$page .= '<div style="margin:0.5em 0;">' . implode( ' | ' , $tocLinks ) . '<br /> </div>';
			
			// initiate model create content and assign to page
 			$aFilter = array( 'constrain' => 'AND' , 'key' => '==='. $this->actualSection.'' ,  'lang' => '==='. $this->actualLanguage );
			$aLocked = NULL;
			$aOptions = array( 
					'title'=>FALSE , 
					'hints'=>FALSE , 
					'addrecords'=> FALSE, 
					'table'=>FALSE , 
					'labels'=>TRUE, 
					'okbutton'=>FALSE , 
					'restrict'=> $this->authUser['group'] , 
					'fields' => array( 'title'=>'H2' , 'body'=>'' , 'display'=>'' ),
			);
			
			$page .= '<input type="hidden" name="act" value="editnotes" />' ;
			$page .= '<input type="hidden" name="lastsec" value="'.$this->actualSection.'" />' ;
			$page .= '<input type="hidden" name="language" value="'.$this->actualLanguage.'" />' ;
			$page .= $this->view->models->htmlModelEditor( 'Notes' , $aFilter , $aLocked , $aOptions );
 			$page .= "\t\t\t\t".'<div class="editnotesAction" style="text-decoration:none;font-style:normal;"><p>##LL:language##: ' . implode( ' ' , $languageLinks ) . '</p></div>' ;
			$page .= "\t\t\t\t".'<input type="submit" name="ok[save]" value="##LL:save##" title="##LL:save##" /> ';
			
			$downloadLink = $this->getDownloadLink();
			if($downloadLink) $page .= '<p>Download: '.$downloadLink.' </p> ';
			
			$this->view->assign( 'page' ,  '<div class="viewnotes">'.$page.'</div>' );
	}

    /**
     * action listnotesAction
     *
     * @return void
     */
    public function listnotesAction() {
			$page = '<H1>'.$this-> getTitle().'</H1>';
			
			// initiate model create content and assign to page
 			$aFilter = NULL;
			$aLocked = NULL;
			$aOptions = array( 'title'=>true , 'hints'=>true , 'addrecords'=> true, 'table'=>true , 'labels'=>true );
			
			$page .= '<input type="hidden" name="newact[listnotes]" value="listnotes" />' ;
			$page .= '<input type="hidden" name="lastsec" value="'.$this->actualSection.'" />' ;
			$page .= $this->view->models->htmlModelEditor( 'Notes' , $aFilter , $aLocked , $aOptions );
			$this->view->assign( 'page' , $page );
	}


    /**
     * getDownloadLink
     *
     * @return string
     */
    public function getDownloadLink() {
			$downloadFileName = 'cloudApiAdmin.'.$this->settings['version'].'.zip';
			$downloadLink = file_exists(dirname($_SERVER['SCRIPT_FILENAME']).'/'.$downloadFileName) ? '<a title="download application" href="'.rtrim($this->getUrl(true),'/').'/'.$downloadFileName.'">CloudApiAdmin '.$this->settings['version'].' &darr;</a>' : '';
			return $downloadLink;
	}

    /**
     * getTitle
     *
     * @return string
     */
    public function getTitle() {
			$downloadLink = $this->getDownloadLink();
			$title = 'CloudApiAdmin '.$this->settings['version'].' - Info';
			if( $downloadLink ) $title .= ' &amp; Download';
			return $title;
	}


}

?>
