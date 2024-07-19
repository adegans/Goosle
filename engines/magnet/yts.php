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
class YTSRequest extends EngineRequest {
	public function get_request_url() {
 		$url = 'https://yts.mx/api/v2/list_movies.json?query_term='.urlencode($this->search->query);
        
        return $url;
	}

    public function get_request_headers() {
		return array(
			'Accept' => 'application/json, */*;q=0.8',
			'Accept-Language' => null,
			'Accept-Encoding' => null,
			'Sec-Fetch-Dest' => null,
			'Sec-Fetch-Mode' => null,
			'Sec-Fetch-Site' => null
		);
	}

	public function parse_results($response) {
		$engine_temp = $engine_result = array();
		$json_response = json_decode($response, true);
		
		// No response
		if(empty($json_response)) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No response', 0);
			return $engine_result;
		}

		// No results
        if($json_response['data']['movie_count'] == 0) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No results', 0);	        
	        return $engine_result;
	    }

		foreach($json_response['data']['movies'] as $result) {
			// Find and process data
			$title = sanitize($result['title']);

			// Find extra data
			$runtime = (!empty($result['runtime'])) ? date('H:i', mktime(0, sanitize($result['runtime']))) : null;
			$year = (!empty($result['year'])) ? sanitize($result['year']) : 0;
			$category = (!empty($result['genres'])) ? $result['genres'] : null;
			$mpa_rating = (!empty($result['mpa_rating'])) ? sanitize($result['mpa_rating']) : null;
			$timestamp = (!empty($result['date_uploaded_unix'])) ? sanitize($result['date_uploaded_unix']) : null;
			$language = (!empty($result['language'])) ? sanitize($result['language']) : null;
			$url = (!empty($result['url'])) ? sanitize($result['url']) : null;
			
			// Process extra data
			if(is_array($category)) {
				// Block these categories
				if(count(array_uintersect($category, $this->opts->yts_categories_blocked, 'strcasecmp')) > 0) continue;
				
				// Set actual category
				$category = sanitize(implode(', ', $category));
			}

			foreach ($result['torrents'] as $download) {
				// Find and process data
				$hash = strtolower(sanitize($download['hash']));
				$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($title).'&tr='.implode('&tr=', $this->opts->magnet_trackers);
				$seeders = sanitize($download['seeds']);
				$leechers = sanitize($download['peers']);
				$filesize = human_filesize(filesize_to_bytes(sanitize($download['size'])));
				
				// Ignore results with 0 seeders?
				if($this->opts->show_zero_seeders == 'off' AND $seeders == 0) continue;
				
				// Find extra data
				$quality = (!empty($download['quality'])) ? sanitize(strtolower($download['quality'])) : null;
				$codec = (!empty($download['video_codec'])) ? sanitize(strtolower($download['video_codec'])) : null;
				$bitrate = (!empty($download['bit_depth'])) ? sanitize($download['bit_depth']) : null;
				$type = (!empty($download['type'])) ? ucfirst(sanitize(strtolower($download['type']))) : null;
				$audio = (!empty($download['audio_channels'])) ? sanitize('AAC '.$download['audio_channels']) : null;

				// Process extra data
				if(!empty($codec)) $quality = $quality.' '.$codec;
				if(!empty($bitrate)) $quality = $quality.' '.$bitrate.'bit';

				$engine_temp[] = array (
					// Required
					'hash' => $hash, // string
					'title' => $title, // string
					'magnet' => $magnet, // string
					'seeders' => $seeders, // int
					'leechers' => $leechers, // int
					'filesize' => $filesize, // int
					// Optional
					'nsfw' => false, // bool
					'quality' => $quality, // string|null
					'type' => $type, // string|null
					'audio' => $audio, // string|null
					'runtime' => $runtime, // int(timestamp)|null
					'year' => $year, // int(4)|null
					'timestamp' => $timestamp, // int(timestamp)|null
					'category' => $category, // string|null
					'mpa_rating' => $mpa_rating, // string|null
					'language' => $language, // string|null
					'url' => $url // string|null
				);
				unset($download, $hash, $magnet, $seeders, $leechers, $filesize, $quality, $codec, $bitrate, $type, $audio);
			}
			unset($result, $title, $year, $category, $language, $runtime, $url, $date_added);
		}

		// Base info
		if(!empty($engine_temp)) {
			$engine_result['source'] = 'yts.mx';
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, $json_response['data']['movie_count'], count($engine_temp));

		unset($response, $json_response, $engine_temp);

		return $engine_result;
	}
}
?>