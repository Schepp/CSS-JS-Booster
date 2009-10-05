<?php  
header("Cache-Control: max-age=2592000");
header("Expires: ".gmdate('D, d M Y H:i:s', mktime(date('h') + (24 * 30)))." GMT");
header("Content-type: text/plain"); 

include('booster_inc.php');

((isset($_GET['dir'])) ? $dir = rtrim(preg_replace('/[^a-zA-Z\.\/]/','',$_GET['dir']),'/') : $dir = 'css');
$mhtmlfile = 'booster_cache/'.preg_replace('/[^a-z]/i','',$dir).'_datauri_mhtml_cache.txt';

if(!file_exists($mhtmlfile)) booster_css('../'.$dir);
if(file_exists($mhtmlfile)) echo file_get_contents($mhtmlfile);
?>