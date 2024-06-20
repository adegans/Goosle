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
function piratebay_boxoffice($opts, $amount) {
	$api_url = 'https://apibay.org/precompiled/data_top100_recent.json';

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
    if($json_response[0]['name'] == 'No results returned') return $results;

	$categories = array(
		100 => 'Audio',
		101 => 'Music',
		102 => 'Audio Book',
		103 => 'Sound Clips',
		104 => 'Audio FLAC',
		199 => 'Audio Other',

		200 => 'Video',
		201 => 'Movie',
		202 => 'Movie DVDr',
		203 => 'Music Video',
		204 => 'Movie Clip',
		205 => 'TV Show',
		206 => 'Handheld',
		207 => 'HD Movie',
		208 => 'HD TV Show',
		209 => '3D Movie',
		210 => 'CAM/TS',
		211 => 'UHD/4K Movie',
		212 => 'UHD/4K TV Show',
		299 => 'Video Other',
		
		300 => 'Applications',
		301 => 'Apps Windows',
		302 => 'Apps Apple',
		303 => 'Apps Unix',
		304 => 'Apps Handheld',
		305 => 'Apps iOS',
		306 => 'Apps Android',
		399 => 'Apps Other OS',

		400 => 'Games',
		401 => 'Games PC',
		402 => 'Games Apple',
		403 => 'Games PSx',
		404 => 'Games XBOX360',
		405 => 'Games Wii',
		406 => 'Games Handheld',
		407 => 'Games iOS',
		408 => 'Games Android',
		499 => 'Games Other OS',
		
		500 => 'Porn',
		501 => 'Porn Movie',
		502 => 'Porn Movie DVDr',
		503 => 'Porn Pictures',
		504 => 'Porn Games',
		505 => 'Porn HD Movie',
		506 => 'Porn Movie Clip',
		507 => 'Porn UHD/4K Movie',
		599 => 'Porn Other',

		600 => 'Other',
		601 => 'Other E-Book',
		602 => 'Other Comic',
		603 => 'Other Pictures',
		604 => 'Other Covers',
		605 => 'Other Physibles',
		699 => 'Other Other'
	);

	foreach($json_response as $result) {		
		$name = sanitize($result['name']);
		$hash = strtolower(sanitize($result['info_hash']));
		$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($name).'&tr='.implode('&tr=', $opts->magnet_trackers);
		$seeders = sanitize($result['seeders']);
		$leechers = sanitize($result['leechers']);
		$filesize = sanitize($result['size']);
		$category = sanitize($result['category']);
		
		// Block these categories
		if(in_array($category, $opts->piratebay_categories_blocked)) continue;
		// Set actual category
		$category = $categories[$category];
		
		$results[] = array(
			'id' => uniqid(rand(0, 9999)), // Semi random string to separate results on the results page
			'name' => $name, // string
			'magnet' => $magnet, // string
			'seeders' => $seeders, // int
			'leechers' => $leechers, // int
			'filesize' => $filesize, // int
			'category' => $category // string
		);

		unset($result, $name, $magnet, $seeders, $leechers, $filesize, $category);
	}
	unset($response, $json_response, $categories);

	$results = array_slice($results, 0, $amount);

	// Cache last request if there is something to cache
	if($opts->cache_type !== 'off') {
		if(count($results) > 0) store_cached_results($opts->cache_type, $opts->hash, $api_url, $results, $opts->cache_time);
	}

	return $results;
}
?>