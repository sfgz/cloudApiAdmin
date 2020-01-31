<?php
/************************************************************************************
 *  
 *  IMPORTANT: This file has to be readable for anyone
 *  
 ************************************************************************************/

$strImagePath = isset($_REQUEST['p']) && file_exists($_REQUEST['p']) ? $_REQUEST['p'] : '/var/www/cloudApiAdmin/data/logo.png';

$suffix = pathinfo($strImagePath,PATHINFO_EXTENSION);

if( $suffix == 'png' ){
	$imgPng = imageCreateFromPng($strImagePath);
	imageAlphaBlending($imgPng, true);
	imageSaveAlpha($imgPng, true);

	/* Output image to browser */
	header("Content-type: image/png");
	imagePng($imgPng);
	exit;
}
if( $suffix == 'gif' ){
	$imgPng = imageCreateFromGif($strImagePath);
	imageAlphaBlending($imgPng, true);
	imageSaveAlpha($imgPng, true);

	/* Output image to browser */
	header("Content-type: image/png");
	imagePng($imgPng);
	exit;
}

if($suffix == 'svg'){
	$suffix .= '+xml';
}
header('Content-Type: image/'.$suffix);

fpassthru( fopen( $strImagePath , 'rb' ) );

exit;
