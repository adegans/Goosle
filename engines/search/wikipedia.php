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
class WikiRequest extends EngineRequest {
    public function get_request_url() {
		// Variables based on https://www.mediawiki.org/wiki/API:Search
        $url = 'https://'.strtolower($this->opts->wikipedia_language).'.wikipedia.org/w/api.php?'.http_build_query(array(
        	'srsearch' => $this->search->query, // Search query
        	'action' => 'query', // Search type (via a query?)
        	'list' => 'search', // Full text search
        	'format' => 'json', // Return format (Must be json)
        	'srlimit' => 5 // How many search results to get, ideally as few as possible since it's just static wiki pages (max 500)
        ));

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
		$number_of_results = $rank = ($json_response['query']['searchinfo']['totalhits'] > 20) ? 20 : $json_response['query']['searchinfo']['totalhits'];

		// No results
        if($number_of_results == 0) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No results', 0);	        
	        return $engine_result;
	    }
		foreach($json_response['query']['search'] as $result) {
			// Find and process data
			$title = strip_newlines(sanitize($result['title']));
			$url = 'https://'.$this->opts->wikipedia_language.'.wikipedia.org/wiki/'.sanitize(str_replace(' ', '_', $result['title']));
			$description = html_entity_decode(limit_string_length(strip_newlines(sanitize($result['snippet']))));
		
			$engine_temp[] = array (
				'title' => $title, 
				'url' => $url, 
				'description' => $description, 
				'engine_rank' => $rank
			);
			$rank -= 1;
		}

		// Base info
		if(!empty($engine_temp)) {
			$engine_result['source'] = 'Wikipedia';
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, $number_of_results, count($engine_temp));

		unset($response, $json_response, $number_of_results, $rank, $engine_temp);
		
		return $engine_result;
	}
}
?>
