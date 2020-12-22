<?php
require_once(__DIR__ . '/config/twitter.php');

function TweetPost($tweetText, $debug = false) {
  global $token, $token_secret, $token_account, $consumer_key, $consumer_secret;

  // Escape tweet text submitted
  $escapedTweet = rawurlencode($tweetText);

  // Create OAuth Signature Hash
  $oauth_hash = '';
  $oauth_hash .= 'oauth_consumer_key=' . $consumer_key .'&';
  $oauth_hash .= 'oauth_nonce=' . time() . '&';
  $oauth_hash .= 'oauth_signature_method=HMAC-SHA1&';
  $oauth_hash .= 'oauth_timestamp=' . time() . '&';
  $oauth_hash .= 'oauth_token=' . $token . '&';
  $oauth_hash .= 'oauth_version=1.0&';
  $oauth_hash .= 'status=' . $escapedTweet;

  $base = '';
  $base .= 'POST&';
  $base .= rawurlencode("https://api.twitter.com/1.1/statuses/update.json") . '&';
  $base .= rawurlencode($oauth_hash);

  $key = '';
  $key .= rawurlencode($consumer_secret) . '&' . rawurlencode($token_secret);

  $signature = base64_encode(hash_hmac('sha1', $base, $key, true));
  $signature = rawurlencode($signature);

  // Create OAuth Header for cURL
  $oauth_header = '';
  $oauth_header .= 'oauth_consumer_key="' . $consumer_key .'", ';
  $oauth_header .= 'oauth_nonce="' . time() . '", ';
  $oauth_header .= 'oauth_signature="' . $signature . '", ';
  $oauth_header .= 'oauth_signature_method="HMAC-SHA1", ';
  $oauth_header .= 'oauth_timestamp="' . time() . '", ';
  $oauth_header .= 'oauth_token="' . $token . '", ';
  $oauth_header .= 'oauth_version="1.0", ';
  $oauth_header .= 'status="' . $escapedTweet .'"';

  $curl_header = array("Authorization: OAuth {$oauth_header}", 'Expect:');

  // Create/Submit cURL request
  $curl_request = curl_init();
  curl_setopt($curl_request, CURLOPT_HTTPHEADER, $curl_header);
  curl_setopt($curl_request, CURLOPT_HEADER, false);
  curl_setopt($curl_request, CURLOPT_URL, "https://api.twitter.com/1.1/statuses/update.json");
  curl_setopt($curl_request, CURLOPT_POST, true);
  curl_setopt($curl_request, CURLOPT_POSTFIELDS, "status=$escapedTweet");
  curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);
  $json = curl_exec($curl_request);
  curl_close($curl_request);

  if (array_key_exists("errors", json_decode($json))) {
    return ($debug) ? "Bad Request: $json" : "Bad Request";
  } else {
    return ($debug) ? "Good Request: $json" : "Good Request";
  }
}
?>