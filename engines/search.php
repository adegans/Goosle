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

		// Add search results
		$success = $this->engine_request->request_successful();
		if($success == "ok") {
			$search_result = $this->engine_request->get_results();

			if($search_result) {
				$results['search'] = $search_result;
			}
			unset($search_result);
		} else {
            $results["error"] = array(
                "message" => $success
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

		// Add warning if there are no results, or a text if there is no search query.
		if(empty($results)) {
			$results["error"] = array(
				"message" => "No results found. Please try with less or different keywords!"
			);
		}

        return $results;
    }

    public static function print_results($results, $opts)  {
		if($opts->raw_output == "on") {
			echo '<pre>Results: ';
			print_r($results);
			echo '</pre>';
		}

		echo "<section class=\"main-column\">";
		echo "<ol>";

		// Elapsed time
		if(array_key_exists("search", $results)) {
			$number_of_results = count($results['search']);
			echo "<li class=\"meta-time\">Fetched ".$number_of_results." results in ".$results['time']." seconds.</li>";
		}

		// No results found
        if(array_key_exists("error", $results)) {
            echo "<li class=\"meta-error\">".$results['error']['message']."</li>";
        }

		// Did you mean/Search suggestion
		if(array_key_exists("search", $results)) {
			$specific_result = "";

			if(array_key_exists("did_you_mean", $results['search'][0])) {
				if(array_key_exists("search_specific", $results['search'][1])) {
					// Add double quotes to Google search
					$search_specific = ($opts->type == 1) ? "\"".$results['search'][1]['search_specific']."\"" : $results['search'][1]['search_specific'];
					$search_specific_url = "./results.php?q="  . urlencode($search_specific)."&t=".$opts->type."&a=".$opts->hash;
					$specific_result = "<br /><small>Or instead search for <a href=\"$search_specific_url\">$search_specific</a>.</small>";
		
					unset($results['search'][1], $search_specific, $search_specific_url);
				}

				$didyoumean = $results['search'][0]['did_you_mean'];
				$didyoumean_url = "./results.php?q="  . urlencode($didyoumean)."&t=".$opts->type."&a=".$opts->hash;
	
				echo "<li class=\"meta-did-you-mean\">Did you mean <a href=\"$didyoumean_url\">$didyoumean</a>?$specific_result</li>";
	
				unset($results['search'][0], $didyoumean, $didyoumean_url, $specific_result);
			}
		}

		// Special result
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
		if(array_key_exists("search", $results)) {
	        foreach($results['search'] as $result) {
		        if(array_key_exists("did_you_mean", $result)) continue;
	
				// Put result together
				echo "<li class=\"result\"><article>";
				echo "<div class=\"url\"><a href=\"".$result["url"]."\" target=\"_blank\">".get_formatted_url($result["url"])."</a></div>";
				echo "<div class=\"title\"><a href=\"".$result["url"]."\" target=\"_blank\"><h2>".$result["title"]."</h2></a></div>";
				echo "<div class=\"description\">".$result["description"]."</div>";
				echo "</article></li>";
	        }
		}
 
        echo "</ol>";
        echo "</section>";
    }
}
?>
