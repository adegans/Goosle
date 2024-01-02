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
		require "engines/search/duckduckgo.php";
		require "engines/search/google.php";
		require "engines/search/wikipedia.php";
		require "engines/search/ecosia.php";
		
		$this->requests = array(
			new DuckDuckGoRequest($opts, $mh),
			new GoogleRequest($opts, $mh),
			new WikiRequest($opts, $mh),
			new EcosiaRequest($opts, $mh),
		);

		// Special search
		$this->special_request = special_search_request($opts);
	}

    public function parse_results($response) {
        $results = array();

		// Merge all results together
        foreach($this->requests as $request) {
			if($request->request_successful()) {
				$engine_result = $request->get_results();

				if(!empty($engine_result)) {
					if(array_key_exists('search', $engine_result)) {

						if(array_key_exists('did_you_mean', $engine_result)) {
							$results['did_you_mean'] = $engine_result['did_you_mean'];
						}
						
						if(array_key_exists('search_specific', $engine_result)) {
							$results['search_specific'][] = $engine_result['search_specific'];
						}
	
						$query_terms = explode(" ", preg_replace("/[^a-z0-9 ]+/", "", strtolower($request->query)));

						// Merge duplicates and apply relevance scoring
						foreach($engine_result['search'] as $result) {
							if(array_key_exists('search', $results)) {
								$result_urls = array_column($results['search'], "url", "id");
								$found_key = array_search($result['url'], $result_urls);
							} else {
								$found_key = false;
							}

							$social_media_multiplier = (is_social_media($result['url'])) ? ($request->opts->social_media_relevance / 10) : 1;
							$goosle_rank = floor($result['engine_rank'] * floatval($social_media_multiplier));
	
							if($found_key !== false) {
								// Duplicate result from another source, merge and rank accordingly
								$results['search'][$found_key]['goosle_rank'] += $goosle_rank;
								$results['search'][$found_key]['combo_source'][] = $result['source'];
							} else {
								// First find, rank and add to results
								$match_rank = match_count($result['title'], $query_terms);
								$match_rank += match_count($result['description'], $query_terms);
//								$match_rank += match_count($result['url'], $query_terms);

								$result['goosle_rank'] = $goosle_rank + $match_rank;
								$result['combo_source'][] = $result['source'];

								$results['search'][$result['id']] = $result;
							}
	
							unset($result, $result_urls, $found_key, $social_media_multiplier, $goosle_rank, $match_rank);
						}
					}
				}
			} else {
				$request_result = curl_getinfo($request->ch);
				$http_code_info = ($request_result['http_code'] > 200 && $request_result['http_code'] < 600) ? " - <a href=\"https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/".$request_result['http_code']."\" target=\"_blank\">What's this</a>?" : "";
				
	            $results['error'][] = array(
	                "message" => "<strong>Ohno! A search query ran into some trouble.</strong> Usually you can try again in a few seconds to get a result!<br /><strong>Engine:</strong> ".get_class($request)."<br /><strong>Error code:</strong> ".$request_result['http_code'].$http_code_info."<br /><strong>Request url:</strong> ".$request_result['url']."."
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
	        search_sources($results['sources']);

			// Did you mean/Search suggestion
			search_suggestion($opts, $results);

			// Special results
			special_search_result($opts, $results);

			// Search results
	        foreach($results['search'] as $result) {
				echo "<li class=\"result rs-".$result['goosle_rank']." id-".$result['id']."\"><article>";
				echo "<div class=\"url\"><a href=\"".$result['url']."\" target=\"_blank\">".get_formatted_url($result['url'])."</a></div>";
				echo "<div class=\"title\"><a href=\"".$result['url']."\" target=\"_blank\"><h2>".$result['title']."</h2></a></div>";
				echo "<div class=\"description\">".$result['description']."</div>";
				if($opts->show_search_source == "on") {
					echo "<div class=\"engine\">";
					echo "Found on ".replace_last_comma(implode(", ", $result['combo_source'])).".";
					if($opts->show_search_rank == "on") echo " [rank: ".$result['goosle_rank']."]";
					echo "</div>";
				}
				echo "</article></li>";
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