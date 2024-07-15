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
class QwantRequest extends EngineRequest {
    public function get_request_url() {
		// Set locale
		$language = (preg_match('/[a-z]{2}_[a-z]{2}/i', $this->opts->qwant_language) && strlen($this->opts->qwant_language) == 5) ? strtolower($this->opts->qwant_language) : 'en_gb';

		// Based on https://github.com/locness3/qwant-api-docs and variables from qwant website
        $url = 'https://api.qwant.com/v3/search/web?'.http_build_query(array(
        	'q' => $this->search->query, // Search query
        	't' => 'web', // Type of search, web search
        	'safesearch' => $this->search->safe, // Safe search filter (0 = off, 1 = normal, 2 = strict)
        	'locale' => $language, // In which language should the search be done
        	'count' => 10, // How many results? (Maximum 10)
        	'device' => 'desktop' // What kind of device are we searching from?
        ));

        unset($query, $language);

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
        if($number_of_results == 0 || $json_response['status'] == 'error') return $engine_temp;

		$rank = $json_response['data']['result']['total'];

		foreach($json_response['data']['result']['items']['mainline'] as $mainline) {
			if($mainline['type'] != 'web') continue;

			foreach ($mainline['items'] as $result) {
				// Find and process data
				$title = strip_newlines(sanitize($result['title']));
				$url = sanitize($result['url']);
				$description = limit_string_length(strip_newlines(sanitize($result['desc'])));
	
				$engine_temp[] = array (
					'title' => $title, 
					'url' => $url, 
					'description' => $description, 
					'engine_rank' => $rank
				);
				$rank -= 1;
			}
		}

		// Base info
		if(!empty($engine_temp)) {
			$engine_result['source'] = 'Qwant';
			$engine_result['search'] = $engine_temp;
		}

		unset($response, $json_response, $number_of_results, $rank, $engine_temp);
		
		return $engine_result;
	}
}
?>
