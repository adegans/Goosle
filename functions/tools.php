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

// Current Goosle version
$current_version = '2.0b61';

/*--------------------------------------
// Load and make config available, pass around variables
--------------------------------------*/
function load_opts() {
	$config_file = ABSPATH.'data/config.php';

	if(!is_file($config_file)) {
		echo "<h3>config.php is missing!</h3>";
		echo "<p>Please check the readme.md file for complete installation instructions.</p>";
		echo "<p>Configure Goosle by copying config.default.php to config.php. In config.php you can set your global preferences.</p>";

		die();
	} else {
		$opts = require $config_file;

		$opts->engines = array();

		// Discover all engines
		$engine_path = ABSPATH."engines/";
		$files = array_diff(scandir($engine_path, SCANDIR_SORT_ASCENDING), array('..', '.'));

		foreach($files as $file) {
			if(substr($file, -4) == ".php" && $file != "index.php") {
				$engine_info["filename"] = $file;
				$engine_info["enabled"] = "on";
				$engine_info["filter"] = array();

				$content = file_get_contents($engine_path.$file, false, null, 0, 1500);
				$content = explode(PHP_EOL, $content);

				foreach($content as $line) {
					// Detect the identifier from the class name
					if(strtolower(substr($line, 0, 6)) === "class ") {
						$line = explode(" ", $line);
						$identifier = $line[1];
						continue;
					}

					// These go into $engine_info
					if(strtolower(substr($line, 0, 8)) === "filter: ") {
						$line = substr($line, 8);
						$line = explode(", ", $line);
						$engine_info["filter"] = $line;
						continue;
					}

					if(strtolower(substr($line, 0, 6)) === "name: ") {
						$engine_info["name"] = substr($line, 6);
						continue;
					}

					if(strtolower(substr($line, 0, 13)) === "description: ") {
						$engine_info["description"] = substr($line, 13);
						continue;
					}

					if(strtolower(substr($line, 0, 12)) === "maintainer: ") {
						$engine_info["maintainer"] = substr($line, 12);
						continue;
					}

					if(strtolower(substr($line, 0, 9)) === "version: ") {
						$engine_info["version"] = substr($line, 9);
						continue;
					}

					unset($line);
				}

				// Only accept engines with complete headers/information
				if(count($engine_info) == 7) $opts->engines[$identifier] = $engine_info;
			}

			unset($file, $engine_info, $identifier);
		}

		// Set up engine timeouts
		$opts->timeouts = load_file('timeout.data');

		// Figure out server protocol
		$protocol = empty($_SERVER['HTTPS']) ? 'http' : 'https';
		$opts->baseurl = $protocol.'://'.$opts->siteurl.'/';

		// Force a few defaults and safeguards
		$opts->pixel = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
		if($opts->cache_type == 'file' && !is_dir(ABSPATH.'cache/')) $opts->cache_type = 'off';
		if($opts->cache_type == 'apcu' && !function_exists('apcu_exists')) $opts->cache_type = 'off';
		if($opts->cache_type == 'apcu' && !function_exists('apcu_exists')) $opts->cache_type = 'off';
		if($opts->cache_time < 1 || ($opts->cache_type == 'apcu' && $opts->cache_time > 8) || ($opts->cache_type == 'file' && $opts->cache_time > 48)) $opts->cache_time = 8;
		if(!is_numeric($opts->search_results_per_page) || ($opts->search_results_per_page < 10 || $opts->search_results_per_page > 100)) $opts->search_results_per_page = 30;
		if(!is_numeric($opts->safemode) || ($opts->safemode < 0 || $opts->safemode > 2)) $opts->safemode = 1;

		// Load the user settings and do a login
		$opts->user = do_login($opts);

		// Set user settings, if available
		if(isset($opts->user->settings)) {
			foreach($opts->user->settings as $key => $value) {
				if(property_exists($opts, $key)) $opts->$key = $value;
			}
			unset($opts->user->settings);
		}

		// Set user engines, if available
		if(isset($opts->user->engines)) {
			foreach($opts->user->engines as $engine => $choice) {
				if(array_key_exists($engine, $opts->engines)) {
					$opts->engines[$engine]["enabled"] = $choice;
				}

				unset($engine, $choice);
			}
			unset($opts->user->engines);
		}

		return $opts;
	}
}

/*--------------------------------------
// Let people in, or not, and load their settings
--------------------------------------*/
function do_login($opts) {
	$ttl = time() + ($opts->profile_cookie * 86400);
	$profile_tokens = load_file('profile-token.data');

	// Start the user object
	$user = new stdClass();
	$user->logged_in = false;
	$user->settings = array();
	$user->engines = array();

	// Using Cookie or Session (Already logged in)
	if(isset($_COOKIE['gsl_logged_in']) || isset($_SESSION['gsl_token'])) {
		// Figure out the session token
		if(!empty($_COOKIE['gsl_logged_in'])) {
			$gsl_token = sanitize_credentials($_COOKIE['gsl_logged_in']);
		} else if(!empty($_SESSION['gsl_token'])) {
			$gsl_token = $_SESSION['gsl_token'];
		} else {
			$gsl_token = null;
		}

		if(!empty($gsl_token)) {
			// Session exists and is not expired?
			if(array_key_exists($gsl_token, $profile_tokens) && $profile_tokens[$gsl_token]['ttl'] > time() && strlen($gsl_token) == 48) {
				// Keep the cookie alive
				setcookie('gsl_logged_in', $gsl_token, $ttl, '/', $opts->siteurl, 1, 1);

				// Load profile
				$profile = get_profile($profile_tokens[$gsl_token]['uid']);

				// Set up user object
				$user->logged_in = true;
				$user->uid = $profile_tokens[$gsl_token]['uid'];
				$user->nicename = $profile['nicename'];
				$user->username = $profile['username'];
				$user->admin = $profile['admin'];
				$user->settings = $profile['settings'];
				$user->engines = $profile['engines'];
			} else {
				// Something is wrong with the token (expired or invalid), please log in again
				$user->error = "error_invalid_token";

				// End the session
				session_destroy();
				setcookie('gsl_logged_in', '', time() - 10, '/', $opts->siteurl, 1, 1);
			}
		} else {
			// Session token is empty/missing
			$user->error = "error_no_token";

			// End the session
			session_destroy();
			setcookie('gsl_logged_in', '', time() - 10, '/', $opts->siteurl, 1, 1);
		}

		unset($gsl_token, $profile, $profile_tokens, $profile_settings);

		return $user;
	}

	// Using login or registration form
	if(isset($_POST['username']) && isset($_POST['password']) && $user->logged_in == false) {
		$profiles = load_file('profile.data');

		$username = sanitize_credentials($_POST['username']);
		$password = sanitize_credentials($_POST['password']);
		$uid = md5($username);

		if(isset($_POST['reg']) && isset($_POST['password_confirm']) && !array_key_exists($uid, $profiles)) {
			// ----------------------------------------------
			// Register a new profile?
			// ----------------------------------------------

			$reg_auth = (isset($_POST['userhash'])) ? sanitize($_POST['userhash']) : '';
			$password2 = (isset($_POST['password_confirm'])) ? sanitize_credentials($_POST['password_confirm']) : '';

			if($reg_auth !== $opts->hash) {
				// Hash does not match
				$user->error = "error_hash_no_match";
			} else if(strlen($username) < 5 || strlen($username) > 15) {
				// Username shorter than 5 or longer than 15 characters
				$user->error = "error_username_length";
			} else if(preg_match('/[^a-zA-Z0-9]/', $username)) {
				// Has special characters in username
				$user->error = "error_username_has_special_chars";
			} else if($password !== $password2) {
				// Passwords do not match
				$user->error = "error_password_no_match";
			} else if(strlen($password) < 10 || strlen($password) > 100) {
				// Password shorter than 10 or longer than 100 characters
				$user->error = "error_password_length";
			} else if(!preg_match('/[^a-zA-Z0-9]/', $password)) {
				// No special characters in password
				$user->error = "error_password_no_special_chars";
			} else {
				// Is this the first ever registration? We need an admin...
				$is_admin = (count($profiles) === 0) ? "yes" : "no";

				// Set up a new profile
				$profiles[$uid] = array(
					'username' => $username,
					'nicename' => $username,
					'admin' => $is_admin,
					'registered' => time(),
					'password' => password_hash($password, PASSWORD_DEFAULT),
					'settings' => array(
						'colorscheme' => $opts->colorscheme,
						'safemode' => $opts->safemode,
						'show_search_source' => $opts->show_search_source,
						'show_yts_highlight' => $opts->show_yts_highlight,
						'show_share_option' => $opts->show_share_option,
						'show_zero_seeders' => $opts->show_zero_seeders
					),
					'engines' => $opts->engines
				);

				// Update profile file
				if(update_file('profile.data', $profiles)) {
					// Do a cookie and session
					$_SESSION['gsl_token'] = string_generator(48);
					setcookie('gsl_logged_in', $_SESSION['gsl_token'], $ttl, '/', $opts->siteurl, 1, 1);

					// Update token file
					$profile_tokens[$_SESSION['gsl_token']] = array('uid' => $uid, 'ttl' => $ttl);
					update_file('profile-token.data', $profile_tokens);

					// Set up user object
					$user->logged_in = true;
					$user->uid = $uid;
					$user->nicename = $username;
					$user->username = $username;
					$user->admin = $is_admin;
					$user->settings = $profiles[$uid]['settings'];
					$user->engines = $profiles[$uid]['engines'];
				}
			}
		} else if(array_key_exists($uid, $profiles) && !isset($_POST['reg']) && !isset($_POST['password_confirm'])) {
			// ----------------------------------------------
			// Existing profile, so we're not registering a new one
			// ----------------------------------------------

			// Load profile
			$profile = $profiles[$uid];

			if(password_verify($password, $profile['password']) && $profile['username'] === $username) {
				session_regenerate_id();

				// Do a cookie and session
				$_SESSION['gsl_token'] = string_generator(48);
				setcookie('gsl_logged_in', $_SESSION['gsl_token'], $ttl, '/', $opts->siteurl, 1, 1);

				// Update token file
				$profile_tokens[$_SESSION['gsl_token']] = array('uid' => $uid, 'ttl' => $ttl);
				update_file('profile-token.data', $profile_tokens);

				// Profile profile
				$user->logged_in = true;
				$user->uid = $profile_tokens[$gsl_token]['uid'];
				$user->nicename = $profile['nicename'];
				$user->username = $profile['username'];
				$user->admin = $profile['admin'];
				$user->settings = $profile['settings'];
				$user->engines = $profile['engines'];
			} else {
				// Incorrect username or password
				$user->error = "error_credentials";
			}
		} else {
			// Unknown account or (if registering) account may already exist
			$user->error = "error_account";
		}

		unset($ttl, $reg_auth, $username, $password, $password2, $uid, $is_admin, $profiles, $profile, $profile_tokens, $profile_settings);

		return $user;
	}

	// Still not logged in? Maybe you're a guest user (Profiles are optional)
	if($user->logged_in == false && $opts->profile_required == 'off') {
		$user->logged_in = true;
		$user->uid = "guest";
		$user->username = "guest";
		$user->nicename = "Guest";
		$user->admin = "no";
	}

	unset($guest_auth, $profile_tokens);

	return $user;
}

/*--------------------------------------
// Process search query
--------------------------------------*/
function load_search() {
	global $opts;

	$search = new stdClass();

	// Regular search
	$search->query = (isset($_REQUEST['q'])) ? trim($_REQUEST['q']) : '';

	// Set pagination page
	$search->page = (isset($_REQUEST['p'])) ? sanitize($_REQUEST['p']) : 1;

	// Preserve quotes
	$search->query = str_replace('%22', '\"', $search->query);
	$search->query = str_replace('%27', '\'', $search->query);

	// Safe search override
	// 0 = off, 1 = normal (default), 2 = on/strict
	$search->safe = 1;
	if($opts->user->logged_in && $search->safe != $opts->safemode) {
		$search->safe = $opts->safemode;
	}

    $search->query_urlsafe = urlencode($search->query);

	// Count stats
	if(!empty($search->query)) count_stats();

	return $search;
}

/*--------------------------------------
// Do some stats
--------------------------------------*/
function count_stats() {
	$stats = load_file('stats.data');

	if(!empty($stats)) {
		// Calculate average searches per day
		$new_day = (mktime(0, 0, 0, date('m'), date('d'), date('Y')) - $stats['started']) / 86400;
		if($new_day > $stats['days_active']) {
			$stats['days_active'] = $stats['days_active'] + 1;
			$stats['avg_per_day'] = $stats['all_queries'] / $stats['days_active'];
		}

		// Count query
		$stats['all_queries'] = $stats['all_queries'] + 1;

		// Save stats
	    update_file('stats.data', $stats);
	}
}

/*--------------------------------------
// Standardized cURL requests that support both POST and GET
// Used for Box Office and Update checks
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
// Set a timeout if an engine is being mean to us
--------------------------------------*/
function set_timeout($engine, $http_code) {
	$timeouts = load_file('timeout.data');

	// 3600 seconds in an hour, 86400 in a day, 604800 for a week
	if($http_code == 401 || $http_code == 403) {
		// Unauthorized / banned
		$timeout = 345600; // 4 days
	} else if($http_code == 410) {
		// Resource no longer available
		$timeout = 86400; // 1 day
	} else if($http_code == 429) {
		// Too many requests
		$timeout = 1800; // 30 minutes
	} else if($http_code >= 500 || $http_code < 600) {
		// Some kind of server error
		$timeout = 43200; // 12 hours
	} else {
		// Unspecified error/status
		$timeout = 900; // 15 minutes
	}

	$timeouts[$engine] = time() + $timeout;

	update_file('timeout.data', $timeouts);
}

/*--------------------------------------
// Engine has a timeout?
--------------------------------------*/
function has_timeout($engine) {
	global $opts;

	if(isset($opts->timeouts)) {
		if(isset($opts->timeouts[$engine])) {
			if($opts->timeouts[$engine] > time()) return true;
		}
	}

	return false;
}

/*--------------------------------------
// Get the basic domain.tld url
--------------------------------------*/
function get_base_url($url = '') {
	global $opts;

	if(empty($url)) {
		$url = $opts->siteurl;

		// Figure out server protocol
		$protocol = empty($_SERVER['HTTPS']) ? 'http' : 'https';
		$url = $protocol.'://'.$url;
	} else {
		$url = parse_url($url);
		$url = $url['scheme'].'://'.$url['host'];
	}

	return $url;
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
// Sanitize/format variables
--------------------------------------*/
function sanitize($variable, $keep_newlines = false) {
	switch(gettype($variable)) {
		case 'string':
			if(str_contains($variable, '<')) {
				$variable = preg_replace('/<(\s;)?br \/>/im', ' ', $variable);
				$variable = strip_tags($variable);
				$variable = str_replace("<\n", "&lt;\n", $variable);
			}

			if(!$keep_newlines) {
				$variable = preg_replace('/[\r\n\t ]+/', ' ', $variable);
			}

			$variable = trim(preg_replace('/ {2,}/', ' ', $variable));
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

// Clean up usernames and passwords
function sanitize_credentials($variable) {
	// Remove all tags/html
	$variable = strip_tags($variable);
	// Remove percent-encoded characters.
	$variable = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $variable);
	// Remove HTML entities
	$variable = preg_replace('/&.+?;/', '', $variable);
	// Remove all whitespace
	$variable = preg_replace('|\s+|', '', $variable);
	$variable = trim($variable);

	return $variable;
}

// Used for result descriptions, saerch query length limitation
function limit_string_length($string, $length = 200, $append = '&hellip;') {
	$string = trim($string);

	if(strlen($string) > $length) {
		$string = wordwrap($string, $length);
		$string = explode("\n", $string);
		$string = $string[0] . $append;
	}

	return $string;
}

// Used for special searches
function make_terms_array_from_string($string) {
	if(empty($string)) return array();

	$string = strtolower($string);

	// Replace anything but alphanumeric with a space
	$string = preg_replace('/\s+|[^a-z0-9]+/', ' ', $string);
	// Filter unique terms with a comma as separator
	$keywords = array_filter(array_unique(explode(' ', $string)));

    return $keywords;
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
// Generate random strings for passwords, hashes, etc.
--------------------------------------*/
function string_generator($length, $separator = '') {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $password = array();
    $rand = strlen($characters) - 1;

    for($i = 0; $i < $length; $i++) {
        $n = rand(0, $rand);
        $password[] = $characters[$n];
    }

	if(!empty($separator)) {
		$one = $length / 4;
	    array_splice($password, $one, 0, $separator);
	    $two = ($one * 2) + 1;
		array_splice($password, $two, 0, $separator);
		$three = ($one * 3) + 2;
		array_splice($password, $three, 0, $separator);

		unset($one, $two, $three);
	}

    return implode($password);
}
?>
