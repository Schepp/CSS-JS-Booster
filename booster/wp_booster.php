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
	if($out = ob_get_contents())
	{
		ob_end_clean();
		$booster_out = '';
		$booster = new Booster();

		$root_to_booster_path = $booster->getpath(str_replace('\\','/',dirname(__FILE__)),str_replace('\\','/',dirname(realpath($_SERVER['SCRIPT_FILENAME']))));
		#$booster_to_css_path = $booster->getpath(str_replace('\\','/',dirname(WP_CONTENT_DIR.preg_replace('/^.*wp-content/i','',$bloginfo_url))),str_replace('\\','/',dirname(__FILE__)));

		if(preg_match_all('/<head.*<\/head>/ims',$out,$headtreffer,PREG_PATTERN_ORDER) > 0)
		{
			// CSS Part
			$css_files = array();
			preg_match_all('/<link.*?href=[\'"]*([^\'"]+\.css)[\'"]*[^>]*?>/ims',$headtreffer[0][0],$treffer,PREG_PATTERN_ORDER);
			for($i=0;$i<count($treffer[0]);$i++) 
			{
				if(preg_match('/media=[\'"]*([^\'"]+)[\'"]*/ims',$treffer[0][0],$mediatreffer)) $media = $mediatreffer[1];
				else $media = 'all';

				if(preg_match('/rel=[\'"]*([^\'"]+)[\'"]*/ims',$treffer[0][0],$reltreffer)) $rel = $reltreffer[1];
				else $rel = 'stylesheet';

				$filename = preg_replace('/^http:\/\/[^\/]+/',rtrim($_SERVER['DOCUMENT_ROOT'],'/'),$treffer[1][$i]);
				$filename = preg_replace('/\?.*$/','',$filename);
				if(file_exists($filename))
				{
					$out = str_replace($treffer[0][$i],'<!-- '.$treffer[0][$i].' -->',$out);

					$booster_to_file_path = $booster->getpath(str_replace('\\','/',dirname($filename)),str_replace('\\','/',dirname(__FILE__)));
					$filename = $booster_to_file_path.'/'.basename($filename);
	
					if(!isset($css_files[$media])) $css_files[$media] = array();
					if(!isset($css_files[$media][$rel])) $css_files[$media][$rel] = array();
					array_push($css_files[$media][$rel],$filename);
				}
				else $out = str_replace($treffer[0][$i],$treffer[0][$i].'<!-- '.$filename.' -->',$out);
			}
			reset($css_files);
			for($i=0;$i<count($css_files);$i++) 
			{
				$media = $css_files[key($css_files)];
				reset($media);
				for($j=0;$j<count($media);$j++) 
				{
					$media[key($media)] = implode(',',$media[key($media)]);
					$booster_out .= '<link type="text/css" rel="'.key($media).'" media="'.key($css_files).'" href="'.htmlentities($root_to_booster_path.'/booster_css.php?dir='.$media[key($media)],ENT_QUOTES).'" />';
					$booster_out .= "\r\n";
					next($media);
				}
				next($css_files);
			}
			
			
			// JS-Part
			$js_files = array();
			preg_match_all('/<script.*?src=[\'"]*([^\'"]+\.js)[\'"]*.*?<\/script>/ims',$headtreffer[0][0],$treffer,PREG_PATTERN_ORDER);
			for($i=0;$i<count($treffer[0]);$i++) 
			{
				$filename = preg_replace('/^http:\/\/[^\/]+/',rtrim($_SERVER['DOCUMENT_ROOT'],'/'),$treffer[1][$i]);
				$filename = preg_replace('/\?.*$/','',$filename);
				if(file_exists($filename))
				{
					$out = str_replace($treffer[0][$i],'<!-- '.$treffer[0][$i].' -->',$out);

					$booster_to_file_path = $booster->getpath(str_replace('\\','/',dirname($filename)),str_replace('\\','/',dirname(__FILE__)));
					$filename = $booster_to_file_path.'/'.basename($filename);
	
					array_push($js_files,$filename);
				}
				else $out = str_replace($treffer[0][$i],$treffer[0][$i].'<!-- '.$filename.' -->',$out);
			}
			$js_files = implode(',',$js_files);
			$booster_out .= '<script type="text/javascript" src="'.htmlentities($root_to_booster_path.'/booster_js.php?dir='.$js_files,ENT_QUOTES).'"></script>';
			$booster_out .= "\r\n";
			
			// Outputting the result
			$out = str_replace('</head>',$booster_out.'</head>',$out);
		}
		
		// output page
		if (
		isset($_SERVER['HTTP_ACCEPT_ENCODING']) 
		&& substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') 
		&& function_exists('ob_gzhandler') 
		&& !ini_get('zlib.output_compression')
		) @ob_start('ob_gzhandler');
		else @ob_start();
		
		echo $out;
	}
}
add_action('wp_footer','booster_wp');
?>