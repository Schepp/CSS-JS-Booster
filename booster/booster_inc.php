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

class Booster {

	public $css_source = 'css';
	public $css_media = 'all';
	public $css_rel= 'stylesheet';
	public $css_title= 'Standard';
	public $css_totalparts = 2;
	public $css_markuptype = 'XHTML';
	public $css_part = 0;
	public $css_recursive = FALSE;

	public $js_source = 'js';
	public $js_totalparts = 1;
	public $js_part = 0;
	public $js_recursive = FALSE;

	public $browserArray;
    
    function __construct()
    {
		$b = new browser();
        $this->browserArray = $b->whatbrowser();
    }

	protected function getpath($source = '',$booster_dir = '',$source_sep = '/')
	{
		$booster_dir = explode($source_sep, $booster_dir);
		$source = explode($source_sep, $source);
		$path = '.';
		$fix = '';
		$diff = 0;
		for($i = -1; ++$i < max(($rC = count($booster_dir)), ($dC = count($source)));)
		{
			if(isset($booster_dir[$i]) and isset($source[$i]))
			{
				if($diff)
				{
					$path .= $source_sep.'..';
					$fix .= $source_sep.$source[$i];
					continue;
				}
				if($booster_dir[$i] != $source[$i])
				{
					$diff = 1;
					$path .= $source_sep.'..';
					$fix .= $source_sep.$source[$i];
					continue;
				}
			}
			elseif(!isset($booster_dir[$i]) and isset($source[$i]))
			{
				for($j = $i-1; ++$j < $dC;)
				{
					$fix .= $source_sep.$source[$j];
				}
				break;
			}
			elseif(isset($booster_dir[$i]) and !isset($source[$i]))
			{
				for($j = $i-1; ++$j < $rC;)
				{
					$fix = $source_sep.'..'.$fix;
				}
				break;
			}
		}
		return $path.$fix;
	} 

	protected function getfiles($source = '',$type = '',$recursive = FALSE,$files = array())
	{
		$source = rtrim($source,'/'); // Remove any trailing slash
		if(is_dir($source))
		{
			$handle=opendir($source);
			while(false !== ($file = readdir($handle)))
			{
				if($file[0] != '.')
				{
					if(is_dir($source.'/'.$file)) 
					{
						if($recursive) $files = $this->getfiles($source.'/'.$file,$type,$recursive,$files);
					}
					else if(substr($file,strlen($file) - strlen($type), strlen($type)) == $type) 
					{
						array_push($files,$source.'/'.$file);
					}
				}
			}
			closedir($handle);
			array_multisort($files, SORT_ASC, $files);
		}
		elseif(is_file($source) && substr($source,strlen($source) - strlen($type), strlen($type)) == $type) array_push($files,$source);
		return $files;
	}

	protected function getfilestime($source = '',$type = '',$recursive = FALSE,$filestime = 0)
	{
		if(is_array($source)) $source = $source;
		else $sources = explode(',',$source);

		reset($sources);
		for($i=0;$i<sizeof($sources);$i++)
		{
			$source = current($sources);
			$source = rtrim($source,'/'); // Remove any trailing slash
			
			if(is_dir($source))
			{
				$files = $this->getfiles($source,$type,$recursive);
				for($i=0;$i<count($files);$i++) 
				{
					if(is_dir($files[$i])) 
					{
						if($recursive) $filestime = $this->getfilestime($files[$i],$type,$recursive,$filestime);
					}
					if(is_file($files[$i])) 
					{
						if(filemtime($files[$i]) > $filestime) $filestime = filemtime($files[$i]);
					}
				}
			}
			elseif(is_file($source) && filemtime($source) > $filestime) $filestime = filemtime($source);
			next($sources);
		}
		return $filestime;
	}

	protected function getfilescontents($source = '',$type = '',$recursive = FALSE,$filescontent = '')
	{
		$source = rtrim($source,'/'); // Remove any trailing slash
		$log = 'Source '.$source."\r\n";
		if(is_dir($source))
		{
			$files = $this->getfiles($source,$type,$recursive);
			for($i=0;$i<count($files);$i++) 
			{
				$filescontent .= "/* ".$files[$i]." */\r\n".preg_replace('/@import[^;]+?;/ms','',file_get_contents($files[$i]))."\r\n\r\n";
				$log .= $files[$i]."\r\n";
			}
		}
		elseif(is_file($source))
		{
			$filescontent .= "/* ".$source." */\r\n".preg_replace('/@import[^;]+?;/ms','',file_get_contents($source))."\r\n\r\n";
			$log .= $source."\r\n";
		}

		if(strlen($filescontent)) file_put_contents(str_replace('\\','/',dirname(__FILE__)).'/booster_cache/'.preg_replace('/[^a-z0-9,\-_]/i','',$source).'_'.$type.'_cache.txt',$filescontent);
		file_put_contents(str_replace('\\','/',dirname(__FILE__)).'/booster_cache/'.preg_replace('/[^a-z0-9,\-_]/i','',$source).'_'.$type.'_log.txt',$log);

		return $filescontent;
	}

	protected function csstidy($filescontent = '')
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
	
	protected function css_datauri($filestime = 0,$filescontent = '')
	{
		// If IE < 8 browser and not on Vista or higher
		if($this->browserArray['browsertype'] == 'MSIE' && floatval($this->browserArray['version']) < 8 && floatval($this->browserArray['ntversion']) < 6)
		{
			$mhtmlarray = array();
			$cachefile = str_replace('\\','/',dirname(__FILE__)).'/booster_cache/'.preg_replace('/[^a-z0-9,\-_]/i','',$this->css_source).'_datauri_ie_cache.txt';
			$mhtmlfile = str_replace('\\','/',dirname(__FILE__)).'/booster_cache/'.preg_replace('/[^a-z0-9,\-_]/i','',$this->css_source).'_datauri_mhtml_cache.txt';
			
			$referrer_parsed = parse_url(dirname($_SERVER['REQUEST_URI']));
			$mhtmlpath = dirname($referrer_parsed['path']);
			
			if(!file_exists($cachefile) || $filestime > filemtime($cachefile))
			{
			
$mhtmlcontent = '/*
Content-Type: multipart/related; boundary="_ANY_STRING_WILL_DO_AS_A_SEPARATOR"

';
	
			preg_match_all('/^([^\{\}]+?\{[^\{\}]+?\:[^\{\}]*?)url\((.+?\.)(gif|png|jpg)\)/ms',$filescontent,$treffer,PREG_PATTERN_ORDER);
			for($i=0;$i<count($treffer[0]);$i++)
			{
				if(is_dir($this->css_source)) $dir = $this->css_source;
				elseif(is_file($this->css_source)) $dir = dirname($this->css_source);
				
				$imagefile = str_replace('\\','/',dirname(__FILE__)).'/'.$dir.'/'.$treffer[2][$i].$treffer[3][$i];
				$imagetag = 'img'.$i;
				if(file_exists($imagefile) && filesize($imagefile) < 24000) 
				{
					$filescontent = str_replace($treffer[0][$i],$treffer[1][$i].'url(mhtml:http://'.$_SERVER['HTTP_HOST'].$mhtmlpath.'/booster_mhtml.php?dir='.$this->css_source.'&nocache='.$filestime.'!'.$imagetag.')',$filescontent);

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
	
		// If IE 7 browser on Vista or higher (like IETester under Windows 7 for example), do not change anything (IE7 on Win 6+ Bug)
		elseif($this->browserArray['browsertype'] == 'MSIE' && floatval($this->browserArray['version']) < 8 && floatval($this->browserArray['ntversion']) >= 6) $filescontent = $filescontent;

		// If any other and then data-URI-compatible browser
		else
		{
			$cachefile = str_replace('\\','/',dirname(__FILE__)).'/booster_cache/'.preg_replace('/[^a-z0-9,\-_]/i','',$this->css_source).'_datauri_cache.txt';
			if(!file_exists($cachefile) || $filestime > filemtime($cachefile))
			{
				preg_match_all('/url\((.+\.)(gif|png|jpg)\)/',$filescontent,$treffer,PREG_PATTERN_ORDER);
				for($i=0;$i<count($treffer[0]);$i++)
				{
					if(is_dir($this->css_source)) $dir = $this->css_source;
					elseif(is_file($this->css_source)) $dir = dirname($this->css_source);
					
					$imagefile = str_replace('\\','/',dirname(__FILE__)).'/'.$dir.'/'.$treffer[1][$i].$treffer[2][$i];
					if(file_exists($imagefile) && filesize($imagefile) < 24000) $filescontent = str_replace($treffer[0][$i],'url(data:image/'.$treffer[2][$i].';base64,'.base64_encode(file_get_contents($imagefile)).')',$filescontent);
				}
				file_put_contents($cachefile,$filescontent);
				chmod($cachefile,0777);
			}
			else $filescontent = file_get_contents($cachefile);
		}
		return $filescontent;
	}

	protected function css_split($filescontent = '')
	{
		if($this->css_totalparts == 1 || $this->css_part == 0) return $filescontent;
		else
		{
			$filescontentlines = explode("\n",$filescontent);
			$filescontentparts = array();
			$i = 0;
			for($j=0;$j<intval($this->css_totalparts);$j++)
			{
				$filescontentparts[$j] = '';
				while(strlen($filescontentparts[$j]) < ceil(strlen($filescontent) / $this->css_totalparts) && isset($filescontentlines[$i]))
				{
					$filescontentparts[$j] .= $filescontentlines[$i]."\n";
					$i++;
				}
			}
			return $filescontentparts[$this->css_part - 1];
		}
	}
		
	public function css()
	{
		$filescontent = '';
		$type = 'css'; // Set file extension to "css"
	
		if(is_array($this->css_source)) $sources = $this->css_source;
		else $sources = explode(',',$this->css_source);
		
		reset($sources);
		for($i=0;$i<sizeof($sources);$i++)
		{
			$source = current($sources);
			$source = rtrim($source,'/'); // Remove any trailing slash
			if(is_dir($source) || is_file($source))
			{
				$filestime = $this->getfilestime($source,$type,$this->css_recursive);
				// If IE < 8 browser and not on Vista or higher
				if($this->browserArray['browsertype'] == 'MSIE' && floatval($this->browserArray['version']) < 8 && floatval($this->browserArray['ntversion']) < 6) $cachefile = str_replace('\\','/',dirname(__FILE__)).'/booster_cache/'.preg_replace('/[^a-z0-9,\-_]/i','',$source).'_datauri_ie_cache.txt';
				// If IE 7 browser on Vista or higher (like IETester under Windows 7 for example), skip cache
				elseif($this->browserArray['browsertype'] == 'MSIE' && floatval($this->browserArray['version']) < 8 && floatval($this->browserArray['ntversion']) >= 6) $cachefile = '';
				// If any other and then data-URI-compatible browser
				else $cachefile = str_replace('\\','/',dirname(__FILE__)).'/booster_cache/'.preg_replace('/[^a-z0-9,\-_]/i','',$source).'_datauri_cache.txt';
				
				if($cachefile != '' && file_exists($cachefile) && filemtime($cachefile) >= $filestime) $filescontent .= file_get_contents($cachefile);
				else
				{
					$currentfilescontent = $this->getfilescontents($source,$type,$recursive);
					$currentfilescontent = $this->csstidy($currentfilescontent);
					$filescontent .= $this->css_datauri($filestime,$currentfilescontent);
				}
				$filescontent .= "\n";
			}
			next($sources);
		}
		$filescontent = $this->css_split($filescontent);
		return $filescontent;
	}
		
	public function mhtml()
	{
		$mhtmlfile = 'booster_cache/'.preg_replace('/[^a-z0-9,\-_]/i','',$this->css_source).'_datauri_mhtml_cache.txt';
		if(!file_exists($mhtmlfile)) $this->css();
		if(file_exists($mhtmlfile)) return file_get_contents($mhtmlfile);
		else return '';
	}
	
	public function css_markup()
	{
		$markup = '';
		$booster_path = $this->getpath(str_replace('\\','/',dirname(__FILE__)),dirname($_SERVER['SCRIPT_FILENAME']));
		$css_path = $this->getpath(dirname($_SERVER['SCRIPT_FILENAME']),str_replace('\\','/',dirname(__FILE__)));
		
		if(is_array($this->css_source)) $sources = $this->css_source;
		else $sources = explode(',',$this->css_source);

		$timestamp_dirs = array();
		reset($sources);
		for($i=0;$i<sizeof($sources);$i++) 
		{
			$sources[key($sources)] = $css_path.'/'.current($sources);
			array_push($timestamp_dirs,$booster_path.'/'.current($sources));
			next($sources);
		}
		$source = implode(',',$sources);
		$timestamp_dir = implode(',',$timestamp_dirs);

		// IE6 fix image flicker
		if($this->browserArray['browsertype'] == 'MSIE' && floatval($this->browserArray['version']) < 7) $markup .= '<script type="text/javascript">try {document.execCommand("BackgroundImageCache", false, true);} catch(err) {}</script>'."\r\n";
	
		for($j=0;$j<intval($this->css_totalparts);$j++)
		{
			$markup .= '<link rel="'.$this->css_rel.'" media="'.$this->css_media.'" title="'.htmlentities($this->css_title,ENT_QUOTES).'" type="text/css" href="'.$booster_path.'/booster_css.php?dir='.htmlentities($source,ENT_QUOTES).'&amp;totalparts='.intval($this->css_totalparts).'&amp;part='.($j+1).'&amp;nocache='.$this->getfilestime($timestamp_dir,'css').'" '.(($this->css_markuptype == 'XHTML') ? '/' : '').'>'."\r\n";
		}
	
		return $markup;
	}
	
	//////////////////////////////////////////////
	
	protected function js_split($filestime = 0,$filescontent = '',$totalparts = 1,$part = 0)
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
	
	public function js()
	{
		$filescontent = '';
		$type = 'js'; // Set file extension to "js"
		
		if(is_array($this->js_source)) $sources = $this->js_source;
		else $sources = explode(',',$this->js_source);

		reset($sources);
		for($i=0;$i<sizeof($sources);$i++)
		{
			$source = current($sources);
			$source = rtrim($source,'/'); // Remove any trailing slash
			if(is_dir($source) || is_file($source))
			{
				$filestime = $this->getfilestime($source,$type,$this->js_recursive);
				$cachefile = str_replace('\\','/',str_replace('\\','/',dirname(__FILE__))).'/booster_cache/'.preg_replace('/[^a-z0-9,\-_]/i','',$source).'_'.$type.'_cache.txt';
				
				if(file_exists($cachefile) && filemtime($cachefile) >= $filestime) $filescontent .= file_get_contents($cachefile);
				else $filescontent .= $this->getfilescontents($source,$type,$recursive);
	
				$filescontent .= "\n";
			}
			next($sources);
		}
		if($filescontent != '') $filescontent = $this->js_split($filestime,$filescontent,$this->js_totalparts,$this->js_part);
		return $filescontent;
	}

	public function js_markup()
	{
		$markup = '';
		$booster_path = $this->getpath(str_replace('\\','/',dirname(__FILE__)),dirname($_SERVER['SCRIPT_FILENAME']));
		$js_path = $this->getpath(dirname($_SERVER['SCRIPT_FILENAME']),str_replace('\\','/',dirname(__FILE__)));
		
		if(is_array($this->js_source)) $sources = $this->js_source;
		else $sources = explode(',',$this->js_source);
		
		$timestamp_dirs = array();
		reset($sources);
		for($i=0;$i<sizeof($sources);$i++) 
		{
			$sources[key($sources)] = $js_path.'/'.current($sources);
			array_push($timestamp_dirs,$booster_path.'/'.current($sources));
			next($sources);
		}
		$source = implode(',',$sources);
		$timestamp_dir = implode(',',$timestamp_dirs);
	
		for($j=0;$j<intval($this->js_totalparts);$j++)
		{
			$markup .= '<script type="text/javascript" src="'.$booster_path.'/booster_js.php?dir='.htmlentities($source,ENT_QUOTES).'&amp;totalparts='.intval($this->js_totalparts).'&amp;part='.($j+1).'&amp;nocache='.$this->getfilestime($timestamp_dir,'js').'"></script>'."\r\n";
		}
		return $markup;
	}
}
?>