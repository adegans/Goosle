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
class WordPressRequest extends EngineRequest {
	public function get_request_url() {
		// What are we looking for?
		if($this->search->query_terms[1] == 'hook') {
			// https://developer.wordpress.org/reference/hooks/HOOK_OR_FILTER_NAME/
			$type = 'hooks';
			$query = $this->search->query_terms[2];
		} else {
			// https://developer.wordpress.org/reference/functions/FUNCTION_NAME/
			$type = 'functions';
			$query = $this->search->query_terms[1];
		}

		

		$url = 'https://developer.wordpress.org/reference/'.$type.'/.'.urlencode($query).'/';

		unset($query, $type);
		
		return $url;
	}
	
    public function get_request_headers() {
		return array(
			'Accept' => 'text/html, application/xhtml+xml, application/xml;q=0.8, */*;q=0.7'
		);
	}

	public function parse_results($response) {
        $engine_result = array();
        $xpath = get_xpath($response);

		// No response
		if(!$xpath) return $engine_result;

		// Scrape the results
		$scrape = $xpath->query("//div/main/article");

		// No results
        if(count($scrape) == 0) return $engine_result;

		if($this->search->query_terms[1] == 'hook') {
			$type = 'hooks';
			$query = $this->search->query_terms[2];
		} else {
			$type = 'functions';
			$query = $this->search->query_terms[1];
		}

		// Process scrape
		$usage = $xpath->evaluate(".//h1[@class='wp-block-wporg-code-reference-title']", $scrape[0]);
		if($usage->length == 0) return $engine_result;

		$purpose = $xpath->evaluate(".//section[@class='wp-block-wporg-code-reference-summary']", $scrape[0]);
		$description = $xpath->evaluate(".//section[contains(@class, 'wp-block-wporg-code-reference-explanation')]/p[1]", $scrape[0]);
		if($description->length == 0) $description = $xpath->evaluate(".//section[contains(@class, 'wp-block-wporg-code-reference-description')]/p[1]", $scrape[0]);
		$introduced = $xpath->evaluate(".//section[@class='wp-block-wporg-code-reference-changelog']//tbody", $scrape[0]);

		$title = sanitize($query);
		$purpose = ($purpose->length > 0) ? sanitize($purpose[0]->textContent) : "";
		$description = ($description->length > 0) ? sanitize($description[0]->textContent) : "";
		$usage = ($usage->length > 0) ? sanitize($usage[0]->textContent) : "";
		$introduced = ($introduced->length > 0) ? sanitize($introduced[0]->lastChild->firstElementChild->textContent) : "(Unknown)";

		// Clean up string
		$usage = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $usage);

		// Return result
		$engine_result = array (
            // Required
			'title' => ucfirst($type).": ".$title,
			'text' => "<p><em><small>Since WordPress ".$introduced."</small></em></p><p>".$purpose."</p><p>".highlight_string("<?php ".htmlspecialchars_decode($usage)." ?>", 1)."</p><p>".$description."</p>",
			'source' => "https://developer.wordpress.org/reference/".$type."/".urlencode($query)."/",
			'note' => "Description may be incomplete. Always check the documentation page for more information."
		);
		unset($response, $xpath, $scrape);

		return $engine_result;
	}
}
?>
