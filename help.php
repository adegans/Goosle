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
	<title>Goosle Search Help</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Get your Goosle on! - The best meta search engine for private and fast internet fun!" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo get_base_url($opts->siteurl); ?>/help.php" />

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
	<h2>How to use Goosle</h2>
	<p>DuckDuckGo and Google are mostly language agnostic and will try to figure out on their own what language to use based on your search query.</p>
	<p>Searching defaults to Moderate Safe mode. To override the safe mode, prefix your search with <strong>safe:on</strong> or <strong>safe:off</strong>.<br /><strong>On</strong> will use 'Strict' mode, while <strong>off</strong> will disable safe searching, this may yield results that are unsuitable for work or minors.</p>
	<?php if($opts->enable_google == "on") { ?>
	<p>Google results are not personalized by default, using Google's own option for it.</p>
	<?php } ?>
	<?php if($opts->enable_duckduckgo == "on") { ?>
	<p>DuckDuckGo search bangs are not supported and the <strong>!</strong> to trigger them is stripped out to prevent issues.</em></p>
	<?php } ?>
	<?php if($opts->enable_wikipedia == "on") { ?>
	<p>Goosle can search directly on Wikipedia. This will yield results linking to Wikipedia pages in the language of your choice. Configure your language in config.php or have it default to english.</p>
	<?php } ?>

	<h3>Result ranking</h3>
	<p>To try and provide the best results first, if a website is found through multiple search engines it will show higher in the results on Goosle. Also the amount of matching words in the title and SEO description is considered. A better match will show higher in the results.</p>
	<p>Result ranking is applied to web search and image search.</p>

	<?php if($opts->enable_image_search == "on") { ?>
		<h2>Image Search</h2>
		<p>The number of results is not limited but typically yields about 60 to 80 images. If you've enabled Openverse you can set a limit for Openverse images of up to 200 images per search.</p>
		<p>Contrary to regular image search such as Google Image Search which opens a popup/slider with more information. Goosle Image Search links directly to the page where the image is displayed, but also to the actual image itself. You'll see the links for it below each image.</p>
		<p>You can search for images in a general size by adding <strong>size:small</strong>, <strong>size:medium</strong> or <strong>size:large</strong> to your search query.</p>
	<?php } ?>
	
	<h2>Special Searches</h2>
	<?php if($opts->special['currency'] == "on") { ?>
		<h3>Currency converter</h3>
		<p>Convert currency with a simple query.<br />
		For example: Search for <strong>20 EUR in HKD</strong> or <strong>14 USD to MXN</strong> and Goosle will search for it, but also a local conversion is done in a highlighted result.</p>
	<?php } ?>
	
	<?php if($opts->special['phpnet'] == "on") { ?>
		<h3>PHP.net Search</h3>
		<p>Prefix your search with <strong>php</strong> to search on php.net for a PHP function.<br />
		For example: Searching for <strong>php in_array</strong> or <strong>php trim</strong> will show you a brief description, compatible PHP versions and the basic syntax for that function.</p>
	<?php } ?>
	
	<?php if($opts->special['definition'] == "on") { ?>
		<h3>Word Definition</h3>
		<p>You can easily look up the meaning of single words. Prefix the word you want to look up with any of the following keywords; <strong>d</strong>, <strong>define</strong>, <strong>mean</strong> or <strong>meaning</strong>.<br />
		For example: Searching for <strong>define search</strong> will search for that as normal, but also show the dictionary definition highlighted above the search results.</p>
		<p><em><strong>Note:</strong> Special Searches do not work for image and magnet search.</em></p>
	<?php } ?>
	
	<?php if($opts->enable_magnet_search == "on") { ?>
		<h2>Magnet Search</h2>
		<p>Search for anything torrent sites have on offer the direct search result should give you the magnet link.<br />Results are gathered from 1337x, Nyaa, The Pirate Bay, EZTV and YTS and are ranked by most seeders. The number of results is limited to 50.</p>
		<p>The search scripts will try to provide useful data which may include; Seeders/Leechers, A link to the torrent page, Download Category, Release year, Movie quality (720p, 4K etc.), Movie Runtime and the Download Size. Not every website makes this available and all results take a best effort approach.</p>
		<?php if($opts->imdb_id_search == "on") { ?>
			<h3>Searching TV Shows</h3>
			<p>To do a specific search on The Pirate Bay and EZTV you can search for IMDb Title IDs. These are numeric IDs prefixed with <strong>tt</strong>. This kind of search is useful when you're looking for a tv show that doesn't have a unique name, or simply if you want to use a specialized tracker for tv shows.</p>
			<p>	If you already know the Title ID you can enter it directly in the Magnet Link search as your search query.<br />
			If you don't know the Title ID you can do a regular search for <strong>imdb [tv show name]</strong>, for example <strong>imdb Jack Ryan</strong>.<br />
			Goosle will detect the IMDb ID from the search results and include a highlight in the result that offers you to search for downloads through a magnet link search.</p>
			
		<?php } ?>
		<h3>Filtering TV Show episodes</h3>
		<p>To help you narrow down results you can search for specific seasons and episodes. For example: If you search for <strong>tt5057054 S02</strong> or <strong>Jack Ryan S02</strong> you'll get filtered results for Jack Ryan Season 2. Searching for <strong>tt5057054 S02E03</strong> or <strong>Jack Ryan S02E03</strong> should find Season 2 Episode 3 and so on.</p>
		<p>Likewise if you want a specific quality of a movie or tv show you can add that directly in your search. For example: If you search for <strong>Arrietty 720p</strong> you should primarily find that movie in 720p quality if it's available. Common screensizes are 480p, 720p, 1080p, 2160p (4K) and terms like HD-DVD, FULLHD, etc..</p>
		<p><em><strong>Note:</strong> If you like, or found a use for, what you downloaded, you should buy a legal copy of it.</em></p>
	<?php } ?>

	<p><small><strong>Acknowledgements:</strong><br />Goosle started as a fork of LibreY, and takes some design cues from DuckDuckGo.com. Goosle is created by <a href="https://ajdg.solutions/" target="_blank">Arnan de Gans</a> with the intent to make search fun and productive.</small></p>
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