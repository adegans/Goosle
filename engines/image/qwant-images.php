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
class QwantImageRequest extends EngineRequest {
	public function get_request_url() {
		$query = $this->search->query;

		// Size override
		$size = 'all'; // All sizes
		if(preg_match('/(size:)(small|medium|large|xlarge)/i', $this->search->query_terms[0], $matches)) {
			$size = $matches[1];
			$query = str_replace($this->search->query_terms[0], '', $query);

			// Engine specific
			if($size == 'xlarge') $size = 'large';
		}
		unset($matches);

		// Set locale
		$language = (strlen($this->opts->qwant_language) > 0 && strlen($this->opts->qwant_language < 6)) ? $this->opts->qwant_language : 'en_gb';

		// Based on https://github.com/locness3/qwant-api-docs and variables from qwant website
        $url = 'https://api.qwant.com/v3/search/images?'.http_build_query(array(
        	'q' => $query, // Search query
        	't' => 'images', // Type of search, Images
        	'count' => 50, // Up-to how many images to return (Max 50)
        	'size' => $size, // General image size
        	'locale' => $language, // In which language should the search be done
        	'device' => 'desktop', // What kind of device are we searching from?
        	'safesearch' => $this->search->safe // Safe search filter (0 = off, 1 = normal, 2 = strict)
        ));

        unset($query, $size, $language);

        return $url;
	}

    public function get_request_headers() {
		return array(
			'Accept' => 'application/json, */*;q=0.8',
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
		if(empty($json_response)) return $engine_temp;

		// Figure out results and base rank
		$number_of_results = $rank = $json_response['data']['result']['total'];

		// No results
        if($number_of_results == 0) return $engine_temp;

		foreach($json_response['data']['result']['items'] as $result) {
			// Find data and process data
			$image_full = sanitize($result['media']);
			$image_thumb = (!empty($result['thumbnail'])) ? sanitize($result['thumbnail']) : $image_full;
			$url = sanitize($result['url']);
			$alt = (!empty($result['title'])) ? sanitize($result['title']) : null;
			$dimensions_w = (!empty($result['width'])) ? sanitize($result['width']) : null;
			$dimensions_h = (!empty($result['height'])) ? sanitize($result['height']) : null;
			$filesize = (!empty($result['size'])) ? sanitize($result['size']) : null;

			// Skip broken results
			if(empty($image_full)) continue;
			if(empty($image_thumb)) continue;
			if(empty($url)) continue;

			// Process data
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
			$engine_result['source'] = 'Qwant';
			$engine_result['search'] = $engine_temp;
		}

		unset($response, $json_response, $number_of_results, $rank);

		return $engine_result;
	}
}
?>