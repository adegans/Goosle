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
class GoogleRequest extends EngineRequest {
    public function get_request_url() {
        $url = 'https://www.google.com/search?'.http_build_query(array(
        	'q' => $this->search->query, // Search query
        	'oq' => $this->search->query, // (Original) Search query
        	'safe' => $this->search->safe, // Safe search (0 = off, 1 = moderate, 2 = on/strict)
        	'gl' => $this->opts->google_search_region, // Primarily search in this region
        	'num' => 50, // Number of results per page
        	'pws' => 0, // Personalized search (0 = off)
        	'udm' => 14, // A view for simpler/non-ai results
			'complete' => '0', // Instant search (0 = off)
        	'source' => 'web', // Where are you searching from
        	'sclient' => 'gws-wiz' // Search client (Google currently seems to prefer 'gws-wiz' or 'gws-wiz-serp', previously 'web')
        ));

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
		$scrape = $xpath->query("//div[@id='search']//div[@class='MjjYud']");

		// Figure out results and base rank
		$number_of_results = $rank = count($scrape);

		// No results
        if($number_of_results == 0) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, 'No results', 0);
	        return $engine_result;
	    }

		// Scrape recommended
        $didyoumean = $xpath->query("//a[@class='gL9Hy']")[0];
        if(!is_null($didyoumean)) {
			$engine_result['did_you_mean'] = strip_tags($didyoumean->textContent);
        }

        foreach($scrape as $result) {
			// Find data
			$url = $xpath->evaluate(".//div[@class='yuRUbf']//a/@href", $result);
			$title = $xpath->evaluate(".//h3", $result);
			$description = $xpath->evaluate(".//div[contains(@class, 'VwiC3b')]", $result);

			// Skip broken results
			if($url->length == 0) continue;
			if($title->length == 0) continue;

			// Process data
			$url = sanitize($url[0]->textContent);
			$title = strip_newlines(sanitize($title[0]->textContent));
			$description = ($description->length == 0) ? "No description was provided for this site." : limit_string_length(strip_newlines(sanitize($description[0]->textContent)));

			// filter duplicate urls/results
            if(!empty($engine_temp)) {
                if(in_array($url, array_column($engine_temp, "url"))) continue;
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
			$engine_result['source'] = 'Google';
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, $number_of_results, count($engine_temp));

		unset($response, $xpath, $scrape, $number_of_results, $rank, $engine_temp);

        return $engine_result;
    }
}
?>
