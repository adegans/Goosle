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
        $results = array();

		// Split the query
	    $query_terms = explode(" ", strtolower($this->query));
	
		// Category search
		$cat = null;
		if($query_terms[0] == 'app') {
			$cat = 'app';
		} else if($query_terms[0] == 'book') {
			$cat = 'bks';
		} else if($query_terms[0] == 'news') {
			$cat = 'nws';
		} else if($query_terms[0] == 'shop') {
			$cat = 'shop';
		} else if($query_terms[0] == 'patent') {
			$cat = 'pts';
		}
		
		// Language override
		$lang = null;
		if(strpos($query_terms[0], "lang") !== false) {
			$switch = explode(":", $query_terms[0]);

			if(strlen($switch[1]) == 2 && !is_numeric($switch[1])) {
				$lang = "lang_".$switch[1];

				$this->query = implode(" ", array_slice($query_terms, 1));
			}
		}

		// Safe search override
		$safe = 1;
		if(strpos($query_terms[0], "safe") !== false) {
			$switch = explode(":", $query_terms[0]);

			if(!is_numeric($switch[1])) {
				$safe = ($switch[1] == "on") ? "2" : "0";

				$this->query = implode(" ", array_slice($query_terms, 1));
			}
		}

		// q = query
		// safe = Safe search (Default 1) 0 = off (may include nsfw/illegal content), 1 = moderate, 2 = on/strict
		// lr = Language (lang_XX, optional)
		// tbm = Category search (app, bks (books), isch (images), vid, nws (News), shop, pts (Patents))
		// pws = Personal search results on|off (Default 0, off)
		// num = Number of results per page (number, multiple on 10 usually)
		// start = Start position in search (Kind of like pages) (number, multiple on 10|15 usually)

		$args = array("q" => $this->query, "safe" => $safe, "lr" => (!is_null($lang)) ? $lang : '', "tbm" => (!is_null($cat)) ? $cat : '', "pws" => 0, "num" => 20, "start" => 0);
        $url = "https://www.google.com/search?".http_build_query($args);

        unset($query_terms, $switch, $args, $cat, $lang, $safe);

        return $url;
    }

    public function parse_results($response) {
        $results = array();
        $xpath = get_xpath($response);

        if(!$xpath) return $results;

        $didyoumean = $xpath->query(".//a[@class='gL9Hy']")[0];
        if(!is_null($didyoumean)) {
            array_push($results, array("did_you_mean" => $didyoumean->textContent));
        }
        $search_specific = $xpath->query(".//a[@class='spell_orig']")[0];
        if(!is_null($search_specific)) {
            array_push($results, array("search_specific" => $search_specific->textContent));
        }

        foreach($xpath->query("//div[@id='search']//div[contains(@class, 'g')]") as $result) {
            $url = $xpath->evaluate(".//div[@class='yuRUbf']//a/@href", $result)[0];
			if($url == null) continue;

            if(!empty($results)) { // filter duplicate urls/results
		        $result_urls = array_column($results, "url");
                if(in_array($url->textContent, $result_urls) OR in_array(get_base_url($url->textContent), $result_urls)) continue;
            }

			$title = $xpath->evaluate(".//h3", $result)[0];
			if($title == null) continue;

			$description = $xpath->evaluate(".//div[contains(@class, 'VwiC3b')]", $result)[0];

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
