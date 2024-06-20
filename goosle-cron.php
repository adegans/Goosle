<?php
if(!defined('ABSPATH')) define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
date_default_timezone_set('UTC');

require ABSPATH.'functions/tools.php';
require ABSPATH.'functions/tools-update.php';

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
---------------------------------------------------------------------------------------
* Includes:
* - Checking for updates.
* - Clearing out old cached results when using the file cache.
* - Renewing access token for Openverse (Expires every 12 hours)
------------------------------------------------------------------------------------ */

if(verify_hash($opts->hash_auth, $opts->hash, $auth)) {
	// Check for updates
	check_update();
	echo "Update cached updated!<br />";

	// Clear out old cached files?
	if($opts->cache_type == 'file') {
		delete_cached_results($opts->cache_time);
		
		echo "Expired file cache results deleted!<br />";
	}

	// Possibly renew the Openverse access token
	if($opts->enable_image_search == 'on' && $opts->enable_openverse == 'on') {
		$token_file = ABSPATH.'cache/token.data';

		if(is_file($token_file)) {
			$tokens = unserialize(file_get_contents($token_file));
			$registration = $tokens['openverse'];
	
			// Is the token expired?
			if($registration['expires'] < time()) {
				$new_token = do_curl_request(
					'https://api.openverse.org/v1/auth_tokens/token/', // (string) Where?
					array('Accept: application/json, */*;q=0.8', 'User-Agent: '.$opts->user_agents[0].';', 'Authorization: Bearer'.$registration['client_id']), // (array) Headers
					'post', // (string) post/get
					array('grant_type' => 'client_credentials', 'client_id' => $registration['client_id'], 'client_secret' => $registration['client_secret']) // (assoc array) Post body
				);
				$new_token = json_decode($new_token, true);
				
				$new_token['expires_in'] = time() + ($new_token['expires_in'] - 3600);
		
				oauth_store_token($token_file, 'openverse', array('client_id' => $registration['client_id'], 'client_secret' => $registration['client_secret'], 'access_token' => $new_token['access_token'], 'expires' => $new_token['expires_in']));
	
				echo "New Openverse token stored!<br />";
			}
		}
	}
	echo "No errors on this page? We're done!<br />";
} else {
	echo "Unauthorized!";
} 

exit;
?>