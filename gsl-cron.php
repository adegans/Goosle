<?php
if(!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');

date_default_timezone_set('UTC');

require ABSPATH.'functions/tools-files.php';
require ABSPATH.'functions/tools.php';

$opts = load_opts();
/* ------------------------------------------------------------------------------------
*  Goosle - The fast, privacy oriented search tool that just works.
*
*  COPYRIGHT NOTICE
*  Copyright 2023-2025 Arnan de Gans. All Rights Reserved.
*
*  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
*  By using this code you agree to indemnify Arnan de Gans from any
*  liability that might arise from its use.
---------------------------------------------------------------------------------------
* Includes:
* - Clear out old cached results when using the file cache.
* - Update Box Office results
------------------------------------------------------------------------------------ */

/*--------------------------------------
// Clear out old cached files?
--------------------------------------*/
if($opts->cache_type == 'file') {
	delete_cached_results($opts->cache_time);

	echo "<p>- Expired file cache results deleted!</p>";
}

/* ------------------------------------------------------------------------------------
// Download new Boxoffice data every 6 hours or so
------------------------------------------------------------------------------------ */
if($opts->cache_type !== 'off' && $opts->enable_magnet_search == 'on') {
	require ABSPATH . 'functions/tools-search.php';
	require ABSPATH . 'functions/boxoffice-results.php';

	$yts = yts_boxoffice($opts, 'date_added');
	echo "<p>- ".count($yts)." YTS results!</p>";
	$eztv = eztv_boxoffice($opts);
	echo "<p>- ".count($eztv)." EZTV results!</p>";

	unset($yts, $eztv);
}

echo "<p><strong>Are there no errors on this page? We're done, you can close the tab/browser.</strong></p>";

exit;
?>
