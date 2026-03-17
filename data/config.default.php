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

/* ------------------------------------------------------------------------------------
SITEURL:
	Set the base domain name for your Goosle setup (ex. example.com, something.example.com, example.com/something/) so that internal links will work correctly.

HASH:
	A lowercase alphanumeric string, something simple like: goosle, 13572468 or goosle1337.
	Used for caching, profiles, creating profiles and sharing magnet results.
	This is NOT a secure hash or password.

PROFILE_REQUIRED:
	If a profile is required or not. If set to 'off' everyone is a guest but also has admin access.
	If set to 'on', Everyone needs an account. Only selected accounts will be admin.

PROFILE_COOKIE:
	How long the login cookie stays valid in seconds after the last visit of the user.
	Any long value, say 3650 days (10 years), will work for 'infinite'.
	Guest users do not get a cookie.

CACHE_TYPE:
	Enabling caching also enables pagination for search results.
	It is highly recommended to enable caching as it will speed up repeat searches by a lot.
	The cache is NOT unique per user but shared between all users. Different users searching for the exact same thing get the same results.
	Results loaded from the cache are much much faster to load and do not make any requests to the search engines.

	Caching can be done in memory with APCu or as temporary files in the /cache/ folder.

	'off' No caching (not recommended).
	'file' Store results in text files (Default).
	'apcu' Faster, requires more memory.

CACHE_TIME:
	APCu stores in memory, using a longer cache time takes up more of it. It is recommended to not exceed a few (1 or 2?) hours for APCu.
	The file cache is only limited by your hosting storage space and can safely be much much longer if you want.
	To not show outdated results the 'limit' is 48 hours.
	Ignored if above 'CACHE_TYPE' option is set to off.
/* ------------------------------------------------------------------------------------
USER AGENTS:
	Add more or less user agents to the list but keep at least one!
	On every search, Goosle picks a user agent at random to identify as.
	Keep them generic to prevent profiling, but also so that the request comes off as a generic boring browser and not as a server/crawler.

	Safari and Firefox are generally safe to use.
	Chrome may attract attention because of the lack of Chrome information (tracking) aside from the user agent. The search engine may know something is 'weird'.
	Opera/Edge/Brave and many others use Chrome under the hood and are not a good pick for that reason.

	Do NOT use user agents for mobile devices or tablets. Where possible Goosle explicitly tells the service it's a desktop computer to get a certain format for results.
	Contradicting the request with a mobile user agent may get you banned.

MAGNET TRACKERS:
	Add more or less magnet trackers to the list but keep at least five or so!
	No one tracker knows everything, more trackers is usually better for faster discovery and downloads.
	Some search engines only provide torrent hashes. Goosle then uses these magnets to create a magnet link.
	Generally you do not need to change these unless you need some specific tracker.
------------------------------------------------------------------------------------ */

return (object) array(
	'siteurl' => 'example.com', // Make sure this is accurate (ex. example.com, goosle.example.com, example.com/goosle/)
	'hash' => '123456', // Lowercase alphanumeric string
	'profile_required' => 'off', // Default: off
	'profile_cookie' => 30, // Default: 30 (Days)
	'cache_type' => 'file', // Default: file
	'cache_time' => 8, // Default: 8 (Hours), see the recommendations above.
	'timezone' => 'UTC', // Default: UTC (Enter UTC+1, UTC-6 etc. for your timezone - Find yours https://time.is/UTC)

	'colorscheme' => 'default', // Default: default (Colorscheme to use. Choose: default, dark, light, auto)
	'safemode' => 1, // Default: 1 (0 = off, 1 = normal (default), 2 = on/strict)
	'show_search_source' => 'on', // Default: on (Shows below each search result)
	'show_yts_highlight' => 'on', // Default: on (Show latest YTS movies above Magnet search results)
	'show_share_option' => 'on', // Default: on (Show a share option for Magnet results)
	'show_zero_seeders' => 'off', // Default: off (Set to 'off' to hide torrents with 0 seeds)
	'search_results_per_page' => 30, // Default: 30 (Any number higher than 10 or lower than 100. Ignored if caching is off)
	'show_search_rank' => 'off', // Default: off (Useful for debugging)
	'querylog' => 'off', // Default: off (Create a log of queries in /cache/*.log to see if they are made and how much results they find and end up with after processing)

	// Keep at-least 1
	// - Mac: https://deviceandbrowserinfo.com/data/user_agent/human/Mac%20OS;;Firefox, 
	// - Windows: https://deviceandbrowserinfo.com/data/user_agent/human/Windows;;Firefox, 
	// - Linux: https://deviceandbrowserinfo.com/data/user_agent/human/Linux;;Firefox)
	'user_agents' => array(
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:134.0) Gecko/20100101 Firefox/134.0', // macOS 10.15 (Seqouia), Firefox 134
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 14.7; rv:134.0) Gecko/20100101 Firefox/134.0', // macOS 10.14 (Sonoma), Firefox 134
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 14.3; rv:123.0) Gecko/20100101 Firefox/123.0', // macOS 10.14 (Sonoma), Firefox 123
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:116.0) Gecko/20100101 Firefox/116.0', // Windows 10, Firefox 116
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0', // Windows 10, Firefox 134
		'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:109.0) Gecko/20100101 Firefox/116.0', // Windows 7, Firefox 116
		'Mozilla/5.0 (X11; Ubuntu; Linux x86_64) Gecko/20100101 Firefox/83.0', // Linux Ubuntu, Firefox 83
		'Mozilla/5.0 (X11; Linux x86_64; rv:135.0) Gecko/20100101 Firefox/135.0', // Linux, Firefox 135
		'Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0', // Linux, Firefox 131
	),

	// Keep at-least a bunch (5/10 or more?)
	'magnet_trackers' => array(
		'udp://tracker.coppersurfer.tk:6969',
		'udp://tracker.leechers-paradise.org:6969',
		'udp://p4p.arenabg.ch:1337',
		'udp://tracker.internetwarriors.net:1337',
		'udp://glotorrents.pw:6969/announce',
		'udp://torrent.gresille.org:80/announce',
		'udp://tracker.openbittorrent.com:80',
		'http://nyaa.tracker.wf:7777/announce',
		'udp://tracker.opentrackr.org:1337/announce',
		'udp://exodus.desync.com:6969/announce',
		'udp://tracker.torrent.eu.org:451/announce',
		'udp://opentracker.i2p.rocks:6969/announce',
		'udp://open.demonii.com:1337/announce',
		'udp://open.stealth.si:80/announce',
		'udp://tracker.moeking.me:6969/announce',
		'udp://explodie.org:6969/announce',
		'udp://tracker1.bt.moack.co.kr:80/announce',
		'udp://tracker.theoks.net:6969/announce',
		'udp://tracker-udp.gbitt.info:80/announce',
		'https://tracker.tamersunion.org:443/announce',
		'https://tracker.gbitt.info:443/announce',
		'udp://tracker.tiny-vps.com:6969/announce',
		'udp://tracker.dump.cl:6969/announce',
		'udp://tamas3.ynh.fr:6969/announce',
		'udp://retracker01-msk-virt.corbina.net:80/announce',
		'udp://open.free-tracker.ga:6969/announce',
		'udp://epider.me:6969/announce',
		'udp://bt2.archive.org:6969/announce',
	)
);
?>
