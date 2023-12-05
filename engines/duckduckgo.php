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
        $results = array();

		// Split the query
	    $query_terms = explode(" ", strtolower($this->query));

		// Safe search override
		$safe = "-1";
		if(strpos($query_terms[0], "safe") !== false) {
			$switch = explode(":", $query_terms[0]);

			if(!is_numeric($switch[1])) {
				$safe = ($switch[1] == "on") ? "1" : "-2";

				$this->query = implode(" ", array_slice($query_terms, 1));
			}
		}

		// q = query
		// kz = Instant answers (1 = on, -1 = off)
		// kc = Autoload images (1 = on, -1 = off)
		// kav = Autoload results (1 = on, -1 = off)
		// kf = Favicons (1 = on, -1 = off)
		// kaf = Full URLs (1 = on, -1 = off)
		// kac = Auto suggest (1 = on, -1 = off)
		// kd = Redirects (1 = on, -1 = off)
		// kh = HTTPS (1 = on, -1 = off)
		// kg = Get/Post (g = GET, p = POST)
		// kp = Safe search  (1 = on, -1 = moderate, -2 = off (may include nsfw/illegal content))
		// k1 = Ads (1 = on, -1 = off)
		// More here: https://duckduckgo.com/duckduckgo-help-pages/settings/params/

		$args = array("q" => $this->query, "kz" => "-1", "kc" => "-1", "kav" => "-1", "kf" => "-1", "kaf" => "1", "kac" => "-1", "kd" => "-1", "kh" => "1", "kg" => "g", "kp" => $safe, "k1" => "-1");
        $url = "https://html.duckduckgo.com/html/?".http_build_query($args);

        unset($query_terms, $switch, $args, $safe);

        return $url;
    }

    public function parse_results($response) {
		$results = array();
		$xpath = get_xpath($response);

		if(!$xpath) return $results;
 
		$didyoumean = $xpath->query(".//div[@id='did_you_mean']/a[1]")[0];
		if(!is_null($didyoumean)) {
			array_push($results, array("did_you_mean" => $didyoumean->textContent));
		}
        $search_specific = $xpath->query(".//div[@id='did_you_mean']/a[2]")[0];
        if(!is_null($search_specific)) {
            array_push($results, array("search_specific" => $search_specific->textContent));
        }
 
        foreach($xpath->query("/html/body/div[1]/div[". count($xpath->query('/html/body/div[1]/div')) ."]/div/div/div[contains(@class, 'web-result')]/div") as $result) {
            $url = $xpath->evaluate(".//h2[@class='result__title']//a/@href", $result)[0];
            if($url == null) continue;

            if(!empty($results)) { // filter duplicate urls/results
		        $result_urls = array_column($results, "url");
                if(in_array($url->textContent, $result_urls) || in_array(get_base_url($url->textContent), $result_urls)) continue;
            }

			$title = $xpath->evaluate(".//h2[@class='result__title']", $result)[0];
			if($title == null) continue;

			$description = $xpath->evaluate(".//a[@class='result__snippet']", $result)[0];

            array_push($results, array (
                "title" => htmlspecialchars($title->textContent),
                "url" =>  htmlspecialchars($url->textContent),
                "description" =>  $description == null ? 'No description was provided for this site.' : htmlspecialchars($description->textContent)
            ));
		}

		return $results;
    }

}
?>
