<?php
if(!defined('ABSPATH')) define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

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
* - Renewing access token for Openverse (Expires every 12 hours)
------------------------------------------------------------------------------------ */

if(verify_hash($opts, $auth)) {
	// Clear out old cached files?
	if($opts->cache_type == "file") {
		$ttl = intval($opts->cache_time) * 60;

		delete_cached_results($ttl);
		
		echo "Cache deleted!<br />";
	}

	// Possibly renew the Openverse access token
	if($opts->enable_image_search == "on" && $opts->enable_openverse == "on") {
		require ABSPATH."functions/oauth-functions.php";

		$token_file = ABSPATH.'cache/token.data';

		if(is_file($token_file)) {
			$tokens = unserialize(file_get_contents($token_file));
			$registration = $tokens['openverse'];
	
			if($registration['expires'] < time()) {
				// Is the token expired?
				$new_token = oath_curl_request(
					'https://api.openverse.org/v1/auth_tokens/token/', // Where?
					$opts->user_agents[0], // Who?
					'post', // post/get
					array('Authorization: Bearer'.$registration['client_id']), // Additional headers
					'grant_type=client_credentials&client_id='.$registration['client_id'].'&client_secret='.$registration['client_secret'] // Payload
				);
				
				$new_token['expires_in'] = time() + ($new_token['expires_in'] - 3600);
		
				oath_store_token($token_file, 'openverse', array("client_id" => $registration['client_id'], "client_secret" => $registration['client_secret'], "access_token" => $new_token['access_token'], "expires" => $new_token['expires_in']));
	
				echo "New Openverse token stored!<br />";
			}
		}
	}
} else {
	echo "Unauthorized!";
} 

exit;
?>