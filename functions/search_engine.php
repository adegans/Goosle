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
abstract class EngineRequest {
    protected $url, $query, $opts, $mh, $ch;

	function __construct($opts, $mh) {
		$this->query = $opts->query;
		$this->mh = $mh;
		// Must be in this order :-/
		$this->opts = $opts;
		$this->url = $this->get_request_url();

		// No search engine url
		if(!$this->url) return;
		
		// Skip if there is a cached result (from earlier search)
		if($this->opts->cache == "on" && has_cached_results($this->opts->cache_type, $this->opts->hash, $this->url, (intval($this->opts->cache_time) * 60))) return;

		// Curl
		$this->ch = curl_init();
		set_curl_options($this->ch, $this->url, $this->opts->user_agents);

		if($mh) curl_multi_add_handle($mh, $this->ch);
	}

	/*--------------------------------------
	// Get search engine url
	--------------------------------------*/
	public function get_request_url() {
		return "";
	}
	
	/*--------------------------------------
	// Check if a request to a search engine was successful
	--------------------------------------*/
	public function request_successful() {
		if((isset($this->ch) && curl_getinfo($this->ch)['http_code'] == '200') || has_cached_results($this->opts->cache_type, $this->opts->hash, $this->url, (intval($this->opts->cache_time) * 60))) {
			return true;
		}			

		return false;
	}
	
	abstract function parse_results($response);

	/*--------------------------------------
	// Load search results
	--------------------------------------*/
	public function get_results() {
		if(!isset($this->url)) {
			return $this->parse_results(null);
		}
		
		$ttl = intval($this->opts->cache_time) * 60;

		// If there is a cached result from an earlier search use that instead
		if($this->opts->cache == "on" && has_cached_results($this->opts->cache_type, $this->opts->hash, $this->url, $ttl)) {
			return fetch_cached_results($this->opts->cache_type, $this->opts->hash, $this->url);
		}

		// Curl request
		if(!isset($this->ch)) {
			return $this->parse_results(null);
		}

		$response = ($this->mh) ? curl_multi_getcontent($this->ch) : curl_exec($this->ch);

		$results = $this->parse_results($response) ?? array();
	
		// Cache last request
		if($this->opts->cache == "on") {
			if(!empty($results)) store_cached_results($this->opts->cache_type, $this->opts->hash, $this->url, $results, $ttl);
			if($this->opts->cache_type == "file") delete_cached_results($ttl);
		}

		return $results;
	}
	
	public static function print_results($results, $opts) {}
}

/*--------------------------------------
// Try to get some search results
--------------------------------------*/
function fetch_search_results($opts) {
    $start_time = microtime(true);

	echo "<section class=\"main-column\">";

	if(!empty($opts->query)) {
		// Curl
	    $mh = curl_multi_init();

		// Load search script
	    if($opts->type == 0) {
	        require ABSPATH."engines/search.php";
	        $search = new Search($opts, $mh);
		} else if($opts->type == 1) {
		    require ABSPATH."engines/search-image.php";
	        $search = new ImageSearch($opts, $mh);
		} else if($opts->type == 9) {
		    require ABSPATH."engines/search-magnet.php";
	        $search = new MagnetSearch($opts, $mh);
	    }
	
	    $running = null;
	
	    do {
	        curl_multi_exec($mh, $running);
	    } while ($running);
	
	    $results = $search->get_results();

		curl_multi_close($mh);
	
		// Add elapsed time to results
		$results['time'] = number_format(microtime(true) - $start_time, 5, '.', '');
	
		// Echoes results and special searches
	    $search->print_results($results, $opts);
	} else {
		echo "<div class=\"warning\">Search query can not be empty!<br />Not sure what went wrong? Learn more about <a href=\"./help.php?a=".$opts->hash."\">how to use Goosle</a>.</div>";
    }

	echo "</section>";
}

/*--------------------------------------
// Process special searches
--------------------------------------*/
function special_search_request($opts) {
	$special_request = null;

    $query_terms = explode(" ", $opts->query);
	$query_terms[0] = strtolower($query_terms[0]);

	// Currency converter
	if($opts->special['currency'] == "on" && count($query_terms) == 4 && (is_numeric($query_terms[0]) && ($query_terms[2] == 'to' || $query_terms[2] == 'in'))) {
        require ABSPATH."engines/special/currency.php";
        $special_request = new CurrencyRequest($opts, null);
	}
	
	// Dictionary
	if($opts->special['definition'] == "on" && count($query_terms) == 2 && ($query_terms[0] == 'define' || $query_terms[0] == 'd' || $query_terms[0] == 'mean' || $query_terms[0] == 'meaning')) {
        require ABSPATH."engines/special/definition.php";
        $special_request = new DefinitionRequest($opts, null);
	}

	// php.net search
	if($opts->special['phpnet'] == "on" && count($query_terms) == 2 && $query_terms[0] == 'php') {
        require ABSPATH."engines/special/php.php";
        $special_request = new PHPnetRequest($opts, null);
	}
	
	return $special_request;
}

/*--------------------------------------
// Process special magnet search features
--------------------------------------*/
function special_magnet_request($opts, $mh) {
	$special_request = array();

	// Latest additions to yts
	if($opts->special['yts'] == "on") {
        require ABSPATH."engines/special/yts_highlights.php";
        $special_request['yts'] = new ytshighlights($opts, $mh);
	}

	if($opts->special['eztv'] == "on") {
        require ABSPATH."engines/special/eztv_highlights.php";
        $special_request['eztv'] = new eztvhighlights($opts, $mh);
	}
	
	return $special_request;
}
?>
