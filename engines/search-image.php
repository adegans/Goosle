<?php
class ImageSearch extends EngineRequest {

	public function get_request_url() {
        $results = array();

		// Split the query
	    $query_terms = explode(" ", strtolower($this->query));

		// Size override
		$size = "";
		if($query_terms[0] == 'size') {
			$switch = explode(":", $query_terms[0]);

			if((strlen($switch[1]) >= 3 && strlen($switch[1]) <= 6) && !is_numeric($switch[1])) {
				if($switch[1] == "med") $switch[1] = "medium";
				if($switch[1] == "lrg") $switch[1] = "large";
	
				if($switch[1] == "small" || $switch[1] == "medium" || $switch[1] == "large") {
					$size = $switch[1];
				}
				
				$this->query = implode(" ", array_slice($query_terms, 1));
			}
		}

		// q = query
		// t = Search type (images)
		// size = Preferred image size (small|medium|large)

		$args = array("q" => $this->query, "t" => "images", "size" => $size);
        $url = "https://lite.qwant.com?".http_build_query($args);

        unset($query_terms, $switch, $args, $size);

        return $url;
	}
	
	public function parse_results($response) {
		$results = array("search" => array());
		$xpath = get_xpath($response);
	
		if(!$xpath) return $results;

		foreach($xpath->query("//a[@rel='noopener']") as $result) {
			$meta = $xpath->evaluate(".//img", $result)[0];

			if($meta) {
				$encoded_url = explode("?position", explode("==/", $result->getAttribute("href"))[1])[0];

				$url = htmlspecialchars(urldecode(base64_decode($encoded_url)));
				$alt_text = get_base_url($url)." - ".htmlspecialchars($meta->getAttribute("alt"));
				$image = urldecode(htmlspecialchars(urlencode($meta->getAttribute("src"))));
				
				// filter duplicate urls/results
	            if(!empty($results)) {
			        $result_urls = array_column($results, "url");
	                if(in_array($url, $result_urls) || in_array(get_base_url($url), $result_urls)) continue;
	            }

				array_push($results["search"], array (
					"alt" => $alt_text,
					"image" => $image,
					"url" => $url,
				));
			}
		}

		// Add warning if there are no results, or a text if there is no search query.
		if(empty($results['search'])) {
			$results["error"] = array(
				"message" => "No results found. Please try with less or different keywords!"
			);
		}
		
		return $results;
	}
	
	public static function print_results($results, $opts) {
		if($opts->raw_output == "on") {
			echo '<pre>Results: ';
			print_r($results);
			echo '</pre>';
		}

		echo "<section class=\"main-column\">";
		echo "<ol>";

		// Elapsed time
		if(array_key_exists("search", $results)) {
			echo "<li class=\"meta-time\">Fetched the results in ".$results['time']." seconds.</li>";
		}

        echo "</ol>";

		echo "<div class=\"image-wrapper\">";
		echo "<ol class=\"image-grid\">";
	
		// Search results
		if(array_key_exists("search", $results)) {
	        foreach($results['search'] as $result) {
				if(!array_key_exists("url", $result) || !array_key_exists("alt", $result)) continue;

				// Put result together
				echo "<li class=\"result\"><div class=\"image-box\">";
				echo "<a href=\"".$result["url"]."\" target=\"_blank\" title=\"".$result["alt"]."\"><img src=\"".$result["image"]."\" alt=\"".$result["alt"]."\" /></a>";
				echo "</div></li>";
	        }
		}

        echo "</ol>";
        echo "</div>";
 		echo "<center><small>Not what you're looking for? Try <a href=\"https://duckduckgo.com/?q=".urlencode($opts->query)."&iax=images&ia=images\" target=\"_blank\">DuckDuckGo</a> or <a href=\"https://www.google.com/search?q=".urlencode($opts->query)."&tbm=isch&pws=0\" target=\"_blank\">Google Images</a>.</small></center>";
 		echo "</section>";
	}
}
?>