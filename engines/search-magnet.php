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
	protected $requests, $boxoffice;
	
	public function __construct($opts, $mh) {
		$this->requests = array();

		// Extra functions to process magnet results
		require ABSPATH.'functions/tools-magnet.php';
	    
		if($opts->enable_limetorrents == 'on') {
			require ABSPATH.'engines/magnet/lime.php';
			$this->requests[] = new LimeRequest($opts, $mh);
		}

		if($opts->enable_piratebay == 'on') {
			require ABSPATH.'engines/magnet/thepiratebay.php';
			$this->requests[] = new PirateBayRequest($opts, $mh);
		}

		if($opts->enable_yts == 'on') {
			require ABSPATH.'engines/magnet/yts.php';
			$this->requests[] = new YTSRequest($opts, $mh);
		}

		if($opts->enable_nyaa == 'on') {
			require ABSPATH.'engines/magnet/nyaa.php';
			$this->requests[] = new NyaaRequest($opts, $mh);
		}

		if($opts->enable_sukebei == 'on') {
			require ABSPATH.'engines/magnet/sukebei.php';
			$this->requests[] = new SukebeiRequest($opts, $mh);
		}

		if($opts->enable_eztv == 'on') {
			if(substr(strtolower($opts->query), 0, 2) == 'tt') {
				require ABSPATH.'engines/magnet/eztv.php';
				$this->requests[] = new EZTVRequest($opts, $mh);
			}
		}
	}

    public function parse_results($response) {
        $results = array();

        if(count($this->requests) !== 0) {
	        foreach($this->requests as $request) {
	            if($request->request_successful()) {
					$engine_result = $request->get_results();
	
					if(!empty($engine_result)) {
						if(array_key_exists('search', $engine_result)) {
							// Count results per source
							$results['sources'][$engine_result['source']] = $engine_result['amount'];
		
							// Merge duplicates and apply relevance scoring
							foreach($engine_result['search'] as $result) {
								if(array_key_exists('search', $results)) {
									$result_urls = array_column($results['search'], 'hash', 'id');
									$found_id = array_search($result['hash'], $result_urls); // Return the result ID, or false if not found
								} else {
									$found_id = false;
								}
		
								if($found_id !== false) {
									// Duplicate result from another engine
									// If seeders or leechers mismatch, assume they're different peers
									if($results['search'][$found_id]['seeders'] != $result['seeders']) $results['search'][$found_id]['combo_seeders'] += intval($result['seeders']);
									if($results['search'][$found_id]['leechers'] != $result['leechers']) $results['search'][$found_id]['combo_leechers'] += intval($result['leechers']);
		
									$results['search'][$found_id]['combo_source'][] = $engine_result['source'];
		
									// If duplicate result has more info, add it
									if(is_null($results['search'][$found_id]['year']) && !is_null($result['year'])) $results['search'][$found_id]['year'] = $result['year'];
									if(is_null($results['search'][$found_id]['category']) && !is_null($result['category'])) $results['search'][$found_id]['category'] = $result['category'];
									if(is_null($results['search'][$found_id]['runtime']) && !is_null($result['runtime'])) $results['search'][$found_id]['runtime'] = $result['runtime'];
									if(is_null($results['search'][$found_id]['url']) && !is_null($result['url'])) $results['search'][$found_id]['url'] = $result['url'];
									if(is_null($results['search'][$found_id]['date_added']) && !is_null($result['date_added'])) $results['search'][$found_id]['date_added'] = $result['date_added'];
									if(is_null($results['search'][$found_id]['quality']) && !is_null($result['quality'])) $results['search'][$found_id]['quality'] = $result['quality'];
									if(is_null($results['search'][$found_id]['type']) && !is_null($result['type'])) $results['search'][$found_id]['type'] = $result['type'];
									if(is_null($results['search'][$found_id]['audio']) && !is_null($result['audio'])) $results['search'][$found_id]['audio'] = $result['audio'];
								} else {
									// First find, rank and add to results
									// Ranks by combo_seeders instead of regular ranking
									$result['combo_seeders'] = intval($result['seeders']);
									$result['combo_leechers'] = intval($result['leechers']);
									$result['combo_source'][] = $engine_result['source'];
									$result['id'] = md5($result['hash']); // Predictable/repeatable 'unique' string 
	
									$results['search'][$result['id']] = $result;
								}
		
								unset($result, $result_urls, $found_id, $social_media_multiplier, $goosle_rank, $match_rank);
							}
						}
					}
				} else {
					$request_result = curl_getinfo($request->ch);
					$http_code_info = ($request_result['http_code'] > 200 && $request_result['http_code'] < 600) ? " - <a href=\"https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/".$request_result['http_code']."\" target=\"_blank\">What's this</a>?" : "";
					$github_issue_url = "https://github.com/adegans/Goosle/discussions/new?category=general&".http_build_query(array('title' => get_class($request)." failed with error ".$request_result['http_code'], 'body' => "```\nEngine: ".get_class($request)."\nError Code: ".$request_result['http_code']."\nRequest url: ".$request_result['url']."\n```", 'labels' => 'request-error'));
					
		            $results['error'][] = array(
		                'message' => "<strong>Ohno! A search query ran into some trouble.</strong> Usually you can try again in a few seconds to get a result!<br /><strong>Engine:</strong> ".get_class($request)."<br /><strong>Error code:</strong> ".$request_result['http_code'].$http_code_info."<br /><strong>Request url:</strong> ".$request_result['url']."<br /><strong>Need help?</strong> Find <a href=\"https://github.com/adegans/Goosle/discussions\" target=\"_blank\">similar issues</a>, or <a href=\"".$github_issue_url."\" target=\"_blank\">ask your own question</a>."
		            );
	            }
	            
	            unset($request);
	        }
		} else {
			$results['error'][] = array(
				'message' => "<strong>Configuration issue!</strong> It appears that all Magnet Search engines are disabled. Please enable at least one in your config.php file.<br /><strong>Need help?</strong> Find <a href=\"https://github.com/adegans/Goosle/discussions\" target=\"_blank\">similar issues</a>."
			);
		}

		if(array_key_exists('search', $results)) {
			// Re-order results based on seeders
	        $keys = array_column($results['search'], 'combo_seeders');
	        array_multisort($keys, SORT_DESC, $results['search']);

			// Cap results
			$results['search'] = array_slice($results['search'], 0, 200);

			unset($keys);
		} else {
			// Add error if there are no search results
            $results['error'][] = array(
                'message' => "No results found. Please try with more specific or different keywords!" 
            );
		}

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

		// Latest additions to yts
		if($opts->show_yts_highlight == 'on') {
	        require ABSPATH.'engines/boxoffice/yts.php';
			
			echo "<div class=\"result-grid\">";
			echo "<h2>Latest releases from YTS</h2>";
			echo "<p>View these and more new releases on the <a href=\"./box-office.php?q=".$opts->query."&t=9&a=".$opts->hash."\">box office</a> page!</p>";
			echo "<ul>";
			
			$highlights = array_slice(yts_boxoffice($opts, 'date_added'), 0, 8);
			
			foreach($highlights as $highlight) {
				$thumb = (!empty($highlight['thumbnail'])) ? $highlight['thumbnail'] : $blank_thumb;

				echo "<li class=\"result highlight yts\">";
				echo "	<div class=\"result-box\">";
				echo "		<a onclick=\"openpopup('highlight-".$highlight['id']."')\" title=\"More info: ".$highlight['name']."\"><img src=\"".$thumb."\" alt=\"".$highlight['name']."\" /></a>";
				echo "	</div>";
				echo "	<span><center><a onclick=\"openpopup('highlight-".$highlight['id']."')\" title=\"More info: ".$highlight['name']."\">".$highlight['name']."</a></center></span>";

				// HTML for popup
				echo "	<div id=\"highlight-".$highlight['id']."\" class=\"goosebox\">";
				echo "		<div class=\"goosebox-body\">";
				echo "			<h2>".$highlight['name']."</h2>";
				echo "			<p>".$highlight['summary']."</p>";
				echo "			<p><a href=\"./results.php?q=".urlencode($highlight['name']." ".$highlight['year'])."&a=".$opts->hash."&t=0\" title=\"Search on Goosle Web Search!\">Search on Goosle</a> &bull; <a href=\"./results.php?q=".urlencode($highlight['name']." ".$highlight['year'])."&a=".$opts->hash."&t=9\" title=\"Search on Goosle Magnet Search! For new additions results may be limited.\">Find more Magnet links</a></p>";
				echo "			<p><strong>Genre:</strong> ".$highlight['category']."<br /><strong>Released:</strong> ".$highlight['year']."<br /><strong>Rating:</strong> ".movie_star_rating($highlight['rating'])." <small>(".$highlight['rating']." / 10)</small></p>";

				// List downloads
				echo "			<h3>Downloads:</h3>";
				echo "			<p>";
				foreach($highlight['magnet_links'] as $magnet) {
					if(!is_null($magnet['quality'])) $meta[] = $magnet['quality'];
					if(!is_null($magnet['type'])) $meta[] = $magnet['type'];
					$meta[] = human_filesize($magnet['filesize']);
		
					echo "<button class=\"download\" onclick=\"location.href='".$magnet['magnet']."'\">".implode(' / ', $meta)."</button>";
					unset($meta);
				}
				echo "			</p>";

				echo "			<p><a onclick=\"closepopup()\">Close</a></p>";
				echo "		</div>";
				echo "	</div>";

				echo "</li>";	

				unset($highlight, $magnet);
			}

	        echo "</ul>";
	        echo "</div>";
		}

		// Main content
		if(array_key_exists('search', $results)) {
			// Is this a shared search? Move shared result to position 1
			if($opts->show_share_option == 'on' && !empty($opts->share)) {
				$keys = array_keys($results['search']);
				$found_id = array_search(md5($opts->share), $keys); // Return the shared key ID
				$found_id = $keys[$found_id]; // Get the shared ID

				$first = array($found_id => $results['search'][$found_id]);
				unset($results['search'][$found_id], $keys, $found_id);

				$results['search'] = array_merge($first, $results['search']);
			}

			echo "<ul>";

			// Elapsed time
			$number_of_results = count($results['search']);
			echo "<li class=\"timer\">Fetched ".$number_of_results." results in ".$results['time']." seconds.</li>";

			// Format sources
			echo "<li class=\"sources\">".search_sources($results['sources'])."</li>";

			// Search results
			foreach($results['search'] as $result) {
				// Extra data
				$base = $meta = array();
				if(!empty($result['combo_seeders'])) $base[] = "<strong>Seeds:</strong> <span class=\"green\">".$result['combo_seeders']."</span>";
				if(!empty($result['combo_leechers'])) $base[] = "<strong>Peers:</strong> <span class=\"red\">".$result['combo_leechers']."</span>";
				if(!empty($result['filesize'])) $base[] = "<strong>Size:</strong> ".$result['filesize'];
				if(!empty($result['date_added'])) $base[] = "<strong>Added on:</strong> ".date("M d, Y", $result['date_added']);

				if(!empty($result['category'])) $meta[] = "<strong>Category:</strong> ".$result['category'];
				if(!empty($result['year'])) $meta[] = "<strong>Year:</strong> ".$result['year'];
				if(!empty($result['runtime'])) $meta[] = "<strong>Runtime:</strong> ".$result['runtime'];
				if(!empty($result['quality'])) $meta[] = "<strong>Quality:</strong> ".$result['quality'];
				if(!empty($result['type'])) $meta[] = "<strong>Type:</strong> ".$result['type'];
				if(!empty($result['audio'])) $meta[] = "<strong>Audio:</strong> ".$result['audio'];

				// Highlight the shared result
				$class = "";
				if($opts->show_share_option == 'on') {
					if(!empty($opts->share) && $result['hash'] == $opts->share) {
						$class = " shared";
					} else {
						$base[] = "<a onclick=\"openpopup('result-".$result['id']."')\" title=\"Share magnet result\">Share</a>";
					}
				}
	
				// Put result together
				echo "<li class=\"result magnet id-".$result['id'].$class."\">";
				echo "	<div class=\"title\"><a href=\"".$result['magnet']."\"><h2>".stripslashes($result['name'])."</h2></a></div>";
				echo "	<div class=\"description\">";
				echo "		<p>".implode(" &bull; ", $base)."</p>";
				echo "		<p>".implode(" &bull; ", $meta)."</p>";
				echo "	</div>";

				// Result sources
				if($opts->show_search_source == 'on') {
					// If available, add a link to the found torrent page
					$url = (array_key_exists('url', $result)) ? " &bull; <a href=\"".$result['url']."\" target=\"_blank\" title=\"Careful - Site may contain intrusive popup ads and malware!\">torrent page</a>" : "";

					echo "	<div class=\"meta\">Found on ".replace_last_comma(implode(', ', $result['combo_source'])).$url."</div>";
				}

				// Share popup
				if($opts->show_share_option == 'on' && $result['hash'] != $opts->share) {
					echo "	<div id=\"result-".$result['id']."\" class=\"goosebox\">";
					echo "		<div class=\"goosebox-body\">";
					echo "			<h2>Share magnet result</h2>";
					echo "			<p>Tap or click on the field below to copy the magnet result to your clipboard.</p>";
					echo "			<h3>Sharing: ".stripslashes($result['name'])."</h3>";
					echo "			<p><input tabindex=\"2\" type=\"text\" id=\"share-result-".$result['id']."\" class=\"share-field\" value=\"".share_encode($opts, $result['hash'])."\" /><button tabindex=\"1\" class=\"share-button\" onclick=\"clipboard('share-result-".$result['id']."')\">Copy magnet link</button></p>";
					echo "			<p><span id=\"share-result-".$result['id']."-response\"></span></p>";
					echo "			<p><a onclick=\"closepopup()\">Close</a></p>";
					echo "		</div>";
					echo "	</div>";
				}

				echo "</li>";

				unset($result, $base, $meta, $class, $url);
			}

			echo "</ul>";
			echo "<center><small>Goosle does not index, offer or distribute torrent files.</small></center>";
		}

		// No results found
        if(array_key_exists('error', $results)) {
	        foreach($results['error'] as $error) {
            	echo "<div class=\"error\">".$error['message']."</div>";
            }
        }
	}
}
?>