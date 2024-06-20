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
function nyaa_boxoffice($opts, $amount) {
	$api_url = 'https://nyaa.si/';
	$results = array();

	// If there is a cached result use that instead
	if($opts->cache_type !== 'off' && has_cached_results($opts->cache_type, $opts->hash, $api_url, $opts->cache_time)) {
		return fetch_cached_results($opts->cache_type, $opts->hash, $api_url);
	}

	$response = do_curl_request( 
		$api_url, // (string) Where?
		array('Accept: text/html, application/xhtml+xml, application/xml;q=0.8, */*;q=0.7', 'User-Agent: '.$opts->user_agents[0].';'), // (array) User agent + Headers
		'get', // (string) post/get
		null // (assoc array|null) Post body
	);
	$xpath = get_xpath($response);
	$results = array();
	
	// No response
	if(!$xpath) return $results;
	
	// Scrape the results
	$limit = $amount + 16;
	$scrape = $xpath->query("//tbody/tr[position() <= $limit]");

	// No results
    if(count($scrape) == 0) return $results;

	foreach($scrape as $result) {
		$meta = $xpath->evaluate(".//td[@class='text-center']", $result);
		
		$name = $xpath->evaluate(".//td[@colspan='2']//a[not(contains(@class, 'comments'))]/@title", $result);
		if($name->length == 0) continue;

		$magnet = $xpath->evaluate(".//a[2]/@href", $meta[0]);
		if($magnet->length == 0) $magnet = $xpath->evaluate(".//a/@href", $meta[0]);
		if($magnet->length == 0) continue;

		$name = sanitize($name[0]->textContent);
		$magnet = sanitize($magnet[0]->textContent);
		parse_str(parse_url($magnet, PHP_URL_QUERY), $hash_parameters);
		$hash = strtolower(str_replace('urn:btih:', '', $hash_parameters['xt']));
		$seeders = sanitize($meta[3]->textContent);
		$leechers = sanitize($meta[4]->textContent);
		$filesize =  filesize_to_bytes(str_replace('TiB', 'TB', str_replace('GiB', 'GB', str_replace('MiB', 'MB', str_replace('KiB', 'KB', sanitize($meta[1]->textContent))))));
		$category = sanitize($xpath->evaluate(".//td[1]//a/@title", $result)[0]->textContent);
		$category = str_replace(' - ', '/', $category);

		$results[] = array (
			'id' => uniqid(rand(0, 9999)), // Semi random string to separate results on the results page
			'name' => $name, // string
			'magnet' => $magnet, // string
			'seeders' => $seeders, // int
			'leechers' => $leechers, // int
			'filesize' => $filesize, // int
			'category' => $category, // string
		);

		unset($result, $meta, $name, $magnet, $seeders, $leechers, $filesize, $category);
	}
	unset($response, $xpath, $scrape, $limit);

	$results = array_slice($results, 0, $amount);

	// Cache last request if there is something to cache
	if($opts->cache_type !== 'off') {
		if(count($results) > 0) store_cached_results($opts->cache_type, $opts->hash, $api_url, $results, $opts->cache_time);
	}

	return $results;
}
?>