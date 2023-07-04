# Twitter Holiday Progress Bot
___IMPORTANT NOTE:___ The Twitter Holiday Progress Bot was officially shut down in February 2023 due to speculation surrounding Twitter's forthcoming API changes. As a result this repository has been archived and under no further development.

The [holiday progress bot](https://twitter.com/holidayprogress) was developed as a novelty bot/project by [@zaskem](https://github.com/zaskem) to play with the Twitter API. For reasons including but not limited to [stubbornness](https://twitter.com/dabit3/status/1340800853217849344/photo/1), this project evolved to include a homegrown OAuth Tweet posting script based on information provided by Twitter. Having a custom posting script bypasses the need to include a full library/utility such as Twurl on the production bot host. Developing such a script also provided ample opportunity for cursing, because 2020 definitely needed more cursing. Just doing my part.

The general idea for this bot was inspired by the [Year Progress](https://twitter.com/year_progress) bot.

The [live bot](https://twitter.com/holidayprogress)'s data is sourced from a modified clone of the "[Holidays in the United States](https://calendar.google.com/calendar/embed?src=en.usa%23holiday%40group.v.calendar.google.com&ctz=America%2FChicago)" Google calendar via the Google Calendar API, analyzed/calculated, and increments tweeted via Twitter's API. The live bot tweets _no more than once every 15 minutes_ and _only_ pushes a tweet when an update is detected (e.g. once at 5%, not again until 6% is reached).

The [GitHub repo](https://github.com/zaskem/twitterbot-holidayprogress) contains the basics for getting started with the bot in your own environment.

## Final update July, 2023
In February, 2023, the source calendar for the holiday progress bot ran out of entries (it needed to be refreshed for the following year). On the horizon was speculation about Twitter's new API access tiers and the bot was soft shutdown until more was known. Though the bot was write-only and Twitter's new API access may theoretically allow for its continued use, the decision was made in late June to officially shut down the bot services. This repository has been archived and under no further development.

I have no intention of deleting the Twitter bot account itself, so past activity of the [holiday progress bot](https://twitter.com/holidayprogress) will live on as long as Twitter does (unless/until the account is deleted by Twitter).