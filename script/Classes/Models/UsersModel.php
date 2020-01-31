<?php
namespace Drg\CloudApi\Models;

class UsersModel extends \Drg\CloudApi\modelBase {

	/**
	 * Property tablename
	 *
	 * @var string
	 */
	Public $tablename = 'auth_users';

	/**
	 * Property store_global
	 *
	 * @var boolean
	 */
	Public $store_global = TRUE; // the default is 'false'. But users are globally stored, so they can change Dir

	/**
	 * Property indexfield
	 *
	 * @var string
	 */
	Public $indexfield = 'user';

	/**
	 * Property properties
	 *
	 * @var array
	 */
	public $properties = array(
		'user' 	=>	array( 
			'type' => 'string',
			'size' => '12em',
			'placeholder' => '##LL:must_be_unique##!',
			'validation' => 'unique,notEmpty',
			'locked' => '1',
		),
		'pass' 	=>	array( 
			'type' => 'pass1way',
			'size' => '23em',
			'placeholder' => '##LL:minChar## 5, ##LL:crypted##',
			'validation' => 'minChar_5',
		),
		'group' 	=>	array( 
			'type' => 'string',
			'size' => '2.5em',
			'placeholder' => '1-99',
			'validation' => 'notEmpty,numeric,maxChar_2',
			'locked' => '1',
		)
	);
	
	/**
	 * getDefaultRows
	 * default rows of this Model can be 
	 * overwitten by file Private/Config/auth_users.php
	 * The filename is given by $this->tablename
	 *
	 * @return array
	 */
	Public function getDefaultRows(){
		return array(
			'4dm1n'=> array(
				'user' => 'admin',
				'pass' => '$2y$10$BxvMo17tm.9gGhLb60gf9ulF7SyI4qf1/qN/P2btGf5aYPQu6vQKa',
				'group' => '99'
			),
			'5up3ru53r'=> array(
				'user' => 'superuser',
				'pass' => '$2y$10$45YVoPjXWFolSsAGNE.wpeEahUIJhgiXM.t9jjruzc4YIl6ry4.JG',
				'group' => '3'
			)
		);
	}

}
?>
