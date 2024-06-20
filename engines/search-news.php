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
class NewsSearch extends EngineRequest {
	protected $requests, $special_request;
	
	public function __construct($opts, $mh) {
		$this->requests = array();
		
		if($opts->enable_qwantnews == 'on') {
			require ABSPATH.'engines/news/qwant-news.php';
			$this->requests[] = new QwantNewsRequest($opts, $mh);	
		}
		
		if($opts->enable_yahoonews == 'on') {
			require ABSPATH.'engines/news/yahoo-news.php';
			$this->requests[] = new YahooNewsRequest($opts, $mh);	
		}
		
		if($opts->enable_bravenews == 'on') {
			require ABSPATH.'engines/news/brave-news.php';
			$this->requests[] = new BraveNewsRequest($opts, $mh);	
		}
		
		if($opts->enable_hackernews == 'on') {
			require ABSPATH.'engines/news/hackernews.php';
			$this->requests[] = new HackernewsRequest($opts, $mh);	
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

									if($result['date_added'] > $request->opts->result_range) {
										$time_rank = time() - $result['date_added'];
										if($time_rank > 7776001) { // More than 3 months old
											$match_rank += 1; 
										} elseif($time_rank > 3888001 && $time_rank < 7776000) {
											$match_rank += 2; 
										} elseif($time_rank > 1209600 && $time_rank < 3888000) {
											$match_rank += 4; 
										} elseif($time_rank > 604801 && $time_rank < 1209600) {
											$match_rank += 6; 
										} elseif($time_rank > 86401 && $time_rank < 604800) {
											$match_rank += 8; 
										} elseif($time_rank > 43201 && $time_rank < 86400) {
											$match_rank += 10; 
										} elseif($time_rank > 32601 && $time_rank < 43200) {
											$match_rank += 12; 
										} else { // Less than 6 hours old
											$match_rank += 14; 
										}
									}
	
									$result['goosle_rank'] = $goosle_rank + $match_rank;
									$result['combo_source'][] = $engine_result['source'];
									$result['id'] = md5($result['url']);
	
									$results['search'][$result['id']] = $result;
								}
	
								unset($result, $result_urls, $found_id, $social_media_multiplier, $goosle_rank, $match_rank, $time_rank, $query_terms);
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
				'message' => "<strong>Configuration issue!</strong> It appears that all Web Search engines are disabled. Please enable at least one in your config.php file.<br /><strong>Need help?</strong> Find <a href=\"https://github.com/adegans/Goosle/discussions\" target=\"_blank\">similar issues</a>."
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

		if(array_key_exists('search', $results)) {
			echo "<ul>";

			// Elapsed time
			$number_of_results = count($results['search']);
			echo "<li class=\"timer\">Fetched ".$number_of_results." results in ".$results['time']." seconds.</li>";

			// Format sources
			echo "<li class=\"sources\">".search_sources($results['sources'])."</li>";

			// Search results
	        foreach($results['search'] as $result) {
				// Extra data
				$base = array();
				if(!empty($result['source'])) $base[] = "<strong>Source:</strong> ".$result['source'];
				if(!empty($result['date_added'])) $base[] = "<strong>Posted:</strong> ".date("M d, Y", $result['date_added']);
				if($opts->show_search_source == 'on') $base[] = replace_last_comma(implode(', ', $result['combo_source']));
				if($opts->show_search_rank == 'on') $base[] = "Rank: ".$result['goosle_rank'];

				echo "<li class=\"result news rank-".$result['goosle_rank']." id-".$result['id']."\">";
				echo "	<div class=\"title\"><a href=\"".$result['url']."\" target=\"_blank\"><h2>".$result['title']."</h2></a></div>";
				echo "	<div class=\"description\">";
				echo "		<p><small>".implode(' &bull; ', $base)."</small></p>";
				if(!empty($result['description'])) echo "		<p>".$result['description']."</p>";
				echo "	</div>";
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