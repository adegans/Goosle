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
		$query = str_replace('%22', '\"', $this->query);
		$query = str_replace('php ', '', $query);
		$query = str_replace('_', '-', $query);

		// Is there no query left? Bail!
		if(empty($query)) return false;

		$url = 'https://www.php.net/manual/function.'.urlencode($query);
		
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
		$scrape = $xpath->query("//div/section/div[@class='refentry']");

		// No results
        if(count($scrape) == 0) return $engine_result;

		$query = str_replace('%22', '', $this->query);
		$query = str_replace('php ', '', $query);
		$query = str_replace('_', '-', $query);

		foreach($scrape as $result) {
			$title = $xpath->query(".//div/h1[@class='refname']")[0]->textContent;
			if(is_null($title)) return $engine_result;

			$php_versions = $xpath->query(".//div/p[@class='verinfo']")[0]->textContent;
			$purpose = $xpath->query(".//div/p[@class='refpurpose']")[0]->textContent;
			$usage = $xpath->query(".//div[@class='refsect1 description']/div[@class='methodsynopsis dc-description']")[0]->textContent;
			$summary = $xpath->query(".//div[@class='refsect1 description']/p[@class='para rdfs-comment']")[0]->textContent;

			$engine_result = array (
                // Required
				'title' => "Function: ".sanitize($title),
				'text' => "<p><em><small>".sanitize($php_versions)."</small></em></p><p>".sanitize($purpose)."</p><p>".highlight_string("<?php ".sanitize($usage)." ?>", 1)."</p><p>".$summary."</p>",
				'source' => "https://www.php.net/manual/function.".urlencode($query)
			);
		}
		unset($response, $xpath, $scrape);

		return $engine_result;
	}
}
?>
