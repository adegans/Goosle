<?php
/* ------------------------------------------------------------------------------------
*  Goosle - The fast, privacy oriented search tool that just works.
*
*  COPYRIGHT NOTICE
*  Copyright 2023-2025 Arnan de Gans. All Rights Reserved.
*
*  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
*  By using this code you agree to indemnify Arnan de Gans from any
*  liability that might arise from its use.
------------------------------------------------------------------------------------ */

if(!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');

session_start();
date_default_timezone_set('UTC');

require ABSPATH.'functions/tools-files.php';
require ABSPATH.'functions/tools.php';

$opts = load_opts();
$search = load_search();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Goosle Search | How to use Goosle</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Learn how to use Goosle, the best meta search engine!" />

	<meta property="og:site_name" content="Goosle Search" />
	<meta property="og:title" content="How to use Goosle" />
	<meta property="og:description" content="Learn how to use Goosle, the best meta search engine!" />
	<meta property="og:url" content="<?php echo $opts->baseurl; ?>help.php" />
	<meta property="og:image" content="<?php echo $opts->baseurl; ?>assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo $opts->baseurl; ?>help.php" />
    <link rel="stylesheet" type="text/css" href="<?php echo $opts->baseurl; ?>assets/css/styles.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo $opts->baseurl; ?>assets/css/<?php echo $opts->colorscheme; ?>.css"/>
</head>

<body class="page help">
<?php
if($opts->user->logged_in) {
?>
<div class="header">
	<div class="logo"><h1><a href="./"><span class="goosle-g">G</span>oosle</a></h1></div>
	<?php
	include ABSPATH . 'template-parts/searchform.php';
	include ABSPATH . 'template-parts/navigation.php';
	?>
</div>

<div class="content">
	<h2>How to use Goosle</h2>
	<p> Goosle provides an easy to use UI, free of clutter and distractions. Hopefully this provides a pleasurable search experience to find downloadable content. You will not find any unnessesary features or complex settings in Goosle. After-all, finding things on the internet is hard and frustrating enough!</p>
	<p>All external links <em>always</em> open in a new tab. That way you never loose your current search results. And to make search results more useful Goosle tries to format them in a neat and clean way so they're easy to read and use.</p>

	<h3>Result ranking</h3>
	<p>Goosle ranks results by most seeders which means the availability of the download. More seeders usually means better availability, which almost always results in faster downloads.</p>

	<h3>Safe search</h3>
	<p>Safe Search filters are disabled by default. This may yield results that are unsuitable for workspaces or minors.<br />You can Enable Safe Search in your profile settings.</p>

	<p>When Safe Search is enabled, Goosle will attempt to hide adult content from search results. Some search engines have categories that can be filtered out. Others rely on keyword matches. Goosle has an extensive list of 'dirty' keywords to try and find adult content and then ignore it.</p>

	<h2>Searching for content</h2>
	<p>Goosle Search aggregates Magnet Links from various Torrent websites. Magnet links are special links to download content from the internet. Usually for free. This includes things like Movies, TV-Shows, EBooks, Music, Games, Software and more. You'll need a Bittorrent client that accepts Magnet links to download anything.</p>
	<p>There are many Torrent clients that support Magnet links but if you don't know which one to choose, give <a href="https://transmissionbt.com/" target="_blank" title="Transmission Bittorrent">Transmission BT</a> a go as it is easy to set up and use.</p>
	<p>For each result, Goosle will try to provide useful information about the download, which includes; Seeders, Leechers, A link to the torrent page, Download Category and Release year. Extra information may also include the Movie quality (720p, 4K etc.), Movie Runtime and the Download Size along with some other bits and bops if available. Often the downloads size is available too. Keep in mind that not every website makes this available and all results take a best effort approach.</p>

	<?php if($opts->show_share_option == 'on') { ?>
		<h3>Sharing results</h3>
		<p>You can share a specific Magnet result by clicking on the <strong>share</strong> link that's behind the result information. In the popup that opens you can copy the Magnet Link and share or store it anywhere you can paste text - For example in a messenger or a note. This special link will allow you to download the content directly from a compatible Torrent Client.</p>
		<p>To use a Magnet Link, simply load it in your browser as if it's a website, or load it from your Torrent client of choice via the add torrent option (or its equivalent).</p>
	<?php } ?>

	<h3>Searching for TV Shows</h3>
	<p>To do a specific search on The Pirate Bay and EZTV you search for IMDb Title IDs. These are numeric IDs prefixed with <strong>tt</strong>. This kind of search is useful when you're looking for a tv show that doesn't have a unique name, or simply if you want to use a specialized tracker for tv shows.</p>
	<p>If you know the IMDb Title ID you can search for it through the Magnet search.</p>

	<h3>Finding specific TV Show episodes and seasons</h3>
	<p>To help you narrow down results you can search for specific seasons and episodes. For example: If you search for <strong>tt7999864 S01</strong> or <strong>Duck and Goose S01</strong> you'll get filtered results for Duck & Goose Season 1. Searching for <strong>tt7999864 S01E02</strong> or <strong>Duck and Goose S01E02</strong> should find Season 1 Episode 2 and so on.</p>

	<h3>Filtering Movie and TV Show results</h3>
	<p>Likewise if you want a specific quality of a movie or tv show you can add that directly in your search. For example: If you search for <strong>Goose on the loose 720p</strong> you should primarily find that movie in 720p quality if it's available. Common screensizes are 480p, 720p, 1080p, 2160p (4K) and terms like HD-DVD, FULLHD, BLURAY etc..</p>
	<p>You can do searches by year as well. Searching for <strong>1080p 2006</strong> should yield mostly movies from that year in the specified quality.</p>

	<h3>The box office</h3>
	<p>Along with Magnet search a Box Office page also appears. This is an overview page of the latest movies and other new downloads available on a few supported torrent sites. The shown results are cached just like regular search results.</p>

	<h3>New arrivals</h3>
	<p>The Arrivals page lists recently added magnet links for several websites for a number of categories. These results are cached and the lists refresh every few hours. Depending on how you set up your Cron job.</p>

	<p><em><strong>Note:</strong> The things you find through magnet search are not always legal to download due to copyright or local restrictions. If possible, try to get a legal copy if you found a use for what you downloaded!</em></p>

	<h4>Acknowledgements:</h4>
	<p><small>All icons are borrowed from the IconFinder <a href="https://www.iconfinder.com/search/icons?family=unicons-line" target="_blank">Unicons Set</a>.<br />
	The Goose icon is borrowed from the Flaticon <a href="https://www.flaticon.com/packs/farm-19" target="_blank">Farm pack</a>.<br />
	Goosle started as a re-imagination of LibreY, and takes some design cues from DuckDuckGo.com.</small></p>
</div>

<?php
	include ABSPATH . 'template-parts/footer.php';
} else {
	include ABSPATH . 'template-parts/login-error.php';
}
?>

</body>
</html>
