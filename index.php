<?php include('booster/booster_inc.php'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Booster Test</title>
<?php echo booster_css_markup('css,css2'); ?>
<?php echo booster_css_markup('css,css2','print'); ?>
</head>
<body>
<?php echo booster_js_markup(); ?>
<?php echo (microtime(TRUE) - $starttime).' ms'; ?>
</body>
</html>
