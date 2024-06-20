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
class PirateBayRequest extends EngineRequest {
	public function get_request_url() {
		$query = str_replace('%22', '\"', $this->query);

		// Is there no query left? Bail!
		if(empty($query)) return false;

        $url = 'https://apibay.org/q.php?q='.urlencode($query);
        
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
        if($json_response[0]['name'] == 'No results returned') return $engine_temp;

		$categories = array(
			100 => 'Audio',
			101 => 'Music',
			102 => 'Audio Book',
			103 => 'Sound Clips',
			104 => 'Audio FLAC',
			199 => 'Audio Other',

			200 => 'Video',
			201 => 'Movie',
			202 => 'Movie DVDr',
			203 => 'Music Video',
			204 => 'Movie Clip',
			205 => 'TV Show',
			206 => 'Handheld',
			207 => 'HD Movie',
			208 => 'HD TV Show',
			209 => '3D Movie',
			210 => 'CAM/TS',
			211 => 'UHD/4K Movie',
			212 => 'UHD/4K TV Show',
			299 => 'Video Other',
			
			300 => 'Applications',
			301 => 'Apps Windows',
			302 => 'Apps Apple',
			303 => 'Apps Unix',
			304 => 'Apps Handheld',
			305 => 'Apps iOS',
			306 => 'Apps Android',
			399 => 'Apps Other OS',

			400 => 'Games',
			401 => 'Games PC',
			402 => 'Games Apple',
			403 => 'Games PSx',
			404 => 'Games XBOX360',
			405 => 'Games Wii',
			406 => 'Games Handheld',
			407 => 'Games iOS',
			408 => 'Games Android',
			499 => 'Games Other OS',
			
			500 => 'Porn',
			501 => 'Porn Movie',
			502 => 'Porn Movie DVDr',
			503 => 'Porn Pictures',
			504 => 'Porn Games',
			505 => 'Porn HD Movie',
			506 => 'Porn Movie Clip',
			507 => 'Porn UHD/4K Movie',
			599 => 'Porn Other',

			600 => 'Other',
			601 => 'Other E-Book',
			602 => 'Other Comic',
			603 => 'Other Pictures',
			604 => 'Other Covers',
			605 => 'Other Physibles',
			699 => 'Other Other'
		);

		foreach($json_response as $result) {
			// Find and process data
			$name = sanitize($result['name']);
			$hash = strtolower(sanitize($result['info_hash']));
			$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($name).'&tr='.implode('&tr=', $this->opts->magnet_trackers);
			$seeders = sanitize($result['seeders']);
			$leechers = sanitize($result['leechers']);
			$filesize = human_filesize(sanitize($result['size']));
			
			// Ignore results with 0 seeders?
			if($this->opts->show_zero_seeders == 'off' AND $seeders == 0) continue;
			
			// Throw out mismatched tv-show episodes when searching for tv shows
			if(!is_season_or_episode($this->query, $name)) continue;
			
			// Find extra data
			$category = (array_key_exists('category', $result)) ? sanitize($result['category']) : null;
			$url = (array_key_exists('id', $result)) ? 'https://thepiratebay.org/description.php?id='.sanitize($result['id']) : null;
			$date_added = (array_key_exists('added', $result)) ? timezone_offset(sanitize($result['added']), $this->opts->timezone) : null;

			// Process extra data
			if(!is_null($category)) {
				// Block these categories
				if(in_array($category, $this->opts->piratebay_categories_blocked)) continue;

				// Detect technical data
				$quality = $codec = $audio = null;
				if(($category >= 200 && $category < 300) || ($category >= 500 && $category < 600)) {
					$quality = find_video_quality($name);
					$codec = find_video_codec($name);
	
					// Add codec to quality
					if(!empty($codec)) $quality = $quality.' '.$codec;
				}
	
				if(($category >= 100 && $category < 200) || ($category >= 200 && $category < 300) || ($category >= 500 && $category < 600)) {
					$audio = find_audio_codec($name);
				}

				// Set actual category
				$category = $categories[$category];
			}

			$engine_temp[] = array(
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
			$engine_result['source'] = 'thepiratebay.org';
			$engine_result['amount'] = $number_of_results;
			$engine_result['search'] = $engine_temp;
		}

		unset($response, $json_response, $number_of_results, $engine_temp, $categories);

		return $engine_result;
	}
}
?>
