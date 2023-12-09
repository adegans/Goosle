<?php 
require "functions/tools.php";
require "functions/search_engine.php";

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
<!DOCTYPE html >
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta charset="UTF-8"/>
    <meta name="description" content="Goosle - A meta search engine for private and fast internet fun!"/>
    <meta name="referrer" content="no-referrer"/>
	<link rel="apple-touch-icon" href="apple-touch-icon.png">
	<link rel="icon" href="favicon.ico" type="image/x-icon"/>
    <link rel="stylesheet" type="text/css" href="assets/css/styles.css"/>
    <title>Goosle Search</title>
</head>
<body class="main">
<?php
if(verify_hash($opts, $auth)) {
?>
<div class="wrap">
	<div class="search-box-main">
	    <form action="results.php" method="get" autocomplete="off">
	        <h1><span class="G">G</span>oosle</h1>
	
	        <input tabindex="10" type="text" class="search" name="q" autofocus required />

	        <input type="hidden" name="t" value="0"/>
	        <input type="hidden" name="a" value="<?php echo $opts->hash; ?>"/>
	
	        <div class="search-box-buttons">
		        <button tabindex="20" name="t" value="0" type="submit">DuckDuckGo</button>
		        <button tabindex="30" name="t" value="1" type="submit">Google</button>
		        <?php if($opts->enable_image_search == "on") { ?>
		        <button tabindex="40" name="t" value="2" type="submit">Image</button>
		        <?php } ?>
		        <?php if($opts->enable_torrent_search == "on") { ?>
		        <button tabindex="50" name="t" value="9" type="submit">Torrent</button>
		        <?php } ?>
	        </div>
	
	    </form>
	</div>

	<?php if($opts->special['password_generator'] == "on") { ?>
	<div class="password-generator">
		<form method="get" action="./" autocomplete="off">
			Password Generator:<br/><input class="password" type="text" name="pw" maxlength="27" value="<?php echo string_generator(); ?>" autocomplete="0" />
		</form>
	</div>
	<?php } ?>
</div>
<?php 
} else {
	echo "<div class=\"auth-error\">Goosle</div>";
} 
?>
</body>
</html>