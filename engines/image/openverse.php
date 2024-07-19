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
		// Safe search override
		if($this->search->safe == 0) {
			$safe = '1';
		} else {
			$safe = '0';
		}

        $url = 'https://api.openverse.org/v1/images/?'.http_build_query(array(
        	'q' => $this->search->query, // Search query
        	'format' => 'json', // Response format
        	'page_size' => 50, // How many results to get
        	'mature' => $safe // Safe search (1 = ON, 0 = OFF)
        ));

        unset($safe);

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
			$image_full = sanitize($result['url']);
			$image_thumb = (!empty($result['thumbnail'])) ? sanitize($result['thumbnail']) : $image_full;
			$url = sanitize($result['foreign_landing_url']);
			$alt = (!is_null($result['title'])) ? sanitize($result['title']) : null;
			$dimensions_w = (!is_null($result['width'])) ? sanitize($result['width']) : null;
			$dimensions_h = (!is_null($result['height'])) ? sanitize($result['height']) : null;
			$filesize = (!is_null($result['filesize'])) ? sanitize($result['filesize']) : null;
			$creator = (!empty($result['creator'])) ? " by ".sanitize($result['creator']) : null;

			// Skip broken results
			if(empty($image_thumb)) continue;
			if(empty($image_full)) continue;
			if(empty($url)) continue;

			// Process data
			if(!is_null($creator)) $alt = $alt.$creator;
			if(!is_null($filesize)) $filesize = intval(preg_replace('/[^0-9]+/', '', $filesize));

			// Skip duplicate IMAGE urls/results
            if(!empty($engine_temp)) {
                if(in_array($image_full, array_column($engine_temp, 'image_full'))) continue;
            }

			$engine_temp[] = array (
				// Required
				'image_full' => $image_full, // string
				'image_thumb' => $image_thumb, // string
				'url' => $url, // string
				'engine_rank' => $rank, // int
				// Optional
				'alt' => $alt, // string | null 
				'width' => $dimensions_w, // int | null
				'height' => $dimensions_h, // int | null
				'filesize' => $filesize, // int | null
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