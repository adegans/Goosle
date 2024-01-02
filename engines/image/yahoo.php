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
class YahooImageRequest extends EngineRequest {
	public function get_request_url() {
		// Split the query
	    $query_terms = explode(" ", strtolower($this->query));

		// Size override
		$size = "";
		if($query_terms[0] == 'size') {
			$switch = explode(":", $query_terms[0]);

			if((strlen($switch[1]) >= 3 && strlen($switch[1]) <= 6) && !is_numeric($switch[1])) {
				if($switch[1] == "med") $switch[1] = "medium";
				if($switch[1] == "lrg") $switch[1] = "large";
				if($switch[1] == "xlrg") $switch[1] = "wallpaper";
	
				if($switch[1] == "small" || $switch[1] == "medium" || $switch[1] == "large" || $switch[1] == "wallpaper") {
					$size = $switch[1];
				}
				
				$this->query = implode(" ", array_slice($query_terms, 1));
			}
		}

		// p = query
		// imgsz = Image size (small|medium|large|wallpaper)

		$args = array("p" => $this->query, "imgsz" => $size);
        $url = "https://images.search.yahoo.com/search/images?".http_build_query($args);

        unset($query_terms, $switch, $args, $size);

        return $url;
	}

	public function parse_results($response) {
		$results = array();
		$xpath = get_xpath($response);
	
        if(!$xpath) return array();

		// Scrape recommended
		$didyoumean = $xpath->query(".//section[@class='dym-c']/section/h3/a")[0];
		if(!is_null($didyoumean)) {
			$results['did_you_mean'] = $didyoumean->textContent;
		}
        $search_specific = $xpath->query(".//section[@class='dym-c']/section/h5/a")[0];
        if(!is_null($search_specific)) {
			$results['search_specific'] = $search_specific->textContent;
        }
		
		// Scrape the results
		$scrape = $xpath->query("//li[contains(@class, 'ld') and not(contains(@class, 'slotting'))][position() < 101]");
		$rank = $results['amount'] = count($scrape);
        foreach($scrape as $result) {
 			$image = $xpath->evaluate(".//img/@src", $result)[0];
            if($image == null) continue;

 			$url_data = $xpath->evaluate(".//a/@href", $result)[0];
            if($url_data == null) continue;

 			// Get meta data
 			// -- Relevant $url_data (there is more, but unused by Goosle)
			// w = Image width (1280)
			// h = Image height (720)
			// imgurl = Actual full size image (Used in Yahoo preview/popup)
			// rurl = Url to page where the image is used
			// size = Image size (413.1KB)
			// tt = Website title (Used for image alt text)
			parse_str($url_data->textContent, $url_data);

			// Deal with optional or missing data
			$dimensions_w = (!array_key_exists('w', $url_data) || empty($url_data['w'])) ? "" : htmlspecialchars($url_data['w']);
			$dimensions_h = (!array_key_exists('h', $url_data) || empty($url_data['h'])) ? "" : htmlspecialchars($url_data['h']);
			$filesize = (!array_key_exists('size', $url_data) || empty($url_data['size'])) ? "" : htmlspecialchars($url_data['size']);
			$link = (!array_key_exists('imgurl', $url_data) || empty($url_data['imgurl'])) ? "" : "//".htmlspecialchars($url_data['imgurl']);

			// Process result
			$image = htmlspecialchars($image->textContent);
			$url = htmlspecialchars($url_data['rurl']);
			$alt = htmlspecialchars($url_data['tt']);

			// filter duplicate urls/results
            if(!empty($results['search'])) {
		        $result_urls = array_column($results['search'], "direct_link");
                if(in_array($link, $result_urls)) continue;
            }

			$id = uniqid(rand(0, 9999));

			$results['search'][] = array ("id" => $id, "source" => "Yahoo! Images", "image" => $image, "alt" => $alt, "url" => $url, "width" => $dimensions_w, "height" => $dimensions_h, "filesize" => $filesize, "direct_link" => $link, "engine_rank" => $rank);
			$rank -= 1;
		}

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
