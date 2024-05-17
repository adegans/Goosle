<?php
/* ------------------------------------------------------------------------------------
*  Goosle - A meta search engine for private and fast internet fun.
*
*  COPYRIGHT NOTICE
*  Copyright 2023-2024 Arnan de Gans. All Rights Reserved.
*
*  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
*  By using this code you agree to indemnify Arnan de Gans from any 
*  liability that might arise from its use.
------------------------------------------------------------------------------------ */
class MagnetSearch extends EngineRequest {
	protected $requests, $special_request;
	
	public function __construct($opts, $mh) {
		$this->requests = array();

		if($opts->enable_limetorrents == "on") {
			require ABSPATH."engines/magnet/lime.php";
			$this->requests[] = new LimeRequest($opts, $mh);
		}

		if($opts->enable_piratebay == "on") {
			require ABSPATH."engines/magnet/thepiratebay.php";
			$this->requests[] = new PirateBayRequest($opts, $mh);
		}

		if($opts->enable_yts == "on") {
			require ABSPATH."engines/magnet/yts.php";
			$this->requests[] = new YTSRequest($opts, $mh);
		}

		if($opts->enable_nyaa == "on") {
			require ABSPATH."engines/magnet/nyaa.php";
			$this->requests[] = new NyaaRequest($opts, $mh);
		}

		if($opts->enable_eztv == "on") {
			if(substr(strtolower($opts->query), 0, 2) == "tt") {
				require ABSPATH."engines/magnet/eztv.php";
				$this->requests[] = new EZTVRequest($opts, $mh);
			}
		}
		
		// Special search
		$this->special_request = special_magnet_request($opts, $mh);
	}

    public function parse_results($response) {
        $results = $results_temp = array();

        foreach($this->requests as $request) {
            if($request->request_successful()) {
				$engine_result = $request->get_results();

				if(!empty($engine_result)) {
					// Merge duplicates and apply relevance scoring
					foreach($engine_result as $result) {
						if(count($results_temp) > 1 && !is_null($result['hash'])) {
							$result_urls = array_column($results_temp, "hash", "id");
							$found_id = array_search($result['hash'], $result_urls);
						} else {
							$found_id = false;
						}

						if($found_id !== false) {
							// Duplicate result from another source
							// If seeders and/or leechers mismatch, assume they're different peers
							if($results_temp[$found_id]['seeders'] != $result['seeders']) $results_temp[$found_id]['combo_seeders'] += intval($result['seeders']);
							if($results_temp[$found_id]['leechers'] != $result['leechers']) $results_temp[$found_id]['combo_leechers'] += intval($result['leechers']);

							$results_temp[$found_id]['combo_source'][] = $result['source'];
						} else {
							// First find - rank (by combo_seeders instead of internal ranking) and add to results
							$result['combo_seeders'] = intval($result['seeders']);
							$result['combo_leechers'] = intval($result['leechers']);
							$result['combo_source'][] = $result['source'];

							$results_temp[$result['id']] = $result;
						}

						unset($result, $result_urls, $found_id, $social_media_multiplier, $goosle_rank, $match_rank);
					}
				}
			} else {
				$request_result = curl_getinfo($request->ch);
				$http_code_info = ($request_result['http_code'] > 200 && $request_result['http_code'] < 600) ? " - <a href=\"https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/".$request_result['http_code']."\" target=\"_blank\">What's this</a>?" : "";
				$github_issue_url = "https://github.com/adegans/Goosle/discussions/new?category=general&".http_build_query(array("title" => get_class($request)." failed with error ".$request_result['http_code'], "body" => "```\nEngine: ".get_class($request)."\nError Code: ".$request_result['http_code']."\nRequest url: ".$request_result['url']."\n```", "labels" => 'request-error'));
				
	            $results['error'][] = array(
	                "message" => "<strong>Ohno! A search query ran into some trouble.</strong> Usually you can try again in a few seconds to get a result!<br /><strong>Engine:</strong> ".get_class($request)."<br /><strong>Error code:</strong> ".$request_result['http_code'].$http_code_info."<br /><strong>Request url:</strong> ".$request_result['url']."<br /><strong>Need help?</strong> Find <a href=\"https://github.com/adegans/Goosle/discussions\" target=\"_blank\">similar issues</a>, or <a href=\"".$github_issue_url."\" target=\"_blank\">ask your own question</a>."
	            );
            }
            
            unset($request);
        }

		// Check for Special result
        if(count($this->special_request) > 0) {
            foreach($this->special_request as $source => $highlight) {
            	$results['special'][$source] = $highlight->get_results();
            }
        }

		if(count($results_temp) > 0) {
			// Sort by highest seeders
	        $seeders = array_column($results_temp, "combo_seeders");
	        array_multisort($seeders, SORT_DESC, $results_temp);
	
			// Cap results to 50
			$results['search'] = array_slice($results_temp, 0, 50);

			// Count results per source
			$results['sources'] = array_count_values(array_column($results['search'], 'source'));

			unset($sources);
		} else {
			// Add error if there are no search results
            $results['error'][] = array(
                "message" => "No results found. Please try with more specific or different keywords!" 
            );
		}

		unset($results_temp);

        return $results; 
    }

    public static function print_results($results, $opts) {
/*
// Uncomment for debugging
echo '<pre>Settings: ';
print_r($opts);
echo '</pre>';
echo '<pre>Search results: ';
print_r($results);
echo '</pre>';
*/

		// Special results
		if(array_key_exists("special", $results)) {
			echo "<div class=\"magnet-grid\">";
			if(array_key_exists("yts", $results['special'])) {
				if($opts->yts_highlight == "date_added") echo "<h2>Latest releases from YTS</h2>";
				if($opts->yts_highlight == "rating") echo "<h2>Highest rated on YTS</h2>";
				if($opts->yts_highlight == "download_count") echo "<h2>Most downloaded from YTS</h2>";
				if($opts->yts_highlight == "seeds") echo "<h2>Most seeded on YTS</h2>";
				echo "<ol>";
		
				foreach($results['special']['yts'] as $highlight) {
					echo "<li class=\"result yts\">";
					echo "<div class=\"magnet-box\">";
					echo "<img src=\"".$highlight['thumbnail']."\" alt=\"".$highlight['name']."\" />";
			       	echo "<p><strong>Genre:</strong> ".$highlight['category']."<br />";
			       	echo "<strong>Released:</strong> ".$highlight['year']."<br />";
			       	echo "<strong>Rating:</strong> ".$highlight['rating']." / 10<br />";
					echo "<strong>Downloads:</strong> ";
					foreach($highlight['magnet_links'] as $magnet) {
						echo "<a href=\"".$magnet['magnet']."\">".$magnet['quality']." ".$magnet['codec']."</a>";
					}
					echo "</p>";
					echo "</div>";
					echo "<strong><center><a href=\"".$highlight['url']."\" target=\"_blank\">".$highlight['name']."</a></center></strong>";
					echo "</li>";	
				}
				unset($highlight);
		
		        echo "</ol>";
			}

			if(array_key_exists("eztv", $results['special'])) {
				echo "<h2>Latest releases from EZTV</h2>";
				echo "<ol>";
		
				foreach($results['special']['eztv'] as $highlight) {
					echo "<li class=\"result eztv\">";
					echo "<div class=\"magnet-box\">";
					if(!empty($highlight['thumbnail'])) {
						echo "<img src=\"".$highlight['thumbnail']."\" alt=\"".$highlight['name']."\" />";
					} else {
						echo "<img src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mOUX3LxDAAE4AJiVKIoaQAAAABJRU5ErkJggg==\" style=\"max-height: 80px;\">";
					}
			       	echo "<p>".$highlight['quality']."<br />";
			       	echo "<a href=\"".$highlight['magnet_link']."\">Download</a></p>";
					echo "</div>";
					echo "<strong>".$highlight['name']." S".$highlight['season']."E".$highlight['episode']."</strong>";
					echo "</li>";	
				}
				unset($highlight);

		        echo "</ol>";
			}
	        echo "</div>";
		}

		// Main content
		if(array_key_exists("search", $results)) {
			echo "<ol>";

			// Elapsed time
			$number_of_results = count($results['search']);
			echo "<li class=\"meta\">Fetched ".$number_of_results." results in ".$results['time']." seconds.</li>";

			// Format sources
			echo "<li class=\"sources\">Includes ".search_sources($results['sources'])."</li>";

			// Search results
			foreach($results['search'] as $result) {
				// Extra data
				$meta = array();
				if(array_key_exists('quality', $result)) $meta[] = "<strong>Quality:</strong> ".$result['quality'];
				if(array_key_exists('codec', $result)) $meta[] = "<strong>Codec:</strong> ".$result['codec'];
				if(array_key_exists('year', $result)) $meta[] = "<strong>Year:</strong> ".$result['year'];
				if(array_key_exists('category', $result)) $meta[] = "<strong>Category:</strong> ".$result['category'];
				if(array_key_exists('runtime', $result)) $meta[] = "<strong>Runtime:</strong> ".date('H:i', mktime(0, $result['runtime']));
				if(array_key_exists('date_added', $result)) $meta[] = "<strong>Added on:</strong> ".date('M d, Y', $result['date_added']);

				// If available, add the url to the first found torrent page
				$url = (array_key_exists('url', $result)) ? " - <a href=\"".$result['url']."\" target=\"_blank\" title=\"Careful - Site may contain intrusive popup ads and malware!\">torrent page</a>" : "";
	
				// Put result together
				echo "<li class=\"result magnet id-".$result['id']."\">";
				echo "<div class=\"title\"><a href=\"".$result['magnet']."\"><h2>".stripslashes($result['name'])."</h2></a></div>";
				echo "<div class=\"description\"><strong>Seeds:</strong> <span class=\"seeders\">".$result['combo_seeders']."</span> - <strong>Peers:</strong> <span class=\"leechers\">".$result['combo_leechers']."</span> - <strong>Size:</strong> ".$result['size']."<br />".implode(" - ", $meta)."</div>";
				if($opts->show_search_source == "on") echo "<div class=\"description\"><strong>Found on:</strong> ".replace_last_comma(implode(", ", $result['combo_source'])).'.'.$url."</div>";
				echo "</li>";

				unset($result, $meta, $url);
			}

			echo "</ol>";
			echo "<center><small>Goosle does not index, offer or distribute torrent files.</small></center>";
		}

		// No results found
        if(array_key_exists("error", $results)) {
	        foreach($results['error'] as $error) {
            	echo "<div class=\"error\">".$error['message']."</div>";
            }
        }
	}
}
?>