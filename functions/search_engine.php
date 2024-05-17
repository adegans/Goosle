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
    protected $query, $ch, $mh, $opts, $url, $headers;

	function __construct($opts, $mh) {
		$this->query = $opts->query;
		$this->mh = $mh;
		// Must be in this order :-/
		$this->opts = $opts;
		$this->url = $this->get_request_url();

		// No search engine url
		if(!$this->url) return;
		
		// Skip if there is a cached result (from earlier search)
		if($this->opts->cache_type !== "off" && has_cached_results($this->opts->cache_type, $this->opts->hash, $this->url, (intval($this->opts->cache_time) * 60))) return;

		// Default headers for the curl request
		$default_headers = array(
			'Accept' => 'text/html, application/xhtml+xml, application/json;q=0.9, application/xml;q=0.8, */*;q=0.7',
			'Accept-Language' => 'en-US,en;q=0.5',
			'Accept-Encoding' => 'gzip, deflate',
// 			'Connection' => 'keep-alive',
			'Upgrade-Insecure-Requests' => '1',
			'User-Agent' => $this->opts->user_agents[array_rand($this->opts->user_agents)],
			'Sec-Fetch-Dest' => 'document',
			'Sec-Fetch-Mode' => 'navigate',
			'Sec-Fetch-Site' => 'none'
		);

		// Override or remove headers per curl request
		$extra_headers = $this->get_request_headers();
		if(count($extra_headers) > 0) {
			$headers = array_filter(array_replace($default_headers, $extra_headers));

			foreach($headers as $key => $value) {
				$this->headers[] = $key.': '.$value;
			}

			unset($key, $value);
		} else {
			$this->headers = $default_headers;
		}

		unset($default_headers, $extra_headers, $key, $value);

		// Curl
		$this->ch = curl_init();
		
		curl_setopt($this->ch, CURLOPT_URL, $this->url);
		curl_setopt($this->ch, CURLOPT_HTTPGET, 1); // Redundant? Probably...
		curl_setopt($this->ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_ENCODING, "gzip,deflate");
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($this->ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($this->ch, CURLOPT_VERBOSE, false);

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
		if($this->opts->cache_type !== "off" && has_cached_results($this->opts->cache_type, $this->opts->hash, $this->url, $ttl)) {
			return fetch_cached_results($this->opts->cache_type, $this->opts->hash, $this->url);
		}

		// Curl request
		if(!isset($this->ch)) {
			return $this->parse_results(null);
		}

		$response = ($this->mh) ? curl_multi_getcontent($this->ch) : curl_exec($this->ch);

		$results = $this->parse_results($response) ?? array();

		// Cache last request
		if($this->opts->cache_type !== "off") {
			if(!empty($results)) store_cached_results($this->opts->cache_type, $this->opts->hash, $this->url, $results, $ttl);

			// Maybe delete old file cache
			if($this->opts->cache_type == "file") delete_cached_results($ttl);
		}

		return $results;
	}
	
	public static function print_results($results, $opts) {}
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

	// Latest additions to eztv
	if($opts->special['eztv'] == "on") {
        require ABSPATH."engines/special/eztv_highlights.php";
        $special_request['eztv'] = new eztvhighlights($opts, $mh);
	}
	
	return $special_request;
}
?>
