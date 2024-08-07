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
class OpenverseRequest extends EngineRequest {
	public function get_request_url() {
		$query = $this->search->query;

		// Max 200 chars
		$query = (strlen($query) > 200) ? substr($query, 0, 200) : $query;
		$query = implode(',', make_tags_from_string($query));

		// Safe search override
		if($this->search->safe == 0) {
			$safe = '1';
		} else {
			$safe = '0';
		}

		// Size override
		$size = 'small,medium,large';
		if($this->search->size == 1) $size = 'small';
		if($this->search->size == 2) $size = 'medium';
		if($this->search->size >= 3) $size = 'large';

		// All parameters and values: https://api.openverse.org/v1/#tag/images/operation/images_search
        $url = 'https://api.openverse.org/v1/images/?'.http_build_query(array(
        	'q' => $query, // Search query
        	'category' => 'photograph', // Only photos
			'size' => $size,
        	'format' => 'json', // Response format
        	'page_size' => 50, // How many results to get (Max 50)
        	'mature' => $safe // Safe search (1 = ON, 0 = OFF)
        ));

        unset($query, $safe, $size);

        return $url;
	}

    public function get_request_headers() {
		$token_file = ABSPATH.'cache/token.data';
		$token = unserialize(file_get_contents($token_file));

		return array(
			'Accept' => 'application/json, */*;q=0.8',
			'Content-type' => 'application/x-www-form-urlencoded',
			'Authorization' => 'Bearer '.$token['openverse']['access_token'],
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
		$number_of_results = $rank = $json_response['result_count'];

		// No results
        if($number_of_results == 0) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No results', 0);
			return $engine_result;
		}

		// Use API result
		foreach($json_response['results'] as $result) {
			// Find data and process data
			$image_thumb = (!empty($result['thumbnail'])) ? sanitize($result['thumbnail']) : null;
			$image_full = (!empty($result['url'])) ? sanitize($result['url']) : null;
			$url = (!empty($result['foreign_landing_url'])) ? sanitize($result['foreign_landing_url']) : null;
			$alt = (!empty($result['title'])) ? sanitize($result['title']) : null;
			$creator = (!empty($result['creator'])) ? " by ".sanitize($result['creator']) : null;
			$tags = (count($result['tags']) > 0) ? array_column($result['tags'], 'name') : make_tags_from_string($alt);

			// Skip broken results
			if(empty($image_thumb)) continue;
			if(empty($image_full)) continue;
			if(empty($url)) continue;

			// Optional
			$dimensions_w = (!empty($result['width'])) ? sanitize($result['width']) : null;
			$dimensions_h = (!empty($result['height'])) ? sanitize($result['height']) : null;

			// Prepare data
			if(!is_null($creator)) $alt = $alt.$creator;
			$tags = array_unique($tags);

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
				'tags' => $tags, // array
				'engine_rank' => $rank, // int
				// Optional
				'width' => $dimensions_w, // int | null
				'height' => $dimensions_h // int | null
			);
			$rank -= 1;
		}

		// Base info
		if(!empty($engine_temp)) {
			$engine_result['source'] = 'Openverse';
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, $number_of_results, count($engine_temp));

		unset($response, $json_response, $number_of_results, $rank);

		return $engine_result;
	}
}
?>
