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
class OpenverseRequest extends EngineRequest {
	public function get_request_url() {

	    $query_terms = substr($this->query, 0, 200);

		// Safe search override
		$safe = "0"; // No mature results
		if(strpos($query_terms[0], "safe") !== false) {
			$switch = explode(":", $query_terms[0]);

			if(!is_numeric($switch[1])) {
				$safe = (strtolower($switch[1]) == "off") ? "1" : "0";
				$this->query = implode(" ", array_slice($query_terms, 1));
			}
		}

		// q = query
		// format = json
		// mature = 1 / 0
		// page_size = 80 (int)

		$args = array("q" => $query_terms, "format" => "json", "mature" => $safe, "page_size" => 50);
        $url = "https://api.openverse.org/v1/images/?".http_build_query($args);

        unset($query_terms, $switch, $safe, $max_results, $args);

        return $url;
	}

    public function get_request_headers() {
		$token_file = ABSPATH.'cache/token.data';
		$token = unserialize(file_get_contents($token_file));
		
		return array(
			'Accept' => 'application/json, */*;q=0.8',
			'Content-type' => 'application/x-www-form-urlencoded',
			'Authorization' => 'Bearer '.$token['openverse']['access_token'],
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
		
		// Set base rank and result amound
		$rank = $results['amount'] = count($json_response['results']);

		// Nothing found
		if($results['amount'] == 0) return $results;

		// Use API result
		foreach ($json_response['results'] as $result) {
			// Deal with optional or missing data
			$dimensions_w = (!empty($result['width'])) ? sanitize($result['width']) : "";
			$dimensions_h = (!empty($result['height'])) ? sanitize($result['height']) : "";
			$filesize = (!empty($result['filesize'])) ? sanitize($result['filesize']) : "";
			$link = (!empty($result['url'])) ? sanitize($result['url']) : "";

			$image_full = (!empty($result['foreign_landing_url'])) ? sanitize($result['foreign_landing_url']) : "";
			$image_thumb = (!empty($result['thumbnail'])) ? sanitize($result['thumbnail']) : $image_full;
			$alt = (!empty($result['title'])) ? sanitize($result['title']) : "";

			// Add attribution to alt text?
			$creator = (!empty($result['creator'])) ? " by ".sanitize($result['creator']) : "";
			$alt = (!empty($creator)) ? $alt.$creator : $alt;

			// Process result
			$filesize = intval(preg_replace('/[^0-9]/', '', $filesize));

			// filter duplicate IMAGE urls/results
            if(!empty($results['search'])) {
                if(in_array($image_full, array_column($results['search'], "image_full"))) continue;
            }

			$results['search'][] = array ("id" => uniqid(rand(0, 9999)), "source" => "Openverse", "image_thumb" => $image_thumb, "alt" => $alt, "image_full" => $image_full, "width" => $dimensions_w, "height" => $dimensions_h, "filesize" => $filesize, "webpage_url" => $link, "engine_rank" => $rank);
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
