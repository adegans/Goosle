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
function eztv_boxoffice($opts) {
	$api_url = 'https://eztvx.to/api/get-torrents?'.http_build_query(array('limit' => 100));

	// If there is a cached result use that instead
	if($opts->cache_type !== 'off' && has_cached_results($opts->cache_type, $opts->hash, $api_url, $opts->cache_time)) {
		return fetch_cached_results($opts->cache_type, $opts->hash, $api_url);
	}

	$response = do_curl_request( 
		$api_url, // (string) Where?
		array('Accept: application/json, */*;q=0.7', 'User-Agent: '.$opts->user_agents[0].';'), // (array) User agent + Headers
		'get', // (string) post/get
		null // (assoc array|null) Post body
	);
	$json_response = json_decode($response, true);
	$results = $results_temp = array();
	
	// No response
	if(empty($json_response)) return $results;

	// Nothing found
	if($json_response['torrents_count'] == 0) return $results;
	
	foreach($json_response['torrents'] as $result) {
		$name = sanitize($result['title']);
		$hash = strtolower(sanitize($result['hash']));
		$thumbnail = sanitize($result['small_screenshot']);
		$magnet_link = sanitize($result['magnet_url']);
		$filesize = sanitize($result['size_bytes']);

		// Get extra data
		$quality = find_video_quality($name);
		$codec = find_video_codec($name);

		// Add codec to quality
		if(!empty($codec)) $quality = $quality.' '.$codec;

		// Clean up show name
		$name = (preg_match('/.+?(?=[0-9]{3,4}p|xvid|divx|(x|h)26(4|5))/i', $name, $clean_name)) ? $clean_name[0] : $name; // Break off show name before video resolution
		$name = trim(str_replace(array('S0E0', 'S00E00'), '', $name)); // Strip spaces and empty season/episode indicator from name

		// Group the same episodes in one result
		if(count($results) > 0) {
			// Do a match
			$result_urls = array_column($results, 'name', 'id');
			$found_id = array_search($name, $result_urls); // Return the result ID
		} else {
			$found_id = false;
		}

		if($found_id !== false) {
			// Add the download to a previous result
			$results[$found_id]['magnet_links'][] = array(
				'hash' => $hash, 
				'magnet' => $magnet_link, 
				'filesize' => $filesize, 
				'quality' => $quality
			);
		} else {
			$result_id = md5($name); // Predictable/repeatable 'unique' string

			// First/new result
			$results[$result_id] = array (
				'id' => $result_id, // string
				'name' => $name, // string
				'thumbnail' => $thumbnail, // string
				'magnet_links' => array(array( // Yes, two array...
					'hash' => $hash, // string
					'magnet' => $magnet_link, // string 
					'filesize' => $filesize, // int
					'quality' => $quality, // string
				))
			);
		}
		unset($result, $result_urls, $found_id, $result_id, $name, $hash, $thumbnail, $magnet_link, $quality, $codec);
	}
	unset($response, $json_response);

	// Cache last request if there is something to cache
	if($opts->cache_type !== 'off') {
		if(count($results) > 0) store_cached_results($opts->cache_type, $opts->hash, $api_url, $results, $opts->cache_time);
	}

	return $results;
}
?>