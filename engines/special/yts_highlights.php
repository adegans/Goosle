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
class ytshighlights extends EngineRequest {
    public function get_request_url() {
        $url = "https://yts.mx/api/v2/list_movies.json?".http_build_query(array("limit" => "20", "sort_by" => $this->opts->yts_highlight));
        return $url;
    }
    
    public function parse_results($response) {
		$results = $torrents = array();
		$response = curl_multi_getcontent($this->ch);
		$json_response = json_decode($response, true);
		
		// No response
		if(empty($json_response)) return $results;
		
		// Nothing found
		if($json_response['status'] != "ok" || $json_response['data']['movie_count'] == 0) return $results;
		
		// Use API result
		foreach ($json_response['data']['movies'] as $highlight) {
			// Prevent gaps
			if(!array_key_exists("year", $highlight)) $highlight['year'] = "0000";
			if(!array_key_exists("genres", $highlight)) $highlight['genres'] = array();
			if(!array_key_exists("rating", $highlight)) $highlight['rating'] = "0";

			// Block these categories
			if(count(array_uintersect($highlight['genres'], $this->opts->yts_categories_blocked, "strcasecmp")) > 0) continue;
			
			$name = sanitize($highlight['title']);
			$thumbnail = sanitize($highlight['medium_cover_image']);
			$year = sanitize($highlight['year']);
			$category = sanitize(implode(', ', array_slice($highlight['genres'], 0, 2)));
			$rating = sanitize($highlight['rating']);

			foreach($highlight['torrents'] as $torrent) {
				$hash = sanitize($torrent['hash']);
				$magnet = "magnet:?xt=urn:btih:".$hash."&dn=".urlencode($name)."&tr=".implode("&tr=", $this->opts->torrent_trackers);
				$quality = sanitize($torrent['quality']);
				$codec = sanitize($torrent['video_codec']);
			
				$torrents[] = array (
					"magnet" => $magnet, "quality" => $quality, "codec" => $codec
				);
			}

			$results[] = array (
				"name" => $name, "thumbnail" => $thumbnail, "year" => $year, "category" => $category, "rating" => $rating, 'torrents' => $torrents
			);
			unset($highlight, $name, $thumbnail, $year, $category, $rating, $hash, $magnet, $quality, $codec, $torrents);
		}
		unset($json_response);

		return array_slice($results, 0, 8);
    }
}
?>
