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
    <meta name="description" content="A private meta search engine!"/>
    <meta name="referrer" content="no-referrer"/>
	<link rel="apple-touch-icon" href="apple-touch-icon.png">
	<link rel="icon" href="favicon.ico" type="image/x-icon"/>
    <link rel="stylesheet" type="text/css" href="assets/css/styles.css"/>
	<title><?php echo $opts->query; ?> - Goosle Search</title>
</head>
<body>
<?php
if(verify_hash($opts, $auth)) {
?>
<div class="wrap">
	<div class="header-wrap">
		<form action="results.php" method="get" autocomplete="off">
		    <h1 class="logo"><a class="no-decoration" href="./?a=<?php echo $opts->hash; ?>"><span class="G">G</span>oosle</a></h1>        
		    <input tabindex="1" class="search" type="text" value="<?php echo (strlen($opts->query) > 0) ? htmlspecialchars($opts->query) : "" ; ?>" name="q" required /><input tabindex="2" class="button" type="submit" value="Search" />
		
	        <input type="hidden" name="t" value="<?php echo $opts->type; ?>"/>
		    <input type="hidden" name="a" value="<?php echo $opts->hash; ?>">
	 
	        <div class="navigation-header">
		        <a <?php echo ($opts->type == "0") ? "class=\"active\" " : ""; ?> href="./results.php?q=<?php echo urlencode($opts->query); ?>&a=<?php echo $opts->hash; ?>&t=0"><img src="assets/images/search.png" alt="DuckDuckGo results" />DuckDuckGo</a>
		        <a <?php echo ($opts->type == "1") ? "class=\"active\" " : ""; ?> href="./results.php?q=<?php echo urlencode($opts->query); ?>&a=<?php echo $opts->hash; ?>&t=1"><img src="assets/images/search.png" alt="Google results" />Google</a>
		        <?php if($opts->enable_image_search == "on") { ?>
		        <a <?php echo ($opts->type == "2") ? "class=\"active\" " : ""; ?> href="./results.php?q=<?php echo urlencode($opts->query); ?>&a=<?php echo $opts->hash; ?>&t=2"><img src="assets/images/image.png" alt="Image results" />Image</a>
		        <?php } ?>
		        <?php if($opts->enable_torrent_search == "on") { ?>
		        <a <?php echo ($opts->type == "9") ? "class=\"active\" " : ""; ?> href="./results.php?q=<?php echo urlencode($opts->query); ?>&a=<?php echo $opts->hash; ?>&t=9"><img src="assets/images/torrent.png" alt="Torrent results" />Torrent</a>
		        <?php } ?>
	       </div>
		</form>
	</div>
	
	<div class="results-wrap">
    <?php fetch_search_results($opts); ?>
	</div>
</div>

<div class="footer-wrap">
	<div class="footer">
		&copy; <?php echo date('Y'); ?> <a href="https://github.com/adegans/Goosle/" target="_blank">Goosle <?php echo $opts->version; ?></a>, by <a href="https://ajdg.solutions/" target="_blank">Arnan de Gans</a>.
		<span style="float:right;"><a href="./?a=<?php echo $opts->hash; ?>">Start</a> - <a href="./help.php?a=<?php echo $opts->hash; ?>">Help</a> - Your IP: <?php echo $_SERVER["REMOTE_ADDR"]; ?></span>
	</div>
</div>

<?php 
} else {
	echo "<div class=\"auth-error\">Goosle</div>";
} 
?>
</body>
</html>