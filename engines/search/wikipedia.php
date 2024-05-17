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
class WikiRequest extends EngineRequest {
    public function get_request_url() {
		$args = array("srsearch" => $this->query, "action" => "query", "format" => "json", "list" => "search", "limit" => "10");
        $url = "https://".$this->opts->wikipedia_language.".wikipedia.org/w/api.php?".http_build_query($args);

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
		
		if(empty($json_response)) return $results;
		
		// No results
		if($json_response['query']['searchinfo']['totalhits'] == 0) return $results;
		
		$rank = $results['amount'] = count($json_response['query']['search']);
		foreach($json_response['query']['search'] as $result) {
			$title = sanitize($result['title']);
			$url = "https://".$this->opts->wikipedia_language.".wikipedia.org/wiki/".sanitize(str_replace(" ", "_", $result['title']));
			$description = sanitize(strip_tags($result['snippet']));
		
			$results['search'][] = array ("id" => uniqid(rand(0, 9999)), "source" => "Wikipedia", "title" => $title, "url" => $url, "description" => $description, "engine_rank" => $rank);
			$rank -= 1;
		}
		unset($response, $json_response, $rank);
		
		return $results;
	}
}
?>
