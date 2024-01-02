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
class PHPnetRequest extends EngineRequest {
	public function get_request_url() {
		$this->query = str_replace("_", "-", str_replace("php ", "", $this->query));

		return "https://www.php.net/manual/function.".urlencode($this->query);
	}
	
	public function parse_results($response) {
        $results = array();
        $xpath = get_xpath($response);

        if($xpath) {
			// Scrape the page
			$title = $xpath->query("//div/section/div[@class='refentry']/div/h1[@class='refname']")[0]->textContent;
			if(is_null($title)) return array();
			$php_versions = $xpath->query("//div/section/div[@class='refentry']/div/p[@class='verinfo']")[0]->textContent;
			$purpose = $xpath->query("//div/section/div[@class='refentry']/div/p[@class='refpurpose']")[0]->textContent;
			$usage = $xpath->query("//div/section/div[@class='refentry']/div[@class='refsect1 description']/div[@class='methodsynopsis dc-description']")[0]->textContent;

			$response = array (
                // Required
				"title" => sanitize($title),
				"text" => "<p><em><small>".$php_versions."</small></em></p><p>".$purpose."</p><p>".highlight_string("<?php ".trim($usage)." ?>", 1)."</p>",
				"source" => "https://www.php.net/manual/function.".urlencode($this->query)
			);

			return $response;
	    } else {
	        return array(
                "title" => "Oof...",
                "text" => "PHP.net didn't provide any answers. Try again later."
	        );
		}
	}
}
?>
