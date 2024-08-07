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
class HackernewsRequest extends EngineRequest {
    public function get_request_url() {
		// Search range (must be in GMT)
		$article_date = time() - (30 * 86400);

		// More info on https://hn.algolia.com/api
        $url = 'https://hn.algolia.com/api/v1/search_by_date?'.http_build_query(array(
			'query' => $this->search->query, // Search query
			'tags' => 'story', // What type of results to show? (story = News stories)
			'hitsPerPage' => 50, // How many results to return?
			'numericFilters' => 'created_at_i>'.$article_date // How old may the article be?
        ));

        unset($article_date);

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
		$number_of_results = $rank = count($json_response['hits']);

		// No results
        if($number_of_results == 0) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No results', 0);	        
	        return $engine_result;
	    }

		foreach($json_response['hits'] as $result) {
			// Skip broken/wrong results
			if(!empty(array_intersect(array('ask_hn', 'show_hn'), $result['_tags']))) continue;
			if(!array_key_exists('url', $result)) continue;
			
			// Find and process data
			$title = strip_newlines(sanitize($result['title']));
			$url = sanitize($result['url']);
			$description = (array_key_exists('story_text', $result)) ? limit_string_length(strip_newlines(sanitize($result['story_text']))) : $title;
			$source = str_replace('www.', '', strtolower(parse_url($url, PHP_URL_HOST)));
			$timestamp = sanitize($result['created_at_i']);

			// Skip duplicate urls/results
            if(!empty($engine_temp)) {
                if(in_array($url, array_column($engine_temp, 'url'))) continue;
            }

			$engine_temp[] = array (
				'title' => $title, // string
				'url' => $url, // string
				'description' => $description, // string
				'image' => null, // string|null
				'source' => $source, // string
				'timestamp' => $timestamp, // int|null
				'engine_rank' => $rank // int
			);
			$rank -= 1;
		}
		
		// Base info
		if(!empty($engine_temp)) {
			$engine_result['source'] = 'Hackernews';
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, $number_of_results, count($engine_temp));

		unset($response, $json_response, $number_of_results, $rank, $engine_temp);

		return $engine_result;
	}
}
?>
