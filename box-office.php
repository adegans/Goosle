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
require ABSPATH.'functions/tools-magnet.php';
require ABSPATH.'engines/boxoffice/yts.php';
require ABSPATH.'engines/boxoffice/eztv.php';

$opts = load_opts();
$search = load_search();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Goosle Search Box Office</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="View the latest magnet links available for download!" />

	<meta property="og:site_name" content="Goosle Search" />
	<meta property="og:title" content="Goosle Search Box Office" />
	<meta property="og:description" content="View the latest magnet links available for download!" />
	<meta property="og:url" content="<?php echo get_base_url($opts->siteurl); ?>/box-office.php" />
	<meta property="og:image" content="<?php echo get_base_url($opts->siteurl); ?>/assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo get_base_url($opts->siteurl); ?>/box-office.php" />
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/styles.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/<?php echo $opts->colorscheme; ?>.css"/>

	<script src="<?php echo get_base_url($opts->siteurl); ?>/assets/js/goose.js" id="goosle-js"></script>
</head>

<body class="boxofficepage">
<?php
if(verify_hash($opts->hash_auth, $opts->hash, $opts->user_auth)) {
?>
<div class="header">
	<form action="results.php" method="get" autocomplete="off">
	    <h1 class="logo"><a href="./?a=<?php echo $opts->user_auth; ?>"><span class="goosle-g">G</span>oosle</a></h1>
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
	<h2>The Box Office</h2>

	<p>Click on any movie poster for more information and available download links.</p>

	<h3>Recently added movies on YTS</h3>
	<?php
	$highlights = array_slice(yts_boxoffice($opts, 'date_added'), 0, 24);
	?>
	<ul class="result-grid">
		<?php
		foreach($highlights as $highlight) {
			$thumb = (!empty($highlight['thumbnail'])) ? $highlight['thumbnail'] : $opts-pixel;

			echo "<li class=\"result highlight yts id-".$highlight['id']."\">";
			echo "	<div class=\"thumb\">";
			echo "		<a onclick=\"openpopup('highlight-".$highlight['id']."')\" title=\"More info: ".$highlight['title']."\"><img src=\"".$thumb."\" alt=\"".$highlight['title']."\" /></a>";
			echo "	</div>";
			echo "	<span><center><a onclick=\"openpopup('highlight-".$highlight['id']."')\" title=\"More info: ".$highlight['title']."\">".$highlight['title']."</a></center></span>";

			// HTML for popup
			echo highlight_popup($opts->user_auth, $highlight);

			echo "</li>";

			unset($highlight, $thumb);
		}
		unset($highlights);
		?>
    </ul>

	<h3>Latest TV Show releases from EZTV</h3>
	<?php
	$highlights = array_slice(eztv_boxoffice($opts), 0, 24);
	?>
	<ul class="result-grid">
		<?php
		foreach($highlights as $highlight) {
			$thumb = (!empty($highlight['thumbnail'])) ? $highlight['thumbnail'] : $opts->pixel;

			echo "<li class=\"result highlight eztv id-".$highlight['id']."\">";
			echo "	<div class=\"thumb\">";
			echo "		<a onclick=\"openpopup('highlight-".$highlight['id']."')\" title=\"More info: ".$highlight['title']."\"><img src=\"".$thumb."\" alt=\"".$highlight['title']."\" /></a>";
			echo "	</div>";
			echo "	<span><center><a onclick=\"openpopup('highlight-".$highlight['id']."')\" title=\"More info: ".$highlight['title']."\">".$highlight['title']."</a></center></span>";

			// HTML for popup
			echo highlight_popup($opts->user_auth, $highlight);

			echo "</li>";

			unset($highlight, $thumb);
		}
		unset($highlights);
		?>
    </ul>

	<p class="text-center"><small>Goosle does not index, offer or distribute torrent files.</small></p>
</div>

<?php
	include_once('footer.php');
} else {
	include_once('error.php');
}
?>

</body>
</html>
