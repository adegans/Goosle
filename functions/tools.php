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
// Set curl options
--------------------------------------*/
function set_curl_options($curl, $url, $user_agents) {
	$referer_url = parse_url($url);

	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPGET, 1); // Redundant? Probably...
	curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_USERAGENT, $user_agents[array_rand($user_agents)]);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
	    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
	    'Accept-Language: en-US,en;q=0.5',
	    'Upgrade-Insecure-Requests: 1',
	    'Sec-Fetch-Dest: document',
		'Sec-Fetch-Mode: navigate',
		'Sec-Fetch-Site: none',
		'Referer: '.$referer_url["scheme"].'://'.$referer_url["host"].'/',
	));
	curl_setopt($curl, CURLOPT_ENCODING, "gzip,deflate");
	curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_WHATEVER);
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
		$cache_file = dirname(__DIR__).'/cache/'.md5("$hash:$url").'.data';
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
		$cache_file = dirname(__DIR__).'/cache/'.md5("$hash:$url").'.data';
		file_put_contents($cache_file, serialize($results));
	}
}

function fetch_cached_results($cache_type, $hash, $url) {
	if($cache_type == "apcu") {
		return apcu_fetch("$hash:$url");
	}

	if($cache_type == "file") {
		$cache_file = dirname(__DIR__).'/cache/'.md5("$hash:$url").'.data';
		if(is_file($cache_file)) {
			return unserialize(file_get_contents($cache_file));
		}
	}

	return array();
}

function delete_cached_results($ttl) {
	$folder = opendir(dirname(__DIR__).'/cache/');	
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
		case 'boolean':
			$variable = ($variable === FALSE) ? 0 : 1;
		break;
		default: 
			$variable = ($variable === NULL) ? 'NULL' : htmlspecialchars(strip_tags(trim($variable)), ENT_QUOTES);
		break;
	}

    return $variable;
}

function sanitize_numeric($variable) {
	$variable = preg_replace('/[^0-9]/', '', $variable);
	if(strlen($variable) == 0) $variable = 0;

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
// Detect social media results
--------------------------------------*/
function is_social_media($string) {
	$string = strtolower($string);
	
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
// Special Search result
--------------------------------------*/
function special_search_result($opts, $results) {
	if($opts->imdb_id_search == "on") {
		$found = false;
		foreach($results['search'] as $search_result) {
			if(!$found && preg_match_all("/(imdb.com|tt[0-9]+)/i", $search_result['url'], $imdb_result) && stristr($search_result['title'], "tv series") !== false) {
				$results['special'] = array(
					"title" => $search_result['title'], 
					"text" => "Goosle found an IMDb ID for this TV Show in your results (".$imdb_result[0][1].") - <a href=\"./results.php?q=".$imdb_result[0][1]."&a=".$opts->hash."&t=9\">search for magnet links</a>?<br /><sub>An IMDb ID is detected when a TV Show is present in the results. The first match is highlighted here.</sub>"
				);
				$found = true;
			}
		}
	}
	if(array_key_exists("special", $results)) {
		echo "<li class=\"special-result\"><article>";
		echo "<div class=\"title\"><h2>".$results['special']['title']."</h2></div>";
		echo "<div class=\"text\">".$results['special']['text']."</div>";
		if(array_key_exists("source", $results['special'])) {
			echo "<div class=\"source\"><a href=\"".$results['special']['source']."\" target=\"_blank\">".$results['special']['source']."</a></div>";
		}
		echo "</article></li>";
	}
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

/*--------------------------------------
// Show version in footer and do periodic update check
--------------------------------------*/
function show_version($opts) {
	$cache_file = dirname(__DIR__).'/version.data';
	
	// Currently installed version
	$current_version = "1.2.1";

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

	// Format version for footer
	$show_version = "<a href=\"https://github.com/adegans/Goosle/\" target=\"_blank\">Goosle ".$current_version."</a>.";

	// Check if a newer version is available and add it to the version display
	if(version_compare($current_version, $version['latest'], "<")) {
		$show_version .= " <a href=\"".$version['url']."\" target=\"_blank\" class=\"update\">Version ".$version['latest']." is available!</a>";
	}

	return $show_version;
}
?>