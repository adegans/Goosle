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
		if($this->opts->cache == "on" && has_cached_results($this->url, $this->opts->hash)) return;
		
		// Curl
		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_URL, $this->url);
		curl_setopt($this->ch, CURLOPT_HTTPGET, 1); // Redundant? Probably...
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_VERBOSE, false);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_USERAGENT, $this->opts->user_agents[array_rand($this->opts->user_agents)]);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
		    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		    'Accept-Language: en-US,en;q=0.5',
		    'Upgrade-Insecure-Requests: 1'
		));
		curl_setopt($this->ch, CURLOPT_ENCODING, "gzip,deflate");
		curl_setopt($this->ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_WHATEVER);
		curl_setopt($this->ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
		curl_setopt($this->ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
		curl_setopt($this->ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		
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
		if((isset($this->ch) && curl_getinfo($this->ch)['http_code'] == '200') || has_cached_results($this->url, $this->opts->hash)) {
			return true;
		}			

		return false;
	}
	
	abstract function parse_results($response);

	/*--------------------------------------
	// Load search results
	--------------------------------------*/
	public function get_results() {

		if(!isset($this->url)) return $this->parse_results(null);

		// Skip if there is a cached result (from earlier search)
		if($this->opts->cache == "on" && has_cached_results($this->url, $this->opts->hash)) return fetch_cached_results($this->url, $this->opts->hash);
	
		if(!isset($this->ch)) return $this->parse_results(null);
		$response = ($this->mh) ? curl_multi_getcontent($this->ch) : curl_exec($this->ch);

		$results = $this->parse_results($response) ?? array();
	
		// Cache last request
		if($this->opts->cache == "on" && !empty($results)) store_cached_results($this->url, $this->opts->hash, $results, ($this->opts->cache_time * 60));

		return $results;
	}
	
	public static function print_results($results, $opts) {}
}

/*--------------------------------------
// Load and make config available, pass around variables
--------------------------------------*/
function load_opts() {
    $opts = require "config.php";

    $opts->query = (isset($_REQUEST['q'])) ? sanitize($_REQUEST["q"]) : "";
    $opts->type = (isset($_REQUEST['t'])) ? sanitize($_REQUEST["t"]) : 0;
    $opts->user_auth = (isset($_REQUEST['a'])) ? sanitize($_REQUEST["a"]) : "";

	// Remove ! at the start of queries to prevent DDG Bangs (!g, !c and crap like that)
	$has_exclamation_mark = substr($opts->query, 0, 1);
	if($has_exclamation_mark == "!") $opts->query = ltrim($opts->query, "!");

    return $opts;
}

/*--------------------------------------
// Try to get some search results
--------------------------------------*/
function fetch_search_results($opts) {
    $start_time = microtime(true);

	// Curl
    $mh = curl_multi_init();

	// Load search script
    if($opts->type == 0 || $opts->type == 1) {
        require "engines/search.php";
        $search = new TextSearch($opts, $mh);
	} else if($opts->type == 2) {
	    require "engines/search-image.php";
        $search = new ImageSearch($opts, $mh);
	} else if($opts->type == 9) {
	    require "engines/search-torrent.php";
        $search = new TorrentSearch($opts, $mh);
    }

    $running = null;

    do {
        curl_multi_exec($mh, $running);
    } while ($running);

    $results = $search->get_results();

	curl_multi_close($mh);

	// Add elapsed time to results
	$results['time'] = number_format(microtime(true) - $start_time, 5, '.', '');

    $search->print_results($results, $opts);

    return $results;
}

/*--------------------------------------
// Process special searches
--------------------------------------*/
function special_search_request($opts) {
	$special_request = null;
    $query_terms = explode(" ", $opts->query);

	// Currency converter
	if($opts->special['currency'] == "on" && is_numeric($query_terms[0]) && ($query_terms[2] == 'to' || $query_terms[2] == 'in')) {
        require "engines/special/currency.php";
        $special_request = new CurrencyRequest($opts, null);
	}
	
	// Dictionary
	if($opts->special['definition'] == "on" && count($query_terms) == 2 && ($query_terms[0] == 'define' || $query_terms[0] == 'd' || $query_terms[0] == 'mean' || $query_terms[0] == 'meaning')) {
        require "engines/special/definition.php";
        $special_request = new DefinitionRequest($opts, null);
	}

	// Wikipedia search
	if($opts->special['wikipedia'] == "on" && count($query_terms) >= 2 && ($query_terms[0] == 'wiki' || $query_terms[0] == 'w')) {
        require "engines/special/wikipedia.php";
        $special_request = new WikipediaRequest($opts, null);
	}

	// php.net search
	if($opts->special['phpnet'] == "on" && count($query_terms) == 2 && $query_terms[0] == 'php') {
        require "engines/special/php.php";
        $special_request = new PHPnetRequest($opts, null);
	}
	
	return $special_request;
}
?>
