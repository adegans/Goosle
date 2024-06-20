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
		if($this->opts->cache_type !== 'off' && has_cached_results($this->opts->cache_type, $this->opts->hash, $this->url, $this->opts->cache_time)) return;

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

		unset($default_headers, $extra_headers);

		// Curl
		$this->ch = curl_init();
		
		curl_setopt($this->ch, CURLOPT_URL, $this->url);
		curl_setopt($this->ch, CURLOPT_HTTPGET, 1); // Redundant? Probably...
		curl_setopt($this->ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_ENCODING, 'gzip,deflate');
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
		return '';
	}
	
	/*--------------------------------------
	// Check if a request to a search engine was successful
	--------------------------------------*/
	public function request_successful() {
		if((isset($this->ch) && curl_getinfo($this->ch)['http_code'] == '200') || ($this->opts->cache_type !== 'off' && has_cached_results($this->opts->cache_type, $this->opts->hash, $this->url, $this->opts->cache_time))) {
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
		
		// If there is a cached result from an earlier search use that instead
		if($this->opts->cache_type !== 'off' && has_cached_results($this->opts->cache_type, $this->opts->hash, $this->url, $this->opts->cache_time)) {
			return fetch_cached_results($this->opts->cache_type, $this->opts->hash, $this->url);
		}

		// Curl request
		if(!isset($this->ch)) {
			return $this->parse_results(null);
		}

		$response = ($this->mh) ? curl_multi_getcontent($this->ch) : curl_exec($this->ch);

		$results = $this->parse_results($response) ?? array();

		// Cache last request if there is something to cache
		if($this->opts->cache_type !== 'off') {
			if(count($results) > 0) store_cached_results($this->opts->cache_type, $this->opts->hash, $this->url, $results, $this->opts->cache_time);

			// Maybe delete old file cache
			if($this->opts->cache_type == 'file') delete_cached_results($this->opts->cache_time);
		}

		return $results;
	}
	
	public static function print_results($results, $opts) {}
}
?>