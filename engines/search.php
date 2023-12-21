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
class TextSearch extends EngineRequest {
    protected $engine, $engine_request, $special_request;

    public function __construct($opts, $mh) {
        $this->query = $opts->query;
        $this->opts = $opts;

        if($this->opts->type == 0) {            
            require "engines/duckduckgo.php";
            $this->engine_request = new DuckDuckGoRequest($opts, $mh);
        }

        if($this->opts->type == 1) {
            require "engines/google.php";
            $this->engine_request = new GoogleRequest($opts, $mh);
        }

		// Special search
		$this->special_request = special_search_request($opts);
    }

    public function parse_results($response) {
        $results = array();

        // Abort if no results from search engine
        if(!isset($this->engine_request)) return $results;

		// Add search results if there are any, otherwise add error
		if($this->engine_request->request_successful()) {
			$search_result = $this->engine_request->get_results();

			if($search_result) {
				$results = $search_result;
			}

			unset($search_result);
		} else {
            $results['error'] = array(
                "message" => "Error code ".curl_getinfo($this->engine_request->ch)['http_code']." for ".curl_getinfo($this->engine_request->ch)['url'].".<br />Try again in a few seconds or <a href=\"".curl_getinfo($this->engine_request->ch)['url']."\" target=\"_blank\">visit the search engine</a> in a new tab."
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

		// Add error if there are no search results
		if(empty($results)) {
			$results['error'] = array(
				"message" => "No results found. Please try with less or different keywords!"
			);
		}

        return $results;
    }

    public static function print_results($results, $opts)  {
/*
		echo '<pre>Results: ';
		print_r($results);
		echo '</pre>';
*/

		if(array_key_exists("search", $results)) {
			echo "<ol>";

			// Elapsed time
			$number_of_results = count($results['search']);
			echo "<li class=\"meta\">Fetched ".$number_of_results." results in ".$results['time']." seconds.</li>";

			// Did you mean/Search suggestion
			if(array_key_exists("did_you_mean", $results)) {
				$specific_result = "";

				if(array_key_exists("search_specific", $results)) {
					// Add double quotes to Google search
					$search_specific = ($opts->type == 1) ? "\"".$results['search_specific']."\"" : $results['search_specific'];

					// Format query url
					$search_specific_url = "./results.php?q="  . urlencode($search_specific)."&t=".$opts->type."&a=".$opts->hash;
					
					// Specific search
					$specific_result = "<br /><small>Or instead search for <a href=\"".$search_specific_url."\">".$search_specific."</a>.</small>";
		
					unset($search_specific, $search_specific_url);
				}

				$didyoumean_url = "./results.php?q="  . urlencode($results['did_you_mean'])."&t=".$opts->type."&a=".$opts->hash;
	
				echo "<li class=\"meta\">Did you mean <a href=\"".$didyoumean_url."\">".$results['did_you_mean']."</a>?".$specific_result."</li>";
	
				unset($didyoumean_url, $specific_result);
			}

			// Special results
			if($opts->special['imdb_id_search'] == "on") {
				$found = false;
				foreach($results['search'] as $search_result) {
					if(!$found && preg_match_all("/(imdb.com|tt[0-9]+)/i", $search_result['url'], $imdb_result) && stristr($search_result['title'], "tv series") !== false) {
						$results['special'] = array(
							"title" => $search_result['title'], 
							"text" => "Goosle found an IMDb ID for this TV Show in your results (".$imdb_result[0][1].") - <a href=\"./results.php?q=".$imdb_result[0][1]."&a=".$opts->hash."&t=9\">search for magnet links</a>?<br /><sub>An IMDb ID is detected when a TV Show is present in the results. The first match is highlighted here.</sub>"
						);
						$found = true;
					}
				}
			}
			if(array_key_exists("special", $results)) {
				echo "<li class=\"special-result\"><article>";
				// Maybe shorten text
				if(strlen($results['special']['text']) > 1250) {
					$results['special']['text'] = substr($results['special']['text'], 0, strrpos(substr($results['special']['text'], 0, 1300), ". "));
					$results['special']['text'] .= '. <a href="'.$results['special']['source'].'" target="_blank">[...]</a>';
				}
	
				// Add image to text
				if(array_key_exists("image", $results['special'])) {
					$image_specs = getimagesize($results['special']['image']);
					$width = $image_specs[0] / 2;
					$height = $image_specs[1] / 2;
	
					$special_image = "<img src=\"".$results['special']['image']."\" align=\"right\" width=\"".$width."\" height=\"".$height."\" />";
					$results['special']['text'] = $special_image.$results['special']['text'];
	
					unset($image_specs, $width, $height, $special_image);
				}
				echo "<div class=\"title\"><h2>".$results['special']['title']."</h2></div>";
				echo "<div class=\"text\">".$results['special']['text']."</div>";
				if(array_key_exists("source", $results['special'])) {
					echo "<div class=\"source\"><a href=\"".$results['special']['source']."\" target=\"_blank\">".$results['special']['source']."</a></div>";
				}
				echo "</article></li>";
			}

			// Search results
	        foreach($results['search'] as $result) {
		        if(array_key_exists("did_you_mean", $result)) continue;
	
				// Put result together
				echo "<li class=\"result\"><article>";
				echo "<div class=\"url\"><a href=\"".$result['url']."\" target=\"_blank\">".get_formatted_url($result['url'])."</a></div>";
				echo "<div class=\"title\"><a href=\"".$result['url']."\" target=\"_blank\"><h2>".$result['title']."</h2></a></div>";
				echo "<div class=\"description\">".$result['description']."</div>";
				echo "</article></li>";

				unset($result);
	        }

			echo "</ol>";
		}

		// No results found
        if(array_key_exists("error", $results)) {
            echo "<div class=\"error\">".$results['error']['message']."</div>";
        }
    }
}
?>
