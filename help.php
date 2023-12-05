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
		<section class="main-column">
			<h2>DuckDuckGo features</h2>
			<p>DuckDuckGo is mostly language agnostic and will try to figure out on it's own what language to use.</p>
			<p>Searching defaults to Moderate Safe mode. To override the safe mode prefix your search with <strong>safe:on</strong> or <strong>safe:off</strong>.<br /><strong>On</strong> will use 'Strict' mode, while <strong>off</strong> will disable safe searching, this may yield results that are unsuitable for work or minors.</p>
			<p><em><strong>Note:</strong> Search bangs are not supported and the <strong>!</strong> to trigger them is stripped out to prevent issues.</em></p>
			
			<h2>Google features</h2>
			<p>Searching defaults to Moderate Safe mode. You can override safe mode by adding the prefix <strong>safe:on</strong> or <strong>safe:off</strong> to your search query.<br /><strong>On</strong> will use 'Strict' mode, while <strong>off</strong> will disable safe searching. this may yield results that are unsuitable for work or minors.</p>
			<p>Google results are not personalized by default, using Google's option for it.</p>
			<p>Google search is language agnostic and Google will try to figure out on it's own what language to use. To search in a specific language prefix your search with <strong>lang fr</strong> for French or <strong>lang:es</strong> for Spanish. Any language that google supports will work as long as you use the ISO639-1:2002 language code. Commonly these are the 2 letter abbreviations for the language such as; en, fr, es, de, sk, and so on.</p>
			<p>To do a category search, prefix your search with one of the following keywords; <strong>app</strong>, <strong>book</strong>, <strong>news</strong>, <strong>shop</strong> or <strong>patent</strong>. This will tell Google to look for results in that specific category.<br />For example: Searching for <strong>book trainspotting</strong> will (or should) show results related to the book Trainspotting.</p>

			<?php if($opts->enable_image_search == "on") { ?>
				<h2>Image Search</h2>
				<p>Search for images through Qwant Image Search.<br />The number of results is limited to 50.</p>
				<p>Contrary to Google Image Search which opens a popup/slider with more information. Goosle Image Search links directly to the page where the image is hosted.</p>
				<p>You can search for images in a general size by adding <strong>size:small</strong>, <strong>size:medium</strong> or <strong>size:large</strong> to your search query.</p>
			<?php } ?>
			
			<h2>Special Searches</h2>
			<?php if($opts->special['currency'] == "on") { ?>
				<h3>Currency converter</h3>
				<p>Convert currency with a simple query.<br />
				For example: Search for <strong>20 EUR in HKD</strong> or <strong>20 USD to MXN</strong> and DuckDuckGo or Google will search for it, but also a local conversion is done in a highlighted result.</p>
			<?php } ?>
			
			<?php if($opts->special['wikipedia'] == "on") { ?>
				<h3>Wikipedia Search</h3>
				<p>Prefix your search with <strong>w</strong> or <strong>wiki</strong> to search on Wikipedia for a page match. This works best for English searches as Wikipedia defaults to English.<br />
				For example: Searching for <strong>wiki beach ball</strong> will show you a excerpt from that page above the search results or suggest the most likely alternative if Wikipedia knows what your search query means.</p>
			<?php } ?>
			
			<?php if($opts->special['phpnet'] == "on") { ?>
				<h3>PHP.net Search</h3>
				<p>Prefix your search with <strong>php</strong> to search on php.net for a PHP function.<br />
				For example: Searching for <strong>php in_array</strong> or <strong>php trim</strong> will show you a brief description, compatible PHP versions and a usage example for that function.</p>
			<?php } ?>
			
			<?php if($opts->special['definition'] == "on") { ?>
				<h3>Word Definition</h3>
				<p>You can easily look up the meaning of single words. Prefix the word you want to look up with any of the following keywords; <strong>d</strong>, <strong>define</strong>, <strong>mean</strong>, <strong>meaning</strong>.<br />
				For example: Searching for <strong>define search</strong> will search for that on Google or DuckDuckGo, but also show the definition highlighted above the search results.</p>
				<p><em><strong>Note:</strong> Special Searches do not work for torrent searches.</em></p>
			<?php } ?>
			
			<?php if($opts->enable_torrent_search == "on") { ?>
				<h2>Torrent Search</h2>
				<p>Search for anything torrent sites have on offer the direct search result should give you the magnet link.<br />Results are gathered from 1337x, Nyaa, The Pirate Bay and YTS and are sorted by Seeders, highest first. The number of results is limited to 50.</p>
				<p>The search scripts will try to provide useful data which may include; Seeders/Leechers, A link to the torrent page, Download Category, Release year, Movie quality (720p, 4K etc.), Movie Runtime and the Download Size. Not every website makes this available and all results take a best effort approach.</p>
				<p><em><strong>Disclaimer:</strong> If you like, or found a use for, what you downloaded, you should probably buy a legal copy of it.</em></p>
			<?php } ?>

			<p><small><strong>Acknowledgements:</strong><br />Goosle started as a fork of LibreY, and takes some design cues from DuckDuckGo.com. Goosle is created by <a href="https://ajdg.solutions/" target="_blank">Arnan de Gans</a>.</small></p>
		</section>
	</div>
</div>

<div class="footer-wrap">
	<div class="footer">
		&copy; <?php echo date('Y'); ?> <a href="https://ajdg.solutions/" target="_blank">Arnan de Gans</a>. All rights reserved.
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