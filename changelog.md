# Goosle
## The best Meta Search Engine to find everything

### 1.7.1 - August 26, 2024
- NOTICE: config.default.php has changed, update your config.php!!
- [new] Engine timeouts for specific response codes when a search engine returns some kind of error or quota limitation
- [new] Engine timeouts status page at /functions/timeout-status.php
- [new] GloTorrents (Glodls) Magnet results
- [new] Use multiple search overrides (eg: 'size:large safe:off goose gone wild')
- [fix] Search suggestions re-added and they now work (Scraped from Mojeek, Google, DuckduckGo and Yahoo Images)
- [fix] Limetorrents search query is now properly formatted
- [fix] Search overrides (Safe search, image size, etc.) trigger no longer included in search query
- [fix] Search query no longer sent out double encoded
- [fix] Strings (Search queries, seo texts, etc.) are now properly limited to max length where required
- [fix] Multiple currency conversions now also works when caching is enabled
- [fix] Multiple ip lookups (from different user IPs) now also works when caching is enabled
- [update] Oauth page no longer center aligned
- [update] Updated Language explanation in config.default.php
- [change] Moved footer code to footer.php
- [change] Redid error.php to only include the error html
- [change] Openverse adds image category tags to image alt text
- [change] Pixabay uses image tags as image alt text
- [change] Image alt text now has a 1.5x multiplier for result ranking (was 1x)
- [change] Added imdb.com link to Box office popup
- [change] Added movie poster to Box office popup
- [change] Simplified verified uploader popup text

### 1.7 - August 7, 2024
- NOTICE: config.default.php has changed, update your config.php!!
- [new] Mojeek search results
- [new] Pixabay Image results (Requires free API key, see installation instructions)
- [new] Keyword multiplier for result ranking
- [new] Web search can be turned off
- [new] Cache News results for an hour only, regardless of the cache setting
- [new] Dynamic SEO description for results page (Should be visible when sharing the page)
- [new] 'Verified' label for magnet results where supported
- [update] Added x.com for social media detection
- [update] Added more keywords for nsfw detection in magnet results
- [change] Raised Qwant Images limit from 50 to 150
- [change] Raised Hackernews and Qwant News limit from 30 to 50
- [change] Lowered Wikipedia results from 10 to maximum 5
- [change] Replaced 'porn' with 'nsfw' for safe search switch
- [change] Removed 'xxx' as an keyword to disable safe search
- [change] Don't search on nyaa.si and YTS if you search with safemode off
- [change] Only search on sukebei.nyaa.si if you search with safemode off
- [change] Moved image size override into search object
- [change] Added a little space between rows for image results on mobile
- [change] Stats font is now 'Courier New'
- [fix] Google search query not providing good results
- [fix] Search query not always properly urlencoded
- [removed] Removed search suggestions as they didn't work

### 1.6.1 - July 19, 2024
- NOTICE: config.default.php has changed, update your config.php!!
- [new] Query logger for debugging (See config.default.php for details)
- [update] Added url arguments to the formatted url of search results
- [change] Scrape query for DuckDuckGo to be more direct
- [change] Improved tooltips to be popups with better explanations
- [fix] Improved spacing for pagination links
- [fix] More accurately show the current version in the footer
- [fix] Current version not properly stored
- [fix] Pagination offset off by one result
- [fix] Unnecessary global in load_search()
- [fix] Typo in wordpress search
- [fix] Qwant initial total hits and ranking more accurate
- [fix] Goosle header title not bold on stats page
- [fix] Visual fixes to the design of Goosle

### 1.6 - July 15, 2024
- NOTICE: config.default.php has changed, update your config.php!!
- [change] Moved magnet popups into combined function
- [change] Better handling of EZTV TV Show data
- [change] Better handling of YTS movie data
- [change] Added 6 new public trackers for Magnets
- [change] Removed regularly unresponsive trackers for Magnets
- [change] Search query string processed before search so all engines don't have to do it individually
- [change] Updated help page
- [new] Special searches can have a note/disclaimer in the lower right corner
- [new] Results pagination for all search tabs (Requires caching to be enabled)
- [new] WordPress function, hook and filter lookup as a special search (See help page)
- [new] Language meta data for some Magnet results
- [new] Try to detect audio codec for EZTV results
- [new] Show MPA Rating for some movie results
- [new] Filter to include NSFW Magnet results or not
- [new] Override NSFW filter with prefix keywords (see config.php)
- [new] Simple search stat counter (Link in footer)
- [tweak] Muted the blue and white text in dark theme a tiny bit
- [tweak] Better light blue header in light theme
- [tweak] Added title and alt attributes to relevant links/images
- [tweak] Removed Magnet search limit of 200 results
- [fix] HTML rendering issues for `<center>` tags in paragraphs
- [fix] Start page buttons in light theme now use the right css variables
- [fix] Properly decode quotes in code snippers for PHP special search
- [fix] Image, News and Magnet search no longer work if they're disabled in config.php
- [fix] 2nd search suggestion not showing if it's available
- [fix] Removed non-functional checking if query is empty in every engine
- [fix] Correctly uses user provided auth hash to keep searching
- [fix] Correctly 'expire' share links for guests so they can not use Goosle beyond seeing the shared results

### 1.5.1 - June 22, 2024
- [fix] Updated help.php, removed incorrect colorscheme information
- [fix] Typo in text output for goosle-cron.php
- [fix] Various php errors/warnings in goosle-cron.php
- [fix] Url formatting for php function special searches

### 1.5 - June 19, 2024
- NOTICE: config.default.php has changed, re-create your config.php!!
- [fix] No longer caches empty results
- [fix] No longer make a request if the search query is empty
- [fix] Movie highlight/box office cache now works
- [fix] Language selector for Qwant, Wikipedia and Duckduckgo
- [fix] Season and Episode filter for tv show searches
- [fix] Safe search filter now actually works
- [fix] Magnet Search category exclusion filter now actually works
- [fix] Image size filter works more reliably
- [fix] Handling of doublequotes in search queries
- [fix] Search sources now show result amounts accurately
- [fix] Old cache files are now actually deleted when expired
- [fix] Search tabs not properly centered on smaller screens
- [new] Box Office page with latest/new downloads from a few supported torrent websites
- [new] News page with the latest news from major outlets
- [new] Popup with movie info and download links for YTS Movie Highlights
- [new] CSS colorschemes configurable in config.php
- [new] Easily share magnet links with other Goosle users
- [new] Search results from Qwant API
- [new] Search results from Brave
- [new] Image results from Qwant Image API
- [new] News results from Hackernews
- [new] News results from Yahoo! News
- [new] News results from Brave News
- [new] News results from Qwant News API
- [new] Magnet results from Sukebei.nyaa.si
- [new] Special search for IP Lookups via ipify (Search for "ip" or "myip")
- [new] Safe search switch for Yahoo! Images
- [new] Image size switch for Qwant Images
- [new] Merge missing magnet meta data from duplicate results if it doesn't already exist in the matched previous result
- [new] Detect meta data for Magnet Search results such as sound and video quality.
- [tweak] Cache ttl is now in hours (was minutes)
- [tweak] Optimizations in CSS, HTML separators and more
- [tweak] Moved icons into CSS so they can be colored using colorschemes
- [tweak] Better handling of image results
- [tweak] Better handling of empty/incomplete results for all engines
- [tweak] Better handling of empty/missing meta data for all magnet engines
- [tweak] Better category detection for Limetorrent magnets
- [tweak] Raised Magnet search limit to 200 (was 50)
- [tweak] Raised Wikipedia search limit to 20 (was 10)
- [tweak] Hide magnet results with 0 seeders by default
- [tweak] Uniform array formatting for all engines
- [tweak] Consistent use of single-quotes and double-qoutes
- [tweak] File size string conversion and formatting for all image and magnet engines
- [tweak] Update checks are now done weekly(ish) via the Cron job
- [tweak] Updated .htaccess caching rules
- [removed] CSS for 320px viewport

### 1.4 - May 16, 2024
- NOTICE: config.default.php has changed, re-create your config.php!!
- [fix] Footer no longer overlaps results
- [fix] Search navigation no longer bunched up on smaller displays
- [fix] Double search type when searching from start page
- [new] Filter for additional/different headers per cURL request
- [new] Image search via Openverse API (Access token and cronjob required, see installation instructions)
- [new] Image search via Qwant
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

### 1.3 - April 11, 2024
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

### 1.2.2 - February 16, 2024
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

### 1.2.1 - January 15, 2024
- [new] Merge identical downloads (determined by info hash) from different torrent sites that provide hashes
- [new] Option to cache to flat files instead of APCu, files stored in /cache/ folder
- [new] Blank index.php files in all subfolders to shield from prying eyes
- [tweak] Improved version check
- [fix] Stray periods in some Limetorrent categories
- [fix] Inconsistent size indication for magnet results

### 1.2 - January 2, 2024
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

### 1.1 - December 21, 2023
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

### 1.0.2 - December 7, 2023
- [change] More useful error response when search doesn't work.
- [change] EngineRequest::request_successful() now provides a boolean response.
- [change] Removed versioning indicator from help page.
- [change] Added version indicator to results.php and help.php footer.
- [change] 'Nope, Go away!' for unauthorized users changed to 'Goosle'.
- [fix] Magnet links no longer opening in new tabs.

### 1.0.1 - December 5, 2023
- [fix] mktime() getting intermittent strings in 1337x crawler.
- [fix] mktime() getting intermittent strings in nyaa crawler.

### 1.0 - December 5, 2023
- Initial release

## Support
Goosle comes with limited support. \
You can post your questions on Github Discussions or say hi on [Mastodon](https://mas.to/@arnan) or [Telegram](https://t.me/arnandegans).
