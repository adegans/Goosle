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
------------------------------------------------------------------------------------ */

abstract class EngineRequest {
    protected $ch, $mh, $search, $opts, $url, $headers;

	function __construct($search, $opts, $mh) {
		$this->mh = $mh;
		$this->search = $search;
		$this->opts = $opts;

		$this->url = $this->get_request_url();
		// No search engine url
		if(!$this->url) return;

		// Skip if there is a cached result
		if($this->opts->cache_type !== 'off' && has_cached_results($this->opts->cache_type, $this->opts->hash, $this->url, $this->opts->cache_time)) return;

		// Default headers for the curl request
		$default_headers = array(
			'Accept' => 'text/html, application/xhtml+xml, application/json;q=0.9, application/xml;q=0.8, */*;q=0.7',
			'Accept-Language' => 'en-US,en;q=0.5',
			'Accept-Encoding' => 'gzip, deflate',
// 			'Connection' => 'keep-alive',
			'Upgrade-Insecure-Requests' => '1',
			'User-Agent' => $this->opts->user_agents[array_rand($this->opts->user_agents)],
			'Sec-Fetch-Dest' => 'document',
			'Sec-Fetch-Mode' => 'navigate',
			'Sec-Fetch-Site' => 'none'
		);

		// Override or remove headers per curl request
		$extra_headers = $this->get_request_headers();
		if(count($extra_headers) > 0) {
			$headers = array_filter(array_replace($default_headers, $extra_headers));

			foreach($headers as $key => $value) {
				$this->headers[] = $key.': '.$value;
			}

			unset($key, $value);
		} else {
			$this->headers = $default_headers;
		}

		unset($default_headers, $extra_headers);

		// Curl
		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_URL, $this->url);
		curl_setopt($this->ch, CURLOPT_HTTPGET, 1); // Redundant? Probably...
		curl_setopt($this->ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_ENCODING, 'gzip,deflate');
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($this->ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($this->ch, CURLOPT_VERBOSE, false);

		if($mh) curl_multi_add_handle($mh, $this->ch);
	}

	/*--------------------------------------
	// Get search engine url
	--------------------------------------*/
	public function get_request_url() {
		return '';
	}

	/*--------------------------------------
	// Check if a request to a search engine was successful
	--------------------------------------*/
	public function request_successful() {
		if((isset($this->ch) && curl_getinfo($this->ch)['http_code'] == '200') || ($this->opts->cache_type !== 'off' && has_cached_results($this->opts->cache_type, $this->opts->hash, $this->url, $this->opts->cache_time))) {
			return true;
		}

		// Set a timeout if Goosle is (temporarily) unable to use engine
		set_timeout(get_class($this), curl_getinfo($this->ch)['http_code']);

		return false;
	}

	/*--------------------------------------
	// Process results so Goosle can use it
	--------------------------------------*/
    public function parse_results($response) {
        $goosle_results = array();
		
		// Process requests and results
		if(count($this->requests) !== 0) {
	        foreach($this->requests as $request) {
	            if($request->request_successful()) {
					$engine_result = $request->get_results();

					if(!empty($engine_result)) {
						if(isset($engine_result['search'])) {
							$how_many_results = 0;

							// Merge duplicates and apply relevance scoring
							foreach($engine_result['search'] as $result) {
								// Safe search set to strict? Skip nsfw!
								if($request->search->safe && $result['nsfw']) continue;

								if(isset($goosle_results['search'])) {
									$result_urls = array_column($goosle_results['search'], 'hash', 'id');
									$found_id = array_search($result['hash'], $result_urls); // Return the result ID, or false if not found
								} else {
									$found_id = false;
								}

								$how_many_results++;

								if($found_id !== false) {
									// Duplicate result from another engine
									// If seeders or leechers mismatch, assume they're different peers
									if($goosle_results['search'][$found_id]['seeders'] != $result['seeders']) $goosle_results['search'][$found_id]['combo_seeders'] += intval($result['seeders']);
									if($goosle_results['search'][$found_id]['leechers'] != $result['leechers']) $goosle_results['search'][$found_id]['combo_leechers'] += intval($result['leechers']);

									$goosle_results['search'][$found_id]['combo_source'][] = $engine_result['source'];

									// If duplicate result has more info, add it
									if(is_null($goosle_results['search'][$found_id]['year']) && !is_null($result['year'])) $goosle_results['search'][$found_id]['year'] = $result['year'];
									if(is_null($goosle_results['search'][$found_id]['category']) && !is_null($result['category'])) $goosle_results['search'][$found_id]['category'] = $result['category'];
									if(is_null($goosle_results['search'][$found_id]['runtime']) && !is_null($result['runtime'])) $goosle_results['search'][$found_id]['runtime'] = $result['runtime'];
									if(is_null($goosle_results['search'][$found_id]['url']) && !is_null($result['url'])) $goosle_results['search'][$found_id]['url'] = $result['url'];
									if(is_null($goosle_results['search'][$found_id]['timestamp']) && !is_null($result['timestamp'])) $goosle_results['search'][$found_id]['timestamp'] = $result['timestamp'];
									if(is_null($goosle_results['search'][$found_id]['quality']) && !is_null($result['quality'])) $goosle_results['search'][$found_id]['quality'] = $result['quality'];
									if(is_null($goosle_results['search'][$found_id]['type']) && !is_null($result['type'])) $goosle_results['search'][$found_id]['type'] = $result['type'];
									if(is_null($goosle_results['search'][$found_id]['audio']) && !is_null($result['audio'])) $goosle_results['search'][$found_id]['audio'] = $result['audio'];
								} else {
									// First find, rank and add to results
									// Ranks by combo_seeders instead of regular ranking
									$result['combo_seeders'] = intval($result['seeders']);
									$result['combo_leechers'] = intval($result['leechers']);
									$result['combo_source'][] = $engine_result['source'];
									$result['id'] = md5($result['hash']); // Predictable/repeatable 'unique' string

									// Add result to final results
									$goosle_results['search'][$result['id']] = $result;
								}

								unset($result, $result_urls, $found_id, $social_media_multiplier, $goosle_rank, $match_rank);
							}

							// Count results per source
							$goosle_results['sources'][$engine_result['source']] = $how_many_results;

							unset($how_many_results);
						}
					}
				} else {
					$request_result = curl_getinfo($request->ch);
					$http_code_info = ($request_result['http_code'] > 200 && $request_result['http_code'] < 600) ? " - <a href=\"https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/".$request_result['http_code']."\" target=\"_blank\">What's this</a>?" : "";
					$github_issue_url = "https://github.com/adegans/Goosle/discussions/new?category=general&".http_build_query(array('title' => get_class($request)." failed with error ".$request_result['http_code'], 'body' => "```\nEngine: ".get_class($request)."\nError Code: ".$request_result['http_code']."\nRequest url: ".$request_result['url']."\n```", 'labels' => 'request-error'));

		            $goosle_results['error'][] = array(
		                'message' => "<strong>Ohno! A search query ran into some trouble.</strong> Usually you can try again in a few seconds to get a result!<br /><strong>Engine:</strong> ".get_class($request)."<br /><strong>Error code:</strong> ".$request_result['http_code'].$http_code_info."<br /><strong>Request url:</strong> ".$request_result['url']."<br /><strong>Need help?</strong> Find <a href=\"https://github.com/adegans/Goosle/discussions\" target=\"_blank\">similar issues</a>, or <a href=\"".$github_issue_url."\" target=\"_blank\">ask your own question</a>."
		            );
	            }

	            unset($request);
	        }

			if(array_key_exists('search', $goosle_results)) {
				// Re-order results based on seeders
		        $keys = array_column($goosle_results['search'], 'combo_seeders');
		        array_multisort($keys, SORT_DESC, $goosle_results['search']);

				// Count all results
				$goosle_results['number_of_results'] = count($goosle_results['search']);

				unset($keys);
			} else {
				// Add error if there are no search results
	            $goosle_results['error'][] = array(
	                'message' => "No results found. Please try with more specific or different keywords!"
	            );
			}
		} else {
			$goosle_results['error'][] = array(
				'message' => "It appears that all Magnet Search engines are disabled. Check your settings or contact the site administrator."
			);
		}

        return $goosle_results;
    }

	/*--------------------------------------
	// Load search results
	--------------------------------------*/
	public function get_results() {
		if(!isset($this->url)) {
			return $this->parse_results(null);
		}

		// If there is a cached result from an earlier search use that instead
		if($this->opts->cache_type !== 'off' && has_cached_results($this->opts->cache_type, $this->opts->hash, $this->url, $this->opts->cache_time)) {
			return fetch_cached_results($this->opts->cache_type, $this->opts->hash, $this->url);
		}

		// Curl request
		if(!isset($this->ch)) {
			return $this->parse_results(null);
		}

		$response = ($this->mh) ? curl_multi_getcontent($this->ch) : curl_exec($this->ch);

		$results = $this->parse_results($response) ?? array();

		// Cache last request if there is something to cache
		if($this->opts->cache_type !== 'off') {
			if(count($results) > 0) store_cached_results($this->opts->cache_type, $this->opts->hash, $this->url, $results, $this->opts->cache_time);
		}

		return $results;
	}

	/*--------------------------------------
	// Output search results after processing
	--------------------------------------*/
    public static function print_results($goosle_results, $search, $opts) {
// Uncomment for debugging
/*
echo "<pre>Settings: ";
print_r($opts);
echo "</pre>";
echo "<pre>Search data: ";
print_r($search);
echo "</pre>";
echo "<pre>Search results: ";
print_r($goosle_results);
echo "</pre>";
*/

		// Latest additions to yts
		if($opts->show_yts_highlight == 'on') {
			echo "<h2>Latest releases from YTS</h2>";
			echo "<ul class=\"result-grid\">";

	        require ABSPATH.'functions/boxoffice-results.php';
			$highlights = array_slice(yts_boxoffice($opts, 'date_added'), 0, 8);

			foreach($highlights as $highlight) {
				$thumb = (!empty($highlight['thumbnail'])) ? $highlight['thumbnail'] : $opts->pixel;

				echo "<li class=\"result highlight yts\">";
				echo "	<div class=\"thumb\">";
				echo "		<a onclick=\"openpopup('highlight-".$highlight['id']."')\" title=\"More info: ".$highlight['title']."\"><img src=\"".$thumb."\" alt=\"".$highlight['title']."\" /></a>";
				echo "	</div>";
				echo "	<span><center><a onclick=\"openpopup('highlight-".$highlight['id']."')\" title=\"More info: ".$highlight['title']."\">".$highlight['title']."</a></center></span>";

				// HTML for popup
				echo highlight_popup($opts->pixel, $highlight);

				echo "</li>";

				unset($highlight);
			}

	        echo "</ul>";
		}

		// Main content
		if(array_key_exists('search', $goosle_results)) {
			// Pagination offset
			if($opts->cache_type !== 'off') {
				$offset = ((($search->page - 1) * $opts->search_results_per_page));
				$goosle_results['search'] = array_slice($goosle_results['search'], $offset, $opts->search_results_per_page);
			}

			echo "<ul>";

			// Elapsed time and search sources
			echo "<li class=\"meta\">";
			echo "	<p class=\"timer\">Fetched ".$goosle_results['number_of_results']." results in ".$goosle_results['time']." seconds.</p>";
			if($opts->show_search_source == 'on') {
				echo "	<p class=\"sources\">".search_sources($goosle_results['sources'])."</p>";
			}
			echo "</li>";

			// Search results
			foreach($goosle_results['search'] as $result) {
				// Extra data
				$base = $meta = array();
				if(!empty($result['verified_uploader'])) {
					$icon = ($result['verified_uploader'] == 'yes') ? 'magnet-verified' : 'magnet-not-verified';
					$base[] = "<a onclick=\"openpopup('info-torrentverified')\" title=\"".$icon." - Click for more information\"><span class=\"".$icon."\"></span></a>";
				}

				if(!empty($result['combo_seeders'])) $base[] = "<strong>Seeds:</strong> <span class=\"green\">".$result['combo_seeders']."</span>";
				if(!empty($result['combo_leechers'])) $base[] = "<strong>Peers:</strong> <span class=\"red\">".$result['combo_leechers']."</span>";
				if(!empty($result['filesize'])) $base[] = "<strong>Size:</strong> ".human_filesize($result['filesize']);
				if(!empty($result['timestamp'])) $base[] = "<strong>Added on:</strong> ".the_date("M d, Y", $result['timestamp']);
				if(!empty($result['mpa_rating'])) $base[] = "<strong>MPA Rating:</strong> ".$result['mpa_rating'];
				if(!empty($result['imdb_id'])) $base[] = "<a href=\"https://www.imdb.com/title/".$result['imdb_id']."\" target=\"_blank\">IMDb page</a>";
				if($opts->show_share_option == 'on') $base[] = "<a onclick=\"openpopup('result-".$result['id']."')\" title=\"Share magnet result\">Share</a>";

				if(!empty($result['category'])) $meta[] = "<strong>Category:</strong> ".$result['category'];
				if(!empty($result['year'])) $meta[] = "<strong>Year:</strong> ".$result['year'];
				if(!empty($result['runtime'])) $meta[] = "<strong>Runtime:</strong> ".$result['runtime'];
				if(!empty($result['quality'])) $meta[] = "<strong>Quality:</strong> ".$result['quality'];
				if(!empty($result['type'])) $meta[] = "<strong>Type:</strong> ".$result['type'];
				if(!empty($result['audio'])) $meta[] = "<strong>Audio:</strong> ".$result['audio'];

				// Put result together
				echo "<li class=\"result magnet id-".$result['id']."\">";
				echo "	<div class=\"title\"><a href=\"".$result['magnet']."\"><h2>".stripslashes($result['title'])."</h2></a></div>";
				echo "	<div class=\"description\">";
				echo "		<p>".implode(" &bull; ", $base)."</p>";
				echo "		<p>".implode(" &bull; ", $meta)."</p>";
				// Result sources
				if($opts->show_search_source == 'on') {
					// If available, add a link to the found torrent page
					$url = (!is_null($result['url'])) ? " &bull; <a href=\"".$result['url']."\" target=\"_blank\" title=\"Visit torrent page\">torrent page</a> <a onclick=\"openpopup('info-torrentpagelink')\" title=\"Click for more information\"><span class=\"tooltip-alert\"></span></a>" : "";

					echo "	<p><small>Found on ".replace_last_comma(implode(', ', $result['combo_source'])).$url."</small></p>";
				}
				echo "	</div>";

				// Share popup
				if($opts->show_share_option == 'on') {
					echo "	<div id=\"result-".$result['id']."\" class=\"goosebox\">";
					echo "		<div class=\"goosebox-body\">";
					echo "			<h2>Copy Magnet Link</h2>";
					echo "			<p>Tap or click on the field below to copy the magnet link to your clipboard.</p>";
					echo "			<h3>Sharing: ".stripslashes($result['title'])."</h3>";
					echo "			<p><input tabindex=\"2\" type=\"text\" id=\"share-result-".$result['id']."\" class=\"share-field\" value=\"".$result['magnet']."\" /><button tabindex=\"1\" class=\"share-button\" onclick=\"clipboard('share-result-".$result['id']."')\">Copy magnet link</button></p>";
					echo "			<p><span id=\"share-result-".$result['id']."-response\"></span></p>";
					echo "			<p><a onclick=\"closepopup()\">Close</a></p>";
					echo "		</div>";
					echo "	</div>";
				}

				echo "</li>";

				unset($result, $base, $meta, $url);
			}

			echo "</ul>";

			// Pagination navigation
			if($opts->cache_type !== 'off' && $goosle_results['number_of_results'] > $opts->search_results_per_page) {
				echo "<p class=\"pagination\">".search_pagination($search, $opts->baseurl, $opts->search_results_per_page, $goosle_results['number_of_results'])."</p>";
			}

			echo "<p class=\"text-center\"><small>Goosle does not index, offer or distribute torrent files. Found content may be subject to copyright.</small></p>";

			// Torrent site warning popup (Normally hidden)
			echo "<div id=\"info-torrentpagelink\" class=\"goosebox\">";
			echo "	<div class=\"goosebox-body\">";
			echo "		<h2>Be careful when you visit torrent sites</h2>";
			echo "		<p>Many torrent websites have intrusive popup ads and malware! If you visit the torrent page, be careful what you click on and close any popups/redirects that appear.</p>";
			echo "		<p><a onclick=\"closepopup()\">Close</a></p>";
			echo "	</div>";
			echo "</div>";

			// Verified magnet info popup (Normally hidden)
			echo "<div id=\"info-torrentverified\" class=\"goosebox\">";
			echo "	<div class=\"goosebox-body\">";
			echo "		<h2>Trusted uploaders</h2>";
			echo "		<p>Some websites have a group of verified and/or trusted uploaders. These usually are persons or groups that are known to provide good quality downloads. Unfortunately most sites do not make this disctintion and as such the badge is generally not something to seek out when you're looking for high quality downloads.</p>";
			echo "		<p><span class=\"magnet-verified\"></span> Downloads with a blue shield and checkmark are uploaded by a verified or trusted uploader according to the torrent site.</p>";
			echo "		<p><span class=\"magnet-not-verified\"></span> Downloads with a red shield and questionmark indicate that the uploader is <em>not</em> verified by the torrent site. Unverified magnet links are not necessarily bad but may contain low quality or misleading content and should be treated with caution.</p>";
			echo "		<p><a onclick=\"closepopup()\">Close</a></p>";
			echo "	</div>";
			echo "</div>";
		}

		// No results found
        if(array_key_exists('error', $goosle_results)) {
	        foreach($goosle_results['error'] as $error) {
            	echo "<div class=\"error\">".$error['message']."</div>";
            }
        }
	}
}
?>
