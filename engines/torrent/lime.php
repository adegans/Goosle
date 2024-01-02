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
class LimeRequest extends EngineRequest {
	public function get_request_url() {
		$this->query = preg_replace("/[^a-z0-9- ]+/", "", $this->query);
		$this->query = str_replace(" ", "-", $this->query);
		$url = "https://www.limetorrents.lol/search/all/".$this->query."/";
        return $url;
	}
	
	public function parse_results($response) {
		$results = array();
		$xpath = get_xpath($response);
		
		// Failed to load page
		if(!$xpath) return $results;

		// Scrape the page
		foreach($xpath->query("//table[@class='table2']//tr[position()>1]") as $result) {
			$name = sanitize($xpath->evaluate(".//td[@class='tdleft']//a[2]", $result)[0]->textContent);
			$hash = sanitize($xpath->evaluate(".//td[@class='tdleft']//a[1]/@href", $result)[0]->textContent);
			$hash = explode("/", substr($hash, 0, strpos($hash, ".torrent?")));
			$magnet = "magnet:?xt=urn:btih:".$hash[array_key_last($hash)]."&dn=".$name."&tr=".implode("&tr=", $this->opts->torrent_trackers);
			$seeders = sanitize($xpath->evaluate(".//td[@class='tdseed']", $result)[0]->textContent);
			$leechers = sanitize($xpath->evaluate(".//td[@class='tdleech']", $result)[0]->textContent);
			$size = sanitize($xpath->evaluate(".//td[@class='tdnormal'][2]", $result)[0]->textContent);

			// Ignore results with 0 seeders?
			if($this->opts->show_zero_seeders == "off" AND $seeders == 0) continue;
			
			// Get extra data
			$category = explode(" ", sanitize($xpath->evaluate(".//td[@class='tdnormal'][1]", $result)[0]->textContent));
			$category = $category[array_key_last($category)];
			$url = "https://www.limetorrents.lol".sanitize($xpath->evaluate(".//td[@class='tdleft']//a[2]/@href", $result)[0]->textContent);
			
			// Filter by Season (S01) or Season and Episode (S01E01)
			if(preg_match_all("/(S[0-9]{1,3})|(E[0-9]{1,3})/i", $this->query, $query_episode) && preg_match_all("/(S[0-9]{1,3})|(E[0-9]{1,3})/i", $name, $match_episode)) {
				if($query_episode[0][0] != $match_episode[0][0] || (array_key_exists(1, $query_episode[0]) && array_key_exists(1, $match_episode[0]) && $query_episode[0][1] != $match_episode[0][1])) {
					continue;
				}
			}
			
			$results[] = array (
				// Required
				"source" => "limetorrents.lol", "name" => $name, "magnet" => $magnet, "seeders" => $seeders, "leechers" => $leechers, "size" => $size,
				// Extra
				"category" => $category, "url" => $url
			);

			unset($name, $seeders, $leechers, $size, $hash, $magnet, $category, $url);
		}
		
		return $results;
	}
}
?>