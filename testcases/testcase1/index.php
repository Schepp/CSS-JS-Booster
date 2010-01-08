<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Vista IE7 Hover Testcase</title>
<?php 
ini_set("display_errors", 1);
error_reporting(2048);
include('../../booster/booster_inc.php'); 
$booster = new Booster();
?>
<?php 
$booster->css_source = 'css';
echo $booster->css_markup(); 
?>
</head>
<body>
	<a href="index2.php">Index2</a>
	<a class="div1">Link 1</a>
	<a class="div2">Link 2</a>
	<div class="div1">Div 1</div>
	<div class="div2">Div 2</div>
</body>
</html>
