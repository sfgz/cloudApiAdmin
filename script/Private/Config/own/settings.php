<?php

/************************************/
/*                                  */
/*  If a file named                 */
/*                                  */
/*  settings.json                   */
/*                                  */
/*  is in the directory             */
/*                                  */
/*  script/Private/Cofig/own        */
/*                                  */
/*  then the that file gets readen  */
/*  and this php - file is obsolete */
/*                                  */
/************************************/

// connection to nextcloud-accounts
// and text that goes into pdf output
$file_settings = array(
	'connection_user' => 'nextcloud-unsername', 
	'connection_pass' => 'nextcloud-password encrypted like VnlDNkoIb2hyOxRJSUxLNy9SOUZ2VU9lNVJpZnBLWHdKYjJZ1GRlcWhyZz=0', 
	'connection_prot' => 'https', 
	'connection_url' => 'nextcloud-url', 
	'connection_folder' => 'cloudApiAdmin', 
	'pdf_options_Footertext_left' => 'Diese Liste wurde automatisch geteilt am __date_long__.',
	'pdf_options_Footertext_right' => '__C__ __date_Y__ beispiel.com',
	'checkByDefault_UsersMaybeObsolete'=>'0',
);

return $file_settings;
?>
