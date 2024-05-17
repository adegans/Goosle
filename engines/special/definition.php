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
class DefinitionRequest extends EngineRequest {
	public function get_request_url() {
        $query_terms = explode(" ", $this->query);

		// [0] = (define|d|mean|meaning)
		// [1] = WORD

		return "https://api.dictionaryapi.dev/api/v2/entries/en/".$query_terms[1];
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
		$json_response = json_decode($response, true);

		if(!empty($json_response)) {
			// Word not found
			if (array_key_exists("title", $json_response)) {
				return array(
	                "title" => strip_tags(trim($json_response['title'])),
	                "text" => strip_tags(trim($json_response['message']))
				);
			}
	
			// Grab first result if there are multiple
			$result = $json_response[0];
			$definitions = array_slice($result['meanings'][0]['definitions'], 0, 3);
	
			// Word found
			$formatted_response = strip_tags(trim($result['meanings'][0]['partOfSpeech']))."<br /><ol class=\"word-definitions\">";
			foreach($definitions as $key => $def) {
				$formatted_response .= "<li>".strip_tags(trim($def['definition']))."</li>";
			}
			$formatted_response .= "</ol>";
			
			return array(
				"title" => strip_tags(trim($result['word']))." <span>[".strip_tags(trim($result['phonetic']))."]</span>",
				"text" => $formatted_response,
				"source" => strip_tags(trim($result['sourceUrls'][0]))
			);
	    } else {
	        return array(
                "title" => "Whoops...",
                "text" => "No definitions could be loaded. Try again later."
	        );
		}
	}
}
?>
