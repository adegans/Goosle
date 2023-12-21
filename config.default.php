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

/* ------------------------------------------------------------------------------------
HASH:
	A simple lowercase passphrase (something simple like: j9fg-i2du-er6m or 1846) used for caching results. This helps to differentiate between instances on the same server.
	You can also add it to your url/bookmark as a simple passphrase to keep unwanted users out.

HASH AUTH:
	Use the above hash as a simple passphrase.
	Using it as a passphrase lets you host Goosle on a public server without providing a public service.

	Usage: https://example.com/?a=1234567890
	Disclaimer: This is not meant to 'hack proof' or truly secure the setup. Just a simple token to keep surface level prying eyes out.
	
CACHE:
	If you have ACPu it is highly recommended to enable caching as it'll speed up repeatable searched by a lot.
	"on" (Recommended) for active sites, requires APCu
	"off" Disables cache, useful for testing or if your server lacks APCu support

CACHE_TIME:
	Minutes the result should be cached in ACPu.

ENABLE IMAGE SEARCH:
	Enable or disable image searches - Search results are provided by Qwant.
	"on" (Default)
	"off"

ENABLE TORRENT SEARCH:
	Enable or disable searching for torrent downloads.
	"on" (Default)
	"off"

SPECIAL:
	Enable or disable special searches that show up before search results.
	"on" (Default)
	"off" Disable this special search

USER AGENTS:
	Add more or less user agents to the list. Keep at least one.
	On every search Goosle picks one at random to identify as.
	Keep them generic to prevent profiling, but also so that the request comes off as a generic boring browser and not a server/script.
	
	Safari and Internet Explorer may be a limiting factor on results as they are lesser supported browsers. But should otherwise be fine.
	Chrome may attract attention because of the lack of Chrome information (tracking) aside from the user agent.
	Opera/Edge/Brave and many others use Chrome under the hood and are not a good pick for that reason.

SHOW ZERO SEEDERS:
	Set to "on" to include results with 0 seeders (slow or stale downloads). Off to exclude these results.

BLOCK 1337x CATEGORIES:
	Add category IDs of 1337x categories, check /engines/torrent/1337x.php for a list of known categories.

BLOCK PIRATEBAY CATEGORIES:
	Add category IDs of Pirate Bay categories, check /engines/torrent/thepiratebay.php for a list of known categories.

BLOCK YTS CATEGORIES:
	Add category names as keywords, eg; "thriller", "war".
	Movies can be in multiple categories, if a movie is in 5 categories it only has to match one to be filtered out.

TORRENT TRACKERS:
	Only used for The Pirate Bay and YTS.
	These are added to the magnet links Goosle creates. You can add more or replace the existing ones.
------------------------------------------------------------------------------------ */

return (object) array(
	"hash" => "j9fg-i2du-er6m",
    "hash_auth" => "off", // Default: off
    "cache" => "off", // Default: off
    "cache_time" => 30, // Default: 30

    "enable_image_search" => "on", // Default: on
    "enable_torrent_search" => "on", // Default: on

	"special" => array(
		"currency" => "on", // Currency converter
		"definition" => "on", // Word dictionary
		"wikipedia" => "on", // Wikipedia highlight
		"phpnet" => "on", // PHP-dot-net highlight
		"imdb_id_search" => "on", // Highlight IMDB IDs for tv shows, to use for torrent searches
		"password_generator" => "on" // Password generator on homepage
	),

	"user_agents" => array(
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15) Gecko/20100101 Firefox/119.0", // macOS 10.15, Firefox 119
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) Gecko/20100101 Firefox/116.0", // Windows 10, Firefox 116
		"Mozilla/5.0 (X11; Ubuntu; Linux x86_64) Gecko/20100101 Firefox/83.0", // Linux Ubuntu, Firefox 83
		"Mozilla/5.0 (X11; Linux i686) Gecko/20100101 Firefox/119.0", // Linux Generic, Firefox 119
		"Mozilla/5.0 (Linux; Android 5.0.2; AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/3.3 Chrome/38.0.2125.102 Safari/537.36", // Android 5, Samsungbrowser 3
		"Mozilla/5.0 (Linux; Android 7.0; AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.116 Safari/537.36", // Android 7, Chrome 60
		"Mozilla/5.0 (Linux; U; Android 4.2.2; he-il; AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Safari/534.30", // Android 4. Some webkit browser (Chrome?)
		"Mozilla/5.0 (iPhone12,1; U; CPU iPhone OS 13_0 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/15E148 Safari/602.1", // iOS 13, Safari
		"Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/13.2b11866 Mobile/16A366 Safari/605.1.15", // iOS 12, Firefox 13
	),

    "show_zero_seeders" => "on", // Default: on
    "leetx_categories_blocked" => array(3, 7, 47), // Default: 3, 7, 47
    "piratebay_categories_blocked" => array(206, 210), // Default: 206, 210
    "yts_categories_blocked" => array("horror"), // Default: "horror"
    "torrent_trackers" => array(
    	"http://nyaa.tracker.wf:7777/announce", 
    	"udp://open.stealth.si:80/announce", 
    	"udp://tracker.opentrackr.org:1337/announce", 
    	"udp://exodus.desync.com:6969/announce", 
    	"udp://tracker.torrent.eu.org:451/announce",
    ),

    "version" => "1.1b2" // Please don't change this
);
?>
