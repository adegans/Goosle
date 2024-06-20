<?php
if(!defined('ABSPATH')) define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
date_default_timezone_set('UTC');

require ABSPATH.'functions/tools.php';
require ABSPATH.'functions/search_engine.php';
require ABSPATH.'functions/tools-update.php';

// Blue pixel
$blank_thumb = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mOUX3LxDAAE4AJiVKIoaQAAAABJRU5ErkJggg==';

$opts = load_opts();
$auth = (isset($_GET['a'])) ? sanitize($_GET['a']) : $opts->user_auth;
$start_time = microtime(true);
/* ------------------------------------------------------------------------------------
*  Goosle - The fast, privacy oriented search tool that just works.
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
	<title>Goosle Search Results</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Check out these Goosle search results!" />

	<meta property="og:site_name" content="Goosle Search results" />
	<meta property="og:title" content="The best meta search engine" />
	<meta property="og:description" content="Check out these Goosle search results!" />
	<meta property="og:url" content="<?php echo get_base_url($opts->siteurl); ?>/results.php" />
	<meta property="og:image" content="<?php echo get_base_url($opts->siteurl); ?>/assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo get_base_url($opts->siteurl); ?>/results.php" />
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/styles.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/<?php echo $opts->colorscheme; ?>.css"/>

	<?php
	if($opts->type == "9") {
    	echo "	<script src='".get_base_url($opts->siteurl)."/assets/js/goose.js' id='goosebox-js'></script>";
	}
	?>
</head>

<body class="resultspage">
<?php
if(verify_hash($opts->hash_auth, $opts->hash, $auth)) {
?>
<div class="header">
	<form action="results.php" method="get" autocomplete="off">
	    <h1 class="logo"><a href="./?a=<?php echo $opts->hash; ?>"><span class="goosle-g">G</span>oosle</a></h1>        
	    <input tabindex="1" class="search-field" type="search" value="<?php echo (strlen($opts->query) > 0) ? htmlspecialchars($opts->query) : "" ; ?>" name="q" /><input tabindex="2" class="button" type="submit" value="Search" />
		
        <input type="hidden" name="t" value="<?php echo $opts->type; ?>"/>
	    <input type="hidden" name="a" value="<?php echo $opts->hash; ?>">
	</form>
 
    <div class="navigation">
        <a class="<?php echo ($opts->type == '0') ? 'active ' : ''; ?>tab-search" href="./results.php?q=<?php echo $opts->query; ?>&a=<?php echo $opts->hash; ?>&t=0">Search</a>

        <?php if($opts->enable_image_search == 'on') { ?>
        <a class="<?php echo ($opts->type == '1') ? 'active ' : ''; ?>tab-image" href="./results.php?q=<?php echo $opts->query; ?>&a=<?php echo $opts->hash; ?>&t=1" >Images</a>
        <?php } ?>

        <?php if($opts->enable_news_search == 'on') { ?>
        <a class="<?php echo ($opts->type == '2') ? 'active ' : ''; ?>tab-news" href="./results.php?q=<?php echo $opts->query; ?>&a=<?php echo $opts->hash; ?>&t=2">News</a>
        <?php } ?>

        <?php if($opts->enable_magnet_search == 'on') { ?>
        <a class="<?php echo ($opts->type == '9') ? 'active ' : ''; ?>tab-magnet" href="./results.php?q=<?php echo $opts->query; ?>&a=<?php echo $opts->hash; ?>&t=9">Magnet links</a>
        <?php } ?>
   </div>
</div>

<div class="content">
<?php
if(!empty($opts->query)) {
	// Curl
    $mh = curl_multi_init();

	// Load search script
    if($opts->type == 0) {
        require ABSPATH.'engines/search.php';
        $search = new Search($opts, $mh);
	} else if($opts->type == 1) {
	    require ABSPATH.'engines/search-image.php';
        $search = new ImageSearch($opts, $mh);
	} else if($opts->type == 2) {
	    require ABSPATH.'engines/search-news.php';
        $search = new NewsSearch($opts, $mh);
	} else if($opts->type == 9) {
	    require ABSPATH.'engines/search-magnet.php';
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
	echo "<div class=\"warning\">";
	echo "	<h3>Search query can not be empty!</h3>";
	echo "	<p>Not sure what went wrong? Learn more about <a href=\"./help.php?a=".$opts->hash."\" title=\"how to use Goosle!\">how to use Goosle</a>.</p>";
	echo "</div>";
}
?>
</div>

<div class="footer grid-container">
	<div class="footer-grid">
		&copy; <?php echo date('Y'); ?> <?php echo show_version(); ?> By <a href="https://ajdg.solutions/" target="_blank">Arnan de Gans</a>.
	</div>
	<div class="footer-grid">
		<a href="./?a=<?php echo $opts->hash; ?>">Start</a> - <a href="./box-office.php?a=<?php echo $opts->hash; ?>&t=9">Box office</a> - <a href="./help.php?a=<?php echo $opts->hash; ?>">Help</a>
	</div>
</div>

<?php 
} else {
	echo "<div class=\"auth-error\">Goosle</div>";
} 
?>
</body>
</html>