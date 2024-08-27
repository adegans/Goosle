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
class DefinitionRequest extends EngineRequest {
	public function get_request_url() {
		// [0] = (define|meaning)
		// [1] = WORD
		$url = 'https://api.dictionaryapi.dev/api/v2/entries/en/'.urlencode($this->search->query_terms[1]);

		return $url;
	}

    public function get_request_headers() {
		return array(
			'Accept' => 'application/json, */*;q=0.8',
			'Accept-Language' => null,
			'Accept-Encoding' => null,
			'Sec-Fetch-Dest' => null,
			'Sec-Fetch-Mode' => null,
			'Sec-Fetch-Site' => null
		);
	}

	public function parse_results($response) {
		$engine_result = array();
		$json_response = json_decode($response, true);

		// No response
		if(empty($json_response)) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No response', 0);
			return $engine_result;
		}


		// No results
        if(isset($json_response['title']) && $json_response['title'] == 'No Definitions Found') {
			if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No results', 0);
			return $engine_result;
		}


		$result = $json_response[0]; // Always grab the first result

		// Incomplete listing? Bail!
        if(!array_key_exists('word', $result)) return $engine_result;

		// Find a phonetic spelling
		if(isset($result['phonetic'])) {
			$phonetic = $result['phonetic'];
		} else if(isset($result['phonetics'])) {
			$phonetic = array_column($result['phonetics'], 'text');
			$phonetic = (count($phonetic) > 0) ? $phonetic[0] : $result['word'];
		} else {
			$phonetic = $result['word'];
		}

		// List definitions
		$formatted_response = "";
		foreach($result['meanings'] as $meaning) {
			$formatted_response .= "<p>".sanitize($meaning['partOfSpeech'])."</p>";
			$definitions = array_slice($meaning['definitions'], 0, 3);

			$formatted_response .= "<ol class=\"word-definitions\">";
			foreach($definitions as $definition) {
				$formatted_response .= "<li>";
				$formatted_response .= "	".sanitize($definition['definition']);
				$formatted_response .= (array_key_exists("example", $definition)) ? "	<br /><small><strong>Example:</strong> ".sanitize($definition['example'])."</small>" : "";
				$formatted_response .= "</li>";

				unset($definition);
			}
			$formatted_response .= "</ol>";

			unset($meaning);
		}

		// Return result
		$engine_result = array(
			'title' => "Definition for: ".sanitize($result['word'])." <span>[".sanitize($phonetic)."]</span>",
			'text' => $formatted_response,
			'source' => sanitize($result['sourceUrls'][0])
		);

		if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 1, 1);

		unset($response, $json_response, $result, $phonetic, $definitions, $formatted_response);

		return $engine_result;
	}
}
?>
