<?php
namespace Drg\CloudApi\Services;

/***************************************************************
 * cloudApiAdmin
 * installer
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
 * Class InstallerService
*/
class InstallerService {

	/**
	 * Property settings
	 *
	 * @var array
	 */
	protected $settings = NULL;

	/**
	 * Property status
	 *
	 * @var string
	 */
	Public $status = FALSE;

	/**
	 * Property userDiretory
	 *
	 * @var string
	 */
	Protected $userDiretory = 'local/users/';

	/**
	 * directoriesToInstall
	 *
	 * @var array
	 */
	protected $directoriesToInstall = array( 
				'api/import/',
				'local/delete/', 
				'local/quota/',
				'local/users/',
				'local/logo/'
	);

	/**
	 * __construct
	 *
	 * @param array $settings
	 * @return  void
	 */
	public function __construct($settings = array() ) {
		$this->settings  = $settings;
	}

    /**
     * action createSampleFiles
     * creates examples of csv-files in table_conf if defined
     *
     * @param string $selectTable optional, all if missed
     * @return void
     */
    public function createSampleFiles( $selectTable = '' ) {
			if( !is_array($this->settings['table_conf']) ) return; // settings missed
			
			foreach( $this->settings['table_conf'] as $tablename => $qSet ){
					if( !empty($selectTable) && $selectTable != $tablename ) continue;
					if( !isset($qSet['samples']) ) continue;
					if( !count($qSet['samples']) ) continue;
					if( !isset($qSet['samples'][0]) ) continue;
					if( !is_array($qSet['samples'][0]) ) continue;
					if( !isset($qSet['location']) ) $qSet['location'] = $this->userDiretory;
					
					$filePath = $this->settings['dataDir'] . $qSet['location'] . $tablename . '.csv';
					if( empty($selectTable) && file_exists($filePath) ) continue;
					
					$initiateValues = array();
					foreach( $qSet['samples'] as $row){
						$aCleanLine = array();
						foreach($row as $field){ $aCleanLine[] = trim($field); }
						$initiateValues[] = implode( $this->settings['sys_csv_delimiter'] , $aCleanLine);
					}
					$strUtf8 = implode( "\n" , $initiateValues ) . "\n";
					$strIsoContent = strtolower($this->settings['sys_csv_charset']) == 'utf-8' ? $strUtf8 : iconv( 'UTF-8' , $this->settings['sys_csv_charset'] , $strUtf8 );
					if( basename(trim($filePath,'/')) != trim($filePath,'/') ) {
						if( file_exists($filePath) ) unlink($filePath);
						file_put_contents( $filePath , $strIsoContent );
					}
			}
			return true;
	}

    /**
     * action installTemporaryDirsAction
     *
     * @return void
     */
    public function installTemporaryDirsAction() {
    
			if( empty($this->settings['edit_directories_manually']) ){
					return $this->installDirsAction( $this->settings['dataDir'] );
			}
			
			// FIXME: unused since version 2.007: 
			// if user wants to edit directories manually run the following instead the above code, 
			$tempDataBasedir = rtrim( dirname($this->settings['dataDir'])  , '/' ).'_tmp/';
			$temp_dataDir = $tempDataBasedir.basename($this->settings['dataDir']). '/' ;
			
			if( file_exists($this->settings['dataDir']) ){
					// try to delete temporary path if both folders exists
					if( file_exists($tempDataBasedir) ) $this->deleteFilesInDirectory( $tempDataBasedir );
					return $this->status;
			}
			
			// create new data folders
			$installResult = $this->installDirsAction( $temp_dataDir );
			
			// create messages
			$newName = pathinfo(dirname($this->settings['dataDir']),PATHINFO_FILENAME);
			$tempName = pathinfo(dirname($temp_dataDir),PATHINFO_FILENAME);
			$dataDir = dirname($temp_dataDir);
			$LL = $this->settings['labels'][$this->settings['language']];
			
			$statusText = '<p>';
			if( !file_exists($temp_dataDir) ){
				$statusText .= ucFirst($LL['temporary.passiv']).' ' . $LL['directory'].' "'.$dataDir.'/<b>'.$tempName.'</b>" '.$LL['created'].'. <br />';
				$statusText.= ucFirst($LL['installerService.copyTheDirAndRenameItTo']).' "<b>'.$newName.'</b>". ';
			}else{
				$statusText .= $LL['directory'].' '.$dataDir.'/<b>'.$newName.'</b> '.$LL['not_exist'].'! <br /><br />';
				$statusText.= ucFirst($LL['installerService.copyThe']).' '.$LL['temporary.active'].' ' . $LL['directory'].' "<b>'.$tempName.'</b>" ' . $LL['installerService.andRenameTheCopy'].' "<b>'.$newName.'</b>". ';
			}
			$statusText.= '<br />'.ucFirst($LL['installerService.webserverHasToHaveRights']).'!  ';
			$statusText.= '</p>';
			$statusText.= '<p>';
			$statusText.= '<a class="small" href="?uninstall=0&amp;controller[Configuration]=1">'.$LL['continue'].' ... </a> ';
			$statusText.= '</p>';
			$statusText.= '<p>';
			$statusText.= ucFirst($LL['installerService.abortRemoveDir']).': ';
			$statusText.= '<a class="small" href="?uninstall=1&act=install&amp;controller[Configuration]=1">'.$LL['abort_installation'].'</a>';
			$statusText.= '</p>';
			$statusText.= '<div>';
			$statusText.= '<p>';
			$statusText.= '<i>'.ucFirst($LL['hint']).':</i> '.ucFirst($LL['installerService.optionEditManuallySlowsDown']).': ';
			$statusText.= '</p>';
			$statusText.= '<pre>array( ... \'edit_directories_manually\' => \'0\', ... )</pre> ';
			$statusText.= '</div>';
			
			$this->status = $statusText ;
			return $this->status;
	}

    /**
     * installDirsAction
     *
     * @param string $dir
     * @return void
     */
    public function installDirsAction( $dir ) {
		$aMustbes = $this->directoriesToInstall;
		
		if( !empty( dirname($this->settings['local_settings_filename']) ) ) $aMustbes[] = dirname($this->settings['local_settings_filename']);
		
		if(!file_exists( $dir )) $this->createDirAndSubdir( $dir );
		$this->createHtaccessFile( dirname( $dir  ) . '/' );

		foreach($aMustbes as $mustbe){
			if(!file_exists( $dir . $mustbe )) {
				$this->createDirAndSubdir( $dir . $mustbe );
			}
		}

		return false; // no message to return
	}

    /**
     * createDirAndSubdir
     *
     * @param string $dir
     * @return void
     */
    public function createDirAndSubdir( $dir ) {
		 if( !file_exists($dir) ) mkdir( $dir , 0777 , true );
	}

    /**
     * createHtaccessFile
     *
     * @param string $dir
     * @return void
     */
    public function createHtaccessFile( $dir ) {
			if( empty( $this->settings['create_htaccess'] ) ) return false;
			if( file_exists( $dir . '/.htaccess' ) ) return false;
			file_put_contents( $dir . '.htaccess' , "Order allow,deny\nDeny from all" );
	}

    /**
     * uninstallTemporaryDirsAction
     *
     * @return void
     */
    public function uninstallTemporaryDirsAction() {
		$statusText = '';
		$temp_dataDir = rtrim( dirname($this->settings['dataDir'])  , '/' ).'_tmp' . '/' ;
		if( file_exists($temp_dataDir) ) {
			$this->deleteFilesInDirectory( $temp_dataDir );
			$statusText .= '<p>' .$temp_dataDir . ' entfernt. </p>';
		}
		$orig_dataDir =  dirname($this->settings['dataDir']) ;
		if( file_exists($orig_dataDir) ){
			$this->deleteFilesInDirectory( $orig_dataDir );
			$LL = $this->settings['labels'][$this->settings['language']];
			$statusText .= '<p>' . $LL['directory'] . ' <b>' . $orig_dataDir . '</b> ' . $LL['removed'] . '. </p>';
			$statusText .= '<p>' . $LL['installerService.uninstallTemporaryDirsAction'] . '</p>';
// 			$statusText .= '<p><a class="small" href="?uninstall=0&amp;act=install&amp;controller[Configuration]=1&default_dir='.basename($this->settings['dataDir']).'">'.$LL['continue'].' ... </a></p>';
			$this->status = $statusText ;
		}
		return $this->status;
	}

    /**
     * deleteFilesInDirectory
     *
     * @param string $dir
     * @return void
     */
    public function deleteFilesInDirectory( $dir ) {
				if( !file_exists($dir) ) return;
				$d = dir($dir);
				while (false !== ($entry = $d->read())) {
					if( '.' == $entry || '..' == $entry ) continue;
					$pathfile = rtrim( $d->path ,'/' ) . '/' . trim( $entry , '/' );
					if( filetype( $pathfile )  == 'dir' ) {
						$this->deleteFilesInDirectory( $pathfile );
					}else{
						//if( is_writable($pathfile) ) 
						unlink( $pathfile );
					}
				}
				$d->close();
				if( file_exists($dir) && filetype($dir) == 'dir' && is_writable($dir) ) rmdir( $dir );
	}

}


?>
