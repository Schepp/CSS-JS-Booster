<?php  
header("Cache-Control: max-age=2592000");
header("Expires: ".gmdate('D, d M Y H:i:s', mktime(date('h') + (24 * 30)))." GMT");
header("Content-type: text/css"); 

include('booster_inc.php');

((isset($_GET['dir'])) ? $dir = rtrim(preg_replace('/[^a-zA-Z0-9,\.\/]/','',$_GET['dir']),'/') : $dir = 'css');
((isset($_GET['totalparts'])) ? $totalparts = intval($_GET['totalparts']) : $totalparts = 1);
((isset($_GET['part'])) ? $part = intval($_GET['part']) : $part = 0);

echo booster_css('../'.$dir,$totalparts,$part);
?>