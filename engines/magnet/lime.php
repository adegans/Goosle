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
class LimeRequest extends EngineRequest {
	public function get_request_url() {
		$query = preg_replace('/[^a-z0-9- ]+/', '', $this->search->query);
		$query = strtolower(str_replace(' ', '-', $query));

		$url = 'https://www.limetorrents.lol/search/all/'.$query.'/';
		
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
		$scrape = $xpath->query("//table[@class='table2']//tr[position() > 1]");

		// No results
        if(count($scrape) == 0) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, 'No results', 0);	        
	        return $engine_result;
	    }

		foreach($scrape as $result) {
			// Find data
			$title = $xpath->evaluate(".//td[@class='tdleft']//a[2]", $result);
			$hash = $xpath->evaluate(".//td[@class='tdleft']//a[1]/@href", $result);
			$seeders = $xpath->evaluate(".//td[@class='tdseed']", $result);
			$leechers = $xpath->evaluate(".//td[@class='tdleech']", $result);
			$filesize = $xpath->evaluate(".//td[@class='tdnormal'][2]", $result);

			// Skip broken results
			if($title->length == 0) continue;
			if($hash->length == 0) continue;

			// Process data
			$title = sanitize($title[0]->textContent);
			$hash = sanitize($hash[0]->textContent);
			$hash = explode('/', substr($hash, 0, strpos($hash, '.torrent?')));
			$hash = strtolower($hash[array_key_last($hash)]);
			$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($title).'&tr='.implode('&tr=', $this->opts->magnet_trackers);
			$seeders = ($seeders->length > 0) ? sanitize($seeders[0]->textContent) : 0;
			$leechers = ($leechers->length > 0) ? sanitize($leechers[0]->textContent) : 0;
			$filesize = ($filesize->length > 0) ? human_filesize(filesize_to_bytes(sanitize($filesize[0]->textContent))) : 0;

			// Ignore results with 0 seeders?
			if($this->opts->show_zero_seeders == 'off' AND $seeders == 0) continue;
			
			// Throw out mismatched tv-show episodes when searching for tv shows
			if(!is_season_or_episode($this->search->query, $title)) continue;
			
			// Find extra data
			$category = $xpath->evaluate(".//td[@class='tdnormal'][1]", $result);
			$url = $xpath->evaluate(".//td[@class='tdleft']//a[2]/@href", $result);

			// Process extra data
			if($category->length > 0) {
				$category = explode(' - ', sanitize($category[0]->textContent));
				$category = str_replace('in ', '', $category[array_key_last($category)]);
				$category = (preg_match('/[a-z0-9 -]+/i', $category, $category)) ? $category[0] : null;
			} else {
				$category = null;
			}
			$url = ($url->length > 0) ? 'https://www.limetorrents.lol'.sanitize($url[0]->textContent) : null;

			// Find meta data for certain categories
			$nsfw = (detect_nsfw($title)) ? true : false;
			$quality = $codec = $audio = null;
			if(in_array(strtolower($category), array('movies', 'tv shows', 'anime'))) {
				$quality = find_video_quality($title);
				$codec = find_video_codec($title);
				$audio = find_audio_codec($title);

				// Add codec to quality
				if(!empty($codec)) $quality = $quality.' '.$codec;
			} else if(in_array(strtolower($category), array('music'))) {
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
			$engine_result['source'] = 'limetorrents.lol';
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, count($scrape), count($engine_temp));

		unset($response, $xpath, $scrape, $engine_temp);

		return $engine_result;
	}
}
?>