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
		$query = str_replace('%22', '\"', $this->query);

		// Is there no query left? Bail!
		if(empty($query)) return false;

 		$url = 'https://yts.mx/api/v2/list_movies.json?query_term='.urlencode($query);
        
        unset($query);
        
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
		if(empty($json_response)) return $engine_temp;

		// No results
        if($json_response['data']['movie_count'] == 0) return $engine_temp;

		foreach($json_response['data']['movies'] as $result) {
			// Find and process data
			$name = sanitize($result['title']);

			// Find extra data
			$year = (array_key_exists('year', $result)) ? sanitize($result['year']) : null;
			$category = (array_key_exists('genres', $result)) ? $result['genres'] : null; // Sanitized later
			$runtime = (array_key_exists('runtime', $result)) ? date('H:i', mktime(0, sanitize($result['runtime']))) : null;
			$url = (array_key_exists('url', $result)) ? sanitize($result['url']) : null;
			$date_added = (array_key_exists('date_uploaded_unix', $result)) ? timezone_offset(sanitize($result['date_uploaded_unix']), $this->opts->timezone) : null;
			
			// Process extra data
			if(!is_null($category)) {
				// Block these categories
				if(count(array_uintersect($category, $this->opts->yts_categories_blocked, 'strcasecmp')) > 0) continue;
				
				// Set actual category
				$category = sanitize(implode(', ', $category));
			}

			foreach ($result['torrents'] as $download) {
				// Find and process data
				$hash = strtolower(sanitize($download['hash']));
				$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($name).'&tr='.implode('&tr=', $this->opts->magnet_trackers);
				$seeders = sanitize($download['seeds']);
				$leechers = sanitize($download['peers']);
				$filesize = human_filesize(filesize_to_bytes(sanitize($download['size'])));
				
				// Ignore results with 0 seeders?
				if($this->opts->show_zero_seeders == 'off' AND $seeders == 0) continue;
				
				// Find extra data
				$quality = (array_key_exists('quality', $download)) ? sanitize(strtolower($download['quality'])) : null;
				$codec = (array_key_exists('video_codec', $download)) ? sanitize(strtolower($download['video_codec'])) : null;
				$type = (array_key_exists('type', $download)) ? ucfirst(sanitize(strtolower($download['type']))) : null;
				$audio = (array_key_exists('audio_channels', $download)) ? sanitize('AAC '.$download['audio_channels']) : null;

				// Process extra data
				if(!empty($codec)) $quality = $quality.' '.$codec;

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
					'type' => $type, // string|null
					'audio' => $audio, // string|null
					'year' => $year, // int(4)|null
					'category' => $category, // string|null
					'runtime' => $runtime, // int(timestamp)|null
					'url' => $url, // string|null
					'date_added' => $date_added // int(timestamp)|null
				);
				unset($download, $hash, $magnet, $seeders, $leechers, $filesize, $quality, $codec, $type, $audio);
			}
			unset($result, $name, $year, $category, $runtime, $url, $date_added);
		}

		// Base info
		$number_of_results = count($engine_temp);
		if($number_of_results > 0) {
			$engine_result['source'] = 'yts.mx';
			$engine_result['amount'] = $number_of_results;
			$engine_result['search'] = $engine_temp;
		}

		unset($response, $json_response, $number_of_results, $engine_temp);

		return $engine_result;
	}
}
?>