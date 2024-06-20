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
class NyaaRequest extends EngineRequest {
	public function get_request_url() {
		$query = str_replace('%22', '\"', $this->query);

		// Is there no query left? Bail!
		if(empty($query)) return false;

        $url = 'https://nyaa.si/?q='.urlencode($query);
        
        unset($query);
        
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
		if(!$xpath) return $engine_temp;
		
		// Scrape the results
		$scrape = $xpath->query("//tbody/tr");

		// No results
        if(count($scrape) == 0) return $engine_temp;

		foreach($scrape as $result) {
			// Find data
			$meta = $xpath->evaluate(".//td[@class='text-center']", $result);
			$name = $xpath->evaluate(".//td[@colspan='2']//a[not(contains(@class, 'comments'))]/@title", $result);
			$magnet = $xpath->evaluate(".//a[2]/@href", $meta[0]);

			// Skip broken results
			if($name->length == 0) continue;
			if($magnet->length == 0) $magnet = $xpath->evaluate(".//a/@href", $meta[0]); // This matches if no torrent file is provided on the page
			if($magnet->length == 0) continue;

			// Process data
			$name = sanitize($name[0]->textContent);
			$magnet = sanitize($magnet[0]->textContent);
			parse_str(parse_url($magnet, PHP_URL_QUERY), $hash_parameters);
			$hash = strtolower(str_replace('urn:btih:', '', $hash_parameters['xt']));
			$seeders = sanitize($meta[3]->textContent);
			$leechers = sanitize($meta[4]->textContent);
			$filesize =  human_filesize(filesize_to_bytes(str_replace('TiB', 'TB', str_replace('GiB', 'GB', str_replace('MiB', 'MB', str_replace('KiB', 'KB', sanitize($meta[1]->textContent)))))));

			// Ignore results with 0 seeders?
			if($this->opts->show_zero_seeders == 'off' AND $seeders == 0) continue;
			
			// Throw out mismatched tv-show episodes when searching for tv shows
			if(!is_season_or_episode($this->query, $name)) continue;
			
			// Find extra data
			$category = $xpath->evaluate(".//td[1]//a/@title", $result);
			$url = $xpath->evaluate(".//td[@colspan='2']//a[not(contains(@class, 'comments'))]/@href", $result);

			// Process extra data
			$category = ($category->length > 0) ? str_replace(' - ', '/', sanitize($category[0]->textContent)) : null;
			$url = ($url->length > 0) ? 'https://nyaa.si'.sanitize($url[0]->textContent) : null;
			$date_added = explode('-', substr(sanitize($meta[2]->textContent), 0, 10));
			$date_added = timezone_offset(gmmktime(0, 0, 0, intval($date_added[1]), intval($date_added[2]), intval($date_added[0])), $this->opts->timezone);

			$quality = $codec = $audio = null;
			if(in_array(strtolower($category), array('anime/anime music video', 'anime/non-english-translated', 'anime/english-translated', 'anime/raw', 'live action/english-translated', 'live action/non-english-translated', 'live action/idol/promotional video', 'live action/raw'))) {
				$quality = find_video_quality($name);
				$codec = find_video_codec($name);

				// Add codec to quality
				if(!empty($codec)) $quality = $quality.' '.$codec;
			}

			if(in_array(strtolower($category), array('audio/lossless', 'audio/lossy', 'anime/anime music video', 'anime/non-english-translated', 'anime/english-translated', 'anime/raw', 'live action/english-translated', 'live action/non-english-translated', 'live action/idol/promotional video', 'live action/raw'))) {
				$audio = find_audio_codec($name);
			}

			$engine_temp[] = array (
				// Required
				'hash' => $hash, // string
				'name' => $name, // string
				'magnet' => $magnet, // string
				'seeders' => $seeders, // int
				'leechers' => $leechers, // int
				'filesize' => $filesize, // int
				// Optional
				'quality' => $quality, // string|null
				'type' => null, // string|null
				'audio' => $audio, // string|null
				'runtime' => null, // int(timestamp)|null
				'year' => null, // int(4)|null
				'date_added' => $date_added, // int(timestamp)|null
				'category' => $category, // string|null
				'url' => $url // string|null
			);

			unset($result, $name, $hash, $magnet, $seeders, $leechers, $filesize, $quality, $codec, $audio, $category, $url, $date_added);
		}

		// Base info
		$number_of_results = count($engine_temp);
		if($number_of_results > 0) {
			$engine_result['source'] = 'nyaa.si';
			$engine_result['amount'] = $number_of_results;
			$engine_result['search'] = $engine_temp;
		}

		unset($response, $xpath, $scrape, $number_of_results, $engine_temp);

		return $engine_result;
	}
}
?>
