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
class GoogleRequest extends EngineRequest {
    public function get_request_url() {
		// Split the query
	    $query_terms = explode(" ", strtolower($this->query), 2);
	    $query_terms[0] = strtolower($query_terms[0]);
	
		// Safe search override
		$safe = 1;
		if(strpos($query_terms[0], "safe") !== false) {
			$switch = explode(":", $query_terms[0]);

			if(!is_numeric($switch[1])) {
				$safe = (strtolower($switch[1]) == "off") ? "0" : "2";
				$this->query = implode(" ", array_slice($query_terms, 1));
			}
		}

		// q = query
		// safe = Safe search (Default 1) 0 = off (may include nsfw/illegal content), 1 = moderate, 2 = on/strict
		// pws = Personal search results 0 = off
		// tbs = In Goosle used for 'verbatim' search, adding this enables it
		// complete = Instant results related, 0 = off
		// num = Number of results per page (number, multiple of 10 usually)
		// sclient = where are you searching from
		
		// Including the preferred language variable breaks the page result, and with that the crawler!
		
		$args = array("q" => $this->query, "safe" => $safe, "pws" => "0", "tbs" => "li:1", "complete" => "0", "num" => "30", "sclient" => "web");
        $url = "https://www.google.com/search?".http_build_query($args);

        unset($query_terms, $safe, $switch, $args);

        return $url;
    }

    public function parse_results($response) {
		$results = array();
        $xpath = get_xpath($response);

        if(!$xpath) return $results;

		// Scrape recommended
        $didyoumean = $xpath->query(".//a[@class='gL9Hy']")[0];
        if(!is_null($didyoumean)) {
			$results['did_you_mean'] = $didyoumean->textContent;
        }
        $search_specific = $xpath->query(".//a[@class='spell_orig']")[0];
        if(!is_null($search_specific)) {
	        // Google doesn't add quotes by itself
			$results['search_specific'] = "\"".$search_specific->textContent."\"";
		}

		// Scrape the results
		$scrape = $xpath->query("//div[@id='search']//div[@class='MjjYud']");
		$rank = $results['amount'] = count($scrape);
        foreach($scrape as $result) {
			$url = $xpath->evaluate(".//div[@class='yuRUbf']//a/@href", $result)[0];
			if($url == null) continue;
			
			$title = $xpath->evaluate(".//h3", $result)[0];
			if($title == null) continue;
			
			$description = $xpath->evaluate(".//div[contains(@class, 'VwiC3b')]", $result)[0];
			$description = ($description == null) ? "No description was provided for this site." : htmlspecialchars(trim($description->textContent));

			$url = htmlspecialchars(trim($url->textContent));
			$title = htmlspecialchars(trim($title->textContent));
			$id = uniqid(rand(0, 9999));
			
			$results['search'][] = array ("id" => $id, "source" => "Google", "title" => $title, "url" => $url, "description" => $description, "engine_rank" => $rank);
			$rank -= 1;
        }
		unset($response, $xpath, $scrape, $rank);

        return $results;
    }
}
?>
