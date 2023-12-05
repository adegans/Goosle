# Goosle
A fast, privacy oriented meta search engine that just works.
Kept simple so everyone can use it and to make sure it works on most (basic) webservers.

Host for yourself and friends, with a access hash key. Or set up a public search website.

After-all, finding things should be easy and not turn into a chore.

## Features
- Search on DuckDuckGo
- Search on Google.com
- Image search through Qwant
- Search for magnet links on popular Torrent sites
- Special searches for; Currency conversion, Dictionary, Wikipedia and php.net
- Instant password generator on the home page
- Randomized user-agents for to prevent profiling by search providers
- Works on *any* hosting package that does PHP7.4 or newer
- Optional: Access key as a very basic way to keep your server to yourself
- Optional: Speed up repeat searches with APCu cache if your server has it

What Goosle does *not* have.
- Trackers and Cookies
- User profiles or user controllable settings
- Javascripts or Frameworks

And yet it just works...

## Requirements
Any basic webserver/webhosting package with PHP7.4 or newer.
Tested to work on Apache with PHP8.2.

## Installation
1. Unzip the download.
2. Edit the config.php file with your preferences.
3. Upload all files to your webserver, for example to the root folder of a subdomain (eg. search.example.com) or a sub-folder on your main site (eg. example.com/search/)
4. Rename goosle.htaccess to .htaccess
5. Load the site in your browser. If you've enabled the access hash add ?a=YOURHASH to the url.

### Notes:
- The .htaccess file has a redirect to force HTTPS as well as browser caching instructions ready to go.
- The robots.txt has a rule to prevent all crawlers from crawling Goosle. But keep in mind that not every crawler obeys this file.
- The access hash is NOT meant as a super secure measure and only works for surface level prying eyes.

Have fun finding things!


## Disclaimer
Goosle started as a fork of LibreY, and ended up as a rewrite and something different completely. While the code structure remains largely the same, most functions have been rewritten or altered to work as I need it to.
Search results take design cues from DuckDuckGo and the torrent search has been modified to show more useful information where possible.

Goosle does not index, store or distribute torrent files. If you like, or found a use for, what you downloaded, you should probably buy a legal copy of it.

## Support
Goosle comes with limited support. You can post your questions on Github or on my support forum on [ajdg.solutions](https://ajdg.solutions/support/).

## Changelog
1.0 - December 5, 2023
- Initial release
