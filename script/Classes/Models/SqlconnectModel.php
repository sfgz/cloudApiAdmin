<?php
namespace Drg\CloudApi\Models;

class SqlconnectModel extends \Drg\CloudApi\modelBase {

	/**
	 * Property tablename
	 *
	 * @var string
	 */
	public $tablename = 'sql_querys';

	/**
	 * Property store_global
	 *
	 * @var boolean
	 */
	Public $store_global = FALSE; // 'false' is default. May be overwritten by settings['store_global.sql_querys'] in __construct()

	/**
	 * Property indexfield
	 *
	 * @var string
	 */
	public $indexfield = 'tablename';

	/**
	 * Property properties
	 *
	 * @var array
	 */
	public $properties = array(
		'tablename' =>	array( 
			'type' => 'string',
			'size' => '12em',
			'placeholder' => 'tablename ##LL:must_be_unique##! *affored',
			'validation' => 'unique,notEmpty',
			'locked' => '1'
		),
		'filename' 	=>	array( 
			'type' => 'string',
			'size' => '12em',
			'options' => array('title'=>'write to this file'),
			'placeholder' => 'filename without suffix',
		),
		'folder'  	=>	array( 
			'type' => 'select' , 
			'options' => array('title'=>'write to this folder'),
			'source' => 'text' , 
			'value' => 'users,quota,delete',
		),
		'sql_query' 	=>	array( 
			'type' => 'textarea',
			'size' => '450px',
			'options' => array( 'rows' => 6 , 'cols'=>40 ),
			'placeholder' => 'SELECT * FROM users',
			'crop' => array( 'length'=>'400' , 'append'=>'...' ),
		),
		'user' 	=>	array( 
			'type' => 'string',
			'size' => '12em',
			'placeholder' => 'username *affored',
			'validation' => 'notEmpty',
		),
		'password' 	=>	array( 
			'type' => 'pass2way',
			'size' => '15em',
			'placeholder' => 'sql password *affored',
			'validation' => 'notEmpty'
		),
		'database' 	=>	array( 
			'type' => 'string',
			'size' => '12.5em',
			'placeholder' => 'database name *affored',
			'validation' => 'notEmpty',
			'authorisation' => '50',
		),
		'host' 	=>	array( 
			'type' => 'string',
			'size' => '12.5em',
			'placeholder' => 'localhost *affored',
			'validation' => 'notEmpty',
			'authorisation' => '50',
		),
		'crontime' 	=>	array( 
			'type' => 'string',
			'size' => '5em',
			'placeholder' => 'eg. * * * * *',
			'validation' => 'cronField',
			'authorisation' => '0',
		),
	);
	
	/**
	 * getDefaultRows
	 * default rows of this Model can be 
	 * overwitten by file Private/Config/sql_querys.php
	 * The filename is given by $this->tablename
	 *
	 * @return array
	 */
	Public function getDefaultRows(){
			return array(
				'default1'=> array(
					'tablename' => 'default_sql1',
					'filename' => 'default_sql',
					'folder' => 'users',
					'user' => 'database_username',
					'password' => 'c01MSkd2SlVZc1NTK3pFRHRUVjdzaGIwUG5UT0NqSkxQOUNhRnM3d3RGST0=',
					'database' => 'database_dbname',
					'host' => 'localhost',
					'sql_query' => 'SELECT users.uid AS key, firstname,lastname,email,"9.5" as quota, groups.name AS grp_1 FROM users INNER JOIN usergroup ON users.uid = usergroup.uid INNER  JOIN groups ON groups.gid = usergroup.gid LIMIT 8;'
				),
			);
	}

	/**
	 * getRowButtons
	 *
	 * @param string $ix
	 * @return  string
	 */
	Public function getRowButtons( $ix ){
		$del = '<input type="submit" name="delete['.$this->tablename.']['.$ix.']" value="##LL:files.delete##" onclick="return window.confirm(\'Index: '.$ix.'\n##LL:files.delete##?\');" class="small" />';
		$execute = '<input type="submit" name="execute['.$this->tablename.']['.$ix.']" value="create csv" class="small" />';
		return '<div style="white-space:nowrap;">'.$execute . '&nbsp;|&nbsp;' . $del . '</div>';
	}

	/**
	 * executeAction
	 *
	 * @param string $key
	 * @return void
	 */
	Public function executeAction( $key = '' ){
	
			$rs = $this->getRecordset( $key );
			
			// disable php error reporting, we have own one
			$this->setErrorOutputState( 0 );
			// try to connect database
			$mysqli = new \mysqli($rs['host'], $rs['user'], $rs['password'], $rs['database']);
			// enable php error reporting
			$this->setErrorOutputState( $this->settings['debug'] );
			
			// if database connection failed then return false an output error
			if( $mysqli->connect_errno > 0 ) {
					$this->debug['databaseAction db-connection'] = 'Unable to connect to database [' . $mysqli->connect_error . ']';
					return false;
			}
			
			// set charset, execute SQL-query and assign queryresult to $result
			$mysqli->set_charset( 'utf-8' );
			if( strpos( ' ' . trim($rs['sql_query']) , 'SELECT' ) != 1 ) {
					return $this->rapport( 0 , 'For security purposes only SELECT-querys are allowed. <br />' . $rs['sql_query'] . ' ' , 'databaseAction sql-query-fobidden' );
			}
			
			$result = $mysqli->query( html_entity_decode($rs['sql_query']) );
			if( !$result ) {
					$this->debug['databaseAction sql-query'] = 'There was an error running the query [' . $mysqli->error . ']';
					return false;
			}
			
			// loop through recordsets resulting from query and store rows as array
			$aResponse = array();
			while($row = $result->fetch_assoc()) $aResponse[] = $row;
			if( !count($aResponse) ) return $this->rapport( 0 , 'Sql call failed: no Data' , 'sql-call');
			
			// transform array to csv-string, translate charset
			$csvService = new \Drg\CloudApi\Services\CsvService($this->settings);
			$rawString = $csvService->arrayToCsvString( $aResponse , $this->settings['sys_csv_delimiter'] , $this->settings['sys_csv_enclosure'] );
 			$encodetString = utf8_encode($rawString);
			
			// detect folder and filename to store the csv-file
			$folder = !empty($rs['folder']) ? $rs['folder'] : 'users';
			$filename = !empty($rs['filename']) ? $rs['filename'] : $rs['tablename'];
			if( $folder == 'quota' && isset($this->settings['table_conf']['group_quota']['force_filename']) ) {
					$filename = $this->settings['table_conf']['group_quota']['force_filename'];
			}elseif( $folder == 'delete' && isset($this->settings['table_conf']['delete_list']['force_filename']) ) {
					$filename = $this->settings['table_conf']['delete_list']['force_filename'];
			}
			$completeFilename = rtrim( $this->settings['dataDir'] , '/' ) . '/local/'. $folder . '/' . basename($filename,'.csv') . '.csv';
			
			// write the file and create a debug message
			file_put_contents( $completeFilename , $encodetString );
			
			$this->debug['databaseAction db-connection'] = number_format( count($aResponse) , 0 , '.' , "'" ) .' ##LL:recordsets.0## ##LL:created## in <a href="?act=dateien&ok['.$filename.']='.$filename.'&dir='.$folder.'">/local/'. $folder . '/' . basename($filename,'.csv') . '.csv'.'</a>' ;
			
			return true ;
	}

	/**
	 * cronAction
	 * action called by cron-daemon. 
	 * it may be called repeatingly 
	 *
	 * @param Drg\CloudApi\cli_boot $cli_boot
	 * @return void
	 */
	Public function cronAction( $cli_boot ){
			if( empty($this->settings['enable_sql']) ) return 'disabled';
			 return parent::cronAction( $cli_boot );
	}

}
?>
