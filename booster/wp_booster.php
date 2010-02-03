<?php
/*
Plugin Name: CSS-JS-Booster
Plugin URI: http://github.com/Schepp/CSS-JS-Booster
Description: combines, optimizes, dataURI-fies, re-splits, compresses and caches your CSS and JS for quicker loading times
Version: 0.1.2
Author: Christian "Schepp" Schaefer
Author URI: http://twitter.com/derSchepp
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

function booster_wp() {
	// Dump output buffer
	if($out = ob_get_contents())
	{
		$booster_out = '';
		$booster = new Booster();
		$booster->js_minify = FALSE;

		// Calculate relative path from root to Booster directory
		$root_to_booster_path = $booster->getpath(str_replace('\\','/',dirname(__FILE__)),str_replace('\\','/',dirname(realpath(ABSPATH))));
		
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
					array_push($css_abs_files[$media][$rel],rtrim(str_replace('\\','/',dirname(realpath(ABSPATH))),'/').'/'.$root_to_booster_path.'/'.$filename);
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
					$booster_out .= '<link type="text/css" rel="'.key($media_rel).'" media="'.key($css_rel_files).'" href="'.get_option('siteurl').'/wp-content/plugins/booster/booster_css.php?dir='.$media_rel[key($media_rel)].(($booster->debug) ? '&amp;debug=1' : '').'&amp;nocache='.$booster->getfilestime($media_abs[key($media_rel)],'css').'" />'."\r\n";
					$booster_out .= "\r\n";
					#$booster_out .= "\r\n<!-- ".$media_abs[key($media_rel)]." -->\r\n";
					next($media_rel);
				}
				next($css_rel_files);
			}
			
			// Injecting the result
			$out = str_replace('</title>',"</title>\r\n".$booster_out,$out);
			$booster_out = '';
			
			
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
						array_push($js_abs_files,rtrim(str_replace('\\','/',dirname(realpath(ABSPATH))),'/').'/'.$root_to_booster_path.'/'.$filename);
					}
					// Leave untouched but put calculated local file name into a comment for debugging
					else $out = str_replace($srctreffer[0],$srctreffer[0].'<!-- Booster had a problems finding '.$filename.' -->',$out);
				}
				else 
				{
					// Save plain JS to file to keep everything in line
					$filename = rtrim(str_replace('\\','/',dirname(__FILE__)),'/').'/booster_cache/'.md5($treffer[1][$i]).'_plain.js';
					if(!file_exists($filename)) file_put_contents($filename,$treffer[1][$i]);
					@chmod($filename,0777);
		
					// Enqueue file to array
					array_push($js_rel_files,'booster_cache/'.md5($treffer[1][$i]).'_plain.js');
					array_push($js_abs_files,rtrim(str_replace('\\','/',dirname(realpath(ABSPATH))),'/').'/'.$filename);

					//$js_plain .= "try{".$treffer[1][$i];
					$out = str_replace($treffer[0][$i],'<!-- '.$treffer[0][$i].' -->',$out);
				}
			}
			
			// Creating Booster markup
			$js_rel_files = implode(',',$js_rel_files);
			$js_abs_files = implode(',',$js_abs_files);
			$js_plain = preg_replace('/\/\*.*?\*\//ims','',$js_plain);
			//$js_plain .= "\r\n";
			$js_plain .= 'jQuery(document).ready(function () {
				jQuery("img[longdesc]").each(function (i) {
					jQuery(this).attr("src",jQuery(this).attr("longdesc"));
				});
			});
			';
			$js_plain = 'try {document.execCommand("BackgroundImageCache", false, true);} catch(err) {}
			';
			
			$booster_out .= '<script type="text/javascript" src="'.get_option('siteurl').'/wp-content/plugins/booster/booster_js.php?dir='.$js_rel_files.(($booster->debug) ? '&amp;debug=1' : '').((!$booster->js_minify) ? '&amp;js_minify=0' : '').'&amp;nocache='.$booster->getfilestime($js_abs_files,'js').'"></script>
			<script type="text/javascript">'.$js_plain.'</script>';
			$booster_out .= "\r\n";
			#$booster_out .= "\r\n<!-- ".$js_abs_files." -->\r\n";
			
			// Injecting the result
			$out = str_replace('</head>',$booster_out.'</head>',$out);
			
			// Replace image-tags for deferred loading
			#$out = preg_replace('/(<img[^>]+?)(src=[\'"]?)([^\'"]+\.(gif|jpg|png))/ims','$1longdesc="$3" $2',$out);
		}
		
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