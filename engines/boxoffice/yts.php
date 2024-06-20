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
	if(empty($json_response)) return $results;

	// No results
    if($json_response['data']['movie_count'] == 0) return $results;

	foreach($json_response['data']['movies'] as $result) {
		$name = sanitize($result['title']);

		$year = (array_key_exists('year', $result)) ? sanitize($result['year']) : 0;
		$category = (array_key_exists('genres', $result)) ? $result['genres'] : array();
		$rating = (array_key_exists('rating', $result)) ? sanitize($result['rating']) : 0;
		$summary = (array_key_exists('summary', $result)) ? sanitize($result['summary']) : "No summary provided";
		$thumbnail = (array_key_exists('medium_cover_image', $result)) ? sanitize($result['medium_cover_image']) : "";

		// Block these categories
		if(count(array_uintersect($category, $opts->yts_categories_blocked, 'strcasecmp')) > 0) continue;		
		// Set actual category
		$category = sanitize(implode(', ', $category));

		foreach($result['torrents'] as $download) {
			$hash = strtolower(sanitize($download['hash']));
			$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($name).'&tr='.implode('&tr=', $opts->magnet_trackers);
			$filesize = filesize_to_bytes(sanitize($download['size']));

			$type = (array_key_exists('type', $download)) ? sanitize(strtolower($download['type'])) : null;
			$quality = (array_key_exists('quality', $download)) ? sanitize($download['quality']) : null;
			$codec = (array_key_exists('video_codec', $download)) ? sanitize($download['video_codec']) : null;

			// Add codec to quality
			if(!empty($codec)) $quality = $quality.' '.$codec;
		
			$downloads[] = array (
				'hash' => $hash, 
				'magnet' => $magnet, 
				'filesize' => $filesize, 
				'type' => $type, 
				'quality' => $quality
			);
			unset($download, $hash, $magnet, $filesize, $type, $quality, $codec);
		}

		$results[] = array (
			'id' => uniqid(rand(0, 9999)), // Semi random string to separate results on the results page
			'name' => $name, // string
			'year' => $year, // int(4)
			'category' => $category, // string
			'rating' => $rating, // float|int
			'summary' => $summary, // string
			'thumbnail' => $thumbnail, // string|empty
			'magnet_links' => $downloads // array
		);
		
		unset($result, $name, $thumbnail, $year, $category, $rating, $url, $summary, $downloads);
	}
	unset($response, $json_response);

	// Cache last request if there is something to cache
	if($opts->cache_type !== 'off') {
		if(count($results) > 0) store_cached_results($opts->cache_type, $opts->hash, $api_url, $results, $opts->cache_time);
	}

	return $results;
}
?>