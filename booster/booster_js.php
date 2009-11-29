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
* GNU Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public License
* along with this program. 
* If not, see <http://www.gnu.org/licenses/lgpl-3.0.txt>
* 
------------------------------------------------------------------------*/

header("Cache-Control: max-age=2592000");
header("Expires: ".gmdate('D, d M Y H:i:s', mktime(date('h') + (24 * 30)))." GMT");
header("Content-type: text/javascript"); 

include('booster_inc.php');

((isset($_GET['dir'])) ? $source = rtrim(preg_replace('/[^a-z0-9,\-_\.\/]/i','',$_GET['dir']),'/') : $source = 'js');
((isset($_GET['totalparts'])) ? $totalparts = intval($_GET['totalparts']) : $totalparts = 1);
((isset($_GET['part'])) ? $part = intval($_GET['part']) : $part = 0);

$booster = new Booster();
$booster->js_source = $source;
$booster->js_totalparts = $totalparts;
$booster->js_part = $part;
echo $booster->js();
?>