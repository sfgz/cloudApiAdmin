<?php
## define default values (original), types (format), conditions (depends) and options (for input type select).
## define store_restriction, storage locations (static, globales, session) and categories of variables

## VARIABLES DEFAULT-VALUES ##
$file_settings['original'] = array(
// following options are fixed values, not editable in GUI
	'version' => '3.033',
	'version_date' => '05. Jan 2020',
	'controller' => 'actions',
	'language' => 'en',
	'cryptedPasswordInDefaultTables' => '1',
	'update_only_users_in_local_list' => '0',
	'hide_info_notes' => '0',
	'logosuffixes'=>'gif,jpg,jpeg,png' , 
	'sys_csv_charset' => 'UTF-8',
	'sys_csv_delimiter' => ';',
	'sys_csv_enclosure' => htmlentities('"'),
	'deletefile'=>'local/delete/' , 
	'localusers'=>'local/users/' , 
	'cloudusers'=>'api/' , 
	'processing'=>'api/import/',
	'locallang_filedir' => 'Private/Language/', 
	'default_data_filedir' => 'Private/Config/',
	'default_models_filedir' => 'Private/Config/model.',
	'default_additional_filedir' => 'Private/Config/own/',
	'table_conf_filepath' => 'Private/Config/table_conf.php', 
	'local_settings_filename' => 'settings.json', 
	'session_settings_filename' => 'session_##USERNAME##.json', 
	'acl_rules_list' => '0,1,2,3,5,7,9,11,99', 
	'directory_autorisation' => '1', 
	'service_spreadsheet_excel_reader' => 'Classes/Contributed/excel_reader27.php',
	'enable_service_spreadsheet_excel_reader' => '1',
	'enable_service_documentscontroller' => '1',
	'enable_service_spreadsheetservice' => '1',
	
//  following values are editable in GUI because they have a 'format' definition and they are not set as static
	
	'source_upload' => '1',
	'source_sample' => '0',
	'source_sql' => '0',
	'default_dir' => 'db1',

	'enable_autostart' => '1',
	'create_default_files' => '1',
	'store_global.table_conf' => '0',
	'enable_sql' => '1',
	'display_sql' => '1',
	'store_global.sql_querys' => '0',
	
	'multiselect_settings_categories' => '0',
	'maximal_rows_in_forms' => '25',
	'debug' => '1',
	'download_format' => 'xlsx',
	'download_csv_charset' => 'ISO-8859-15',
	'download_csv_delimiter' => ';',
	'download_csv_enclosure' => htmlentities('"'),
	'bgimage' => 'background.jpg', 

	'edit_joblist' => '1',
	'download_details' => '0',
	'never_decrease_quota_set_in_cloud' => '1',
	'viewcloudtimeout' => '8',
	'use_quota_list' => '1',
	'update_quota_if_distant_empty' => '0',
	'increase_personal_quota_to_group' => '1',
	'use_delete_list' => '0',
	'checkByDefault_UsersMaybeObsolete'=>'1',
	'checkByDefault_UsersMissed'=>'1',
	'checkByDefault_GroupMissed'=>'1',
	'checkByDefault_GroupMaybeObsolete'=>'1',
	'delete_apidata_after_export' => '1',
	'group_amount' => '5',
	'orphan_groupname' => 'nogroup',
	
	'connection_user' => 'cloud_admin', 
	'connection_pass' => 'bk8weHhqKzNtVVFqZHhpdllPZDBPQT09', 
	'connection_prot' => 'https', 
	'connection_url' => 'nextcloud.example.com', 
	'connection_folder' => 'cloudApiAdmin', 
	
	'exectimeout' => '30',
	'refresh' => '10',
	'exec_type' => 'none', 
	'exec_reset' => '59 23 * * 1-5', 
	'exec_action' => '*/5 0-6 * * 2-6',
	'exec_document_group_filters' => '',
	'pdf_clear_dir_on_start' => '1', 
	'pdf_share_on_upload' => '0', 
	'exec_info' => '0-59 0-23 1-31 1-12 0-6', 
	'max_logfile_lines' => '60',
	'dataDir' => 'db1',
	
	'bgimage_login' => 'ireland.jpg', 
	'loginform_lifetime_s' => '60',
	'login_life_period_h' => '168',
	'individual_login' => '1',
	'create_htaccess' => '1',
	'edit_directories_manually' => '0',
	'autostart' => 0,
//  following options are editable in GUI by own edit-form for PDF-options
	'pdf_options_Logofile'=>'local/logo/logo' , 
	'pdf_options_ImageWidth' => '120',
	'pdf_options_ImageTop' => '9',
	'pdf_options_ImageLeft' => '12',
	'pdf_options_TopMargin' => '30',
	'pdf_options_LeftMargin' => '30',
	'pdf_options_Title' => 'Mitgliederliste Nextcloud-Gruppe ##GROUP##',
	'pdf_options_Subject' => 'Mit Mitgliedern der Gruppe ##GROUP## geteilt',
	'pdf_options_Hint_text' => 'Dieses Dokument wurde mit der Nextcloud-Gruppe __laquo__##GROUP##__raquo__ geteilt.',
	'pdf_options_Footertext_left' => 'Diese Liste wurde automatisch geteilt am __date_long__. ',
	'pdf_options_Footertext_right' => '__C__ __date_Y__ ##SERVER_NAME##',

	// categoriesAccessRules Kategorien
	'acl_applicationCategory' => 9,
	'acl_outputCategory' => 1,
	'acl_syncronisationCategory' =>  7,
	'acl_connectionCategory' => 5,
	'acl_cronCategory' => 5,
	'acl_pdfCategory' => 5,
 	'acl_change_data_dirCategory' => 5,
	// accessRules ActionsController
	'acl_dateienAction' => 1,
	'acl_clouduserAction' => 3,
	'acl_viewcloudAction' => 3,
	'acl_vergleichAction' =>  3,
	'acl_exportAction' => 7,
	// accessRules DocumentsController
	'acl_documentsAction' =>  5, 
	// accessRules NotesController
	'acl_viewnotesAction' =>  0, 
	'acl_editnotesAction' =>  1, 
	'acl_listnotesAction' =>  3, 
	// accessRules ConfigurationController
	'acl_usersAction' => 11,
	'acl_profileAction' => 1,
	'acl_settingsAction' => 1,
	'acl_installAction' => 11,
	'acl_tableeditorAction' => 2,
	'acl_databaseAction' => 2,
);


## FIELD-TYPES, DEPENDENCIES and OPTION LISTS for variables of type SELECT ##
// field-formats are used only by configuration-editor in settingsAction 
$file_settings['format'] = array(
	'pdf_options_Logofile' => 'file', 
	'pdf_options_ImageWidth' => 'text_small', 
	'pdf_options_ImageTop' => 'text_small',
	'pdf_options_ImageLeft' => 'text_small',
	'pdf_options_TopMargin' => 'text_small',
	'pdf_options_LeftMargin' => 'text_small',
	'pdf_options_Title' => 'text_large',
	'pdf_options_Subject' => 'text_large',
	'pdf_options_Hint_text' => 'text_large',
	'pdf_options_Footertext_left' => 'text_large',
	'pdf_options_Footertext_right' => 'text_large',
	'connection_name' => 'select', 
	'connections' => 'button', 
	'connection_user' => 'text', 
	'connection_pass' => 'pass2way', 
	'connection_prot' => 'select', 
	'connection_url' => 'text_large', 
	'connection_folder' => 'text', 
	'exectimeout' => 'select',
	'refresh' => 'text_small',
	'enable_autostart' => 'select',
	'create_default_files' => 'check',
	'multiselect_settings_categories' => 'check',
	'orphan_groupname' => 'text',
	'enable_sql' => 'check',
	'display_sql' => 'check',
	'store_global.sql_querys' => 'check',
	'store_global.table_conf' => 'check',
	'download_details' => 'check',
	'never_decrease_quota_set_in_cloud' => 'check',
	'edit_joblist' => 'check',
	'viewcloudtimeout' => 'select',
	'update_quota_if_distant_empty' => 'check',
	'use_quota_list' => 'check',
	'increase_personal_quota_to_group' => 'check',
	'use_delete_list' => 'check',
	'checkByDefault_UsersMaybeObsolete' => 'check',
	'checkByDefault_UsersMissed' => 'check',
	'checkByDefault_GroupMissed' => 'check',
	'checkByDefault_GroupMaybeObsolete' => 'check',
	'delete_apidata_after_export' => 'check',
	'exec_type' => 'select',
	'exec_reset' => 'text',
	'exec_action' => 'text',
	'exec_document_group_filters' => 'text',
	'pdf_clear_dir_on_start' => 'check',
	'pdf_share_on_upload' => 'check',
	'exec_info' => 'label', 
	'max_logfile_lines' => 'text_small',
	'dataDir' => 'label', 
	'default_dir' => 'select', 
	'group_amount' => 'select', 
	'maximal_rows_in_forms' => 'select',
	'download_format' => 'select', 
	'download_csv_charset' => 'select', 
	'download_csv_delimiter' => 'select', 
	'download_csv_enclosure' => 'select', 
	'debug' => 'select',
	'create_htaccess' => 'check',
	'bgimage' => 'select', 
	'bgimage_login' => 'select', 
	'loginform_lifetime_s' => 'text', 
	'login_life_period_h' => 'text', 
	'individual_login' => 'check', 
	'directory_autorisation' => 'select', 
);

// dependences are used only by configuration-editor in settingsAction 
$file_settings['depends'] = array(
	'display_sql'						=>array('enable_sql',1),
	'store_global.sql_querys'						=>array('enable_sql',1),
	'pdf_clear_dir_on_start'			=>array('exec_type','documents'),
	'pdf_share_on_upload'				=>array('exec_type','documents'),
	'exec_reset'						=>array('exec_type','*'),
	'exec_action'						=>array('exec_type','*'),
	'exec_document_group_filters'		=>array('exec_type','documents'),
	'update_quota_if_distant_empty'		=>array('use_quota_list',1),
	'increase_personal_quota_to_group'		=>array('use_quota_list',1),
	'checkByDefault_UsersMaybeObsolete'	=>array('use_delete_list',0),
	'viewcloudtimeout'					=>array('edit_joblist',0),
	'never_decrease_quota_set_in_cloud'	=>array('download_details',1),
	'update_only_users_in_local_list'	=>array('download_details',1),
	'download_csv_charset'	=>array('download_format','csv'),
	'download_csv_enclosure'	=>array('download_format','csv'),
	'download_csv_delimiter'	=>array('download_format','csv'),
);

// option lists for select-objects, mostly used by configuration-editor in settingsAction 
$file_settings['options'] = array(
	'directory_autorisation' => array(
			'source' => 'viewhelper',
			'proc_name' => 'getAclForActiveUser',
			'proc_options' => 'authUserGroup',
	),
	'enable_autostart' => array(
			'source' => 'viewhelper',
			'proc_name' => 'getAclForActiveUser',
			'proc_options' => 'authUserGroup',
	),
	'language' => array(
			'source' => 'viewhelper',
			'proc_name' => 'getArrayKeys',
			'proc_options' => 'labels'
	),
	'default_dir' => array(
			'source' => 'viewhelper',
			'proc_name' => 'getDirsInDataDir',
			'proc_options' => 'authUserGroup',
	),
	'connection_name' => array(
			'source' => 'viewhelper',
			'proc_name' => 'getArrayKeys',
			'proc_options' => 'connections_users'
	),
	'bgimage' => array(
			'source' => 'viewhelper',
			'proc_name' => 'getFilesInDir',
			'proc_options' => 'Public/BgImg/'
	),
	'bgimage_login' => array(
			'source' => 'viewhelper',
			'proc_name' => 'getFilesInDir',
			'proc_options' => 'Public/BgImg/'
	),
	'debug' => array(
			'source' => 'text',
			'value' => '0,1,2'
	),
	'viewcloudtimeout' => array(
			'source' => 'text',
			'value' => '0.5,1,5,8,10,20'
	),
	'controller' => array(
			'source' => 'text',
			'value' => 'actions,configuration'
	),
	'maximal_rows_in_forms' => array(
			'source' => 'text',
			'value' => '5,10,20,25,30,50,100,200'
	),
	'exectimeout' => array(
			'source' => 'text',
			'value' => '0.01,0.5,1,2,5,10,20,30,55,60,120,' . ini_get('max_execution_time')
	),
	'exec_type' => array(
			'source' => 'text',
			'value' => 'none,reset,import,export,documents',
			'onchange' => 'toggleCronrelatedElements',
	),
	'group_amount' => array(
			'source' => 'text',
			'value' => '1,2,3,4,5,6,7,8,9'
	),
	'download_format' => array(
			'source' => 'csvService',
			'proc_name' => 'avaiableDownloadExtensions',
			'proc_options' => 'array',
			'onchange' => 'toggleSelectrelatedElements',
	),
	'download_csv_charset' => array(
			'source' => 'text',
			'value' => 'ISO-8859-15,ISO-8859-1,UTF-16,UTF-8'
	),
	'download_csv_enclosure' => array(
			'source' => 'array',
			'value' => array( '' , "'" , htmlentities('"') )
	),
	'download_csv_delimiter' => array(
			'source' => 'array',
			'value' => array(';' , "," , '&hArr;' )
	),
	'connection_prot' => array(
			'source' => 'array',
			'value' => array('https' , 'http' )
	),
);


## STORING RESTRICTIONS and LOCATIONS ##

// store variables only if they come in as POST
$file_settings['store_restriction'] = array(
	'newDirectory' => 'POST',
);

// do not store this variables at all
$file_settings['static'] = array(
	'localusers'=>'' , 
	'cloudusers'=>'' , 
	'processing'=>'',
	'edit_directories_manually' => '',
	'individual_login' => '',
	'bgimage_login' => '', 
	'create_htaccess' => '',
	'loginform_lifetime_s' => '',
	'login_life_period_h' => '',
	'dataDir' => '',
);

// session: store this variables in userfiles: for each index-file and user a file
// global: store in root data-folder. Hide them in settingsAction if they are in a category - otherwise they dont get displayed anyway
// all variables whitch are not registered in here where stored in the local folders
$file_settings['globales'] = array(
	'multiselect_settings_categories' => 'session', 
	'bgimage' => 'session', 
	'source_sample' => 'session',
	'source_upload' => 'session',
	'source_sql' => 'session',
	'default_dir' => 'session',
	'debug' => 'session',
	// categoriesAccessRules Kategorien
	'acl_applicationCategory' => 'global',
	'acl_syncronisationCategory' => 'global',
	'acl_outputCategory' => 'global',
	'acl_connectionCategory' => 'global',
	'acl_cronCategory' => 'global',
	'acl_pdfCategory' => 'global',
	'acl_change_data_dirCategory' => 'global',
	// accessRules ActionsController
	'acl_dateienAction' => 'global',
	'acl_clouduserAction' => 'global',
	'acl_viewcloudAction' => 'global',
	'acl_vergleichAction' =>  'global',
	'acl_exportAction' => 'global',
	// accessRules DocumentsController
	'acl_documentsAction' =>  'global', 
	// accessRules NotesController
	'acl_viewnotesAction' =>  'global', 
	'acl_editnotesAction' =>  'global', 
	'acl_listnotesAction' =>  'global', 
	// accessRules ConfigurationController
	'acl_usersAction' => 'global',
	'acl_profileAction' => 'global',
	'acl_settingsAction' => 'global',
	'acl_installAction' => 'global',
	'acl_tableeditorAction' => 'global',
	'acl_databaseAction' => 'global',
	// installation contributed services
	'enable_service_spreadsheet_excel_reader' => 'global',
	'enable_service_documentscontroller' => 'global',
	'enable_service_spreadsheetservice' => 'global',
);

## CATEGORIES ##
// categories are used only by configuration-editor in settingsAction 
// variables with a category are listed automatically in 'settings'
$file_settings['categories'] = array(
		'bgimage' => 'output', 
		'autostart' => 'autostart',
		'change_data_dir' => 'change_data_dir',
		'pdf_options_Logofile' => 'pdf',
		'pdf_options_ImageWidth' => 'pdf',
		'pdf_options_ImageTop' => 'pdf',
		'pdf_options_ImageLeft' => 'pdf',
		'pdf_options_TopMargin' => 'pdf',
		'pdf_options_LeftMargin' => 'pdf',
		'pdf_options_Title' => 'pdf',
		'pdf_options_Subject' => 'pdf',
		'pdf_options_Hint_text' => 'pdf',
		'pdf_options_Footertext_left' => 'pdf',
		'pdf_options_Footertext_right' => 'pdf',
		'orphan_groupname' => 'syncronisation',
		'debug' => 'output',
		'exectimeout' => 'cron',
		'refresh' => 'cron',
		'directory_autorisation' => 'application',
		'enable_autostart' => 'application',
		'create_default_files' => 'application',
		'enable_sql' => 'application',
		'display_sql' => 'application',
		'store_global.sql_querys' => 'application',
		'store_global.table_conf' => 'application',
		'group_amount' => 'syncronisation', 
		'download_details' => 'syncronisation',
		'never_decrease_quota_set_in_cloud' => 'syncronisation',
		'edit_joblist' => 'syncronisation',
		'update_quota_if_distant_empty' => 'syncronisation',
		'use_quota_list' => 'syncronisation',
		'increase_personal_quota_to_group' => 'syncronisation',
		'use_delete_list' => 'syncronisation',
		'checkByDefault_UsersMaybeObsolete' => 'syncronisation',
		'checkByDefault_UsersMissed' => 'syncronisation',
		'checkByDefault_GroupMissed' => 'syncronisation',
		'checkByDefault_GroupMaybeObsolete' => 'syncronisation',
		'delete_apidata_after_export' => 'syncronisation',
		'multiselect_settings_categories' => 'output',
		'maximal_rows_in_forms' => 'output',
		'download_format' => 'output', 
		'download_csv_charset' => 'output', 
		'download_csv_delimiter' => 'output', 
		'download_csv_enclosure' => 'output', 
		'connection_name' => 'connection', 
		'connections' => 'connection', 
		'connection_user' => 'connection', 
		'connection_pass' => 'connection', 
		'connection_prot' => 'connection', 
		'connection_url' => 'connection', 
		'connection_folder' => 'connection',
		'exec_type' => 'cron',
		'exec_reset' => 'cron',
		'exec_action' => 'cron',
		'exec_document_group_filters' => 'cron',
		'pdf_clear_dir_on_start' => 'cron',
		'pdf_share_on_upload' => 'cron',
		'exec_info' => 'cron', 
		'max_logfile_lines' => 'cron',
);

return $file_settings;
?>
