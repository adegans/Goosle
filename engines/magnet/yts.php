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
        $url = "https://yts.mx/api/v2/list_movies.json?".http_build_query(array("query_term" => $this->query));

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
			if(!array_key_exists("year", $result)) $result['year'] = 0;
			if(!array_key_exists("genres", $result)) $result['genres'] = array();
			if(!array_key_exists("runtime", $result)) $result['runtime'] = 0;
			if(!array_key_exists("url", $result)) $result['url'] = '';
			
			// Block these categories
			if(count(array_uintersect($result['genres'], $this->opts->yts_categories_blocked, "strcasecmp")) > 0) continue;
			
			$name = sanitize($result['title']);
			
			// Get extra data
			$year = sanitize($result['year']);
			$category = sanitize(implode(', ', $result['genres']));
			$runtime = sanitize($result['runtime']);
			$url = sanitize($result['url']);
			$date_added = sanitize($result['date_uploaded_unix']);

			foreach ($result['torrents'] as $download) {
				$hash = strtolower(sanitize($download['hash']));
				$magnet = "magnet:?xt=urn:btih:".$hash."&dn=".urlencode($name)."&tr=".implode("&tr=", $this->opts->magnet_trackers);
				$seeders = sanitize($download['seeds']);
				$leechers = sanitize($download['peers']);
				$size = sanitize($download['size']);
				
				// Ignore results with 0 seeders?
				if($this->opts->show_zero_seeders == "off" AND $seeders == 0) continue;
				
				// Get extra data
				$quality = sanitize($download['quality']);
				$codec = sanitize($download['video_codec']);
				$id = uniqid(rand(0, 9999));
			
				$results[] = array (
					// Required
					"id" => $id, "source" => "yts.mx", "name" => $name, "magnet" => $magnet, "hash" => $hash, "seeders" => $seeders, "leechers" => $leechers, "size" => $size,
					// Extra
					"quality" => $quality, "codec" => $codec, "year" => $year, "category" => $category, "runtime" => $runtime, "url" => $url, "date_added" => $date_added
				);
			}
		}
		unset($json_response);

		return $results;
	}
}
?>
