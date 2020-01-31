<?php
namespace Drg\CloudApi\Services;

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

class FileHandlerService {

	/**
	 * Property directories
	 *
	 * @var array
	 */
	protected $directories = NULL;

	/**
	 * Property files
	 *
	 * @var array
	 */
	protected $files = NULL;

	/**
	 * debug
	 *
	 * @var array
	 */
	public $debug = NULL;

	/**
	 * resetPaths
	 *
	 * @return  void
	 */
	public function resetPaths() {
		$this->files = array();
		$this->directories = array();
	}

    /**
     * unlinkFile
     * deletes file $fileToUnlink 
     * if the filename is in array $aDirs
     * updates array $aDirs and returns it
     * 
     * @param string $fileToUnlink
     * @param array $aDirs
     * @return array
     */
    public function unlinkFile( $fileToUnlink , $aDirs ) {
		if( empty($fileToUnlink) ) return;
		if( !isset($aDirs['fil']) ) return;
		foreach( $aDirs['fil'] as $filename => $shortname ) {
			if ( file_exists($filename) && 
				pathinfo($filename,PATHINFO_FILENAME) == pathinfo($fileToUnlink,PATHINFO_FILENAME)  && 
				pathinfo(dirname($filename),PATHINFO_FILENAME) == pathinfo(dirname($fileToUnlink),PATHINFO_FILENAME)
			) {
				unlink( $filename );
				$this->debug['filehandler-unlinkFile'] = '##LL:file## &laquo;' . $filename . '&raquo; ##LL:deleted##';
				unset($aDirs['fil'][$filename]);
			}
		}
		return $aDirs;
	}

    /**
     * handleSingleUpload
     * allows only 1 file per directory, 
     * deletes all other files in folder if upload is sucsessfull
     * 
     * @param string $uploaddir
     * @param string $fieldname
     * @param string $newfile
     * @param string $possibleSuffixes
     * @return array
     */
    public function handleSingleUpload( $uploaddir , $fieldname = 'userfile' , $newfile = 'logo' , $possibleSuffixes = '') {
		$uploadedFile = $this->handleUpload( $uploaddir  , $fieldname , $newfile , $possibleSuffixes );
		// read files in directory
		$aFilesInDir = $this->getFilesFromDir( $uploaddir , $possibleSuffixes );
		// delete other image-files and refresh directories
		if( !empty($uploadedFile) && count($aFilesInDir) ){
			foreach( $aFilesInDir as $filename => $shortname ) {
				if($shortname != $uploadedFile){
					unset($aFilesInDir[$filename]); 
					unlink( $filename );
				}
			}
		}
		return count($aFilesInDir) ? array_shift($aFilesInDir) : false;
	}

    /**
     * handleUpload
     * 
     * @param string $uploaddir
     * @param string $fieldname
     * @param string $newfile
     * @param string $possibleSuffixes
     * @return array
     */
    public function handleUpload( $uploaddir , $fieldname = 'userfile' , $newfile = '' , $possibleSuffixes = '') {
		if( !isset($_FILES[$fieldname]) || empty($_FILES[$fieldname]) ) return;
		if( !isset($_FILES[$fieldname]['name']) || empty($_FILES[$fieldname]['name']) ) return;
		
		$allowedSuffixes = empty($possibleSuffixes) ? array() : array_flip(explode( ',' , strtolower($possibleSuffixes) ));
		$uploadSuffix = strtolower( pathinfo( $_FILES[$fieldname]['name'] , PATHINFO_EXTENSION ) );
		
		$uploadfile = empty($newfile) ? basename($_FILES[$fieldname]['name']) : pathinfo($newfile, PATHINFO_FILENAME) . '.' . $uploadSuffix;
		if( count($allowedSuffixes) && !empty($uploadSuffix) ){
			if( !isset($allowedSuffixes[$uploadSuffix]) ){
				$this->debug['filehandler-handleUpload'] = '##LL:file## &laquo;'.$uploadfile.'&raquo; <strong>##LL:forbidden_extension##</strong>: &laquo;<b>' . $uploadSuffix . '</b>&raquo;&nbsp;';
				return false;
			}
		}
		$uploaddir = rtrim( $uploaddir , '/' ) . '/';
		$uploadWorkSuccess = move_uploaded_file( $_FILES[$fieldname]['tmp_name'] , $uploaddir . $uploadfile );
		if ( $uploadWorkSuccess ) {
			$this->debug['filehandler-handleUpload'] = '##LL:file## &laquo;'.$uploadfile.'&raquo; ##LL:uploaded_successfully##. ';
			return $uploadfile;
		}
		// no file uploaded
		return false;
	}

    /**
     * getFilesFromDir
     * 
     * @param string $dirname
     * @param string $suffixes comma separed list of suffixes without dot eg. 'csv' default is 'gif,jpg,jpeg,png'
     * @return array
     */
    public function getFilesFromDir( $dirname , $suffixes= 'gif,jpg,jpeg,png' ) {
			$dirname = rtrim($dirname,'/').'/';
			$aDr = array();
			
			$d = dir($dirname);
			if(!$d) return $aDr;
			
			$aSuffix = array_flip( explode( ',' , trim(strtolower($suffixes)) ) );
			while (false !== ($entry = $d->read())) {
				if( '.' == $entry || '..' == $entry ) continue;
				$filePathName = $d->path . $entry;
				$extension = strtolower(pathinfo( $filePathName , PATHINFO_EXTENSION ));
				if( is_file( $filePathName ) && isset( $aSuffix[$extension]) ) $aDr[ $filePathName ] = $entry;
			}
			$d->close();

			return $aDr;
	}

    /**
     * getDir
     * 
     * @param string $dirname
     * @param integer $dive optional default is no dive [ -1: infinite | 0: no dive | 1: - n deepness ]
     * @param integer $iteration leave empty on start or set to 1 if you want to read root of dir like $dirname/* [ 0 ... n ]
     * @param boolean $dontReset default is FALSe do not reset paths property
     * @return array
     */
    public function getDir( $dirname , $dive = 0 , $iteration = 0 , $dontReset = FALSE) {
			$dirname = rtrim($dirname,'/').'/';
			
			if( !file_exists($dirname) ) return false;
			
			if( $dontReset != TRUE ) $this->resetPaths();
			
			$aDr = array();
			
			$d = dir($dirname);
			if(!$d) return false;
			
			while (false !== ($entry = $d->read())) {
				if( '.' == $entry || '..' == $entry ) continue;
				$aDr[$d->path . $entry] = $entry;
			}
			$d->close();

			if( count($aDr) ){
				// $this->resetPaths();
				foreach($aDr as $pathFile => $entry){ 
					if( is_file( $pathFile ) &&  __FILE__ != $pathFile ) {
						if ($iteration!=0) $this->files[ $pathFile ] = $entry;
					}elseif( is_dir( $pathFile ) ) {
						if( $dive === -1 ){ // infinite loop
							$this->getDir( $pathFile .'/' , $dive , $iteration+1 , TRUE );
						}elseif( $dive >= 1 ){
							$this->getDir( $pathFile .'/' , $dive-1 , $iteration+1 , TRUE );
						}
						$this->directories[ $pathFile .'/'  ] = $pathFile .'/' ;
					}
				}
			}
			return array( 'dir' => $this->directories , 'fil' => $this->files );
	}

    /**
     * cleanDir
     * 
     * @param string $dirname
     * @param integer $dive optional default is no dive [ -1: infinite | 0: no dive | 1: - n deepness ]
     * @param string $suffix
     * @return array
     */
    public function cleanDir( $dirname , $dive = 0 , $suffix = '' ) {
			$aSuffixes = array_flip( explode( ',' , $suffix ) );
			$filesInDir = $this->getDir( $dirname , $dive , 1);
			$counter = 0;

			if( !is_array($filesInDir) ) return false;
			
			foreach($filesInDir as $fType => $files ){
				if( !is_array($files) ) continue;
				foreach($files as $filepath => $filename ){
						if( empty($dive) && $dirname . $filename != $filepath ) continue;
						if( !isset( $aSuffixes[ pathinfo( $filename , PATHINFO_EXTENSION ) ] ) ) continue;
						if( !is_file($filepath) ) continue;
						unlink($filepath);
						$counter += 1;
				}
			}
			
			return $counter;
	}

    /**
     * readDefaultFile
     * tryes first to read a json-file in subfolder
     * then it tryes to read php file from subfolder
     * finally it looks for php file in default folder (parent of subfolder)
     * 
     * @param string $filePathName
     * @return array
     */
    public function readDefaultFile( $filePathName ) {
			$usersPath = dirname($filePathName) . '/';
			$defaultPath = dirname(dirname($filePathName)) . '/';
			$shortname = pathinfo( $filePathName , PATHINFO_FILENAME );

			if( file_exists( $usersPath . $shortname . '.json' ) ){
				$aResult = $this->readCompressedFile( $usersPath . $shortname . '.json' );
				
			}elseif( file_exists( $usersPath . $shortname . '.php' ) ){
				$aResult = include( $usersPath . $shortname . '.php' );
				
			}elseif( file_exists( $defaultPath . $shortname . '.php' ) ){
				$aResult = include( $defaultPath . $shortname . '.php' );
			}
			
			return isset($aResult) ? $aResult : FALSE;
	}

    /**
     * readCompressedFile
     * 
     * @param string $filePathName
     * @param string $type [ json | serialize ] optional, default is json
     * @return array
     */
    public function readCompressedFile( $filePathName , $type = 'json' ) {
			if( $type == 'json' ){
				$aResult = json_decode( file_get_contents( $filePathName ) , true );
			}
			if( !isset($aResult) || !is_array($aResult) ){
				$aResult = unserialize( file_get_contents( $filePathName ) );
			}
			return isset($aResult) ? $aResult : FALSE;
	}

    /**
     * writeCompressedFile
     * 
     * @param string $filePathName
     * @param array $aContent 
     * @param string $type [ json | serialize ] optional, default is json
     * @return array
     */
    public function writeCompressedFile( $filePathName , $aContent , $type = 'json' ) {
			if( $type == 'json' ){
				file_put_contents( $filePathName , json_encode( $aContent ) );
				return true;
			}elseif( $type == 'serialize' ){
				file_put_contents( $filePathName , serialize( $aContent ) );
				return true;
			}
			return false;
	}

}

?>
