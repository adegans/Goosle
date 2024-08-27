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
	if(empty($json_response)) {
		if($opts->querylog == 'on') querylog('BoxofficeEZTV', 'a', $api_url, 'No response', 0);
		return $results;
	}

	// No Results
	if($json_response['torrents_count'] == 0) {
		if($opts->querylog == 'on') querylog('BoxofficeEZTV', 'a', $api_url, 'No Results', 0);
		return $results;
	}

	foreach($json_response['torrents'] as $result) {
		$title = (!empty($result['title'])) ? sanitize($result['title']) : null;
		$imdb = sanitize($result['imdb_id']);

		$year = (!empty($result['date_released_unix'])) ? gmdate('Y', sanitize($result['date_released_unix'])) : null;
		$hash = (!empty($result['hash'])) ? strtolower(sanitize($result['hash'])) : null;
		$thumbnail = (!empty($result['small_screenshot'])) ? sanitize($result['small_screenshot']) : null;
		$magnet_link = (!empty($result['magnet_url'])) ? sanitize($result['magnet_url']) : null;
		$filesize = (!empty($result['size_bytes'])) ? sanitize($result['size_bytes']) : null;

		// Get extra data
		$quality = find_video_quality($title);
		$codec = find_video_codec($title);
		$audio = find_audio_codec($title);

		// Add codec to quality
		if(!empty($codec)) $quality = $quality.' '.$codec;

		// Clean up show name and fix up the imdb ID
		$title = (preg_match('/.+?(?=[0-9]{3,4}p|xvid|divx|(x|h)26(4|5))/i', $title, $clean_name)) ? $clean_name[0] : $title; // Break off show name before video resolution
		$title = trim(str_replace(array('S0E0', 'S00E00'), '', $title)); // Strip spaces and empty season/episode indicator from name
		$imdb = 'tt'.$imdb;

		// Group the same episodes in one result
		if(count($results) > 0) {
			// Do a match
			$result_urls = array_column($results, 'title', 'id');
			$found_id = array_search($title, $result_urls); // Return the result ID
		} else {
			$found_id = false;
		}

		if($found_id !== false) {
			// Add the download to a previous result
			$results[$found_id]['magnet_links'][] = array(
				'hash' => $hash,
				'magnet' => $magnet_link,
				'filesize' => $filesize,
				'quality' => $quality,
				'audio' => $audio
			);
		} else {
			$result_id = md5($title); // Predictable/repeatable 'unique' string, can't be md5($hash) other nothing will match/merge!

			// First/new result
			$results[$result_id] = array (
				'id' => $result_id, // string
				'title' => $title, // string
				'imdb_id' => $imdb, // string
				'year' => $year, // int(4)
				'thumbnail' => $thumbnail, // string
				'magnet_links' => array(array( // Yes, two array (For merging results)...
					'hash' => $hash, // string
					'magnet' => $magnet_link, // string
					'filesize' => $filesize, // int
					'quality' => $quality, // string
					'audio' => $audio // string
				))
			);
		}
		unset($result, $result_urls, $found_id, $result_id, $title, $hash, $thumbnail, $magnet_link, $quality, $codec);
	}
	unset($response, $json_response);

	if($opts->querylog == 'on') querylog('BoxofficeEZTV', 'a', $api_url, 'up-to 100', count($results));

	// Cache last request if there is something to cache
	if($opts->cache_type !== 'off') {
		if(count($results) > 0) store_cached_results($opts->cache_type, $opts->hash, $api_url, $results, $opts->cache_time);
	}

	return $results;
}
?>
