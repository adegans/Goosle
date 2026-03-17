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
require ABSPATH.'functions/tools-search.php';
require ABSPATH.'functions/tools.php';
require ABSPATH.'functions/boxoffice-results.php';

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
	<meta name="description" content="View the latest movie and tv-show links available for download!" />

	<meta property="og:site_name" content="Goosle Search" />
	<meta property="og:title" content="Goosle Search Box Office" />
	<meta property="og:description" content="View the latest magnet links available for download!" />
	<meta property="og:url" content="<?php echo $opts->baseurl; ?>box-office.php" />
	<meta property="og:image" content="<?php echo $opts->baseurl; ?>assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo $opts->baseurl; ?>box-office.php" />
    <link rel="stylesheet" type="text/css" href="<?php echo $opts->baseurl; ?>assets/css/styles.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo $opts->baseurl; ?>assets/css/<?php echo $opts->colorscheme; ?>.css"/>

	<script src="<?php echo $opts->baseurl; ?>assets/js/goose.js" id="goosle-js"></script>
	<style>
		.result-grid .result.image .thumb::before, .result-grid .result.highlight .thumb::before { background-image:url('<?php echo $opts->baseurl; ?>assets/images/goosle-nobg.webp'); }
	</style>
</head>

<body class="page boxoffice">
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
			echo highlight_popup($opts->pixel, $highlight);

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
			echo highlight_popup($opts->pixel, $highlight);

			echo "</li>";

			unset($highlight, $thumb);
		}
		unset($highlights);
		?>
    </ul>

	<p class="text-center"><small>Goosle does not index, offer or distribute torrent files.</small></p>
</div>

<?php
	include ABSPATH . 'template-parts/footer.php';
} else {
	include ABSPATH . 'template-parts/login-error.php';
}
?>

</body>
</html>
