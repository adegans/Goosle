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
class eztvhighlights extends EngineRequest {
    public function get_request_url() {
        $url = "https://eztvx.to/api/get-torrents?".http_build_query(array("limit" => "16"));
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
			$thumbnail = sanitize($result['small_screenshot']);
			$season = sanitize($result['season']);
			$episode = sanitize($result['episode']);
			$magnet_link = sanitize($result['magnet_url']);
			$quality = (preg_match('/(480p|720p|1080p|2160p)/i', $name, $quality)) ? $quality[0] : "";
			$codec = (preg_match('/(x264|h264|x265|h265|xvid)/i', $name, $codec)) ? $codec[0] : "";

			// Clean up show name
			$name = (preg_match("/.+?(?=S[0-9]{1,3}E[0-9]{1,3})/i", $name, $clean_name)) ? $clean_name[0] : $name;
			
			// Set up codec for quality
			if(!empty($codec)) $quality = $quality." ".$codec;

			$results[] = array (
				"name" => $name, "thumbnail" => $thumbnail, "season" => $season, "episode" => $episode, "magnet_link" => $magnet_link, "quality" => $quality
			);

			unset($result, $name, $clean_name, $thumbnail, $season, $episode, $magnet_link, $quality, $codec);
		}
		unset($json_response);

		return array_slice($results, 0, 16);
    }
}
?>
