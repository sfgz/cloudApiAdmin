<?php
namespace Drg\CloudApi\Models;

class NotesModel extends \Drg\CloudApi\modelBase {

	/**
	 * Property tablename
	 *
	 * @var string
	 */
	Public $tablename = 'notes';

	/**
	 * Property store_global
	 *
	 * @var boolean
	 */
	Public $store_global = TRUE; // the default is 'false'. But users are globally stored, otherwise they could not change Dir

	/**
	 * Property indexfield
	 *
	 * @var string
	 */
	Public $indexfield = 'uid';

	/**
	 * Property properties
	 *
	 * @var array
	 */
	public $properties = array(
		'uid' 	=>	array(
			'type' => 'string',
			'size' => '1em',
			'placeholder' => '##LL:must_be_unique##!',
			'validation' => 'unique,notEmpty,iterate',
			'locked' => '1'
		),
		'lang' 	=>	array( 
			'type' => 'select' , 
			'options' => array('title'=>'language'),
			'source' => 'array' , 
			'value' => array( 'en' , 'de' ),
			'default' => 'de',
		),
		'key' 	=>	array( 
			'type' => 'string',
			'size' => '8em',
			'placeholder' => 'notEmpty',
			'validation' => 'notEmpty',
		),
		'title' 	=>	array( 
			'type' => 'string',
			'size' => '18em',
			'placeholder' => 'notEmpty',
			'validation' => 'notEmpty',
		),
		'body' 	=>	array( 
			'type' => 'textarea',
			'size' => '850px',
			'options' => array( 'rows' => 16 , 'cols'=>80 ),
			'crop' => array( 'length'=>'150' , 'append'=>'...' ),
			'placeholder' => '',
		),
		'sort' 	=>	array( 
			'type' => 'string',
			'size' => '2em',
			'placeholder' => '',
			'validation' => 'iterate',
		),
		'display' 	=>	array( 
			'type' => 'select' , 
			'source' => 'viewhelper',
			'proc_name' => 'getAclForActiveUser',
			'proc_options' => 'authUserGroup',
			'default' => '1',
		),
	);

	/**
	 * getPrependButtons
	 *
	 * @return  string
	 */
	Public function getPrependButtons( ){
		return '';
// 		return '<input type="submit" name="ok[save]" value="##LL:save##" title="##LL:save##" />';
	}
	
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
'introduction-de'=> array(
		'uid' => '11',
		'key' => 'introduction',
		'lang' => 'de',
		'display' => '0',
		'sort' => '10',
		'title' => 'Was ist CloudApiAdmin?',
		'body' => "CloudApiAdmin besteht aus einem kleinen Webframework welches Dienste zur Ausf&uuml;hrung von Skripten wie beispielsweise DB- oder API- Abfragen bereitstellt. 

==== &Uuml;bersichtlich ====
Die Applikation besteht aus 4 Programmteilen (Aktionen).
- In **Dateien** werden hochgeladene oder über SQL-Befehl erstellte CSV-Tabellen miteinander kombiniert. 
- In **Clouduser** werden alle in der Cloud vorhandenen Benutzer und Gruppen abgefragt und mit den lokalen CSV-Dateien abgeglichen.
- In **Export** wird der Export zur Cloud gestartet und überwacht.
- In **Cloudgruppen** werden gruppenweise PDF-Dateien erstellt, zur Cloud hochgeladen und (optional) mit den Gruppenmitgliedern geteilt.


====Ressourcen schonend====
Die Dauer der Cloud-Abrufe und der Pausen zwischen den Wiederholung werden vom Anwender festgelegt. Dadurch wird stark belasteten Cloudservern Zeit zur Erholung einger&auml;umt. Durch den Einsatz eines Cron-D&auml;mons kann die Datenpflege auf eine Tageszeit mit wenig Serverlast verlegt werden.

====Flexible Konfiguration====
* Die hochgeladenen CSV (oder XLS, XLSX, ODS) -Dateien k&ouml;nnen unterschiedliche Spezifikationen aufweisen und miteinander kombiniert und ver&auml;ndert werden. 
* Quotas k&ouml;nnen der Gruppenzugeh&ouml;rigkeit entsprechend &uuml;ber eine Quota-Tabelle zugewiesen werden.
* Das l&ouml;schen von Benutzern kann entweder &uuml;ber eine L&ouml;sch-CSV-Datei (Option 'use_delete_list') oder durch Abgleichung erfolgen (Fehlende werden gel&ouml;scht).
* Eine Anwendung kann mehrere Cloud-Accounts verwalten.
* Der Zugriff von Benutzern auf einzelne Cloud-Accounts, Programmteile und Funktionen wird Gruppenweise mittels ACL gesteuert.
* Die Anzeige erfolgt je nach Spracheinstellung des Browsers in Deutsch oder Englisch.

==== Erweiterbar ====

Das Microframework wurde zur Ressourcen schonenden Pflege der Benutzerdaten von Nextcloud entwickelt, kann aber beliebig abge&auml;ndert oder erweitert werden. 
Dienste von anderen Autoren (FPDF, Spout f&uuml;r XLSX / ODS, PHP-ExcelReader f&uuml;r XLS) k&ouml;nnen abgeschaltet oder gel&ouml;scht werden. (Konfiguration &rarr; Installation)"
),

'requirements-de'=> array(
		'uid' => '12',
		'key' => 'requirements',
		'lang' => 'de',
		'display' => '0',
		'sort' => '20',
		'title' => 'Anforderungen',
		'body' => "* Serverumgebung mit PHP ab 5.5
* keine Datenbank erforderlich.
* Unter Apache die Verwendung von .htaccess einschalten oder einen anderen Schutz vor Einsicht anwenden (siehe unter Installation).
* PHP-Module: FPDF ben&ouml;tigt Zlib und GD f&uuml;r den GIF support siehe [[ http://www.fpdf.org | www.fpdf.org ]]
* Getestet an Nexcloud 11.0x und 12.04, PHP 5.6.33 und 7.0, MySQL 5.6.38"
),

'installation-de'  => array(
		'uid' => '13',
		'key' => 'installation',
		'lang' => 'de',
		'display' => '0',
		'sort' => '30',
		'title' => "Installation" , 
		'body' => "==== Dateien kopieren und einloggen ====
Den Ordner 'cloudApiAdmin' oder dessen Inhalt zum Webserver kopieren (in den Webspace).

===Startdateien===
Wenn die Datei **index.php** verschoben wird, muss darin der Pfad zum Scripte-Ordner (SCR_DIR) und zum Daten-Ordner (DATA_DIR) angepasst werden. Die Index-Datei kann noch weitere ver&auml;nderbare Optionen enthalten, sie kann auch umbenannt werden.
**db2.php** ist eine Kopie von index.php mit anderem Datenordner und abge&auml;nderten Parametern.

Wenn der daten Ordner verschoben wird, muss auch der Pfad zum Daten-Ordner (DATA_DIR) in der Datei script/Classes/Commands/**cli_dispatch.phpsh** angepasst werden.

=== Rechte &uuml;berpr&uuml;fen (nur f&uuml;r Linux Server)===
* Alle Ordner m&uuml;ssen durch den __Webserver lesbar__ sein.
* Der Ordner 'data' muss durch den __Webserver beschreibbar__ sein. Eine .htaccess Datei sch&uuml;tzt vor Lesezugriff &uuml;ber Browser.
* Die Ordner ./script und ./script/Public (und alle Dateien unterhalb von Public) m&uuml;ssen f&uuml;r __jedermann lesbar__ sein.
* Um Cron-D&auml;mon und Kommandozeile zu erm&ouml;glichen versucht das installscript die Datei .../script/Classes/Commands/cli_dispatch.phpsh f&uuml;r den Webserver und den __Crond&auml;mon ausf&uuml;hrbar__ zu machen.

===Login===
Der vordefinierte **Benutzername** lautet 'admin', das Passwort 'admin1'. Bitte im Menu Konfiguration unter 'Benutzer' &auml;ndern.

====Konfiguration====
* Alle Konfigurations-Dateien befinden sich im Ordner script/Private/Config. 
* Im Menu 'Konfiguration' vorgenomme &Auml;nderungen werden in separaten json-Dateien im Daten-Verzeichnis (global) oder einem dort untergeordneten Verzeichnis (lokal) gespeichert.
* Um die eigenen Einstellungen als Voreinstellungen zu speichern einfach die gew&uuml;nschten json-Dateien ab Datenordner in den Ordner Private/Config/own/ kopieren.
* Passworte werden zur Sicherheit immer verschl&uuml;sselt gespeichert. Die Variable 'cryptedPasswordInDefaultTables' auf '0' setzen, wenn in den Voreinstellungen unverschl&uuml;sselte Passworte eingesetzt werden sollen.
* Weitere Voreinstellungen befinden sich in den Models (NotesModel, SqlconnectModel und UsersModel). Durch Ableiten der Klasse modelBase werden sehr einfach zus&auml;tzliche Tabellen und Funktionen erstellt. 
* In der Klasse TableFunctionsUtility k&ouml;nnen eigene Skripte erfasst werden."
),

'releasenotes-de'  => array(
		'uid' => '14',
		'key' => 'releasenotes',
		'lang' => 'de',
		'display' => '0',
		'sort' => '40',
		'title' => "Release Notes" , 
		'body' => "+ __Vers. Date       Notes__
+ 3.033 05.01.2020 favicon added.
+ 3.032 24.12.2019 Bugfix obdsolete OK Button on lists without checkboxes.
+ 3.031 21.12.2019 Bugfix delete_list was broken.
+ 3.030 19.12.2019 Download Compare-Lists as Table. Pager in ActionController: handling combined with submit-button.
+ 3.029 15.12.2019 Cronjob 1. delayed jobs are called up to 4 minutes before. 2. Wrong cron-time returns a message.
+ 3.028 08.12.2019 Help-Text correctures.
+ 3.027 08.08.2019 Bugfix in ActionsController. Deleting cache was only possible in debug-mode.
+ 3.026 04.07.2019 Fixes for PHP 7, count on null produces errors in PHP7.
+ 3.025 02.07.2019 Bugfix in ReadCloudUtility: The xml file from Nextcloud Version 15 contains no fieldname if a field is empty. 
+ 3.024 01.06.2018 Bugfix: Wrong uploadfolder when working with else than csv (xlsx,ods).
+ 3.023 10.05.2018 Bugfix: users had doubled group-rows.
+ 3.022 23.02.2018 edit upload list displays now beside amount also checked items in update list. Name of service listed in ^^Install^^
+ 3.020 20.02.2018 Display and download calculated differences - for information or backup purpose. Bugfixes ...3.021
+ 3.019 18.02.2018 now rename files, delete BgImg-Folder possible. Re-edited Note defaults. Moved row_accounts to category 'syncronisation'.
+ 3.018 14.02.2018 storing with json, no more serialize. Re-edited notes.
+ 3.017 13.02.2018 Notes can be edited with own Texteditor in wiki-syntax, json-files can be used as default files.
+ 3.012 03.02.2018 installer tries to make cli_dispatch.phpsh executable on linux-server. ...3.016
+ 3.011 02.02.2018 added directory_autorisation for folders, connection error handling
+ 3.010 28.01.2018 Major settings changes. Moved orphan-handling from global to local. Changed behavior of autostarter.
+ 3.001 22.01.2018 new design, added AccesControlSystem, redesigned core, bugfixes in toolbar, autostarter, model. ...3.003
+ 2.001 31.12.2017 create tables from sql-querys; share documents and edit pdf-settings in settings-mask ...2.012
+ 1.000 01.11.2017 first published release ...1.208
+ 0.001 20.08.2017 first release ...0.037"
),

'cron-daemon-de'   => array(
		'uid' => '15',
		'key' => 'cron',
		'lang' => 'de',
		'display' => '1',
		'sort' => '50',
		'title' => "Cron-D&auml;mon &amp; Kommandozeile" , 
		'body' => "Der Cron-D&auml;mon soll angewiesen werden, die ausf&uuml;hrbare Scriptdatei cli_dispatch.phpsh ohne Parameter jede Minute einmal auszuf&uuml;hren.
Die eigentliche Ausf&uuml;hrungszeit wird in der Konfiguration der Applikation festgelegt (settings['exec_reset'] und settings['exec_action']).

Beispiel eines Cron-Eintrages ohne Parameter:
+ * * * * * [Pfad-zur-Anwendung]/cloudApiAdmin/script/Classes/Commands/cli_dispatch.phpsh

Beispiel eines Cron-Eintrages ohne Parameter und ohne Email- Benachrichtigung:
+ * * * * * [Pfad-zur-Anwendung]/cloudApiAdmin/script/Classes/Commands/cli_dispatch.phpsh >/dev/null 2>&1

Weitere Hinweise zu m&ouml;glichen Parametern der ausf&uuml;hrbaren Datei cli_dispatch.phpsh befinden sich in der Datei selber.

**Wichtig:** (nur f&uuml;r Linux Server) &Uuml;berpr&uuml;fen ob erfolgt: Um Cron-D&auml;mon und Kommandozeile zu erm&ouml;glichen versucht das installscript die Datei .../script/Classes/Commands/cli_dispatch.phpsh f&uuml;r den Crond&auml;mon ausf&uuml;hrbar zu machen."
),

'scripts-de'   => array(
		'uid' => '16',
		'key' => 'scripts',
		'lang' => 'de',
		'display' => '1',
		'sort' => '60',
		'title' => "Skript-Dateien" , 
		'body' => "==== Dateien ====
Der Ordner 'script' enth&auml;lt folgende Dateien:
+ __Classes__
+  23 scripts in Core, ViewHelpers and Services
+  16 scripts in Controller, Utility and Models (can easy be customized)
+   1 script in Command (executable for cron + cli-user) 
+   1 script in Contributed/fpdf + 14 font-files
+   1 script in Contributed/excel_reader
+ 101 files in Contributed/Spout (xlsx + ods)
+ __Private__
+   2 static config files (default settings)
+   2 language files (en,de)
+   8 temlate files 
+ __Public__
+   3 background image files (readable by anyone, may be changed or deleted) 
+   1 css file (readable by anyone) 
+   5 icons image files (readable by anyone)
+   1 php image-file (allows display image files in private folders)
+   2 js files (readable by anyone)

====Speicherplatz Verbrauch====
725 KiB verwendet durch eigene Skripte und Templates (zum Betrieb n&ouml;tige Dateien) 
333 KiB verwendet durch 3 Hintergrundbilder (Optional, Inhalt des Ordners BgImg kann gel&ouml;scht oder ge&auml;ndert werden)
__654 KiB__ verwendet durch Skripts anderer Autoren in Contributed (Optional, kann ausgeschaltet oder gel&ouml;scht werden)
1.7 MiB total (Kann auf 725 KiB reduziert werden)

==== PHP-Klassen, Konfigurationsdateien und Templates ====

===SCRIPTS in folder Classes/===

==CORE (10)==

+ core
+ core/bootup_settings
+ core/boot
^^boot^^ startet controller -> view -> html

+ core/boot/cli_boot
^^cli_boot^^ wird ab Kommandozeile oder Cron durch Aufruf der Datei ^^cli_dispatch.phpsh^^ gestartet und f&uuml;hrt Befehle in der Klasse ^^CliTasksUtility^^ aus.

+ core/controllerBase
+ core/modelBase( settings )
+ core/tablefunctionsBase( settings )

+ viewBase( settings! )
+ viewBase/obj( settings! )
+ viewBase/view( settings! ) 
^^view^^ L&auml;dt alle ViewHelpers ihrem Namen entsprechend als ^^view->Models^^, ^^view->Objects^^, ^^view->JavaScript^^.


==VIEWHELPER (3)==

+ viewBase/obj/ModelsViewHelper( settings! )
+ viewBase/obj/ObjectsViewHelper( settings! )
+ viewBase/obj/JavaScriptViewHelper( settings! )

==SERVICES (10)==
Services erweitern keine anderen Klassen, sie k&ouml;nnen als Standalone-Klassen gestartet werden

+ AuthService( settings! )
+ ConnectorService( settings! )
+ CryptService( array clear_key! )
+ CsvService
+ FileHandlerService( settings! )
+ InstallerService( settings! )
+ PdfService
+ SpreadsheetService
+ SyntaxService
+ XmlService

==CONTROLLER (4)==

+ core/controllerBase/ActionsController
+ core/controllerBase/ConfigurationController
+ core/controllerBase/DocumentsController
+ core/controllerBase/NotesController

==UTILITY (9)==

+ core/TransformTablesUtility
+ core/controllerBase/DataUtility
+ core/controllerBase/DataUtility/ReadCloudUtility
+ core/controllerBase/DataUtility/CreateJobsUtility
+ core/controllerBase/DataUtility/CreateJobsUtility/JobsEditorUtility
+ core/tablefunctionsBase/TableFunctionsUtility
+ core/controllerBase/DataUtility/UpdateCloudUtility
+ core/controllerBase/CliTasksUtility
+ core/controllerBase/DataUtility/GroupDocsUtility 
GroupDocsUtility Ben&ouml;tigt FPDF vom Ordner Contributed

==MODELS (3)==

+ core/modelBase/NotesModel
+ core/modelBase/SqlconnectModel
+ core/modelBase/UsersModel

==COMMANDS (1)==

+ cli_dispatch.phpsh 
Startet cli_boot, muss ausf&uuml;hrbar sein!

==CONTRIBUTED (3 Anwendungen)==

+ fpdf.php
+ fonts ordner f&uuml;r fpdf
+ Spout
+ excel_reader27.php

==== Ordner Private/====
==CONFIG im Ordner Private/Config/==

Voreinstellungen settings:
+ Private/Config/settings.php
+ Private/Config/table_conf.php

Eigene Voreinstellungen f&uuml;r Settings oder Models einsetzen:
Json-Dateien ab Datenordner in den Ordner Private/Config/own/ kopieren.


==LANGUAGE im Ordner Private/Language/==


+ Private/Language/labels.de.php
+ Private/Language/labels.en.php

== HTML-TEMPLATES in Ordnern Private/Layouts/, Private/Partials/ und Private/View/ ==

+ Private/Layouts/general.html
+ Private/Partials/ActionsToolbar.html
+ Private/Partials/ConfigurationToolbar.html
+ Private/Partials/DocumentsToolbar.html
+ Private/Partials/NotesToolbar.html
+ Private/View/default.html
+ Private/View/viewnotes.html
+ Private/View/welcome.html

Im Ordner Private/Partials/ muss sich pro Controller ein template f&uuml;r die Toolbar befinden, der Name kann in der Variable fallbackPartial ge&auml;ndert werden. Voreinstellung ist der Controllername.
Wenn eine Datei in Private/View/ mit einer Aktion im Actioncontroller &uuml;bereinstimmt, wird dieses Template zum rendern genommen."
),

'introduction-en'  => array(
		'uid' => '21',
		'key' => 'introduction',
		'lang' => 'en',
		'display' => '0',
		'sort' => '10',
		'title' => "What is CloudApiAdmin?" , 
		'body' => "CloudApiAdmin comes with a tiny webframework leaned on MVC-framworks - it can execute scripts like SQL- or API requests.

==== Handy ====
The application comes in 4 program parts (actions).
- The part **Files** handles uploaded CSV-files or creates it from sql-statements and combines them for optimized export.
- The part **Clouduser** requests the cloud for all existing users and groups to detect differences between cloud and local CSV-files.
- The part **Export** starts and controls exports to cloud.
- The part **Cloudgroups** finally creates, uploads and shares pdf-files with group-members.

==== Resource friendly ====
The executiontime and pause until the next call is defined by application-operator. Heavily used servers may release during pauses. Calling the script by cron is practically for api-calls during periods with less traffic.

==== Flexible Configuration ====
* Uploaded csv-files  (or XLS, XLSX, ODS) with variably specifications can be combined. 
* Quotas can be assigned in relation to group-membership over a quota-table.
* User deletion can be done over a deletion-list (option 'use_delete_list') or by exclusion.
* A single Application can handle severeal cloud-Accounts. 
* Users access to specific cloud-Account, variable or function ist controled by ACL.
* Current languages are german and english, the output depends on browsers setting.

==== Extendable ====
 
CloudApiAdmin was developped to provide ressources-friendly user provisioning for owncloud or nextcloud installations. But it is easy to modify or extend it.
Services from other authors (FPDF, Spout for XLSX / ODS, PHP-ExcelReader for XLS) can be desabled or deleted. (Configuration &rarr; Installation)"
),

'requirements-en'  => array(
		'uid' => '22',
		'key' => 'requirements',
		'lang' => 'en',
		'display' => '0',
		'sort' => '20',
		'title' => "Requirements" , 
		'body' => "* Server environment with PHP >= 5.5
* No Database affored
* Enable the use of .htaccess if you run apache, or activate some other read-protection by yourself (see installation).
* PHP-Modules: FPDF requires Zlib to enable compression and GD for GIF support, see [[ http://www.fpdf.org | www.fpdf.org ]]
* Tested on Nextcloud 11.0x and 12.04, PHP 5.6.33 and 7.0, MySQL 5.6.38"
),

'installation-en'  => array(
		'uid' => '23',
		'key' => 'installation',
		'lang' => 'en',
		'display' => '0',
		'sort' => '30',
		'title' => "Installation" , 
		'body' => "==== Copy files and login ====
Copy the folder 'cloudApiAdmin' or all contents to the Webspace.

===Startfiles===
The file **index.php** contains the path to the script- (SCR_DIR) and the data-directory (DATA_DIR). Optionally it may contain some more settings. If the file get moved, the pathnames in the file itself must be changed aswell.
**db2.php** is a copy of index.php with different data-folder and parameters.

If you change the path to the 'data' directory, you must also change the data-directory (DATA_DIR) in file **cli_dispatch.phpsh**. 

=== Proove Rights  (only for Linux servers)===
* All directories must be __readable by the webserver__.
* The folder named 'data' must be __writable by webserver__. It gets protected from reading by browsers with a .htaccess file.
* The folders ./script and ./script/Public (and all files underneath Public)  must be __readable for public__ (all).
* For usage of cron and cli the boot-script tries to make the file ./cloudApiAdmin/script/Classes/Commands/cli_dispatch.phpsh __executable by cron__ & webserver.

===Login===
Predefined  ** username **  ist 'admin' with password 'admin1'. Please change it after login by visiting the 'users' page (in configuration).

==== Configuration ====
* All configuration-files are located at script/Private/Config/
* Changed options where stored in separate files located in the data-folders root (global) or in one of its subfolders (local)
* Passwords are stored always crypted. Set the variable 'cryptedPasswordInDefaultTables' to '0' if you want to use clear password in your default settings.
* More default values are stored in the Models (NotesModel, SqlconnectModel und UsersModel). By extending the class modelBase it is easy to add new tables and functions.
* The class TableFunctionsUtility can be extended with own scripts."
),

'releasenotes-en'  => array(
		'uid' => '24',
		'key' => 'releasenotes',
		'lang' => 'en',
		'display' => '0',
		'sort' => '40',
		'title' => "Release Notes" , 
		'body' => "+ __Vers. Date       Notes__
+ 3.033 05.01.2020 favicon added.
+ 3.032 24.12.2019 Bugfix obdsolete OK Button on lists without checkboxes.
+ 3.031 21.12.2019 Bugfix delete_list was broken.
+ 3.030 19.12.2019 Download Compare-Lists as Table. Pager in ActionController: handling combined with submit-button.
+ 3.029 15.12.2019 Cronjob 1. delayed jobs are called up to 4 minutes before. 2. Wrong cron-time returns a message.
+ 3.028 08.12.2019 Help-Text correctures.
+ 3.027 08.08.2019 Debug in ActionsController. Deleting cache was only possible in debug-mode.
+ 3.026 04.07.2019 Fixes for PHP 7, count on null produces errors in PHP7.
+ 3.025 02.07.2019 Bugfix in ReadCloudUtility: The xml file from Nextcloud Version 15 contains no fieldname if a field is empty. 
+ 3.024 01.06.2018 Bugfix: Wrong uploadfolder when working with else than csv (xlsx,ods).
+ 3.023 10.05.2018 Bugfix: users had doubled group-rows.
+ 3.022 23.02.2018 edit upload list displays now beside amount also checked items in update list. Name of service listed in ^^Install^^
+ 3.020 20.02.2018 Display and download calculated differences - for information or backup purpose. Bugfixes ...3.021
+ 3.019 18.02.2018 now rename files, delete BgImg-Folder possible. Re-edited Note defaults. Moved row_accounts to category 'syncronisation'.
+ 3.018 14.02.2018 storing with json, no more serialize. Re-edited notes.
+ 3.017 13.02.2018 Notes can be edited with own Texteditor in wiki-syntax, json-files can be used as default files.
+ 3.012 03.02.2018 installer tries to make cli_dispatch.phpsh executable on linux-server. ...3.016
+ 3.011 02.02.2018 added directory_autorisation for folders, connection error handling
+ 3.010 28.01.2018 Major settings changes. Moved orphan-handling from global to local. Changed behavior of autostarter.
+ 3.001 22.01.2018 new design, added AccesControlSystem, redesigned core, bugfixes in toolbar, autostarter, model. ...3.003
+ 2.001 31.12.2017 create tables from sql-querys; share documents and edit pdf-settings in settings-mask ...2.012
+ 1.000 01.11.2017 first published release ...1.208
+ 0.001 20.08.2017 first release ...0.037"
),

'cron-daemon-en'   => array(
		'uid' => '25',
		'key' => 'cron',
		'lang' => 'en',
		'display' => '1',
		'sort' => '50',
		'title' => "Cron-daemon & command line" , 
		'body' => "For Cron-Usage call the commmand cli_dispatch.phpsh without options every minute.
The real execution time gets configurated in the applications configuration-page.

<br />
Example of a cron call without parameters:
<pre> * * * * * [path-to-application]/cloudApiAdmin/script/Classes/Commands/cli_dispatch.phpsh</pre>
Example of a cron call without parameters and withot email-notifications: <pre> * * * * * [path-to-application]/cloudApiAdmin/script/Classes/Commands/cli_dispatch.phpsh >/dev/null 2>&1</pre>

<br />
See documentation in the file cli_dispatch.phpsh for further informations on how to run the command.

** Important **  (only for Linux servers) For usage of cron and cli the file .../cloudApiAdmin/script/Classes/Commands/cli_dispatch.phpsh must be executable by webserver. The boot-script tries to do that for you, please prove whether it was successful."
),
	
'scripts-en'   => array(
		'uid' => '26',
		'key' => 'scripts',
		'lang' => 'en',
		'display' => '1',
		'sort' => '60',
		'title' => "script-files" , 
		'body' => "==== Files ====
The directory 'script' contains following php-files: 
+ __Classes__
+  23 scripts in Core, ViewHelpers and Services
+  16 scripts in Controller, Utility and Models (can easy be customized)
+   1 script in Command (executable for cron + cli-user) 
+   1 script in Contributed/fpdf + 14 font-files
+   1 script in Contributed/excel_reader
+ 101 files in Contributed/Spout (xlsx + ods)
+ __Private__
+   2 static config files (default settings)
+   2 language files (en,de)
+   8 temlate files 
+ __Public__
+   3 background image files (readable by anyone, may be changed or deleted) 
+   1 css file (readable by anyone) 
+   5 icons image files (readable by anyone)
+   1 php image-file (allows display image files in private folders)
+   2 js files (readable by anyone)

====Diskspace usage====
725 KiB used by own scripts and templates (minimal affored files) 
333 KiB used by Background images (optional, content of folder BgImg can be deleted)
__654 KiB__ by scripts in Contributed (optional, content can be disabled or deleted)
1.7 MiB total (can be reduced to 725 KiB)

==== PHP-Classes, config files and templates ====

===SCRIPTS in folder Classes/===

==CORE (10)==

+ core
+ core/bootup_settings
+ core/boot
^^boot^^ starts controller -> view -> html

+ core/boot/cli_boot
^^cli_boot^^ get started from commandline or cron by calling the file ^^cli_dispatch.phpsh^^. The script executes commmands from class ^^CliTasksUtility^^.

+ core/controllerBase
+ core/modelBase( settings! )
+ core/tablefunctionsBase( settings! )

+ viewBase( settings! )
+ viewBase/obj( settings! )
+ viewBase/view( settings! ) 
^^view^^ Loads all found viewhelpers as ^^view->Models^^, ^^view->Objects^^, ^^view->JavaScript^^ (depending on their names)

==VIEWHELPER (3)==

+ viewBase/obj/ModelsViewHelper( settings! )
+ viewBase/obj/ObjectsViewHelper( settings! )
+ viewBase/obj/JavaScriptViewHelper( settings! )

==SERVICES (10)==
Services dont extend any class, so they can be used as standalone classes

+ AuthService( settings! )
+ ConnectorService( settings! )
+ CryptService( array clear_key! )
+ CsvService
+ FileHandlerService( settings! )
+ InstallerService( settings! )
+ PdfService
+ SpreadsheetService
+ SyntaxService
+ XmlService

==CONTROLLER (4)==

+ core/controllerBase/ActionsController
+ core/controllerBase/ConfigurationController
+ core/controllerBase/DocumentsController
+ core/controllerBase/NotesController

==UTILITY (9)==

+ core/TransformTablesUtility
+ core/controllerBase/DataUtility
+ core/controllerBase/DataUtility/ReadCloudUtility
+ core/controllerBase/DataUtility/CreateJobsUtility
+ core/controllerBase/DataUtility/CreateJobsUtility/JobsEditorUtility
+ core/tablefunctionsBase/TableFunctionsUtility
+ core/controllerBase/DataUtility/UpdateCloudUtility
+ core/controllerBase/CliTasksUtility
+ core/controllerBase/DataUtility/GroupDocsUtility 
GroupDocsUtility needs class FPDF from folder contributed.

==MODELS (3)==

+ core/modelBase/NotesModel
+ core/modelBase/SqlconnectModel
+ core/modelBase/UsersModel

==COMMANDS (1)==

+ cli_dispatch.phpsh 
Starts cli_boot, must be executable!

==CONTRIBUTED (3 Applications)==

+ fpdf.php
+ fonts folder for fpdf
+ Spout
+ excel_reader27.php

==== Ordner Private/====
==CONFIG in folder Private/Config/==

default settings:
+ Private/Config/settings.php
+ Private/Config/table_conf.php

Add your own default settings or default model-tables:
Drop files from data-folder to Private/Config/own/



==LANGUAGE in folder Private/Language/==


+ Private/Language/labels.de.php
+ Private/Language/labels.en.php

== HTML-TEMPLATES in folders Private/Layouts/, Private/Partials/ and Private/View/ ==

+ Private/Layouts/general.html
+ Private/Partials/ActionsToolbar.html
+ Private/Partials/ConfigurationToolbar.html
+ Private/Partials/DocumentsToolbar.html
+ Private/Partials/NotesToolbar.html
+ Private/View/default.html
+ Private/View/viewnotes.html
+ Private/View/welcome.html

If a file in Private/View/ matches with an action, this file is taken to render.
Each controller needs a corresponding toolbar-template in Private/Partials/ unless there is a other template registered in the controllers variable ^^fallbackPartial^^ "
),

);
	}

}
?>
