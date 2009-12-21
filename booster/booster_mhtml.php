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

((isset($_GET['dir'])) ? $source = rtrim(preg_replace('/[^a-z0-9,\-_\.\/]/i','',preg_replace('/!.+/i','',$_GET['dir'])),'/') : $source = 'css');

include('booster_inc.php');
$booster = new Booster();
$booster->css_source = $source;
$etag = md5($source.$booster->mhtmltime($source));

if (@$_SERVER['HTTP_IF_NONE_MATCH'] === $etag) 
{
	header('HTTP/1.1 304 Not Modified');
	exit();
}

#header("Cache-Control: max-age=2592000");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: ".gmdate('D, d M Y H:i:s')." GMT");
header("Content-type: text/plain"); 
header("ETag: ".$etag);

echo $booster->mhtml();
?>