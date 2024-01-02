<h1><center>Goosle</center></h1>
<h2><center>The best Meta Search Engine to find everything.</center></h2>

Goosle is a fast, privacy oriented search tool that just works. \
It's kept simple so everyone can use it and to make sure it works on most (basic) webservers.

Replace Google search, replace DuckDuckGo and Ecosia but do not give up on it's search results! Goosle uses it all and shows you the most relevant results through a neat, clean interface. Goosle has **no** distractions, **no** trackers, **no** cookies and **no** javascript or other things to slow you down. 

On top of that, Goosle has a basic Image search tab which for now shows image results from Yahoo! Image Search.

And, also very useful, a safe and clean Torrent search tab. Find anything you like in seconds without malware, ads or other site-breaking nonsense that would otherwise require a VPN to safely use Torrent sites. Results are sourced from some of the largest torrent providers, compiled and ordered in by the most seeders.

Host for yourself and friends, with a access hash key. Or set up a public search website.

After-all, finding things should be easy and not turn into a chore.

[![Goosle Mainpage](https://ajdg.solutions/assets/goosle/goosle-main.jpg)](https://ajdg.solutions/assets/goosle/goosle-main.jpg)

## Features
- Works on **any** hosting package that does PHP7.4 or newer
- Get search results from DuckDuckGo
- Get search results from Google
- Get search results from Wikipedia
- Get search results from Ecosia (Bing)
- Image search through Yahoo! Images
- Algorithm for ranking search results on the results page
- Option to down-rank the biggest social media sites such as facebook, instagram, twitter, tiktok, snapchat and some others.
- Search for magnet links on popular Torrent sites
- Special searches for; Currency conversion, Dictionary and php.net
- Randomized user-agents for to prevent profiling by search providers
- Non-personalized Google results without instant results or other non-sense
- Optional: Speed up repeat searches with APCu cache if your server has it
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
[![Goosle Search results](https://ajdg.solutions/assets/goosle/goosle-search-150x150.jpg)](https://ajdg.solutions/assets/goosle/goosle-search.jpg)
[![Goosle Image results](https://ajdg.solutions/assets/goosle/goosle-images-150x150.jpg)](https://ajdg.solutions/assets/goosle/goosle-images.jpg)
[![Goosle Torrent results](https://ajdg.solutions/assets/goosle/goosle-torrents-150x150.jpg)](https://ajdg.solutions/assets/goosle/goosle-torrents.jpg)

## Requirements
Any basic webserver/webhosting package with PHP7.4 or newer. \
No special requirments other than APCu for caching. \
Tested to work on Apache with PHP8.0.24-8.2.x.

## Installation
1. Unzip the download.
2. In the main directory. Copy config.default.php to config.php.
3. Edit the config.php file and set your preferences.
4. Upload all files to your webserver, for example to the root folder of a domain (eg. example.com), subdomain (eg. search.example.com) or a sub-folder on your main domain (eg. example.com/search/)
5. Rename goosle.htaccess to .htaccess
6. Load the site in your browser. If you've enabled the access hash add *?a=YOURHASH* to the url.
7. Let me know where you installed Goosle :-)

## Updates
1. Unzip the download.
2. Check your config.php file and go over your preferences. Make sure any new settings are present in your config.php. (Or reconfigure Goosle with a new copy from config.default.php)
3. Upload all files to your webserver, overwriting the current Goosle files.
4. Load the site in your browser. If you've enabled the access hash don't forget to add *?a=YOURHASH* to the url.
5. Enjoy your updated search experience!

### Notes:
- The .htaccess file has a redirect to force HTTPS as well as browser caching rules ready to go.
- The robots.txt has a rule to prevent all crawlers from crawling Goosle. But keep in mind that not every crawler obeys this file.
- The access hash is NOT meant as a super secure measure and only works for surface level prying eyes.

Have fun finding things! And tell your friends!

## Support
Goosle comes with limited support. \
You can post your questions on Github Discussions or on my support forum on [ajdg.solutions](https://ajdg.solutions/support/?mtm_campaign=goosle_readme). \
Or say hi on [Mastodon](https://mas.to/@arnan) or [Telegram](https://t.me/arnandegans).

## Changelog
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
- [new] Special result and torrent redirect for IMDb IDs.
- [new] Replaced image search with Yahoo! Images.
- [new] Styled 'reset' button for search fields.
- [tweak] Removed 'raw_output' option.
- [tweak] Re-arranged results array to be more logical/easy to use.
- [tweak] Re-arranged code for results to do no double checks for search results.
- [tweak] Added more user-agents.
- [tweak] Torrent results page.
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
- [fix] Magnet links for torrents no longer opening in new tabs.

1.0.1 - December 5, 2023
- [fix] mktime() getting intermittent strings in 1337x crawler.
- [fix] mktime() getting intermittent strings in nyaa crawler.

1.0 - December 5, 2023
- Initial release

## Acknowledgements and stuff
Goosle started as a fork of LibreY, and ended up as a rewrite and something different completely. While the code structure remains largely the same, most functions have been rewritten or altered to work as I need it to. \
Search results take design cues from DuckDuckGo and the torrent search has been modified to show more useful information where possible. \
Goosle does not index, store or distribute torrent files. If you like, or found a use for, what you downloaded, you should probably buy a legal copy of it.

The name Goosle is my last name with an L added in. Translate it from Dutch. Not in any way a derivation of Google and DuckDuckGo combined :wink: