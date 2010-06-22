<?php

/**
 * CSS-JS-BOOSTER
 * 
 * An easy to use PHP-Library that combines, optimizes, dataURI-fies, re-splits, 
 * compresses and caches your CSS and JS for quicker loading times.
 * 
 * PHP version 5
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
 * @category  PHP 
 * @package   CSS-JS-Booster 
 * @author    Christian Schepp Schaefer <schaepp@gmx.de> <http://twitter.com/derSchepp>
 * @copyright 2009 Christian Schepp Schaefer
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 3.0
 * @link      http://github.com/Schepp/CSS-JS-Booster 
 */

// Starting zlib-compressed output
@ini_set('zlib.output_compression',2048);
@ini_set('zlib.output_compression_level',4);

// Turning on strict error reporting
#ini_set("display_errors", 1);
#error_reporting(E_ALL);

// Starting gzip-compressed output if zlib-compression is turned off
if (
isset($_SERVER['HTTP_ACCEPT_ENCODING']) 
&& substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') 
&& function_exists('ob_gzhandler') 
&& (!ini_get('zlib.output_compression') || intval(ini_get('zlib.output_compression')) != 2048)
&& !function_exists('booster_wp')
) @ob_start('ob_gzhandler');
else @ob_start();


/**
 * Inclusion of user agent detection class
 */
include_once('browser_class_inc.php');

/**
 * CSS-JS-BOOSTER
 * 
 * An easy to use PHP-Library that combines, optimizes, dataURI-fies, re-splits, 
 * compresses and caches your CSS and JS for quicker loading times.
 * 
 * @category  PHP 
 * @package   CSS-JS-Booster 
 * @author    Christian Schepp Schaefer <schaepp@gmx.de> <http://twitter.com/derSchepp>
 * @copyright 2009 Christian Schepp Schaefer
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 3.0
 * @link      http://github.com/Schepp/CSS-JS-Booster 
 */
class Booster {

    /**
     * Defines source to take the CSS stylesheets from.
     * 
     * It accepts foldernames, filenames, multiple files and folders comma-delimited in strings or as array.
     * When passing foldernames, containing files will be processed in alphabetical order.
     * The variable also accepts a stylesheet-string if you set css_stringmode to "TRUE"
     * Defaults to "css".
     * @var    mixed
     * @access public 
     * @see    $css_stringmode
     */
	public $css_source = 'css';

    /**
     * Defines media-attribute for CSS markup output
     *
     * Specify differing media-types like "print", "handheld", etc.
     * Defaults to "all".
     * @var    string 
     * @access public 
     */
	public $css_media = 'all';

    /**
     * Defines rel-attribute for CSS markup output
     *
     * Specify differing relations like "alternate stylesheet"
     * Defaults to "stylesheet".
     * @var    string 
     * @access public 
     */
	public $css_rel = 'stylesheet';

    /**
     * Defines a title-attribute for CSS markup output
     *
     * If you like to title multiple stylesheets
     * Defaults to "Standard".
     * @var    string 
     * @access public 
     */
	public $css_title = 'Standard';

    /**
     * Defines the markup language to use.
     *
     * Defaults to "XHTML".
     * @var    string 
     * @access public 
     */
	public $css_markuptype = 'XHTML';

    /**
     * Defines in how many parts the CSS output shall be split
     *
     * As newer browsers support more than 2 concurrent parallel connections 
     * to a webserver you can decrease loading-time by splitting the output up 
     * into more than one file.
     * Defaults to "2".
     * @var    number 
     * @access public 
     */
	public $css_totalparts = 2;

    /**
     * Defines which part to ouput when retrieving CSS in multiple parts
     *
     * Used by accompagning script "booster_css.php"
     * Defaults to "0".
     * @var    number 
     * @access public 
     */
	public $css_part = 0;

    /**
     * Defines if source-file retrieval shall be recursive
     *
     * Only matters when passing folders as source-parameter.
     * If set to "TRUE" contents of folders found inside source-folder are also fetched.
     * Defaults to "FALSE".
     * @var    boolean 
     * @access public  
     */
	public $css_recursive = FALSE;

    /**
     * Switches on string-mode, when passing styleheet-strings as source
     *
     * Instead of folders and files to read and parse you can also pass
     * stylesheet-code as source. But, this only works if you switch string-mode on.
     * Defaults to "FALSE".
     * @var    boolean 
     * @access public  
     * @see    $css_source
     */
	public $css_stringmode = FALSE;

    /**
     * Defines the base-folder for all files referenced in stylesheet-string
     *
     * When being in string-mode, the booster prepends this path going out from the caller-location 
     * in order to find all referenced files.
     * Defaults to "./".
     * @var    string 
     * @access public 
     * @see    $css_stringmode
     */
	public $css_stringbase = './';

    /**
     * Used to store the date of last change of a stylesheet-string
     *
     * Is set to the file-time of the calling script during construction.
     * @var    integer 
     * @access private  
     * @see    $css_stringmode
     */
	private $css_stringtime = 0;

    /**
     * Defines source to take the JS from
     * 
     * It accepts foldernames, filenames, multiple files and folders comma-delimited in strings or as array.
     * When passing foldernames, containing files will be processed in alphabetical order.
     * The variable also accepts a javascript-string if you set js_stringmode to "TRUE"
     * Defaults to "js".
     * @var    mixed 
     * @access public 
     * @see    $js_stringmode
     */
	public $js_source = 'js';

    /**
     * Defines in how many parts the JS output shall be split [Deprecated, still in there for backward compatibility]
     *
     * Newer browsers support more than 2 concurrent parallel connections 
     * but NOT for JS-files. So here one single output-file would be best. 
     * You can still uppen the number of output-files here if like. [Deprecated, still in there for backward compatibility]
     * Defaults to "1".
     * @var    integer 
     * @access public  
     */
	public $js_totalparts = 1;

    /**
     * Defines which part to ouput when retrieving JS in multiple parts [Deprecated, still in there for backward compatibility]
     *
     * Used by accompagning script "booster_js.php" [Deprecated, still in there for backward compatibility]
     * Defaults to "0".
     * @var    integer 
     * @access public  
     */
	public $js_part = 0;

    /**
     * Defines if Google Closure Compiler should be used [Deprecated, still in there for backward compatibility]
     *
     * Used by accompagning script "booster_js.php" [Deprecated, still in there for backward compatibility]
     * Defaults to "TRUE".
     * @var    boolean 
     * @access public  
     */
	public $js_minify = TRUE;

    /**
     * Defines if source-file retrieval shall be recursive
     *
     * Only matters when passing folders as source-parameter.
     * If set to "TRUE" contents of folders found inside source-folder are also fetched.
     * Defaults to "FALSE".
     * @var    boolean 
     * @access public  
     */
	public $js_recursive = FALSE;

    /**
     * Switches on string-mode, when passing javascript-strings as source
     *
     * Instead of folders and files to read and parse you can also pass
     * javascript-code as source. But, this only works if you switch string-mode on.
     * Defaults to "FALSE".
     * @var    boolean 
     * @access public  
     * @see    $js_source
     */
	public $js_stringmode = FALSE;

    /**
     * Defines the base-folder for all files referenced in javascript-string
     *
     * When being in string-mode, the booster prepends this path going out from the caller-location 
     * in order to find all referenced files.
     * Defaults to "./".
     * @var    string 
     * @access public 
     * @see    $js_stringmode
     */
	public $js_stringbase = './';

    /**
     * Used to store the date of last change of a javascript-string
     *
     * Is set to the file-time of the calling script during construction.
     * @var    integer 
     * @access private  
     * @see    $js_stringmode
     */
	private $js_stringtime = 0;

    /**
     * Defines the directory to use for caching
     *
     * The directory is relative to "booster"-folder and should be write-enabled
     * Defaults to "booster_cache".
     * @var    string 
     * @access public 
     */
	public $booster_cachedir = 'booster_cache';

    /**
     * Used to remember if the working-path has already been calculated.
     * @var    boolean 
     * @access private 
      * @see    setcachedir
    */
	private $booster_cachedir_transformed = FALSE;

    /**
     * Used to to store user-agent info
     * @var    object 
     * @access public 
     * @see    __construct
     */
	public $browser;

	/**
	 * You can use the closure compiler of CSS-JS-Booster with this option but ATM
	 * it will works only on linux with java installed and there's no tests done so
	 * carefull !
	 * @var boolean
	 */
	public $js_hosted_minifier = FALSE;

	/**
	 * Will store the closure compiler path
	 * @var string
	 * @access private
	 */
	private $js_hosted_minifier_path;

	/**
	 * You can use the hosted css minifier wich is yui compressor
	 * @var boolean
	 */
	public $css_hosted_minifier = FALSE;

	/**
	 * Will store the yui compressor path
	 * @var string
	 * @access private
	 */
	private $css_hosted_minifier_path;

    /**
     * Switch debug mode on/off
     * @var    boolean 
     * @access public  
     */
	public $debug = FALSE;
    
    /**
     * Defines the file to use for logging in debug-mode
     *
     * The file is located inside cache-folder
     * Starts empty, defaults later to "booster_cache/debug_log".
     * @var    string 
     * @access private 
     */
	private $debug_log = '';

    /**
     * Constructor
     * 
     * Sets @var $css_stringtime to caller file time
     * Invokes new browser object for further use
     * 
     * @return void   
     * @access public 
     */
    public function __construct()
    {
		$this->css_stringtime = filemtime(realpath($_SERVER['SCRIPT_FILENAME']));
		$this->js_stringtime = filemtime(realpath($_SERVER['SCRIPT_FILENAME']));
		$this->browser = new browser();
    }

    /**
     * Setcachedir calculates correct cache-path once and checks directory's writability
     * 
     * @return void   
     * @access public 
     */
	public function setcachedir()
	{
		$errormessage = '';
		// Check if @var $booster_cachedir_transformed is still "FALSE"
		if(!$this->booster_cachedir_transformed) 
		{
			$this->booster_cachedir = str_replace('\\','/',dirname(__FILE__)).'/'.$this->booster_cachedir;
			$this->debug_log = $this->booster_cachedir.'/debug_log.txt';
			$this->booster_cachedir_transformed = TRUE;
		}
		// Throw a warning and quit if cache-directory doesn't exist or isn't writable
		if(!@is_dir($this->booster_cachedir) && !@mkdir($this->booster_cachedir,0777)) 
		{
			$errormessage = "\r\nYou need to create a directory \r\n".$this->booster_cachedir."\r\n with CHMOD 0777 rights!!!\r\n";
		}
		// Also check here for the right PHP version
		if(strnatcmp(phpversion(),'5.0.0') < 0)
		{
			$errormessage = "\r\nYou need to upgrade to PHP 5 or higher to have CSS-JS-Booster work. You currently are running on PHP ".phpversion().".\r\n";
		}
		// If there are error, output them and stop execution
		if($errormessage != '')
		{
			$errormessage_css = str_replace("\r\n","\\00000A",$errormessage);
			echo "/* ".$errormessage." */\r\n\r\n";
			echo "body:before {display: block; padding: 1em; background-color: #FFF9D0; color: #912C2C; border: 1px solid #912C2C; font-family: Calibri, 'Lucida Grande', Arial, Verdana, sans-serif; white-space: pre; content: \"".$errormessage_css."\";}\r\n\r\n";
			exit;
		}
	}

    /**
     * Getpath calculates the relative path between @var $path1 and @var $path2
     * 
     * @param  string    $path1
     * @param  string    $path2
     * @param  string    $path1_sep   Sets the folder-delimiter, defaults to '/'
     * @return string    relative path between @var $path1 and @var $path2
     * @access protected 
     */
	public function getpath($path1 = '',$path2 = '',$path1_sep = '/')
	{
		$path2 = str_replace('\\','/',realpath($path2));
		$path1 = str_replace('\\','/',realpath($path1));
		$path2 = explode($path1_sep, $path2);
		$path1 = explode($path1_sep, $path1);
		$path = '.';
		$fix = '';
		$diff = 0;
		for($i = -1; ++$i < max(($rC = count($path2)), ($dC = count($path1)));)
		{
			if(isset($path2[$i]) and isset($path1[$i]))
			{
				if($diff)
				{
					$path .= $path1_sep.'..';
					$fix .= $path1_sep.$path1[$i];
					continue;
				}
				if($path2[$i] != $path1[$i])
				{
					$diff = 1;
					$path .= $path1_sep.'..';
					$fix .= $path1_sep.$path1[$i];
					continue;
				}
			}
			elseif(!isset($path2[$i]) and isset($path1[$i]))
			{
				for($j = $i-1; ++$j < $dC;)
				{
					$fix .= $path1_sep.$path1[$j];
				}
				break;
			}
			elseif(isset($path2[$i]) and !isset($path1[$i]))
			{
				for($j = $i-1; ++$j < $rC;)
				{
					$fix = $path1_sep.'..'.$fix;
				}
				break;
			}
		}
		$pathfix = $path.$fix;
		// Remove any unneccessary "./"
		$pathfix = preg_replace('/(?<!\.)\.\//','',$pathfix);
		return $pathfix;
	} 

    /**
     * Getfiles returns all files of a certain type within a folder
     * 
     * @param  string    $source    folder to look for files
     * @param  string    $type      sets file-type/suffix (for security reasons)
     * @param  boolean   $recursive tells the script to scan all subfolders, too
     * @param  array     $files     prepopulated array of files to append to and return
     * @return array     filenames sorted alphabetically
     * @access protected 
     */
	public function getfiles($source = '',$type = '',$recursive = FALSE,$files = array())
	{
		// Remove any trailing slash
		$source = rtrim($source,'/');
		// Check if @var $source really is a folder
		if(is_dir($source))
		{
			$handle=opendir($source);
			while(false !== ($file = readdir($handle)))
			{
				if($file[0] != '.')
				{
					// If it is a folder
					if(is_dir($source.'/'.$file)) 
					{
						 // If the @var $recursive is set to "TRUE" start fetching the subfolder
						if($recursive) $files = $this->getfiles($source.'/'.$file,$type,$recursive,$files);
					}
					// If it is a file and if the filetype matches
					else if(substr($file,strlen($file) - strlen($type), strlen($type)) == $type) 
					{
						// Add to file-list
						array_push($files,$source.'/'.$file);
					}
				}
			}
			closedir($handle);
			// Sort list alphabetically
			array_multisort($files, SORT_ASC, $files);
		}
		// If @var $source is a file, add it to the file-list
		elseif(is_file($source) && substr($source,strlen($source) - strlen($type), strlen($type)) == $type) array_push($files,$source);
		// Return file-list
		return $files;
	}

    /**
     * Getfilestime returns the timestamp of the newest file of a certain type within a folder
     * 
     * @param  mixed   $source    single folder or multiple comma-delimited folders or array of folders in which to look for files
     * @param  string  $type      sets file-type/suffix (for security reasons)
     * @param  boolean $recursive tells the script to scan all subfolders, too
     * @param  integer $filestime prepopulated timestamp to also check against
     * @return integer timestamp of the newest of all scanned files
     * @access public  
     */
	public function getfilestime($source = '',$type = '',$recursive = FALSE,$filestime = 0)
	{
		// Load @var $source with an array made form @var $source parameter
		if(is_array($source)) $sources = $source;
		else $sources = explode(',',$source);

		reset($sources);
		for($i=0;$i<sizeof($sources);$i++)
		{
			$source = current($sources);
			 // Remove any trailing slash
			$source = rtrim($source,'/');
			
			// Check if @var $source really is a folder
			if(is_dir($source))
			{
				// Get a list (array) of all folders and files inside that folder
				$files = $this->getfiles($source,$type,$recursive);
				// Check all list-item's timestamps
				for($i=0;$i<count($files);$i++) 
				{
					// In case it is a folder, run this funtion on the folder
					if(is_dir($files[$i])) 
					{
						if($recursive) $filestime = $this->getfilestime($files[$i],$type,$recursive,$filestime);
					}
					// In case it is a file, get its timestamp
					if(is_file($files[$i])) 
					{
						if(filemtime($files[$i]) > $filestime) $filestime = filemtime($files[$i]);
					}
				}
			}
			// If @var $source is a file check its file time
			elseif(is_file($source) && filemtime($source) > $filestime) $filestime = filemtime($source);
			next($sources);
		}
		// Return most recent timestamp
		return $filestime;
	}

    /**
     * Getfilescontents puts together all contents from files of a certain type within a folder
     * 
     * @param  string    $source       folder to look for files or file or code-string
     * @param  string    $type         sets file-type/suffix (for security reasons)
     * @param  boolean   $recursive    tells the script to scan all subfolders, too
     * @param  string    $filescontent prepopulated string to append to and return
     * @return string    Return all file contents
     * @access protected 
     */
	protected function getfilescontents($source = '',$type = '',$recursive = FALSE,$filescontent = '')
	{
		// Remove any trailing slash
		$source = rtrim($source,'/');
		
		// Prepare content storage
		$currentfilecontent = '';
		
		// If @var $source is a folder, get file-list and call itself on them
		if(is_dir($source))
		{
			$files = $this->getfiles($source,$type,$recursive);
			for($i=0;$i<count($files);$i++) $filescontent .= $this->getfilescontents($files[$i],$type,$recursive);
		}
		// If @var $source is a file
		elseif(is_file($source)) {
			if ($type === 'js')
			{
				if ($this->is_js($source))
				{
					if($this->debug) file_put_contents($this->debug_log,"processing file: ".$source."\r\n",FILE_APPEND);
					$currentfilecontent = file_get_contents($source);
				}
			}
			else if ($type === 'css')
			{
				if ($this->is_css($source))
				{
					if($this->debug) file_put_contents($this->debug_log,"processing file: ".$source."\r\n",FILE_APPEND);
					$currentfilecontent = file_get_contents($source);
				}
			}
		}
		// If @var $source is a string
		else $currentfilecontent = $source;

		// Find and resolve import-rules
		if($type == 'css')
		{
			preg_match_all('/@import\surl\([\'"]*?([^\'")]+\.css)[\'"]*?\);/ims',$currentfilecontent,$treffer,PREG_PATTERN_ORDER);
			for($i=0;$i<count($treffer[0]);$i++)
			{
				$importfile = realpath(dirname($source)).'/'.$treffer[1][$i];
				if($this->debug) file_put_contents($this->debug_log,"found file in @import-rule: ".$importfile."\r\n",FILE_APPEND);
				$diroffset = dirname($treffer[1][$i]);
				if(file_exists($importfile)) 
				{
					$importfilecontent = $this->getfilescontents($importfile,$type);
					$importfilecontent = preg_replace('/(url\([\'"]*)([^\/])/ims','\1'.$diroffset.'/\2',$importfilecontent);
				
					$currentfilecontent = str_replace($treffer[0][$i],$importfilecontent."\r\n",$currentfilecontent);
				}
				
				// @todo media-type sensivity
				#if(trim($treffer[2][$i]) != '') $mediatype = trim($treffer[2][$i]);
				#else $mediatype = 'all';
				#if($this->debug) $filescontent .= "/* importfile: ".$importfile." */\r\n";
				#if(file_exists($importfile)) $currentfilecontent = str_replace($treffer[0][$i],"@media ".$mediatype." {\r\n".$this->getfilescontents($importfile,$type)."}\r\n",$currentfilecontent);
			}
		}		
		// Append to @var $filescontent
		$filescontent .= $currentfilecontent."\r\n\r\n";

		return $filescontent;
	}

    /**
     * Css_minify does some soft minifications to the stylesheets, avoiding damage by optimizing too much
     * 
     * Replaces CSSTidy 1.3 which in some cases was destroying stylesheets
     * Removing unnecessessary whitespaces, tabs and newlines
     * Leaving commments in there as they may be browser hacks or needed to fulfill the terms of a license
     * 
     * @param  string    styles-string
     * @return string    minified styles-string
     * @access protected 
     */
	protected function css_minify($filescontent = '')
	{
		if($this->debug) file_put_contents($this->debug_log,"-----------------\r\ncss_minify input:\r\n-----------------\r\n".$filescontent."\r\n-----------------\r\n",FILE_APPEND);

		if ($this->css_hosted_minifier !== FALSE)
		{
			// the webserver must have read right on the jar, again, no test done :)
			$this->css_hosted_minifier_path = realpath(dirname(__FILE__).'/yuicompressor/yuicompressor-2.4.2.jar');
			// must create tmp files because closure compiler can't work with direct input..
			$tmp_file_path = sys_get_temp_dir().'/'.uniqid();
			file_put_contents($tmp_file_path, $filescontent);
			$filescontent = `java -jar $this->css_hosted_minifier_path $tmp_file_path --type css --charset utf-8`;
			unlink($tmp_file_path);
		}
		else
		{
			// Backup any values within single or double quotes
			preg_match_all('/(\'[^\']+\'|"[^"]+")/ims',$filescontent,$treffer,PREG_PATTERN_ORDER);
			for($i=0;$i<count($treffer[1]);$i++)
			{
				$filescontent = str_replace($treffer[1][$i],'##########'.$i.'##########',$filescontent);
			}

			// Remove traling semicolon of selector's last property
			$filescontent = preg_replace('/;[\s\r\n\t]*?}[\s\r\n\t]*/ims',"}\r\n",$filescontent);
			// Remove any whitespaces/tabs/newlines between semicolon and property-name
			$filescontent = preg_replace('/;[\s\r\n\t]*?([\r\n]?[^\s\r\n\t])/ims',';$1',$filescontent);
			// Remove any whitespaces/tabs/newlines surrounding property-colon
			$filescontent = preg_replace('/[\s\r\n\t]*:[\s\r\n\t]*?([^\s\r\n\t])/ims',':$1',$filescontent);
			// Remove any whitespaces/tabs/newlines surrounding selector-comma
			$filescontent = preg_replace('/[\s\r\n\t]*,[\s\r\n\t]*?([^\s\r\n\t])/ims',',$1',$filescontent);
			// Remove any whitespaces/tabs/newlines surrounding opening parenthesis
			$filescontent = preg_replace('/[\s\r\n\t]*{[\s\r\n\t]*?([^\s\r\n\t])/ims','{$1',$filescontent);
			// Remove any whitespaces/tabs/newlines between numbers and units
			$filescontent = preg_replace('/([\d\.]+)[\s\r\n\t]+(px|em|pt|%)/ims','$1$2',$filescontent);
			// Shorten zero-values
			$filescontent = preg_replace('/([^\d\.]0)(px|em|pt|%)/ims','$1',$filescontent);
			// Constrain multiple newlines
			$filescontent = preg_replace('/[\r\n]+/ims',"\n",$filescontent);
			// Constrain multiple whitespaces
			$filescontent = preg_replace('/\p{Zs}+/ims',' ',$filescontent);

			// Insert newline at certain points as preparation for parsing
			$filescontent = str_replace("{","\n{",$filescontent);
			$filescontent = str_replace("}","\n}",$filescontent);
			$filescontent = str_replace("/*","\n/*",$filescontent);
			$filescontent = str_replace("*/","\n*/",$filescontent);

			// Restore backupped values within single or double quotes
			for($i=0;$i<count($treffer[1]);$i++)
			{
				$filescontent = str_replace('##########'.$i.'##########',$treffer[1][$i],$filescontent);
			}
		}

		// For analysis purposes we log minify output
		if($this->debug) file_put_contents($this->debug_log,"-----------------\r\ncss_minify output:\r\n-----------------\r\n".$filescontent."\r\n-----------------\r\n",FILE_APPEND);

		return $filescontent;
	}
	
    /**
     * Css_datauri embeds external files like images into the stylesheet
     * 
     * Depending on the browser and operating system, this funtion does the following:
     * IE 6 and 7 on XP and IE 7 on Vista or higher don't understand data-URIs, but a proprietary format named MHTML. 
     * So they get served that.
     * Any other common browser understands data-URIs, even IE 8 up to a file-size of 24KB, so those get data-URI-embedding
     * IE 6 on Vista or higher doesn't understand any of the embeddings so it just gets standard styles.
     * 
     * @param  integer   $filestime    timestamp of the last modification of the content following
     * @param  string    $filescontent stylesheet-content
     * @return string    stylesheet-content with data-URI or MHTML embeddings
     * @see    function  Setcachedir
     * @access protected 
     */
	protected function css_datauri($filestime = 0,$filescontent = '',$dir = '')
	{
		// Call Setcachedir to make sure, cache-path has been calculated
		$this->setcachedir();
		
		// Prepare different RegExes
		// Media-files (currently images and fonts)
		$regex_embed = '/url\([\'"]*(.+?\.)(gif|png|jpg|otf|ttf|woff)[\'"]*\)/msi';
		$regex_embed_ie = '/url\([\'"]*(.+?\.)(gif|png|jpg|eot)[\'"]*\)/msi';
		// Any files
		$regex_url = '/(url\([\'"]??)([^\'"\)]+?\.[^\'"\)]+?)([\'"]??\))/msi';

		// identifier for the cache-files
		$identifier = md5($filescontent);
		
		// --------------------------------------------------------------------------------------

		// If User Agent is IE 6/7 on XP or IE 7 on Vista or higher proceed with MHTML-embedding
		if(
			$this->browser->family == 'MSIE' && $this->browser->platform == 'Windows' && 
			(
				(round(floatval($this->browser->familyversion)) == 6 && floatval($this->browser->platformversion) < 6) || 
				(round(floatval($this->browser->familyversion)) == 7 && floatval($this->browser->platformversion) >= 6)
			)
		)
		{
			// The @var $mhtmlarray collects references to all processed images so that we can look up if we already have embedded a certain image
			$mhtmlarray = array();
			// The external absolute path to where "booster_mhtml.php" resides
			$mhtmlpath = '/'.$this->getpath(str_replace('\\','/',dirname(__FILE__)),rtrim($_SERVER['DOCUMENT_ROOT'],'/'));
			// Cachefile for the extra MHTML-data
			$mhtmlfile = $this->booster_cachedir.'/'.$identifier.'_datauri_mhtml_'.(($this->debug) ? 'debug_' : '').'cache.txt';
			// Get Domainname
			if(isset($_SERVER['SCRIPT_URI']))
			{
				$mhtmlhost = parse_url($_SERVER['SCRIPT_URI'],PHP_URL_HOST);
			}
			else
			{
				$mhtmlhost = $_SERVER['HTTP_HOST'];
			}
			
			
			// Start putting together the styles and MHTML
			$mhtmlcontent = "Content-Type: multipart/related; boundary=\"_ANY_STRING_WILL_DO_AS_A_SEPARATOR\"\r\n\r\n";

			preg_match_all($regex_embed_ie,$filescontent,$treffer,PREG_PATTERN_ORDER);
			if(!$this->debug) for($i=0;$i<count($treffer[0]);$i++)
			{
				// Calculate full image path
				$imagefile = str_replace('\\','/',dirname(__FILE__)).'/'.$dir.'/'.$treffer[1][$i].$treffer[2][$i];
				// Create a new anchor-tag for the MHTML-file
				$imagetag = 'img'.$i;
				
				// If image-file exists and if file-size is lower than 24 KB
				if(file_exists($imagefile) && filesize($imagefile) < 24000) 
				{
					// Replace reference to image with reference to MHTML-file with corresponding anchor
					$filescontent = str_replace($treffer[0][$i],'url(mhtml:http://'.$mhtmlhost.$mhtmlpath.'/booster_mhtml.php?dir='.$identifier.'!'.$imagetag.')',$filescontent);

					// Look up in our list if we did not already process that exact file, if not append it
					if(!isset($mhtmlarray[$imagetag])) 
					{
						$mhtmlcontent .= "--_ANY_STRING_WILL_DO_AS_A_SEPARATOR\r\n";
						$mhtmlcontent .= "Content-Location:".$imagetag."\r\n";
						$mhtmlcontent .= "Content-Transfer-Encoding:base64\r\n\r\n";
						$mhtmlcontent .= base64_encode(file_get_contents($imagefile))."==\r\n";
						
						// Put file on our processed-list
						$mhtmlarray[$imagetag] = 1;
					}
				}
			}
			$mhtmlcontent .= "\r\n\r\n";
	
			// Hack suggested by Stoyan Stefanov: prepend a star in front of background-property
			//$filescontent = preg_replace('/(background[^;]+?mhtml)/','*$1',$filescontent);
			
			// Scan for any left file-references and adjust their path
			preg_match_all($regex_url,$filescontent,$treffer,PREG_PATTERN_ORDER);
			for($i=0;$i<count($treffer[0]);$i++)
			{
				if(
					substr(str_replace(array('"',"'"),'',$treffer[2][$i]),0,5) != 'http:' && 
					substr(str_replace(array('"',"'"),'',$treffer[2][$i]),0,6) != 'https:' && 
					substr(str_replace(array('"',"'"),'',$treffer[2][$i]),0,5) != 'data:' && 
					substr(str_replace(array('"',"'"),'',$treffer[2][$i]),0,6) != 'mhtml:'
				) $filescontent = str_replace(
					$treffer[1][$i].$treffer[2][$i].$treffer[3][$i],
					$treffer[1][$i].((!$this->css_stringmode) ? $dir : rtrim($this->css_stringbase,'/')).'/'.$treffer[2][$i].$treffer[3][$i],
					$filescontent);
			}
			
			// Store the cache-files
			// @file_put_contents($cachefile,$filescontent);
			// @chmod($cachefile,0777);
			@file_put_contents($mhtmlfile,$mhtmlcontent);
			@chmod($mhtmlfile,0777);
		}
		
		// --------------------------------------------------------------------------------------

	
		// If IE 6 browser on Vista or higher (like IETester under Vista / Windows 7 for example) do not embed
		// If IE 6 browser on Vista or higher (like IETester under Vista / Windows 7 for example), or IE 7 on XP do not embed
		elseif(
			$this->browser->family == 'MSIE' && floatval($this->browser->familyversion) < 8		
		)
		{
			// Scan for any file-references and adjust their path
			preg_match_all($regex_url,$filescontent,$treffer,PREG_PATTERN_ORDER);
			for($i=0;$i<count($treffer[0]);$i++)
			{
				if(
					substr(str_replace(array('"',"'"),'',$treffer[2][$i]),0,5) != 'http:' && 
					substr(str_replace(array('"',"'"),'',$treffer[2][$i]),0,6) != 'https:' && 
					substr(str_replace(array('"',"'"),'',$treffer[2][$i]),0,5) != 'data:' && 
					substr(str_replace(array('"',"'"),'',$treffer[2][$i]),0,6) != 'mhtml:'
				) $filescontent = str_replace(
					$treffer[1][$i].$treffer[2][$i].$treffer[3][$i],
					$treffer[1][$i].((!$this->css_stringmode) ? $dir : rtrim($this->css_stringbase,'/')).'/'.$treffer[2][$i].$treffer[3][$i],
					$filescontent);
			}
		}
		
		// --------------------------------------------------------------------------------------

		// If any other and (then we assume) data-URI-compatible browser
		else
		{
			if($this->browser->family == 'MSIE') preg_match_all($regex_embed_ie,$filescontent,$treffer,PREG_PATTERN_ORDER);
			else preg_match_all($regex_embed,$filescontent,$treffer,PREG_PATTERN_ORDER);
			if(!$this->debug) for($i=0;$i<count($treffer[0]);$i++)
			{
				// Calculate full image path
				$imagefile = str_replace('\\','/',dirname(__FILE__)).'/'.$dir.'/'.$treffer[1][$i].$treffer[2][$i];
				if($this->debug) $filescontent .= "/* embed-file: ".$imagefile." */\r\n";
				
				// Switch to right MIME-type
				switch(strtolower($treffer[2][$i]))
				{
					default:
					case 'gif':
					case 'jpg':
					case 'png':
						$mimetype = 'image/'.strtolower($treffer[2][$i]);
						break;
					case 'eot':
						$mimetype = 'application/vnd.ms-fontobject';
						break;
					case 'otf':
					case 'ttf':
					case 'woff':
						$mimetype = 'application/octet-stream';
						break;
				}
				
				// If image-file exists and if file-size is lower than 24 KB
				if(file_exists($imagefile) && filesize($imagefile) < 24000) $filescontent = str_replace($treffer[0][$i],'url(data:'.$mimetype.';base64,'.base64_encode(file_get_contents($imagefile)).')',$filescontent);
			}

			// Scan for any left file-references and adjust their path
			preg_match_all($regex_url,$filescontent,$treffer,PREG_PATTERN_ORDER);
			for($i=0;$i<count($treffer[0]);$i++)
			{
				if(
					substr(str_replace(array('"',"'"),'',$treffer[2][$i]),0,5) != 'http:' && 
					substr(str_replace(array('"',"'"),'',$treffer[2][$i]),0,6) != 'https:' && 
					substr(str_replace(array('"',"'"),'',$treffer[2][$i]),0,5) != 'data:' && 
					substr(str_replace(array('"',"'"),'',$treffer[2][$i]),0,6) != 'mhtml:'
				) $filescontent = str_replace(
					$treffer[1][$i].$treffer[2][$i].$treffer[3][$i],
					$treffer[1][$i].((!$this->css_stringmode) ? $dir : rtrim($this->css_stringbase,'/')).'/'.$treffer[2][$i].$treffer[3][$i],
					$filescontent);
			}
		}
		
		// --------------------------------------------------------------------------------------

		return $filescontent;
	}

    /**
     * Css_split takes a multiline CSS-string and splits it according to @var $css_totalparts and @var $css_part
     * 
     * @param  string    $filescontent contents to split
     * @return string    requested part-number of splitted content
     * @access protected 
     */
	protected function css_split($filescontent = '')
	{
		// If sum of parts is 1 or requested part-number is 0 return full string
		if($this->css_totalparts == 1 || $this->css_part == 0 || $this->css_stringmode) return $filescontent;
		// Else process string
		else
		{
			// In order for @-rule-blocks like @media-blocks, but also @font-face not to get stupidly ripped apart
			// while splitting the file into multiple parts, we need to parse it take some notes for us later.
			
			// In this array we will store some information for later when we split
			$line_infos = array();
			// As we split line-based we will take notices by lines
			$currentline = 0;
			// Here we note during the parsing if we are currently inside a block or not, and of which type
			$currentblock = '';
			// Here we note during the parsing if we are currently inside a comment or not
			$currentcomment = '';
			// Here we note during the parsing if we are currently inside a comment or not
			$comment_on = 0;
			// Here we note during the parsing if we are currently inside a property and if yes save its selector
			$currentselector = '';
			// Here we note during the parsing if we are currently inside a selector's properties
			$property_on = 0;
			// Here we note during the parsing if we are currently inside a singlequote-protected string
			$singlequote_on = 0;
			// Here we note during the parsing if we are currently inside a doublequote-protected string
			$doublequote_on = 0;
			
			// For analysis purposes we log file contents
			if($this->debug) file_put_contents($this->debug_log,"-----------------\r\ncss_split input file:\r\n-----------------\r\n".$source."\r\n-----------------\r\n",FILE_APPEND);
			
			// Now we cycle through every character
			for($i=0;$i<strlen($filescontent);$i++)
			{
				// Here we store current character and do different things depending on what it is
				$currentchar = substr($filescontent,$i,1);
				switch($currentchar)
				{
					// We run into or out of a single-quoted file-reference or generated content, remember that
					case "'":
					if($doublequote_on == 0 && $comment_on == 0) $singlequote_on = 1 - $singlequote_on;
					break;
					
					// We run into or out of a double-quoted file-reference or generated content, remember that
					case '"':
					if($singlequote_on == 0 && $comment_on == 0) $doublequote_on = 1 - $doublequote_on;
					break;
					
					// We probably ran into a comment
					case '*':
					if($singlequote_on == 0 && $doublequote_on == 0 && $comment_on == 0)
					{
						if($comment_on == 0 && substr($filescontent,$i - 1,1) == '/')
						{
							// Remember that we are inside some /*-comment
							$comment_on = 1;
							$currentcomment = 'comment';
						}
					}
					break;
					
					// We maybe leave a comment
					case '/':
					if($singlequote_on == 0 && $doublequote_on == 0 && $comment_on == 1 && substr($filescontent,$i - 1,1) == '*')
					{
						// Remember that we finished being inside some comment
						$comment_on = 0;
						$currentcomment = '';
					}
					break;
					
					// Newline
					case "\n":
					// If not yet done: store @-rule for this line
					if(!isset($line_infos[$currentline]['block'])) $line_infos[$currentline]['block'] = $currentblock;
					// Store type of comment for this line
					$line_infos[$currentline]['comment'] = $currentcomment;
					// Store selector for this line
					if(!isset($line_infos[$currentline]['selector'])) $line_infos[$currentline]['selector'] = $currentselector;
					$currentline++;
					break;
					
					// That what we are here for: is this a block-creating @-rule like @media{} or @font-face{}?
					case "@":
					if($singlequote_on == 0 && $doublequote_on == 0 && $comment_on == 0) 
					{
						if(preg_match('/\A@([^\{\}@]+)\{/ims', substr($filescontent,$i), $treffer) == 1)
						{
							// remember in what @-rule we are
							$currentblock = $treffer[1];
							// remove @-rule-opener for now (we will put it back in later)
							$filescontent = substr_replace($filescontent,'',$i,strlen($currentblock) + 2);
							// store @-rule for this line
							$line_infos[$currentline]['block'] = $currentblock;
							$i--;
						}
					}
					break;
	
					// This closing parenthesis could be closing some selector's properties or an @-rule, lets see... 
					case "}":
					if($singlequote_on == 0 && $doublequote_on == 0 && $comment_on == 0) 
					{
						// We are currently inside some selector's properties
						if($property_on == 1) 
						{
							$property_on = 0;
							// remember that we are in no @-rule
							$currentselector = '';
							// Store selector for this line
							$line_infos[$currentline]['selector'] = $currentselector;
							// Remove closing parenthesis for now (we will put it back in later)
							$filescontent = substr_replace($filescontent,'',$i,1);
							$i--;
						}
						// Or else it must be a closing parenthesis of an @-rule
						else 
						{
							// remember that we are in no @-rule
							$currentblock = '';
							// Store no-@-rule for this line
							$line_infos[$currentline]['block'] = $currentblock;
							// Remove closing parenthesis for now (we will put it back in later)
							$filescontent = substr_replace($filescontent,'',$i,1);
							$i--;
						}
					}
					break;

					default:
					if($singlequote_on == 0 && $doublequote_on == 0 && $comment_on == 0 && $property_on == 0)
					{
						if(preg_match('/\A([a-zA-Z\.#\*:][^\{\}@;\/]+)\{/ims', substr($filescontent,$i), $treffer) == 1)
						{
							// remember in what selector we are
							$currentselector = $treffer[1];
							// remove selector for now (we will put it back in later)
							$filescontent = substr_replace($filescontent,'',$i,strlen($currentselector) + 1);
							// store selector for this line
							$line_infos[$currentline]['selector'] = $currentselector;
							// Remember that we are inside some selector's properties
							$property_on = 1;
							$i--;
						}
					}
					break;
				}
			}		
			// Store one further line-entry in array
			$line_infos[$currentline] = array('block'=>$currentblock,'comment'=>$currentcomment,'selector'=>$currentselector);
			
			// For analysis purposes we log pre-parser structure findings
			if($this->debug) file_put_contents($this->debug_log,"-----------------\r\ncss_split pre-parser structure findings:\r\n".var_export($line_infos, TRUE)."\r\n".$source."\r\n-----------------\r\n",FILE_APPEND);
			
			// Finished with out pre-parsing, beginning split process ///////////////////////////////////////////////////
			
			// Split at every new line
			$filescontentlines = explode("\n",$filescontent);
			// Prepare storage for parts
			$filescontentparts = array();
			$i = 0;
			// Create all parts
			for($j=0;$j<intval($this->css_totalparts);$j++)
			{
				// Here we note during the processing if we are currently inside a block or not, and of which type
				$currentblock = '';
				// Here we note during the processing if we are currently inside a comment or not, and of which type
				$currentcomment = '';
				// Here we note during the processing if we are currently inside a property or not, and of which type
				$currentselector = '';
				// Here we will store this part's content
				$filescontentparts[$j] = '';
				// Starting to process the different parts
				while(
					(
						// If we are not building the final part, stop at around (file / total parts) length
						(
							$j != (intval($this->css_totalparts) - 1) && 
							strlen($filescontentparts[$j]) < ceil(strlen($filescontent) / $this->css_totalparts)
						) || 
						// If we are building the final part, no stop required
						$j == (intval($this->css_totalparts) - 1)
					) && 
					// Stop if there is no corresponding line in the source (like at the end for example)
					isset($filescontentlines[$i])
				)
				{
					// If a comment begins
					if($j > 0 && $line_infos[$i]['comment'] != '' && $currentcomment == '')
					{
						$filescontentparts[$j] .= '/*';
						$currentcomment = $line_infos[$i]['comment'];
					}
					// If at this place an @-rule-status should start or stop (=changes)
					if($line_infos[$i]['block'] != $currentblock) 
					{
						// If an @-rule begins
						if($line_infos[$i]['block'] != '')
						{
							if($currentblock != '') $filescontentparts[$j] .= '}';
							$filescontentparts[$j] .= '@'.$line_infos[$i]['block'].'{';
							$currentblock = $line_infos[$i]['block'];
						}
						// If an @-rule ends
						else
						{
							$filescontentparts[$j] .= '}';
							$currentblock = $line_infos[$i]['block'];
						}						
					}
					// If at this place a selector should start or stop (=changes)
					if($line_infos[$i]['selector'] != $currentselector) 
					{
						// If a selector begins
						if($line_infos[$i]['selector'] != '')
						{
							if($currentselector != '') $filescontentparts[$j] .= '}';
							$filescontentparts[$j] .= $line_infos[$i]['selector'].'{';
							$currentselector = $line_infos[$i]['selector'];
						}
						// If a selector ends
						else
						{
							$filescontentparts[$j] .= '}';
							$currentselector = $line_infos[$i]['selector'];
						}						
					}

					$filescontentparts[$j] .= $filescontentlines[$i]."\n";
					$i++;
				}

				// If at the end of this part any comment is left open, close it
				if($line_infos[$i - 1]['comment'] != '') $filescontentparts[$j] .= '*/';

				// If at the end of this part any selector is left open, close it
				if($line_infos[$i - 1]['selector'] != '') $filescontentparts[$j] .= '}';

				// If at the end of this part any @-rule-block is left open, close it
				if($line_infos[$i - 1]['block'] != '') $filescontentparts[$j] .= '}';

				// Doing a last cleanup of all the specialformatting of our pre-parser ///////////////////////////////////////////
				
				// Backup any values within single or double quotes
				preg_match_all('/(\'[^\']+\'|"[^"]+")/ims',$filescontentparts[$j],$treffer,PREG_PATTERN_ORDER);
				for($k=0;$k<count($treffer[1]);$k++)
				{
					$filescontentparts[$j] = str_replace($treffer[1][$k],'##########'.$k.'##########',$filescontentparts[$j]);
				}
				
				// Remove newline at certain points as preparation for parsing
				$filescontentparts[$j] = str_replace("\n{","{",$filescontentparts[$j]);
				$filescontentparts[$j] = str_replace("\n}","}",$filescontentparts[$j]);
				$filescontentparts[$j] = str_replace("\n/*","/*",$filescontentparts[$j]);
				$filescontentparts[$j] = str_replace("\n*/","*/",$filescontentparts[$j]);
				$filescontentparts[$j] = preg_replace('/\}[\r\n]+\}/ims','}}',$filescontentparts[$j]);
		
				// Restore backupped values within single or double quotes
				for($k=0;$k<count($treffer[1]);$k++)
				{
					$filescontentparts[$j] = str_replace('##########'.$k.'##########',$treffer[1][$k],$filescontentparts[$j]);
				}
			}
			// For analysis purposes we log split result
			if($this->debug) file_put_contents($this->debug_log,"-----------------\r\ncss_split result for part ".($j + 1).":\r\n".$filescontentparts[$this->css_part - 1]."\r\n".$source."\r\n-----------------\r\n",FILE_APPEND);

			// Return only the requested part
			return $filescontentparts[$this->css_part - 1];
		}
	}
		
    /**
     * Css fetches and optimizes all stylesheet-files
     * 
     * @return string optimized stylesheet-code
     * @access public 
     */
	public function css()
	{
		// Call Setcachedir to make sure, cache-path has been calculated
		$this->setcachedir();
		
		// Empty storage for stylesheet-contents to come
		$filescontent = '';
		// Specify file extension "css" for security reasons
		$type = 'css';
	
		// Prepare @var $sources as an array
		// if @var $css_source is an array
		if(is_array($this->css_source)) $sources = $this->css_source;
		// if @var $css_source is not an array and @var $css_stringmode is not set
		elseif(!$this->css_stringmode) $sources = explode(',',$this->css_source);
		// if @var $css_stringmode is set
		else $sources = array($this->css_source);
		
		// if @var $css_stringmode is not set: newest filedate within the source array
		if(!$this->css_stringmode) $filestime = $this->getfilestime($sources,$type,$this->css_recursive);
		// if @var $css_stringmode is set
		else $filestime = $this->css_stringtime;
		// identifier for the cache-files
		$identifier = md5(implode('',$sources));
		// Defining the cache-filename
			// If IE 6/7 on XP or IE 7 on Vista/Win7
			if(
				$this->browser->family == 'MSIE' && $this->browser->platform == 'Windows' && 
				(
					(round(floatval($this->browser->familyversion)) == 6 && floatval($this->browser->platformversion) < 6) || 
					(round(floatval($this->browser->familyversion)) == 7 && floatval($this->browser->platformversion) >= 6)
				)
			) $cachefile = $this->booster_cachedir.'/'.$identifier.'_datauri_ie_'.(($this->debug) ? 'debug_' : '').'cache.txt';
			
			
			// If IE 6 browser on Vista or higher (like IETester under Windows 7 for example), skip dataURI
			elseif(
				$this->browser->family == 'MSIE' && floatval($this->browser->familyversion) < 7 && 
				$this->browser->platform == 'Windows' && floatval($this->browser->platformversion) >= 6
			) $cachefile = $this->booster_cachedir.'/'.$identifier.'_datauri_off_'.(($this->debug) ? 'debug_' : '').'cache.txt';
			
			
			// If any other and (then we assume) data-URI-compatible browser
			else $cachefile = $this->booster_cachedir.'/'.$identifier.'_datauri_'.(($this->debug) ? 'debug_' : '').'cache.txt';

		
		
		// If that cache-file is there, fetch its contents
		if(file_exists($cachefile) && filemtime($cachefile) >= $filestime && filemtime($cachefile) >= filemtime(str_replace('\\','/',dirname(__FILE__)))) $filescontent .= file_get_contents($cachefile);
		// if that cache-file does not exist or is too old, create it
		else
		{
			reset($sources);
			for($i=0;$i<sizeof($sources);$i++)
			{
				$source = current($sources);
				// Remove any trailing slash
				$source = rtrim($source,'/');
				if($source != '')
				{
					// If current source is a folder or file, get its contents
					if(is_dir($source) || is_file($source)) $currentfilescontent = $this->getfilescontents($source,$type,$this->css_recursive);
					// If current source is already a string
					else $currentfilescontent = $source;
					
					// Optimize stylesheets with CSS Tidy
					if(!$this->debug) $currentfilescontent = $this->css_minify($currentfilescontent);
		
					// Prepare @var $dir that we need to prepend as path to any images we find to get the full path
					// if @var $css_source is a folder
					if(is_dir($source)) $dir = $source;
					// if @var $css_source is a file
					elseif(is_file($source)) $dir = dirname($source);
					// if @var $css_source is code-string
					else $dir = rtrim($this->getpath(dirname($_SERVER['SCRIPT_FILENAME']).'/'.$this->css_stringbase,str_replace('\\','/',dirname(__FILE__))),'/');
					// Embed media to save HTTP-requests
					$filescontent .= $this->css_datauri($filestime,$currentfilescontent,$dir);
				}
				next($sources);
			}
			// Write cache-file
			file_put_contents($cachefile,$filescontent);
			@chmod($cachefile,0777);
		}
		$filescontent .= "\n";
		
		// Split results up in order to have multiple parts load in parallel and get the currently requested part back
		$filescontent = $this->css_split($filescontent);

		// Return the currently requested part of the stylesheets
		return $filescontent;
	}
		
    /**
     * Mhtmltime returns the last-modified-timestamp of the MHTML-cache-file
     * 
     * @return integer timestamp of the requested MHTML-cache-file
     * @access public  
     */
	public function mhtmltime()
	{
		$mhtmlfile = $this->booster_cachedir.'/'.preg_replace('/[^a-z0-9,\-_]/i','',$this->css_source).'_datauri_mhtml_'.(($this->debug) ? 'debug_' : '').'cache.txt';
		if(file_exists($mhtmlfile)) return filemtime($mhtmlfile);
		else return 0;
	}
	
    /**
     * Mhtml reads and returns the contents of the requested MHTML-cache-file
     * 
     * @return string contents of the MHTML-cache-file
     * @access public 
     */
	public function mhtml()
	{
		$mhtmlfile = $this->booster_cachedir.'/'.preg_replace('/[^a-z0-9,\-_]/i','',$this->css_source).'_datauri_mhtml_'.(($this->debug) ? 'debug_' : '').'cache.txt';
		if(file_exists($mhtmlfile)) return file_get_contents($mhtmlfile);
		else return '';
	}
	
    /**
     * Css_markup creates HTML-<link>-tags for all CSS
     * 
     * @return string the markup
     * @access public 
     */
	public function css_markup()
	{
		// For CSS debugging we don't split the contents up
		if($this->debug) $this->css_totalparts = 1;

		// Empty storage for markup to come
		$markup = '';
		
		// Calculate possible relative path-offset created by mod_rewrite
		$booster_offset_path = rtrim($this->getpath((substr($_SERVER['REQUEST_URI'],strlen($_SERVER['REQUEST_URI']) - 1,1) == '/' ? $_SERVER['REQUEST_URI'] : dirname($_SERVER['REQUEST_URI'])),dirname($_SERVER['SCRIPT_NAME'])),'/');
		// Calculate relative path from calling script to booster-folder
		$booster_path = $booster_offset_path.'/'.$this->getpath(str_replace('\\','/',dirname(__FILE__)),dirname($_SERVER['SCRIPT_FILENAME']));
		// Calculate relative path from booster-folder to calling script
		$css_path = $this->getpath(dirname($_SERVER['SCRIPT_FILENAME']),str_replace('\\','/',dirname(__FILE__)));

		$booster_path = '/CSS-JS-Booster/booster';

		// If sources were defined as array
		if(is_array($this->css_source)) $sources = $this->css_source;
		// If sources were defined as string, convert them into an array
		else $sources = explode(',',$this->css_source);

		// Empty folder/file-storage for full pathed source-files
		$timestamp_dirs = array();
		
		// Fill folder/file-storage-array with prefixed folders/files
		reset($sources);
		for($i=0;$i<sizeof($sources);$i++) 
		{
			$sources[key($sources)] = $css_path.'/'.current($sources);
			array_push($timestamp_dirs,$booster_path.'/'.current($sources));
			next($sources);
		}
		
		// Make sure $source now ends up as string fed from $sources to use as URL-parameter
		$source = implode(',',$sources);
		// Make sure $timestamp_dir now ends up as string fed from $timestamp_dirs to use as URL-parameter
		$timestamp_dir = implode(',',$timestamp_dirs);

		// Insert IE6 fix image flicker
		if($this->browser->family == 'MSIE' && floatval($this->browser->familyversion) < 7 && $this->browser->platform == 'Windows') $markup .= '<script type="text/javascript">try {document.execCommand("BackgroundImageCache", false, true);} catch(err) {}</script>'."\r\n";
	
		// Put together the markup linking to our booster-css-files
		// Append timestamps of the $timestamp_dir to make sure browser reloads once the CSS was updated
		for($j=0;$j<intval($this->css_totalparts);$j++)
		{
			$markup .= '<link rel="'.$this->css_rel.'" media="'.$this->css_media.'" title="'.htmlentities($this->css_title,ENT_QUOTES).'" type="text/css" href="'.$booster_path.'/booster_css.php?dir='.htmlentities($source,ENT_QUOTES).'&amp;cachedir='.htmlentities($this->booster_cachedir,ENT_QUOTES).(($this->css_hosted_minifier) ? '&amp;css_hosted_minifier=1' : '').'&amp;totalparts='.intval($this->css_totalparts).'&amp;part='.($j+1).(($this->debug) ? '&amp;debug=1' : '').((!$this->js_minify) ? '&amp;js_minify=0' : '').'&amp;nocache='.$this->getfilestime($timestamp_dir,'css').'" '.(($this->css_markuptype == 'XHTML') ? '/' : '').'>'."\r\n";
		}
	
		return $markup;
	}
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	

    /**
     * Js_minify takes a JS-string and tries to minify it with the Google Closure Webservice
     * 
     * If the input JavaScript surpasses Google's POST-limit of 200.000 bytes it switches to
     * minification through Douglas Crockford's JSMin
     * 
     * @param  string    $filescontent contents to minify
     * @return string    minified Javascript
     * @access protected 
     */
	protected function js_minify($filescontent = '')
	{
		if ($this->js_hosted_minifier !== FALSE)
		{
			// the webserver must have read right on the jar, again, no test done :)
			$this->js_hosted_minifier_path = realpath(dirname(__FILE__).'/compiler/compiler.jar');
			// must create tmp files because closure compiler can't work with direct input..
			$tmp_file_path = sys_get_temp_dir().'/'.uniqid();
			file_put_contents($tmp_file_path, $filescontent);
			$js_minified = `java -jar $this->js_hosted_minifier_path --charset utf-8 --js $tmp_file_path`;
			unlink($tmp_file_path);
		} 
		else
		{
			// URL-encoded file contents (Thanks vvo!)
			$filescontent_urlencoded = urlencode($filescontent);

			// Google Closure has a max limit of 200KB POST size, and will break JS with eval-command
			if(strlen($filescontent_urlencoded) < 200000 && preg_match('/[^a-z]eval\(/ism',$filescontent) == 0)
			{
				// Working vars
				$js_minified = '';
				$host = "closure-compiler.appspot.com";
				$service_uri = "/compile";
				$vars = 'js_code='.$filescontent_urlencoded.'&compilation_level=SIMPLE_OPTIMIZATIONS&output_format=text&output_info=compiled_code';

				// Compose HTTP request header
				$header = "Host: $host\r\n";
				$header .= "User-Agent: PHP Script\r\n";
				$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$header .= "Content-Length: ".strlen($vars)."\r\n";
				$header .= "Connection: close\r\n\r\n";

				$fp = pfsockopen($host, 80, $errno, $errstr);
				// If we cannot open connection to Google Closure
				if(!$fp) $js_minified = $filescontent;
				else
				{
					fputs($fp, "POST $service_uri  HTTP/1.0\r\n");
					fputs($fp, $header.$vars);
					while (!feof($fp)) {
						$js_minified .= fgets($fp);
					}
					fclose($fp);
					$js_minified = preg_replace('/^HTTP.+[\r\n]{2}/ims','',$js_minified);
				}
			}
			// Switching over to Douglas Crockford's JSMin (which in turn breaks IE's conditional compilation)
			else
			{
				/**
				 * Inclusion of JSMin
				 */
				include_once('jsmin/jsmin.php');
				$js_minified = JSMin::minify($filescontent);
			}
		}
		
		return $js_minified;
	}
	
    /**
     * Js fetches and optimizes all javascript-files
     * 
     * @return string optimized javascript-code
     * @access public 
     */
	public function js()
	{
		// Call Setcachedir to make sure, cache-path has been calculated
		$this->setcachedir();

		// Empty storage for javascript-contents to come
		$filescontent = '';
		// Specify file extension "js" for security reasons
		$type = 'js';
		
	
		// Prepare @var $sources as an array
		// if @var $js_source is an array
		if(is_array($this->js_source)) $sources = $this->js_source;
		// if @var $js_source is not an array and @var $js_stringmode is not set
		elseif(!$this->js_stringmode) $sources = explode(',',$this->js_source);
		// if @var $js_stringmode is set
		else $sources = array($this->js_source);
		
		
		// if @var $js_stringmode is not set: newest filedate within the source array
		if(!$this->js_stringmode) $filestime = $this->getfilestime($sources,$type,$this->js_recursive);
		// if @var $js_stringmode is set
		else $filestime = $this->js_stringtime;
		// identifier for the cache-files
		$identifier = md5(implode('',$sources));


		// Defining the cache-filename
		$cachefile = $this->booster_cachedir.'/'.$identifier.'_js_'.(($this->debug) ? 'debug_' : '').'cache.txt';

		// If cache-file exists and cache-file date is newer than code-date, read from there
		if(file_exists($cachefile) && filemtime($cachefile) >= $filestime && filemtime($cachefile) >= filemtime(str_replace('\\','/',dirname(__FILE__)))) $filescontent .= file_get_contents($cachefile);
		// There is no cache-file or it is outdated, create it
		else 
		{
			reset($sources);
			for($i=0;$i<sizeof($sources);$i++)
			{
				$source = current($sources);
				// Remove any trailing slash
				$source = rtrim($source,'/');
				
				// If current source is a folder or file, get its contents
				if(is_dir($source) || is_file($source)) $filescontent .= $this->getfilescontents($source,$type,$this->js_recursive);
				// If current source is already a string
				else $filescontent .= $source;

				next($sources);
			}
			// Minify
			if(!$this->debug && $this->js_minify) $filescontent = $this->js_minify($filescontent);

			// Write cache-file
			file_put_contents($cachefile,$filescontent);
			@chmod($cachefile,0777);
		}
		$filescontent .= "\n";

		// Return the currently requested part of the javascript
		return $filescontent;
	}

    /**
     * Js_markup creates HTML-<script>-tags for all JS
     * 
     * @return string the markup
     * @access public 
     */
	public function js_markup()
	{
		// Empty storage for markup to come
		$markup = '';

		// Calculate possible relative path-offset created by mod_rewrite
		$booster_offset_path = rtrim($this->getpath((substr($_SERVER['REQUEST_URI'],strlen($_SERVER['REQUEST_URI']) - 1,1) == '/' ? $_SERVER['REQUEST_URI'] : dirname($_SERVER['REQUEST_URI'])),dirname($_SERVER['SCRIPT_NAME'])),'/');
		// Calculate relative path from calling script to booster-folder
		$booster_path = $booster_offset_path.'/'.$this->getpath(str_replace('\\','/',dirname(__FILE__)),dirname($_SERVER['SCRIPT_FILENAME']));
		// Calculate relative path from booster-folder to calling script
		$js_path = $this->getpath(dirname($_SERVER['SCRIPT_FILENAME']),str_replace('\\','/',dirname(__FILE__)));

		$booster_path = '/CSS-JS-Booster/booster';

		// If sources were defined as array
		if(is_array($this->js_source)) $sources = $this->js_source;
		// If sources were defined as string, convert them into an array
		else $sources = explode(',',$this->js_source);

		// Empty folder/file-storage for full pathed source-files
		$timestamp_dirs = array();

		// Fill folder/file-storage-array with prefixed folders/files
		reset($sources);
		for($i=0;$i<sizeof($sources);$i++) 
		{
			$sources[key($sources)] = $js_path.'/'.current($sources);
			array_push($timestamp_dirs,$booster_path.'/'.current($sources));
			next($sources);
		}

		// Make sure $source now ends up as string fed from $sources to use as URL-parameter
		$source = implode(',',$sources);
		// Make sure $timestamp_dir now ends up as string fed from $timestamp_dirs to use as URL-parameter
		$timestamp_dir = implode(',',$timestamp_dirs);

		// Put together the markup linking to our booster-js-files
		// Append timestamps of the $timestamp_dir to make sure browser reloads once the JS was updated
		$markup .= '<script defer="defer" async="async" type="text/javascript" src="'.$booster_path.'/booster_js.php?dir='.htmlentities($source,ENT_QUOTES).'&amp;cachedir='.htmlentities($this->booster_cachedir,ENT_QUOTES).(($this->js_hosted_minifier) ? '&amp;js_hosted_minifier=1' : '').(($this->debug) ? '&amp;debug=1' : '').((!$this->js_minify) ? '&amp;js_minify=0' : '').'&amp;nocache='.$this->getfilestime($timestamp_dir,'js').'"></script>'."\r\n";

		return $markup;
	}

	protected function is_css($filename)
	{
		$finfo = new finfo(FILEINFO_MIME);

		// easy check, the extension
		$path_parts = pathinfo($filename);
		if($path_parts['extension'] !== 'css') return false;

		// hard check, mime type
		$finfo = new finfo(FILEINFO_MIME);
		if (!$finfo)
		{
			// can't get the mime type, abort
			return false;
		} else
		{
			if (strpos($finfo->file($filename), 'text/x-c') !== false)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	protected function is_js($filename)
	{
		$finfo = new finfo(FILEINFO_MIME);

		// easy check, the extension
		$path_parts = pathinfo($filename);
		if($path_parts['extension'] !== 'js') return false;

		// hard check, mime type
		$finfo = new finfo(FILEINFO_MIME);
		if (!$finfo)
		{
			// can't get the mime type, abort
			return false;
		} else
		{
			if (strpos($finfo->file($filename), 'text/plain') !== false)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}
}
?>