# Goosle
## The best Meta Search Engine to find magnet links

Goosle does Torrent (Magnet) search. Collecting information from various sources and also shown in a simple easy to use manner.

Goosle is a fast, privacy oriented search tool that just works. \
It's kept simple so everyone can use it and to make sure it works on most webservers.

If you're looking for results that are not laced with AI, popup ads and other non-features most people do not care for, Goosle has your back! Goosle searches on several search engines at the same time and shows you the most relevant magnet results through a neat, clean interface. Goosle has **no** ads or sponsored results, **no** distractions, **no** trackers, **no** cookies and **no** bloated libraries, frameworks, dependencies or other things that slow you down.

On top of that, Goosle provides a useful Box Office page for movies and TV Shows on YTS and EZTV. But also the newest magnets from other sites. Find any torrent you like in seconds without malware, ads or other browser-breaking dangers that would otherwise require a VPN to safely use Torrent sites. Results are sourced from some of the largest torrent providers, compiled and ordered by the most seeders.

Host for yourself and friends through the simple user profile system.

After-all, finding things should be easy and not turn into a chore.

[![Goosle Mainpage](https://ajdg.solutions/assets/goosle/homepage-950.webp)](https://ajdg.solutions/assets/goosle/homepage-950.webp)

## Features
- Works on **any** hosting package that does PHP7.4 or newer
- Search for magnet links on popular Torrent sites
- Simple algorithm and filters for ranking search results for relevancy
- Randomized user-agents for to prevent profiling by search providers
- Optional: Speed up repeat searches with APCu cache or file cache
- Optional: Basic access key as a basic way to keep your server to yourself

What Goosle does **not** have.
- Trackers and Cookies
- Ads, malware and distractions
- User profiles or user controllable settings
- Libraries, dependencies or Frameworks

And yet it just works... fast!

If you like Goosle, or found a use for it, please support my work and [donate](https://www.arnan.me/donate.html?mtm_campaign=goosle_readme) and tell everyone about its existence.

## Requirements
Any basic webserver/webhosting package with PHP7.4 or newer. \
No special dependencies other than APCu for caching (Optional). \
Developed on Apache with PHP8.2.

## Installation
1. Download and unzip Goosle.
2. In the main directory copy /data/config.default.php to /data/config.php.
3. Edit the config.php file and set your preferences.
4. Upload all files to your webserver. (eg. example.com or search.example.com or a sub-folder such as example.com/search/)
5. Rename goosle.htaccess to .htaccess or add its contents to your existing .htaccess file.
6. Load Goosle in your browser and follow the prompts.
7. Set up a background task (Cronjob) as described below. This runs a background task to delete old cache.

## Updating Goosle to a newer release
1. Download and unzip the latest release of Goosle.
2. Reconfigure Goosle with a new copy from config.default.php (Or, compare your config.php file with config.default.php and make sure any new settings or changed values are present)
3. Upload all the files to your webserver, overwriting all files except perhaps config.php.
6. Load Goosle in your browser and follow the prompts.

Take a look at the [changelog](changelog.md) for every update here.

## Installation and setup notes
- When using file caching you should set up a cronjob to execute gsl-cron.php every few hours. This deletes cached results.
- The .htaccess file has a redirect to force HTTPS, catch 404 errors with a redirect as well as browser caching rules ready to go.
- The robots.txt has a rule to tell all crawlers to not crawl Goosle. But keep in mind that not every crawler obeys this file.
- The access hash is NOT meant as a super secure measure and only works for surface level prying eyes.

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
`10 */3 * * * wget -qO - https://example.com/gsl-cron.php`

Example for 5 minutes past every 8 hours (I use this on my Goosle) \
`5 */8 * * * wget -qO - https://example.com/gsl-cron.php`

Example for every midnight \
`0 0 * * * wget -qO - https://example.com/gsl-cron.php`

Why a few minutes past the hour? Because most people run stuff exactly on the hour or some other predictable interval like 15 or 30 minutes. Running things a few minutes offset helps spread server load.

## Known "issues"
- YTS api does not reliably provide complete movie information for new additions, mostly missing movie summaries.
- Some crawlers for Magnet searches may return empty results. These are likely quota limits on their end.
