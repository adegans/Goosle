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

/*--------------------------------------
// Do periodic update check
--------------------------------------*/
function check_update() {
	$cache_file = ABSPATH.'cache/version.data';
	
	// Currently installed version
	$current_version = "1.5";

	if(!is_file($cache_file)) {
		// Create update cache file if it doesn't exist
	    $version = array('current' => $current_version, 'latest' => '0.0', 'checked' => 0, 'url' => '');
	    file_put_contents($cache_file, serialize($version));
	} else {
		// Get update information
		$version = unserialize(file_get_contents($cache_file));
	}

	// Update check, every week
	if($version['checked'] < time() - 604800) {
		$response = do_curl_request( 
			'https://api.github.com/repos/adegans/goosle/releases/latest', // (string) Where?
			array('Accept: application/json, */*;q=0.7', 'User-Agent: goosle/'.$version['current'].';'), // (array) User agent + Headers
			'get', // (string) post/get
			null // (assoc array|null) Post body
		);
		$json_response = json_decode($response, true);
		
		// Got a response? Store it!
		if(!empty($json_response)) {
			// Update version info
			$version = array('current' => $version['current'], 'latest' => $json_response['tag_name'], 'checked' => time(), 'url' => $json_response['html_url']);
			file_put_contents($cache_file, serialize($version));
		}
	}
}

/*--------------------------------------
// Show version in footer
--------------------------------------*/
function show_version() {
	$cache_file = ABSPATH.'cache/version.data';
	
	if(is_file($cache_file)) {
		// Get update information
		$version = unserialize(file_get_contents($cache_file));

		// TODO: Remove in a future version
		if(!isset($version['current'])) $version['current'] = "1.5";

		// Format current version for footer
		$show_version = "<a href=\"https://github.com/adegans/Goosle/\" target=\"_blank\">Goosle ".$version['current']."</a>.";
	
		// Check if a newer version is available and add it to the version display
		if(version_compare($version['current'], $version['latest'], '<')) {
			$show_version .= " <a href=\"".$version['url']."\" target=\"_blank\" class=\"update\">Version ".$version['latest']." is available!</a>";
		}
	} else {
		// If the update cache doesn't exist...
		$show_version = "<a href=\"https://github.com/adegans/Goosle/\" target=\"_blank\">Goosle</a>.";
	}

	return $show_version;
}
?>