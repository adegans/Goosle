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
class EZTVRequest extends EngineRequest {
	public function get_request_url() {
		$query = preg_replace('/[^0-9]/', '', $this->query);
		if(strlen($query) == 0) $query = "0000";

		// Is eztvx.to blocked for you? Use one of these urls as an alternative
		// eztv1.xyz, eztv.wf, eztv.tf, eztv.yt

		$args = array("imdb_id" => $query);
        $url = "https://eztvx.to/api/get-torrents?".http_build_query($args);

        unset($query, $args);

        return $url;
	}

	public function parse_results($response) {
		$results = array();
		$json_response = json_decode($response, true);
		
		// No response
		if(empty($json_response)) return $results;
		
		// Nothing found
		if($json_response['torrents_count'] == 0) return $results;
		
		// Use API result
		foreach($json_response['torrents'] as $episode) {
			$name = sanitize($episode['title']);
			$magnet = sanitize($episode['magnet_url']);
			$hash = sanitize($episode['hash']);
			$seeders = sanitize_numeric(sanitize($episode['seeds']));
			$leechers = sanitize_numeric(sanitize($episode['peers']));
			$size = sanitize($episode['size_bytes']);
			
			// Ignore results with 0 seeders?
			if($this->opts->show_zero_seeders == "off" AND $seeders == 0) continue;
			
			// Get extra data
			$quality = (preg_match('/(480p|720p|1080p|2160p)/i', $name, $quality)) ? $quality[0] : "Unknown";
			$date_added = sanitize($episode['date_released_unix']);
			
			// Filter by Season (S01) or Season and Episode (S01E01)
			// Where [0][0] = Season and [0][1] = Episode
			$season = sanitize($episode['season']);
			$episode = sanitize($episode['episode']);
			
			if(preg_match_all("/(S[0-9]{1,3})|(E[0-9]{1,3})/i", $this->query, $filter_episode)) {
				if(str_ireplace("s0", "", $filter_episode[0][0]) != $season || (array_key_exists(1, $filter_episode[0]) && str_ireplace("e0", "", $filter_episode[0][1]) != $episode)) {
					continue;
				}
			}
			
			$id = uniqid(rand(0, 9999));
			
			$results[] = array (
				// Required
				"id" => $id, "source" => "EZTV", "name" => $name, "magnet" => $magnet, "hash" => $hash, "seeders" => $seeders, "leechers" => $leechers, "size" => human_filesize($size),
				// Extra
				"quality" => $quality, "date_added" => $date_added
			);
		}
		unset($json_response);
		
		return $results;
	}
}
?>
