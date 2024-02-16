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
class ImageSearch extends EngineRequest {
	protected $requests;
	
	public function __construct($opts, $mh) {
		require ABSPATH."engines/image/yahoo.php";
		
		$this->requests = array(
			new YahooImageRequest($opts, $mh),
		);
	}

    public function parse_results($response) {
        $results = array();

		// Merge all results together
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
						// Merge duplicates and apply relevance scoring
						foreach($engine_result['search'] as $result) {
							if(array_key_exists('search', $results)) {
								$result_urls = array_column($results['search'], "direct_link", "id");
								$found_key = array_search($result['direct_link'], $result_urls);
							} else {
								$found_key = false;
							}

							if($found_key !== false) {
								// Duplicate result from another source, merge and rank accordingly
								$results['search'][$found_key]['goosle_rank'] += $result['engine_rank'];
							} else {
								// First find, rank and add to results
								$query_terms = explode(" ", preg_replace("/[^a-z0-9 ]+/", "", strtolower($request->query)));
								$match_rank = match_count($result['alt'], $query_terms);

								$result['goosle_rank'] = $result['engine_rank'] + $match_rank;

								$results['search'][$result['id']] = $result;
							}
	
							unset($result, $result_urls, $found_key, $social_media_multiplier, $goosle_rank, $match_rank);
						}
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

		if(array_key_exists('search', $results)) {
			// Re-order results based on rank
			$keys = array_column($results['search'], 'goosle_rank');
			array_multisort($keys, SORT_DESC, $results['search']);

			// Count results per source
			$results['sources'] = array_count_values(array_column($results['search'], 'source'));
			
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

		if(array_key_exists("search", $results)) {
			echo "<ol>";

			// Elapsed time
			$number_of_results = count($results['search']);
			echo "<li class=\"meta\">Fetched ".$number_of_results." results in ".$results['time']." seconds.</li>";

			// Format sources
	        search_sources($results['sources']);

			// Did you mean/Search suggestion
			search_suggestion($opts, $results);

			echo "</ol>";

			// Search results
			echo "<div class=\"image-wrapper\">";
			echo "<ol class=\"image-grid\">";
	
	        foreach($results['search'] as $result) {
				// Extra data
				$meta = $links = array();
				if(!empty($result['height']) && !empty($result['width'])) $meta[] = $result['width']."&times;".$result['height'];
				if(!empty($result['filesize'])) $meta[] = $result['filesize'];

				$links[] = "<a href=\"".$result['url']."\" target=\"_blank\">Website</a>";
				if(!empty($result['direct_link'])) $links[] = "<a href=\"".$result['direct_link']."\" target=\"_blank\">Image</a>";

				// Put result together
				echo "<li class=\"result\"><div class=\"image-box\">";
				echo "<a href=\"".$result['url']."\" target=\"_blank\" title=\"".$result['alt']."\"><img src=\"".$result['image']."\" alt=\"".$result['alt']."\" /></a>";
				echo "</div><span>".implode(" - ", $meta)."<br />".implode(" - ", $links)."</span>";
				echo "</li>";
	        }

	        echo "</ol>";
	        echo "</div>";
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