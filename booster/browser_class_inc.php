<?php

/**
 * Class to detect which browser is currently accessing the page/site
 * @author Christian "Schepp" Schaefer
 * This class is loosely based on scripts by Paul Scott, which in turn is very loosely based on scripts by Gary White
 * @package browser
 */

class browser 
{

	/**
	 * @var string $useragent
	 */
	public $useragent = NULL;


	/**
	 * @var string $name Specific browser name, e.g. "Mozilla", "Firefox", "Netscape", "Flock", "Avant Browser", "Safari", "Chrome", etc.
	 */
	public $name = "";


	/**
	 * @var float $version This browser's version
	 */
	public $version = 0;


	/**
	 * @var string $family Family group browser belongs to, e.g. "Firefox", "MSIE", "WebKit", etc.
	 */
	public $family = "";


	/**
	 * @var float $familyversion Family group version, if applicable
	 */
	public $familyversion = "";


	/**
	 * @var string $engine Engine the browser uses, if applicable, e.g. "Gecko"
	 */
	public $engine = "";


	/**
	 * @var float $engineversion Engine version
	 */
	public $engineversion = "";


	/**
	 * @var string $platform Which OS-platform the client is running, e.g. "Windows", "Macintosh", "Linux", "Unix", "Windows CE", etc.
	 */
	public $platform = "";


	/**
	 * @var float $platformversion Which OS-version if detectable (mostly the Windows-versions) "4.0" (NT), "5.0" (W2K), "5.1" (XP), "6.0" (Vista), "6.1" (Win7), etc.
	 */
	public $platformversion = 0;


	/**
	 * @var string $platformtype "desktop"-browser or "mobile"
	 */
	public $platformtype = "desktop";


	/**
	 * Class constructor
	 * @return void
	 */
	function __construct()
	{
		//set the useragent property
		$this->useragent = $_SERVER['HTTP_USER_AGENT'];
		$this->getPlatform();
		$this->getBrowser();
	}


	/**
	 * getPlatform
	 * Method to get the platform details from the USER_AGENT string
	 * @return void
	 */
	private function getPlatform()
	{
		// Check for known strings for OSes
		// Windows
		if(preg_match("/windows/i", $this->useragent) == 1)
		{
			$this->platform = "Windows";
			if(preg_match('/Windows NT\s*([0-9\.]+)/i',$this->useragent,$match) > 0) $this->platformversion = floatval($match[1]); // NT4, W2K, XP, Vista, Win7...
			elseif(preg_match('/Windows\s*([0-9\.]+)/i',$this->useragent,$match) > 0) $this->platformversion = floatval($match[1]); // Windows 3.1/3.11
			elseif(preg_match('/Windows XP/i',$this->useragent) > 0) $this->platformversion = 5.1;
			elseif(preg_match('/Windows 95/i',$this->useragent) > 0) $this->platformversion = 4;
			elseif(preg_match('/Windows 98/i',$this->useragent) > 0) $this->platformversion = 4.1;
			elseif(preg_match('/Windows ME/i',$this->useragent) > 0) $this->platformversion = 4.9;
			elseif(preg_match('/Windows CE/i',$this->useragent) > 0) 
			{
				$this->platform = "Windows CE";
				if(preg_match('/Windows Mobile\s*([0-9\.]+)/i',$this->useragent,$match) > 0) $this->platformversion = floatval($match[1]);
				elseif(preg_match('/Windows Phone\s*([0-9\.]+)/i',$this->useragent,$match) > 0) $this->platformversion = floatval($match[1]);
				elseif(preg_match('/Windows CE\s*([0-9\.]+)/i',$this->useragent,$match) > 0) $this->platformversion = floatval($match[1]);
				$this->platformtype = "mobile";
			}
		}
		// Linux
		elseif (preg_match("/linux/i", $this->useragent) == 1) 
		{
			$this->platform = "Linux";
			if(preg_match('/linux arm/',$this->useragent) > 0) 
			{
				$this->platformtype = "mobile";
			}
		}
		// Mac
		elseif(preg_match("/mac/i", $this->useragent) == 1) $this->platform = "Macintosh"; 
		// Unixes
		elseif(preg_match("/freebsd/i", $this->useragent) == 1) $this->platform = "FreeBSD"; 
		elseif(preg_match("/openbsd/i", $this->useragent) == 1) $this->platform = "OpenBSD"; 
		elseif(preg_match("/solaris/i", $this->useragent) == 1 || preg_match("/sunos/i", $this->useragent) == 1) $this->platform = "Solaris"; 
		// Consoles
		elseif(preg_match("/nintendo wii/i", $this->useragent) == 1) 
		{
			$this->platform = "Nintendo Wii"; 
			$this->platformtype = "mobile";
		}
		elseif(preg_match("/playstation 3/i", $this->useragent) == 1) 
		{
			$this->platform = "Playstation 3";
			$this->platformtype = "mobile";
			if(preg_match('/playstation 3[);\s]+([0-9\.]+)/i',$this->useragent,$match) > 0) $this->platformversion = floatval($match[1]);
		}
		elseif(preg_match("/playstation portable/i", $this->useragent) == 1) 
		{
			$this->platform = "Playstation Portable";
			$this->platformtype = "mobile";
			if(preg_match('/Playstation Portable[);\s]+([0-9\.]+)/i',$this->useragent,$match) > 0) $this->platformversion = floatval($match[1]);
		}
		// Mobile only Devices
		// TODO: This should be a public class variable for easy extension/updating
		else
		{
			$mobileAgents = array(
			'Android',
			'Blackberry',
			'Blazer',
			'Handspring',
			'iPhone',
			'iPod',
			'Kyocera',
			'LG',
			'Motorola',
			'Nokia',
			'Palm',
			'PlayStation Portable',
			'Samsung',
			'Smartphone',
			'SonyEricsson',
			'Symbian',
			'WAP'
			);
			for($i=0;$i<count($mobileAgents);$i++)
			{
				if(preg_match("/".$mobileAgents[$i]."/i", $this->useragent) == 1)
				{
					$this->platform = $mobileAgents[$i];
					$this->platformtype = "mobile";
					break;
				} 
			}
		}
	}


	/**
	 * Method to get the browser details from the USER_AGENT string
	 * @return string $property platform 
	 */
	private function getBrowser()
	{
		// Check for known strings for browsers

		// Check for Opera-family
		if(preg_match("/opera mini/i", $this->useragent) == 1)
		{
			$this->name = "Opera Mini";
			if(preg_match('/Opera Mini[\/\s]([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "Opera";
			if(preg_match('/Opera[\/\s]([0-9\.]+)/i',$this->useragent,$match) > 0) $this->familyversion = floatval($match[1]);
			$this->engine = $this->family;
			$this->engineversion = $this->familyversion;
			$this->platformtype = "mobile";
		}
		elseif(preg_match("/opera/i", $this->useragent) == 1)
		{
			$this->name = "Opera";
			if(preg_match('/Opera[\/\s]([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = $this->name;
			$this->familyversion = $this->version;
			$this->engine = $this->family;
			$this->engineversion = $this->familyversion;
		}

		// Check for Gecko/Firefox-family
		elseif(preg_match("/navigator/i", $this->useragent) == 1)
		{
			$this->name = "Netscape";
			if(preg_match('/Navigator\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "Firefox";
			if(preg_match('/Firefox\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->familyversion = floatval($match[1]);
			$this->engine = "Gecko";
			if(preg_match('/rv:([0-9\.]+)/i',$this->useragent,$match) > 0) $this->engineversion = floatval($match[1]);
		}
		elseif(preg_match("/flock/i", $this->useragent) == 1)
		{
			$this->name = "Flock";
			if(preg_match('/Flock\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "Firefox";
			if(preg_match('/Firefox\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->familyversion = floatval($match[1]);
			$this->engine = "Gecko";
			if(preg_match('/rv:([0-9\.]+)/i',$this->useragent,$match) > 0) $this->engineversion = floatval($match[1]);
		}
		elseif(preg_match("/mozilla/i", $this->useragent) == 1)
		{
			$this->name = "Mozilla";
			if(preg_match('/rv:([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "Firefox";
			$this->engine = "Gecko";
			if(preg_match('/rv:([0-9\.]+)/i',$this->useragent,$match) > 0) $this->engineversion = floatval($match[1]);
		}
		elseif(preg_match("/minimo/i", $this->useragent) == 1)
		{
			$this->name = "Minimo";
			if(preg_match('/Minimo\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "Firefox";
			$this->engine = "Gecko";
			if(preg_match('/rv:([0-9\.]+)/i',$this->useragent,$match) > 0) $this->engineversion = floatval($match[1]);
		}
		elseif(preg_match("/fennec/i", $this->useragent) == 1)
		{
			$this->name = "Fennec";
			if(preg_match('/Fennec\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "Firefox";
			$this->engine = "Gecko";
			if(preg_match('/rv:([0-9\.]+)/i',$this->useragent,$match) > 0) $this->engineversion = floatval($match[1]);
		}
		elseif(preg_match("/minimo/i", $this->useragent) == 1)
		{
			$this->name = "Minimo";
			if(preg_match('/Minimo\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "Firefox";
			$this->engine = "Gecko";
			if(preg_match('/rv:([0-9\.]+)/i',$this->useragent,$match) > 0) $this->engineversion = floatval($match[1]);
		}
		if(preg_match("/minefield/i", $this->useragent) == 1)
		{
			$this->name = "Minefield";
			if(preg_match('/Minefield\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "Firefox";
			$this->engine = "Gecko";
			if(preg_match('/rv:([0-9\.]+)/i',$this->useragent,$match) > 0) $this->engineversion = floatval($match[1]);
		}
		elseif(preg_match("/firebird/i", $this->useragent) == 1)
		{
			$this->name = "Firebird";
			if(preg_match('/Firebird\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "Firefox";
			$this->engine = "Gecko";
			if(preg_match('/rv:([0-9\.]+)/i',$this->useragent,$match) > 0) $this->engineversion = floatval($match[1]);
		}
		elseif(preg_match("/k-meleon/i", $this->useragent) == 1)
		{
			$this->name = "K-Meleon";
			if(preg_match('/K-Meleon\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "Firefox";
			$this->engine = "Gecko";
			if(preg_match('/rv:([0-9\.]+)/i',$this->useragent,$match) > 0) $this->engineversion = floatval($match[1]);
		}
		elseif(preg_match("/seamonkey/i", $this->useragent) == 1)
		{
			$this->name = "Seamonkey";
			if(preg_match('/Seamonkey[\/-]([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "Firefox";
			$this->engine = "Gecko";
			if(preg_match('/rv:([0-9\.]+)/i',$this->useragent,$match) > 0) $this->engineversion = floatval($match[1]);
		}
		elseif(preg_match("/orca/i", $this->useragent) == 1)
		{
			$this->name = "Orca";
			if(preg_match('/Orca\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "Firefox";
			$this->engine = "Gecko";
			if(preg_match('/rv:([0-9\.]+)/i',$this->useragent,$match) > 0) $this->engineversion = floatval($match[1]);
		}
		elseif(preg_match("/firefox/i", $this->useragent) == 1)
		{
			$this->name = "Firefox";
			if(preg_match('/Firefox\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "Firefox";
			if(preg_match('/Firefox\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->familyversion = floatval($match[1]);
			$this->engine = "Gecko";
			if(preg_match('/rv:([0-9\.]+)/i',$this->useragent,$match) > 0) $this->engineversion = floatval($match[1]);
		}
		elseif(preg_match("/gecko/i", $this->useragent) == 1)
		{
			$this->name = "Gecko";
			if(preg_match('/rv:([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "Firefox";
			$this->engine = "Gecko";
			if(preg_match('/rv:([0-9\.]+)/i',$this->useragent,$match) > 0) $this->engineversion = $this->version;
		}
		// Check for WebKit-family
		elseif(preg_match("/safari/i", $this->useragent) == 1)
		{
			$this->name = "Safari";
			if(preg_match('/Version\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "WebKit";
			if(preg_match('/WebKit\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->familyversion = floatval($match[1]);
			$this->engine = $this->family;
			$this->engineversion = $this->familyversion;
		}
		elseif(preg_match("/chromeframe/i", $this->useragent) == 1)
		{
			$this->name = "Chromeframe";
			if(preg_match('/chromeframe\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "WebKit";
			$this->engine = $this->family;
		}
		elseif(preg_match("/chrome/i", $this->useragent) == 1)
		{
			$this->name = "Chrome";
			if(preg_match('/Chrome\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "WebKit";
			if(preg_match('/WebKit\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->familyversion = floatval($match[1]);
			$this->engine = $this->family;
			$this->engineversion = $this->familyversion;
		}
		elseif(preg_match("/omniweb/i", $this->useragent) == 1)
		{
			$this->name = "Omniweb";
			if(preg_match('/Omniweb\/v([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "WebKit";
			if(preg_match('/WebKit\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->familyversion = floatval($match[1]);
			$this->engine = $this->family;
			$this->engineversion = $this->familyversion;
		}
		elseif(preg_match("/shiira/i", $this->useragent) == 1)
		{
			$this->name = "Shiira";
			if(preg_match('/Shiira\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "WebKit";
			if(preg_match('/WebKit\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->familyversion = floatval($match[1]);
			$this->engine = $this->family;
			$this->engineversion = $this->familyversion;
		}
		elseif(preg_match("/arora/i", $this->useragent) == 1)
		{
			$this->name = "Arora";
			if(preg_match('/Arora\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "WebKit";
			if(preg_match('/WebKit\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->familyversion = floatval($match[1]);
			$this->engine = $this->family;
			$this->engineversion = $this->familyversion;
		}
		elseif(preg_match("/midori/i", $this->useragent) == 1)
		{
			$this->name = "Midori";
			if(preg_match('/Midori\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "WebKit";
			if(preg_match('/WebKit\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->familyversion = floatval($match[1]);
			$this->engine = $this->family;
			$this->engineversion = $this->familyversion;
		}
		elseif(preg_match("/icab/i", $this->useragent) == 1)
		{
			$this->name = "iCab";
			if(preg_match('/iCab\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			if($this->version >= 4) $this->family = "WebKit";
			else
			{
				$this->family = "iCab";
				$this->familyversion = $this->version;
			}
			$this->engine = $this->family;
			$this->engineversion = $this->familyversion;
		}
		elseif(preg_match("/webkit/i", $this->useragent) == 1)
		{
			$this->name = "WebKit";
			if(preg_match('/WebKit\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = $this->name;
			$this->familyversion = $this->version;
			$this->engine = $this->family;
			$this->engineversion = $this->familyversion;
		}
		// Check for KHTML-family
		elseif(preg_match("/konqueror/i", $this->useragent) == 1)
		{
			$this->name = "Konqueror";
			if(preg_match('/Konqueror\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "KHTML";
			if(preg_match('/KHTML\/([0-9\.]+)/i',$this->useragent,$match) > 0) $this->familyversion = floatval($match[1]);
			$this->engine = $this->family;
			$this->engineversion = $this->familyversion;
		}
		// Check for MSIE-family
		elseif(preg_match("/aol/i", $this->useragent) == 1)
		{
			$this->name = "AOL";
			if(preg_match('/aol\s([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "MSIE";
			if(preg_match('/MSIE\s*([0-9\.]+)/i',$this->useragent,$match) > 0) $this->familyversion = floatval($match[1]);
			$this->engine = $this->name;
			$this->engineversion = $this->version;
		}
		elseif(preg_match("/avant browser/i", $this->useragent) == 1)
		{
			$this->name = "Avant Browser";
			if(preg_match('/maxthon\s*([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "MSIE";
			if(preg_match('/MSIE\s*([0-9\.]+)/i',$this->useragent,$match) > 0) $this->familyversion = floatval($match[1]);
			$this->engine = $this->name;
			$this->engineversion = $this->version;
		}
		elseif(preg_match("/maxthon/i", $this->useragent) == 1)
		{
			$this->name = "Maxthon";
			if(preg_match('/maxthon\s*([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = "MSIE";
			if(preg_match('/MSIE\s*([0-9\.]+)/i',$this->useragent,$match) > 0) $this->familyversion = floatval($match[1]);
			$this->engine = $this->name;
			$this->engineversion = $this->version;
		}
		elseif(preg_match("/msie/i", $this->useragent) == 1)
		{
			$this->name = "MSIE";
			if(preg_match('/MSIE\s*([0-9\.]+)/i',$this->useragent,$match) > 0) $this->version = floatval($match[1]);
			$this->family = $this->name;
			$this->familyversion = $this->version;
			$this->engine = $this->name;
			$this->engineversion = $this->version;
		}
	}
}


?>