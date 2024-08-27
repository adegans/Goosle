<?php
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

if(!defined('ABSPATH')) define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
date_default_timezone_set('UTC');

require ABSPATH.'functions/tools.php';
require ABSPATH.'functions/search_engine.php';

$opts = load_opts();
$search = load_search();
$start_time = microtime(true);

// SEO description
$description = (strlen($search->query) > 0) ? "Check out these Goosle search results about: '".urldecode($search->query)."'." : "Check out these Goosle search results!";
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Goosle Search | Results</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="<?php echo $description; ?>" />

	<meta property="og:site_name" content="Goosle Search" />
	<meta property="og:title" content="The best meta search engine" />
	<meta property="og:description" content="<?php echo $description; ?>" />
	<meta property="og:url" content="<?php echo get_base_url($opts->siteurl); ?>/results.php" />
	<meta property="og:image" content="<?php echo get_base_url($opts->siteurl); ?>/assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo get_base_url($opts->siteurl); ?>/results.php" />
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/styles.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/<?php echo $opts->colorscheme; ?>.css"/>
	<script src="<?php echo get_base_url($opts->siteurl);?>/assets/js/goose.js" id="goosebox-js"></script>
</head>

<body class="resultspage">
<?php
if(verify_hash($opts->hash_auth, $opts->hash, $opts->user_auth, $search->share)) {
?>
<div class="header">
	<form action="results.php" method="get" autocomplete="off">
	    <h1 class="logo"><a href="./?a=<?php echo $opts->hash; ?>"><span class="goosle-g">G</span>oosle</a></h1>
	    <input tabindex="1" class="search-field" type="search" value="<?php echo (strlen($search->query) > 0) ? htmlspecialchars($search->query) : "" ; ?>" name="q" /><input tabindex="2" class="button" type="submit" value="Search" />

        <input type="hidden" name="t" value="<?php echo $search->type; ?>"/>
	    <input type="hidden" name="a" value="<?php echo $opts->user_auth; ?>">
	</form>

    <div class="navigation">
        <?php if($opts->enable_web_search == 'on') { ?>
        <a class="<?php echo ($search->type == '0') ? 'active ' : ''; ?>tab-search" href="./results.php?q=<?php echo $search->query; ?>&a=<?php echo $opts->user_auth; ?>&t=0">Search</a>
        <?php } ?>

        <?php if($opts->enable_image_search == 'on') { ?>
        <a class="<?php echo ($search->type == '1') ? 'active ' : ''; ?>tab-image" href="./results.php?q=<?php echo $search->query; ?>&a=<?php echo $opts->user_auth; ?>&t=1" >Images</a>
        <?php } ?>

        <?php if($opts->enable_news_search == 'on') { ?>
        <a class="<?php echo ($search->type == '2') ? 'active ' : ''; ?>tab-news" href="./results.php?q=<?php echo $search->query; ?>&a=<?php echo $opts->user_auth; ?>&t=2">News</a>
        <?php } ?>

        <?php if($opts->enable_magnet_search == 'on') { ?>
        <a class="<?php echo ($search->type == '9') ? 'active ' : ''; ?>tab-magnet" href="./results.php?q=<?php echo $search->query; ?>&a=<?php echo $opts->user_auth; ?>&t=9">Magnet links</a>
        <?php } ?>
   </div>
</div>

<div class="content">
	<?php
	if(!empty($search->query)) {
		// Curl
    	$mh = curl_multi_init();

		// Load search script
    	if($search->type == 0) {
        	require ABSPATH.'engines/search.php';
        	$search_results = new Search($search, $opts, $mh);
		} else if($search->type == 1) {
	    	require ABSPATH.'engines/search-image.php';
        	$search_results = new ImageSearch($search, $opts, $mh);
		} else if($search->type == 2) {
	    	require ABSPATH.'engines/search-news.php';
        	$search_results = new NewsSearch($search, $opts, $mh);
		} else if($search->type == 9) {
	    	require ABSPATH.'engines/search-magnet.php';
        	$search_results = new MagnetSearch($search, $opts, $mh);
    	}

    	$running = null;

    	do {
        	$status = curl_multi_exec($mh, $running);
	    	if($running) {
	        	curl_multi_select($mh);
	    	}
    	} while ($running && $status == CURLM_OK);

    	$results = $search_results->get_results();

		curl_multi_close($mh);

		// Add elapsed time to results
		$results['time'] = number_format(microtime(true) - $start_time, 5, '.', '');

		// Echoes results and special searches
    	$search_results->print_results($results, $search, $opts);
	} else {
		echo "<div class=\"warning\">";
		echo "	<h3>Search query can not be empty!</h3>";
		echo "	<p>Not sure what went wrong? Learn more about <a href=\"./help.php?a=".$opts->user_auth."\" title=\"how to use Goosle!\">how to use Goosle</a>.</p>";
		echo "</div>";
	}
	?>
</div>

<?php
	include_once('footer.php');
} else {
	include_once('error.php');
}
?>

</body>
</html>
