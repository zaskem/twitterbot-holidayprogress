The [holiday progress bot](https://twitter.com/holidayprogress) was developed as a novelty bot/project by [@zaskem](https://github.com/zaskem) to play with the Twitter API. For reasons including but not limited to [stubbornness](https://twitter.com/dabit3/status/1340800853217849344/photo/1), this project evolved to include a homegrown OAuth Tweet posting script based on information provided by Twitter. Having a custom posting script bypasses the need to include a full library/utility such as Twurl on the production bot host. Developing such a script also provided ample opportunity for cursing, because 2020 definitely needed more cursing. Just doing my part.

The general idea for this bot was inspired by the [Year Progress](https://twitter.com/year_progress) bot.

The [live bot](https://twitter.com/holidayprogress)'s data is sourced from the "[Holidays in the United States](https://calendar.google.com/calendar/embed?src=en.usa%23holiday%40group.v.calendar.google.com&ctz=America%2FChicago)" Google calendar via the Google Calendar API, analyzed/calculated, and increments tweeted via Twitter's API. The live bot tweets _no more than once every 15 minutes_ and _only_ pushes a tweet when an update is detected (e.g. once at 5%, not again until 6% is reached).

The [GitHub repo](https://github.com/zaskem/twitterbot-holidayprogress) contains the basics for getting started with the bot in your own environment.