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
        $url = 'https://nyaa.si/?q='.urlencode($this->search->query);

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
		$scrape = $xpath->query("//tbody/tr");

		// No results
        if(count($scrape) == 0) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, 'No results', 0);
	        return $engine_result;
	    }

		foreach($scrape as $result) {
			// Find data
			$meta = $xpath->evaluate(".//td[@class='text-center']", $result);
			$title = $xpath->evaluate(".//td[@colspan='2']//a[not(contains(@class, 'comments'))]/@title", $result);
			$magnet = $xpath->evaluate(".//a[2]/@href", $meta[0]);

			// Skip broken results
			if($title->length == 0) continue;
			if($magnet->length == 0) $magnet = $xpath->evaluate(".//a/@href", $meta[0]); // This matches if no torrent file is provided
			if($magnet->length == 0) continue;

			// Process data
			$title = sanitize($title[0]->textContent);
			$magnet = sanitize($magnet[0]->textContent);
			parse_str(parse_url($magnet, PHP_URL_QUERY), $hash_parameters);
			$hash = strtolower(str_replace('urn:btih:', '', $hash_parameters['xt']));
			$seeders = sanitize($meta[3]->textContent);
			$leechers = sanitize($meta[4]->textContent);
			$filesize =  filesize_to_bytes(str_replace('TiB', 'TB', str_replace('GiB', 'GB', str_replace('MiB', 'MB', str_replace('KiB', 'KB', sanitize($meta[1]->textContent))))));

			// Ignore results with 0 seeders?
			if($this->opts->show_zero_seeders == 'off' AND $seeders == 0) continue;

			// Throw out mismatched tv-show episodes when searching for tv shows
			if(!is_season_or_episode($this->search->query, $title)) continue;

			// Find extra data
			$verified = $xpath->evaluate("./@class", $result);
			$category = $xpath->evaluate(".//td[1]//a/@title", $result);
			$url = $xpath->evaluate(".//td[@colspan='2']//a[not(contains(@class, 'comments'))]/@href", $result);
			$date_added = $xpath->evaluate(".//td[@class='text-center']/@data-timestamp", $result);

			// Process extra data
			$verified = ($verified->length > 0) ? sanitize($verified[0]->textContent) : null;
			if($verified == 'success') {
				$verified = 'yes';
			} else if($verified == 'danger') {
				$verified = 'no';
			} else {
				$verified = null;
			}

			$category = ($category->length > 0) ? str_replace(' - ', '/', sanitize($category[0]->textContent)) : null;
			$url = ($url->length > 0) ? 'https://nyaa.si'.sanitize($url[0]->textContent) : null;
			$timestamp = sanitize($date_added[0]->textContent);

			// Find meta data for certain categories
			$nsfw = (detect_nsfw($title)) ? true : false;
			$quality = $codec = $audio = null;
			if(in_array(strtolower($category), array('anime/anime music video', 'anime/non-english-translated', 'anime/english-translated', 'anime/raw', 'live action/english-translated', 'live action/non-english-translated', 'live action/idol/promotional video', 'live action/raw'))) {
				$quality = find_video_quality($title);
				$codec = find_video_codec($title);
				$audio = find_audio_codec($title);

				// Add codec to quality
				if(!empty($codec)) $quality = $quality.' '.$codec;
			} else if(in_array(strtolower($category), array('audio/lossless', 'audio/lossy'))) {
				$audio = find_audio_codec($title);
			}

			$engine_temp[] = array (
				// Required
				'hash' => $hash, // string
				'title' => $title, // string
				'magnet' => $magnet, // string
				'seeders' => $seeders, // int
				'leechers' => $leechers, // int
				'filesize' => $filesize, // int
				// Optional
				'verified_uploader' => $verified, // string|null
				'nsfw' => $nsfw, // bool
				'quality' => $quality, // string|null
				'type' => null, // string|null
				'audio' => $audio, // string|null
				'runtime' => null, // int(timestamp)|null
				'year' => null, // int(4)|null
				'timestamp' => $timestamp, // int(timestamp)|null
				'category' => $category, // string|null
				'mpa_rating' => null, // string|null
				'language' => null, // string|null
				'url' => $url // string|null
			);

			unset($result, $title, $hash, $magnet, $seeders, $leechers, $filesize, $quality, $codec, $audio, $category, $url, $date_added);
		}

		// Base info
		if(!empty($engine_temp)) {
			$engine_result['source'] = 'nyaa.si';
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, count($scrape), count($engine_temp));

		unset($response, $xpath, $scrape, $engine_temp);

		return $engine_result;
	}
}
?>
