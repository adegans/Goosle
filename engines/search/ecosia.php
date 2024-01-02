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
class EcosiaRequest extends EngineRequest {
    public function get_request_url() {
		$args = array("q" => $this->query, "addon" => "opensearch");
        $url = "https://www.ecosia.org/search/?".http_build_query($args);

        return $url;
    }

    public function parse_results($response) {
		$results = array();
		$xpath = get_xpath($response);

		if(!$xpath) return $results;

		// Scrape the results
		$scrape = $xpath->query("//article[@class='result web-result mainline__result']");
		$rank = $results['amount'] = count($scrape);
		foreach($scrape as $result) {
			$url = $xpath->evaluate(".//a[@class='result__link']/@href", $result)[0];
			if($url == null) continue;

			$title = $xpath->evaluate(".//h2[@class='result-title__heading']", $result)[0];
			if($title == null) continue;
			
			$description = $xpath->evaluate(".//p[@class='web-result__description']", $result)[0];
			$description = ($description == null) ? "No description was provided for this site." : htmlspecialchars(trim($description->textContent));

			$url = htmlspecialchars(trim($url->textContent));
			$title = htmlspecialchars(trim($title->textContent));
			$id = uniqid(rand(0, 9999));
			
			$results['search'][] = array ("id" => $id, "source" => "Ecosia", "title" => $title, "url" => $url, "description" => $description, "engine_rank" => $rank);
			$rank -= 1;
		}
		unset($response, $xpath, $scrape, $rank);

		return $results;
    }
}
?>
