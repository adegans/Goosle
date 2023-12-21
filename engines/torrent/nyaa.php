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
class NyaaRequest extends EngineRequest {
	public function get_request_url() {
		return "https://nyaa.si/?q=".urlencode($this->query);
	}
	
	public function parse_results($response) {
		$results = array();
		$xpath = get_xpath($response);
		
		// Failed to load page
		if(!$xpath) return $results;
		
		// Scrape the page
		foreach($xpath->query("//tbody/tr") as $result) {
			$meta = $xpath->evaluate(".//td[@class='text-center']", $result);
			
			$name = sanitize($xpath->evaluate(".//td[@colspan='2']//a[not(contains(@class, 'comments'))]/@title", $result)[0]->textContent);
			$magnet = sanitize($xpath->evaluate(".//a[2]/@href", $meta[0])[0]->textContent);
			$seeders = sanitize($meta[3]->textContent);
			$leechers = sanitize($meta[4]->textContent);
			$size =  sanitize($meta[1]->textContent);
			
			$category = sanitize($xpath->evaluate(".//td[1]//a/@title", $result)[0]->textContent);
			$url = sanitize($xpath->evaluate(".//td[@colspan='2']//a[not(contains(@class, 'comments'))]/@href", $result)[0]->textContent);
			$date_added =  sanitize($meta[2]->textContent);
			$date_added = explode("-", substr($date_added, 0, 10));
			$date_added = mktime(0, 0, 0, intval($date_added[1]), intval($date_added[2]), intval($date_added[0]));
			
			// Remove results with 0 seeders?
			if($this->opts->show_zero_seeders == "off" AND $seeders == 0) continue;
			
			array_push($results, array (
				// Required
				"source" => "nyaa.si",
				"name" => $name,
				"magnet" => $magnet,
				"seeders" => $seeders,
				"leechers" => $leechers,
				"size" => $size,
				// Optional values
				"category" => str_replace(" - ", "/", $category),
				"url" => "https://nyaa.si".$url,
				"date_added" => $date_added
			));

			unset($name, $magnet, $seeders, $leechers, $size, $category, $url, $date_added, $meta);
		}

		return $results;
	}
}
?>
