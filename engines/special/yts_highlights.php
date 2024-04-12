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
		$results = array();
		$response = curl_multi_getcontent($this->ch);
		$json_response = json_decode($response, true);
		
		// No response
		if(empty($json_response)) return $results;
		
		// Nothing found
		if($json_response['status'] != "ok" || $json_response['data']['movie_count'] == 0) return $results;
		
		// Use API result
		foreach ($json_response['data']['movies'] as $result) {
			// Prevent gaps
			if(!array_key_exists("year", $result)) $result['year'] = "0000";
			if(!array_key_exists("genres", $result)) $result['genres'] = array();
			if(!array_key_exists("rating", $result)) $result['rating'] = "0";

			// Block these categories
			if(count(array_uintersect($result['genres'], $this->opts->yts_categories_blocked, "strcasecmp")) > 0) continue;
			
			$name = sanitize($result['title']);
			$thumbnail = sanitize($result['medium_cover_image']);
			$year = sanitize($result['year']);
			$category = sanitize(implode(', ', array_slice($result['genres'], 0, 2)));
			$url = sanitize($result['url']);
			$rating = sanitize($result['rating']);

			foreach($result['torrents'] as $download) {
				$hash = sanitize($download['hash']);
				$magnet = "magnet:?xt=urn:btih:".$hash."&dn=".urlencode($name)."&tr=".implode("&tr=", $this->opts->magnet_trackers);
				$quality = sanitize($download['quality']);
				$codec = sanitize($download['video_codec']);
			
				$downloads[] = array (
					"magnet" => $magnet, "quality" => $quality, "codec" => $codec
				);
			}

			$results[] = array (
				"name" => $name, "thumbnail" => $thumbnail, "year" => $year, "category" => $category, "rating" => $rating, "magnet_links" => $downloads, "url" => $url
			);
			unset($result, $name, $thumbnail, $year, $category, $rating, $hash, $download, $quality, $codec, $downloads, $url);
		}
		unset($json_response);

		return array_slice($results, 0, 8);
    }
}
?>
