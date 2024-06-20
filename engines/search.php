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
class Search extends EngineRequest {
	protected $requests, $special_request;
	
	public function __construct($opts, $mh) {
		$this->requests = array();
		
		if($opts->enable_duckduckgo == 'on') {
			require ABSPATH.'engines/search/duckduckgo.php';
			$this->requests[] = new DuckDuckGoRequest($opts, $mh);	
		}

		if($opts->enable_google == 'on') {
			require ABSPATH.'engines/search/google.php';
			$this->requests[] = new GoogleRequest($opts, $mh);	
		}

		if($opts->enable_qwant == 'on') {
			require ABSPATH.'engines/search/qwant.php';
			$this->requests[] = new QwantRequest($opts, $mh);	
		}
		
		if($opts->enable_brave == 'on') {
			require ABSPATH.'engines/search/brave.php';
			$this->requests[] = new BraveRequest($opts, $mh);	
		}

		if($opts->enable_wikipedia == 'on') {
			require ABSPATH.'engines/search/wikipedia.php';
			$this->requests[] = new WikiRequest($opts, $mh);	
		}
		
		// Special searches
	    $query_terms = explode(' ', $opts->query);
		$query_terms[0] = strtolower($query_terms[0]);
	
		// Currency converter
		if($opts->special['currency'] == 'on' && count($query_terms) == 4 && (is_numeric($query_terms[0]) && ($query_terms[2] == 'to' || $query_terms[2] == 'in'))) {
	        require ABSPATH.'engines/special/currency.php';
	        $this->special_request = new CurrencyRequest($opts, null);
		}
		
		// Dictionary
		if($opts->special['definition'] == 'on' && count($query_terms) == 2 && ($query_terms[0] == 'define' || $query_terms[0] == 'd' || $query_terms[0] == 'mean' || $query_terms[0] == 'meaning')) {
	        require ABSPATH.'engines/special/definition.php';
	        $this->special_request = new DefinitionRequest($opts, null);
		}
	
		// IP Lookup
		if($opts->special['ipaddress'] == 'on' && ($query_terms[0] == 'ip' || $query_terms[0] == 'myip' || $query_terms[0] == 'ipaddress')) {
	        require ABSPATH.'engines/special/ipify.php';
	        $this->special_request = new ipRequest($opts, null);
		}
		
		// php.net search
		if($opts->special['phpnet'] == 'on' && count($query_terms) == 2 && $query_terms[0] == 'php') {
	        require ABSPATH.'engines/special/php.php';
	        $this->special_request = new PHPnetRequest($opts, null);
		}
		
		unset($query_terms);
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
									$result_urls = array_column($results['search'], 'url', 'id');
									$found_id = array_search($result['url'], $result_urls); // Return the result ID, or false if not found
								} else {
									$found_id = false;
								}
	
								$social_media_multiplier = (is_social_media($result['url'])) ? ($request->opts->social_media_relevance / 10) : 1;
								$goosle_rank = floor($result['engine_rank'] * floatval($social_media_multiplier));
		
								if($found_id !== false) {
									// Duplicate result from another engine
									$results['search'][$found_id]['goosle_rank'] += $goosle_rank;
									$results['search'][$found_id]['combo_source'][] = $engine_result['source'];
								} else {
									// First find, rank and add to results
									// Replace anything but alphanumeric with a space
									$query_terms = explode(' ', preg_replace('/\s{2,}|[^a-z0-9]+/', ' ', strtolower($request->query)));
									$match_rank = match_count($result['title'], $query_terms);
									$match_rank += match_count($result['description'], $query_terms);
									$match_rank += match_count($result['url'], $query_terms);
	
									$result['goosle_rank'] = $goosle_rank + $match_rank;
									$result['combo_source'][] = $engine_result['source'];
									$result['id'] = md5($result['url']);
	
									$results['search'][$result['id']] = $result;
								}
	
								unset($result, $result_urls, $found_id, $social_media_multiplier, $goosle_rank, $match_rank, $query_terms);
							}
						}
					}
				} else {
					$request_result = curl_getinfo($request->ch);
					$http_code_info = ($request_result['http_code'] > 200 && $request_result['http_code'] < 600) ? " - <a href=\"https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/".$request_result['http_code']."\" target=\"_blank\">What's this</a>?" : '';
					$github_issue_url = "https://github.com/adegans/Goosle/discussions/new?category=general&".http_build_query(array('title' => get_class($request).' failed with error '.$request_result['http_code'], 'body' => "```\nEngine: ".get_class($request)."\nError Code: ".$request_result['http_code']."\nRequest url: ".$request_result['url']."\n```", 'labels' => 'request-error'));
	
		            $results['error'][] = array(
		                'message' => "<strong>Ohno! A search query ran into some trouble.</strong> Usually you can try again in a few seconds to get a result!<br /><strong>Engine:</strong> ".get_class($request)."<br /><strong>Error code:</strong> ".$request_result['http_code'].$http_code_info."<br /><strong>Request url:</strong> ".$request_result['url']."<br /><strong>Need help?</strong> Find <a href=\"https://github.com/adegans/Goosle/discussions\" target=\"_blank\">similar issues</a>, or <a href=\"".$github_issue_url."\" target=\"_blank\">ask your own question</a>."
		            );
				}
				
				unset($request);
	        }
		} else {
			$results['error'][] = array(
				'message' => "<strong>Configuration issue!</strong> It appears that all Web Search engines are disabled. Please enable at least one in your config.php file.<br /><strong>Need help?</strong> Find <a href=\"https://github.com/adegans/Goosle/discussions\" target=\"_blank\">similar issues</a>."
			);
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
echo "<pre>Settings: ";
print_r($opts);
echo "</pre>";
echo "<pre>Search results: ";
print_r($results);
echo "</pre>";
*/

		if(array_key_exists('search', $results)) {
			echo "<ul>";

			// Elapsed time
			$number_of_results = count($results['search']);
			echo "<li class=\"timer\">Fetched ".$number_of_results." results in ".$results['time']." seconds.</li>";

			// Format sources
			echo "<li class=\"sources\">".search_sources($results['sources'])."</li>";

			// Did you mean/Search suggestion
			if(array_key_exists('did_you_mean', $results)) {
				echo "<li class=\"suggestion\">Did you mean <a href=\"./results.php?q=".urlencode($results['did_you_mean'])."&t=".$opts->type."&a=".$opts->hash."\">".$results['did_you_mean']."</a>?".search_suggestion($opts, $results)."</li>";
			}

			// Special result
			if(array_key_exists('special', $results)) {
				echo "<li class=\"result-special web\">";
				echo "	<div class=\"title\"><h2>".$results['special']['title']."</h2></div>";
				echo "	<div class=\"description\">".$results['special']['text']."</div>"; // <p> is in the engine files
				if(array_key_exists('source', $results['special'])) {
					echo "	<div class=\"source\"><a href=\"".$results['special']['source']."\" target=\"_blank\">".$results['special']['source']."</a></div>";
				}
				echo "</li>";
			}
		
			// Search results
	        foreach($results['search'] as $result) {
				if($opts->enable_magnet_search == 'on' && $opts->imdb_id_search == 'on') {
					if(stristr($result['url'], 'imdb.com') !== false && preg_match_all('/(?:tt[0-9]+)/i', $result['url'], $imdb_result)) {
						$result['description'] .= "<br /><strong>Goosle detected an IMDb ID for this result, search for <a href=\"./results.php?q=".$imdb_result[0][0]."&a=".$opts->hash."&t=9\" title=\"A Magnet link is a method of downloading movies and tv-shows.\">Magnet links</a>?</strong>";
					}
				}

				echo "<li class=\"result web rank-".$result['goosle_rank']." id-".$result['id']."\">";
				echo "	<div class=\"url\"><a href=\"".$result['url']."\" target=\"_blank\">".get_formatted_url($result['url'])."</a></div>";
				echo "	<div class=\"title\"><a href=\"".$result['url']."\" target=\"_blank\"><h2>".$result['title']."</h2></a></div>";
				echo "	<div class=\"description\">";
				echo "		<p>".$result['description']."</p>";
				echo "	</div>";

				if($opts->show_search_source == 'on') {
					echo "	<div class=\"meta\">";
					echo "		Found on ".replace_last_comma(implode(', ', $result['combo_source'])).".";
					if($opts->show_search_rank == 'on') {
						echo " [rank: ".$result['goosle_rank']."]";
					}
					echo "	</div>";
				}

				echo "</li>";
	        }

			echo "</ul>";
		}

		// Some error occured
        if(array_key_exists('error', $results)) {
	        foreach($results['error'] as $error) {
            	echo "<div class=\"error\">".$error['message']."</div>";
            }
        }
		unset($results);
	}
}
?>