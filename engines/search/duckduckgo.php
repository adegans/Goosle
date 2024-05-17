<?php
/* ------------------------------------------------------------------------------------
*  Goosle - A meta search engine for private and fast internet fun.
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
		// Split the query
	    $query_terms = explode(" ", strtolower($this->query), 2);
	    $query_terms[0] = strtolower($query_terms[0]);

		// Safe search override
		$safe = "-1";
		if(strpos($query_terms[0], "safe") !== false) {
			$switch = explode(":", $query_terms[0]);

			if(!is_numeric($switch[1])) {
				$safe = (strtolower($switch[1]) == "off") ? "-2" : "1";
				$this->query = implode(" ", array_slice($query_terms, 1));
			}
		}

		// All parameters and values: https://duckduckgo.com/duckduckgo-help-pages/settings/params/
		// q = query
		// kp = Safe search  (1 = on, -1 = moderate, -2 = off (may include nsfw/illegal content))
		// kl = Search results language (Works as a region setting, see params page for more supported regions: en-us, en-uk, nl-nl, es-es, fr-fr, etc.)
		// kz = Instant answers (1 = on, -1 = off)
		// kc = Autoload images (1 = on, -1 = off)
		// kav = Autoload results (1 = on, -1 = off)
		// kf = Favicons (1 = on, -1 = off)
		// kaf = Full URLs (1 = on, -1 = off)
		// kac = Auto suggest (1 = on, -1 = off)
		// kd = Redirects (1 = on, -1 = off)
		// kh = HTTPS (1 = on, -1 = off)
		// kg = Get/Post (g = GET, p = POST)
		// k1 = Ads (1 = on, -1 = off)

		$args = array("q" => $this->query, "kl" => $this->opts->duckduckgo_language, "kp" => $safe, "kz" => "-1", "kc" => "-1", "kav" => "-1", "kf" => "-1", "kaf" => "1", "kac" => "-1", "kd" => "-1", "kh" => "1", "kg" => "g", "k1" => "-1");
        $url = "https://html.duckduckgo.com/html/?".http_build_query($args);

        unset($query_terms, $safe, $switch, $args);

        return $url;
    }

    public function get_request_headers() {
		return array(
			'Accept' => 'text/html, application/xhtml+xml, application/xml;q=0.8, */*;q=0.7',
		);
	}

    public function parse_results($response) {
		$results = array();
		$xpath = get_xpath($response);

		if(!$xpath) return $results;
 
		// Scrape recommended
		$didyoumean = $xpath->query(".//div[@id='did_you_mean']/a[1]")[0];
		if(!is_null($didyoumean)) {
			$results['did_you_mean'] = $didyoumean->textContent;
		}
        $search_specific = $xpath->query(".//div[@id='did_you_mean']/a[2]")[0];
        if(!is_null($search_specific)) {
			$results['search_specific'] = $search_specific->textContent;
        }
 
		// Scrape the results
		$scrape = $xpath->query("/html/body/div[1]/div[".count($xpath->query('/html/body/div[1]/div'))."]/div/div/div[contains(@class, 'web-result')]/div");
		$rank = $results['amount'] = count($scrape);
		foreach($scrape as $result) {
			$url = $xpath->evaluate(".//h2[@class='result__title']//a/@href", $result)[0];
			if(is_null($url)) continue;

			$title = $xpath->evaluate(".//h2[@class='result__title']", $result)[0];
			if(is_null($title)) continue;
			
			$description = $xpath->evaluate(".//a[@class='result__snippet']", $result)[0];
			$description = (is_null($description)) ? "No description was provided for this site." : sanitize($description->textContent);

			$url = sanitize($url->textContent);
			$title = sanitize($title->textContent);
			
			// filter duplicate urls/results
            if(!empty($results['search'])) {
                if(in_array($url, array_column($results['search'], "url"))) continue;
            }

			$results['search'][] = array("id" => uniqid(rand(0, 9999)), "source" => "DuckDuckGo", "title" => $title, "url" => $url, "description" => $description, "engine_rank" => $rank);
			$rank -= 1;
		}
		unset($response, $xpath, $scrape, $rank);

		return $results;
    }
}
?>
