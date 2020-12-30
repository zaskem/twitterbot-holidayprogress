<?php
  require_once(__DIR__ . '/config/bot.php');
  require_once(__DIR__ . '/config/google.php');
  require_once(__DIR__ . '/config/tweets.php');
  require_once(__DIR__ . '/TweetPost.php');
  $status = include(__DIR__ . '/config/status.php');

  if (php_sapi_name() != 'cli') {
      throw new Exception('This application must be run on the command line.');
  }

  /**
   * Returns an authorized API client.
   * @return Google_Client the authorized client object
   */
  function getClient()
  {
    global $googleAppName, $googleAppKey;
    $client = new Google_Client();
    $client->setApplicationName($googleAppName);
    $client->setDeveloperKey($googleAppKey);
    $client->useApplicationDefaultCredentials();
    $client->setScopes(Google_Service_Calendar::CALENDAR_READONLY);
    return $client;
  }

  /**
   * getEventDate($sourceEvent, $eventType = 'next', $start = true)
   *  Simple function to return the string format of $sourceEvent's date/dateTime
   *  - $sourceEvent = instance of Google_Service_Calendar_Event Object (not an array of events)
   *  - $eventType = 'next' (default): nature of event. Possible options ('next','past'), though 
   *      any argument other than 'next' is treated as 'past'
   *      $eventType is used to nuance a default date if/when none exists.
   *  - $start = obtain the event's start (true) or end (false) time.
   * @return string the event date
   */
  function getEventDate($sourceEvent, $eventType = 'next', $start = true) {
    global $allDayEventEndTime;
    if (empty($sourceEvent)) {
      // Default to today/now in unlikely situation $sourceEvent is empty
        $year = ('next' == $eventType) ? date('Y') + 1 : date('Y') - 1;
        $calcDate = "$year-01-01";
    } else {
      // If dateTime format is availble, prefer it (not an all-day event)
      $calcDate = ($start) ? $sourceEvent->start->dateTime : $sourceEvent->end->dateTime;
      if (empty($calcDate)) {
        // Use date format if dateTime isn't available (an all-day event), add time for "end" dates
        $calcDate = ($start) ? $sourceEvent->start->date : $sourceEvent->start->date . " " . $allDayEventEndTime;
      }
    }
    return $calcDate;
  }


  // Get the API client and construct the service object.
  $client = getClient();
  $service = new Google_Service_Calendar($client);

  // Obtain most recent (past) event
  $lastResult = $service->events->listEvents($calendarId, $pastParams);
  $lastEvents = $lastResult->getItems();
  $lastEvent = end($lastEvents);
  $leStart = getEventDate($lastEvent, $eventType = 'past', true);
  $leEnd = getEventDate($lastEvent, $eventType = 'past', false);
  
  // Obtain first upcoming event
  $results = $service->events->listEvents($calendarId, $futureParams);
  $events = $results->getItems();
  $nextEvent = reset($events);
  $start = getEventDate($nextEvent, $eventType = 'next', true);
  $end = getEventDate($nextEvent, $eventType = 'next', false);

  $activeEvent = false;
  $eventInterval = date_diff(date_create(($respectEventDuration) ? $leEnd : $leStart), date_create($start));
  if ($leStart == $start) {
    // Active event, grab the next one for and recalculate.
    $nextEvent = next($events);
    $start = getEventDate($nextEvent, $eventType = 'next', true);
    $end = getEventDate($nextEvent, $eventType = 'next', false);
    $eventInterval = date_diff(date_create(($respectEventDuration) ? $leEnd : $leStart), date_create($start));

    // Allow override based on bot settings if an active all-day event hasn't ended
    if ($respectEventDuration) {
      if ($timeAtRun <= $leEnd) {
        // Handle div/0 situation on the duration of an active all-day event
        $eventInterval = date_diff(date_create($end), date_create($start));
        $activeEvent = true;
      }
    } 
  }

  $timePassed = date_diff(date_create(($respectEventDuration) ? $leEnd : $leStart), date_create());
  $percentComplete = intval((((($timePassed->days * 24) + $timePassed->h) * 60) + $timePassed->i) / (((($eventInterval->days * 24) + $eventInterval->h) * 60) + $eventInterval->i) * 100);
  $completeBars = min(intval($percentComplete / (100 / ($totalBars + 1))), $totalBars);
  $incompleteBars = $totalBars - $completeBars;

  // Debug information if necessary
  if($debug_bot) { $debug_info = array('lastEvent' => $lastEvent, 'nextEvent' => $nextEvent, 'eventInterval' => $eventInterval, 'timePassed' => $timePassed, 'percentComplete' => $percentComplete, 'completeBars' => $completeBars, 'incompleteBars' => $incompleteBars); }

  /**
   * We make a special tweet when we match that special circumstance when a holiday/event is reached the first time.
   *  Tweet a little differently (celebrate!)
   *  - this is the FIRST TIME we've seen this event title/summary.
   *      The logic looks weird because $lastEvent->getSummary() is for the _current_ run and $status['lastEventSummary']
   *      is for the _previous_ run of the bot script.
  */
  if ($lastEvent->getSummary() != $status['lastEventSummary']) {
    $tweetText = "Hooray! It's " . $lastEvent->getSummary() . "!";
    if ($debug_bot) {
      $debug_info['tweetText'] = $tweetText;
      $debug_info['tweetSubmitted'] = 'yes';
    }
    $result = TweetPost($tweetText, $debug_tweet);
  /**
   * Prepare to normally tweet...if we should (see negative logic). We don't normally tweet when:
   *  - an active event is happening today (no tweet), unless we don't respect active events
   *  - the percent complete hasn't changed since our last go (no tweet)
   */
  } else if (!((($respectEventDuration) && ($activeEvent)) || ($percentComplete == $status['lastPercentComplete']))) {
    // Craft a traditional tweet text
    $tweetText = "";
    // Progress bar creation...
    $z = 0;
    while ($z < $completeBars) {
      $tweetText .= $progressCharacter;
      $z++;
    }
    $z = 0;
    while ($z < $incompleteBars) {
      $tweetText .= $remainingCharacter;
      $z++;
    }

    // Auxiliary/Ancillary text for the tweet
    $tweetText .= "\n\n$percentComplete% of the way ";
    if ($includeEventSummary) {
      $tweetText .= "from " . $lastEvent->getSummary();
      if ($includeLastEventDate) { $tweetText .= " (" . $leStart . ")"; }
      $tweetText .= " to " . $nextEvent->getSummary();
      if ($includeNextEventDate) { $tweetText .= " (" . $start . ")"; }
      $tweetText .= "!";
    } else {
      $tweetText .= "to the next holiday!";
    }

    if ($debug_bot) {
      $debug_info['tweetText'] = $tweetText;
      $debug_info['tweetSubmitted'] = 'yes';
    }
    $result = TweetPost($tweetText, $debug_tweet);
  /**
   * Skip tweet
   */
  } else {
    // We skipped tweeting for a reason
    if ($debug_bot) { $debug_info['skippedActiveEvent'] = (($respectEventDuration) && ($activeEvent)) ? 'yes' : 'no'; }
    if ($debug_bot) { $debug_info['skippedSamePercent'] = ($percentComplete == $status['lastPercentComplete']) ? 'yes' : 'no'; }
    if ($debug_bot) { $debug_info['skippedSameSummary'] = ($lastEvent->getSummary() == $status['lastEventSummary']) ? 'yes' : 'no'; }
    // Craft $result response for skipped tweet
    if ($debug_bot) { $debug_info['tweetSubmitted'] = 'no'; }
    $result = 'Skipped Tweet';
  }

  // Handle debugging output
  if ($debug_bot) {
    $debug_info['tweetResponse'] = $result;
    $debug_info['timestamp'] = date('r');
    if ('json' == $debug_format) {
      print json_encode($debug_info);
    } else {
      print_r($debug_info);
    }
  } else if ($debug_tweet) {
    print $result;
  }

  // Finally, write out our last status
  $status['lastPercentComplete'] = $percentComplete;
  $status['lastEventDate'] = $leStart;
  $status['lastEventSummary'] = $lastEvent->getSummary();
  file_put_contents(__DIR__ . '/config/status.php', '<?php return ' . var_export($status, true) . '; ?>');
?>