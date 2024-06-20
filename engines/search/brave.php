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
class BraveRequest extends EngineRequest {
    public function get_request_url() {
		$query = str_replace('%22', '\"', $this->query);
 
		// Safe search ignore (not supported)
		if(preg_match('/(safe:)(on|off)/i', $query, $matches)) {
			$query = trim(str_replace($matches[0], '', $query));
		}
		unset($matches);

		// Is there no query left? Bail!
		if(empty($query)) return false;

		$url = 'https://search.brave.com/search?'.http_build_query(array(
        	'q' => $query, // Search query
        	'offset' => 0, // Start on 'page' 1 of results (0 = 1)
        	'show_local' => 0, // Localize results (0 = no localization)
        	'spellcheck' => 0, // No spellcheck on your query
        	'source' => 'web' // Where are you searching from? (Web)
        ));
        
        unset($query);

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
		$scrape = $xpath->query("//div[@id='results']//div[contains(@class, 'snippet')]");

		// Figure out results and base rank
		$number_of_results = $rank = count($scrape);

		// No results
        if($number_of_results == 0) return $engine_temp;

		foreach($scrape as $result) {
			// Find data
			$url = $xpath->evaluate(".//a[contains(@class, 'h')]//@href", $result);
			$title = $xpath->evaluate(".//a[contains(@class, 'h')]//div[contains(@class, 'title')]", $result);
			$description = $xpath->evaluate(".//div[contains(@class, 'snippet-content')]//div[contains(@class, 'snippet-description')]", $result);

			// Skip broken results
			if($url->length == 0) continue;
			if($title->length == 0) continue;

			// Process data
			$url = sanitize($url[0]->textContent);
			$url = (strpos($url, '/a/redirect?click_url=', 0) !== false) ? "https://search.brave.com".$url : $url;
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
		$number_of_results = count($engine_temp);
		if($number_of_results > 0) {
			$engine_result['source'] = 'Brave';
			$engine_result['amount'] = $number_of_results;
			$engine_result['search'] = $engine_temp;
		}

		unset($response, $xpath, $scrape, $number_of_results, $rank, $engine_temp);

		return $engine_result;
    }
}
?>
