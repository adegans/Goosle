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
	
	public function __construct($search, $opts, $mh) {
		$this->requests = array();
		
		if($opts->enable_news_search == 'on') {
			if($opts->enable_qwantnews == 'on') {
				require ABSPATH.'engines/news/qwant-news.php';
				$this->requests[] = new QwantNewsRequest($search, $opts, $mh);	
			}
			
			if($opts->enable_yahoonews == 'on') {
				require ABSPATH.'engines/news/yahoo-news.php';
				$this->requests[] = new YahooNewsRequest($search, $opts, $mh);	
			}
			
			if($opts->enable_bravenews == 'on') {
				require ABSPATH.'engines/news/brave-news.php';
				$this->requests[] = new BraveNewsRequest($search, $opts, $mh);	
			}
			
			if($opts->enable_hackernews == 'on') {
				require ABSPATH.'engines/news/hackernews.php';
				$this->requests[] = new HackernewsRequest($search, $opts, $mh);	
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
							$time = time();

							// Merge duplicates and apply relevance scoring
							foreach($engine_result['search'] as $result) {
								if(isset($goosle_results['search'])) {
									$result_urls = array_column($goosle_results['search'], 'url', 'id');
									$found_id = array_search($result['url'], $result_urls); // Return the result ID, or false if not found
								} else {
									$found_id = false;
								}
	
								$how_many_results++;
								$social_media_multiplier = (is_social_media($result['url'])) ? ($request->opts->social_media_relevance / 10) : 1;
								$goosle_rank = floor($result['engine_rank'] * floatval($social_media_multiplier));
		
								if($found_id !== false) {
									// Duplicate result from another engine
									$goosle_results['search'][$found_id]['goosle_rank'] += $goosle_rank;
									$goosle_results['search'][$found_id]['combo_source'][] = $engine_result['source'];
								} else {
									// First find, rank and add to results
									$match_rank = match_count($result['title'], $request->search->query_terms);
									$match_rank += match_count($result['description'], $request->search->query_terms);
									$match_rank += match_count($result['url'], $request->search->query_terms);

									$time_rank = $time - $result['timestamp'];
									if($time_rank > 21600) { // Less than 6 hours old
										$match_rank += 8; 
									} elseif($time_rank > 86400 && $time_rank < 21600) { // About a day old
										$match_rank += 6; 
									} elseif($time_rank > 604800 && $time_rank < 86400) { // Less than a week old, but more than a day
										$match_rank += 4; 
									} elseif($time_rank > 2592000 && $time_rank < 604800) { // Less than a month old, but more than a week
										$match_rank += 4; 
									} elseif($time_rank > 31536000 && $time_rank < 2592000) { // Less than a year old, but more than a month 
										$match_rank += 2; 
									} else { // More than a year old
										$match_rank += 1; 
									}
	
									$result['goosle_rank'] = $goosle_rank + $match_rank;
									$result['combo_source'][] = $engine_result['source'];
									$result['id'] = md5($result['url']);
	
									// Add result to final results
									$goosle_results['search'][$result['id']] = $result;
								}
	
								unset($result, $result_urls, $found_id, $social_media_multiplier, $goosle_rank, $match_rank, $time_rank, $query_terms);
							}
							
							// Count results per source
							$goosle_results['sources'][$engine_result['source']] = $how_many_results;

							unset($how_many_results, $time);
						}
					}
				} else {
					$request_result = curl_getinfo($request->ch);
					$http_code_info = ($request_result['http_code'] > 200 && $request_result['http_code'] < 600) ? " - <a href=\"https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/".$request_result['http_code']."\" target=\"_blank\">What's this</a>?" : '';
					$github_issue_url = "https://github.com/adegans/Goosle/discussions/new?category=general&".http_build_query(array('title' => get_class($request)." failed with error ".$request_result['http_code'], 'body' => "```\nEngine: ".get_class($request)."\nError Code: ".$request_result['http_code']."\nRequest url: ".$request_result['url']."\n```", 'labels' => 'request-error'));
	
		            $goosle_results['error'][] = array(
		                'message' => "<strong>Ohno! A search query ran into some trouble.</strong> Usually you can try again in a few seconds to get a result!<br /><strong>Engine:</strong> ".get_class($request)."<br /><strong>Error code:</strong> ".$request_result['http_code'].$http_code_info."<br /><strong>Request url:</strong> ".$request_result['url']."<br /><strong>Need help?</strong> Find <a href=\"https://github.com/adegans/Goosle/discussions\" target=\"_blank\">similar issues</a>, or <a href=\"".$github_issue_url."\" target=\"_blank\">ask your own question</a>."
		            );
				}
				
				unset($request);
	        }

			if(array_key_exists('search', $goosle_results)) {
				// Re-order results based on rank
				$keys = array_column($goosle_results['search'], 'goosle_rank');
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
				'message' => "It appears that all News Search engines are disabled or that searching for news is disabled."
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

		if(array_key_exists('search', $goosle_results)) {
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
				$meta = array();
				if(!empty($result['source'])) $meta[] = "<strong>Source:</strong> ".$result['source'];
				if(!empty($result['timestamp'])) $meta[] = "<strong>Posted:</strong> ".the_date("M d, Y", $result['timestamp']);
				if($opts->show_search_source == 'on') $meta[] = replace_last_comma(implode(', ', $result['combo_source']));
				if($opts->show_search_rank == 'on') $meta[] = "Rank: ".$result['goosle_rank'];

				$thumb = (!empty($result['image'])) ? $result['image'] : $opts->pixel;

				echo "<li class=\"result news rank-".$result['goosle_rank']." id-".$result['id']."\">";
				echo "	<div class=\"image\"><a href=\"".$result['url']."\" target=\"_blank\" title=\"".$result['title']."\"><img src=\"".$thumb."\" /></a></div>";
				echo "	<div>";
				echo "		<div class=\"title\"><a href=\"".$result['url']."\" target=\"_blank\"><h2>".$result['title']."</h2></a></div>";
				echo "		<div class=\"description\">";
				echo "			<p><small>".implode(' &bull; ', $meta)."</small></p>";
				echo "			<p>".$result['description']."</p>";
				echo "		</div>";
				echo "	</div>";
				echo "</li>";
				
				unset($meta, $thumb);
	        }

			echo "</ul>";

			// Pagination navigation
			if($opts->cache_type !== 'off' && $goosle_results['number_of_results'] > $opts->search_results_per_page) {
				echo "<p class=\"pagination\">".search_pagination($search, $opts, $goosle_results['number_of_results'])."</p>";
			}
		}

		// Some error occured
        if(array_key_exists('error', $goosle_results)) {
	        foreach($goosle_results['error'] as $error) {
            	echo "<div class=\"error\">".$error['message']."</div>";
            }
        }
		unset($goosle_results);
	}
}
?>