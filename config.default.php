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
	It is highly recommended to enable caching as it'll speed up repeat searches by a lot.

CACHE_TYPE:
	Choose how to cache results. The cache is NOT unique per user but shared between all users. Different users searching for the exact same thing get the same results.
	Default caching method is APCu. Alternatively, you can store the results in text files in the /cache/ folder.
	Ignored if above 'cache' option is set to off.
	"apcu" (Recommended) faster, utilize memory.
	"file" Store results in text files.

CACHE_TIME:
	Minutes the result should be cached. Accepts a numeric value between 1 and 720.
	APCu stores in memory, using a longer time takes up more of it. It is recommended to not exceed 30 minutes for it.
	The file cache is only limited by your hosting storage space and can safely be much much longer if you want.
	To not show outdated results the 'limit' is 720 minutes, which equals 12 hours.
	Ignored if above 'cache' option is set to off.



ENABLE IMAGE SEARCH:
	Enable or disable image searches - Search results are provided by Yahoo! Images.

ENABLE TORRENT SEARCH:
	Enable or disable searching for torrent downloads.

ENABLE SEARCH ENGINES:
	Enable or disable search engines.

ENABLE MAGNET CRAWLERS:
	Enable or disable crawlers to pull magnet links from.



LANGUAGE:
	DuckDuckGo, Google and Ecosia are language agnostic. But they DO profile you for your locale.
	For example: Me searching with english terms has me seeing Spanish results because I live in Mexico. This setting should minimize that for supported engines.

	DuckDuckGo uses language regions as opposed to a simpler language choice. See if your region is available - https://duckduckgo.com/duckduckgo-help-pages/settings/params/.
	Google's language option breaks reasonable results and other options like verbatim mode and some other settings. So you'll have to rely on Google picking up on the query language.
	Wikipedia needs to be told which language you want. This changes the search url. Use any of their supported languages (en, es, fr, nl, etc.)

SOCIAL MEDIA RELEVANCE:
	Show social media results lower in the combined results if you don't value such results.
	Downranked results include: Facebook, Instagram, Twitter, Snapchat, TikTok, LinkedIn and Reddit.
	!!CAREFUL!! This is a blanket setting, if what you're searching for primarily has social media links then less relevant results may show first.
	Accepts a numeric value between 1 and 10. With 10 having *NO* effect on the rank, and 0 not ranking the link at all (shows very very low in the results)

SHOW SEARCH SOURCE:
	Show which search engine(s) came up with the result.

SHOW SEARCH RANK:
	When search source is enabled, show the rank Goosle gave the result.

IMDB ID SEARCH:
	Highlight imdb results if it's a tv-show or movie.
	Handy for finding better results for specific tv-shows through EZTV and The Pirate Bay.

PASSWORD GENERATOR
	Show a password generator on the Goosle home page.


		
SPECIAL:
	Enable or disable special searches that show up before search results.



SHOW ZERO SEEDERS:
	Set to "on" to include results with 0 seeders (slow or stale downloads). Off to exclude these results.

YTS HIGHLIGHT:
	If you've enabled the YTS special search, you can also choose what it should show. The 8 most [insert choice] movies.
	"date_added" = Newest movies (Default).
	"rating" = Highest rated movies as per imdb.
	"download_count" = Most downloaded movies.

BLOCK 1337x CATEGORIES:
	Add category IDs of 1337x categories, check /engines/torrent/1337x.php for a list of known categories.
	Accepts a basic numeric array, comma separated.

BLOCK PIRATEBAY CATEGORIES:
	Add category IDs of Pirate Bay categories, check /engines/torrent/thepiratebay.php for a list of known categories.
	Accepts a basic numeric array, comma separated.

BLOCK YTS CATEGORIES:
	Add category names as keywords, eg; "thriller", "war".
	Movies can be in multiple categories, if a movie is in 5 categories it only has to match one to be filtered out.
	Accepts a basic array of keywords, comma separated.



USER AGENTS:
	Add more or less user agents to the list. Keep at least one!
	On every search Goosle picks one at random to identify as.
	Keep them generic to prevent profiling, but also so that the request comes off as a generic boring browser and not as a server/crawler.
	
	Safari, Firefox and Internet Explorer/Edge should be safe to use.
	Chrome may attract attention because of the lack of Chrome information (tracking) aside from the user agent. The search engine may know something is 'weird'.
	Opera/Edge/Brave and many others use Chrome under the hood and are not a good pick for that reason.
	Mobile agents may work, but some services like Wikipedia are a bit picky when it comes to answering API calls. Mobile users generally do not use APIs, so they may block your search.

TORRENT TRACKERS:
	Only used for The Pirate Bay, LimeTorrents and YTS.
	Generally you do not need to change these.
	These are added to the magnet links Goosle creates. You can add more or replace the existing ones if you know what you're doing.
	Accepts a basic array of strings (tracker urls), comma separated.
------------------------------------------------------------------------------------ */

return (object) array(
	"hash" => "j9fg-i2du-er6m",
    "hash_auth" => "off", // Default: off
    "cache" => "off", // Default: off
    "cache_type" => "apcu", // Default: apcu
    "cache_time" => 30, // Default: 30 (Minutes)

    "enable_image_search" => "on", // Default: on
    "enable_torrent_search" => "on", // Default: on
    "enable_duckduckgo" => "on", // Default: on
    "enable_google" => "on", // Default: on
    "enable_wikipedia" => "on", // Default: on
    "enable_ecosia" => "off", // Default: on	
    	// Site uses some kind of bot detector preventing crawler from working reliably since Feb 1, 2024, remove support in future release?)

    "enable_limetorrents" => "on", // Default: on
    "enable_piratebay" => "on", // Default: on
    "enable_yts" => "on", // Default: on
    "enable_nyaa" => "on", // Default: on	
    "enable_eztv" => "on", // Default: on
    "enable_l33tx" => "off", // Default: off
    	// Site now uses cloudflare preventing crawler from working since Jan 20, 2024, remove support in future release?)

    "duckduckgo_language" => "uk-en", // Default: uk-en (United Kingdom)
    "wikipedia_language" => "en", // Default: en (English)
    "social_media_relevance" => 8, // Default: 8
    "show_search_source" => "on", // Default: on
    "show_search_rank" => "off", // Default: off
	"imdb_id_search" => "off", // Default: off
	"password_generator" => "on", // Default: on

	"special" => array(
		"currency" => "on", // Default: on, Currency converter
		"definition" => "on", // Default: on, Word dictionary
		"phpnet" => "on", // Default: on, PHP-dot-net highlight
		"yts" => "on", // Default: on, Show latest, or highlighted movies from YTS
		"eztv" => "on" // Default: on, Show latest TV Show episodes from EZTV
	),

    "show_zero_seeders" => "on", // Default: on
    "yts_highlight" => "date_added", // Default: "date_added"
    "leetx_categories_blocked" => array(3, 7, 47), // Default: 3, 7, 47
    "piratebay_categories_blocked" => array(206, 210), // Default: 206, 210
    "yts_categories_blocked" => array("horror"), // Default: "horror"

	"user_agents" => array(
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15) Gecko/20100101 Firefox/119.0", // macOS 10.15, Firefox 119
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) Gecko/20100101 Firefox/116.0", // Windows 10, Firefox 116
		"Mozilla/5.0 (X11; Ubuntu; Linux x86_64) Gecko/20100101 Firefox/83.0", // Linux Ubuntu, Firefox 83
		"Mozilla/5.0 (X11; Linux i686) Gecko/20100101 Firefox/119.0", // Linux Generic, Firefox 119
	),

    "torrent_trackers" => array(
    	"http://nyaa.tracker.wf:7777/announce", 
		"http://tracker.openbittorrent.com:80/announce",
    	"udp://tracker.opentrackr.org:1337/announce", 
    	"udp://exodus.desync.com:6969/announce", 
    	"udp://tracker.torrent.eu.org:451/announce",
    	"udp://opentracker.i2p.rocks:6969/announce",
    )
);
?>
