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
				if($switch[1] == "xlrg") $switch[1] = "wallpaper";
	
				if($switch[1] == "small" || $switch[1] == "medium" || $switch[1] == "large" || $switch[1] == "wallpaper") {
					$size = $switch[1];
				}
				
				$this->query = implode(" ", array_slice($query_terms, 1));
			}
		}

		// p = query
		// imgsz = Image size (small|medium|large|wallpaper)

		$args = array("p" => $this->query, "imgsz" => $size);
        $url = "https://images.search.yahoo.com/search/images?".http_build_query($args);

        unset($query_terms, $switch, $args, $size);

        return $url;
	}
	
	public function parse_results($response) {
		$results = array("search" => array());
		$xpath = get_xpath($response);
	
		// Failed to load page
        if(!$xpath) return array();

		// Scrape recommended
		$didyoumean = $xpath->query(".//section[@class='dym-c']/section/h3/a")[0];
		if(!is_null($didyoumean)) {
			$results['did_you_mean'] = $didyoumean->textContent;
		}
        $search_specific = $xpath->query(".//section[@class='dym-c']/section/h5/a")[0];
        if(!is_null($search_specific)) {
			$results['search_specific'] = $search_specific->textContent;
        }
		
		// Scrape the results
		foreach($xpath->query("//li[contains(@class, 'ld') and not(contains(@class, 'slotting'))][position() < 101]") as $result) {
 			$image = $xpath->evaluate(".//img/@src", $result)[0];
            if($image == null) continue;

 			$url_data = $xpath->evaluate(".//a/@href", $result)[0];
            if($url_data == null) continue;

 			// Get meta data
 			// -- Relevant $url_data (there is more, but unused by Goosle)
			// w = Image width (1280)
			// h = Image height (720)
			// imgurl = Actual full size image (Used in Yahoo preview/popup)
			// rurl = Url to page where the image is used
			// size = Image size (413.1KB)
			// tt = Website title (Used for image alt text)
			parse_str($url_data->textContent, $url_data);

			// filter duplicate urls/results
            if(!empty($results['search'])) {
		        $result_urls = array_column($results['search'], "direct_link");
                if(in_array($url_data['imgurl'], $result_urls)) continue;
            }

			// Deal with optional or missing data
			$dimensions_w = (!array_key_exists('w', $url_data) || empty($url_data['w'])) ? 0 : htmlspecialchars($url_data['w']);
			$dimensions_h = (!array_key_exists('h', $url_data) || empty($url_data['h'])) ? 0 : htmlspecialchars($url_data['h']);
			$filesize = (!array_key_exists('size', $url_data) || empty($url_data['size'])) ? "" : htmlspecialchars($url_data['size']);
			$link = (!array_key_exists('imgurl', $url_data) || empty($url_data['imgurl'])) ? "" : "//".htmlspecialchars($url_data['imgurl']);

			array_push($results['search'], array (
				"image" => htmlspecialchars($image->textContent),
				"alt" => htmlspecialchars($url_data['tt']),
				"url" => htmlspecialchars($url_data['rurl']),
				"height" => $dimensions_w,
				"width" => $dimensions_h,
				"filesize" => $filesize,
				"direct_link" => $link
			));
		}

		// Add error if there are no search results
		if(empty($results['search'])) {
			$results['error'] = array(
				"message" => "No results found. Please try with less or different keywords!"
			);
		}
		
		return $results;
	}
	
	public static function print_results($results, $opts) {
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
					// Format query url
					$search_specific = "\"".$results['search_specific']."\"";
					$search_specific_url = "./results.php?q=".urlencode($search_specific)."&t=".$opts->type."&a=".$opts->hash;
					
					// Specific search
					$specific_result = "<br /><small>Or instead search for <a href=\"".$search_specific_url."\">".$search_specific."</a>.</small>";
		
					unset($search_specific, $search_specific_url);
				}

				$didyoumean_url = "./results.php?q=".urlencode($results['did_you_mean'])."&t=".$opts->type."&a=".$opts->hash;
	
				echo "<li class=\"meta\">Did you mean <a href=\"".$didyoumean_url."\">".$results['did_you_mean']."</a>?$specific_result</li>";
	
				unset($didyoumean_url, $specific_result);
			}
			echo "</ol>";

			// Search results
			echo "<div class=\"image-wrapper\">";
			echo "<ol class=\"image-grid\">";
	
	        foreach($results['search'] as $result) {
				$meta = $links = array();

				// Optional data
				if(!empty($result['height']) && !empty($result['width'])) $meta[] = $result['width']."&times;".$result['height'];
				if(!empty($result['filesize'])) $meta[] = $result['filesize'];

				// Links
				$links[] = "<a href=\"".$result['url']."\" target=\"_blank\">Website</a>";
				if(!empty($result['direct_link'])) $links[] = "<a href=\"".$result['direct_link']."\" target=\"_blank\">Image</a>";

				// Put result together
				echo "<li class=\"result\"><div class=\"image-box\">";
				echo "<a href=\"".$result['url']."\" target=\"_blank\" title=\"".$result['alt']."\"><img src=\"".$result['image']."\" alt=\"".$result['alt']."\" /></a>";
				echo "</div><span>".implode(" - ", $meta)."<br />".implode(" - ", $links)."</span>";
				echo "</li>";
				
				unset($result, $meta, $links);
	        }

	        echo "</ol>";
	        echo "</div>";
	 		echo "<center><small>Not what you're looking for? Try <a href=\"https://duckduckgo.com/?q=".urlencode($opts->query)."&iax=images&ia=images\" target=\"_blank\">DuckDuckGo</a>, <a href=\"https://images.search.yahoo.com/search/images?p=".urlencode($opts->query)."\" target=\"_blank\">Yahoo! Images</a> or <a href=\"https://www.google.com/search?q=".urlencode($opts->query)."&tbm=isch&pws=0\" target=\"_blank\">Google Images</a>.</small></center>";
		}

		// No results found
        if(array_key_exists("error", $results)) {
            echo "<div class=\"error\">".$results['error']['message']."</div>";
        }

	}
}
?>