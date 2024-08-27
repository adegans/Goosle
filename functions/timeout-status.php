<?php
if(!defined('ABSPATH')) define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

require ABSPATH.'functions/tools.php';

$opts = load_opts();
$auth = (isset($_GET['a'])) ? sanitize($_GET['a']) : $opts->user_auth;
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
    <title>Goosle Search Timeouts</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Get your Goosle on! - The best meta search engine for private and fast internet fun!" />

	<link rel="icon" href="../favicon.ico" />
	<link rel="apple-touch-icon" href="../apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo get_base_url($opts->siteurl); ?>/functions/oauth-openverse.php" />

    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/styles.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/<?php echo $opts->colorscheme; ?>.css"/>
</head>

<body class="plainpage">
<?php
if(verify_hash($opts->hash_auth, $opts->hash, $auth)) {
?>

<div class="content">
	<h1><span class="goosle-g">G</span>oosle</h1>
	<p>This page lists all recorded timeouts, currently active and from the past.<br>
	If a search engine doesn't work, or results are missing. Check here if it isn't simply in a timeout.</p>

	<p>A timeout will be set by Goosle if a search engine blocks your request or you make too many requests. Depending on the response code a timeout of 15 minutes up-to 12 hours can be set. Dates in red are still in effect.</p>

	<h2>Timeouts</h2>
	<?php
	$timeout_file = ABSPATH.'cache/timeout.data';

	if(is_file($timeout_file)) {
		$timeouts = unserialize(file_get_contents($timeout_file));

		echo "<ul>";
		foreach($timeouts as $engine => $expiry) {
			$class = ($expiry > time()) ? "red" : "green";
			echo "<li>".$engine.": <span class=\"".$class."\">".the_date('M d, Y H:i:s', $expiry)."</span>";
		}
		echo "<ul>";
	} else {
		echo "No timeouts have been set";
	}
	?>
	<p><a href="/">Back to Goosle</a></p>
</div>

<?php
} else {
	include_once('error.php');
}
?>

</body>
</html>
