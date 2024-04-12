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
class MagnetDLRequest extends EngineRequest {
	public function get_request_url() {
        $url = "https://www.magnetdl.com/".substr($this->query, 0, 1)."/".str_replace(' ', '-', $this->query);

        return $url;
	}
	
	public function parse_results($response) {
		$results = array();
		$xpath = get_xpath($response);
		
		// Failed to load page
		if(!$xpath) return $results;
		
		// Scrape the page
		foreach($xpath->query("//table[@class='download']/tbody/tr") as $result) {			
			// Skip page navigation and incompatible rows
			if(is_null($xpath->evaluate(".//td[2]", $result)[0])) continue;

			$name = sanitize($xpath->evaluate(".//td[2]/a/@title", $result)[0]->textContent);
			$magnet = sanitize($xpath->evaluate(".//td[1]/a/@href", $result)[0]->textContent);
			$hash = parse_url($magnet, PHP_URL_QUERY);
			parse_str($hash, $hash_parameters);
			$hash = strtolower(str_replace("urn:btih:", "", $hash_parameters['xt']));
			$seeders = sanitize($xpath->evaluate(".//td[7]", $result)[0]->textContent);
			$leechers = sanitize($xpath->evaluate(".//td[8]", $result)[0]->textContent);
			$size = sanitize($xpath->evaluate(".//td[6]", $result)[0]->textContent);

			// Ignore results with 0 seeders?
			if($this->opts->show_zero_seeders == "off" AND $seeders == 0) continue;
			
			// Get extra data
			$category = sanitize($xpath->evaluate(".//td[4]", $result)[0]->textContent);
			$url = "https://www.magnetdl.com".sanitize($xpath->evaluate(".//td[2]//a/@href", $result)[0]->textContent);
			
			// Filter episodes
			if(!is_season_or_episode($this->query, $name)) continue;
			
			$id = uniqid(rand(0, 9999));
			
			$results[] = array (
				// Required
				"id" => $id, "source" => "magnetdl.com", "name" => $name, "magnet" => $magnet, "hash" => $hash, "seeders" => $seeders, "leechers" => $leechers, "size" => $size,
				// Extra
				"category" => $category, "url" => $url
			);
		}
		unset($response, $xpath);

		return $results;
	}
}
?>
