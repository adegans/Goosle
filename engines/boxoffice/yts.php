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
function yts_boxoffice($opts, $what) {
	$api_url = 'https://yts.mx/api/v2/list_movies.json?'.http_build_query(array('limit' => 40, 'sort_by' => $what));

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

		$year = (!empty($result['year'])) ? sanitize($result['year']) : 0;
		$category = (!empty($result['genres'])) ? $result['genres'] : null;
		$language = (!empty($result['language'])) ? sanitize($result['language']) : null;
		$rating = (!empty($result['rating'])) ? sanitize($result['rating']) : null;
		$mpa_rating = (!empty($result['mpa_rating'])) ? sanitize($result['mpa_rating']) : null;
		$summary = (!empty($result['summary'])) ? sanitize($result['summary']) : null;
		if(is_null($summary)) $summary = (!empty($result['synopsis'])) ? sanitize($result['synopsis']) : "No summary provided";
		$thumbnail = (!empty($result['medium_cover_image'])) ? sanitize($result['medium_cover_image']) : null;
		if(is_null($thumbnail)) $thumbnail = (!empty($result['small_cover_image'])) ? sanitize($result['small_cover_image']) : "";

		// Process extra data
		if(is_array($category)) {
			// Block these categories
			if(count(array_uintersect($category, $opts->yts_categories_blocked, 'strcasecmp')) > 0) continue;
			
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
			'year' => $year, // int(4)
			'category' => $category, // string|null
			'language' => $language, // string|null
			'rating' => $rating, // float|null
			'mpa_rating' => $mpa_rating, // string|null
			'summary' => $summary, // string
			'thumbnail' => $thumbnail, // string|empty
			'magnet_links' => $downloads // array
		);
		
		unset($result, $title, $thumbnail, $year, $category, $language, $rating, $url, $summary, $downloads);
	}
	unset($response, $json_response);

	if($opts->querylog == 'on') querylog('BoxofficeYTS', 'a', $api_url, 'up-to 40', count($results));

	// Cache last request if there is something to cache
	if($opts->cache_type !== 'off') {
		if(count($results) > 0) store_cached_results($opts->cache_type, $opts->hash, $api_url, $results, $opts->cache_time);
	}

	return $results;
}
?>