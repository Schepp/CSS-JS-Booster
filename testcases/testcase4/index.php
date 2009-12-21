<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Array Source Testcase</title>
<style type="text/css">
<?php 
include('../../booster/booster_inc.php'); 
$booster = new Booster();
$booster->css_stringmode = TRUE;
$booster->css_source = '.div1 {
	display: block;
	width: 400px;
	height: 100px;
	margin: 10px;
	background-image: url(images/normalstate.gif);
	background-repeat: repeat-x;
	color: #F66;
	font-family: Arial, Helvetica, sans-serif;
	font-size: 16px;
	font-weight: bold;
	border: #CCC 1px solid;
}

.div1:hover {
	background-image: url(images/hoverstate.gif);
	color: #66F;
}

.div2 {
	display: block;
	width: 400px;
	height: 100px;
	margin: 10px;
	background-image: url(images/bothstates.gif);
	background-repeat: repeat-x;
	background-position: left top;
	color: #F66;
	font-family: Arial, Helvetica, sans-serif;
	font-size: 16px;
	font-weight: bold;
	border: #CCC 1px solid;
}

.div2:hover {
	background-position: left bottom;
	color: #66F;
}';
#$booster->css_totalparts = 1;
$booster->debug = TRUE;
echo $booster->css(); 
?>
</style>
</head>
<body>
	<a class="div1">Link 1</a>
	<a class="div2">Link 2</a>
	<div class="div1">Div 1</div>
	<div class="div2">Div 2</div>
</body>
</html>
