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
class QwantImageRequest extends EngineRequest {
	public function get_request_url() {
		// Split the query
	    $query_terms = explode(" ", strtolower($this->query));

		// Safe search override
		$safe = "1"; // Moderate results
		if(strpos($query_terms[0], "safe") !== false) {
			$switch = explode(":", $query_terms[0]);

			if(!is_numeric($switch[1])) {
				$safe = (strtolower($switch[1]) == "off") ? "0" : "2";
				$this->query = implode(" ", array_slice($query_terms, 1));
			}
		}

		$language = (strlen($this->opts->qwant_language) > 0 && strlen($this->opts->qwant_language < 6)) ? $this->opts->qwant_language : "en_gb";

		// q = query
		// t = Type of search, Images
		// count = Up-to how many images to return (Max 50)
		// locale = In which language should the search be done
		// device = What kind of device are we searching from?
		// safesearch = Safe search filter (0 = off, 1 = normal, 2 = strict)

		$args = array("q" => $this->query, "t" => 'images', 'count' => 50, 'locale' => $language, 'device' => 'desktop', 'safesearch' => $safe);
        $url = "https://api.qwant.com/v3/search/images?".http_build_query($args);

        unset($query_terms, $switch, $safe, $language, $args);

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
        if($json_response["status"] != "success") return $results;

		// Set base rank and result amound
		$rank = $results['amount'] = $json_response["data"]["result"]["total"];

		// Use API result
		foreach ($json_response["data"]["result"]["items"] as $result) {
			// Deal with optional or missing data
			$dimensions_w = (!empty($result['width'])) ? sanitize($result['width']) : "";
			$dimensions_h = (!empty($result['height'])) ? sanitize($result['height']) : "";
			$filesize = (!empty($result['size'])) ? sanitize($result['size']) : "";
			$link = (!empty($result['url'])) ? sanitize($result['url']) : "";

			$image_full = (!empty($result['media'])) ? sanitize($result['media']) : "";
			$image_thumb = (!empty($result['thumbnail'])) ? sanitize($result['thumbnail']) : $image_full;
			$alt = (!empty($result['title'])) ? sanitize($result['title']) : "";

			// Process result
			$filesize = intval(preg_replace('/[^0-9]/', '', $filesize));

			// filter duplicate IMAGE urls/results
            if(!empty($results['search'])) {
                if(in_array($image_full, array_column($results['search'], "image_full"))) continue;
            }

			$results['search'][] = array ("id" => uniqid(rand(0, 9999)), "source" => "Qwant", "image_thumb" => $image_thumb, "alt" => $alt, "image_full" => $image_full, "width" => $dimensions_w, "height" => $dimensions_h, "filesize" => $filesize, "webpage_url" => $link, "engine_rank" => $rank);
			$rank -= 1;
			unset($url_data, $usable_data, $dimensions_w, $dimensions_h, $filesize, $link, $image_full, $alt, $image_thumb);
		}
		unset($json_response, $rank);

		// Add error if there are no search results
		if(empty($results['search'])) {
			$results['error'] = array(
				"message" => "No results found. Please try with less or different keywords!"
			);
		}
		
		return $results;
	}
}
?>