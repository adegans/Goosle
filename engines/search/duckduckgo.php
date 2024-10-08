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
class DuckDuckGoRequest extends EngineRequest {
    public function get_request_url() {
		// Safe search override
		if($this->search->safe == 0) {
			$safe = '-2';
		} else if($this->search->safe == 2) {
			$safe = '1';
		} else {
			$safe = '-1';
		}

		// All parameters and values: https://duckduckgo.com/duckduckgo-help-pages/settings/params/
        $url = 'https://html.duckduckgo.com/html/?'.http_build_query(array(
        	'q' => $this->search->query, // Search query
        	'kp' => $safe, // Safe search (1 = on, -1 = moderate, -2 = off
        	'kl' => strtolower($this->opts->duckduckgo_language), // Language region
        	'kz' => '-1', // Instant answers (1 = on, -1 = off)
        	'kc' => '-1', // Autoload images (1 = on, -1 = off)
        	'kav' => '-1', // Autoload results (1 = on, -1 = off)
        	'kaf' => '1', // Full URLs (1 = on, -1 = off)
        	'kac' => '-1', // Auto suggest (1 = on, -1 = off)
        	'kd' => '-1', // Redirects (1 = on, -1 = off)
        	'kh' => '1', // HTTPS (1 = on, -1 = off)
        	'kg' => 'g', // Get/Post (g = GET, p = POST)
			'k1' => '-1' // Ads (1 = on, -1 = off)
        ));

        unset($safe);

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
		if(!$xpath) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, 'No response', 0);
			return $engine_result;
		}

		// Scrape the results
		$scrape = $xpath->query("//div[contains(@class, 'result__body')]");

		// Figure out results and base rank
		$number_of_results = $rank = count($scrape);

		// No results
        if($number_of_results == 0) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, 'No results', 0);
	        return $engine_result;
	    }

		// Scrape recommended
		$didyoumean = $xpath->query('//div[contains(@class, "msg--spelling")]/div/a[1]')[0];
		if(!is_null($didyoumean)) {
			$engine_result['did_you_mean'] = strip_tags($didyoumean->textContent);
		}

		foreach($scrape as $result) {
			// Find data
			$url = $xpath->evaluate(".//h2[@class='result__title']//a/@href", $result);
			$title = $xpath->evaluate(".//h2[@class='result__title']", $result);
			$description = $xpath->evaluate(".//a[@class='result__snippet']", $result);

			// Skip broken results
			if($url->length == 0) continue;
			if($title->length == 0) continue;

			// Process data
			$url = sanitize($url[0]->textContent);
			$title = strip_newlines(sanitize($title[0]->textContent));
			$description = ($description->length == 0) ? "No description was provided for this site." : limit_string_length(strip_newlines(sanitize($description[0]->textContent)));

			// filter duplicate urls/results
            if(!empty($engine_temp)) {
                if(in_array($url, array_column($engine_temp, 'url'))) continue;
            }

			$engine_temp[] = array(
				'title' => $title,
				'url' => $url,
				'description' => $description,
				'engine_rank' => $rank
			);
			$rank -= 1;
		}

		// Base info
		if(!empty($engine_temp)) {
			$engine_result['source'] = 'DuckDuckGo';
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, $number_of_results, count($engine_temp));

		unset($response, $xpath, $scrape, $number_of_results, $rank, $engine_temp);

		return $engine_result;
    }
}
?>
