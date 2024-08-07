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
	<meta property="og:url" content="<?php echo get_base_url($opts->siteurl); ?>/help.php" />
	<meta property="og:image" content="<?php echo get_base_url($opts->siteurl); ?>/assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo get_base_url($opts->siteurl); ?>/help.php" />
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/styles.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/<?php echo $opts->colorscheme; ?>.css"/>
</head>

<body class="helppage">
<?php
if(verify_hash($opts->hash_auth, $opts->hash, $opts->user_auth)) {
?>
<div class="header">
	<form action="results.php" method="get" autocomplete="off">
	    <h1 class="logo"><a href="./?a=<?php echo $opts->hash; ?>"><span class="goosle-g">G</span>oosle</a></h1>
	    <input tabindex="1" class="search-field" type="search" value="<?php echo (strlen($search->nice_query) > 0) ? htmlspecialchars($search->nice_query) : "" ; ?>" name="q" /><input tabindex="2" class="button" type="submit" value="Search" />

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
	<h2>How to use Goosle</h2>
	<p> Goosle has an easy to use UI, free of clutter and distractions. Hopefully this provides a pleasurable search experience. You will not find any unnessesary features or complex settings in Goosle. After-all, navigating the internet is hard and frustrating enough. Search engines should make that more easy, not harder!</p>
	<p>All external links <em>always</em> open in a new tab. That way you never loose your current search results. And to make search results more useful Goosle tries to format them in a neat and clean way so they're easy to read and use.</p>

	<p>Goosle is created by <a href="https://www.arnan.me/" target="_blank">Arnan de Gans</a> with the intent to make search more productive and fun.</p>

	<h3>Result ranking</h3>
	<p>Goosle tries to provide you with search results in the right order no matter which search engine provides them. To try and provide the best results first Goosle has a basic algorithm to rank results.</p>
	<?php if($opts->enable_web_search == 'on' || $opts->enable_image_search == 'on') { ?>
		<p>For Web and Image search. If a website or image is found through multiple search engines it will rank higher. Also the amount of matching words in the title and SEO description are considered.</p>
	<?php } ?>
	<?php if($opts->enable_news_search == 'on') { ?>
		<p>News search is ranked by your keywords and how many times a certain link is found, but also by publish date. Newer results rank higher.</p>
	<?php } ?>
	<?php if($opts->enable_magnet_search == 'on') { ?>
		<p>Magnet results are sorted by most seeders (eg: availability of the download).</p>
	<?php } ?>

	<h3>Safe search</h3>
	<p>Search defaults to Moderate Safe mode. To override the safe mode, prefix your search with <strong>safe:on</strong> or <strong>safe:off</strong> (example: <strong>safe:off geese gone wild</strong>).<br /><strong>On</strong> will use 'Strict' mode, while <strong>off</strong> will disable safe searching, this may yield results that are unsuitable for workspaces or minors.</p>

	<?php if($opts->show_nsfw_magnets == 'off') { ?>
		<p>The Not Suitable For Work (NSFW) filter for Magnet results is enabled. This is an attempt to hide adult content from results. Some search engines have categories that can be filtered out. Others rely on keyword matches. Goosle has an extensive list of 'dirty' keywords to try and find adult content and then ignore it. To override the setting use the <strong>safe:off</strong> or <strong>nsfw</strong> prefix.<br />
		For example: Search for <strong>nsfw goose on goose action</strong> or <strong>safe:off dirty geese</strong> to include adult content in the results.</p>
	<?php } ?>

	<?php if($opts->enable_web_search == 'on') { ?>
		<h2>Web search</h2>
		<p>Goosle Web Search gathers links through search engines and search API and shows them in a neat and organised results page. Results are ranked by relevance to your current search.</p>

		<?php if($opts->special['currency'] == 'on') { ?>
			<h4>Currency converter</h4>
			<p>Convert currency with a simple query.<br />
			For example: Search for <strong>20 EUR in HKD</strong> or <strong>14 USD to MXN</strong> and Goosle will search for it, but also a local conversion is done in a highlighted result.</p>
		<?php } ?>

		<?php if($opts->special['definition'] == 'on') { ?>
			<h4>Word Definition</h4>
			<p>Look up the meaning of single words. Prefix the word you want to look up with <strong>define</strong>, <strong>def</strong> or <strong>meaning</strong>.<br />
			For example: Searching for <strong>define goose</strong> will do a web search for 'goose' but will also show a dictionary definition highlighted above the search results.</p>
		<?php } ?>

		<?php if($opts->special['ipaddress'] == 'on') { ?>
			<h4>IP Address lookup</h4>
			<p>Search for <strong>ip</strong>, <strong>myip</strong> or <strong>ipaddress</strong> to look up your IP Address.<br />
			Goosle knows your IP Address but the searches you do via Goosle will hide your IP address from the target sites such as Google or Limetorrents. You can see and verify the difference with this tool.</p>
		<?php } ?>

		<?php if($opts->special['phpnet'] == 'on') { ?>
			<h4>PHP.net Search</h4>
			<p>Prefix your search with <strong>php</strong> to search on php.net for a PHP function.<br />
			For example: Searching for <strong>php in_array</strong> or <strong>php trim</strong> will show you a brief description, compatible PHP versions and the basic syntax for that function.</p>
		<?php } ?>

		<?php if($opts->special['wordpress'] == 'on') { ?>
			<h4>WordPress documentation Search</h4>
			<p>Prefix your search with <strong>wordpress</strong> or <strong>wp</strong> to search on wordpress.org for a WordPress function. You can also search for hooks or filters by adding 'hook' as the 2nd keyword.<br />
			For example: Searching for <strong>wordpress the_content</strong> or <strong>wp hook admin_init</strong> will show you a brief description and the basic syntax for that function or hook/filter.</p>
		<?php } ?>
	<?php } ?>

	<?php if($opts->enable_image_search == 'on') { ?>
		<h2>Image Search</h2>
		<p>Goosle Image Search links directly to the web page where the image is displayed, but also tries to link to the actual image itself.</p>
		<p>You can search for images in a general size by adding <strong>size:small</strong>, <strong>size:medium</strong>, <strong>size:large</strong> or <strong>size:xlarge</strong> to the beginning of your search query (example: <strong>size:small huge goose</strong>).</p>
	<?php } ?>

	<?php if($opts->enable_news_search == 'on') { ?>
		<h2>News search</h2>
		<p>Look for current and recent news through News Search. Search for any topic and you'll find news from the last 30 days. Search is loosely ranked by post date. Newer news ranks higher.<p>
	<?php } ?>

	<?php if($opts->enable_magnet_search == 'on') { ?>
		<h2>Magnet Search</h2>
		<p>Magnet Search provides Magnet links, these are special links from Torrent sites to download content from the internet. Things like Movies, TV-Shows, EBooks, Music, Games, Software and more. You'll need a Bittorrent client that accepts Magnet links to download the search results.</p>
		<p>There are many <a href="./results.php?q=Torrent+clients+Magnet+links&a=<?php echo $opts->hash; ?>&t=0" target="_blank">Torrent clients that support Magnet links</a> but if you don't know which one to choose, give <a href="https://transmissionbt.com/" target="_blank" title="Transmission Bittorrent">Transmission BT</a> a go as it's easy to set up and use.</p>
		<p>Goosle will try to provide useful information about the download, which includes; Seeders, Leechers, A link to the torrent page, Download Category and Release year. Extra information may also include the Movie quality (720p, 4K etc.), Movie Runtime and the Download Size along with some other bits and bops if available. Not every website makes this available and all results take a best effort approach.</p>

		<h3>Searching for TV Shows</h3>
		<p>To do a specific search on The Pirate Bay and EZTV you search for IMDb Title IDs. These are numeric IDs prefixed with <strong>tt</strong>. This kind of search is useful when you're looking for a tv show that doesn't have a unique name, or simply if you want to use a specialized tracker for tv shows.</p>
		<p>If you know the IMDb Title ID you can search for it through the Magnet search.</p>
		<?php if($opts->imdb_id_search == 'on') { ?>
			<p>If you don't know the Title ID you can do a regular search for <strong>imdb [tv show name]</strong>, for example <strong>imdb Duck and Goose</strong>.<br />
			Goosle will detect the IMDb ID from the search results and highlight it in the result as a link. This link offers you to search for downloads through a Magnet Search.</p>
		<?php } ?>

		<?php if($opts->show_share_option == 'on') { ?>
			<h3>Sharing results</h3>
			<p>You can share a specific Magnet result by clicking on the <strong>share</strong> link that's behind the result information. In the popup that opens you can copy the link and share or store it anywhere you can paste text - For example in a messenger or note. This special link will perform the same search as you did yourself and highlight the result that you want to share.</p>
			<?php if($opts->hash_auth == 'off') { ?>
				<p>The links can be shared with anyone since you do not run a private Goosle. Anyone who has the shared link can see the results.</p>
			<?php } else { ?>
				<p>The links can be shared with anyone but keep in mind that since you run a private installation you might be giving uninvited guests access to it. To prevent this from happening the share link does NOT include the passphrase hash. This means that any guests can see your shared results but searching for new things will not work for them.</p>
			<?php } ?>
		<?php } ?>

		<h3>Finding specific TV Show episodes and seasons</h3>
		<p>To help you narrow down results you can search for specific seasons and episodes. For example: If you search for <strong>tt7999864 S01</strong> or <strong>Duck and Goose S01</strong> you'll get filtered results for Duck & Goose Season 1. Searching for <strong>tt7999864 S01E02</strong> or <strong>Duck and Goose S01E02</strong> should find Season 1 Episode 2 and so on.</p>

		<h3>Filtering Movie and TV Show results</h3>
		<p>Likewise if you want a specific quality of a movie or tv show you can add that directly in your search. For example: If you search for <strong>Goose on the loose 720p</strong> you should primarily find that movie in 720p quality if it's available. Common screensizes are 480p, 720p, 1080p, 2160p (4K) and terms like HD-DVD, FULLHD, BLURAY etc..</p>
		<p>You can do searches by year as well. Searching for <strong>1080p 2006</strong> should yield mostly movies from that year in the specified quality.</p>

		<h3>The box office</h3>
		<p>Along with Magnet search a Box Office page also appears. This is an overview page of the latest movies and other new downloads available on a few supported torrent sites. The shown results are cached just like regular search results.</p>
		<p><em><strong>Note:</strong> The things you find through magnet search are not always legal to download due to copyright or local restrictions. If possible, try to get a legal copy if you found a use for what you downloaded!</em></p>
	<?php } ?>

	<h2>Default search engine</h2>
	<p>In some browsers you can add a custom search engine. To do so follow the browsers instruction and use the following link: <strong>https://example.com/results.php?q=%s</strong>.</p>
	<p>Or if you use the Auth Hash as a password add the <strong>a</strong> argument, like so: <strong>https://example.com/results.php?a=YOUR_HASH&q=%s</strong>. Obviously replace example.com with your actual goosle addesss.</p>

	<p>Most browsers will instruct you to add <strong>%s</strong> for the search query as shown in the examples. If your browser has a different value for this simply replace %s with what your browser requires.

	<h2>Colorschemes</h2>
	<p>Goose comes with several colorschemes, configurable through the config.php file.</p>

	<h3>Available colorschemes are:</h3>
	<ol>
		<li>"default" A dark headers and main backgrounds with light search results.</li>
		<li>"light" More light elements.</li>
		<li>"dark" More dark elements, some apps would call this dark mode.</li>
		<li>"auto" Let the browser decide what to use. This is typically linked to the darkmode setting of your device.</li>
	</ol>

	<h4>Acknowledgements:</h4>
	<p><small>All icons are borrowed from the IconFinder <a href="https://www.iconfinder.com/search/icons?family=unicons-line" target="_blank">Unicons Set</a>.<br />
	The Goose icon is borrowed from the Flaticon <a href="https://www.flaticon.com/packs/farm-19" target="_blank">Farm pack</a>.<br />
	Goosle started as a fork of LibreY, and takes some design cues from DuckDuckGo.com.</small></p>
</div>

<div class="footer grid-container">
	<div class="footer-grid">
		&copy; <?php echo the_date('Y'); ?> Goosle <?php echo $current_version; ?> <?php echo show_update_notification(); ?>
	</div>
	<div class="footer-grid">
		<a href="./?a=<?php echo $opts->hash; ?>">Start</a> - <a href="./box-office.php?a=<?php echo $opts->hash; ?>&t=9">Box office</a> - <a href="./help.php?a=<?php echo $opts->hash; ?>">Help</a> - <a href="./stats.php?a=<?php echo $opts->hash; ?>">Stats</a>
	</div>
</div>

<?php } else { ?>
	<div class="auth-error">Redirecting</div>
	<meta http-equiv="refresh" content="1; url=<?php echo get_base_url($opts->siteurl); ?>/error.php" />
<?php } ?>

</body>
</html>
