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
class PHPnetRequest extends EngineRequest {
	public function get_request_url() {
		// Format query/url for php.net
		$query = str_replace('_', '-', $this->search->query_terms[1]);

		$url = 'https://www.php.net/manual/function.'.urlencode($query).'.php';
		
		unset($query);
		
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
		$scrape = $xpath->query("//div[@class='refentry']");

		// No results
        if(count($scrape) == 0) return $engine_result;

		$query = str_replace('_', '-', $this->search->query_terms[1]);

		// Process scrape
		$title = $xpath->evaluate(".//div/h1[@class='refname']", $scrape[0]);
		if($title->length == 0) return $engine_result;

		$php_versions = $xpath->evaluate(".//div/p[@class='verinfo']", $scrape[0]);
		$purpose = $xpath->evaluate(".//div/p[@class='refpurpose']", $scrape[0]);
		$usage = $xpath->evaluate(".//div[@class='refsect1 description']/div[@class='methodsynopsis dc-description']", $scrape[0]);
		$summary = $xpath->evaluate(".//div[@class='refsect1 description']/p[@class='para rdfs-comment']", $scrape[0]);

		$title = sanitize($title[0]->textContent);
		$php_versions = ($php_versions->length > 0) ? sanitize($php_versions[0]->textContent) : "";
		$purpose = ($purpose->length > 0) ? sanitize($purpose[0]->textContent) : "";
		$usage = ($usage->length > 0) ? sanitize($usage[0]->textContent) : "";
		$summary = ($summary->length > 0) ? sanitize($summary[0]->textContent) : "";

		// Clean up string
		$usage = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $usage);

		// Return result
		$engine_result = array (
            // Required
			'title' => "Function: ".$title,
			'text' => "<p><em><small>".$php_versions."</small></em></p><p>".$purpose."</p><p>".highlight_string("<?php ".htmlspecialchars_decode($usage)." ?>", 1)."</p><p>".$summary."</p>",
			'source' => "https://www.php.net/manual/function.".urlencode($query).".php",
			'note' => "Description may be incomplete. Always check the documentation page for more information."
		);
		unset($response, $xpath, $scrape);

		return $engine_result;
	}
}
?>
