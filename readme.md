<h1><center>Goosle</center></h1>
<h2><center>The best Meta Search Engine to find everything.</center></h2>

Goosle is a fast, privacy oriented search tool that just works. \
It's kept simple so everyone can use it and to make sure it works on most webservers.

If you're tired of traditional results from one site like Google search or DuckDuckGo and want to see more at once, Goosle has your back! Goosle searches on several search engine at the same time and shows you the most relevant results through a neat, clean interface. Goosle has **no** distractions, **no** trackers, **no** cookies and **no** javascript or other things to slow you down. 

Goosle does Image search which shows results from Yahoo! Images and Openverse.

On top of that, Goosle provides a safe and clean Magnet Link search tab. Find anything you like in seconds without malware, ads or other site-breaking nonsense that would otherwise require a VPN to safely use Torrent sites. Results are sourced from some of the largest torrent providers, compiled and ordered by the most seeders.

Host for yourself and friends, with a access hash key. Or set up a public search website.

After-all, finding things should be easy and not turn into a chore.

[![Goosle Mainpage](https://ajdg.solutions/wp-content/uploads/2023/12/goosle-mainpage-960x593.png)](https://ajdg.solutions/wp-content/uploads/2023/12/goosle-mainpage.png)

## Features
- Works on **any** hosting package that does PHP7.4 or newer
- Get search results from DuckDuckGo
- Get search results from Google
- Get search results from Qwant
- Get search results from Wikipedia
- Image search through Yahoo! Images, Qwant and Openverse
- Search for magnet links on popular Torrent sites
- Algorithm for ranking search results on the results page
- Option to down-rank the biggest social media sites such as facebook, instagram, twitter, tiktok, reddit, snapchat and a few others.
- Special searches for; Currency conversion, Dictionary and php.net
- Randomized user-agents for to prevent profiling by search providers
- Non-personalized Google results without instant results or other non-sense
- Optional: Speed up repeat searches with APCu cache if your server has it or a basic file cache
- Optional: Access key as a basic way to keep your server to yourself
- Optional: Instant password generator on the start page

What Goosle does **not** have.
- Trackers and Cookies
- Ads, malware and distractions
- User profiles or user controllable settings
- Javascripts or Frameworks

And yet it just works... fast!

If you like Goosle, or found a use for it, please support my work and [donate](https://www.arnan.me/donate.html?mtm_campaign=goosle_readme) and tell everyone about its existence.

## Screenshots
[![Goosle Mainpage](https://ajdg.solutions/wp-content/uploads/2024/01/goosle-main-150x150.jpg)](https://ajdg.solutions/wp-content/uploads/2024/01/goosle-main.jpg)
[![Goosle Web Search](https://ajdg.solutions/wp-content/uploads/2024/01/goosle-search-150x150.jpg)](https://ajdg.solutions/wp-content/uploads/2024/01/goosle-search.jpg)
[![Goosle Image Search](https://ajdg.solutions/wp-content/uploads/2024/01/goosle-images-150x150.jpg)](https://ajdg.solutions/wp-content/uploads/2024/01/goosle-images.jpg)
[![Goosle Magnet Search](https://ajdg.solutions/wp-content/uploads/2024/01/goosle-torrents-150x150.jpg)](https://ajdg.solutions/wp-content/uploads/2024/01/goosle-torrents.jpg)

## Requirements
Any basic webserver/webhosting package with PHP7.4 or newer. \
No special requirements other than APCu for caching (Optional). \
Tested to work on Apache with PHP8.0.24 and 8.2.x.

## Installation
1. Download and unzip Goosle.
2. In the main directory. Copy config.default.php to config.php.
3. Edit the config.php file and set your preferences.
4. Upload all files to your webserver. (eg. example.com or search.example.com or a sub-folder such as example.com/search/)
5. Rename goosle.htaccess to .htaccess or add its contents to your existing .htaccess file.
6. Load Goosle in your browser. If you've enabled the access hash, add *?a=YOUR_HASH* to the url.
7. Let me know where you installed Goosle :-)

## Updating Goosle to a newer version
1. Download and unzip the latest release of Goosle.
2. Check your config.php file and compare it to config.default.php. Go over your preferences. Make sure any new settings or changed values are present in your config.php. (Or reconfigure Goosle with a new copy from config.default.php)
3. Upload all files to your webserver, overwriting all files except perhaps config.php.
4. Load Goosle in your browser. If you've enabled the access hash don't forget to add *?a=YOUR_HASH* to the url.
5. Enjoy your updated search experience!

## Authorizing access to the Openverse search API
This is required to use Openverse Image Search.
In your browser navigate to your goosle setup and add /functions/oauth.php to the url (ex. example.com/functions/oauth.php or example.com/functions/oauth.php?a=YOUR_HASH). \
Follow the onscreen prompts to register Goosle with Openverse.

At the end, please save the Client ID and Client Secret somewhere on your computer, in a note or something. Should the token file Goosle creates get lost you'll need those strings to continue using Openverse.

This procedure generates an access token, this token expires every 12 hours. Yeah, annoying! \
To automatically renew the token you can set up the Goosle cronjob as described below.

## Setting up a cronjob
For a number of background tasks like clearing up the file cache and/or renewing your Openverse access token you need to set up a cronjob. \
Execute this cronjob a couple of times per day, recommended is every couple of hours.

Without it, Openverse access will expire and you have to generate a new key every day.
For low traffic setups or if you do not use Openverse a longer interval of two or three times a day is fine.

If you've enabled the access hash as a password, don't forget to include ?a=YOUR_HASH to the url.

Example for 10 minutes past every 3 hours \
`10 */3 * * * wget -qO - https://example.com/goosle-cron.php?a=YOUR_HASH`

Example for 5 minutes past every 8 hours (I use this on my Goosle) \
`5 */8 * * * wget -qO - https://example.com/goosle-cron.php?a=YOUR_HASH`

Example for every midnight \
`0 0 * * * wget -qO - https://example.com/goosle-cron.php?a=YOUR_HASH`

### Notes:
- When using file caching you should set up a cronjob to execute goosle-cron.php every few hours. This deletes 'old' results.
- When you use Openverse for your image searches you should set up a cron job to execute goosle-cron.php every 11 hours or less. This will automagically renew the access token.
- The .htaccess file has a redirect to force HTTPS, catch 404 errors with a redirect as well as browser caching rules ready to go.
- The robots.txt has a rule to tell all crawlers to not crawl Goosle. But keep in mind that not every crawler obeys this file.
- The access hash is NOT meant as a super secure measure and only works for surface level prying eyes.

Have fun finding things! Tell your friends!

## Support
Goosle comes with limited support. \
You can post your questions on Github Discussions or say hi on [Mastodon](https://mas.to/@arnan) or [Telegram](https://t.me/arnandegans).

### Known "issues"
- Duckduckgo sometimes returns a 202 header and no results. I'm not sure what causes that but suspect it's something to do with quotas or service limitation on their end.
- Some crawlers for Magnet searches may return empty results. These are likely quota limits on their end.

## Changelog
1.4 - May 16, 2024
- NOTICE: config.default.php has changed, re-create your config.php!!
- [fix] Footer no longer overlaps results
- [fix] Search navigation no longer bunched up on smaller displays
- [fix] Double search type when searching from start page
- [new] Filter for additional/different headers per cURL request
- [new] Image search via Openverse API (Access token and cronjob required, see installation instructions)
- [new] Image search via Qwant API
- [new] Web (recent news) search via Qwant API
- [tweak] Merged 'cache' option into 'cache-type', see config.default.php for details
- [tweak] Better filtering for duplicate web results
- [tweak] File size formatting for images more uniform
- [tweak] Optimized curl_multi_exec handling
- [tweak] Improved SEO headers
- [tweak] Layout tweaks and optimizations for search results, header and footer
- [tweak] Removed redundant HTML, CSS and some PHP
- [tweak] MagnetDL search disabled by default because of Cloudflare (Will probably be removed in future version)
- [tweak] Removed non-functional magnet trackers
- [tweak] Added 15 extra public magnet trackers
- [change] Removed Ecosia support
- [change] Removed Reddit support
- [change] Removed 1337x support
- [change] Removed MagnetDL support

1.3 - April 11, 2024
- [fix] Image search crawler filters out non-image results better
- [new] Crawler for results from magnetdl.com
- [new] Direct Reddit.com search, search for 'Top Posts' created in the past year
- [new] YTS movie highlights now link to YTS website when clicking the title
- [new] Placeholder image for missing eztv highlight thumbnails
- [tweak] Better hash matching for duplicate magnet results
- [tweak] Better checking for missing/empty values in image search results
- [tweak] Code cleanup
- [tweak] More uniform code/variable names
- [change] Naming overhaul - Replaced 'Torrent' with 'Magnet' throughout most of Goosle

1.2.2 - February 16, 2024
- [new] Individual on/off setting for each search engine and torrent site
- [new] YTS Highlights for latest releases, highest rated or most downloaded movies
- [new] EZTV Highlights for latest TV Show episode releases
- [new] Goosle-cron.php file for if you want to clear the file cache in the background
- [change] l33tx search disabled by default - They use Cloudflare now, preventing the crawler from working reliably
- [change] Ecosia search disabled by default - They use some kind of bot detector now, preventing the crawler from working once caught
- [change] Now uses an ABSPATH global for file inclusions and paths
- [change] More discrete TV Show and Movie result detection in text search
- [tweak] Filter for eztv search, only include eztv if the search term starts with 'tt' (case insensitive)
- [tweak] Better ecosia link formatting to (hopefully) not get blocked by their bot detector
- [tweak] cURL headers to be (even) more browser-like
- [fix] Variable $url sometimes empty for certain magnet results
- [fix] Blocked category filter for YTS results now actually works

1.2.1 - January 15, 2024
- [new] Merge identical downloads (determined by info hash) from different torrent sites that provide hashes
- [new] Option to cache to flat files instead of APCu, files stored in /cache/ folder
- [new] Blank index.php files in all subfolders to shield from prying eyes
- [tweak] Improved version check
- [fix] Stray periods in some Limetorrent categories
- [fix] Inconsistent size indication for magnet results

1.2 - January 2, 2024
- [new] Preferred language setting for DuckDuckGo results in config.php.
- [new] Preferred language setting for Wikipedia results in config.php.
- [new] Combined DuckDuckGo, Google, Wikipedia and Ecosia (Bing) results into one page.
- [new] Ranking algorithm for search results.
- [new] Option to down-rank certain social media sites in results (Makes them show lower down the page).
- [new] Option to show the Goosle rank along with the search source.
- [new] Crawler for results from Limetorrents.lol.
- [new] Periodic check for updates in footer.
- [change] Moved duckduckgo.php and google.php into the engines/search/ folder.
- [change] Removed Wikipedia special search in favor of actual search results.
- [change] Removed 'Date Added' from 1337x results.
- [change] Removed Chrome based and Mobile user-agents, as they don't work for the WikiPedia API.
- [change] Added more trackers for generating magnet links.
- [tweak] 30-50% faster parsing of search results (couple of ms per search query).
- [tweak] Expanded the season/episode filter to all sources that support TV Shows.
- [tweak] More sensible santization of variables (Searching for html tags/basic code should now work).
- [tweak] Moved 'imdb_id_search' out from special results into its 'own' setting.
- [tweak] Moved 'password_generator' out from special results into its 'own' setting.
- [tweak] More accurate and faster Google scrape.
- [tweak] Reduced paragraph margins.
- [tweak] More code cleanup, making it more uniform.
- [fix] Prevents searching on disabled methods by 'cheating' the search type in the url.
- [fix] Better decoding for special characters in urls for search results.
- [fix] Better validation for special searches trigger words.
- [fix] Better sanitization for DuckDuckGo and Google results.

1.1 - December 21, 2023
- [new] API search for EZTV TV Shows.
- [new] config.default.php with default settings.
- [new] New option 'imdb_id_search' in 'special' settings in config.php.
- [new] New option 'show_zero_seeders' in config.php.
- [new] Special result and redirect for IMDb IDs.
- [new] Replaced image search with Yahoo! Images.
- [new] Styled 'reset' button for search fields.
- [tweak] Removed 'raw_output' option.
- [tweak] Re-arranged results array to be more logical/easy to use.
- [tweak] Re-arranged code for results to do no double checks for search results.
- [tweak] Added more user-agents.
- [tweak] Magnet results page.
- [tweak] Sanitize scraped data earlier in the process.
- [tweak] Consistent single quotes for arrays.
- [tweak] Consistent spaces, tabs and newlines.
- [fix] Inconsistent input height for search field vs search button.
- [fix] Better check if a search is currency conversion or not.
- [fix] Typos in help.php.

1.0.2 - December 7, 2023
- [change] More useful error response when search doesn't work.
- [change] EngineRequest::request_successful() now provides a boolean response.
- [change] Removed versioning indicator from help page.
- [change] Added version indicator to results.php and help.php footer.
- [change] 'Nope, Go away!' for unauthorized users changed to 'Goosle'.
- [fix] Magnet links no longer opening in new tabs.

1.0.1 - December 5, 2023
- [fix] mktime() getting intermittent strings in 1337x crawler.
- [fix] mktime() getting intermittent strings in nyaa crawler.

1.0 - December 5, 2023
- Initial release

## Acknowledgements and stuff
Goosle started as a fork of LibreY, and ended up as a rewrite and something different completely. While the code structure remains largely the same, most functions have been rewritten or altered to work as I need it to. \
Search results take design cues from DuckDuckGo and the magnet search has been modified to show more useful information where possible. \
Goosle does not index, store or distribute torrent files. If you like, or found a use for, what you downloaded, you should probably buy a legal copy of it.

The name Goosle comes from my last name with an L added in. Translate it from Dutch.