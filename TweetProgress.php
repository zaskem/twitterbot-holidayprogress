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

  // Get the API client and construct the service object.
  $client = getClient();
  $service = new Google_Service_Calendar($client);

  // Obtain most recent (past) event
  $lastResult = $service->events->listEvents($calendarId, $pastParams);
  $lastEvents = $lastResult->getItems();
  $lastEvent = end($lastEvents);

  if (empty($lastEvent)) {
    // Default to today/now if no history is found
    $leStart = date_create();
  } else {
    $leStart = $lastEvent->start->dateTime;
    if (empty($leStart)) {
      $leStart = $lastEvent->start->date;
    }
  }

  // Obtain first upcoming event
  $results = $service->events->listEvents($calendarId, $futureParams);
  $events = $results->getItems();
  $nextEvent = reset($events);

  if (empty($nextEvent)) {
    // Default to next new year for no upcoming event
    $nextYear = date('Y') + 1;
    $start = "$nextYear-01-01";
  } else {
    $start = $nextEvent->start->dateTime;
    if (empty($start)) {
      $start = $nextEvent->start->date;
    }
  }

  // Calculate intervals/times/progress bar status
  $eventInterval = date_diff(date_create($leStart), date_create($start));
  $timePassed = date_diff(date_create($leStart), date_create());
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
    $tweetText .= "Hooray! It's " . $lastEvent->getSummary() . "!";
    if ($debug_bot) {
      $debug_info['tweetText'] = $tweetText;
      $debug_info['tweetSubmitted'] = 'yes';
    }

    //$result = '';
    $result = TweetPost($tweetText, $debug_tweet);
/**
 * Prepare to normally tweet...if we should (see negative logic). We don't normally tweet when:
 *  - an active all-day event is happening today (no tweet)
 *  - the percent complete hasn't changed since our last go (no tweet)
 */
  } else if (!((($respectAllDayEvents) && ($leStart == $today)) || ($percentComplete == $status['lastPercentComplete']))) {
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

    //$result = '';
    $result = TweetPost($tweetText, $debug_tweet);
/**
 * Skip tweet
 */
  } else {
    // We skipped tweeting for a reason
    if ($debug_bot) { $debug_info['skippedSameDay'] = (($respectAllDayEvents) && ($start == $today)) ? 'yes' : 'no'; }
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