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
// Load and make config available, pass around variables
--------------------------------------*/
function load_opts() {
	$opts = require ABSPATH."config.php";
	
	// From the url/request	
	$opts->query = (isset($_REQUEST['q'])) ? trim($_REQUEST['q']) : "";
	$opts->type = (isset($_REQUEST['t'])) ? sanitize($_REQUEST['t']) : 0;
	$opts->user_auth = (isset($_REQUEST['a'])) ? sanitize($_REQUEST['a']) : "";
	
	// Force a few defaults and safeguards
	if($opts->cache_type == "file" && !is_dir(ABSPATH.'cache/')) $opts->cache = "off";
	if($opts->cache_type == "apcu" && !function_exists("apcu_exists")) $opts->cache = "off";
	if($opts->enable_image_search == "off" && $opts->type == 1) $opts->type = 0;
	if($opts->enable_magnet_search == "off" && $opts->type == 9) $opts->type = 0;
	if(!is_numeric($opts->cache_time) || ($opts->cache_time > 720 || $opts->cache_time < 1)) $opts->cache_time = 30;
	if(!is_numeric($opts->social_media_relevance) || ($opts->social_media_relevance > 10 || $opts->social_media_relevance < 0)) $opts->social_media_relevance = 8;
	
	// Remove ! at the start of queries to prevent DDG Bangs (!g, !c and crap like that)
	if(substr($opts->query, 0, 1) == "!") $opts->query = substr($opts->query, 1);
	
	return $opts;
}

/*--------------------------------------
// Set curl options
--------------------------------------*/
function set_curl_options($curl, $url, $user_agents) {
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPGET, 1); // Redundant? Probably...
	curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_USERAGENT, $user_agents[array_rand($user_agents)]);
	curl_setopt($curl, CURLOPT_ENCODING, "gzip,deflate");
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
	    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
	    'Accept-Language: en-US,en;q=0.5',
	    'Accept-Encoding: gzip, deflate',
	    'Connection: keep-alive',
	    'Upgrade-Insecure-Requests: 1',
	    'Sec-Fetch-Dest: document',
		'Sec-Fetch-Mode: navigate',
		'Sec-Fetch-Site: none'
	));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
	curl_setopt($curl, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
	curl_setopt($curl, CURLOPT_TIMEOUT, 3);
	curl_setopt($curl, CURLOPT_VERBOSE, false);
}

/*--------------------------------------
// Load pages into a DOM
--------------------------------------*/
function get_xpath($response) {
	if(!$response) return null;
	
	$htmlDom = new DOMDocument;
	@$htmlDom->loadHTML($response);
	$xpath = new DOMXPath($htmlDom);
	
	return $xpath;
}

/*--------------------------------------
// Format search result urls
--------------------------------------*/
function get_formatted_url($url) {
	$url = parse_url($url);
	
	$formatted_url = $url['scheme'] . "://" . $url['host'];
	$formatted_url .= str_replace('/', ' &rsaquo; ', urldecode(str_replace('%20', ' ', rtrim($url['path'], '/'))));
	
	return $formatted_url;
}

/*--------------------------------------
// Result Caching
--------------------------------------*/
function has_cached_results($cache_type, $hash, $url, $ttl) {
	if($cache_type == "apcu") {
		return apcu_exists("$hash:$url");
	}

	if($cache_type == "file") {
		$cache_file = ABSPATH.'cache/'.md5("$hash:$url").'.data';
		if(is_file($cache_file)) {
			if(filemtime($cache_file) >= (time() - $ttl)) {
				return true;
			}
		}
	}

	return false;
}

function store_cached_results($cache_type, $hash, $url, $results, $ttl) {
	if($cache_type == "apcu" && !empty($results)) {
		apcu_store("$hash:$url", $results, $ttl);
	}

	if($cache_type == "file") {
		$cache_file = ABSPATH.'cache/'.md5("$hash:$url").'.data';
		file_put_contents($cache_file, serialize($results));
	}
}

function fetch_cached_results($cache_type, $hash, $url) {
	if($cache_type == "apcu") {
		return apcu_fetch("$hash:$url");
	}

	if($cache_type == "file") {
		$cache_file = ABSPATH.'cache/'.md5("$hash:$url").'.data';
		if(is_file($cache_file)) {
			return unserialize(file_get_contents($cache_file));
		}
	}

	return array();
}

function delete_cached_results($ttl) {
	$folder = opendir(ABSPATH.'cache/');	
	while($file_name = readdir($folder)) {
		$extension = pathinfo($file_name, PATHINFO_EXTENSION);
		if($file_name == "." OR $file_name == ".." OR $extension != "data") continue; 
	
		if(is_file($folder.$file_name)) {
			if(filemtime($folder.$file_name) < (time() - $ttl)) {
				unlink($folder.$file_name);
			}
		}
	}
}

/*--------------------------------------
// Sanitize variables
--------------------------------------*/
function sanitize($variable) {
	switch(gettype($variable)) {
		case 'string': 
			$variable = htmlspecialchars(trim($variable), ENT_QUOTES);
		break;
		case 'integer':
			$variable = preg_replace('/[^0-9]/', '', $variable);
			if(strlen($variable) == 0) $variable = 0;
		break;
		case 'boolean':
			$variable = ($variable === FALSE) ? 0 : 1;
		break;
		default: 
			$variable = ($variable === NULL) ? 'NULL' : htmlspecialchars(strip_tags(trim($variable)), ENT_QUOTES);
		break;
	}

    return $variable;
}

/*--------------------------------------
// Search result match counter
--------------------------------------*/
function match_count($string, $query) {
	$string = strtolower($string);

	if(filter_var($string, FILTER_VALIDATE_URL)) { 
		$string = preg_replace("/[^a-z0-9]+/", " ", $string);
	}

	$string = preg_replace("/[^a-z0-9 ]+/", "", $string);
	$string = preg_replace("/\s{2,}/", " ", $string);

	$matches = array_intersect(array_filter(array_unique(explode(" ", $string))), $query);
	$matches = count($matches);

    return $matches;
}

/*--------------------------------------
// Detect Season and Episodes in results
--------------------------------------*/
function is_season_or_episode($search_query, $result_name) {
	$search_query = strtolower($search_query);
	$result_name = strtolower($result_name);
	
	// Filter by Season (S01) or Season and Episode (S01E01)
	// Where [0][0] = Season and [0][1] = Episode
	if(preg_match_all("/(S[0-9]{1,3})|(E[0-9]{1,3})/", $search_query, $query_episode) && preg_match_all("/(S[0-9]{1,3})|(E[0-9]{1,3})/", $result_name, $result_episode)) {
		if($query_episode[0][0] != $result_episode[0][0] 
			|| (array_key_exists(1, $query_episode[0]) 
				&& array_key_exists(1, $result_episode[0]) 
				&& $query_episode[0][1] != $result_episode[0][1]
			)
		) {
			return false;
		}
	}

    return true;
}

/*--------------------------------------
// Detect social media results
--------------------------------------*/
function is_social_media($string) {
	$string = strtolower($string);
	
	// Borrowed from https://github.com/lorey/social-media-profiles-regexs
	if(preg_match("/(?:https?:)?\/\/(?:www\.)?(?:facebook|fb)\.com\/(?P<profile>(?![A-z]+\.php)(?!marketplace|gaming|watch|me|messages|help|search|groups)[A-z0-9_\-\.]+)\/?/", $string)
		|| preg_match("/(?:https?:)?\/\/(?:www\.)?(?:instagram\.com|instagr\.am)\/(?P<username>[A-Za-z0-9_](?:(?:[A-Za-z0-9_]|(?:\.(?!\.))){0,28}(?:[A-Za-z0-9_]))?)/", $string)
		|| preg_match("/(?:https?:)?\/\/(?:[A-z]+\.)?twitter\.com\/@?(?P<username>[A-z0-9_]+)\/status\/(?P<tweet_id>[0-9]+)\/?/", $string)
		|| preg_match("/(?:https?:)?\/\/(?:[A-z]+\.)?twitter\.com\/@?(?!home|share|privacy|tos)(?P<username>[A-z0-9_]+)\/?/", $string)
		|| preg_match("/(?:https?:)?\/\/(?:[a-z]+\.)?reddit\.com\/(?:u(?:ser)?)\/(?P<username>[A-z0-9\-\_]*)\/?/", $string)
		|| preg_match("/(?:https?:)?\/\/(?:www\.)?snapchat\.com\/add\/(?P<username>[A-z0-9\.\_\-]+)\/?/", $string)
		|| preg_match("/^.*https:\/\/(?:m|www|vm)?\.?tiktok\.com\/((?:.*\b(?:(?:usr|v|embed|user|video)\/|\?shareId=|\&item_id=)(\d+))|\w+)/", $string)
		|| preg_match("/(?:https?:)?\/\/(?:[\w]+\.)?linkedin\.com\/(?P<company_type>(company)|(school))\/(?P<company_permalink>[A-z0-9-À-ÿ\.]+)\/?/", $string)
		|| preg_match("/(?:https?:)?\/\/(?:[\w]+\.)?linkedin\.com\/feed\/update\/urn:li:activity:(?P<activity_id>[0-9]+)\/?/", $string)
		|| preg_match("/(?:https?:)?\/\/(?:[\w]+\.)?linkedin\.com\/in\/(?P<permalink>[\w\-\_À-ÿ%]+)\/?/", $string)
	) return true;

    return false;
}

/*--------------------------------------
// Search suggestions
--------------------------------------*/
function search_suggestion($opts, $results) {
	if(array_key_exists("did_you_mean", $results)) {
		$specific_result = $specific_result2 = "";

		if(array_key_exists("search_specific", $results)) {
			if($opts->type == 3 && count($results['search_specific']) > 1) {
				// Format query url
				$search_specific_url2 = "./results.php?q=".urlencode($results['search_specific'][1])."&t=".$opts->type."&a=".$opts->hash;
				$specific_result2 = " or <a href=\"".$search_specific_url2."\">".$results['search_specific'][1]."</a>";
			}

			// Format query url			
			$search_specific_url = "./results.php?q=".urlencode($results['search_specific'][0])."&t=".$opts->type."&a=".$opts->hash;
			$specific_result = "<br /><small>Or instead search for <a href=\"".$search_specific_url."\">".$results['search_specific'][0]."</a>".$specific_result2.".</small>";

			unset($search_specific, $search_specific_url, $search_specific2, $search_specific_url2);
		}

		$didyoumean_url = "./results.php?q=".urlencode($results['did_you_mean'])."&t=".$opts->type."&a=".$opts->hash;

		echo "<li class=\"meta\">Did you mean <a href=\"".$didyoumean_url."\">".$results['did_you_mean']."</a>?".$specific_result."</li>";

		unset($didyoumean_url, $specific_result, $specific_result2);
	}
}

/*--------------------------------------
// Count and format search sources
--------------------------------------*/
function search_sources($results) {
	$sources = array();
	foreach($results as $source => $amount) {
		$plural = ($amount > 1) ? "results" : "result";
		$sources[] = $amount." ".$plural." from ".$source;
	}

    $sources = replace_last_comma(implode(', ', $sources));

	echo "<li class=\"sources\">Includes ".$sources.".</li>";
	
	unset($sources);
}

/*--------------------------------------
// Find and replace the last comma in a string
--------------------------------------*/
function replace_last_comma($string) {
    $last_comma = strrpos($string, ', ');
    if($last_comma !== false) {
        $string = substr_replace($string, ' and ', $last_comma, 2);
    }

    return $string;
}

/*--------------------------------------
// Human readable file sizes
--------------------------------------*/
function human_filesize($bytes, $dec = 2) {
    $size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
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

    for($i = 0; $i < 24; $i++) {
        $n = rand(0, $length);
        $password[] = $characters[$n];
    }

    array_splice($password, 6, 0, '-');
	array_splice($password, 13, 0, '-');
	array_splice($password, 20, 0, '-');

    return implode($password);
}

/*--------------------------------------
// Show version in footer and do periodic update check
--------------------------------------*/
function show_version() {
	$cache_file = dirname(__DIR__).'/version.data';
	
	// Currently installed version
	$current_version = "1.3";

	// Format current version for footer
	$show_version = "<a href=\"https://github.com/adegans/Goosle/\" target=\"_blank\">Goosle ".$current_version."</a>.";

	if(!is_file($cache_file)){
		// Create update cache file
	    $version = array('latest' => "0.0", "checked" => 0, "url" => "");
	    file_put_contents($cache_file, serialize($version));
	} else {
		// Get update information
		$version = unserialize(file_get_contents($cache_file));
	}

	// Update check, every week
	if($version['checked'] < time() - 604800) {
		$ch = curl_init();
		set_curl_options($ch, "https://api.github.com/repos/adegans/goosle/releases/latest", array("goosle/".$current_version.";"));
		$response = curl_exec($ch);
		curl_close($ch);
		
		$json_response = json_decode($response, true);
		
		// No response
		if(empty($json_response)) return $show_version;

		// Update version info
		$version = array('latest' => $json_response['tag_name'], "checked" => time(), "url" => $json_response['html_url']);
		file_put_contents($cache_file, serialize($version));
	}

	// Check if a newer version is available and add it to the version display
	if(version_compare($current_version, $version['latest'], "<")) {
		$show_version .= " <a href=\"".$version['url']."\" target=\"_blank\" class=\"update\">Version ".$version['latest']." is available!</a>";
	}

	return $show_version;
}
?>