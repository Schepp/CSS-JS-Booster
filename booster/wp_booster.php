<?php
/*
Plugin Name: CSS-JS-Booster
Plugin URI: http://github.com/Schepp/CSS-JS-Booster
Description: combines, optimizes, dataURI-fies, re-splits, compresses and caches your CSS and JS for quicker loading times
Version: 0.1
Author: Christian Schepp Schaefer <schaepp@gmx.de> <http://twitter.com/derSchepp>
Author URI: http://github.com/Schepp/CSS-JS-Booster
*/

/*  Copyright 2009  Christian Schepp Schaefer  (email : schaepp@gmx.de)

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

/*
function booster_wp_css($bloginfo_url, $show) {
	if($show == 'stylesheet_url')
	{
		$booster = new Booster();

		$root_to_booster_path = $booster->getpath(str_replace('\\','/',dirname(__FILE__)),str_replace('\\','/',dirname(realpath($_SERVER['SCRIPT_FILENAME']))));
		$booster_to_css_path = $booster->getpath(str_replace('\\','/',dirname(WP_CONTENT_DIR.preg_replace('/^.*wp-content/i','',$bloginfo_url))),str_replace('\\','/',dirname(__FILE__)));
		
		$bloginfo_url = $root_to_booster_path.'/booster_css.php?dir='.$booster_to_css_path.'/'.basename(preg_replace('/^.*wp-content/i','',$bloginfo_url));
	}
	return $bloginfo_url;
}
add_filter('bloginfo_url','booster_wp_css',9999,2);
*/

function booster_wp() {
	// Dump output buffer
	if($out = ob_get_contents())
	{
		ob_end_clean();
		$booster_out = '';
		$booster = new Booster();
		$booster->debug = TRUE;

		// Calculate relative path from root to Booster directory
		$root_to_booster_path = $booster->getpath(str_replace('\\','/',dirname(__FILE__)),str_replace('\\','/',dirname(realpath($_SERVER['SCRIPT_FILENAME']))));

		if(preg_match_all('/<head.*<\/head>/ims',$out,$headtreffer,PREG_PATTERN_ORDER) > 0)
		{
			// Prevent processing of conditional comments
			$headtreffer[0][0] = preg_replace('/<!--\[if.+?endif\]-->/ims','',$headtreffer[0][0]);
			
			// CSS part
			$css_rel_files = array();
			preg_match_all('/<link[^>]*?href=[\'"]*?([^\'"]+?\.css)[\'"]*?[^>]*?>/ims',$headtreffer[0][0],$treffer,PREG_PATTERN_ORDER);
			for($i=0;$i<count($treffer[0]);$i++) 
			{
				// Get media-type
				if(preg_match('/media=[\'"]*([^\'"]+)[\'"]*/ims',$treffer[0][0],$mediatreffer)) $media = $mediatreffer[1];
				else $media = 'all';

				// Get relation
				if(preg_match('/rel=[\'"]*([^\'"]+)[\'"]*/ims',$treffer[0][0],$reltreffer)) $rel = $reltreffer[1];
				else $rel = 'stylesheet';

				// Convert file's URI into an absolute local path
				$filename = preg_replace('/^http:\/\/[^\/]+/',rtrim($_SERVER['DOCUMENT_ROOT'],'/'),$treffer[1][$i]);
				// Remove any parameters from file's URI
				$filename = preg_replace('/\?.*$/','',$filename);
				// If file exists
				if(file_exists($filename))
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
					array_push($css_abs_files[$media][$rel],$root_to_booster_path.'/'.$filename);
				}
				// Leave untouched but put calculated local file name into a comment for debugging
				else $out = str_replace($treffer[0][$i],$treffer[0][$i].'<!-- Booster had a problems finding '.$filename.' -->',$out);
			}

			// Creating Booster markup for each media and relation seperately
			reset($css_rel_files);
			for($i=0;$i<count($css_rel_files);$i++) 
			{
				$media_rel = $css_rel_files[key($css_rel_files)];
				$media_abs = $css_abs_files[key($css_rel_files)];
				reset($media_rel);
				for($j=0;$j<count($media_rel);$j++) 
				{
					$media_rel[key($media_rel)] = implode(',',$media_rel[key($media_rel)]);
					$media_abs[key($media_rel)] = implode(',',$media_abs[key($media_rel)]);
					$booster_out .= '<link type="text/css" rel="'.key($media_rel).'" media="'.key($css_rel_files).'" href="/'.htmlentities($root_to_booster_path.'/booster_css.php?dir='.$media_rel[key($media_rel)],ENT_QUOTES).'&amp;nocache='.$booster->getfilestime($media_abs[key($media_rel)],'css').'" />'."\r\n";
					$booster_out .= "\r\n";
					next($media_rel);
				}
				next($css_rel_files);
			}
			
			
			// JS-part
			$js_rel_files = array();
			$js_abs_files = array();
			$js_plain = '';
			preg_match_all('/<script[^>]*>(.*?)<\/script>/ims',$headtreffer[0][0],$treffer,PREG_PATTERN_ORDER);
			for($i=0;$i<count($treffer[0]);$i++) 
			{
				if(preg_match('/<script.*?src=[\'"]*([^\'"]+\.js)[\'"]*.*?<\/script>/ims',$treffer[0][$i],$srctreffer))
				{
					// Convert file's URI into an absolute local path
					$filename = preg_replace('/^http:\/\/[^\/]+/',rtrim($_SERVER['DOCUMENT_ROOT'],'/'),$srctreffer[1]);
					// Remove any parameters from file's URI
					$filename = preg_replace('/\?.*$/','',$filename);
					// If file exists
					if(file_exists($filename))
					{
						// Put file-reference inside a comment
						$out = str_replace($srctreffer[0],'<!-- Processed by Booster '.$srctreffer[0].' -->',$out);
	
						// Calculate relative path from Booster to file
						$booster_to_file_path = $booster->getpath(str_replace('\\','/',dirname($filename)),str_replace('\\','/',dirname(__FILE__)));
						$filename = $booster_to_file_path.'/'.basename($filename);
		
						// Enqueue file to array
						array_push($js_rel_files,$filename);
						array_push($js_abs_files,$root_to_booster_path.'/'.$filename);
					}
					// Leave untouched but put calculated local file name into a comment for debugging
					else $out = str_replace($srctreffer[0],$srctreffer[0].'<!-- Booster had a problems finding '.$filename.' -->',$out);
				}
				else 
				{
					$js_plain .= $treffer[1][$i];
					$out = str_replace($treffer[0][$i],'<!-- '.$treffer[0][$i].' -->',$out);
				}
			}
			
			// Creating Booster markup
			$js_rel_files = implode(',',$js_rel_files);
			$js_abs_files = implode(',',$js_abs_files);
			$js_plain = preg_replace('/\/\*.*?\*\//ims','',$js_plain);
			$booster_out .= '<script type="text/javascript" src="/'.htmlentities($root_to_booster_path.'/booster_js.php?dir='.$js_rel_files,ENT_QUOTES).'&amp;nocache='.$booster->getfilestime($js_abs_files,'js').'"></script>
			<script type="text/javascript">'.$js_plain.'</script>';
			$booster_out .= "\r\n";
			
			
			// Injecting the result
			$out = str_replace('</head>',$booster_out.'</head>',$out);
		}
		
		// Recreate output buffer
		if (
		isset($_SERVER['HTTP_ACCEPT_ENCODING']) 
		&& substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') 
		&& function_exists('ob_gzhandler') 
		&& !ini_get('zlib.output_compression')
		) @ob_start('ob_gzhandler');
		else @ob_start();
		
		// Output page
		echo $out;
	}
}
add_action('wp_footer','booster_wp');
?>