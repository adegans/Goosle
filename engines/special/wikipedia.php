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
class WikipediaRequest extends EngineRequest {
	public function get_request_url() {
        $query_terms = explode(" ", $this->query);
 
 		// [0] = (wiki|w)
		// [1] = SEARCH TERM

		unset($query_terms[0]); // Remove first item (w or wiki) from array and encode the rest for Wikipedia	
		$this->query = implode(" ", $query_terms);
	
		return "https://wikipedia.org/w/api.php?format=json&action=query&prop=extracts%7Cpageimages&exintro&explaintext&redirects=1&pithumbsize=500&titles=".urlencode($this->query);
	}
	
	public function parse_results($response) {
		$json_response = json_decode($response, true);

		if(!empty($json_response)) {
			$result = $json_response['query']['pages'];
			
			// Abort on invalid response
			if (!is_array($result)) return array();
	
			// Grab first result if there are multiple
			$result = $result[array_key_first($result)];

			// Page not found
			if (array_key_exists("missing", $result)) {
				return array(
					"title" => "Wiki page not found", 
					"text" => "Maybe the page doesn't exist. Try searching on Wikipedia with the link below or search for something else.", 
					"source" => "https://wikipedia.org/wiki/Special:Search?go=Go&search=".urlencode($this->query)
				);
			}

			// Page found
			$response = array(
				"title" => strip_tags(trim($result['title'])),
				"text" => strip_tags(trim($result['extract'])),
				"source" => "https://wikipedia.org/wiki/".urlencode($this->query)
			);
			
			if (array_key_exists("thumbnail",  $result)) {
				$response['image'] = strip_tags(trim($result['thumbnail']['source']));
			}
			
			return $response;
	    } else {
	        return array(
                "title" => "Sigh...",
                "text" => "Wikipedia could not be loaded. Try again later."
	        );
		}
	}
}
?>
