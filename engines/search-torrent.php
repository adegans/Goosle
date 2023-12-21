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
		require "engines/torrent/eztv.php";
		
		$this->requests = array(
			new LeetxRequest($opts, $mh), // 1337x
			new NyaaRequest($opts, $mh),
			new PirateBayRequest($opts, $mh),
			new YTSRequest($opts, $mh),
			new EZTVRequest($opts, $mh)
		);
	}

    public function parse_results($response) {
        $results = $results_temp = array();

        foreach($this->requests as $request) {
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

			// Count results per site
			$sources = array_count_values(array_column($results['search'], 'source'));
			if(count($sources) > 0) $results['sources'] = $sources;

			unset($sources);
		}

		unset($results_temp);

		// Add error if there are no search results
        if(empty($results)) {
            $results['error'] = array(
                "message" => "No results found. Please try with more specific or different keywords!" 
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

			// Format sources
			$sources = "";
	        if(array_key_exists("sources", $results)) {
				$sources = array();
				foreach($results['sources'] as $source => $amount) {
					$plural = ($amount > 1) ? "results" : "result";
					$sources[] = $amount." ".$plural." from ".$source;
				}
				$sources = implode(', ', $sources);
	
			    $last_comma = strrpos($sources, ', ');
			    if($last_comma !== false) {
			        $sources = substr_replace($sources, ' and ', $last_comma, 2);
			    }

				$sources = "<br /><small>Including ".$sources.". Links with the most seeders are listed first.</small>";
			}

			// Elapsed time
			$number_of_results = count($results['search']);
			echo "<li class=\"meta\">Fetched ".$number_of_results." results in ".$results['time']." seconds.".$sources."</li>";

			// Search results
			foreach($results['search'] as $result) {
				$meta = array();

				// Optional data
				if(array_key_exists('quality', $result)) $meta[] = "<strong>Quality:</strong> ".$result['quality'];
				if(array_key_exists('year', $result)) $meta[] = "<strong>Year:</strong> ".$result['year'];
				if(array_key_exists('category', $result)) $meta[] = "<strong>Category:</strong> ".$result['category'];
				if(array_key_exists('runtime', $result)) $meta[] = "<strong>Runtime:</strong> ".date('H:i', mktime(0, $result['runtime']));
				if(array_key_exists('date_added', $result)) $meta[] = "<strong>Added:</strong> ".date('M d, Y', $result['date_added']);
				if(array_key_exists('url', $result)) $meta[] = "<a href=\"".$result['url']."\" target=\"_blank\" title=\"Careful - Site may contain intrusive popup ads and malware!\">Torrent page</a>";
	
				// Put result together
				echo "<li class=\"result\"><article>";
				echo "<div class=\"url\"><a href=\"".$result['magnet']."\">".$result['source']."</a></div>";
				echo "<div class=\"title\"><a href=\"".$result['magnet']."\"><h2>".stripslashes($result['name'])."</h2></a></div>";
				echo "<div class=\"description\"><strong>Seeds:</strong> <span class=\"seeders\">".$result['seeders']."</span> - <strong>Peers:</strong> <span class=\"leechers\">".$result['leechers']."</span> - <strong>Size:</strong> ".$result['size']."<br />".implode(" - ", $meta)."</div>";
				echo "</article></li>";

				unset($result, $meta);
			}

			echo "</ol>";
			echo "<center><small>Showing up to 50 results, sorted by most seeders.<br />Goosle does not index, offer or distribute torrent files.</small></center>";
		}

		// No results found
        if(array_key_exists("error", $results)) {
            echo "<div class=\"error\">".$results['error']['message']."</div>";
        }
	}
}
?>