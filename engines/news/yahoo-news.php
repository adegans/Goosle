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
class YahooNewsRequest extends EngineRequest {
    public function get_request_url() {
		$query = str_replace('%22', '\"', $this->query);
 
		// Safe search override
		$safe = ''; // No mature results
		if(preg_match('/(safe:)(on|off)/i', $query, $matches)) {
			if($matches[2] == 'on') $safe = '';
			if($matches[2] == 'off') $safe = '0';
			$query = str_replace($matches[0], '', $query);
		}
		unset($matches);

	    $query_terms = explode(' ', $query);
		$query_terms[0] = strtolower($query_terms[0]);
	
		// Search range
		$today = time() - 86400;
		if($query_terms[0] == 'now' || $query_terms[0] == 'today' || $query_terms[0] == 'yesterday') {
			// Last 24 hours 
			$this->opts->result_range = $today;
		} else if($query_terms[0] == 'week' || ($query_terms[0] == 'this' && $query_terms[1] == 'week') || $query_terms[0] == 'recent') {
			// Last 7 days
			$this->opts->result_range = $today - (6 * 86400);
		} else if($query_terms[0] == 'year' || ($query_terms[0] == 'this' && $query_terms[1] == 'year')) {
			// This year
			$this->opts->result_range = timezone_offset(gmmktime(0, 0, 0, 1, 1, gmdate('Y')), $this->opts->timezone);
		} else {
			// This month
			$this->opts->result_range = timezone_offset(gmmktime(0, 0, 0, gmdate('m'), 1, gmdate('Y')), $this->opts->timezone);
		}

		// Is there no query left? Bail!
		if(empty($query)) return false;

		$url = 'https://news.search.yahoo.com/search?'.http_build_query(array(
        	'p' => $query, // Search query
        	'safe' => $safe // Safe search filter (0 = off, "" = on)
        ));
        
        unset($query, $query_terms, $safe, $today);

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
		$scrape = $xpath->query("//div[@id='web']/ol/li[position() < 30]");

		// Figure out results and base rank
		$number_of_results = $rank = count($scrape);

		// No results
        if($number_of_results == 0) return $engine_temp;

		foreach($scrape as $result) {
			// Find data
			$title = $xpath->evaluate("./div/ul/li/a[contains(@class, 'thmb')]/@title", $result);
			$url = $xpath->evaluate("./div/ul/li/h4[contains(@class, 's-title')]/a/@href", $result);
			$description = $xpath->evaluate("./div/ul/li/p[contains(@class, 's-desc')]", $result);
			$date_added = $xpath->evaluate("./div/ul/li/span[contains(@class, 's-time')]", $result);

			// Skip broken results
			if($title->length == 0) continue;
			if($url->length == 0) continue;

			// Process data
			$title = strip_newlines(sanitize($title[0]->textContent));
			$url = (preg_match('/\/ru=(.+)(%3ffr|\/rk)/i', $url[0]->textContent, $found_url)) ? urldecode($found_url[1]) : $url[0]->textContent;
			$url = (preg_match('/\??&?(utm_).+?(&|$)$/i', $url, $found_url)) ? urldecode($found_url[1]) : $url;
			$url = sanitize(str_replace('?fr=sycsrp_catchall', '', $url));
			$description = ($description->length == 0) ? "No description was provided for this site." : limit_string_length(strip_newlines(sanitize($description[0]->textContent)));
			$source = str_replace('www.', '', strtolower(parse_url($url, PHP_URL_HOST)));
			$date_added = ($date_added->length == 0) ? null : timezone_offset(strtotime(sanitize(preg_replace('/[^a-z0-9 ]+/i', '', $date_added[0]->textContent))), $this->opts->timezone);

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
			$engine_result['source'] = 'Yahoo News';
			$engine_result['amount'] = $number_of_results;
			$engine_result['search'] = $engine_temp;
		}

		unset($response, $xpath, $scrape, $number_of_results, $rank, $engine_temp);

		return $engine_result;
    }
}
?>
