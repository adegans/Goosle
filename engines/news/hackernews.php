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
		$query = str_replace('%22', '\"', $this->query);

	    $query_terms = explode(' ', $query);
		$query_terms[0] = strtolower($query_terms[0]);
	
		// Safe search ignore (not supported)
		if($query_terms[0] == 'safe:on' || $query_terms[0] == 'safe:off') {
			$query = trim(str_replace($query_terms[0], '', $query));
		}

		// Search range
		$today = time() - 86400;
		if($query_terms[0] == 'now' || $query_terms[0] == 'today' || $query_terms[0] == 'yesterday') {
			// Last 24 hours 
			$this->opts->result_range = $today;
			$age = 'created_at_i>'.$this->opts->result_range.',created_at_i<'.$today; // Yesterday
		} else if($query_terms[0] == 'week' || ($query_terms[0] == 'this' && $query_terms[1] == 'week') || $query_terms[0] == 'recent') {
			// Last 7 days
			$this->opts->result_range = $today - (6 * 86400);
			$age = 'created_at_i>'.$this->opts->result_range; // This week
		} else if($query_terms[0] == 'year' || ($query_terms[0] == 'this' && $query_terms[1] == 'year')) {
			// This year
			$this->opts->result_range = timezone_offset(gmmktime(0, 0, 0, 1, 1, gmdate('Y')), $this->opts->timezone);
			$age = 'created_at_i>'.$this->opts->result_range;
		} else {
			// This month (default)
			$this->opts->result_range = timezone_offset(gmmktime(0, 0, 0, gmdate('m'), 1, gmdate('Y')), $this->opts->timezone);
			$age = 'created_at_i>'.$this->opts->result_range; 
		}

		// Is there no query left? Bail!
		if(empty($query)) return false;

		// More info on https://hn.algolia.com/api
        $url = 'http://hn.algolia.com/api/v1/search_by_date?'.http_build_query(array(
			'query' => $query, // Search query
			'tags' => 'story', // What type of results to show? (story = News stories)
			'hitsPerPage' => 30, // How many results to return?
			'restrictSearchableAttributes' => 'title,url', // Only match on URLs
			'attributesToRetrieve' => '_tags,title,url,author,created_at_i', // Data to retrieve
			'numericFilters' => $age // How old may the article be?
        ));

        unset($query, $query_terms, $today, $age);

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
		$number_of_results = $rank = count($json_response['hits']);

		// No results
        if($number_of_results == 0) return $engine_temp;

		foreach($json_response['hits'] as $result) {
			// Skip broken/wrong results
			if(!empty(array_intersect(array('ask_hn', 'show_hn'), $result['_tags']))) continue;
			if(!array_key_exists('url', $result)) continue;
			
			// Find and process data
			$title = strip_newlines(sanitize($result['title']));
			$url = sanitize($result['url']);
			$description = (array_key_exists('story_text', $result)) ? limit_string_length(strip_newlines(sanitize($result['story_text']))) : $title;
			$source = str_replace('www.', '', strtolower(parse_url($url, PHP_URL_HOST)));
			$date_added = timezone_offset(sanitize($result['created_at_i']), $this->opts->timezone);

			// Skip duplicate urls/results
            if(!empty($engine_temp)) {
                if(in_array($url, array_column($engine_temp, 'url'))) continue;
            }

			// Ignore results that are too old
			if(isset($this->opts->result_range)) {
				if($date_added < $this->opts->result_range) continue;
			}

			$engine_temp[] = array (
				'title' => $title, // string
				'url' => $url, // string
				'description' => $description, // string
				'source' => $source, // string
				'date_added' => $date_added, // int (timestamp)
				'engine_rank' => $rank // int
			);
			$rank -= 1;
		}
		
		// Base info
		$number_of_results = count($engine_temp);
		if($number_of_results > 0) {
			$engine_result['source'] = 'Hackernews';
			$engine_result['amount'] = $number_of_results;
			$engine_result['search'] = $engine_temp;
		}

		unset($response, $json_response, $number_of_results, $rank, $engine_temp);

		return $engine_result;
	}
}
?>
