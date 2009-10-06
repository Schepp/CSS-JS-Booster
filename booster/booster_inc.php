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

@ini_set('zlib.output_compression',2048);
@ini_set('zlib.output_compression_level',4);

if (
isset($_SERVER['HTTP_ACCEPT_ENCODING']) 
&& substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') 
&& function_exists('ob_gzhandler') 
&& !ini_get('zlib.output_compression')
) @ob_start('ob_gzhandler');
else @ob_start();

$starttime = microtime(TRUE);

include_once('browser_class_inc.php');
include_once('csstidy-1.3/class.csstidy.php');

$booster_cachedir = str_replace('\\','/',dirname(__FILE__)).'/booster_cache';
if(!is_dir($booster_cachedir) && !mkdir($booster_cachedir,0777)) 
{
	echo 'You need to create a directory '.$booster_cachedir.' with CHMOD 0777 rights';
	exit;
}

function booster_getpath($dir = '',$booster_dir = '',$dir_sep = '/')
{
	$booster_dir = explode($dir_sep, $booster_dir);
	$dir = explode($dir_sep, $dir);
	$path = '.';
	$fix = '';
	$diff = 0;
	for($i = -1; ++$i < max(($rC = count($booster_dir)), ($dC = count($dir)));)
	{
		if(isset($booster_dir[$i]) and isset($dir[$i]))
		{
			if($diff)
			{
				$path .= $dir_sep.'..';
				$fix .= $dir_sep.$dir[$i];
				continue;
			}
			if($booster_dir[$i] != $dir[$i])
			{
				$diff = 1;
				$path .= $dir_sep.'..';
				$fix .= $dir_sep.$dir[$i];
				continue;
			}
		}
		elseif(!isset($booster_dir[$i]) and isset($dir[$i]))
		{
			for($j = $i-1; ++$j < $dC;)
			{
				$fix .= $dir_sep.$dir[$j];
			}
			break;
		}
		elseif(isset($booster_dir[$i]) and !isset($dir[$i]))
		{
			for($j = $i-1; ++$j < $rC;)
			{
				$fix = $dir_sep.'..'.$fix;
			}
			break;
		}
	}
	return $path.$fix;
} 

function booster_getfiles($dir = '',$type = '',$recursive = FALSE,$files = array())
{
	$dir = rtrim($dir,'/'); // Remove any trailing slash
	if(is_dir($dir))
	{
		$handle=opendir($dir);
		while(false !== ($file = readdir($handle)))
		{
			if($file[0] != '.')
			{
				if(is_dir($dir.'/'.$file)) 
				{
					if($recursive) $files = booster_getfiles($dir.'/'.$file,$type,$recursive,$files);
				}
				else if(substr($file,strlen($file) - strlen($type), strlen($type)) == $type) 
				{
					array_push($files,$dir.'/'.$file);
				}
			}
		}
		closedir($handle);
		array_multisort($files, SORT_ASC, $files);
	}
	return $files;
}

function booster_getfilestime($dir = '',$type = '',$recursive = FALSE,$filestime = 0)
{
	$dirs = explode(',',$dir);
	reset($dirs);
	for($i=0;$i<sizeof($dirs);$i++)
	{
		$dir = current($dirs);
		$dir = rtrim($dir,'/'); // Remove any trailing slash
		if(is_dir($dir))
		{
			$files = booster_getfiles($dir,$type,$recursive);
			for($i=0;$i<count($files);$i++) 
			{
				if(is_dir($files[$i])) 
				{
					if($recursive) $filestime = booster_getfilestime($files[$i],$type,$recursive,$filestime);
				}
				if(is_file($files[$i])) 
				{
					if(filemtime($files[$i]) > $filestime) $filestime = filemtime($files[$i]);
				}
			}
		}
		next($dirs);
	}
	return $filestime;
}

function booster_getfilescontents($dir = '',$type = '',$recursive = FALSE,$filescontent = '')
{
	$dir = rtrim($dir,'/'); // Remove any trailing slash
	if(is_dir($dir))
	{
		$log = 'Directory '.$dir."\r\n";
		$files = booster_getfiles($dir,$type,$recursive);
		for($i=0;$i<count($files);$i++) 
		{
			$filescontent .= "/* ".$files[$i]." */\r\n".preg_replace('/@import[^;]+?;/ms','',file_get_contents($files[$i]))."\r\n\r\n";
			$log .= $files[$i]."\r\n";
		}
		if(strlen($filescontent)) file_put_contents(str_replace('\\','/',dirname(__FILE__)).'/booster_cache/'.preg_replace('/[^a-z0-9]/i','',$dir).'_'.$type.'_cache.txt',$filescontent);
		file_put_contents(str_replace('\\','/',dirname(__FILE__)).'/booster_cache/'.preg_replace('/[^a-z0-9]/i','',$dir).'_'.$type.'_log.txt',$log);
	}
	return $filescontent;
}

function booster_csstidy($filescontent = '')
{
	$css = new csstidy();
	$css->set_cfg('sort_selectors',false);
	$css->set_cfg('sort_properties',false);
	$css->set_cfg('merge_selectors',0);
	$css->set_cfg('optimise_shorthands',1);
	$css->set_cfg('compress_colors',true);
	$css->set_cfg('compress_font-weight',true);
	$css->set_cfg('lowercase_s',false);
	$css->set_cfg('case_properties',1);
	$css->set_cfg('remove_bslash',false);
	$css->set_cfg('remove_last_;',true);
	$css->set_cfg('discard_invalid_properties',false);
	$css->load_template('high_compression');
	$result = $css->parse($filescontent);
	$filescontent = $css->print->plain();
	return $filescontent;
}

function booster_css_datauri($dir = '',$type = '',$filestime = 0,$filescontent = '')
{
	$b = new browser();
	$browserArray = $b->whatbrowser();

	// If IE < 8 browser
	if($browserArray['browsertype'] == 'MSIE' && floatval($browserArray['version']) < 8)
	{
		$mhtmlarray = array();
		$cachefile = str_replace('\\','/',dirname(__FILE__)).'/booster_cache/'.preg_replace('/[^a-z0-9]/i','',$dir).'_datauri_ie_cache.txt';
		$mhtmlfile = str_replace('\\','/',dirname(__FILE__)).'/booster_cache/'.preg_replace('/[^a-z0-9]/i','',$dir).'_datauri_mhtml_cache.txt';
		
		$referrer_parsed = parse_url(dirname($_SERVER['REQUEST_URI']));
		$mhtmlpath = dirname($referrer_parsed['path']);
		
		if(!file_exists($cachefile) || $filestime > filemtime($cachefile))
		{
		
$mhtmlcontent = '/*
Content-Type: multipart/related; boundary="_ANY_STRING_WILL_DO_AS_A_SEPARATOR"

';

		preg_match_all('/url\((.+\.)(gif|png|jpg)\)/',$filescontent,$treffer,PREG_PATTERN_ORDER);
		for($i=0;$i<count($treffer[0]);$i++)
		{
			$imagefile = str_replace('\\','/',dirname(__FILE__)).'/'.$dir.'/'.$treffer[1][$i].$treffer[2][$i];
			$imagetag = 'img'.$i;
			if(file_exists($imagefile) && filesize($imagefile) < 32000) 
			{
				$filescontent = str_replace($treffer[0][$i],'url(mhtml:http://'.$_SERVER['HTTP_HOST'].$mhtmlpath.'/booster_mhtml.php?dir='.$dir.'&nocache='.$filestime.'!'.$imagetag.')',$filescontent);

if(!isset($mhtmlarray[$imagetag])) 
{
$mhtmlcontent .= '--_ANY_STRING_WILL_DO_AS_A_SEPARATOR
Content-Location:'.$imagetag.'
Content-Transfer-Encoding:base64

'.base64_encode(file_get_contents($imagefile)).'==
';
$mhtmlarray[$imagetag] = 1;
}

				}
				else $filescontent = str_replace($treffer[0][$i],'',$filescontent);
			}

$mhtmlcontent .= '*/

';


			file_put_contents($cachefile,$filescontent);
			chmod($cachefile,0777);
			file_put_contents($mhtmlfile,$mhtmlcontent);
			chmod($mhtmlfile,0777);
		}
		else $filescontent = file_get_contents($cachefile);
	}

	// If data-URI-compatible browser
	else
	{
		$cachefile = str_replace('\\','/',dirname(__FILE__)).'/booster_cache/'.preg_replace('/[^a-z0-9]/i','',$dir).'_datauri_cache.txt';
		if(!file_exists($cachefile) || $filestime > filemtime($cachefile))
		{
			preg_match_all('/url\((.+\.)(gif|png|jpg)\)/',$filescontent,$treffer,PREG_PATTERN_ORDER);
			for($i=0;$i<count($treffer[0]);$i++)
			{
				$imagefile = str_replace('\\','/',dirname(__FILE__)).'/'.$dir.'/'.$treffer[1][$i].$treffer[2][$i];
				if(file_exists($imagefile) && filesize($imagefile) < 32000) $filescontent = str_replace($treffer[0][$i],'url(data:image/'.$treffer[2][$i].';base64,'.base64_encode(file_get_contents($imagefile)).')',$filescontent);
			}
			file_put_contents($cachefile,$filescontent);
			chmod($cachefile,0777);
		}
		else $filescontent = file_get_contents($cachefile);
	}
	return $filescontent;
}

function booster_css_split($filescontent = '',$totalparts = 2,$part = 0)
{
	if($totalparts == 1 || $part == 0) return $filescontent;
	else
	{
		$filescontentlines = explode("\n",$filescontent);
		$filescontentparts = array();
		$i = 0;
		for($j=0;$j<intval($totalparts);$j++)
		{
			$filescontentparts[$j] = '';
			while(strlen($filescontentparts[$j]) < ceil(strlen($filescontent) / $totalparts) && isset($filescontentlines[$i]))
			{
				$filescontentparts[$j] .= $filescontentlines[$i]."\n";
				$i++;
			}
		}
		
		return $filescontentparts[$part - 1];
	}
}

function booster_css($dir = 'css',$totalparts = 2,$part = 0,$recursive = FALSE)
{
	$filescontent = '';
	$type = 'css'; // Set file extension to "css"

	$b = new browser();
	$browserArray = $b->whatbrowser();

	$dirs = explode(',',$dir);
	reset($dirs);
	for($i=0;$i<sizeof($dirs);$i++)
	{
		$dir = current($dirs);
		$dir = rtrim($dir,'/'); // Remove any trailing slash
		if(is_dir($dir))
		{
			$filestime = booster_getfilestime($dir,$type,$recursive);
			// If IE < 8 browser
			if($browserArray['browsertype'] == 'MSIE' && floatval($browserArray['version']) < 8) $cachefile = str_replace('\\','/',dirname(__FILE__)).'/booster_cache/'.preg_replace('/[^a-z0-9]/i','',$dir).'_datauri_ie_cache.txt';
			// If data-URI-compatible browser
			else $cachefile = str_replace('\\','/',dirname(__FILE__)).'/booster_cache/'.preg_replace('/[^a-z0-9]/i','',$dir).'_datauri_cache.txt';
			
			if(file_exists($cachefile) && filemtime($cachefile) >= $filestime) $filescontent .= file_get_contents($cachefile);
			else
			{
				$currentfilescontent = booster_getfilescontents($dir,$type,$recursive);
				$currentfilescontent = booster_csstidy($currentfilescontent);
				$filescontent .= booster_css_datauri($dir,$type,$filestime,$currentfilescontent);
			}
			$filescontent .= "\n";
		}
		next($dirs);
	}
	$filescontent = booster_css_split($filescontent,$totalparts,$part);
	return $filescontent;
}

function booster_css_markup($dir = 'css',$media = 'all',$rel='stylesheet',$title='Standard',$totalparts = 2,$markuptype = 'XHTML')
{
	$markup = '';
	$booster_path = booster_getpath(str_replace('\\','/',dirname(__FILE__)),dirname($_SERVER['SCRIPT_FILENAME']));
	$css_path = booster_getpath(dirname($_SERVER['SCRIPT_FILENAME']),str_replace('\\','/',dirname(__FILE__)));
	
	$dirs = explode(',',$dir);
	reset($dirs);
	for($i=0;$i<sizeof($dirs);$i++) 
	{
		$dirs[key($dirs)] = $css_path.'/'.current($dirs);
		next($dirs);
	}
	$dir = implode(',',$dirs);

	for($j=0;$j<intval($totalparts);$j++)
	{
		$markup .= '<link rel="'.$rel.'" media="'.$media.'" title="'.htmlentities($title,ENT_QUOTES).'" type="text/css" href="'.$booster_path.'/booster_css.php?dir='.htmlentities($dir,ENT_QUOTES).'&amp;totalparts='.intval($totalparts).'&amp;part='.($j+1).'&amp;nocache='.booster_getfilestime($dir,'css').'" '.(($markuptype == 'XHTML') ? '/' : '').'>'."\r\n";
	}
	return $markup;
}

//////////////////////////////////////////////

function booster_js_split($filestime = 0,$filescontent = '',$totalparts = 1,$part = 0)
{
	if($totalparts == 1 || $part == 0) return $filescontent;
	else
	{
		$filescontentlines = explode(";\n",$filescontent);
		$filescontentparts = array();
		$i = 0;
		for($j=0;$j<intval($totalparts);$j++)
		{
			$filescontentparts[$j] = '';
			while(strlen($filescontentparts[$j]) < ceil(strlen($filescontent) / $totalparts) && isset($filescontentlines[$i]))
			{
				$filescontentparts[$j] .= $filescontentlines[$i]."\n";
				$i++;
			}
		}
		
		return $filescontentparts[$part - 1];
	}
}

function booster_js($dir = 'js',$totalparts = 1,$part = 0,$recursive = FALSE)
{
	$filescontent = '';
	$type = 'js'; // Set file extension to "js"

	$b = new browser();
	$browserArray = $b->whatbrowser();

	$dirs = explode(',',$dir);
	reset($dirs);
	for($i=0;$i<sizeof($dirs);$i++)
	{
		$dir = current($dirs);
		$dir = rtrim($dir,'/'); // Remove any trailing slash
		if(is_dir($dir))
		{
			$filestime = booster_getfilestime($dir,$type,$recursive);
			$cachefile = str_replace('\\','/',str_replace('\\','/',dirname(__FILE__))).'/booster_cache/'.preg_replace('/[^a-z0-9]/i','',$dir).'_'.$type.'_cache.txt';
			
			if(file_exists($cachefile) && filemtime($cachefile) >= $filestime) $filescontent .= file_get_contents($cachefile);
			else $filescontent .= booster_getfilescontents($dir,$type,$recursive);

			$filescontent .= "\n";
		}
		next($dirs);
	}
	if($filescontent != '') $filescontent = booster_js_split($filestime,$filescontent,$totalparts,$part);
	return $filescontent;
}

function booster_js_markup($dir = 'js',$totalparts = 1)
{
	$markup = '';
	$booster_path = booster_getpath(str_replace('\\','/',dirname(__FILE__)),dirname($_SERVER['SCRIPT_FILENAME']));
	$js_path = booster_getpath(dirname($_SERVER['SCRIPT_FILENAME']),str_replace('\\','/',dirname(__FILE__)));
	
	$dirs = explode(',',$dir);
	reset($dirs);
	for($i=0;$i<sizeof($dirs);$i++) 
	{
		$dirs[key($dirs)] = $js_path.'/'.current($dirs);
		next($dirs);
	}
	$dir = implode(',',$dirs);

	for($j=0;$j<intval($totalparts);$j++)
	{
		$markup .= '<script type="text/javascript" src="'.$booster_path.'/booster_js.php?dir='.htmlentities($dir,ENT_QUOTES).'&amp;totalparts='.intval($totalparts).'&amp;part='.($j+1).'&amp;nocache='.booster_getfilestime($dir,'js').'"></script>'."\r\n";
	}
	return $markup;
}
?>