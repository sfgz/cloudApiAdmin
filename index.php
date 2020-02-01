<?php

 /***************************************************************
 *
 *  index.php
 *  
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

	if( !defined('SCR_DIR') ) define( 'SCR_DIR' , dirname(__FILE__) . '/script/' );
	if( !defined('DATA_DIR') ) define( 'DATA_DIR' , dirname(__FILE__) . '/data/' );

	if( !file_exists(  SCR_DIR . 'Classes/Core/boot.php' ) ) die( basename(__FILE__).': Uups, an error encourred. SCR_DIR not defined' );
	include_once( SCR_DIR . 'Classes/Core/boot.php' );

	$objCloudApiAdmin = new Drg\CloudApi\boot(
		array(
			'default_dir' => 'cloud_sfgz',
			'bgimage_login' => 'ireland.jpg',
			'loginform_lifetime_s' => '120',
			'login_life_period_h' => '168'
		)
	);

?>
