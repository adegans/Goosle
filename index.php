<?php 
if(!defined('ABSPATH')) define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

require ABSPATH."functions/tools.php";

$opts = load_opts();
$auth = (isset($_GET['a'])) ? sanitize($_GET['a']) : $opts->user_auth;
/* ------------------------------------------------------------------------------------
*  Goosle - A meta search engine for private and fast internet fun.
*
*  COPYRIGHT NOTICE
*  Copyright 2023-2024 Arnan de Gans. All Rights Reserved.
*
*  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
*  By using this code you agree to indemnify Arnan de Gans from any 
*  liability that might arise from its use.
------------------------------------------------------------------------------------ */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Goosle Search</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Get your Goosle on! - The best meta search engine for private and fast internet fun!" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo get_base_url($opts->siteurl); ?>" />

    <link rel="stylesheet" type="text/css" href="assets/css/styles.css"/>
</head>

<body class="startpage">
<?php
if(verify_hash($opts, $auth)) {
?>

<div class="startpage-search">
	<h1><span class="G">G</span>oosle</h1>
    
    <form action="results.php" method="get" autocomplete="off">
        <input tabindex="10" type="search" class="search" name="q" autofocus />
        <input type="hidden" name="a" value="<?php echo $opts->hash; ?>"/>

        <div class="search-buttons">
	        <button tabindex="20" name="t" value="0" type="submit">Web search</button>

	        <?php if($opts->enable_image_search == "on") { ?>
	        <button tabindex="40" name="t" value="1" type="submit">Image search</button>
	        <?php } ?>

	        <?php if($opts->enable_magnet_search == "on") { ?>
	        <button tabindex="50" name="t" value="9" type="submit">Magnet search</button>
	        <?php } ?>
        </div>
    </form>
</div>

<?php if($opts->password_generator == "on") { ?>
<div class="password-generator">
	<form method="get" action="./" autocomplete="off">
		Password Generator<br/><input class="password" type="text" name="pw" maxlength="27" value="<?php echo string_generator(); ?>" autocomplete="0" />
	</form>
</div>
<?php } ?>

<?php 
} else {
	echo "<div class=\"auth-error\">Goosle</div>";
} 
?>
</body>
</html>