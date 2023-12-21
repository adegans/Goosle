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
		// Make reasonably sure it's an IMDb id and abort if it's not
		$query_terms = explode(" ", $this->query);
		if(substr(strtolower($query_terms[0]), 0, 2) !== "tt") return "";
		
		// Prepare a search query by stripping out everything but numbers and abort if nothing is left
		$query = preg_replace('/[^0-9]/', '', $query_terms[0]);
		if(strlen($query) == 0) return "";
		
		// Is eztvx.to blocked for you? Use one of these urls as an alternative
		// eztv1.xyz, eztv.wf, eztv.tf, eztv.yt
		return "https://eztvx.to/api/get-torrents?imdb_id=".urlencode($query);
	}

	public function parse_results($response) {
		$results = array();
		
		$response = curl_multi_getcontent($this->ch);
		$json_response = json_decode($response, true);
		
		// No response
		if(empty($json_response)) return $results;
		
		// Nothing found
		if($json_response['torrents_count'] == 0) return $results;
		
		// Use API result
		foreach ($json_response['torrents'] as $episode) {
			$name = sanitize($episode['title']);
			$magnet = sanitize($episode['magnet_url']);
			$seeders = sanitize($episode['seeds']);
			$leechers = sanitize($episode['peers']);
			$size = sanitize($episode['size_bytes']);
			
			// Find actual quality of episode
			if(preg_match('/(480p|720p|1080p|2160p)/i', $name, $quality)) {
				$quality = $quality[0];
			} else {
				$quality = "Unknown";
			}
			
			$date_added = sanitize($episode['date_released_unix']);
			
			// Filter by Season (S01) or Season and Episode (S01E01)
			$season = sanitize($episode['season']);
			$episode = sanitize($episode['episode']);
			
			if(preg_match_all("/(S[0-9]{1,3}|E[0-9]{1,3})/i", $this->query, $filter_episode)) {
				if(str_ireplace("s0", "", $filter_episode[0][0]) != $season || (array_key_exists(1, $filter_episode[1]) && str_ireplace("e0", "", $filter_episode[0][1]) != $episode)) {
					continue;
				}
			}
			
			// Remove results with 0 seeders?
			if($this->opts->show_zero_seeders == "off" AND $seeders == 0) continue;
			
			array_push($results, array (
				// Required
				"source" => "EZTV",
				"name" => $name,
				"magnet" => $magnet,
				"seeders" => $seeders,
				"leechers" => $leechers,
				"size" => human_filesize($size),
				// Optional
				"quality" => $quality,
				"date_added" => $date_added
			));

			unset($name, $magnet, $seeders, $leechers, $size, $quality, $category, $date_added, $season, $episode);
		}
		
		return $results;
	}
}
?>
