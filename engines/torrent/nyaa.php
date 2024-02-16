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
		$args = array("q" => $this->query);
        $url = "https://nyaa.si/?".http_build_query($args);

        unset($args);

        return $url;
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
			$hash = parse_url($magnet, PHP_URL_QUERY);
			parse_str($hash, $hash_parameters);
			$hash = str_replace("urn:btih:", "", $hash_parameters['xt']);
			$seeders = sanitize($meta[3]->textContent);
			$leechers = sanitize($meta[4]->textContent);
			$size =  str_replace("GiB", "GB", str_replace("MiB", "MB", sanitize($meta[1]->textContent)));

			// Ignore results with 0 seeders?
			if($this->opts->show_zero_seeders == "off" AND $seeders == 0) continue;
			
			// Get extra data
			$category = sanitize($xpath->evaluate(".//td[1]//a/@title", $result)[0]->textContent);
			$category = str_replace(" - ", "/", $category);
			$url = "https://nyaa.si".sanitize($xpath->evaluate(".//td[@colspan='2']//a[not(contains(@class, 'comments'))]/@href", $result)[0]->textContent);
			$date_added =  sanitize($meta[2]->textContent);
			$date_added = explode("-", substr($date_added, 0, 10));
			$date_added = mktime(0, 0, 0, intval($date_added[1]), intval($date_added[2]), intval($date_added[0]));
			
			// Filter by Season (S01) or Season and Episode (S01E01)
			// Where [0][0] = Season and [0][1] = Episode
			if(preg_match_all("/(S[0-9]{1,3})|(E[0-9]{1,3})/i", $this->query, $query_episode) && preg_match_all("/(S[0-9]{1,3})|(E[0-9]{1,3})/i", $name, $match_episode)) {
				if($query_episode[0][0] != $match_episode[0][0] || (array_key_exists(1, $query_episode[0]) && array_key_exists(1, $match_episode[0]) && $query_episode[0][1] != $match_episode[0][1])) {
					continue;
				}
			}
			
			$id = uniqid(rand(0, 9999));
			
			$results[] = array (
				// Required
				"id" => $id, "source" => "nyaa.si", "name" => $name, "magnet" => $magnet, "hash" => $hash, "seeders" => $seeders, "leechers" => $leechers, "size" => $size,
				// Extra
				"category" => $category, "url" => $url, "date_added" => $date_added
			);
		}
		unset($response, $xpath);

		return $results;
	}
}
?>
