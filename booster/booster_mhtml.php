<?php  
/*------------------------------------------------------------------------
* 
* CSS-JS-BOOSTER
* Copyright (C) 2009 Christian "Schepp" Schaefer
* http://twitter.com/derSchepp
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Lesser General Public License as published 
* by the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public License
* along with this program. 
* If not, see <http://www.gnu.org/licenses/lgpl-3.0.txt>
* 
------------------------------------------------------------------------*/

header("Cache-Control: max-age=2592000");
header("Expires: ".gmdate('D, d M Y H:i:s', mktime(date('h') + (24 * 30)))." GMT");
header("Content-type: text/plain"); 

include('booster_inc.php');

((isset($_GET['dir'])) ? $dir = rtrim(preg_replace('/[^a-zA-Z\.\/]/','',$_GET['dir']),'/') : $dir = 'css');
$mhtmlfile = 'booster_cache/'.preg_replace('/[^a-z]/i','',$dir).'_datauri_mhtml_cache.txt';

if(!file_exists($mhtmlfile)) booster_css($dir);
if(file_exists($mhtmlfile)) echo file_get_contents($mhtmlfile);
?>