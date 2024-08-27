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
		// Safe search override
		if($this->search->safe == 0) {
			$safe = '0';
		} else {
			$safe = '';
		}

		// Size override
		$size = '';
		if($this->search->size == 1) $size = 'small';
		if($this->search->size == 2) $size = 'medium';
		if($this->search->size == 3) $size = 'large';
		if($this->search->size == 4) $size = 'wallpaper';

        $url = 'https://images.search.yahoo.com/search/images?'.http_build_query(array(
        	'p' => $this->search->query, // Search query
        	'imgsz' => $size, // Image size (small|medium|large|wallpaper)
        	'safe' => $safe // Safe search filter (0 = off, "" = on)
        ));

        unset($size, $safe);

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
		$didyoumean = $xpath->query("//section[@class='dym-c']/section/h3/a")[0];
		if(!is_null($didyoumean)) {
			$engine_result['did_you_mean'][] = $didyoumean->textContent;
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
			// w = Image width
			// h = Image height
			// imgurl = Full size image (Used in Yahoo preview/popup)
			// rurl = Url to page where the image is used
			// size = Image size
			// tt = Website title
			foreach(explode('&', strstr($url_data[0]->textContent, '?')) as &$meta) {
				if(!empty($meta)) {
					$value = explode('=', trim($meta));

					if(!empty($value[0]) && !empty($value[1])) {
						$usable_data[$value[0]] = urldecode($value[1]);
					}
				}
				unset($meta, $value);
			}

			// Process data
			$image_thumb = sanitize($image_thumb[0]->textContent);
			$image_full = (array_key_exists('imgurl', $usable_data)) ? sanitize($usable_data['imgurl']) : null;
			$url = (array_key_exists('rurl', $usable_data)) ? sanitize($usable_data['rurl']) : null;
			$alt = (array_key_exists('tt', $usable_data)) ? sanitize($usable_data['tt']) : null;

			// Skip broken results
			if(empty($image_full)) continue;
			if(empty($url)) continue;

			// Optional
			$dimensions_w = (array_key_exists('w', $usable_data)) ? sanitize($usable_data['w']) : null;
			$dimensions_h = (array_key_exists('h', $usable_data)) ? sanitize($usable_data['h']) : null;

			// Process data
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
				'image_thumb' => $image_thumb, // string
				'image_full' => $image_full, // string
				'url' => $url, // string
				'alt' => $alt, // string
				'engine_rank' => $rank, // int
				// Optional
				'width' => $dimensions_w, // int | null
				'height' => $dimensions_h // int | null
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
