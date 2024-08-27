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

	public function __construct($search, $opts, $mh) {
		$this->requests = array();

		if($opts->enable_image_search == 'on') {
			if($opts->image['yahooimages'] == 'on' && !has_timeout('YahooImageRequest')) {
				require ABSPATH.'engines/image/yahoo-images.php';
				$this->requests[] = new YahooImageRequest($search, $opts, $mh);
			}

			if($opts->image['qwantimages'] == 'on' && !has_timeout('QwantImageRequest')) {
				require ABSPATH.'engines/image/qwant-images.php';
				$this->requests[] = new QwantImageRequest($search, $opts, $mh);
			}

			if($opts->image['pixabay'] == 'on' && !has_timeout('PixabayRequest')) {
				require ABSPATH.'engines/image/pixabay.php';
				$this->requests[] = new PixabayRequest($search, $opts, $mh);
			}

			if($opts->image['openverse'] == 'on' && !has_timeout('OpenverseRequest')) {
				require ABSPATH.'engines/image/openverse.php';
				$this->requests[] = new OpenverseRequest($search, $opts, $mh);
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
							$goosle_results['did_you_mean'] = $engine_result['did_you_mean'];
						}

						if(isset($engine_result['search_specific'])) {
							$goosle_results['search_specific'][] = $engine_result['search_specific'];
						}

						if(isset($engine_result['search'])) {
							$how_many_results = 0;

							// Merge duplicates and apply relevance scoring
							foreach($engine_result['search'] as $key => $result) {
								if(isset($goosle_results['search'])) {
									$result_urls = array_column($goosle_results['search'], 'image_full', 'id');
									$found_id = array_search($result['image_full'], $result_urls); // Return the result ID, or false if not found
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
									$match_rank += match_count($result['alt'], $request->search->query_terms, 1.5);
									$match_rank += match_count($result['url'], $request->search->query_terms, 0.5);

									$result['goosle_rank'] = $goosle_rank + $match_rank;
									$result['combo_source'][] = $engine_result['source'];
									$result['id'] = md5($result['image_full']);

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
	                "message" => "No results found. Please try with more specific or different keywords!"
	            );
			}
		} else {
			$goosle_results['error'][] = array(
				'message' => "It appears that all Image Search engines are disabled or that searching for images is disabled."
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

			echo "</ul>";

			// Search results
			echo "<ul class=\"result-grid\">";

	        foreach($goosle_results['search'] as $result) {
				// Put result together
				echo "<li class=\"result image rank-".$result['goosle_rank']." id-".$result['id']."\">";
				echo "	<div class=\"thumb\">";
				echo "		<a href=\"".$result['url']."\" target=\"_blank\" title=\"".$result['alt']."\"><img src=\"".$result['image_thumb']."\" alt=\"".$result['alt']."\" /></a>";
				echo "	</div>";
				echo "	<div class=\"meta\">";
				if(!empty($result['height']) && !empty($result['width'])) {
					echo "		<p>".$result['width']."&times;".$result['height']."</p>";
				}
				echo "		<p><a href=\"".$result['url']."\" target=\"_blank\" title=\"Open website\">Website</a> &bull; <a href=\"".$result['image_full']."\" target=\"_blank\" title=\"Open image\">Image</a></p>";
				if($opts->show_search_rank == 'on') echo "		<p>Rank: ".$result['goosle_rank']."</p>";
				echo "	</div>";
				echo "</li>";

				unset($meta);
	        }

	        echo "</ul>";

			// Pagination navigation
			if($opts->cache_type !== 'off' && $goosle_results['number_of_results'] > $opts->search_results_per_page) {
				echo "<p class=\"pagination\">".search_pagination($search, $opts, $goosle_results['number_of_results'])."</p>";
			}

			echo "<p class=\"text-center\"><small>Goosle does not store or distribute image files. Images may be subject to copyright.</small></p>";
		}

		// No results found
        if(array_key_exists("error", $goosle_results)) {
	        foreach($goosle_results['error'] as $error) {
            	echo "<div class=\"error\">".$error['message']."</div>";
            }
        }

		unset($goosle_results);
	}
}
?>
