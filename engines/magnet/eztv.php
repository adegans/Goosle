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
class EZTVRequest extends EngineRequest {
	public function get_request_url() {
		$query = preg_replace('/[^0-9]+/', '', $this->search->query);

		// Is eztvx.to blocked for you? Use one of these urls as an alternative
		// Try: eztv1.xyz, eztv.wf, eztv.tf, eztv.yt
		$url = 'https://eztvx.to/api/get-torrents?imdb_id='.urlencode($query);
		
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
		if(empty($json_response)) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No response', 0);
			return $engine_result;
		}

		// No results
        if($json_response['torrents_count'] == 0) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No results', 0);	        
	        return $engine_result;
	    }

		foreach($json_response['torrents'] as $result) {
			$title = sanitize($result['title']);
			$hash = strtolower(sanitize($result['hash']));
			$magnet = sanitize($result['magnet_url']);
			$seeders = sanitize($result['seeds']);
			$leechers = sanitize($result['peers']);
			$filesize = human_filesize(sanitize($result['size_bytes']));
			
			// Ignore results with 0 seeders?
			if($this->opts->show_zero_seeders == 'off' AND $seeders == 0) continue;

			// Clean up season and episode number
			$season = sanitize($result['season']);
			if($season < 10) $season = '0'.$season;
			$episode = sanitize($result['episode']);
			if($episode < 10) $episode = '0'.$episode;
			
			// Throw out mismatched episodes
			if(!is_season_or_episode($this->search->query, 'S'.$season.'E'.$episode)) continue;

			// Get extra data
			$timestamp = (isset($result['date_released_unix'])) ? sanitize($result['date_released_unix']) : null;
			$quality = find_video_quality($title);
			$codec = find_video_codec($title);
			$audio = find_audio_codec($title);
	
			// Add codec to quality
			if(!empty($codec)) $quality = $quality.' '.$codec;

			// Clean up show name
			$title = (preg_match('/.+?(?=[0-9]{3,4}p)|xvid|divx|(x|h)26(4|5)/i', $title, $clean_name)) ? $clean_name[0] : $title; // Break off show name before video resolution
			$title = str_replace(array('S0E0', 'S00E00'), '', $title); // Strip empty season/episode indicator from name

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
				'type' => null, // string|null
				'audio' => $audio, // string|null
				'runtime' => null, // int(timestamp)|null
				'year' => null, // int(4)|null
				'timestamp' => $timestamp, // int(timestamp)|null
				'category' => null, // string|null
				'mpa_rating' => null, // string|null
				'language' => null, // string|null
				'url' => null // string|null
			);

			unset($result, $season, $episode, $title, $hash, $magnet, $seeders, $leechers, $filesize, $quality, $codec, $date_added);
		}

		// Base info
		if(!empty($engine_temp)) {
			$engine_result['source'] = 'EZTV';
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, $json_response['torrents_count'], count($engine_temp));

		unset($response, $json_response, $engine_temp);
		
		return $engine_result;
	}
}
?>
