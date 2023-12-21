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

/*--------------------------------------
// Verify the hash, or not, and let people in, or not
--------------------------------------*/
function verify_hash($opts, $auth) {
	if(($opts->hash_auth == "on" && strtolower($opts->hash) === strtolower($auth)) || $opts->hash_auth == "off") return true;

    return false;
}

/*--------------------------------------
// Load pages into a DOM
--------------------------------------*/
function get_xpath($response) {
    if(!$response)
        return null;

    $htmlDom = new DOMDocument;
    @$htmlDom->loadHTML($response);
    $xpath = new DOMXPath($htmlDom);

    return $xpath;
}

/*--------------------------------------
// Strip all extras from an url
--------------------------------------*/
function get_base_url($url) {
    $url = parse_url($url);

    return $url['scheme'] . "://" . $url['host'] . "/";
}

/*--------------------------------------
// Format search result urls
--------------------------------------*/
function get_formatted_url($url) {
    $url = parse_url($url);

    return $url['scheme'] . "://" . $url['host'] . str_replace('/', ' &rsaquo; ', str_replace('%20', ' ', rtrim($url['path'], '/')));
}

/*--------------------------------------
// APCu Caching
--------------------------------------*/
function has_cached_results($url, $hash) {
	if(function_exists("apcu_exists")) {
		return apcu_exists("$hash:$url");
	}

	return false;
}

function store_cached_results($url, $hash, $results, $ttl = 0) {
	if(function_exists("apcu_store") && !empty($results)) {
		return apcu_store("$hash:$url", $results, $ttl);
	}
}

function fetch_cached_results($url, $hash) {
	if(function_exists("apcu_fetch")) {
		return apcu_fetch("$hash:$url");
	}
	
	return array();
}

/*--------------------------------------
// Sanitize variables
--------------------------------------*/
function sanitize($thing) {
	switch(gettype($thing)) {
		case 'string': 
			$thing = stripslashes(strip_tags(trim($thing)));
		break;
		case 'boolean':
			$thing = ($thing === FALSE) ? 0 : 1;
		break;
		default: 
			$thing = ($thing === NULL) ? 'NULL' : strip_tags(trim($thing));
		break;
	}

    return $thing;
}

/*--------------------------------------
// Human readable file sizes
--------------------------------------*/
function human_filesize($bytes, $dec = 2) {
    $size   = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $factor = floor((strlen($bytes) - 1) / 3);

    return sprintf("%.{$dec}f ", $bytes / pow(1024, $factor)) . @$size[$factor];
}

/*--------------------------------------
// Generate random strings for passwords
--------------------------------------*/
function string_generator() {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $password = array();
    $length = strlen($characters) - 1;

    for ($i = 0; $i < 24; $i++) {
        $n = rand(0, $length);
        $password[] = $characters[$n];
    }

    array_splice($password, 6, 0, '-');
	array_splice($password, 13, 0, '-');
	array_splice($password, 20, 0, '-');

    return implode($password);
}
?>