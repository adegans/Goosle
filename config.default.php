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

/* ------------------------------------------------------------------------------------
SITEURL:
	Set the base domain name for your Goosle setup (ex. example.com, something.example.com, example.com/something/) so that internal links will work correctly.

COLORSCHEME:
	Set a default colorscheme.

	'default' A dark headers and main backgrounds with light search results.
	'light' More light elements.
	'dark' More dark elements, some apps would call this dark mode.
	'auto' Let the browser decide what to use, uses dark.css for Darkmode. default.css for regular viewing.

	For advanced users: You can create your own colorschemes this way too.
	Duplicate the file /assets/css/default.css and name it something like 'mycolorscheme.css'.
	Edit the color variables to your liking.
	To use the colorscheme, use the filename without extension in this setting.

HASH:
	A simple lowercase passphrase, something simple like: goose1234 or 1846.
	Used for caching search results and optionally for accessing Goosle (See HASH_AUTH option).

HASH_AUTH:
	Use the HASH option as a simple passphrase.
	Using a passphrase lets you host Goosle on a public facing server without providing a public service.
	This is useful for if just you and some friends or family should be able to use Goosle from anywhere.

	'off' Don't use the hash as a password.
	'on' Use the hash as a password.

	Usage: https://example.com/?a=goose1234
	Disclaimer: This is not meant to 'hack proof' or truly secure the setup. Just a simple token to keep surface level prying eyes out.

CACHE_TYPE:
	It is highly recommended to enable caching as it will speed up repeat searches by a lot.
	The cache is NOT unique per user but shared between all users. Different users searching for the exact same thing get the same results.
	Results loaded from the cache are much much faster to load.

	Caching can be done in memory with APCu or as temporary files in the /cache/ folder.
	Enabling caching also enables pagination for search results.

	'off' No caching.
	'file' Store results in text files (Default).
	'apcu' Faster, requires more memory.

CACHE_TIME:
	APCu stores in memory, using a longer cache time takes up more of it. It is recommended to not exceed a few (1 or 2?) hours for APCu.
	The file cache is only limited by your hosting storage space and can safely be much much longer if you want.
	To not show outdated results the 'limit' is 48 hours.
	Ignored if above 'CACHE_TYPE' option is set to off.

/* ------------------------------------------------------------------------------------
LANGUAGE:
	To not fit the USA mold, Goosle defaults to the United Kingdom for english results.
	DuckDuckGo and Google are mostly language agnostic.
	Invalid values either cause the search engine to fail or will default to English depending on how wrong the value is.

	Google uses a search region and defaults to the United Kingdom. This usually applies to a country (us, uk, es, fr, nl, etc.)

	DuckDuckGo uses language regions and defaults to the United Kingdom. A list of all regions: https://duckduckgo.com/duckduckgo-help-pages/settings/params/.

	Qwant uses a locale similar to DuckDuckGo and defaults to the United Kingdom as well.
	Available locales are: bg_bg, br_fr, ca_ad, ca_es, ca_fr, co_fr, cs_cz, cy_gb, da_dk, de_at, de_ch, de_de, ec_ca, el_gr, en_au, en_ca, en_gb, en_ie, en_my, en_nz, en_us, es_ad, es_ar, es_cl, es_co, es_es, es_mx, es_pe, et_ee, eu_es, eu_fr, fc_ca, fi_fi, fr_ad, fr_be, fr_ca, fr_ch, fr_fr, gd_gb, he_il, hu_hu, it_ch, it_it, ko_kr, nb_no, nl_be, nl_nl, pl_pl, pt_ad, pt_pt, ro_ro, sv_se, th_th, zh_cn, zh_hk.

	Mojeek supports a few search regions: uk, de, fr, eu and '' (empty, no preference)

	Wikipedia needs to be told which language you want. This changes the search url. Use any of their supported languages (en, es, fr, nl, etc.)

SOCIAL MEDIA RELEVANCE:
	Show social media results lower in results if you don't value such results.
	This includes websites like Facebook, Instagram, Twitter/X, Snapchat, TikTok, LinkedIn and Reddit.
	!! CAREFUL !! This is a blanket setting, if what (or who) you're searching for primarily has social media links then less relevant results may show first.
	Accepts a numeric value between 1 and 10. With 10 having *NO* effect on the rank, and 0 not ranking the link at all (shows very very low in the results).
/* ------------------------------------------------------------------------------------
USER AGENTS:
	Add more or less user agents to the list but keep at least one!
	On every search Goosle picks a user agent at random to identify as.
	Keep them generic to prevent profiling, but also so that the request comes off as a generic boring browser and not as a server/crawler.

	Safari, Firefox and Internet Explorer (Yes that's old!) should be safe to use.
	Chrome may attract attention because of the lack of Chrome information (tracking) aside from the user agent. The search engine may know something is 'weird'.
	Opera/Edge/Brave and many others use Chrome under the hood and are not a good pick for that reason.

	Do NOT use user agents for mobile devices or tablets. Where possible Goosle explicitly tells the service it's a desktop computer to get a certain format for results.
	Contradicting the request with a mobile user agent may get your banned.

MAGNET TRACKERS:
	Add more or less magnet trackers to the list but keep at least five or so!
	No one tracker knows everything, more trackers is usually better for faster discovery and downloads.
	Some search engines only provide torrent hashes. Goosle then uses these magnets to create a magnet link.
	Generally you do not need to change these unless you need some specific tracker.
------------------------------------------------------------------------------------ */

return (object) array(
	'siteurl' => 'example.com', // Make sure this is accurate (ex. example.com, goosle.example.com, example.com/goosle/)
	'colorscheme' => 'default', // Default colorscheme to use
	'hash' => '123456', // Some kind of alphanumeric password-like string, used for caching and optionally for access to Goosle
	'hash_auth' => 'off', // Default: off
	'cache_type' => 'file', // Default: file
	'cache_time' => 8, // Default: 8 (Hours), see the recommendations above.
	'timezone' => 'UTC', // Default: UTC (Enter UTC+1, UTC-6 etc. for your timezone - Find yours https://time.is/UTC)

	'enable_web_search' => 'on', // Default: on (Disables all web search regardless of settings for individual engines)
	'web' => array(
		'duckduckgo' => 'on', // Default: on
		'mojeek' => 'on', // Default: on
		'qwant' => 'on', // Default: on
		'google' => 'on', // Default: on
		'brave' => 'on', // Default: on
		'wikipedia' => 'on' // Default: on
	),

	'enable_image_search' => 'on', // Default: on (Disables all image search regardless of settings for individual engines)
	'image' => array(
		'yahooimages' => 'on', // Default: on
		'qwantimages' => 'on', // Default: on
		'pixabay' => 'off', // Default: off (Requires free account from Pixabay.com, see readme for set up instructions)
		'openverse' => 'off', // Default: off (Requires oAuth token, see readme for set up instructions)
	),

	'enable_news_search' => 'on', // Default: on (Disables all news search regardless of settings for individual engines)
	'news' => array(
		'qwantnews' => 'on', // Default: on
		'yahoonews' => 'on', // Default: on
		'bravenews' => 'on', // Default: on
		'hackernews' => 'on', // Default: on
	),

	'enable_magnet_search' => 'on', // Default: on (Disables all magnet search regardless of settings for individual engines as well as the box office page)
	'magnet' => array(
		'limetorrents' => 'on', // Default: on (Anything)
		'piratebay' => 'on', // Default: on (Anything)
		'glotorrents' => 'on', // Default: on (Anything)
		'yts' => 'on', // Default: on (Movies)
		'eztv' => 'on', // Default: on (TV-Shows)
		'nyaa' => 'on', // Default: on (Anime)
		'sukebei' => 'on', // Default: on (NSFW Anime)
	),

	'duckduckgo_language' => 'uk-en', // Default: uk-en (United Kingdom)
	'mojeek_language' => 'en', // Default: en (English)
	'google_search_region' => 'uk', // Default: uk (United Kingdom)
	'qwant_language' => 'en_gb', // Default: en_gb (United Kingdom)
	'wikipedia_language' => 'en', // Default: en (English)

	'pixabay_api_key' => '', // Default: '' (Requires free account from Pixabay.com, see readme for set up instructions)

	'search_results_per_page' => 24, // Default: 24 (Any number between 8 and 160, preferably a multiple of 8. Ignored if caching is off)
	'social_media_relevance' => 8, // Default: 8
	'show_search_source' => 'on', // Default: on
	'imdb_id_search' => 'off', // Default: off, (Requires enable_magnet_search to also be on)
	'password_generator' => 'on', // Default: on

	'show_search_rank' => 'off', // Default: off (Useful for debugging)
	'querylog' => 'off', // Default: off (Create a log of queries in /cache/*.log to see if they are made and how much results they find and end up with after processing)

	'special' => array(
		'currency' => 'on', // Default: on, Currency converter
		'definition' => 'on', // Default: on, Word dictionary
		'ipaddress' => 'on', // Default: on, Look up your IP Address
		'phpnet' => 'on', // Default: on, PHP-dot-net functions highlight
		'wordpress' => 'off' // Default: off, Wordpress functions highlight
	),

	'show_nsfw_magnets' => 'off', // Default: off (Set to 'off' to try and hide adult content. Override with 'safe:off' or 'nsfw')
	'show_zero_seeders' => 'off', // Default: off (Set to 'off' to hide torrents with 0 seeds)
	'show_yts_highlight' => 'on', // Default: off (Show latest YTS movies above Magnet search results)
	'show_share_option' => 'on', // Default: on (Show a share option for Magnet results)
	'piratebay_categories_blocked' => array(206, 210), // Default: 206, 210 (Comma separated numbers, see /engines/magnet/thepiratebay.php for all categories)
	'yts_categories_blocked' => array('horror'), // Default: 'horror' (Comma separated keywords; array('action', 'drama', 'sci-fi') etc.. There is no defined list, so block keywords that you see on results and don't like)

	// Keep at-least 1
	'user_agents' => array(
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15) Gecko/20100101 Firefox/119.0', // macOS 10.15, Firefox 119
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Gecko/20100101 Firefox/116.0', // Windows 10, Firefox 116
		'Mozilla/5.0 (X11; Ubuntu; Linux x86_64) Gecko/20100101 Firefox/83.0', // Linux Ubuntu, Firefox 83
		'Mozilla/5.0 (X11; Linux i686) Gecko/20100101 Firefox/119.0', // Linux Generic, Firefox 119
	),

	// Keep at-least 5
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
