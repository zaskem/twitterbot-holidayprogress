# twitterbot-holidayprogress
A novelty bot written in PHP to tweet the progress toward and announcement of the next holiday/event. General idea inspired by the [Year Progress](https://twitter.com/year_progress) bot.

Data is sourced from Google calendar (public or shared) via the Google Calendar API, analyzed, and tweeted via Twitter's API with a homegrown `statuses/update.json` POST implementation.

## Contributors
Originally developed as a novelty bot/project by [@zaskem](https://github.com/zaskem) to play with the Twitter API. Project evolved slightly to include a homegrown OAuth Tweet posting mechanism using information provided by Twitter, bypassing the need for a library/utility such as Twurl on the production bot host and providing ample opportunity for cursing during development.
