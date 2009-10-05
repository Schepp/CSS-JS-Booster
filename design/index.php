<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Image List</title>
</head>
<body>
<?php
include('../_system/functions.php');
$imagePath = $config['baselocation'].'design';
$imageFiles = ListFiles($imagePath);
for($i=0;$i<count($imageFiles);$i++)
{
	$imageFile = $imagePath.'/'.$imageFiles[$i];
	$imageProperties = getimagesize($imageFile);
	echo '<img src="'.$config['basepath'].'design/'.$imageFiles[$i].'" width="'.$imageProperties[0].'" height="'.$imageProperties[1].'" vspace="5" /><br />';
}
?>
</body>
</html>
