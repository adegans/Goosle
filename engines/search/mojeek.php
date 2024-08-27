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
class MojeekRequest extends EngineRequest {
    public function get_request_url() {
		// Safe search override
		if($this->search->safe == 0) {
			$safe = '';
		} else if($this->search->safe == 2) {
			$safe = '1';
		} else {
			$safe = '';
		}

		// All parameters and values: https://www.mojeek.com/preferences
        $url = 'https://www.mojeek.com/search?'.http_build_query(array(
        	'q' => $this->search->query, // Search query
        	'safe' => $safe, // Safe search (1 = on, 0 = off
        	'lb' => strtolower($this->opts->mojeek_language), // Results language
        	'arc' => 'none', // Region search bias
        	't' => '40', // How many results
        	'tn' => '0', // No news results (Goes in a separate column)
        	'si' => '4', // Max same site results
        	'tlen' => '100', // Title length
        	'dlen' => '300', // Description length
        	'ib' => '0', // No result info boxes
        	'sumt' => '0', // Hide summary tab
        	'sumb' => '0' // Hide summary button
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
		$scrape = $xpath->query("//ul[contains(@class, 'results-standard')]/li");

		// Figure out results and base rank
		$number_of_results = $rank = count($scrape);

		// No results
        if($number_of_results == 0) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, 'No results', 0);
	        return $engine_result;
	    }

		// Scrape recommended
		$didyoumean = $xpath->query("//p[contains(@class, 'spell')]//a")[0];
		if(!is_null($didyoumean)) {
			$engine_result['did_you_mean'] = strip_tags($didyoumean->textContent);
		}

		foreach($scrape as $result) {
			// Find data
			$url = $xpath->evaluate(".//h2/a/@href", $result);
			$title = $xpath->evaluate(".//h2", $result);
			$description = $xpath->evaluate(".//p[@class='s']", $result);

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
			$engine_result['source'] = 'Mojeek';
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, $number_of_results, count($engine_temp));

		unset($response, $xpath, $scrape, $number_of_results, $rank, $engine_temp);

		return $engine_result;
    }
}
?>
