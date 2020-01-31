<?php
namespace Drg\CloudApi\Services;

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

class XmlService {

    /**
    * Convert XML to an Array
    *
    * @param string  $XML
    * @return array
    */
    function XMLtoArray($XML) {
		$xml_parser = xml_parser_create();
		xml_parse_into_struct($xml_parser, $XML, $vals);
		xml_parser_free($xml_parser);
		$_tmp='';
		foreach ($vals as $xml_elem) {
				$x_tag=$xml_elem['tag'];
				$x_level=$xml_elem['level'];
				$x_type=$xml_elem['type'];
				if ($x_level!=1 && $x_type == 'close') {
				if (isset($multi_key[$x_tag][$x_level]))
					$multi_key[$x_tag][$x_level]=1;
				else
					$multi_key[$x_tag][$x_level]=0;
				}
				if ($x_level!=1 && $x_type == 'complete') {
				if ($_tmp==$x_tag)
					$multi_key[$x_tag][$x_level]=1;
				$_tmp=$x_tag;
				}
		}
		// jedziemy po tablicy
		foreach ($vals as $xml_elem) {
				$x_tag=$xml_elem['tag'];
				$x_level=$xml_elem['level'];
				$x_type=$xml_elem['type'];
				if ($x_type == 'open')
				$level[$x_level] = $x_tag;
				$start_level = 1;
				$php_stmt = '$xml_array';
				if ($x_type=='close' && $x_level!=1)
				$multi_key[$x_tag][$x_level]++;
				while ($start_level < $x_level) {
					$php_stmt .= '[$level['.$start_level.']]';
					if (isset($multi_key[$level[$start_level]][$start_level]) && $multi_key[$level[$start_level]][$start_level])
						$php_stmt .= '['.($multi_key[$level[$start_level]][$start_level]-1).']';
					$start_level++;
				}
				$add='';
				if (isset($multi_key[$x_tag][$x_level]) && $multi_key[$x_tag][$x_level] && ($x_type=='open' || $x_type=='complete')) {
					if (!isset($multi_key2[$x_tag][$x_level]))
						$multi_key2[$x_tag][$x_level]=0;
					else
						$multi_key2[$x_tag][$x_level]++;
					$add='['.$multi_key2[$x_tag][$x_level].']';
				}
				if (isset($xml_elem['value']) && trim($xml_elem['value'])!='' && !array_key_exists('attributes', $xml_elem)) {
					if ($x_type == 'open')
						$php_stmt_main=$php_stmt.'[$x_type]'.$add.'[\'content\'] = $xml_elem[\'value\'];';
					else
						$php_stmt_main=$php_stmt.'[$x_tag]'.$add.' = $xml_elem[\'value\'];';
					eval($php_stmt_main);
				}
				if (array_key_exists('attributes', $xml_elem)) {
					if (isset($xml_elem['value'])) {
						$php_stmt_main=$php_stmt.'[$x_tag]'.$add.'[\'content\'] = $xml_elem[\'value\'];';
						eval($php_stmt_main);
					}
					foreach ($xml_elem['attributes'] as $key=>$value) {
						$php_stmt_att=$php_stmt.'[$x_tag]'.$add.'[$key] = $value;';
						eval($php_stmt_att);
					}
				}
		}
		return isset($xml_array) ? $xml_array : '';
    }


}

?>
