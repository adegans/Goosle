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
class Search extends EngineRequest {
	protected $requests, $special_request;
	
	public function __construct($opts, $mh) {
		$this->requests = array();
		
		if($opts->enable_duckduckgo == "on") {
			require ABSPATH."engines/search/duckduckgo.php";
			$this->requests[] = new DuckDuckGoRequest($opts, $mh);	
		}

		if($opts->enable_google == "on") {
			require ABSPATH."engines/search/google.php";
			$this->requests[] = new GoogleRequest($opts, $mh);	
		}

		if($opts->enable_qwantnews == "on") {
			require ABSPATH."engines/search/qwantnews.php";
			$this->requests[] = new QwantNewsRequest($opts, $mh);	
		}
		
		if($opts->enable_wikipedia == "on") {
			require ABSPATH."engines/search/wikipedia.php";
			$this->requests[] = new WikiRequest($opts, $mh);	
		}
		
		// Special search
		$this->special_request = special_search_request($opts);
	}

    public function parse_results($response) {
        $results = array();

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
								$result_urls = array_column($results['search'], "url", "id");
								$found_id = array_search($result['url'], $result_urls);
							} else {
								$found_id = false;
							}

							$social_media_multiplier = (is_social_media($result['url'])) ? ($request->opts->social_media_relevance / 10) : 1;
							$goosle_rank = floor($result['engine_rank'] * floatval($social_media_multiplier));
	
							if($found_id !== false) {
								// Duplicate result from another source, merge and rank accordingly
								$results['search'][$found_id]['goosle_rank'] += $goosle_rank;
								$results['search'][$found_id]['combo_source'][] = $result['source'];
							} else {
								// First find, rank and add to results
								$query_terms = explode(" ", preg_replace("/[^a-z0-9 ]+/", "", strtolower($request->query)));
								$match_rank = match_count($result['title'], $query_terms);
								$match_rank += match_count($result['description'], $query_terms);
//								$match_rank += match_count($result['url'], $query_terms);

								$result['goosle_rank'] = $goosle_rank + $match_rank;
								$result['combo_source'][] = $result['source'];

								$results['search'][$result['id']] = $result;
							}
	
							unset($result, $result_urls, $found_id, $social_media_multiplier, $goosle_rank, $match_rank);
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

		// Check for Special result
        if($this->special_request) {
            $special_result = $this->special_request->get_results();

            if($special_result) {
				$results['special'] = $special_result;
            }

			unset($special_result);
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
			echo "<li class=\"sources\">Includes ".search_sources($results['sources'])."</li>";

			// Did you mean/Search suggestion
			if(array_key_exists("did_you_mean", $results)) {
				echo "<li class=\"suggestion\">Did you mean <a href=\"./results.php?q=".urlencode($results['did_you_mean'])."&t=".$opts->type."&a=".$opts->hash."\">".$results['did_you_mean']."</a>?".search_suggestion($opts, $results)."</li>";
			}

			// Special results
			if(array_key_exists("special", $results)) {
				echo "<li class=\"result-special\">";
				echo "<div class=\"title\"><h2>".$results['special']['title']."</h2></div>";
				echo "<div class=\"text\">".$results['special']['text']."</div>";
				if(array_key_exists("source", $results['special'])) {
					echo "<div class=\"source\"><a href=\"".$results['special']['source']."\" target=\"_blank\">".$results['special']['source']."</a></div>";
				}
				echo "</li>";
			}
		
			// Search results
	        foreach($results['search'] as $result) {
				if($opts->imdb_id_search == "on") {
					if(stristr($result['url'], "imdb.com") !== false && preg_match_all("/(?:tt[0-9]+)/i", $result['url'], $imdb_result)) {
						$result['description'] = $result['description']."<br /><strong>Goosle detected an IMDb ID for this result, search for <a href=\"./results.php?q=".$imdb_result[0][0]."&a=".$opts->hash."&t=9\">magnet links</a>?</strong>";
					}
				}

				echo "<li class=\"result web rs-".$result['goosle_rank']." id-".$result['id']."\">";
				echo "<div class=\"url\"><a href=\"".$result['url']."\" target=\"_blank\">".get_formatted_url($result['url'])."</a></div>";
				echo "<div class=\"title\"><a href=\"".$result['url']."\" target=\"_blank\"><h2>".$result['title']."</h2></a></div>";
				echo "<div class=\"description\">".$result['description']."</div>";

				if($opts->show_search_source == "on") {
					echo "<div class=\"engine\">";
					echo "Found on ".replace_last_comma(implode(", ", $result['combo_source'])).".";
					if($opts->show_search_rank == "on") echo " [rank: ".$result['goosle_rank']."]";
					echo "</div>";
				}

				echo "</li>";
	        }

			echo "</ol>";
		}

		// Some error occured
        if(array_key_exists("error", $results)) {
	        foreach($results['error'] as $error) {
            	echo "<div class=\"error\">".$error['message']."</div>";
            }
        }
		unset($results);
	}
}
?>