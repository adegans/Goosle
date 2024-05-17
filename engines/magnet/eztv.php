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
        $url = "https://eztvx.to/api/get-torrents?".http_build_query(array("imdb_id" => $query));

        unset($query);

        return $url;
	}

    public function get_request_headers() {
		return array(
			'Accept' => 'application/json, */*;q=0.8',
			'Accept-Language' => null,
			'Accept-Encoding' => null,
			'Connection' => null,
			'Sec-Fetch-Dest' => null,
			'Sec-Fetch-Mode' => null,
			'Sec-Fetch-Site' => null
		);
	}

	public function parse_results($response) {
		$results = array();
		$json_response = json_decode($response, true);
		
		// No response
		if(empty($json_response)) return $results;
		
		// Nothing found
		if($json_response['torrents_count'] == 0) return $results;
		
		// Use API result
		foreach($json_response['torrents'] as $result) {
			$name = sanitize($result['title']);
			$magnet = sanitize($result['magnet_url']);
			$hash = strtolower(sanitize($result['hash']));
			$seeders = sanitize($result['seeds']);
			$leechers = sanitize($result['peers']);
			$size = sanitize($result['size_bytes']);
			
			// Ignore results with 0 seeders?
			if($this->opts->show_zero_seeders == "off" AND $seeders == 0) continue;
			
			// Get extra data
			$quality = (preg_match('/(480p|720p|1080p|2160p)/i', $name, $quality)) ? $quality[0] : "";
			$codec = (preg_match('/(x264|h264|x265|h265|xvid)/i', $name, $codec)) ? $codec[0] : "";
			$date_added = sanitize($result['date_released_unix']);
			
			// Filter by Season (S01) or Season and Episode (S01E01)
			// Where [0][0] = Season and [0][1] = Episode
			$season = sanitize($result['season']);
			$episode = sanitize($result['episode']);
			
			if(preg_match_all("/(S[0-9]{1,3})|(E[0-9]{1,3})/i", $this->query, $filter_episode)) {
				if(str_ireplace("s0", "", $filter_episode[0][0]) != $season || (array_key_exists(1, $filter_episode[0]) && str_ireplace("e0", "", $filter_episode[0][1]) != $episode)) {
					continue;
				}
			}
			
			$results[] = array (
				// Required
				"id" => uniqid(rand(0, 9999)), "source" => "EZTV", "name" => $name, "magnet" => $magnet, "hash" => $hash, "seeders" => $seeders, "leechers" => $leechers, "size" => human_filesize($size),
				// Extra
				"quality" => $quality, "codec" => $codec, "date_added" => $date_added
			);
		}
		unset($json_response);
		
		return $results;
	}
}
?>
