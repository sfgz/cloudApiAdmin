<?php

/************************************/
/*                                  */
/*  If a file named                 */
/*                                  */
/*  sql_querys.json                 */
/*                                  */
/*  is in the directory             */
/*                                  */
/*  script/Private/Cofig/own        */
/*                                  */
/*  then the that file gets readen  */
/*  and this php - file is obsolete */
/*                                  */
/************************************/

return array(
	'my_example'=> array(
        'tablename' => 'my_example_sql1',
        'filename' => 'example_mysql',
        'folder' => 'users',
        'user' => 'database-username',
        'password' => 'database-password encrypted like aFNEOmt1ZVBibGV4dyQxdG07p0sxZz09',
        'database' => 'database-name',
        'host' => 'database-host',
        'sql_query' => "SELECT users.uid AS id, firstname,lastname,email,'9.5' as quota, 
        case when groups.name='kurier' then 'kurier' else '' end AS grp_1, 
        case when groups.name='kunde' then 'kunde' else '' end AS grp_2, 
        case when groups.name='user' then 'user' else '' end AS grp_3, 
        case when groups.name='kunde' or groups.name='kurier'  or groups.name='user' then '' else groups.name end AS grp_4 
        FROM users INNER JOIN usergroup ON users.uid = usergroup.uid INNER JOIN groups ON groups.gid = usergroup.gid LIMIT 18;"
	),
);

?>
