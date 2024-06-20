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
		$query = str_replace('%22', '\"', $this->query);

		// Safe search override
		$safe = '1'; // Moderate results
		if(preg_match('/(safe:)(on|off)/i', $query, $matches)) {
			if($matches[2] == 'on') $safe = '2';
			if($matches[2] == 'off') $safe = '0';
			$query = trim(str_replace($matches[0], '', $query));
		}
		unset($matches);

		// Set locale
		$language = (preg_match('/[a-z]{2}_[a-z]{2}/i', $this->opts->qwant_language) && strlen($this->opts->qwant_language) == 5) ? strtolower($this->opts->qwant_language) : 'en_gb';

	    $query_terms = explode(' ', $query);
		$query_terms[0] = strtolower($query_terms[0]);
	
		// Search range
		$today = time() - 86400;
		if($query_terms[0] == 'now' || $query_terms[0] == 'today' || $query_terms[0] == 'yesterday') {
			// Last 24 hours 
			$this->opts->result_range = $today;
			$age = 'day';
		} else if($query_terms[0] == 'week' || ($query_terms[0] == 'this' && $query_terms[1] == 'week') || $query_terms[0] == 'recent') {
			// Last 7 days
			$this->opts->result_range = $today - (6 * 86400);
			$age = 'week';
		} else if($query_terms[0] == 'year' || ($query_terms[0] == 'this' && $query_terms[1] == 'year')) {
			// This year
			$this->opts->result_range = timezone_offset(gmmktime(0, 0, 0, 1, 1, gmdate('Y')), $this->opts->timezone);
			$age = 'all';
		} else {
			// This month
			$this->opts->result_range = timezone_offset(gmmktime(0, 0, 0, gmdate('m'), 1, gmdate('Y')), $this->opts->timezone);
			$age = 'month';
		}

		// Is there no query left? Bail!
		if(empty($query)) return false;

		// Based on https://github.com/locness3/qwant-api-docs and variables from qwant website
        $url = 'https://api.qwant.com/v3/search/news?'.http_build_query(array(
        	'q' => $query, // Search query
        	't' => 'news', // News search
        	'safesearch' => $safe, // Safe search filter (0 = off, 1 = normal, 2 = strict)
        	'locale' => $language, // Language region
        	'count' => 30, // How many results? (Maximum 50)
        	'device' => 'desktop', // Where are you searching from
        	'source' => 'all', // Where to get the news from (All)
        	'freshness' => $age, // How old may the article be?
        	'order' => 'date' // Sort by date
        ));

        unset($query, $query_terms, $safe, $language, $age);

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

		foreach($json_response['data']['result']['items'] as $result) {
			// Find and process data
			$title = sanitize($result['title']);
			$url = strip_newlines(sanitize($result['url']));
			$description = limit_string_length(strip_newlines(sanitize($result['desc'])));
			$source = str_replace('www.', '', strtolower(parse_url($url, PHP_URL_HOST)));
			$date_added = timezone_offset(sanitize($result['date']), $this->opts->timezone);

			// Ignore results that are too old
			if(isset($this->opts->result_range)) {
				if($date_added < $this->opts->result_range) continue;
			}

			$engine_temp[] = array(
				'title' => $title, // string
				'url' => $url, // sting
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
			$engine_result['source'] = 'Qwant News';
			$engine_result['amount'] = $number_of_results;
			$engine_result['search'] = $engine_temp;
		}

		unset($response, $json_response, $number_of_results, $rank, $engine_temp);

		return $engine_result;
	}
}
?>