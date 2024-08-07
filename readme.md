# Goosle
## The best Meta Search Engine to find everything

Goosle is a fast, privacy oriented search tool that just works. \
It's kept simple so everyone can use it and to make sure it works on most webservers.

If you're looking for more varied results that are not laced with AI results and other non-features most people do not care for. Or if you're simply looking for traditional results from more than one search engine, Goosle has your back! Goosle searches on several search engine at the same time and shows you the most relevant results through a neat, clean interface. Goosle has **no** ads or sponsored results, **no** distractions, **no** trackers, **no** cookies and **no** bloated libraries, frameworks, dependencies or other things that slow you down.

Goosle does Image and News search. Collecting information from various sources and also shown in a simple easy to use manner.

On top of that, Goosle provides a safe and clean Magnet Link search tab along with a Box Office page. Find any torrent you like in seconds without malware, ads or other browser-breaking dangers that would otherwise require a VPN to safely use Torrent sites. Results are sourced from some of the largest torrent providers, compiled and ordered by the most seeders.

Host for yourself and friends, with a access hash key. Or set up a public search website.

After-all, finding things should be easy and not turn into a chore.

[![Goosle Mainpage](https://ajdg.solutions/assets/goosle/homepage-950.webp)](https://ajdg.solutions/assets/goosle/homepage-950.webp)

## Features
- Works on **any** hosting package that does PHP7.4 or newer
- Search results from DuckDuckGo, Google, Qwant, Brave, Wikipedia
- Image search through Yahoo! Images, Qwant, Pixabay and Openverse
- Recent news via Qwant news, Yahoo! News, Brave and Hackernews
- Search for magnet links on popular Torrent sites
- Algorithm for ranking search results for relevancy
- Option to down-rank the biggest social media sites such as facebook, instagram, twitter, tiktok, reddit, snapchat and a few others.
- Special searches for; Currency conversion, Dictionary, IP Lookup and php.net
- Randomized user-agents for to prevent profiling by search providers
- Non-personalized Google results without instant results or other non-sense
- Optional: Speed up repeat searches with APCu cache or file cache
- Optional: Basic access key as a basic way to keep your server to yourself
- Optional: Instant password generator on the start page

What Goosle does **not** have.
- Trackers and Cookies
- Ads, malware and distractions
- User profiles or user controllable settings
- Libraries, dependencies or Frameworks

And yet it just works... fast!

If you like Goosle, or found a use for it, please support my work and [donate](https://www.arnan.me/donate.html?mtm_campaign=goosle_readme) and tell everyone about its existence.

## Screenshots
[![Goosle Mainpage](https://ajdg.solutions/assets/goosle/homepage-250.webp)](https://ajdg.solutions/assets/goosle/homepage.webp)
[![Goosle Web Search](https://ajdg.solutions/assets/goosle/web-search-250.webp)](https://ajdg.solutions/assets/goosle/web-search.webp)
[![Goosle Image Search](https://ajdg.solutions/assets/goosle/image-search-250.webp)](https://ajdg.solutions/assets/goosle/image-search.webp)
[![Goosle News Search](https://ajdg.solutions/assets/goosle/news-search-250.webp)](https://ajdg.solutions/assets/goosle/news-search.webp)
[![Goosle Magnet Search](https://ajdg.solutions/assets/goosle/magnet-search-250.webp)](https://ajdg.solutions/assets/goosle/magnet-search.webp)
[![Goosle Boxoffice releases](https://ajdg.solutions/assets/goosle/boxoffice-250.webp)](https://ajdg.solutions/assets/goosle/boxoffice.webp)
[![Goosle Usage stats](https://ajdg.solutions/assets/goosle/usage-stats-250.webp)](https://ajdg.solutions/assets/goosle/usage-stats.webp)

## Requirements
Any basic webserver/webhosting package with PHP7.4 or newer. \
No special requirements other than APCu for caching (Optional). \
Developed on Apache with PHP8.2.

## Installation
1. Download and unzip Goosle.
2. In the main directory copy config.default.php to config.php.
3. Edit the config.php file and set your preferences.
4. Upload all files to your webserver. (eg. example.com or search.example.com or a sub-folder such as example.com/search/)
5. Rename goosle.htaccess to .htaccess or add its contents to your existing .htaccess file.
6. Load Goosle in your browser. If you've enabled the access hash, add *?a=YOUR_HASH* to the url.
7. Set up a background task (Cronjob) as described below. This runs a background task to delete old cache files and renews authorization tokens and checks for updates.
8. Let me know where you installed Goosle in the 'Show your Goosle' discussion on Github :-)

## Updating Goosle to a newer version
1. Download and unzip the latest release of Goosle.
2. Reconfigure Goosle with a new copy from config.default.php (Or, compare your config.php file with config.default.php and make sure any new settings or changed values are present)
3. Upload all the files to your webserver, overwriting all files except perhaps config.php.
4. Load Goosle in your browser. If you've enabled the access hash don't forget to add *?a=YOUR_HASH* to the url.
5. Enjoy your updated search experience!

Take a look at the [changelog](changelog.md) for every update here. \

## Setting up a Cronjob / background task
For a number of background tasks like clearing up the file cache and/or renewing your Openverse access token you need to set up a cronjob. \
Execute this cronjob a couple of times per day, recommended is every 8 hours.

Without it, Openverse access will expire and you have to generate a new key every few hours. \
For low traffic setups or if you do not use Openverse a longer interval of once a day is fine.

The access hash is always required as an access token, don't forget to include ?a=YOUR_HASH to the url. \
Cron jobs are commonly set up from your hosting dashboard, or through something like DirectAdmin, cPanel or WHM. \
Ask your hosting provider where to find the Cron job scheduler or have them set it up for you if you don't see it.

You can also use something like [cron-job.org](https://cron-job.org/) to trigger the background task remotely. \
To test, you can also load the url in your browser and trigger the script that way. Look for the onscreen prompts to see what routines are executed.

### Usage examples
Example for 10 minutes past every 3 hours \
`10 */3 * * * wget -qO - https://example.com/goosle-cron.php?a=YOUR_HASH`

Example for 5 minutes past every 8 hours (I use this on my Goosle) \
`5 */8 * * * wget -qO - https://example.com/goosle-cron.php?a=YOUR_HASH`

Example for every midnight \
`0 0 * * * wget -qO - https://example.com/goosle-cron.php?a=YOUR_HASH`

Why a few minutes past the hour? Because most people run stuff exactly on the hour or some other predictable interval like 15 or 30 minutes. Running things a few minutes later spreads server load.

## Authorizing access to the Openverse search API
OpenVerse image search provides (mostly) royalty free images. \
Millions of high quality photos from photographers from all over the world. \
If you're into high quality photo backgrounds, need images for blogs and articles or just like to look at high-res anything, then Openverse is a useful engine to use.

To use Openverse Image Search you'll need to register Goosle for an oAUTH access token.

Goosle includes a oAuth routine to easily register for an access token. \
- In your browser navigate to your goosle setup and add /functions/oauth-openverse.php to the url (ex. example.com/functions/oauth-openverse.php or example.com/functions/oauth-openverse.php?a=YOUR_HASH).
- Follow the onscreen prompts to get an authorization token to use Openverse.
- When prompted save the Client ID and Client Secret somewhere on your computer, in a note or something. Should the token file that Goosle creates get lost you'll need these strings to continue using Openverse.
- An email from Openverse will arrive within a few minutes with a confirmation link to finalize the set up.
- Once activated, enable openverse in your config.php and you're all set!

This procedure generates an access token which is stored in /cache/token.data, this token expires every 12 hours. Yeah, annoying! \
To automatically renew the token you can set up the Goosle cronjob as described elsewhere in this readme.

## API access to the Pixabay search API
Pixabay is a high quality photo and illustration database with (generally) royalty-free images. \
Pixabay has a database of hundreds of thousands of images provided by Photographers from all over the world. \
If you're a content creator who regularly need images for blogs and articles or just like to look at high-res photography, Pixabay is for you.

To get image results from Pixabay you'll need a free account to get a free API key. \
Register an account here: [https://pixabay.com](https://pixabay.com) (Click the Join button in the top right)

Once registered and logged in, you can find your API key in the Documentation here: [https://pixabay.com/api/docs/](https://pixabay.com/api/docs/), look for the first parameter specification (looks like a list), the Key will be highlighted in green at the top of it. Copy this key to your config.php into the pixabay_api_key option.

## Support
You can post your questions on Github Discussions or say hi on [Mastodon](https://mas.to/@arnan) or through my [website](https://www.arnan.me).

## Notes
- When using file caching you should set up a cronjob to execute goosle-cron.php every few hours. This deletes 'old' results.
- When you use Openverse for your image searches you should set up a cron job to execute goosle-cron.php every 11 hours or so. This will automagically renew the access token.
- If you want update notifications in the footer of Goosle set up the cron job so Goosle can ping Github weekly to see what's new.
- The .htaccess file has a redirect to force HTTPS, catch 404 errors with a redirect as well as browser caching rules ready to go.
- The robots.txt has a rule to tell all crawlers to not crawl Goosle. But keep in mind that not every crawler obeys this file.
- The access hash is NOT meant as a super secure measure and only works for surface level prying eyes.
- Results provided by Openverse and Pixabay are simplistic keyword matches which are not necessarily accurately sorted by relevancy.

## Known "issues"
- Duckduckgo sometimes returns a 202 header and no results. I'm not sure what causes that but suspect it's something to do with quotas or a service limitation on their end.
- Some crawlers for Magnet searches may return empty results. These are likely quota limits on their end.
