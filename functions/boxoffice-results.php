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
function yts_boxoffice($opts, $what) {
	$api_url = 'https://yts.lt/api/v2/list_movies.json?'.http_build_query(array('limit' => 40, 'sort_by' => $what));

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
	$results = array();

	// No response
	if(empty($json_response)) {
		if($opts->querylog == 'on') querylog('BoxofficeYTS', 'a', $api_url, 'No response', 0);
		return $results;
	}

	// No Results
	if($json_response['data']['movie_count'] == 0) {
		if($opts->querylog == 'on') querylog('BoxofficeYTS', 'a', $api_url, 'No Results', 0);
		return $results;
	}

	foreach($json_response['data']['movies'] as $result) {
		$title = sanitize($result['title']);
		$imdb = sanitize($result['imdb_code']);

		$year = (!empty($result['year'])) ? sanitize($result['year']) : 0;
		$category = (!empty($result['genres'])) ? $result['genres'] : null;
		$language = (!empty($result['language'])) ? sanitize($result['language']) : null;
		$rating = (!empty($result['rating'])) ? sanitize($result['rating']) : null;
		$mpa_rating = (!empty($result['mpa_rating'])) ? sanitize($result['mpa_rating']) : null;
		$thumbnail = (!empty($result['medium_cover_image'])) ? sanitize($result['medium_cover_image']) : null;
		if(is_null($thumbnail)) $thumbnail = (!empty($result['small_cover_image'])) ? sanitize($result['small_cover_image']) : "";

		// Process extra data
		if(is_array($category)) {
			// Block these categories
			//if(count(array_uintersect($category, $opts->yts_categories_blocked, 'strcasecmp')) > 0) continue;

			// Set actual category
			$category = sanitize(implode(', ', $category));
		}

		foreach($result['torrents'] as $download) {
			$hash = strtolower(sanitize($download['hash']));
			$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($title).'&tr='.implode('&tr=', $opts->magnet_trackers);
			$filesize = filesize_to_bytes(sanitize($download['size']));

			$type = (!empty($download['type'])) ? sanitize(strtolower($download['type'])) : null;
			$quality = (!empty($download['quality'])) ? sanitize($download['quality']) : null;
			$codec = (!empty($download['video_codec'])) ? sanitize($download['video_codec']) : null;
			$bitrate = (!empty($download['bit_depth'])) ? sanitize($download['bit_depth']) : null;
			$audio = (!empty($download['audio_channels'])) ? sanitize('AAC '.$download['audio_channels']) : null;

			// Add codec and bitrate to quality
			if(!empty($codec)) $quality = $quality.' '.$codec;
			if(!empty($bitrate)) $quality = $quality.' '.$bitrate.'bit';

			$downloads[] = array (
				'hash' => $hash,
				'magnet' => $magnet,
				'filesize' => $filesize,
				'type' => $type,
				'quality' => $quality,
				'audio' => $audio
			);
			unset($download, $hash, $magnet, $filesize, $type, $quality, $codec, $bitrate, $audio);
		}

		$result_id = md5($title);

		$results[$result_id] = array (
			'id' => $result_id, // Semi random string to separate results
			'title' => $title, // string
			'imdb_id' => $imdb, // string
			'year' => $year, // int(4)
			'category' => $category, // string|null
			'language' => $language, // string|null
			'rating' => $rating, // float|null
			'mpa_rating' => $mpa_rating, // string|null
			'thumbnail' => $thumbnail, // string|empty
			'magnet_links' => $downloads // array
		);

		unset($result, $title, $imdb, $thumbnail, $year, $category, $language, $rating, $url, $downloads);
	}
	unset($response, $json_response);

	if($opts->querylog == 'on') querylog('BoxofficeYTS', 'a', $api_url, 'up-to 40', count($results));

	// Cache last request if there is something to cache
	if($opts->cache_type !== 'off') {
		if(count($results) > 0) store_cached_results($opts->cache_type, $opts->hash, $api_url, $results, $opts->cache_time);
	}

	return $results;
}

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
