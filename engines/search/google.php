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
		$query = str_replace('%22', '\"', $this->query);

		// Safe search override
		$safe = '1';
		if(preg_match('/(safe:)(on|off)/i', $query, $matches)) {
			if($matches[2] == 'on') $safe = '2';
			if($matches[2] == 'off') $safe = '0';
			$query = trim(str_replace($matches[0], '', $query));
		}
		unset($matches);

		// Is there no query left? Bail!
		if(empty($query)) return false;

		// Including the preferred language variable breaks the page result, and with that the crawler!
        $url = 'https://www.google.com/search?'.http_build_query(array(
        	'q' => $query, // Search query
        	'safe' => $safe, // Safe search (0 = off, 1 = moderate, 2 = on/strict)
        	'num' => 30, // Number of results per page
        	'pws' => 0, // Personalized search results (0 = off)
        	'udm' => 14, // A view for simpler/non-ai results
        	'tbs' => 'li:1', // 'verbatim' search, adding this enables it
        	'complete' => '0', // Instant results related (0 = off)
        	'sclient' => 'web' // Where are you searching from
        ));

		unset($query, $safe);

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

		// Scrape the results
		$scrape = $xpath->query("//div[@id='search']//div[@class='MjjYud']");

		// Figure out results and base rank
		$number_of_results = $rank = count($scrape);

		// No results
        if($number_of_results == 0) return $engine_temp;

		// Scrape recommended
        $didyoumean = $xpath->query(".//a[@class='gL9Hy']")[0];
        if(!is_null($didyoumean)) {
			$engine_result['did_you_mean'] = $didyoumean->textContent;
        }
        $search_specific = $xpath->query(".//a[@class='spell_orig']")[0];
        if(!is_null($search_specific)) {
	        // Google doesn't add quotes by itself
			$engine_result['search_specific'] = "\"".$search_specific->textContent."\"";
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
		$number_of_results = count($engine_temp);
		if($number_of_results > 0) {
			$engine_result['source'] = 'Google';
			$engine_result['amount'] = $number_of_results;
			$engine_result['search'] = $engine_temp;
		}

		unset($response, $xpath, $scrape, $number_of_results, $rank, $engine_temp);

        return $engine_result;
    }
}
?>
