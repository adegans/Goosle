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
SITEURL:
	Set the base domain name for your Goosle setup (ex. example.com, something.example.com, example.com/something/) so that internal links will work correctly.

HASH:
	A simple lowercase passphrase, something simple like: j9fg-i2du-er6m or 1846.
	Used for caching results and optionally for accessing Goosle (See HASH_AUTH option).

HASH_AUTH:
	Use the above hash as a simple passphrase.
	Using it as a passphrase lets you host Goosle on a public facing server without providing a public service.
	This is useful for if just you and some friends or family should be able to use Goosle from anywhere.

	"off" Don't use the hash as a password.
	"on" Use the hash as a password.

	Usage: https://example.com/?a=1234567890
	Disclaimer: This is not meant to 'hack proof' or truly secure the setup. Just a simple token to keep surface level prying eyes out.	

CACHE_TYPE:
	It is highly recommended to enable caching as it will speed up repeat searches by a lot.
	The cache is NOT unique per user but shared between all users. Different users searching for the exact same thing get the same results.

	Caching can be done in memory with APCu or as temporary files in the /cache/ folder.

	"off" No caching.
	"file" Store results in text files (Default).
	"apcu" Faster, utilizes memory.

CACHE_TIME:
	Minutes the result should be cached. Accepts a numeric value between 1 and 720.
	APCu stores in memory, using a longer time takes up more of it. It is recommended to not exceed 30 minutes for APCu.
	The file cache is only limited by your hosting storage space and can safely be much much longer if you want.
	To not show outdated results the 'limit' is 720 minutes, which equals 12 hours.
	Ignored if above 'CACHE_TYPE' option is set to off.
/* ------------------------------------------------------------------------------------
LANGUAGE:
	DuckDuckGo and Google are mostly language agnostic.
	
	To not fit the USA mold, Goosle defaults to the United Kingdom for english results.
	
	Google has no language setting because as soon as you specify it all 'anonymous' settings stop working.
	DuckDuckGo uses language regions and defaults to the United Kingdom. To change it see if your region is available - https://duckduckgo.com/duckduckgo-help-pages/settings/params/.
	Wikipedia needs to be told which language you want. This changes the search url. Use any of their supported languages (en, es, fr, nl, etc.)
	Qwant uses a locale similar to DuckDuckGo. Available locales are: bg_bg, br_fr, ca_ad, ca_es, ca_fr, co_fr, cs_cz, cy_gb, da_dk, de_at, de_ch, de_de, ec_ca, el_gr, en_au, en_ca, en_gb, en_ie, en_my, en_nz, en_us, es_ad, es_ar, es_cl, es_co, es_es, es_mx, es_pe, et_ee, eu_es, eu_fr, fc_ca, fi_fi, fr_ad, fr_be, fr_ca, fr_ch, fr_fr, gd_gb, he_il, hu_hu, it_ch, it_it, ko_kr, nb_no, nl_be, nl_nl, pl_pl, pt_ad, pt_pt, ro_ro, sv_se, th_th, zh_cn, zh_hk.

SOCIAL MEDIA RELEVANCE:
	Show social media results lower in the combined results if you don't value such results.
	Downranked results include websites like Facebook, Instagram, Twitter, Snapchat, TikTok, LinkedIn and Reddit.
	!! CAREFUL !! This is a blanket setting, if what (or who) you're searching for primarily has social media links then less relevant results may show first.
	Accepts a numeric value between 1 and 10. With 10 having *NO* effect on the rank, and 0 not ranking the link at all (shows very very low in the results).
/* ------------------------------------------------------------------------------------
USER AGENTS:
	Add more or less user agents to the list but keep at least one!
	On every search Goosle picks one at random to identify as.
	Keep them generic to prevent profiling, but also so that the request comes off as a generic boring browser and not as a server/crawler.
	
	Safari, Firefox and Internet Explorer (Yes that's old!) should be safe to use.
	Chrome may attract attention because of the lack of Chrome information (tracking) aside from the user agent. The search engine may know something is 'weird'.
	Opera/Edge/Brave and many others use Chrome under the hood and are not a good pick for that reason.
	Mobile user agents may work, but some services like Wikipedia are a bit picky when it comes to answering API calls. 
	Mobile users generally do not use APIs, so they may block your search or show a trimmed version of results.

MAGNET TRACKERS:
	These are added to the magnet links Goosle creates by itself. 
	Generally you do not need to change these.
	Currently only The Pirate Bay, LimeTorrents and YTS use generated magnet links.

	You can add more or replace the existing ones if you know what you're doing. But keep at least one, preferably 3-5+.
------------------------------------------------------------------------------------ */

return (object) array(
	// ALL OPTIONS ARE REQUIRED, EVEN IF YOU DO NOT USE THE FEATURE. EMPTY VALUES OR MISSING SETTINGS CAUSE ISSUES!!!
	"siteurl" => "example.com", // Make sure this is accurate
	"hash" => "j9fg-i2du-er6m", // Some kind of alphanumeric password-like string, used for caching and optionally for access to Goosle
    "hash_auth" => "off", // Default: off
    "cache_type" => "file", // Default: file
    "cache_time" => 30, // Default: 30 (Minutes)

    "enable_duckduckgo" => "on", // Default: on
    "enable_google" => "on", // Default: on
    "enable_qwantnews" => "on", // Default: on
    "enable_wikipedia" => "on", // Default: on

    "enable_image_search" => "on", // Default: on (Disables all image search regardless of settings for individual engines)
    "enable_yahooimages" => "on", // Default: on
    "enable_openverse" => "off", // Default: off (Requires API token, see readme for details)
    "enable_qwant" => "on", // Default: on

    "enable_magnet_search" => "on", // Default: on (Disables all image search regardless of settings for individual engines)
    "enable_eztv" => "on", // Default: on
    "enable_limetorrents" => "on", // Default: on
    "enable_nyaa" => "on", // Default: on	
    "enable_piratebay" => "on", // Default: on
    "enable_yts" => "on", // Default: on

    "duckduckgo_language" => "uk-en", // Default: uk-en (United Kingdom)
    "wikipedia_language" => "en", // Default: en (English)
	"qwant_language" => "en_gb", // Default: en_gb (United Kingdom)

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
    "piratebay_categories_blocked" => array(206, 210), // Default: 206, 210
    "yts_categories_blocked" => array("horror"), // Default: "horror"

	"user_agents" => array(
		"Lynx/2.8.5rel.1 libwww-FM/2.14 SSL-MM/1.4.1", // Linux, Lynx browser 2.8.5
		"Lynx/2.8.9rel.1 libwww-FM/2.14 SSL-MM/1.4.1", // Linux, Lynx browser 2.8.9
		"Lynx/2.8.6rel.4 libwww-FM/2.14 SSL-MM/1.4.1", // Linux, Lynx browser 2.8.6
		"TinyBrowser/2.0 (TinyBrowser Comment; rv:1.9.1a2pre) Gecko/20201231", // Linux, Tinybrowser 2
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15) DuckDuckGo/7 Safari/605.1.15", // macOS 10.15, DuckDuckGo 7
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15) Gecko/20100101 Firefox/119.0", // macOS 10.15, Firefox 119
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) Gecko/20100101 Firefox/116.0", // Windows 10, Firefox 116
		"Mozilla/5.0 (X11; Ubuntu; Linux x86_64) Gecko/20100101 Firefox/83.0", // Linux Ubuntu, Firefox 83
		"Mozilla/5.0 (X11; Linux i686) Gecko/20100101 Firefox/119.0", // Linux Generic, Firefox 119
	),

    "magnet_trackers" => array(
    	"http://nyaa.tracker.wf:7777/announce", 
    	"udp://tracker.opentrackr.org:1337/announce",
    	"udp://exodus.desync.com:6969/announce",
    	"udp://tracker.torrent.eu.org:451/announce",
    	"udp://opentracker.i2p.rocks:6969/announce",
    	"udp://open.demonii.com:1337/announce", 
		"udp://open.stealth.si:80/announce", 
		"udp://tracker.moeking.me:6969/announce", 
		"udp://explodie.org:6969/announce", 
		"udp://tracker1.bt.moack.co.kr:80/announce", 
		"udp://tracker.theoks.net:6969/announce", 
		"udp://tracker-udp.gbitt.info:80/announce", 
		"https://tracker.tamersunion.org:443/announce", 
		"https://tracker.gbitt.info:443/announce", 
		"udp://tracker.tiny-vps.com:6969/announce", 	
		"udp://tracker.dump.cl:6969/announce", 
		"udp://tamas3.ynh.fr:6969/announce", 
		"udp://retracker01-msk-virt.corbina.net:80/announce", 
		"udp://open.free-tracker.ga:6969/announce", 
		"udp://epider.me:6969/announce", 
		"udp://bt2.archive.org:6969/announce",
    )
);
?>