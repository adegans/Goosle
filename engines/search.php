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

	public function __construct($search, $opts, $mh) {
		$this->requests = array();

		if($opts->enable_web_search == 'on') {
			if($opts->web['duckduckgo'] == 'on' && !has_timeout('DuckDuckGoRequest')) {
				require ABSPATH.'engines/search/duckduckgo.php';
				$this->requests[] = new DuckDuckGoRequest($search, $opts, $mh);
			}

			if($opts->web['mojeek'] == 'on' && !has_timeout('MojeekRequest')) {
				require ABSPATH.'engines/search/mojeek.php';
				$this->requests[] = new MojeekRequest($search, $opts, $mh);
			}

			if($opts->web['google'] == 'on' && !has_timeout('GoogleRequest')) {
				require ABSPATH.'engines/search/google.php';
				$this->requests[] = new GoogleRequest($search, $opts, $mh);
			}

			if($opts->web['qwant'] == 'on' && !has_timeout('QwantRequest')) {
				require ABSPATH.'engines/search/qwant.php';
				$this->requests[] = new QwantRequest($search, $opts, $mh);
			}

			if($opts->web['brave'] == 'on' && !has_timeout('BraveRequest')) {
				require ABSPATH.'engines/search/brave.php';
				$this->requests[] = new BraveRequest($search, $opts, $mh);
			}

			if($opts->web['wikipedia'] == 'on' && !has_timeout('WikiRequest')) {
				require ABSPATH.'engines/search/wikipedia.php';
				$this->requests[] = new WikiRequest($search, $opts, $mh);
			}
		}

		/* --- SPECIAL SEARCHES --- */

		// Currency converter
		if($opts->special['currency'] == 'on') {
			if($search->count_terms == 4 && (is_numeric($search->query_terms[0]) && ($search->query_terms[2] == 'to' || $search->query_terms[2] == 'in'))) {
		        require ABSPATH.'engines/special/currency.php';
		        $this->special_request = new CurrencyRequest($search, $opts, $mh);
		    }
		}

		// Dictionary
		if($opts->special['definition'] == 'on') {
			if($search->count_terms == 2 && ($search->query_terms[0] == 'def' || $search->query_terms[0] == 'define' || $search->query_terms[0] == 'meaning')) {
		        require ABSPATH.'engines/special/definition.php';
				$this->special_request = new DefinitionRequest($search, $opts, $mh);
			}
		}

		// IP Lookup
		if($opts->special['ipaddress'] == 'on') {
			if($search->count_terms == 1 && ($search->query_terms[0] == 'ip' || $search->query_terms[0] == 'myip' || $search->query_terms[0] == 'ipaddress')) {
		        require ABSPATH.'engines/special/ipify.php';
				$this->special_request = new ipRequest($search, $opts, $mh);
			}
		}

		// php.net search
		if($opts->special['phpnet'] == 'on') {
			if($search->count_terms == 2 && $search->query_terms[0] == 'php') {
		        require ABSPATH.'engines/special/php.php';
				$this->special_request = new PHPnetRequest($search, $opts, $mh);
			}
		}

		// wordpress.org search
		if($opts->special['wordpress'] == 'on') {
			if(($search->count_terms == 2 && ($search->query_terms[0] == 'wordpress' || $search->query_terms[0] == 'wp')) || ($search->count_terms == 3 && ($search->query_terms[0] == 'wordpress' || $search->query_terms[0] == 'wp') && $search->query_terms[1] == 'hook')) {
		        require ABSPATH.'engines/special/wordpress.php';
				$this->special_request = new WordPressRequest($search, $opts, $mh);
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
						if(isset($engine_result['did_you_mean'])) {
							$goosle_results['did_you_mean'][] = $engine_result['did_you_mean'];
						}

						if(isset($engine_result['search'])) {
							$how_many_results = 0;

							// Merge duplicates and apply relevance scoring
							foreach($engine_result['search'] as $result) {
								if(isset($goosle_results['search'])) {
									$result_urls = array_column($goosle_results['search'], 'url', 'id');
									$found_id = array_search($result['url'], $result_urls); // Return the result ID, or false if not found
								} else {
									$found_id = false;
								}

								$how_many_results++;
								$social_media_multiplier = (detect_social_media($result['url'])) ? ($request->opts->social_media_relevance / 10) : 1;
								$goosle_rank = floor($result['engine_rank'] * $social_media_multiplier);

								if($found_id !== false) {
									// Duplicate result from another engine
									$goosle_results['search'][$found_id]['goosle_rank'] += $goosle_rank;
									$goosle_results['search'][$found_id]['combo_source'][] = $engine_result['source'];
								} else {
									// First find, rank and add to results
									$match_rank = 0;
									$match_rank += match_count($result['title'], $request->search->query_terms);
									$match_rank += match_count($result['description'], $request->search->query_terms, 2);;
									$match_rank += match_count($result['url'], $request->search->query_terms, 0.5);

									$result['goosle_rank'] = $goosle_rank + $match_rank;
									$result['combo_source'][] = $engine_result['source'];
									$result['id'] = md5($result['url']);

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
					$http_code_info = ($request_result['http_code'] > 200 && $request_result['http_code'] < 600) ? " - <a href=\"https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/".$request_result['http_code']."\" target=\"_blank\">What's this</a>?" : '';
					$github_issue_url = "https://github.com/adegans/Goosle/discussions/new?category=general&".http_build_query(array('title' => get_class($request).' failed with error '.$request_result['http_code'], 'body' => "```\nEngine: ".get_class($request)."\nError Code: ".$request_result['http_code']."\nRequest url: ".$request_result['url']."\n```", 'labels' => 'request-error'));

		            $goosle_results['error'][] = array(
		                'message' => "<strong>Ohno! A search query ran into some trouble.</strong> Usually you can try again in a few seconds to get a result!<br /><strong>Engine:</strong> ".get_class($request)."<br /><strong>Error code:</strong> ".$request_result['http_code'].$http_code_info."<br /><strong>Request url:</strong> ".$request_result['url']."<br /><strong>Need help?</strong> Find <a href=\"https://github.com/adegans/Goosle/discussions\" target=\"_blank\">similar issues</a>, or <a href=\"".$github_issue_url."\" target=\"_blank\">ask your own question</a>."
		            );
				}

				unset($request);
	        }

			// Check for Special result
	        if($this->special_request) {
	            $special_result = $this->special_request->get_results();

	            if($special_result) {
					$goosle_results['special'] = $special_result;
	            }

				unset($special_result);
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
				'message' => "It appears that all Web Search engines are disabled. Please enable at least one in your config.php file."
			);
		}

        return $goosle_results;
    }

    public static function print_results($goosle_results, $search, $opts) {
// Uncomment for debugging
/*
echo "<pre>Settings: ";
print_r($opts);
echo "</pre>";
echo "<pre>Search data: ";
print_r($search);
echo "</pre>";
echo "<pre>Search results: ";
print_r($goosle_results);
echo "</pre>";
*/

		if(array_key_exists('search', $goosle_results)) {
			// Pagination offset
			if($opts->cache_type !== 'off') {
				$offset = ((($search->page - 1) * $opts->search_results_per_page));
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

			// Search suggestions
			if(array_key_exists('did_you_mean', $goosle_results)) {
				echo "<li class=\"meta\">";
				echo "	<p class=\"didyoumean\">".search_suggestion($search->type, $opts->hash, $goosle_results['did_you_mean'])."</p>";
				echo "	<p class=\"suggestion\">Or instead search for <a href=\"./results.php?q=%22".urlencode($search->query)."%22&t=".$search->type."&a=".$opts->hash."\">\"".$search->query."\"</a></p>";
				echo "</li>";
			}

			// Special result
			if(array_key_exists('special', $goosle_results)) {
				echo "<li class=\"result-special web\">";
				echo "	<div class=\"title\"><h2>".$goosle_results['special']['title']."</h2></div>";
				echo "	<div class=\"description\">".$goosle_results['special']['text']."</div>"; // <p> is in the engine files
				echo "	<div class=\"source\"><a href=\"".$goosle_results['special']['source']."\" target=\"_blank\">".$goosle_results['special']['source']."</a></div>";
				if(array_key_exists('note', $goosle_results['special'])) {
					echo "	<div class=\"note\"><small><em>".$goosle_results['special']['note']."</em></small></div>";
				}
				echo "</li>";
			}

			// Search results
	        foreach($goosle_results['search'] as $result) {
				// Extra data
				$meta = array();
				if($opts->show_search_source == 'on') $meta[] = replace_last_comma(implode(', ', $result['combo_source'])).".";
				if($opts->show_search_rank == 'on') $meta[] = "Rank: ".$result['goosle_rank'];

				echo "<li class=\"result web rank-".$result['goosle_rank']." id-".$result['id']."\">";
				echo "	<div class=\"url\"><a href=\"".$result['url']."\" target=\"_blank\">".search_formatted_url($result['url'])."</a></div>";
				echo "	<div class=\"title\"><a href=\"".$result['url']."\" target=\"_blank\"><h2>".$result['title']."</h2></a></div>";
				echo "	<div class=\"description\">";
				echo "		<p>".$result['description']."</p>";
				if($opts->enable_magnet_search == 'on' && $opts->imdb_id_search == 'on') {
					if(stristr($result['url'], 'imdb.com') !== false && preg_match_all('/(?:tt[0-9]+)/i', $result['url'], $imdb_result)) {
						echo "		<p><strong>Goosle detected an IMDb ID for this result, search for <a href=\"./results.php?q=".$imdb_result[0][0]."&a=".$opts->hash."&t=9\" title=\"Search for Magnet links\">Magnet links</a>?</strong> <a onclick=\"openpopup('info-magnetresult')\" title=\"Click for more information\"><span class=\"tooltip-question\"></span></a></p>";
					}
				}

				echo "		<p><small>".implode(' &bull; ', $meta)."</small></p>";
				echo "	</div>";
				echo "</li>";
				unset($meta);
	        }

			echo "</ul>";

			// Pagination navigation
			if($opts->cache_type !== 'off' && $goosle_results['number_of_results'] > $opts->search_results_per_page) {
				echo "<p class=\"pagination\">".search_pagination($search, $opts, $goosle_results['number_of_results'])."</p>";
			}

			// Popup (Normally hidden)
			echo "<div id=\"info-magnetresult\" class=\"goosebox\">";
			echo "	<div class=\"goosebox-body\">";
			echo "		<h2>Magnet links</h2>";
			echo "		<p>A Magnet link is a special link that torrent clients can use to find and download software, music, movies and tv-shows.</p>";
			echo "		<p>Magnet links are part of the Magnet Search function. You'll need a Bittorrent client that accepts Magnet links in order to use these search results. You can find more information about how to use Magnet Search on the <a href=\"./help.php?a=".$opts->hash."\">Help page</a>.</p>";
			echo "		<p><a onclick=\"closepopup()\">Close</a></p>";
			echo "	</div>";
			echo "</div>";
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
