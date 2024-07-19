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
class QwantNewsRequest extends EngineRequest {
    public function get_request_url() {
		// Set locale
		$language = (preg_match('/[a-z]{2}_[a-z]{2}/i', $this->opts->qwant_language) && strlen($this->opts->qwant_language) == 5) ? strtolower($this->opts->qwant_language) : 'en_gb';

		// Based on https://github.com/locness3/qwant-api-docs and variables from qwant website
        $url = 'https://api.qwant.com/v3/search/news?'.http_build_query(array(
        	'q' => $this->search->query, // Search query
        	't' => 'news', // News search
        	'safesearch' => $this->search->safe, // Safe search filter (0 = off, 1 = normal, 2 = strict)
        	'locale' => $language, // Language region
        	'count' => 30, // How many results? (Maximum 50)
        	'device' => 'desktop', // Where are you searching from
        	'source' => 'all', // Where to get the news from (All)
        	'freshness' => 'month', // How old may the article be?
        	'order' => 'date' // Sort by date
        ));

        unset($language);

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
		if(empty($json_response)) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No response', 0);
			return $engine_result;
		}

		// Figure out results and base rank
		$number_of_results = $rank = $json_response['data']['result']['total'];

		// No results
        if($number_of_results == 0 || $json_response['status'] == 'error') {
			if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No results', 0);	        
	        return $engine_result;
	    }

		foreach($json_response['data']['result']['items'] as $result) {
			// Find and process data
			$title = sanitize($result['title']);
			$url = strip_newlines(sanitize($result['url']));
			$description = limit_string_length(strip_newlines(sanitize($result['desc'])));
			$image = sanitize($result['media'][0]['pict']['url']);
			$source = str_replace('www.', '', strtolower(parse_url($url, PHP_URL_HOST)));
			$timestamp = sanitize($result['date']);

			// Fix up the image
			if($image == 'NULL') $image = null;

			$engine_temp[] = array(
				'title' => $title, // string
				'url' => $url, // string
				'description' => $description, // string
				'image' => $image, // string|null
				'source' => $source, // string
				'timestamp' => $timestamp, // int|null
				'engine_rank' => $rank // int
			);
			$rank -= 1;
		}

		// Base info
		if(!empty($engine_temp)) {
			$engine_result['source'] = 'Qwant News';
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, $number_of_results, count($engine_temp));

		unset($response, $json_response, $number_of_results, $rank, $engine_temp);

		return $engine_result;
	}
}
?>