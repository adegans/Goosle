<?php
/* ------------------------------------------------------------------------------------
*  Goosle - The fast, privacy oriented search tool that just works.
*
*  COPYRIGHT NOTICE
*  Copyright 2023-2025 Arnan de Gans. All Rights Reserved.
*
*  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
*  By using this code you agree to indemnify Arnan de Gans from any
*  liability that might arise from its use.
---------------------------------------------------------------------------------------
Name: ExAmple
Description: My excellent search engine as an example
Maintainer: Arnan de Gans
Version: 20250209
Filter: 
------------------------------------------------------------------------------------ */

// The filter (above) determines when a search engine is active in certain use cases.
// Comma separate tags: imdb, nsfw
// Leave it empty (without tags) for default use.
// imdb = When the search engine only accepts IMdB IDs, like EZTV does.
// nsfw = When the engine only provides adult content, like Sukebei does.

// Name your class, replace ExAmple with something unique
class ExAmpleRequest extends EngineRequest {

	public function get_request_url() {
		// The website or API to make the rquest to.

		// For normal use, use $this->search->query_urlsafe, it's the urlencoded version of $this->search->query.
		// However, if your search query has special requirements or needs to be filtered use $this->search->query and urlencode it when you're done.
		// See eztv.php for a simple example.

		$url = 'https://example.com/?q='.$this->search->query_urlsafe;

		return $url;
	}

	public function get_request_headers() {
		// Headers the search engine accepts. Add more or less ass needed.
		// By default Goosle mimics a modern browser, JSON sites have different (simpler) requirements.

		// Default headers included:
		
		// 'Accept' => 'text/html, application/xhtml+xml, application/json;q=0.9, application/xml;q=0.8, */*;q=0.7',
		// 'Accept-Language' => 'en-US,en;q=0.5',
		// 'Accept-Encoding' => 'gzip, deflate',
		// 'Upgrade-Insecure-Requests' => '1',
		// 'User-Agent' => $this->opts->user_agents[array_rand($this->opts->user_agents)],
		// 'Sec-Fetch-Dest' => 'document',
		// 'Sec-Fetch-Mode' => 'navigate',
		// 'Sec-Fetch-Site' => 'none'

		// ONLY INCLUDE THE HEADERS THAT YOU ARE CHANGING
		// Set headers to 'null' to override the default and remove them.
		
		// Here we assume a simple JSON API, see other engines for more examples
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

		// Goosle can talk to a JSON API or scrape websites with Xpath.
		$method = "JSON"; // Or XPATH
		
		if($method == 'JSON') {
			$json_response = json_decode($response, true);
			
			// No response from server
			if(empty($json_response)) {
				if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No response', 0);
				return $engine_result;
			}
	
			// Figure out how many results there are. Use one of the response fields, or use count()
			$result_count = $json_response['torrents_count'];
	
			// No results/data found
			if($result_count == 0) {
				if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No results', 0);
				return $engine_result;
			}
			
			$results = $json_response['torrents'];

			unset($json_response);
		} else if($method == 'XPATH') {
			$xpath = get_xpath($response);
	
			// No response from server
			if(!$xpath) {
				if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, 'No response', 0);
				return $engine_result;
			}
	
			// Scrape the website for results
			// Change the starting point of your scrape here
			// Standard Xpath rules apply - https://www.w3schools.com/xml/xpath_syntax.asp
			$scrape = $xpath->query("//tbody/tr");

			// Count the results
			$result_count = count($scrape);
	
			// No results/data found
			if($result_count == 0) {
				if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, 'No results', 0);
				return $engine_result;
			}
			
			$results = $scrape;

			unset($xpath, $scrape);
		} else {
			return $engine_result;
		}

		// Process your results
		foreach($results as $result) {
			// CAUTION: This bit looks a bit messy and you'll probably have to rewrite/expand/change the loop to suit the search engine.
			// CAUTION: Every engine is different, each has it's own idiosyncrasies and frustrations.
			// CAUTION: There is no 'best' method except for what you figure out for yourself.

			// Obviously you can take inspiration from other engines included with Goosle.
			
			// For JSON results simply interpret each result and sanitize and use the fields.
			// For XPATH results go into each result and grab and sanitize the relevant data.
			// Uncomment the part between the /* */ tags that you want to start with below and modify it to your engines needs.
			// Review the $engine_temp array further down to see what fields are required, which are optional and what kind of variable it expects.
			
			// Obviously choose the method that works for you and match the field names or xpath paths.
			// Goosle has a sanitize() function to make inputs 'safe'. The basic routine is very similar to what WordPress uses. Review the function in /functions/tools.php.

			// EVERY INPUT FROM OUTSIDE GOOSLE MUST BE SANITIZED!
			// EVERY INPUT FROM OUTSIDE GOOSLE MUST BE SANITIZED!
			
			// Basic JSON example (from eztv.php)
/*
			$title = sanitize($result['title']);
			$hash = strtolower(sanitize($result['hash']));
			$magnet = sanitize($result['magnet_url']);
			$seeders = sanitize($result['seeds']);
			$leechers = sanitize($result['peers']);
			$filesize = sanitize($result['size_bytes']);
*/

			// Basic XPATH example (from nyaa.php)
/*
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
*/
			
			// Ignore results with 0 seeders?
			// This is a user configurable setting
			if($this->opts->show_zero_seeders == 'off' AND $seeders == 0) continue;

			// Do we expect TV Episodes? You can remove this if not.
/*
			// Clean up season and episode number
			$season = sanitize($result['season']);
			if($season < 10) $season = '0'.$season;
			$episode = sanitize($result['episode']);
			if($episode < 10) $episode = '0'.$episode;

			// Throw out mismatched episodes
			if(!is_season_or_episode($this->search->query, 'S'.$season.'E'.$episode)) continue;
*/

			// Get extra data

			// Expand on this 'list' to get the other bits and pieces for optional data
			// There are several filter functions in /functions/tools-search.php to figure stuff out from existing variables.
			// Preferably you use provided data from the engine. See other engines for examples.

			$timestamp = (isset($result['date_released_unix'])) ? sanitize($result['date_released_unix']) : null;
			$quality = find_video_quality($title);
			$codec = find_video_codec($title);
			$audio = find_audio_codec($title);

			// Add codec to quality
			if(!empty($codec)) $quality = $quality.' '.$codec;

			// Do we expect TV Episodes? You can remove this if not.
/*
			// Clean up show name
			$title = (preg_match('/.+?(?=[0-9]{3,4}p)|xvid|divx|(x|h)26(4|5)/i', $title, $clean_name)) ? $clean_name[0] : $title; // Break off show name before video resolution
			$title = str_replace(array('S0E0', 'S00E00'), '', $title); // Strip empty season/episode indicator from name
*/

			$engine_temp[] = array (
				// Required
				'hash' => $hash, // string (Torrent hash)
				'title' => $title, // string (Torrent name/title, name of the download)
				'magnet' => $magnet, // string (Magnet link)
				'seeders' => $seeders, // int (How many seeders)
				'leechers' => $leechers, // int (How many leechers)
				'filesize' => $filesize, // int (File size in bytes - Use filesize_to_bytes() for a conversion, see /functions/tools.php and other engines for examples)

				// Optional
				'verified_uploader' => null, // string|null (Is this a trusted uploader on the site - Generally this will be 'null', if not provided by the engine)
				'nsfw' => false, // bool (Is it porn?)
				'quality' => $quality, // string|null (Video quality)
				'type' => null, // string|null (What kind of video is it? Bluray? DVD? Web rip? Etc.)
				'audio' => $audio, // string|null (Audio codec and channels)
				'runtime' => null, // int(timestamp)|null (How long is the video/audio in seconds)
				'year' => null, // int(4)|null (From when is the content?)
				'timestamp' => $timestamp, // int(timestamp)|null (When was the content release on the engine?)
				'category' => null, // string|null (Category, movie, music, etc. Review the various engines in Goosle for ideas on how to implement this and filter with it)
				'imdb_id' => null, // string|null (Full IMDB id if available (tt123456)
				'mpa_rating' => null, // string|null (If a movie, what is the rating?)
				'language' => null, // string|null (Language of the content)
				'url' => null // string|null (Torrent site download page url, where to get the torrent from)
			);

			// Clean up, unset every variable you've created in the foreach()
			unset($result, $season, $episode, $title, $hash, $magnet, $seeders, $leechers, $filesize, $quality, $codec, $date_added);
		}

		// If we have something to include, complete the array and include it
		if(!empty($engine_temp)) {
			$engine_result['source'] = $this->opts->engines[get_class($this)]['name'];
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, $result_count, count($engine_temp));

		// More clean up
		unset($response, $results, $engine_temp);

		return $engine_result;
	}
}
?>
