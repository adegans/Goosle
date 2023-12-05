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

CACHE:
	If you have ACPu it is highly recommended to enable caching as it'll speed up repeatable searched by a lot.
	"on" (Recommended) for active sites, requires APCu
	"off" Disables cache, useful for testing or if your server lacks APCu support

CACHE_TIME:
	Minutes the result should be cached in ACPu.

HASH AUTH:
	Use the above hash as a simple passphrase.
	Using it as a passphrase lets you host Goosle on a public server without providing a public service.

	Usage: https://example.com/?a=1234567890
	Disclaimer: This is not meant to 'hack proof' or truly secure the setup. Just a simple token to keep surface level prying eyes out.
	
RAW OUTPUT:
	"off" (Recommended) for active sites
	"on" Output the search results as an array instead of formatted with HTML
  
ENABLE TORRENT SEARCH:
	Enable or disable searching for torrent downloads.
	"on" (Default)
	"off"

ENABLE IMAGE SEARCH:
	Enable or disable image searches - Search results are provided by Qwant.
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
    "cache" => "off",
    "cache_time" => 30, // (Default: 30)
    "hash_auth" => "off", // Default: off)
    "raw_output" => "off", // (Default: off)

    "enable_torrent_search" => "on", // (Default: on)
    "enable_image_search" => "on", // (Default: on)

	"special" => array(
		"currency" => "on", // Currency converter
		"definition" => "on", // Word dictionary
		"wikipedia" => "on", // Wikipedia highlight
		"phpnet" => "on", // PHP-dot-net highlight
		"password_generator" => "on" // Password generator on homepage
	),

	"user_agents" => array(
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15) Gecko/20100101 Firefox/119.0", // macOS 10.15, FF 119
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) Gecko/20100101 Firefox/116.0", // Windows 10, FF 116
		"Mozilla/5.0 (X11; Ubuntu; Linux x86_64) Gecko/20100101 Firefox/83.0", // Linux Ubuntu, FF 83
		"Mozilla/5.0 (X11; Linux i686) Gecko/20100101 Firefox/119.0", // Linux Generic, FF 119
	),

    "leetx_categories_blocked" => array(3, 7, 47), // Default: 3, 7, 47
    "piratebay_categories_blocked" => array(206, 210), // Default: 206, 210
    "yts_categories_blocked" => array("horror"), // Default: "horror"
    "torrent_trackers" => array(
    	"http://nyaa.tracker.wf:7777/announce", 
    	"udp://open.stealth.si:80/announce", 
    	"udp://tracker.opentrackr.org:1337/announce", 
    	"udp://exodus.desync.com:6969/announce", 
    	"udp://tracker.torrent.eu.org:451/announce",
    )
);
?>
