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
class MagnetSearch extends EngineRequest {
	protected $requests;
	
	public function __construct($search, $opts, $mh) {
		$this->requests = array();

		if($opts->enable_magnet_search == 'on') {
			// Extra functions to process magnet results
			require ABSPATH.'functions/tools-magnet.php';
		    
			if($opts->enable_limetorrents == 'on') {
				require ABSPATH.'engines/magnet/lime.php';
				$this->requests[] = new LimeRequest($search, $opts, $mh);
			}
	
			if($opts->enable_piratebay == 'on') {
				require ABSPATH.'engines/magnet/thepiratebay.php';
				$this->requests[] = new PirateBayRequest($search, $opts, $mh);
			}
	
			if($opts->enable_yts == 'on') {
				if($search->safe !== 0) {
					require ABSPATH.'engines/magnet/yts.php';
					$this->requests[] = new YTSRequest($search, $opts, $mh);
				}
			}
	
			if($opts->enable_nyaa == 'on') {
				require ABSPATH.'engines/magnet/nyaa.php';
				$this->requests[] = new NyaaRequest($search, $opts, $mh);
			}
	
			if($opts->enable_sukebei == 'on') {
				if($opts->show_nsfw_magnets == 'on' || ($opts->show_nsfw_magnets == 'off' && $search->safe === 0)) {
					require ABSPATH.'engines/magnet/sukebei.php';
					$this->requests[] = new SukebeiRequest($search, $opts, $mh);
				}
			}
	
			if($opts->enable_eztv == 'on') {
				if(substr(strtolower($search->query), 0, 2) == 'tt') {
					require ABSPATH.'engines/magnet/eztv.php';
					$this->requests[] = new EZTVRequest($search, $opts, $mh);
				}
			}
		}
	}

    public function parse_results($response) {
        $goosle_results = array();

		if(count($this->requests) !== 0) {
	        foreach($this->requests as $request) {
	            if($request->request_successful()) {
					$engine_result = $request->get_results();
	
					if(!empty($engine_result)) {
						if(isset($engine_result['search'])) {	
							$how_many_results = 0;

							// Merge duplicates and apply relevance scoring
							foreach($engine_result['search'] as $result) {
								// Safe search, skip nsfw?
								if($request->opts->show_nsfw_magnets == 'off' && $request->search->safe !== 0 && $result['nsfw']) continue;

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
				'message' => "It appears that all Magnet Search engines are disabled or that searching for magnet links is disabled."
			);
		}

        return $goosle_results; 
    }

    public static function print_results($goosle_results, $search, $opts) {
/*
// Uncomment for debugging
echo '<pre>Settings: ';
print_r($opts);
echo '</pre>';
echo "<pre>Search data: ";
print_r($search);
echo "</pre>";
echo '<pre>Search results: ';
print_r($goosle_results);
echo '</pre>';
*/

		// Latest additions to yts
		if($opts->show_yts_highlight == 'on') {
	        require ABSPATH.'engines/boxoffice/yts.php';
			
			echo "<h2>Latest releases from YTS</h2>";
			echo "<p>View these and more new releases on the <a href=\"./box-office.php?q=".$search->query."&t=9&a=".$opts->hash."\">box office</a> page!</p>";
			echo "<ul class=\"result-grid\">";
			
			$highlights = array_slice(yts_boxoffice($opts, 'date_added'), 0, 8);
			
			foreach($highlights as $highlight) {
				$thumb = (!empty($highlight['thumbnail'])) ? $highlight['thumbnail'] : $opts->pixel;

				echo "<li class=\"result highlight yts\">";
				echo "	<div class=\"thumb\">";
				echo "		<a onclick=\"openpopup('highlight-".$highlight['id']."')\" title=\"More info: ".$highlight['title']."\"><img src=\"".$thumb."\" alt=\"".$highlight['title']."\" /></a>";
				echo "	</div>";
				echo "	<span><center><a onclick=\"openpopup('highlight-".$highlight['id']."')\" title=\"More info: ".$highlight['title']."\">".$highlight['title']."</a></center></span>";

				// HTML for popup
				echo highlight_popup($opts->hash, $highlight);

				echo "</li>";	

				unset($highlight);
			}

	        echo "</ul>";
		}

		// Main content
		if(array_key_exists('search', $goosle_results)) {
			// Is this a shared search? Move shared result to position 1
			if($opts->show_share_option == 'on' && !empty($search->share)) {
				$keys = array_keys($goosle_results['search']);
				$found_id = array_search(md5($search->share), $keys); // Return the shared key ID
				$found_id = $keys[$found_id]; // Get the actual shared ID

				// Get the result
				$first = array($found_id => $goosle_results['search'][$found_id]);
				// Delete the result wherever it is
				unset($goosle_results['search'][$found_id], $keys, $found_id);
				// Add the result as the first item
				$goosle_results['search'] = array_merge($first, $goosle_results['search']);
			}

			// Pagination offset
			if($opts->cache_type !== 'off') {
				$offset = ((($search->page - 1) * $opts->search_results_per_page) + 1);
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
				if(!empty($result['combo_seeders'])) $base[] = "<strong>Seeds:</strong> <span class=\"green\">".$result['combo_seeders']."</span>";
				if(!empty($result['combo_leechers'])) $base[] = "<strong>Peers:</strong> <span class=\"red\">".$result['combo_leechers']."</span>";
				if(!empty($result['filesize'])) $base[] = "<strong>Size:</strong> ".$result['filesize'];
				if(!empty($result['timestamp'])) $base[] = "<strong>Added on:</strong> ".the_date("M d, Y", $result['timestamp']);
				if(!empty($result['mpa_rating'])) $base[] = "<strong>MPA Rating:</strong> ".$result['mpa_rating'];

				if(!empty($result['category'])) $meta[] = "<strong>Category:</strong> ".$result['category'];
				if(!empty($result['year'])) $meta[] = "<strong>Year:</strong> ".$result['year'];
				if(!empty($result['runtime'])) $meta[] = "<strong>Runtime:</strong> ".$result['runtime'];
				if(!empty($result['quality'])) $meta[] = "<strong>Quality:</strong> ".$result['quality'];
				if(!empty($result['type'])) $meta[] = "<strong>Type:</strong> ".$result['type'];
				if(!empty($result['audio'])) $meta[] = "<strong>Audio:</strong> ".$result['audio'];

				// Highlight the shared result
				$class = "";
				if($opts->show_share_option == 'on') {
					if(!empty($search->share) && $result['hash'] == $search->share) {
						$class = " shared";
					} else {
						$base[] = "<a onclick=\"openpopup('result-".$result['id']."')\" title=\"Share magnet result\">Share</a>";
					}
				}
	
				// Put result together
				echo "<li class=\"result magnet id-".$result['id'].$class."\">";
				echo "	<div class=\"title\"><a href=\"".$result['magnet']."\"><h2>".stripslashes($result['title'])."</h2></a></div>";
				echo "	<div class=\"description\">";
				echo "		<p>".implode(" &bull; ", $base)."</p>";
				echo "		<p>".implode(" &bull; ", $meta)."</p>";
				// Result sources
				if($opts->show_search_source == 'on') {
					// If available, add a link to the found torrent page
					$url = (!is_null($result['url'])) ? " &bull; <a href=\"".$result['url']."\" target=\"_blank\" title=\"Visit torrent page\">torrent page</a> <span class=\"tooltip tooltip-alert\"><span class=\"tooltiptext\"><strong>Careful!</strong> Site may contain intrusive popup ads and malware!</span></span>" : "";

					echo "	<p><small>Found on ".replace_last_comma(implode(', ', $result['combo_source'])).$url."</small></p>";
				}
				echo "	</div>";

				// Share popup
				if($opts->show_share_option == 'on' && $result['hash'] != $search->share) {
					// Generate an encoded hash for sharing
					$share_url = base64_url_encode($search->query.'||'.$search->type.'||'.$result['hash']);

					// The actual popup
					echo "	<div id=\"result-".$result['id']."\" class=\"goosebox\">";
					echo "		<div class=\"goosebox-body\">";
					echo "			<h2>Share magnet result</h2>";
					echo "			<p>Tap or click on the field below to copy the magnet result to your clipboard.</p>";
					echo "			<h3>Sharing: ".stripslashes($result['title'])."</h3>";
					echo "			<p><input tabindex=\"2\" type=\"text\" id=\"share-result-".$result['id']."\" class=\"share-field\" value=\"".get_base_url($opts->siteurl)."/results.php?s=".$share_url."\" /><button tabindex=\"1\" class=\"share-button\" onclick=\"clipboard('share-result-".$result['id']."')\">Copy magnet link</button></p>";
					echo "			<p><span id=\"share-result-".$result['id']."-response\"></span></p>";
					echo "			<p><a onclick=\"closepopup()\">Close</a></p>";
					echo "		</div>";
					echo "	</div>";
				}

				echo "</li>";

				unset($result, $base, $meta, $class, $url);
			}

			echo "</ul>";

			// Pagination navigation
			if($opts->cache_type !== 'off' && $goosle_results['number_of_results'] > $opts->search_results_per_page) {
				echo "<p class=\"pagination\">".search_pagination($search, $opts, $goosle_results['number_of_results'])."</p>";
			}

			echo "<p class=\"text-center\"><small>Goosle does not index, offer or distribute torrent files.</small></p>";
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