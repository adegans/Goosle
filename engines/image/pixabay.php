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
class PixabayRequest extends EngineRequest {
	public function get_request_url() {
		// Format query & max 100 chars
		$query = implode(' ', make_terms_array_from_string(limit_string_length($this->search->query, 100, '')));

		// Safe search override
		if($this->search->safe == 0) {
			$safe = true;
		} else {
			$safe = false;
		}

		// Size override
		$min_width = 1280;
		$min_height = 720;
		if($this->search->size == 1) {
			$min_width = 640;
			$min_height = 360;
		}
		if($this->search->size == 2) {
			$min_width = 1280;
			$min_height = 720;
		}
		if($this->search->size == 3) {
			$min_width = 1600;
			$min_height = 900;
		}
		if($this->search->size == 4) {
			$min_width = 2560;
			$min_height = 1440;
		}

		// All parameters and values: https://pixabay.com/api/docs/
        $url = 'https://pixabay.com/api/?'.http_build_query(array(
        	'key' => $this->opts->pixabay_api_key, // Api Key for authentification
        	'q' => $query, // Search query
        	'image_type' => 'photo', // Only photos
        	'per_page' => 100, // How many results to get (Max 200)
        	'min_width' => $min_width, // Minimum width
        	'min_height' => $min_height, // Minimum height
        	'safesearch' => $safe // Safe search (1 = ON, 0 = OFF)
        ));

        unset($query, $safe, $min_height, $min_width);

        return $url;
	}

    public function get_request_headers() {
		return array(
			'Accept' => 'application/json, */*;q=0.8',
			'Content-type' => 'application/x-www-form-urlencoded',
			'Accept-Language' => null,
			'Accept-Encoding' => null,
			'Sec-Fetch-Dest' => null,
			'Sec-Fetch-Mode' => null,
			'Sec-Fetch-Site' => null
		);
	}

	public function parse_results($response) {
		$engine_temp = $engine_result = array();
		$json_response = json_decode($response, true);

		// No response
		if(empty($json_response)) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No response', 0);
			return $engine_result;
		}

		// Figure out results and base rank
		$number_of_results = $rank = count($json_response['hits']);

		// No results
        if($number_of_results == 0) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No results', 0);
			return $engine_result;
		}

		// Use API result
		foreach($json_response['hits'] as $result) {
			// Find data and process data
			$image_thumb = (!empty($result['previewURL'])) ? sanitize($result['previewURL']) : null;
			$image_full = (!empty($result['largeImageURL'])) ? sanitize($result['largeImageURL']) : null;
			$url = (!empty($result['pageURL'])) ? sanitize($result['pageURL']) : null;
			$alt = (!empty($result['tags'])) ? $result['tags'] : null;
			$creator = (!empty($result['user'])) ? " by ".sanitize($result['user']) : null;

			// Skip broken results
			if(empty($image_thumb)) continue;
			if(empty($image_full)) continue;
			if(empty($url)) continue;

			// Optional
			$dimensions_w = (!empty($result['imageWidth'])) ? sanitize($result['imageWidth']) : null;
			$dimensions_h = (!empty($result['imageHeight'])) ? sanitize($result['imageHeight']) : null;

			// Process data
			if(!is_null($creator)) $alt = $alt.$creator;

			// Skip duplicate IMAGE urls/results
			if(!empty($engine_temp)) {
				if(in_array($image_full, array_column($engine_temp, 'image_full'))) continue;
			}

			$engine_temp[] = array (
				// Required
				'image_thumb' => $image_thumb, // string
				'image_full' => $image_full, // string
				'url' => $url, // string
				'alt' => $alt, // string
				'engine_rank' => $rank, // int
				// Optional
				'width' => $dimensions_w, // int | null
				'height' => $dimensions_h // int | null
			);
			$rank -= 1;
		}

		// Base info
		if(!empty($engine_temp)) {
			$engine_result['source'] = 'Pixabay';
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, $number_of_results, count($engine_temp));

		unset($response, $json_response, $number_of_results, $rank);

		return $engine_result;
	}
}
?>
