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
		$url = 'https://search.brave.com/news?'.http_build_query(array(
        	'q' => $this->search->query, // Search query
        	'offset' => 0, // Start on 'page' 1 of results (0 = 1)
        	'spellcheck' => 0, // No spellcheck on your query
        	'source' => 'web', // Where are you searching from? (Web)
        	'tf' => 'pm' // How old may the article be?
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
			$image = $xpath->evaluate(".//div[contains(@class, 'image-wrapper')]/img/@src", $result);
			$date_added = $xpath->evaluate(".//cite[contains(@class, 'snippet-url')]/span[2]", $result);

			// Skip broken results
			if($title->length == 0) continue;
			if($url->length == 0) continue;

			// Process data
			$title = strip_newlines(sanitize($title[0]->textContent));
			$url = sanitize($url[0]->textContent);
//			$url = (strpos($url, "/a/redirect?click_url=", 0) !== false) ? "https://search.brave.com".$url : $url;
			$description = ($description->length == 0) ? "No description was provided for this site." : limit_string_length(strip_newlines(sanitize($description[0]->textContent)));
			$image = ($image->length == 0) ? null : sanitize($image[0]->textContent);
			$source = str_replace('www.', '', strtolower(parse_url($url, PHP_URL_HOST)));
			$timestamp = ($date_added->length == 0) ? null : strtotime(sanitize($date_added[0]->textContent));

			// filter duplicate urls/results
            if(!empty($engine_temp)) {
                if(in_array($url, array_column($engine_temp, 'url'))) continue;
            }

			$engine_temp[] = array(
				'title' => $title, // string
				'url' => $url, // string
				'description' => $description, // string
				'image' => $image, // string|null
				'source' => $source, // string
				'timestamp' => $timestamp, // int|null
				'engine_rank' => $rank // int
			);
			$rank -= 1;
		}

		// Base info
		if(!empty($engine_temp)) {
			$engine_result['source'] = 'Brave News';
			$engine_result['search'] = $engine_temp;
		}

		unset($response, $xpath, $scrape, $number_of_results, $rank, $engine_temp);

		return $engine_result;
    }
}
?>
