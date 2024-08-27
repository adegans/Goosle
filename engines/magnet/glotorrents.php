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
class GlodlsRequest extends EngineRequest {
	public function get_request_url() {
		// Alternative: https://gtso.cc
		$url = 'https://glodls.to/search_results.php?search='.urlencode($this->search->query);

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
		if(!$xpath) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, 'No response', 0);
			return $engine_result;
		}

		// Scrape the results
		$scrape = $xpath->query('//div[@class="myBlock-con"]/table//tr');

		// No results
        if(count($scrape) == 0) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, 'No results', 0);
	        return $engine_result;
	    }

		$categories = array(
			1 => 'Movies',
			5 => 'Android',
			10 => 'Games',
			18 => 'Software/Apps',
			22 => 'Music',
			28 => 'Anime',
			33 => 'Other',
			41 => 'TV',
			51 => 'Books',
			52 => 'Mobile Apps/Games',
			54 => 'Windows',
			55 => 'Macintosh',
			70 => 'Pictures',
			71 => 'Video',
			72 => 'TV/Movie Packs',
			74 => 'Tutorials',
			75 => 'FLAC',
			76 => 'Sports'
		);

		foreach($scrape as $result) {
			// Find data
			$title = $xpath->evaluate(".//td[2]//a[2]/@title", $result);
			$magnet = $xpath->evaluate(".//td[4]/a/@href", $result);
			$seeders = $xpath->evaluate(".//td[6]//b", $result);
			$leechers = $xpath->evaluate(".//td[7]//b", $result);
			$filesize = $xpath->evaluate(".//td[5]", $result);

			// Skip broken results
			if($title->length == 0) continue;
			if($magnet->length == 0) continue;

			// Process data
			$title = sanitize($title[0]->textContent);
			$magnet = sanitize($magnet[0]->textContent);
			parse_str(parse_url($magnet, PHP_URL_QUERY), $hash_parameters);
			$hash = strtolower(str_replace('urn:btih:', '', $hash_parameters['xt']));
			$seeders = ($seeders->length > 0) ? sanitize($seeders[0]->textContent) : 0;
			$leechers = ($leechers->length > 0) ? sanitize($leechers[0]->textContent) : 0;
			$filesize = ($filesize->length > 0) ? filesize_to_bytes(sanitize($filesize[0]->textContent)) : 0;

			// Ignore results with 0 seeders?
			if($this->opts->show_zero_seeders == 'off' AND $seeders == 0) continue;

			// Throw out mismatched tv-show episodes when searching for tv shows
			if(!is_season_or_episode($this->search->query, $title)) continue;

			// Find extra data
			$category = $xpath->evaluate(".//td[1]/a/@href", $result);
			$url = $xpath->evaluate(".//td[2]//a[2]/@href", $result);

			// Process extra data
			if($category->length > 0) {
				$category = str_replace('/search.php?cat=', '', sanitize($category[0]->textContent));
				$category = (preg_match('/[0-9]+/', $category, $category)) ? $category[0] : null;
			} else {
				$category = null;
			}
			$url = ($url->length > 0) ? 'https://glodls.to'.sanitize($url[0]->textContent) : null;

			// Find meta data for certain categories
			if(!is_null($category)) {
				$nsfw = (detect_nsfw($title)) ? true : false;
				$quality = $codec = $audio = null;
				if($category == 1 || $category == 28 || $category == 41 ||  $category == 71 ||  $category == 72 ||  $category == 74) {
					$quality = find_video_quality($title);
					$codec = find_video_codec($title);
					$audio = find_audio_codec($title);

					// Add codec to quality
					if(!empty($codec)) $quality = $quality.' '.$codec;
				} else if($category == 22 || $category == 75) {
					$audio = find_audio_codec($title);
				}

				// Set actual category
				$category = $categories[$category];
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
				'verified_uploader' => null, // string|null
				'nsfw' => $nsfw, // bool
				'quality' => $quality, // string|null
				'type' => null, // string|null
				'audio' => $audio, // string|null
				'runtime' => null, // int(timestamp)|null
				'year' => null, // int(4)|null
				'timestamp' => null, // int(timestamp)|null
				'category' => $category, // string|null
				'mpa_rating' => null, // string|null
				'language' => null, // string|null
				'url' => $url // string|null
			);

			unset($result, $title, $hash, $magnet, $seeders, $leechers, $filesize, $quality, $codec, $audio, $category, $url);
		}

		// Base info
		if(!empty($engine_temp)) {
			$engine_result['source'] = 'glodls.to';
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, count($scrape), count($engine_temp));

		unset($response, $xpath, $scrape, $engine_temp);

		return $engine_result;
	}
}
?>
