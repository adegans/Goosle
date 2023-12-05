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
        return "https://yts.mx/api/v2/list_movies.json?query_term=".urlencode($this->query);
    }

    public function parse_results($response) {
        $results = array();
        $response = curl_multi_getcontent($this->ch);
        $json_response = json_decode($response, true);

		// No response
        if(empty($json_response)) return $results;

		// Nothing found
        if($json_response["status"] != "ok" || $json_response["data"]["movie_count"] == 0) return $results;

		// Use API result
        foreach ($json_response["data"]["movies"] as $movie) {
			// Prevent gaps
            if(!array_key_exists("year", $movie)) $movie['year'] = 0;
            if(!array_key_exists("genres", $movie)) $movie['genres'] = array();
            if(!array_key_exists("runtime", $movie)) $movie['runtime'] = 0;
            if(!array_key_exists("url", $movie)) $movie['url'] = '';

            // Block these categories
           	if(array_intersect($movie["genres"], $this->opts->yts_categories_blocked)) continue;

            $name = sanitize($movie["title"]);

            foreach ($movie["torrents"] as $torrent) {
                $magnet = "magnet:?xt=urn:btih:".sanitize($torrent["hash"])."&dn=".urlencode($name)."&tr=".implode("&tr=", $this->opts->torrent_trackers);

                array_push($results, array (
	                // Required
					"source" => "yts.mx",
					"name" => $name,
					"magnet" => $magnet,
					"seeders" => sanitize($torrent["seeds"]),
					"leechers" => sanitize($torrent["peers"]),
					"size" => sanitize($torrent["size"]),
					// Optional
					"quality" => sanitize($torrent["quality"]),
					"year" => sanitize($movie["year"]),
					"category" => sanitize(implode(', ', $movie["genres"])),
					"runtime" => sanitize($movie["runtime"]),
					"url" => sanitize($movie["url"]),
					"date_added" => sanitize($movie["date_uploaded_unix"])
				));
            }
        }

		return $results;
	}
}
?>
