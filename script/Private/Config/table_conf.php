<?php
if (!class_exists('Drg\CloudApi\core', false)) die( 'Die Datei "' . pathinfo( __FILE__ , PATHINFO_FILENAME ) . '" muss von Klasse "core" aus aufgerufen werden.' );

/**
 * Usage of this file table_conf.php 
 * this file manages the mapping and tables configuration
 * placeholders:
 * {filename}: the name of uploaded file. predefined names are 'default', 'group_quota' and 'delete_list'
 * {inFieldname}: the headline of the row in uploaded table (e.g. 'username')
 * {outFieldname}: the fieldname expected by cloud (e.g. ID or DISPLAYNAME)
 * {parameter}: as described below: CHAR, FIELDS, INDEX.
 * {ownFunctionName}: shortname of own (user) function. Own Functions are stored in class 'TableFunctionsUtility'. 
 * see example for {ownFunctionName} below. (line 45 +flwng)
 *
 * FIELD-MAPPING
 * [{filename}][mapping][ {outFieldname} ] => '{inFieldname}'
 * 
 * FUNCTIONS call
 * [{filename}][mapping][ {outFieldname}.FUNCTION ] = 'APPEND'|'EXTRACT'|'CONCAT'|'VALUE'|'{ownFunctionName}'
 * 
 * FUNCTIONS Parameters depending on called function. Predefined {parameter} are FIELDS, CHAR and INDEX.
 * [{filename}][mapping][ {outFieldname}.PARAM.{parameter} ] = 'xyz' (string)
 * 
 * Predefined functions and parameters:
 * 
 * CONCAT
 * [{filename}][mapping][ {outFieldname}.FUNCTION ] => 'CONCAT' (string)
 * [{filename}][mapping][ {outFieldname}.PARAM.CHAR => ' ' (string)
 * [{filename}][mapping][ {outFieldname}.PARAM.FIELDS => '{inFieldname1},{inFieldname2},{inFieldnameN}' (string,comma-separed list)
 * 
 * EXTRACT
 * [{filename}][mapping][ {outFieldname}.FUNCTION ] => 'EXTRACT' (string)
 * [{filename}][mapping][ {outFieldname}.PARAM.FIELDS ] => {inFieldname} (string,single fieldname)
 * [{filename}][mapping][ {outFieldname}.PARAM.CHAR ] => '@' (string),(char to split fieldcontent in parts)
 * [{filename}][mapping][ {outFieldname}.PARAM.INDEX ] => '0' (integer),(position of requested part, 0=first part)
 * 
 * APPEND 
 * [{filename}][mapping][ {outFieldname}.FUNCTION ] => 'APPEND' (string)
 * [{filename}][mapping][ {outFieldname}.PARAM.CHAR ] => ' GB' (string)
 * 
 * VALUE 
 * [{filename}][mapping][ {outFieldname} ] => '{inFieldname}' (string), (only insert static VALUE if cell in given column {inFieldname} is empty) 
 * [{filename}][mapping][ {outFieldname}.FUNCTION ] => 'VALUE'  (string)
 * [{filename}][mapping][ {outFieldname}.PARAM.CHAR ] => ' GB' (string)
 * 
 * {ownFunctionName}
 * [{filename}][mapping][ {outFieldname}.FUNCTION ] => {ownFunctionName}
 * 
 * own Functions Name is arbitrary  (e.g. gibWert)
 * [{filename}][mapping][ {outFieldname}.FUNCTION ] => 'gibWert';
 * [{filename}][mapping][ {outFieldname}.PARAM.FIELDS ] => '9.5 GB';
 * 
 * If the user-function needs one or more of the input fields FIELDS, CHAR and/or INDEX 
 * then add it to the property '$afforedFields' in class 'TableFunctionsUtility'
 * 
 * the userfuntions itself expects 2 parameters, fieldname (string) and the dataRow (array) of actual userRecordset
 * e.g. Public function userFunct_gibWert( (string)$fieldName , (array)$userDataRow ){ ... return (string)'The users Id is:' . $userDataRow['ID']; }
 * 
 */

 //define default tables 'default', 'group_quota' and 'delete_list'
$file_settings = array(

	'default'=> array(
		'mapping'=> array(
			'ID'=>'username',
			'ID.FUNCTION'=>'EXTRACT',
			'ID.PARAM.FIELDS' => 'email',
			'ID.PARAM.CHAR'=>'@',
			'ID.PARAM.INDEX'=>'0',
			'DISPLAYNAME'=>'',
			'DISPLAYNAME.FUNCTION'=>'CONCAT',
			'DISPLAYNAME.PARAM.FIELDS' => 'firstname,lastname',
			'DISPLAYNAME.PARAM.CHAR'=>' ',
			'EMAIL'=>'email',
			'QUOTA'=>'quota',
			'QUOTA.FUNCTION'=>'APPEND',
			'QUOTA.PARAM.CHAR'=>' GB',
			'grp_1'=>'grp_1',
			'grp_2'=>'grp_2',
			'grp_3'=>'grp_3',
			'grp_4'=>'grp_4',
			'grp_5'=>'grp_5',
		),
		'location'=> 'local/users/',
		'samples'=> array(
			0 => array(
				'0' =>'lastname', 
				'1' =>'firstname', 
				'2' =>'grp_1',
				'3'=>'username',
				'4'=>'email',
				'5'=>'quota'
			),
			1 => array(
				'0' =>'Müller', 
				'1' =>'Heino', 
				'2' =>'Consulting',
				'3'=>'heino.mueller',
				'4'=>'heino-mueller@beispiel.ch',
				'5'=>'80'
			),
			2 => array(
				'0' =>'Muster', 
				'1' =>'Hansine', 
				'2' =>'Manager',
				'3'=>'hansine.muster',
				'4'=>'hansine@beispiel.ch',
				'5'=>'2'
			),
			3 => array(
				'0' =>'Doe', 
				'1' =>'Joan', 
				'2' =>'Worker',
				'3'=>'joan.doe',
				'4'=>'jd@example.ch',
				'5'=>'5'
			),
		)
	),

	'group_quota'=> array(
		'mapping'=> array(
			'ID'=>'gruppe',
			'QUOTA'=>'quota',
		),
		'force_filename'=> 'group_quota.csv',
		'location'=> 'local/quota/',
		'fallback_option_name'=> 'default',
		'samples'=> array(
			array( 
				'0'=>'gruppe', 
				'1' =>'quota' 
			) , 
			array( 
				'0'=>'default', 
				'1' =>'10 GB' 
			) , 
			array( 
				'0'=>'*er', 
				'1' =>'12 GB'
			), 
			array( 
				'0'=>'*ger', 
				'1' =>'19 GB'
			), 
			array( 
				'0'=>'C*', 
				'1' =>'12.5 GB'
			), 
			array( 
				'0'=>'ku*e', 
				'1' =>'16.5 GB'
			), 
			array(
				'0'=>'ad*', 
				'1' =>'18 GB' 
			)
		)
	),

	'delete_list'=> array(
		'mapping'=> array( 'ID'=>'ID' ),
		'force_filename'=> 'delete_list.csv',
		'location'=> 'local/delete/',
		'samples'=> array(
			0 => array(
				'0' =>'ID'
			),
			1 => array(
				'0' =>'heino.mueller'
			),
		)
	)

);

return $file_settings;

?>
