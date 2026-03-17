<?php
/* ------------------------------------------------------------------------------------
*  Goosle - The fast, privacy oriented search tool that just works.
*
*  COPYRIGHT NOTICE
*  Copyright 2023-2025 Arnan de Gans. All Rights Reserved.
*
*  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
*  By using this code you agree to indemnify Arnan de Gans from any
*  liability that might arise from its use.
------------------------------------------------------------------------------------ */

/*--------------------------------------
// Load data file
// Input: string
// Return: array
--------------------------------------*/
function load_file($filename) {
	// Set up the file path
	$file = ABSPATH.'data/'.$filename;

	// Look for the file and return the data as array if found	
	if(!is_file($file)) {
		if($filename == 'stats.data') {
			$contents = array('started' => mktime(0, 0, 0, date('m'), date('d'), date('Y')), 'days_active' => 0, 'all_queries' => 0, 'avg_per_day' => 0);
		} else if($filename == 'version.data') {
			global $current_version;
		    $contents = array('current' => $current_version, 'latest' => '0.0', 'checked' => 0, 'url' => '');			
		} else {
			$contents = array();
		}

		error_log('WARNING: Could not load '.$filename.'. Attempting to create file!');
		file_put_contents($file, serialize($contents));

		return $contents;
	} else {
		$contents = unserialize(file_get_contents($file));
		
		// Make sure an array is returned
		if(is_array($contents)) {
	    	return $contents;
	    }
	}

    return array();
}

/*--------------------------------------
// Update data file
// Input: string, array
// Return: boolean
--------------------------------------*/
function update_file($filename, $data) {
	
	if(is_array($data)) {
		$file = ABSPATH.'data/'.$filename;
		
		if(!is_file($file)){
		    error_log('FATAL ERROR: Can not update '.$filename.'. File not readable or does not exist!');
		} else {
			file_put_contents($file, serialize($data));
			return true;
	    }
	}

    return false;
}

/*--------------------------------------
// Update profile data
// Input: string, string
// Return: string|array|boolean
--------------------------------------*/
function get_profile($uid, $data = '') {
	// Set up the file path
	$profiles = ABSPATH.'data/profile.data';

	// Look for the file and return the data as array if found	
	if(!is_file($profiles)){
		error_log('FATAL ERROR: Could not load profile.data!');
	} else {
		$profiles = unserialize(file_get_contents($profiles));
		
		if(array_key_exists($uid, $profiles)) {
			// Don't include sensitive information
			unset($profiles[$uid]['password']);

			// Get 1 value or the whole profile
			if(!empty($data) && array_key_exists($data, $profiles[$uid])) {
				// One value
				return $profiles[$uid][$data];
			} else {
				// Entire profile
				$profile = $profiles[$uid];
				
				return $profile;
			}
		}
	}

	// Fail
    return false;
}

/*--------------------------------------
// Update profile data
// Input: string, string, string|array
// Return: boolean
--------------------------------------*/
function update_profile($uid, $name, $data) {
	if(!empty($uid) && !empty($name) && !empty($data)) {
		// Get the profile
		$profiles = load_file('profile.data');
	
		// Update contents
		if(!empty($profiles)) {
			// Get the profile
			$profile = $profiles[$uid];

			// Update profile, put it in the main array and clean up
			$profile[$name] = $data; // (Should be) already sanitized elsewhere
			$profiles[$uid] = $profile;
			unset($uid, $name, $data, $profile);

		    // Store and return
		    return update_file('profile.data', $profiles);
		}
	}

	// Fail
    return false;
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

				// Only delete cache files (*.result)
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
// Log requests
--------------------------------------*/
function querylog($engine, $type, $request_url, $scraped_results, $final_results) {
	$log_file = ABSPATH.'cache/querylog_'.the_date('d_m_Y').'.log';
    file_put_contents($log_file, '['.the_date('d-m-Y H:i:s').']['.$type.'] '.$engine.': '.$scraped_results.' -> '.$final_results.', '.$request_url."\n", FILE_APPEND);
}
?>
