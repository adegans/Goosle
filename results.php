<?php
if(!defined('ABSPATH')) define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

require ABSPATH."functions/tools.php";
require ABSPATH."functions/search_engine.php";

$opts = load_opts();
$auth = (isset($_GET['a'])) ? sanitize($_GET['a']) : $opts->user_auth;
$start_time = microtime(true);
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
	<title><?php echo $opts->query; ?> - Goosle Search Results</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Get your Goosle on! - The best meta search engine for private and fast internet fun!" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo get_base_url($opts->siteurl); ?>/results.php" />

    <link rel="stylesheet" type="text/css" href="assets/css/styles.css"/>
</head>

<body>
<?php
if(verify_hash($opts, $auth)) {
?>
<div class="header">
	<form action="results.php" method="get" autocomplete="off">
	    <h1 class="logo"><a class="no-decoration" href="./?a=<?php echo $opts->hash; ?>"><span class="G">G</span>oosle</a></h1>        
	    <input tabindex="1" class="search" type="search" value="<?php echo (strlen($opts->query) > 0) ? $opts->query : "" ; ?>" name="q" /><input tabindex="2" class="button" type="submit" value="Search" />
		
        <input type="hidden" name="t" value="<?php echo $opts->type; ?>"/>
	    <input type="hidden" name="a" value="<?php echo $opts->hash; ?>">
 
        <div class="navigation">
	        <a <?php echo ($opts->type == "0") ? "class=\"active\" " : ""; ?> href="./results.php?q=<?php echo urlencode($opts->query); ?>&a=<?php echo $opts->hash; ?>&t=0"><img src="assets/images/search.png" alt="Search results" />Search</a>

	        <?php if($opts->enable_image_search == "on") { ?>
	        <a <?php echo ($opts->type == "1") ? "class=\"active\" " : ""; ?> href="./results.php?q=<?php echo urlencode($opts->query); ?>&a=<?php echo $opts->hash; ?>&t=1"><img src="assets/images/image.png" alt="Image results" />Images</a>
	        <?php } ?>

	        <?php if($opts->enable_magnet_search == "on") { ?>
	        <a <?php echo ($opts->type == "9") ? "class=\"active\" " : ""; ?> href="./results.php?q=<?php echo urlencode($opts->query); ?>&a=<?php echo $opts->hash; ?>&t=9"><img src="assets/images/magnet.png" alt="Magnet results" />Magnet links</a>
	        <?php } ?>
       </div>
	</form>
</div>

<div class="content">
<?php
if(!empty($opts->query)) {
	// Curl
    $mh = curl_multi_init();

	// Load search script
    if($opts->type == 0) {
        require ABSPATH."engines/search.php";
        $search = new Search($opts, $mh);
	} else if($opts->type == 1) {
	    require ABSPATH."engines/search-image.php";
        $search = new ImageSearch($opts, $mh);
	} else if($opts->type == 9) {
	    require ABSPATH."engines/search-magnet.php";
        $search = new MagnetSearch($opts, $mh);
    }

    $running = null;

    do {
        $status = curl_multi_exec($mh, $running);
	    if($running) {
	        curl_multi_select($mh);
	    }
    } while ($running && $status == CURLM_OK);

    $results = $search->get_results();

	curl_multi_close($mh);

	// Add elapsed time to results
	$results['time'] = number_format(microtime(true) - $start_time, 5, '.', '');

	// Echoes results and special searches
    $search->print_results($results, $opts);
} else {
	echo "<div class=\"warning\">Search query can not be empty!<br />Not sure what went wrong? Learn more about <a href=\"./help.php?a=".$opts->hash."\">how to use Goosle</a>.</div>";
}
?>
</div>

<div class="footer">
	<div class="footer-left">
		&copy; <?php echo date('Y'); ?> <?php echo show_version(); ?> By <a href="https://ajdg.solutions/" target="_blank">Arnan de Gans</a>.
	</div>
	<div class="footer-right">
		<a href="./?a=<?php echo $opts->hash; ?>">Start</a> - <a href="./help.php?a=<?php echo $opts->hash; ?>">Help</a>
	</div>
</div>

<?php 
} else {
	echo "<div class=\"auth-error\">Goosle</div>";
} 
?>
</body>
</html>