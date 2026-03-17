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
require ABSPATH.'functions/search-engine.php';

$opts = load_opts();
$user = do_login();
$search = load_search();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Goosle Search | Video Player</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Watch videos on Goosle!" />

	<meta property="og:site_name" content="Goosle Search" />
	<meta property="og:title" content="Goosle Search | Video Player" />
	<meta property="og:description" content="Watch videos on Goosle!" />
	<meta property="og:url" content="<?php echo $opts->baseurl; ?>watch.php" />
	<meta property="og:image" content="<?php echo $opts->baseurl; ?>assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo $opts->baseurl; ?>watch.php" />
    <link rel="stylesheet" type="text/css" href="<?php echo $opts->baseurl; ?>assets/css/styles.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo $opts->baseurl; ?>assets/css/<?php echo $opts->colorscheme; ?>.css"/>

	<script src="<?php echo $opts->baseurl; ?>assets/js/goosle.js" id="goosle-js"></script>
	<script src="<?php echo $opts->baseurl; ?>assets/js/livesearch.js" id="livesearch-js"></script>
</head>

<body class="page video">
<?php
if($user->logged_in) {
?>
<div class="header">
	<div class="logo"><h1><a href="./"><span class="goosle-g">G</span>oosle</a></h1></div>
	<?php
	include ABSPATH . 'template-parts/searchform.php';
	include ABSPATH . 'template-parts/navigation.php';
	?>
</div>

<div class="content">
	<?php
	if(!empty($search->share)) {
		$video = (array) json_decode($search->share);
	?>
		<h2>You're watching: <?php echo $video['title']; ?></h2>

		<div class="videowrap">
			<iframe src="<?php echo $video['embed']; ?>" title="<?php echo $video['title']; ?>" frameborder="0" allow="encrypted-media; web-share" referrerpolicy="strict-origin-when-cross-origin"></iframe>
		</div>

		<?php
		// Meta data
		$meta = array();
		if(!empty($video['views'])) $meta[] = "<strong>Views:</strong> ".number_format($video['views']);
		if(!empty($video['length'])) $meta[] = "<strong>Length:</strong> ".format_play_length($video['length']);
		if(!empty($video['timestamp'])) $meta[] = "<strong>Uploaded:</strong> ".the_date("M d, Y", $video['timestamp']);
		if(!empty($video['uploader']) && !empty($video['url_uploader'])) $meta[] = "<strong>Channel:</strong> <a href=\"".$video['url_uploader']."\" target=\"_blank\" title=\"".$video['uploader']."\">".$video['uploader']."</a>";

		if(count($meta) > 0) {
			echo "<p>".implode(' &bull; ', $meta)."</p>";
		}

		// Show description if there is one
		if(!empty($video['description'])) {
			echo "	<h3>Description</h3>";
			echo "	<p>".$video['description']."</p>";
		}

		// External links
		if(!is_null($video['links'])) {
			$links = array();
			foreach($video['links'] as $video_source => $video_url) {
				$links[] = "<a href=\"".$video_url."\" target=\"_blank\" title=\"Watch on ".$video_source."\">".$video_source."</a>";
			}
			echo "	<p>Watch on: ".implode(' &bull; ', $links)."</p>";
		}
		?>
		<p class="text-center"><a href="./results.php?q=<?php echo $search->query_urlsafe; ?>&t=3">Back to results</a></p>
		<p class="text-center"><small>Goosle does not store or distribute video files. Content may be subject to copyright.</small></p>
	<?php
	} else {
	?>
		<div class="error">Video ID is missing.</div>
	<?php
	}
	?>
</div>

<?php
	include ABSPATH . 'template-parts/footer.php';
} else {
	include ABSPATH . 'template-parts/login-error.php';
}
?>

</body>
</html>
