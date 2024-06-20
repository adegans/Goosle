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
class ImageSearch extends EngineRequest {
	protected $requests;
	
	public function __construct($opts, $mh) {
		$this->requests = array();
		
		if($opts->enable_yahooimages == 'on') {
			require ABSPATH.'engines/image/yahoo-images.php';
			$this->requests[] = new YahooImageRequest($opts, $mh);	
		}

		if($opts->enable_openverse == 'on') {
			require ABSPATH.'engines/image/openverse.php';
			$this->requests[] = new OpenverseRequest($opts, $mh);	
		}

		if($opts->enable_qwantimages == 'on') {
			require ABSPATH.'engines/image/qwant-images.php';
			$this->requests[] = new QwantImageRequest($opts, $mh);	
		}
	}

    public function parse_results($response) {
        $results = array();

        if(count($this->requests) !== 0) {
	        foreach($this->requests as $request) {
				if($request->request_successful()) {
					$engine_result = $request->get_results();
	
					if(!empty($engine_result)) {
						if(array_key_exists('did_you_mean', $engine_result)) {
							$results['did_you_mean'] = $engine_result['did_you_mean'];
						}
						
						if(array_key_exists('search_specific', $engine_result)) {
							$results['search_specific'][] = $engine_result['search_specific'];
						}
	
						if(array_key_exists('search', $engine_result)) {
							// Count results per source
							$results['sources'][$engine_result['source']] = $engine_result['amount'];
	
							// Merge duplicates and apply relevance scoring
							foreach($engine_result['search'] as $result) {
								if(array_key_exists('search', $results)) {
									$result_urls = array_column($results['search'], 'image_full', 'id');
									$found_id = array_search($result['image_full'], $result_urls); // Return the result ID, or false if not found
								} else {
									$found_id = false;
								}
	
								if($found_id !== false) {
									// Duplicate result from another engine
									$results['search'][$found_id]['goosle_rank'] += $result['engine_rank'];
									$results['search'][$found_id]['combo_source'][] = $engine_result['source'];
								} else {
									// First find, rank and add to results
									// Replace anything but alphanumeric with a space
									$query_terms = explode(' ', preg_replace('/\s{2,}|[^a-z0-9]+/', ' ', strtolower($request->query)));
									$match_rank = match_count($result['url'], $query_terms);
									$match_rank += match_count($result['alt'], $query_terms);
	
									$result['goosle_rank'] = $result['engine_rank'] + $match_rank;
									$result['combo_source'][] = $engine_result['source'];
									$result['id'] = md5($result['image_full']);
	
									$results['search'][$result['id']] = $result;
								}
		
								unset($result, $result_urls, $found_id, $social_media_multiplier, $goosle_rank, $match_rank, $query_terms);
							}
						}
					}
				} else {
					$request_result = curl_getinfo($request->ch);
					$http_code_info = ($request_result['http_code'] > 200 && $request_result['http_code'] < 600) ? " - <a href=\"https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/".$request_result['http_code']."\" target=\"_blank\">What's this</a>?" : '';
					$github_issue_url = "https://github.com/adegans/Goosle/discussions/new?category=general&".http_build_query(array('title' => get_class($request)." failed with error ".$request_result['http_code'], 'body' => "```\nEngine: ".get_class($request)."\nError Code: ".$request_result['http_code']."\nRequest url: ".$request_result['url']."\n```", 'labels' => 'request-error'));
					
		            $results['error'][] = array(
		                'message' => "<strong>Ohno! A search query ran into some trouble.</strong> Usually you can try again in a few seconds to get a result!<br /><strong>Engine:</strong> ".get_class($request)."<br /><strong>Error code:</strong> ".$request_result['http_code'].$http_code_info."<br /><strong>Request url:</strong> ".$request_result['url']."<br /><strong>Need help?</strong> Find <a href=\"https://github.com/adegans/Goosle/discussions\" target=\"_blank\">similar issues</a>, or <a href=\"".$github_issue_url."\" target=\"_blank\">ask your own question</a>."
		            );
				}
				
				unset($request);
	        }
		} else {
			$results['error'][] = array(
				'message' => "<strong>Configuration issue!</strong> It appears that all Image Search engines are disabled. Please enable at least one in your config.php file.<br /><strong>Need help?</strong> Find <a href=\"https://github.com/adegans/Goosle/discussions\" target=\"_blank\">similar issues</a>."
			);
		}

		if(array_key_exists('search', $results)) {
			// Re-order results based on rank
			$keys = array_column($results['search'], 'goosle_rank');
			array_multisort($keys, SORT_DESC, $results['search']);

			unset($keys);
		} else {
			// Add error if there are no search results
            $results['error'][] = array(
                "message" => "No results found. Please try with more specific or different keywords!" 
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

		if(array_key_exists('search', $results)) {
			echo "<ul>";

			// Elapsed time
			$number_of_results = count($results['search']);
			echo "<li class=\"timer\">Fetched ".$number_of_results." results in ".$results['time']." seconds.</li>";

			// Format sources
			echo "<li class=\"sources\">".search_sources($results['sources'])."</li>";

			// Did you mean/Search suggestion
			if(array_key_exists("did_you_mean", $results)) {
				echo "<li class=\"suggestion\">Did you mean <a href=\"./results.php?q=".urlencode($results['did_you_mean'])."&t=".$opts->type."&a=".$opts->hash."\">".$results['did_you_mean']."</a>?".search_suggestion($opts, $results)."</li>";
			}

			echo "</ul>";

			// Search results
			echo "<div class=\"result-grid\">";
			echo "<ul>";
	
	        foreach($results['search'] as $result) {
				// Extra data
				$meta = $links = array();
				if(!empty($result['height']) && !is_null($result['width'])) $meta[] = $result['width']."&times;".$result['height'];
				if(!empty($result['filesize'])) $meta[] = human_filesize($result['filesize']);

				// Put result together
				echo "<li class=\"result image rank-".$result['goosle_rank']." id-".$result['id']."\">";
				echo "	<div class=\"result-box\">";
				echo "		<a href=\"".$result['url']."\" target=\"_blank\" title=\"".$result['alt']."\"><img src=\"".$result['image_thumb']."\" alt=\"".$result['alt']."\" /></a>";
				echo "	</div>";
				echo "	<div class=\"meta\">".implode(" - ", $meta)."<br /><a href=\"".$result['url']."\" target=\"_blank\">Website</a> - <a href=\"".$result['image_full']."\" target=\"_blank\">Image</a></div>";
				echo "</li>";
	        }

	        echo "</ul>";
	        echo "</div>";
			echo "<center><small>Goosle does not store or distribute image files.</small></center>";
		}

		// No results found
        if(array_key_exists("error", $results)) {
	        foreach($results['error'] as $error) {
            	echo "<div class=\"error\">".$error['message']."</div>";
            }
        }

		unset($results);
	}
}
?>