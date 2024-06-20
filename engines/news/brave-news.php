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
class BraveNewsRequest extends EngineRequest {
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
			$age = 'pd';
		} else if($query_terms[0] == 'week' || ($query_terms[0] == 'this' && $query_terms[1] == 'week') || $query_terms[0] == 'recent') {
			// Last 7 days
			$this->opts->result_range = $today - (6 * 86400);
			$age = 'pw';
		} else if($query_terms[0] == 'year' || ($query_terms[0] == 'this' && $query_terms[1] == 'year')) {
			// This year
			$this->opts->result_range = timezone_offset(gmmktime(0, 0, 0, 1, 1, gmdate('Y')), $this->opts->timezone);
			$age = 'py';
		} else {
			// This month (default)
			$this->opts->result_range = timezone_offset(gmmktime(0, 0, 0, gmdate('m'), 1, gmdate('Y')), $this->opts->timezone);
			$age = 'pm';
		}

		// Is there no query left? Bail!
		if(empty($query)) return false;

		$url = 'https://search.brave.com/news?'.http_build_query(array(
        	'q' => $query, // Search query
        	'offset' => 0, // Start on 'page' 1 of results (0 = 1)
        	'spellcheck' => 0, // No spellcheck on your query
        	'source' => 'web', // Where are you searching from? (Web)
        	'tf' => $age // How old may the article be?
        ));
        
        unset($query, $query_terms, $today, $age);

        return $url;
    }

    public function get_request_headers() {
		return array(
			'Accept' => 'text/html, application/xhtml+xml, application/xml;q=0.8, */*;q=0.7',
		);
	}

    public function parse_results($response) {
		$engine_temp = $engine_result = array();
		$xpath = get_xpath($response);

		// No response
		if(!$xpath) return $engine_temp;

		// Scrape the results (Max 30)
		$scrape = $xpath->query("//main[contains(@class, 'main-column')]//div[contains(@class, 'snippet')][position() < 30]");

		// Figure out results and base rank
		$number_of_results = $rank = count($scrape);

		// No results
        if($number_of_results == 0) return $engine_temp;

		foreach($scrape as $result) {
			// Find data
			$title = $xpath->evaluate(".//a[contains(@class, 'result-header')]//span[contains(@class, 'snippet-title')]", $result);
			$url = $xpath->evaluate(".//a[contains(@class, 'result-header')]/@href", $result);
			$description = $xpath->evaluate(".//p[contains(@class, 'snippet-description')]", $result);
			$date_added = $xpath->evaluate(".//cite[contains(@class, 'snippet-url')]/span[2]", $result);

			// Skip broken results
			if($title->length == 0) continue;
			if($url->length == 0) continue;

			// Process data
			$title = strip_newlines(sanitize($title[0]->textContent));
			$url = sanitize($url[0]->textContent);
//			$url = (strpos($url, "/a/redirect?click_url=", 0) !== false) ? "https://search.brave.com".$url : $url;
			$description = ($description->length == 0) ? "No description was provided for this site." : limit_string_length(strip_newlines(sanitize($description[0]->textContent)));
			$source = str_replace('www.', '', strtolower(parse_url($url, PHP_URL_HOST)));
			$date_added = ($date_added->length == 0) ? null : timezone_offset(strtotime(sanitize($date_added[0]->textContent)), $this->opts->timezone);

			// filter duplicate urls/results
            if(!empty($engine_temp)) {
                if(in_array($url, array_column($engine_temp, 'url'))) continue;
            }

			// Ignore results that are too old
			if(isset($this->opts->result_range) && !is_null($date_added)) {
				if($date_added < $this->opts->result_range) continue;
			}

			$engine_temp[] = array(
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
			$engine_result['source'] = 'Brave News';
			$engine_result['amount'] = $number_of_results;
			$engine_result['search'] = $engine_temp;
		}

		unset($response, $xpath, $scrape, $number_of_results, $rank, $engine_temp);

		return $engine_result;
    }
}
?>
