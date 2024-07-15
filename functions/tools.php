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

/*--------------------------------------
// Verify the hash, or not, and let people in, or not
--------------------------------------*/
function verify_hash($use_hash, $hash, $auth, $is_shared = null) {
	if(($use_hash == 'on' && strtolower($hash) === strtolower($auth)) || $use_hash == 'off' || !empty($is_shared)) return true;

    return false;
}

/*--------------------------------------
// Load and make config available, pass around variables
--------------------------------------*/
function load_opts() {
	$config_file = ABSPATH.'config.php';

	if(!is_file($config_file)) {
		echo "<h3>config.php is missing!</h3>";
		echo "<p>Please check the readme.md file for complete installation instructions.</p>";
		echo "<p>Configure Goosle properly by copying config.default.php to config.php. In config.php you can set your preferences.</p>";

		die();
	} else {
		$opts = require $config_file;
		
		// From the url/request	
		$opts->user_auth = (isset($_REQUEST['a'])) ? sanitize($_REQUEST['a']) : '';
		$opts->pixel = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

		// Force a few defaults and safeguards
		if(empty($opts->colorscheme)) $opts->colorscheme = 'default';
		if($opts->cache_type == 'file' && !is_dir(ABSPATH.'cache/')) $opts->cache_type = 'off';
		if($opts->cache_type == 'apcu' && !function_exists('apcu_exists')) $opts->cache_type = 'off';
		if($opts->cache_time < 1 || ($opts->cache_type == 'apcu' && $opts->cache_time > 8) || ($opts->cache_type == 'file' && $opts->cache_time > 48)) $opts->cache_time = 8;
		if(!is_numeric($opts->search_results_per_page) || ($opts->search_results_per_page < 8 || $opts->search_results_per_page > 160)) $opts->social_media_relevance = 24;
		if(!is_numeric($opts->social_media_relevance) || ($opts->social_media_relevance > 10 || $opts->social_media_relevance < 0)) $opts->social_media_relevance = 8;
		
		return $opts;
	}
}

/*--------------------------------------
// Process search query
--------------------------------------*/
function load_search() {
	global $opts;

	$search = new stdClass();

	// From the url/request	
	if(!isset($_REQUEST['s'])) {
		// Regular search
		$search->query = (isset($_REQUEST['q'])) ? trim($_REQUEST['q']) : '';
		$search->type = (isset($_REQUEST['t'])) ? sanitize($_REQUEST['t']) : 0;
		$search->share = null;
	} else {
		// Shared result
		$share_string = explode('||', base64_url_decode(sanitize($_REQUEST['s'])));
		if(is_array($share_string) && count($share_string) === 3) {
			$search->query = sanitize($share_string[0]);
			$search->type = sanitize($share_string[1]);
			$search->share = sanitize($share_string[2]);
		} else {
			$search->query = '';
			$search->type = 0;
			$search->share = null;
		}
		unset($share_string);
	}

	// Set pagination page
	$search->page = (isset($_REQUEST['p'])) ? sanitize($_REQUEST['p']) : 1;

	// Remove ! at the start of queries to prevent DDG Bangs (!g, !c and crap like that)
	if(substr($search->query, 0, 1) == '!') $search->query = substr($search->query, 1);
	
	// Preserve quotes
	$search->query = str_replace('%22', '\"', $search->query);
	
	// Special searches and filters
    $search->query_terms = explode(' ', strtolower($search->query)); // Break up query
	$search->count_terms = count($search->query_terms); // How many keywords?

	// Safe search override
	// 0 = off, 1 = normal (default), 2 = on/strict
	$search->safe = 1;
	if($search->query_terms[0] == 'safe:on') {
		$search->safe = 2;
		$search->query = trim(str_replace($search->query_terms[0], '', $search->query));
	}
	
	if($search->query_terms[0] == 'safe:off' || $search->query_terms[0] == 'xxx' || $search->query_terms[0] == 'porn') {
		$search->safe = 0;
		$search->query = trim(str_replace($search->query_terms[0], '', $search->query));
	}

	// Maybe count stats?
	if(!empty($search->query)) count_stats();
		
	return $search;
}

/*--------------------------------------
// Do some stats
--------------------------------------*/
function load_stats() {
	$stats_file = ABSPATH.'cache/stats.data';
	
	if(!is_file($stats_file)) {
		// Create stats file if it doesn't exist
	    $stats = array('started' => mktime(0, 0, 0, date('m'), date('d'), date('Y')), 'days_active' => 0, 'all_queries' => 0, 'avg_per_day' => 0);
	    file_put_contents($stats_file, serialize($stats));
	} else {
		// Get stats
		$stats = unserialize(file_get_contents($stats_file));
	}
	
	return $stats;
}

function count_stats() {
	$stats = load_stats();

	// Calculate average searches per day
	$new_day = (mktime(0, 0, 0, date('m'), date('d'), date('Y')) - $stats['started']) / 86400;
	if($new_day > $stats['days_active']) {
		$stats['days_active'] = $stats['days_active'] + 1;
		$stats['avg_per_day'] = $stats['all_queries'] / $stats['days_active'];
	}

	// Count query
	$stats['all_queries'] = $stats['all_queries'] + 1;

	// Save stats
	$stats_file = ABSPATH.'cache/stats.data';
    file_put_contents($stats_file, serialize($stats));
}

/*--------------------------------------
// Show version in footer
--------------------------------------*/
function show_version() {
	$version_file = ABSPATH.'cache/version.data';
	
	if(is_file($version_file)) {
		// Get version information
		$version = unserialize(file_get_contents($version_file));

		// Format current version for footer
		$show_version = "<a href=\"https://github.com/adegans/Goosle/\" target=\"_blank\">Goosle ".$version['current']."</a>.";
	
		// Check if a newer version is available and add it to the version display
		if(version_compare($version['current'], $version['latest'], '<')) {
			$show_version .= " <a href=\"".$version['url']."\" target=\"_blank\" class=\"update\">Version ".$version['latest']." is available!</a>";
		}
	} else {
		// If the update cache doesn't exist...
		$show_version = "<a href=\"https://github.com/adegans/Goosle/\" target=\"_blank\">Goosle</a>.";
	}

	return $show_version;
}

/*--------------------------------------
// Standardized cURL requests that support both POST and GET
// For Box Office, Update checks and oAUTH
// NOT (YET?) USED FOR ENGINE REQUESTS!!
// NOT (YET?) USED FOR ENGINE REQUESTS!!
--------------------------------------*/
function do_curl_request($url, $headers, $method, $post_fields) {
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $url);
	if($method == 'post' && !empty($post_fields)) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
	} else {
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
	}
	curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
	curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ch, CURLOPT_VERBOSE, false);
	
	$response = curl_exec($ch);
	curl_close($ch);
	
	return $response;
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
// Get Goosle's base url
--------------------------------------*/
function get_base_url($siteurl) {
	// Figure out server protocol
	$protocol = empty($_SERVER['HTTPS']) ? 'http' : 'https';	
	
	return $protocol.'://'.$siteurl;
}

/*--------------------------------------
// URL Safe base64 encoding and decoding
--------------------------------------*/
function base64_url_encode($input) {
	return strtr(base64_encode($input), '+/=', '-_.');
}

function base64_url_decode($input) {
	return base64_decode(strtr($input, '-_.', '+/='));
}

/*--------------------------------------
// Result Caching
--------------------------------------*/
function has_cached_results($cache_type, $hash, $url, $ttl) {
	$ttl = intval($ttl * 3600); // Make it hours

	if($cache_type == 'apcu') {
		return apcu_exists($hash.':'.$url);
	}

	if($cache_type == 'file') {
		$cache_file = ABSPATH.'cache/'.md5($hash.':'.$url).'.result';
		if(is_file($cache_file)) {
			if(filemtime($cache_file) >= (time() - $ttl)) {
				return true;
			}
		}
	}

	return false;
}

function store_cached_results($cache_type, $hash, $url, $results, $ttl) {
	$ttl = intval($ttl * 3600); // Make it hours

	if($cache_type == 'apcu') {
		apcu_store($hash.':'.$url, $results, $ttl);
	}

	if($cache_type == 'file') {
		$cache_file = ABSPATH.'cache/'.md5($hash.':'.$url).'.result';
		file_put_contents($cache_file, serialize($results));
	}
}

function fetch_cached_results($cache_type, $hash, $url) {
	if($cache_type == 'apcu') {
		return apcu_fetch($hash.':'.$url);
	}

	if($cache_type == 'file') {
		$cache_file = ABSPATH.'cache/'.md5($hash.':'.$url).'.result';
		if(is_file($cache_file)) {
			return unserialize(file_get_contents($cache_file));
		}
	}

	return array();
}

function delete_cached_results($ttl) {
	$ttl = intval($ttl * 3600); // Make it hours
	$folder = ABSPATH.'cache/';

	if(is_dir($folder)) {
	    if($handle = opendir($folder)) {
		    // Loop through all files
	        while(($file = readdir($handle)) !== false) {
		        // Skip some of them
				$extension = pathinfo($file, PATHINFO_EXTENSION);
				if($file == '.' OR $file == '..' OR $extension != 'result') continue; 

				// Delete if expired
				if(filemtime($folder.$file) < (time() - $ttl)) {
					unlink($folder.$file);
				}
	        }
	        closedir($handle);
	    }
	}
}

/*--------------------------------------
// Store generated tokens
--------------------------------------*/
function oauth_store_token($token_file, $connect, $token) {
	if(!is_file($token_file)){
		// Create token file
	    file_put_contents($token_file, serialize(array($connect => $token)));
	} else {
		// Update token file
		$tokens = unserialize(file_get_contents($token_file));
		$tokens[$connect] = $token;
	    file_put_contents($token_file, serialize($tokens));
	}
}		

/*--------------------------------------
// Sanitize/format variables
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

function strip_newlines($string) {
	return preg_replace('/<br>|\n/', '', $string);
}

function limit_string_length($string, $length = 200, $append = '&hellip;') {
	$string = trim($string);

	if(str_word_count($string, 0) > $length) {
		$words = str_word_count($string, 2);
		$pos = array_keys($words);
		$string = substr($string, 0, $pos[$length]) . $append;
	}

	return $string;
}

/*--------------------------------------
// Search result match counter
--------------------------------------*/
function match_count($string, $query) {
	if(empty($string)) return 0;

	$string = strtolower($string);

	if(filter_var($string, FILTER_VALIDATE_URL)) { 
		$string = preg_replace('/[^a-z0-9]+/', ' ', $string);
	}

	// Replace anything but alphanumeric with a space
	$string = preg_replace('/\s{2,}|[^a-z0-9]+/', ' ', $string);

	$matches = array_intersect(array_filter(array_unique(explode(' ', $string))), $query);
	$matches = count($matches);

    return $matches;
}

/*--------------------------------------
// Detect social media results
--------------------------------------*/
function is_social_media($string) {
	$string = strtolower($string);
	
	// Borrowed from https://github.com/lorey/social-media-profiles-regexs
	if(preg_match('/(?:https?:)?\/\/(?:www\.)?(?:facebook|fb)\.com\/(?P<profile>(?![A-z]+\.php)(?!marketplace|gaming|watch|me|messages|help|search|groups)[A-z0-9_\-\.]+)\/?/', $string)
		|| preg_match('/(?:https?:)?\/\/(?:www\.)facebook.com\/(?:profile.php\?id=)?(?P<id>[0-9]+)/', $string)
		|| preg_match('/(?:https?:)?\/\/(?:www\.)?(?:instagram\.com|instagr\.am)\/(?P<username>[A-Za-z0-9_](?:(?:[A-Za-z0-9_]|(?:\.(?!\.))){0,28}(?:[A-Za-z0-9_]))?)/', $string)
		|| preg_match('/(?:https?:)?\/\/(?:[A-z]+\.)?twitter\.com\/@?(?P<username>[A-z0-9_]+)\/status\/(?P<tweet_id>[0-9]+)\/?/', $string)
		|| preg_match('/(?:https?:)?\/\/(?:[A-z]+\.)?twitter\.com\/@?(?!home|share|privacy|tos)(?P<username>[A-z0-9_]+)\/?/', $string)
		|| preg_match('/(?:https?:)?\/\/(?:[a-z]+\.)?reddit\.com\/(?:u(?:ser)?)\/(?P<username>[A-z0-9\-\_]*)\/?/', $string)
		|| preg_match('/(?:https?:)?\/\/(?:www\.)?snapchat\.com\/add\/(?P<username>[A-z0-9\.\_\-]+)\/?/', $string)
		|| preg_match('/^.*https:\/\/(?:m|www|vm)?\.?tiktok\.com\/((?:.*\b(?:(?:usr|v|embed|user|video)\/|\?shareId=|\&item_id=)(\d+))|\w+)/', $string)
		|| preg_match('/(?:https?:)?\/\/(?:[\w]+\.)?linkedin\.com\/(?P<company_type>(company)|(school))\/(?P<company_permalink>[A-z0-9-À-ÿ\.]+)\/?/', $string)
		|| preg_match('/(?:https?:)?\/\/(?:[\w]+\.)?linkedin\.com\/feed\/update\/urn:li:activity:(?P<activity_id>[0-9]+)\/?/', $string)
		|| preg_match('/(?:https?:)?\/\/(?:[\w]+\.)?linkedin\.com\/in\/(?P<permalink>[\w\-\_À-ÿ%]+)\/?/', $string)
		|| preg_match('/(?:https?:)?\/\/(?:[A-z]+\.)?youtube.com\/(?:c(?:hannel)?)\/(?P<id>[A-z0-9-\_]+)\/?/', $string)
		|| preg_match('/(?:https?:)?\/\/(?:[A-z]+\.)?youtube.com\/(?:u(?:ser)?)\/(?P<username>[A-z0-9]+)\/?/', $string)
		|| preg_match('/(?:https?:)?\/\/(?:(?:www\.)?youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)(?P<id>[A-z0-9\-\_]+)/', $string)
	) return true;

    return false;
}

/*--------------------------------------
// Search suggestions
--------------------------------------*/
function search_suggestion($search, $opts, $results) {
	$specific_result = $specific_result2 = '';

	if(($search->type == 0 || $search->type == 1) && count($results['search_specific']) > 1) {
		// Format query url
		$search_specific_url2 = "./results.php?q=".urlencode($results['search_specific'][1])."&t=".$search->type."&a=".$opts->hash;
		$specific_result2 = " or <a href=\"".$search_specific_url2."\">".$results['search_specific'][1]."</a>";
	}

	// Format query url			
	$search_specific_url = "./results.php?q=".urlencode($results['search_specific'][0])."&t=".$search->type."&a=".$opts->hash;
	$specific_result = "Or instead search for <a href=\"".$search_specific_url."\">".$results['search_specific'][0]."</a>".$specific_result2.".";

	unset($search_specific_url, $search_specific_url2, $specific_result2);

	return $specific_result;
}

/*--------------------------------------
// Count and format search sources
--------------------------------------*/
function search_sources($results) {
	$sources = array();
	foreach($results as $source => $amount) {
		$plural = ($amount > 1) ? 'results' : 'result';
		$sources[] = $amount.' '.$plural.' from '.$source;
	}

	$sources = replace_last_comma(implode(', ', $sources));
	$sources = 'Includes '.$sources.'.';
    
    return $sources;
}

/*--------------------------------------
// Format search result urls
--------------------------------------*/
function search_formatted_url($url) {
	$url = parse_url($url);

	$formatted_url = $url['scheme'] . '://' . $url['host'];
	if(array_key_exists('path', $url)) {
		$formatted_url .= str_replace('/', ' &rsaquo; ', urldecode(str_replace('%20', ' ', rtrim($url['path'], '/'))));
	}
	
	return $formatted_url;
}

/*--------------------------------------
// Results pagination
--------------------------------------*/
function search_pagination($search, $opts, $number_of_results) {
	$number_of_pages = ceil($number_of_results / $opts->search_results_per_page);

	$pagination = "";
	
	if($search->page > 1) {
		$prev = $search->page - 1;
		$pagination .= "<a href=\"".get_base_url($opts->siteurl)."/results.php?q=".urlencode($search->query)."&t=".$search->type."&a=".$opts->hash."&p=".$prev."\" title=\"Previous page\"><span class=\"arrow-left\"></span></a> ";  
	}

	for($page = 1; $page <= $number_of_pages; $page++) {
		$class = ($search->page == $page) ? "current" : "";
		$pagination .= "<a href=\"".get_base_url($opts->siteurl)."/results.php?q=".urlencode($search->query)."&t=".$search->type."&a=".$opts->hash."&p=".$page."\" class=\"".$class."\" title=\"To page ".$page."\">".$page."</a> ";  
	}

	if($search->page < $number_of_pages) {
		$next = $search->page + 1;
		$pagination .= "<a href=\"".get_base_url($opts->siteurl)."/results.php?q=".urlencode($search->query)."&t=".$search->type."&a=".$opts->hash."&p=".$next."\" title=\"Next page\"><span class=\"arrow-right\"></span></a> ";
	}
    
    return $pagination;
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
// Human readable file sizes from bytes
--------------------------------------*/
function human_filesize($bytes, $dec = 2) {
    $size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $factor = floor((strlen($bytes) - 1) / 3);

    return sprintf("%.{$dec}f ", $bytes / pow(1024, $factor)) . @$size[$factor];
}


/*--------------------------------------
// Output and format dates in local time
--------------------------------------*/
function the_date($format, $timestamp = null) {
	global $opts;

	$offset = preg_replace('/UTC\+?/i', '', $opts->timezone);
	if(empty($offset)) $offset = 0;

	if(is_null($timestamp) || !is_numeric($timestamp)) $timestamp = time();

	$hours = (int) $offset;
	$minutes = ($offset - $hours);

	$sign = ($offset < 0) ? '-' : '+';
	$abs_hour = abs($hours);
	$abs_mins = abs($minutes * 60);

	$datetime = date_create('@'.$timestamp);
	$datetime->setTimezone(new DateTimeZone(sprintf('%s%02d:%02d', $sign, $abs_hour, $abs_mins)));
	
	return $datetime->format($format);
}

/*--------------------------------------
// Turn a string size (600 MB) into bytes (int)
--------------------------------------*/
function filesize_to_bytes($num) {
    preg_match('/(b|kb|mb|gb|tb|pb|eb|zb|yb)/', strtolower($num), $match);

	$num = floatval(preg_replace('/[^0-9.]+/', '', $num));
	$match = $match[0];

	if($match == 'kb') {
		$num = $num * 1024;
	} else if($match == 'mb') {
		$num = $num * pow(1024, 2);
	} else if($match == 'gb') {
		$num = $num * pow(1024, 3);
	} else if($match == 'tb') {
		$num = $num * pow(1024, 4);
	} else if($match == 'pb') {
		$num = $num * pow(1024, 5);
	} else if($match == 'eb') {
		$num = $num * pow(1024, 6);
	} else if($match == 'zb') {
		$num = $num * pow(1024, 7);
	} else if($match == 'yb') {
		$num = $num * pow(1024, 8);
	} else {
		$num = $num;
	}
	
	return intval($num);
}

/*--------------------------------------
// Generate random strings for passwords
--------------------------------------*/
function string_generator($length, $separator) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $password = array();
    $rand = strlen($characters) - 1;

    for($i = 0; $i < $length; $i++) {
        $n = rand(0, $rand);
        $password[] = $characters[$n];
    }
	if(!empty($separator)) {
	    array_splice($password, 6, 0, $separator);
		array_splice($password, 13, 0, $separator);
		array_splice($password, 20, 0, $separator);
	}

    return implode($password);
}
?>