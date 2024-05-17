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
class QwantNewsRequest extends EngineRequest {
    public function get_request_url() {
		// Split the query
	    $query_terms = explode(" ", strtolower($this->query));

		// Safe search override
		$safe = "1"; // Moderate results
		if(strpos($query_terms[0], "safe") !== false) {
			$switch = explode(":", $query_terms[0]);

			if(!is_numeric($switch[1])) {
				$safe = (strtolower($switch[1]) == "off") ? "0" : "2";
				$this->query = implode(" ", array_slice($query_terms, 1));
			}
		}

		$language = (strlen($this->opts->qwant_language) > 0 && strlen($this->opts->qwant_language < 6)) ? $this->opts->qwant_language : "en_gb";

		// q = query
		// t = Type of search, Images
		// locale = In which language should the search be done
		// source = Where to get the news from (All)
		// freshness = How old may the article be? (1 month)
		// device = What kind of device are we searching from?
		// safesearch = Safe search filter (0 = off, 1 = normal, 2 = strict)

		$args = array("q" => $this->query, "t" => 'news', 'locale' => $language, 'source' => 'all', 'freshness' => 'month', 'device' => 'desktop', 'safesearch' => $safe);
        $url = "https://api.qwant.com/v3/search/news?".http_build_query($args);

        unset($query_terms, $switch, $safe, $language, $args);

        return $url;
    }

    public function get_request_headers() {
		return array(
			'Accept' => 'application/json, */*;q=0.8',
			'Accept-Language' => null,
			'Accept-Encoding' => null,
			'Connection' => null,
			'Sec-Fetch-Dest' => null,
			'Sec-Fetch-Mode' => null,
			'Sec-Fetch-Site' => null
		);
	}

	public function parse_results($response) {
		$results = array();
		$json_response = json_decode($response, true);

		// No response
		if(empty($json_response)) return $results;

		// Nothing found
        if($json_response["status"] != "success") return $results;

		// Set base rank and result amound
		$rank = $results['amount'] = $json_response["data"]["result"]["total"];

		foreach ($json_response["data"]["result"]["items"] as $result) {
			$title = sanitize($result['title']);
			$url = sanitize($result['url']);
			$description = date('M d, Y H:i', sanitize($result['date']))." &sdot; ".sanitize($result['desc']);

			$results['search'][] = array ("id" => uniqid(rand(0, 9999)), "source" => "Qwant News", "title" => $title, "url" => $url, "description" => $description, "engine_rank" => $rank);
			$rank -= 1;
		}
		unset($response, $json_response, $rank);
		
		return $results;
	}
}
?>
