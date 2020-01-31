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

function toggleFunctionRelatedElements( fieldname ) {
	
	var funct_name = document.getElementById( fieldname + '.FUNCTION' ).value;
	
	var inputs = document.getElementsByClassName( "param_of_" + fieldname );
	for (var i = 0; i < inputs.length; i++) {
		if( inputs[i].classList.contains("funct_" + funct_name) ){
			inputs[i].disabled = 0;
			inputs[i].style.display = "inline";
		}else{
			inputs[i].disabled = 1;
			inputs[i].style.display = "none";
		}
	}
	 
}

function toggleSelectrelatedElements() {
	var selected = document.getElementById( 'settings_download_format' ).value;
	if( selected == 'csv'){
			document.getElementById( 'settings_download_csv_charset' ).disabled = 0;
			document.getElementById( 'settings_download_csv_enclosure' ).disabled = 0;
			document.getElementById( 'settings_download_csv_delimiter' ).disabled = 0;
			var inputs = document.getElementsByClassName( "download_format_csv" );
			for (var i = 0; i < inputs.length; i++) { 
				inputs[i].disabled = 0;
				inputs[i].classList.remove("disabled");
				inputs[i].classList.add("visible");
			}
	}else{
			document.getElementById( 'settings_download_csv_charset' ).disabled = 1;
			document.getElementById( 'settings_download_csv_enclosure' ).disabled = 1;
			document.getElementById( 'settings_download_csv_delimiter' ).disabled = 1;
			var inputs = document.getElementsByClassName( "download_format_csv" );
			for (var i = 0; i < inputs.length; i++) { 
				inputs[i].disabled = 1;
				inputs[i].classList.remove("visible");
				inputs[i].classList.add("disabled");
			}
	}
}

function toggleCronrelatedElements() {
	var selected = document.getElementById( 'settings_exec_type' ).value;
	if( selected == 'documents'){
			document.getElementById( 'settings_exec_document_group_filters' ).disabled = 0;
			document.getElementById( 'settings_exec_reset' ).disabled = 0;
			document.getElementById( 'settings_exec_action' ).disabled = 0;
			document.getElementById( 'settings_exec_action' ).disabled = 0;
			document.getElementById( 'settings_pdf_share_on_upload' ).disabled = 0;
			document.getElementById( 'settings_pdf_clear_dir_on_start' ).disabled = 0;
			var inputs = document.getElementsByClassName( "exec_type_documents" );
			for (var i = 0; i < inputs.length; i++) { 
				inputs[i].disabled = 0;
				inputs[i].classList.remove("disabled");
				inputs[i].classList.add("visible");
			}
	}else{
		document.getElementById( 'settings_pdf_share_on_upload' ).disabled = 1;
		document.getElementById( 'settings_pdf_clear_dir_on_start' ).disabled = 1;
		document.getElementById( 'settings_exec_document_group_filters' ).disabled = 1;
		var inputs = document.getElementsByClassName( "exec_type_documents" );
		for (var i = 0; i < inputs.length; i++) { 
			inputs[i].disabled = 1;
			inputs[i].classList.remove("visible");
			inputs[i].classList.add("disabled");
		}
		if( selected == 'export' ){
			document.getElementById( 'settings_exec_reset' ).disabled = 0;
			document.getElementById( 'settings_exec_action' ).disabled = 0;
		}else{
			if( selected == 'import' ){
				document.getElementById( 'settings_exec_reset' ).disabled = 0;
				document.getElementById( 'settings_exec_action' ).disabled = 0;
			}else{
				if( selected == 'reset' ){
					document.getElementById( 'settings_exec_reset' ).disabled = 0;
					document.getElementById( 'settings_exec_action' ).disabled = 1;
						
					}else{
						if( selected == 'none' ){
							document.getElementById( 'settings_exec_reset' ).disabled = 1;
							document.getElementById( 'settings_exec_action' ).disabled = 1;
						}
				}
			}
		}
	}
}
function toggleFormElements( classname , checked ) { 
	if( checked == true ){
		var iEnable = 1;
		var iDisable = 0;
	}else{
		var iEnable = 0;
		var iDisable = 1;
	}
	/* if parent element is selected then set classes with name_1 to enabled + visible */
	var inputs = document.getElementsByClassName( classname + "_" + iEnable );
	for (var i = 0; i < inputs.length; i++) { 
		inputs[i].disabled = 0;
		inputs[i].classList.remove("disabled");
		inputs[i].classList.add("visible");
	}
	/* if parent element is selected then set classes with name_0 to disabled + invisible */
	var inputs = document.getElementsByClassName( classname + "_" + iDisable );
	for (var i = 0; i < inputs.length; i++) {
		inputs[i].disabled = 1;
		inputs[i].classList.remove("visible");
		inputs[i].classList.add("disabled");
	}
	/* run click-methods twice */
	/* run click-method of child-elements of elements with classes  name_1  */
	var inputs = document.getElementsByClassName( classname + "_" + iEnable );
	for (var i = 0; i < inputs.length; i++) { 
		if ( inputs[i].name !== undefined ) {
			if ( inputs[i].type === 'checkbox' ){
				if( inputs[i].disabled === false ){
					var namarr = inputs[i].id.split('_');
					namarr.shift();
					var shortname = namarr.join('_');
					toggleFormElements( shortname , inputs[i].checked );
				}
			}
		}
	}
	/* run click-method of child-elements of elements with classes name_0 */
	var inputs = document.getElementsByClassName( classname + "_" + iDisable );
	for (var i = 0; i < inputs.length; i++) { 
		if ( inputs[i].name !== undefined ) {
			if ( inputs[i].type === 'checkbox' ){
				if( inputs[i].disabled === false ){
					var namarr = inputs[i].id.split('_');
					namarr.shift();
					var shortname = namarr.join('_');
					toggleFormElements( shortname , (inputs[i].checked === 0) );
				}
			}
		}
	}
	
}

function countdown( elementId ){
	var inputs = document.getElementById( elementId );
	setInterval(function(){ inputs.innerHTML = inputs.innerHTML - 1; }, 1000);
}

function checkAll(classname, checktoggle){
	var checkboxes = new Array(); 
	checkboxes = document.getElementsByTagName("input");
	for (var i=0; i<checkboxes.length; i++)  {
		if (checkboxes[i].type == "checkbox" && checkboxes[i].className == classname)   {
			checkboxes[i].checked = checktoggle;
		}
	}
}
