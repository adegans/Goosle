<?php
/* ------------------------------------------------------------------------------------
*  Goosle - The fast, privacy oriented search tool that just works.
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
		$query = $this->search->query;

		// Safe search override
		if($this->search->safe == 0) {
			$safe = '0';
		} else {
			$safe = '';
		}

		// Size override
		$size = ''; // All sizes
		if(preg_match('/(size:)(small|medium|large|xlarge)/i', $this->search->query_terms[0], $matches)) {
			$size = $matches[1];
			$query = str_replace($this->search->query_terms[0], '', $query);

			// Engine specific
			if($size == 'xlarge') $size = 'wallpaper';
		}
		unset($matches);

        $url = 'https://images.search.yahoo.com/search/images?'.http_build_query(array(
        	'p' => $query, // Search query
        	'imgsz' => $size, // Image size (small|medium|large|wallpaper)
        	'safe' => $safe // Safe search filter (0 = off, "" = on)
        ));

        unset($query, $size, $safe);

        return $url;
	}

    public function get_request_headers() {
		return array(
			'Accept' => 'text/html, application/xhtml+xml, application/xml;q=0.8, */*;q=0.7',
		);
	}

	public function parse_results($response) {
		$engine_temp = $engine_result = array();
		$xpath = get_xpath($response);
	
 		// No response
		if(!$xpath) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, 'No response', 0);
			return $engine_result;
		}

		// Scrape the results
//		$scrape = $xpath->query("//li[contains(@class, 'ld') and not(contains(@class, 'slotting'))][position() < 101]");
		$scrape = $xpath->query("//li[contains(@class, 'ld') and not(contains(@class, 'ignore'))][position() < 101]");

		// Figure out results and base rank
		$number_of_results = $rank = count($scrape);

		// No results
        if($number_of_results == 0) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, 'No results', 0);	        
	        return $engine_result;
	    }

		// Scrape recommended
		$didyoumean = $xpath->query(".//section[@class='dym-c']/section/h3/a")[0];
		if(!is_null($didyoumean)) {
			$engine_result['did_you_mean'] = $didyoumean->textContent;
		}
        $search_specific = $xpath->query(".//section[@class='dym-c']/section/h5/a")[0];
        if(!is_null($search_specific)) {
			$engine_result['search_specific'] = $search_specific->textContent;
        }

        foreach($scrape as $result) {
			// Find data
			$image_thumb = $xpath->evaluate(".//img/@src", $result);
			$url_data = $xpath->evaluate(".//a/@href", $result);

			// Skip broken results
			if($image_thumb->length == 0) continue;
			if($url_data->length == 0) continue;
			
			// Get and prepare meta data
			// -- Relevant $url_data (there is more, but unused by Goosle)
			// w = Image width (1280)
			// h = Image height (720)
			// imgurl = Actual full size image (Used in Yahoo preview/popup)
			// rurl = Url to page where the image is used
			// size = Image size (413.1KB)
			// tt = Website title (Used for image alt text)
			foreach(explode('&', strstr($url_data[0]->textContent, '?')) as &$meta) {
				if(!empty($meta)) {
					$value = explode('=', trim($meta));

					if(!empty($value[0]) && !empty($value[1])) {
						$usable_data[$value[0]] = urldecode($value[1]);
					}
				}
				unset($meta, $value);
			}

			// Skip broken results
			if(!array_key_exists('imgurl', $usable_data)) continue;			
			if(!array_key_exists('rurl', $usable_data)) continue;			

			// Process data
			$image_full = (array_key_exists('imgurl', $usable_data)) ? sanitize($usable_data['imgurl']) : null;
			$image_thumb = sanitize($image_thumb[0]->textContent);
			$url = sanitize($usable_data['rurl']);
			$alt = (array_key_exists('tt', $usable_data)) ? sanitize($usable_data['tt']) : null;
			$dimensions_w = (array_key_exists('w', $usable_data)) ? sanitize($usable_data['w']) : null;
			$dimensions_h = (array_key_exists('h', $usable_data)) ? sanitize($usable_data['h']) : null;
			$filesize = (array_key_exists('size', $usable_data)) ? intval(preg_replace('/[^0-9]+/', '', sanitize($usable_data['size']))) : null;

			// Fix incomplete image url
			if(!is_null($image_full)) {
				$is_https = parse_url($url);
				if($is_https['scheme'] == 'https') {
					$image_full = 'https://'.$image_full;
				} else if($is_https['scheme'] == 'http') {
					$image_full = 'http://'.$image_full;
				} else {
					$image_full = '//'.$image_full;
				}
			}

			// Skip duplicate IMAGE urls/results
            if(!empty($engine_temp)) {
                if(in_array($image_full, array_column($engine_temp, 'image_full'))) continue;
            }

			$engine_temp[] = array (
				// Required
				'image_full' => $image_full, // string
				'image_thumb' => $image_thumb, // string
				'url' => $url, // string
				'engine_rank' => $rank, // int
				// Optional
				'alt' => $alt, // string | null
				'width' => $dimensions_w, // int | null
				'height' => $dimensions_h, // int | null
				'filesize' => $filesize, // int | null
			);
			$rank -= 1;
		}

		// Base info
		if(!empty($engine_temp)) {
			$engine_result['source'] = 'Yahoo Images';
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, $number_of_results, count($engine_temp));

		unset($response, $xpath, $scrape, $number_of_results, $rank);

		return $engine_result;
	}
}
?>