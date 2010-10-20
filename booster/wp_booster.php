<?php
/*
Plugin Name: CSS-JS-Booster
Plugin URI: http://github.com/Schepp/CSS-JS-Booster
Description: automates performance optimizing steps related to CSS, Media and Javascript linking/embedding.
Version: 0.6.2.179
Author: Christian "Schepp" Schaefer
Author URI: http://twitter.com/derSchepp
*/

/*  Copyright 2010  Christian Schepp Schaefer  (email : schaepp@gmx.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
include('booster_inc.php'); 

// Pre-2.6 compatibility
if(!defined('WP_CONTENT_URL')) define('WP_CONTENT_URL',get_option('siteurl').'/wp-content');
if(!defined('WP_CONTENT_DIR')) define('WP_CONTENT_DIR',ABSPATH.'wp-content');
if(!defined('WP_PLUGIN_URL')) define('WP_PLUGIN_URL',WP_CONTENT_URL.'/plugins');
if(!defined('WP_PLUGIN_DIR')) define('WP_PLUGIN_DIR',WP_CONTENT_DIR.'/plugins');

// Set Booster Cache Folder
define('BOOSTER_CACHE_DIR',str_replace('\\','/',dirname(__FILE__)).'/../../booster_cache');

function booster_htaccess() {
	$wp_htacessfile = get_home_path().'.htaccess';
	$booster_htacessfile = rtrim(str_replace('\\','/',realpath(dirname(__FILE__))),'/').'/htaccess/.htaccess';
	if(file_exists($booster_htacessfile))
	{
		if(file_exists($wp_htacessfile) && is_writable($wp_htacessfile))
		{
			$wp_htacessfile_contents = file_get_contents($wp_htacessfile);
			$wp_htacessfile_contents = preg_replace('/#CSS-JS-Booster Start#################################################.*#CSS-JS-Booster End#################################################/ims','',$wp_htacessfile_contents);
			$wp_htacessfile_contents = $wp_htacessfile_contents.file_get_contents($booster_htacessfile);
		}
		else $wp_htacessfile_contents = file_get_contents($booster_htacessfile);
		@file_put_contents($wp_htacessfile,$wp_htacessfile_contents);
	}
	@mkdir(BOOSTER_CACHE_DIR,0777);
	@chmod(BOOSTER_CACHE_DIR,0777);
}
register_activation_hook(__FILE__,'booster_htaccess');



function booster_cleanup() {
	// Remove entries from .htaccess
	$wp_htacessfile = get_home_path().'.htaccess';
	if(file_exists($wp_htacessfile) && is_writable($wp_htacessfile))
	{
		$wp_htacessfile_contents = file_get_contents($wp_htacessfile);
		$wp_htacessfile_contents = preg_replace('/#CSS-JS-Booster Start#################################################.*#CSS-JS-Booster End#################################################/ims','',$wp_htacessfile_contents);
		@file_put_contents($wp_htacessfile,$wp_htacessfile_contents);
	}
	
	// Remove all cache files
	$handle=opendir(BOOSTER_CACHE_DIR);
	while(false !== ($file = readdir($handle)))
	{
		if($file[0] != '.' && is_file(BOOSTER_CACHE_DIR.'/'.$file)) unlink(BOOSTER_CACHE_DIR.'/'.$file);
	}
	closedir($handle);
}
register_deactivation_hook(__FILE__,'booster_cleanup');



function booster_wp() {
	// Dump output buffer
	if($out = ob_get_contents())
	{
		// Check for right PHP version
		if(strnatcmp(phpversion(),'5.0.0') >= 0)
		{ 
			$booster_cache_dir = BOOSTER_CACHE_DIR;
			$js_plain = '';
			$booster_out = '';
			$booster_folder = explode('/',rtrim(str_replace('\\','/',realpath(dirname(__FILE__))),'/'));
			$booster_folder = $booster_folder[count($booster_folder) - 1];
			$booster = new Booster();
			if(!is_dir($booster_cache_dir)) 
			{
				@mkdir($booster_cache_dir,0777);
				@chmod($booster_cache_dir,0777);
			}
			if(is_dir($booster_cache_dir) && is_writable($booster_cache_dir) && substr(decoct(fileperms($booster_cache_dir)),1) == "0777")
			{
				$booster_cache_reldir = $booster->getpath(str_replace('\\','/',realpath($booster_cache_dir)),str_replace('\\','/',dirname(__FILE__)));
			}
			else 
			{
				$booster_cache_dir = rtrim(str_replace('\\','/',dirname(__FILE__)),'/').'/../../booster_cache';
				$booster_cache_reldir = '../../booster_cache';
			}
			$booster->booster_cachedir = $booster_cache_reldir;
			$booster->js_minify = FALSE;
	
			// Calculate relative path from root to Booster directory
			$root_to_booster_path = $booster->getpath(str_replace('\\','/',dirname(__FILE__)),str_replace('\\','/',dirname(realpath(ABSPATH))));
			
			if(preg_match_all('/<head.*<\/head>/ims',$out,$headtreffer,PREG_PATTERN_ORDER) > 0)
			{
				// Prevent processing of (conditional) comments
				$headtreffer[0][0] = preg_replace('/<!--.+?-->/ims','',$headtreffer[0][0]);
				
				// Detect charset
				if(preg_match('/<meta http-equiv="Content-Type" content="text\/html; charset=(.+?)" \/>/',$headtreffer[0][0],$charset))
				{
					$headtreffer[0][0] = str_replace($charset[1],'',$headtreffer[0][0]);
					$charset = $charset[1];
				}
				else $charset = '';
				
				// CSS part
				$css_rel_files = array();
				
				// Start width inline-files
				preg_match_all('/<style[^>]*>(.*?)<\/style>/ims',$headtreffer[0][0],$treffer,PREG_PATTERN_ORDER);
				for($i=0;$i<count($treffer[0]);$i++) 
				{
					// Get media-type
					if(preg_match('/media=[\'"]*([^\'"]+)[\'"]*/ims',$treffer[0][$i],$mediatreffer)) 
					{
						$media = preg_replace('/[^a-z]+/i','',$mediatreffer[1]);
						if(trim($media) == '') $media = 'all';
					}
					else $media = 'all';

					// Save plain CSS to file to keep everything in line
					$filename = $booster_cache_dir.'/'.md5($treffer[1][$i]).'_plain.css';
					if(!file_exists($filename)) file_put_contents($filename,$treffer[1][$i]);
					if(file_exists($filename))
					{
						@chmod($filename,0777);
			
						// Calculate relative path from Booster to file
						$booster_to_file_path = $booster->getpath($booster_cache_dir.'/',str_replace('\\','/',dirname(realpath(__FILE__))).'/');
						$linkhref = get_option('siteurl').'/wp-content/plugins/'.$booster_folder.'/'.$booster_to_file_path.'/'.basename($filename);
	
						$booster_cache_dir = $booster_cache_dir;
						$linkcode = '<!-- Moved to file by Booster '.$treffer[0][$i].' --><link rel="stylesheet" media="'.$media.'" href="'.$linkhref.'" />';
						$headtreffer[0][0] = str_replace($treffer[0][$i],$linkcode,$headtreffer[0][0]);
						$out = str_replace($treffer[0][$i],$linkcode,$out);
					}
					else
					{
						$linkcode = '<!-- Failed to move inline-style to file '.$filename.' by Booster -->'.$treffer[0][$i];
						$headtreffer[0][0] = str_replace($treffer[0][$i],$linkcode,$headtreffer[0][0]);
						$out = str_replace($treffer[0][$i],$linkcode,$out);
					}
				}

				// Continue with external files
				preg_match_all('/<link[^>]*?href=[\'"]*?([^\'"]+?\.css)[\'"]*?[^>]*?>/ims',$headtreffer[0][0],$treffer,PREG_PATTERN_ORDER);
				for($i=0;$i<count($treffer[0]);$i++) 
				{
					// Get media-type
					if(preg_match('/media=[\'"]*([^\'"]+)[\'"]*/ims',$treffer[0][$i],$mediatreffer)) 
					{
						$media = preg_replace('/[^a-z]+/i','',$mediatreffer[1]);
						if(trim($media) == '') $media = 'all';
					}
					else $media = 'all';
	
					// Get relation
					if(preg_match('/rel=[\'"]*([^\'"]+)[\'"]*/ims',$treffer[0][$i],$reltreffer)) $rel = $reltreffer[1];
					else $rel = 'stylesheet';
	
					// Convert file's URI into an absolute local path
					$filename = preg_replace('/^http:\/\/[^\/]+/',rtrim($_SERVER['DOCUMENT_ROOT'],'/'),$treffer[1][$i]);
					// Remove any parameters from file's URI
					$filename = preg_replace('/\?.*$/','',$filename);
					// If file exists
					if(file_exists($filename))
					{
						// If its a normal CSS-file
						if(substr($filename,strlen($filename) - 4,4) == '.css' && file_exists($filename))
						{
							// Put file-reference inside a comment
							$out = str_replace($treffer[0][$i],'<!-- Processed by Booster '.$treffer[0][$i].' -->',$out);
		
							// Calculate relative path from Booster to file
							$booster_to_file_path = $booster->getpath(str_replace('\\','/',dirname($filename)),str_replace('\\','/',dirname(__FILE__)));
							$filename = $booster_to_file_path.'/'.basename($filename);
			
							// Create sub-arrays if not yet there
							if(!isset($css_rel_files[$media])) $css_rel_files[$media] = array();
							if(!isset($css_abs_files[$media])) $css_abs_files[$media] = array();
							if(!isset($css_rel_files[$media][$rel])) $css_rel_files[$media][$rel] = array();
							if(!isset($css_abs_files[$media][$rel])) $css_abs_files[$media][$rel] = array();
							
							// Enqueue file to respective array
							array_push($css_rel_files[$media][$rel],$filename);
							array_push($css_abs_files[$media][$rel],rtrim(str_replace('\\','/',dirname(realpath(ABSPATH))),'/').'/'.$root_to_booster_path.'/'.$filename);
						}
						else $out = str_replace($treffer[0][$i],$treffer[0][$i].'<!-- Booster skipped '.$filename.' -->',$out);
					}
					// Leave untouched but put calculated local file name into a comment for debugging
					else $out = str_replace($treffer[0][$i],$treffer[0][$i].'<!-- Booster had a problems finding '.$filename.' -->',$out);
				}
	
				// Creating Booster markup for each media and relation seperately
				$links = '';
				reset($css_rel_files);
				for($i=0;$i<count($css_rel_files);$i++) 
				{
					$media_rel = $css_rel_files[key($css_rel_files)];
					$media_abs = $css_abs_files[key($css_rel_files)];
					reset($media_rel);
					for($j=0;$j<count($media_rel);$j++) 
					{
						$booster->getfilestime($media_rel[key($media_rel)],'css');

						$media_rel[key($media_rel)] = implode(',',$media_rel[key($media_rel)]);
						$media_abs[key($media_rel)] = implode(',',$media_abs[key($media_rel)]);
						$link = '<link type="text/css" rel="'.key($media_rel).
						'" media="'.key($css_rel_files).
						'" href="'.get_option('siteurl').'/wp-content/plugins/'.
						$booster_folder.
						'/booster_css.php'.
						($booster->mod_rewrite ? '/' : '?').
						'dir='.htmlentities(str_replace('..','%3E',$media_rel[key($media_rel)])).
						'&amp;cachedir='.htmlentities(str_replace('..','%3E',$booster_cache_reldir),ENT_QUOTES).
						($booster->debug ? '&amp;debug=1' : '').
						($booster->librarydebug ? '&amp;librarydebug=1' : '').
						'&amp;nocache='.$booster->filestime.'" />';
						
						if(key($css_rel_files) != 'print')
						{
							$links .= $link."\r\n";
						}
						else
						{
							$links .= '<noscript>'.$link.'</noscript>'."\r\n";
							$js_plain .= 'jQuery(document).ready(function () {
								jQuery("head").append("'.addslashes($link).'");
							});
							';
						}
						$links .= "\r\n";
						next($media_rel);
					}
					next($css_rel_files);
				}

				// Insert markup for normal browsers and IEs (CC's now replacing former UA-sniffing)
				if($charset != '') $booster_out .= '<meta http-equiv="Content-Type" content="text/html; charset='.$charset.'" />'."\r\n";
				$booster_out .= '<!--[if IE]><![endif]-->'."\r\n";
				$booster_out .= '<!--[if (gte IE 8)|!(IE)]><!-->'."\r\n";
				$booster_out .= $links;
				$booster_out .= '<!--<![endif]-->'."\r\n";
				$booster_out .= '<!--[if lte IE 7 ]>'."\r\n";
				$booster_out .= str_replace('booster_css.php','booster_css_ie.php',$links);
				$booster_out .= '<![endif]-->'."\r\n";
				
				// Injecting the result
				$out = str_replace('</title>',"</title>\r\n".$booster_out,$out);
				$booster_out = '';
				
				
				// JS-part
				$js_rel_files = array();
				$js_abs_files = array();
				$js_parameters = array();
				preg_match_all('/<script[^>]*>(.*?)<\/script>/ims',$headtreffer[0][0],$treffer,PREG_PATTERN_ORDER);
				for($i=0;$i<count($treffer[0]);$i++) 
				{
					if(preg_match('/<script.*?src=[\'"]*([^\'"]+\.js)\??([^\'"]*)[\'"]*.*?<\/script>/ims',$treffer[0][$i],$srctreffer))
					{
						// Get Domainname
						if(isset($_SERVER['SCRIPT_URI']))
						{
							$host = parse_url($_SERVER['SCRIPT_URI'],PHP_URL_HOST);
						}
						else
						{
							$host = $_SERVER['HTTP_HOST'];
						}
						// Convert siteurl into a regex-safe expression
						$host = str_replace(array('/','.'),array('\/','\.'),$host);
						// Convert file's URI into an absolute local path
						$filename = preg_replace('/^http:\/\/'.$host.'[^\/]*/',rtrim($_SERVER['DOCUMENT_ROOT'],'/'),$srctreffer[1]);
						// If file is external
						if(substr($filename,0,7) == 'http://')
						{
							// Skip processing of external files altogether
							/* 
							$vars_array = explode('&',html_entity_decode($srctreffer[2],ENT_QUOTES,'ISO-8859-1'));
							$js_parameters = array_merge($js_parameters,$vars_array);

							$buffered_filename = $booster_cache_dir.'/'.md5($filename).'_buffered.js';
							
							if(!file_exists($buffered_filename) || filemtime($buffered_filename) < filemtime(__FILE__) || filemtime($buffered_filename) < (time() - (1 * 24 * 60 * 60))) 
							{
								$parsed_url = parse_url($filename);
								$host = $parsed_url['host'];
								$service_uri = $parsed_url['path'];
								$vars = $parsed_url['query'];
								
								// Compose HTTP request header
								$header = "Host: $host\r\n";
								$header .= "User-Agent: CSS-JS-Booster\r\n";
								$header .= "Connection: close\r\n\r\n";

								$fp = fsockopen($host, 80, $errno, $errstr);
								if($fp) 
								{
									$body = '';
									fputs($fp,"GET $service_uri?$vars  HTTP/1.0\r\n");
									fputs($fp,$header.$vars);
									while (!feof($fp)) {
										$body .= fgets($fp,65000);
									}
									
									fclose($fp);
									$body = preg_replace('/^HTTP.+?[\r\n]{1}[\r\n]{1}[\r\n]{1}/ms','',$body);
									@file_put_contents($buffered_filename,$body);
									@chmod($filename,0777);

									// Put file-reference inside a comment
									$out = str_replace($srctreffer[0],'<!-- Processed by Booster '.$srctreffer[0].' -->',$out);
		
									// Enqueue file to array
									$booster_to_file_path = $booster->getpath(str_replace('\\','/',dirname($buffered_filename)),str_replace('\\','/',dirname(__FILE__)));
									array_push($js_rel_files,$booster_cache_reldir.'/'.md5($filename).'_buffered.js');
									array_push($js_abs_files,rtrim(str_replace('\\','/',dirname(realpath(ABSPATH))),'/').'/'.$buffered_filename);
								}
								// Leave untouched but put calculated local file name into a comment for debugging
								else $out = str_replace($srctreffer[0],$srctreffer[0].'<!-- Booster had a problems retrieving '.$filename.' -->',$out);
							}
							else
							{
								// Put file-reference inside a comment
								$out = str_replace($srctreffer[0],'<!-- Processed by Booster '.$srctreffer[0].' -->',$out);
	
								// Enqueue file to array
								$booster_to_file_path = $booster->getpath(str_replace('\\','/',dirname($buffered_filename)),str_replace('\\','/',dirname(__FILE__)));
								array_push($js_rel_files,$booster_cache_reldir.'/'.md5($filename).'_buffered.js');
								array_push($js_abs_files,rtrim(str_replace('\\','/',dirname(realpath(ABSPATH))),'/').'/'.$buffered_filename);
							}						
							*/
						}
						// If file is internal and does exist
						elseif(file_exists($filename))
						{
							// If its a normal JavaScript-file
							if(substr($filename,strlen($filename) - 3,3) == '.js')
							{
								// Remove any parameters from file's URI
								$filename = preg_replace('/\?.*$/','',$filename);
	
								// Put file-reference inside a comment
								$out = str_replace($srctreffer[0],'<!-- Processed by Booster '.$srctreffer[0].' -->',$out);
			
								// Calculate relative path from Booster to file
								$booster_to_file_path = $booster->getpath(str_replace('\\','/',dirname($filename)),str_replace('\\','/',dirname(__FILE__)));
								$filename = $booster_to_file_path.'/'.basename($filename);
				
								// Enqueue file to array
								array_push($js_rel_files,$filename);
								array_push($js_abs_files,rtrim(str_replace('\\','/',dirname(realpath(ABSPATH))),'/').'/'.$root_to_booster_path.'/'.$filename);
							}
							else $out = str_replace($srctreffer[0],$srctreffer[0].'<!-- Booster skipped '.$filename.' -->',$out);
						}
						// Leave untouched but put calculated local file name into a comment for debugging
						else $out = str_replace($srctreffer[0],$srctreffer[0].'<!-- Booster had a problems finding '.$filename.' -->',$out);
					}
					else 
					{
						// Save plain JS to file to keep everything in line
						$filename = $booster_cache_dir.'/'.md5($treffer[1][$i]).'_plain.js';
						if(!file_exists($filename)) @file_put_contents($filename,$treffer[1][$i]);
						@chmod($filename,0777);
			
						// Enqueue file to array
						$booster_to_file_path = $booster->getpath(str_replace('\\','/',dirname($filename)),str_replace('\\','/',dirname(__FILE__)));
						array_push($js_rel_files,$booster_cache_reldir.'/'.md5($treffer[1][$i]).'_plain.js');
						#array_push($js_rel_files,$booster_cache_reldir.'/'.md5($treffer[1][$i]).'_plain.js');
						array_push($js_abs_files,rtrim(str_replace('\\','/',dirname(realpath(ABSPATH))),'/').'/'.$filename);
	
						//$js_plain .= "try{".$treffer[1][$i];
						$out = str_replace($treffer[0][$i],'<!-- '.$treffer[0][$i].' -->',$out);
					}
				}
				
				// Creating Booster markup
				$js_rel_files = implode(',',$js_rel_files);
				$js_abs_files = implode(',',$js_abs_files);
				$js_plain = preg_replace('/\/\*.*?\*\//ims','',$js_plain);
				$js_plain .= 'try {document.execCommand("BackgroundImageCache", false, true);} catch(err) {}
				';
				
				$booster_out .= '<script type="text/javascript" src="'.
				get_option('siteurl').'/wp-content/plugins/'.$booster_folder.'/booster_js.php/dir='.
				htmlentities(str_replace('..','%3E',$js_rel_files)).
				'&amp;cachedir='.htmlentities(str_replace('..','%3E',$booster_cache_reldir),ENT_QUOTES).
				(($booster->debug) ? '&amp;debug=1' : '').
				((!$booster->js_minify) ? '&amp;js_minify=0' : '').
				'&amp;nocache='.$booster->filestime.
				'?'.implode('&amp;',$js_parameters).'"></script>
				<script type="text/javascript">'.$js_plain.'</script>';
				$booster_out .= "\r\n";
				#$booster_out .= "\r\n<!-- ".$js_abs_files." -->\r\n";
				
				// Injecting the result
				$out = str_replace('</head>',$booster_out.'</head>',$out);
			}
		}
		else $out = str_replace('<body','<div style="display: block; padding: 1em; background-color: #FFF9D0; color: #912C2C; border: 1px solid #912C2C; font-family: Calibri, \'Lucida Grande\', Arial, Verdana, sans-serif; white-space: pre;">You need to upgrade to PHP 5 or higher to have CSS-JS-Booster work. You currently are running on PHP '.phpversion().'</div><body',$out);
		
		// Recreate output buffer
		ob_end_clean();
		if (
		isset($_SERVER['HTTP_ACCEPT_ENCODING']) 
		&& substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') 
		&& function_exists('ob_gzhandler') 
		&& (!ini_get('zlib.output_compression') || intval(ini_get('zlib.output_compression')) <= 0) 
		&& !function_exists('wp_cache_ob_callback')
		) @ob_start('ob_gzhandler');
		elseif(function_exists('wp_cache_ob_callback')) @ob_start('wp_cache_ob_callback');
		else @ob_start();
		
		// Output page
		echo $out;
	}
}
add_action('wp_footer','booster_wp');
?>
