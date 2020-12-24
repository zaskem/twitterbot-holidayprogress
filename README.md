# twitterbot-holidayprogress
A novelty bot written in PHP to tweet the progress toward and announcement of the next holiday/event. General idea inspired by the [Year Progress](https://twitter.com/year_progress) bot.

Data is sourced from Google calendar (public or shared) via the Google Calendar API, analyzed, and tweeted via Twitter's API with a homegrown `statuses/update.json` POST implementation.

## Requirements
To run the bot code, the following libraries/accounts/things are required:

* [Google APIs Client Library for PHP](https://github.com/googleapis/google-api-php-client) must be installed/available on the bot host;
* A project with service account and key pair from the [Google API Console](https://console.developers.google.com);
* A bot/user account on Twitter for tweets;
* A project and app configured on the [Twitter Developer Portal](https://developer.twitter.com/);
* A manner by which you can generate an OAuth token and grant permission to the app for the bot account as necessary; and
* A host on which to run this code (not at a browsable path).

### Google API
Creating a project, creating a service account and key for the Google API falls outside the scope of this README. At a minimum, a project must be available on the [Google API Console](https://console.developers.google.com) and a Service Accont with an automatically-generated key pair created. As the bot is a consumer of the Google API, no special permissions are required for the service account. You will need the key name/ID and associated `.json` file.

### Twitter API
Applying for access to the [Twitter Developer Portal](https://developer.twitter.com/) is outside the scope of this README. You will need to create a new Project and/or App for the Twitter bot, configure the App permissions to allow `Read and Write` access. You will obtain the `consumer_key` and `consumer_secret` from the App's keys and tokens page.

Assuming the account associated with the Developer Portal is _not_ the bot account, you will need to enable `3-Legged OAuth` for the App. This is required to generate a user access token and secret for an independent bot account.

#### A note about generating user access tokens and secrets:
This repo does not include a library/mechanism to address user access and callback for the bot app, which is ___required___ to generate a user access token and secret, and is a one-time action. It is recommended to use [Twurl](https://developer.twitter.com/en/docs/tutorials/using-twurl) for its simplicity, in particular the following short steps on a local WSL/Ubuntu instance independent of the bot host:

1. `gem install twurl` (to install Twurl, also requires ruby)
2. `twurl authorize --consumer-key key --consumer-secret secret` (with your Twitter App key/secret, follow prompts)
3. `more ~/.twurlrc` (to obtain the bot account `token` and `secret` values)

## Bot Configuration
Five configuration files should exist in the `config/` directory. Example/Stubout versions are provided, and each one of these files should be copied without the `.example` extension (e.g. `cp bot.php.example bot.php`):

* `bot.php`
* `google.php`
* `tweets.php`
* `twitter.php`
* `status.php` (optional: file is autocreated and autoupdated)

Edit each file as necessary for your bot (`status.php` can be ignored). Note that `google.php` and `twitter.php` require the most cusomization as they contain the source calendar ID, key/secret and other path/data source information.

## Bot Usage, Crontab Setup
The entire process can be kicked off with a simple command:
`php TweetProgress.php`

Out of the box, this command will return a JSON string with debug information to verify the bot was successful (or not). Once satisfied/ready for production, setting `$debug_bot = false;` in `config/bot.php` will disable this output.

Cron can/should be used for production. A simple default crontab setting might look like this:
```bash
*/15 * * * * /path/to/php /path/to/TweetProgress.php
```
The above will run the bot script every 15 minutes, which would adequately tweet almost every single percentage increment over the course of a day. Set this to whatever makes sense for the source calendar event cadence.

## Tweet Posting
By design, `TweetProgress.php` will _not_ attempt to post a tweet if the progress percentage hasn't changed (and some other scenarios). The debug information in `skipped*` values will identify reasons for a skipped post.

`TweetPost.php` should require no direct modification or attention. It is a self-contained homegrown mechanism to generate a valid OAuth signature and POST a status update request to Twitter's API. Using `TweetPost.php` removes the need for third-party libraries (such as Twurl) on the production bot host to _tweet updates_. That said, the bot itself (`TweetProgress.php`) can be easily modified to use a library such as Twurl if available/desired.

## Troubleshooting and Tweet Posting
This bot doesn't have a lot of moving parts, so there's not a lot to troubleshoot. There are two likely problems to troubleshoot:

* Failure to get calendar/source data; and
* Failure to post a tweet.

Setting `$debug_bot` to `true` in `config/bot.php` will output a substantial amount of information about the process. If event details are not populating, there is likely a Google API or calendar/pointer issue. If a `"tweetResponse":"Bad Request"` status shows up, there is likely a Twitter API/POST problem. Both problems are likely related to credentials/keys/secrets.

Setting `$debug_tweet` to `true` in `config/tweets.php` will output specific information from the OAuth/POST action on Twitter. Error codes can be referenced against [Twitter's API troubleshooting documentation](https://developer.twitter.com/en/support/twitter-api/error-troubleshooting).

`$debug_tweet` can be used independently of or in addition to `$debug_bot`.

## Contributors
Originally developed as a novelty bot/project by [@zaskem](https://github.com/zaskem) to play with the Twitter API. Project evolved slightly to include a homegrown OAuth Tweet posting mechanism using information provided by Twitter, bypassing the need for a library/utility such as Twurl on the production bot host and providing ample opportunity for cursing during development.