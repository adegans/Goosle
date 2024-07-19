<?php
if(!defined('ABSPATH')) define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
date_default_timezone_set('UTC');

require ABSPATH.'functions/tools.php';

$opts = load_opts();
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
* - Check for updates.
* - Clear out old cached results when using the file cache.
* - Renew access token for Openverse (Expires every 12 hours)
------------------------------------------------------------------------------------ */

if(verify_hash('on', $opts->hash, $opts->user_auth)) {
	/*--------------------------------------
	// Do update check
	--------------------------------------*/
	global $current_version;
	$version_file = ABSPATH.'cache/version.data';
	
	if(!is_file($version_file)) {
		// Create update cache file if it doesn't exist
	    $version = array('current' => $current_version, 'latest' => '0.0', 'checked' => 0, 'url' => '');
	    file_put_contents($version_file, serialize($version));
	} else {
		// Get update information
		$version = unserialize(file_get_contents($version_file));
	}

	// Update check, every week
	if($version['checked'] < time() - 604800) {
		$response = do_curl_request( 
			'https://api.github.com/repos/adegans/goosle/releases/latest', // (string) Where?
			array('Accept: application/json, */*;q=0.7', 'User-Agent: goosle/'.$current_version.';'), // (array) User agent + Headers
			'get', // (string) post/get
			null // (assoc array|null) Post body
		);
		$json_response = json_decode($response, true);
		
		// Got a response? Store it!
		if(!empty($json_response)) {
			// Update version info
			$version = array('current' => $current_version, 'latest' => $json_response['tag_name'], 'checked' => time(), 'url' => $json_response['html_url']);
			file_put_contents($version_file, serialize($version));
			
			echo "<p>- Checked for updates and update cache updated!</p>";
		}
	}

	/*--------------------------------------
	// Clear out old cached files?
	--------------------------------------*/
	if($opts->cache_type == 'file') {
		delete_cached_results($opts->cache_time);
		
		echo "<p>- Expired file cache results deleted!</p>";
	}

	/*--------------------------------------
	// Renew the Openverse access token
	--------------------------------------*/
	if($opts->enable_image_search == 'on' && $opts->enable_openverse == 'on') {
		$token_file = ABSPATH.'cache/token.data';

		if(is_file($token_file)) {
			$tokens = unserialize(file_get_contents($token_file));
			$registration = $tokens['openverse'];
	
			// Is the token expired?
			if($registration['expires'] < time()) {
				$response = do_curl_request(
					'https://api.openverse.org/v1/auth_tokens/token/', // (string) Where?
					array('Accept: application/json, */*;q=0.8', 'User-Agent: '.$opts->user_agents[0].';', 'Authorization: Bearer'.$registration['client_id']), // (array) Headers
					'post', // (string) post/get
					array('grant_type' => 'client_credentials', 'client_id' => $registration['client_id'], 'client_secret' => $registration['client_secret']) // (assoc array) Post body
				);
				$json_response = json_decode($response, true);
				
				// Got a response
				if(!empty($json_response)) {
					// Got some data?
			        if(array_key_exists('access_token', $json_response)) {

						$json_response['expires_in'] = time() + ($json_response['expires_in'] - 3600);
				
						oauth_store_token($token_file, 'openverse', array('client_id' => $registration['client_id'], 'client_secret' => $registration['client_secret'], 'access_token' => $json_response['access_token'], 'expires' => $json_response['expires_in']));
			
						echo "<p>- New Openverse token stored!</p>";
					}
				}
				unset($response, $json_response);
			}
		}
	}
	echo "<p><strong>Are there no errors on this page? We're done, you can close the tab/browser.</strong></p>";
} else {
	echo "<p>!! Unauthorized !!</p>";
} 

exit;
?>