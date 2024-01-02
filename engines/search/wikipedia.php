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

	public function parse_results($response) {
		$results = array();
		$json_response = json_decode($response, true);
		
		if(empty($json_response)) return $results;
		
		// No results
		if($json_response['query']['searchinfo']['totalhits'] == 0) return $results;
		
		$rank = $results['amount'] = count($json_response['query']['search']);
		foreach($json_response['query']['search'] as $result) {
			$title = htmlspecialchars(trim($result['title']));
			$url = "https://".$this->opts->wikipedia_language.".wikipedia.org/wiki/".htmlspecialchars(str_replace(" ", "_", trim($result['title'])));
			$description = htmlspecialchars(strip_tags(trim($result['snippet'])));
			$id = uniqid(rand(0, 9999));
		
			$results['search'][] = array ("id" => $id, "source" => "Wikipedia", "title" => $title, "url" => $url, "description" => $description, "engine_rank" => $rank);
			$rank -= 1;
		}
		unset($response, $json_response, $rank);
		
		return $results;
	}
}
?>
