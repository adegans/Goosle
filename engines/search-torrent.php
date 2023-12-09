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
class TorrentSearch extends EngineRequest {
	protected $requests;
	
	public function __construct($opts, $mh) {
        $this->opts = $opts;
		$this->url = 'torrent'; // Dummy value to satisfy EngineRequest::get_results()

		require "engines/torrent/1337x.php";
		require "engines/torrent/nyaa.php";
		require "engines/torrent/thepiratebay.php";
		require "engines/torrent/yts.php";
		
		$this->requests = array(
			new LeetxRequest($opts, $mh), // 1337x
			new NyaaRequest($opts, $mh),
			new PirateBayRequest($opts, $mh),
			new YTSRequest($opts, $mh)
		);
	}

    public function parse_results($response) {
        $results = $results_temp = array();

        foreach ($this->requests as $request) {
            if($request->request_successful()) {
                $results_temp = array_merge($results_temp, $request->get_results());
            }
        }

		if(count($results_temp) > 0) {
			// Ensure highest seeders are shown first
	        $seeders = array_column($results_temp, "seeders");
	        array_multisort($seeders, SORT_DESC, $results_temp);
	
			// Cap results
			$results['search'] = array_slice($results_temp, 0, 50);
			unset($results_temp);
		}

		// Add warning if there are no results
        if(empty($results)) {
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
		echo "<li class=\"meta-time\">Fetched the results in ".$results['time']." seconds.</li>";

		// No results found
        if(array_key_exists("error", $results)) {
            echo "<li class=\"meta-error\">".$results['error']['message']."</li>";
        }

		// Search results
		if(array_key_exists("search", $results)) {
			foreach($results['search'] as $result) {
				$meta = array();
				// Optional data
				if(array_key_exists('quality', $result)) $meta[] = "<strong>Quality:</strong> ".$result['quality'];
				if(array_key_exists('year', $result)) $meta[] = "<strong>Year:</strong> ".$result['year'];
				if(array_key_exists('category', $result)) $meta[] = "<strong>Category:</strong> ".$result['category'];
				if(array_key_exists('runtime', $result)) $meta[] = "<strong>Runtime:</strong> ".date('H:i', mktime(0, $result['runtime']));
				if(array_key_exists('date_added', $result)) $meta[] = "<strong>Added:</strong> ".date('M d, Y', $result['date_added']);
				if(array_key_exists('url', $result)) $meta[] = "<a href=\"".$result["url"]."\" target=\"_blank\" title=\"Careful - Site may contain intrusive popup ads and malware!\">Torrent page</a>";
	
				// Put result together
				echo "<li class=\"result\"><article>";
				echo "<div class=\"url\"><a href=\"".$result["magnet"]."\">".$result["source"]."</a></div>";
				echo "<div class=\"title\"><a href=\"".$result["magnet"]."\"><h2>".stripslashes($result["name"])."</h2></a></div>";
				echo "<div class=\"description\"><strong>Seeds:</strong> <span class=\"seeders\">".$result['seeders']."</span> - <strong>Peers:</strong> <span class=\"leechers\">".$result['leechers']."</span> - <strong>Size:</strong> ".$result['size']."<br />".implode(" - ", $meta)."</div>";
				echo "</article></li>";
			}
		}

		echo "<li class=\"result\"><article>";
		echo "<small>Goosle does not store, index, offer or distribute torrent files.</small>";
		echo "</article></li>";

		echo "</ol>";
		echo "<center><small>Showing 50 results, sorted by most seeders.</small></center>";
		echo "</section>";
	}
}
?>