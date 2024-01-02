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
class YTSRequest extends EngineRequest {
	public function get_request_url() {
		$args = array("query_term" => $this->query);
        $url = "https://yts.mx/api/v2/list_movies.json?".http_build_query($args);

        unset($args);

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
		foreach ($json_response['data']['movies'] as $movie) {
			// Prevent gaps
			if(!array_key_exists("year", $movie)) $movie['year'] = 0;
			if(!array_key_exists("genres", $movie)) $movie['genres'] = array();
			if(!array_key_exists("runtime", $movie)) $movie['runtime'] = 0;
			if(!array_key_exists("url", $movie)) $movie['url'] = '';
			
			// Block these categories
			if(array_intersect($movie['genres'], $this->opts->yts_categories_blocked)) continue;
			
			$name = sanitize($movie['title']);
			
			// Get extra data
			$year = sanitize($movie['year']);
			$category = sanitize(implode(', ', $movie['genres']));
			$runtime = sanitize($movie['runtime']);
			$url = sanitize($movie['url']);
			$date_added = sanitize($movie['date_uploaded_unix']);

			foreach ($movie['torrents'] as $torrent) {
				$magnet = "magnet:?xt=urn:btih:".sanitize($torrent['hash'])."&dn=".urlencode($name)."&tr=".implode("&tr=", $this->opts->torrent_trackers);
				$seeders = sanitize($torrent['seeds']);
				$leechers = sanitize($torrent['peers']);
				$size = sanitize($torrent['size']);
				
				// Ignore results with 0 seeders?
				if($this->opts->show_zero_seeders == "off" AND $seeders == 0) continue;
				
				// Get extra data
				$quality = sanitize($torrent['quality']);
				
				$results[] = array (
					// Required
					"source" => "yts.mx", "name" => $name, "magnet" => $magnet, "seeders" => $seeders, "leechers" => $leechers, "size" => $size,
					// Extra
					"quality" => $quality, "year" => $year, "category" => $category, "runtime" => $runtime, "url" => $url, "date_added" => $date_added
				);
			}

			unset($name, $magnet, $seeders, $leechers, $size, $quality, $year, $category, $runtime, $url, $date_added);
		}

		return $results;
	}
}
?>
