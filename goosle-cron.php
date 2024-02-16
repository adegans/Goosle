<?php
if(!defined('ABSPATH')) define('ABSPATH', dirname(__FILE__) . '/');

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
---------------------------------------------------------------------------------------
* Includes:
* - Clearing out old cached results when using the file cache.
---------------------------------------------------------------------------------------
* Execute this file with a cron once or twice a day.
* If you've enabled the access hash, don't forget to include ?a=YOUR_HASH to the url.
* 
* Example for 5 minutes past every midnight and noon: 
* 		5 0,12 * * * wget -qO - https://example.com/goosle-cron.php?a=YOUR_HASH
*
* Example for every midnight: 
* 		0 0 * * * wget -qO - https://example.com/goosle-cron.php?a=YOUR_HASH
------------------------------------------------------------------------------------ */

if(verify_hash($opts, $auth)) {
	// Clear out old cached files?
	if($opts->cache == "on" && $opts->cache_type == "file") {
		$ttl = intval($opts->cache_time) * 60;
		delete_cached_results($ttl);
	}

	echo "Done!";
} else {
	echo "Unauthorized!";
} 

exit;
?>