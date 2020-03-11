<?php 
include_once('./phpsdk5/autoload.php');
session_start();


$fb = new Facebook\Facebook([
  'app_id' => '1619981548238247', // Replace {app-id} with your app id
  'app_secret' => '6abf3bd7b2b7be4a16fe060f2219c120',
  'default_graph_version' => 'v2.9',
  ]);

$helper = $fb->getRedirectLoginHelper();
 
try {
  $accessToken = $helper->getAccessToken();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When Graph returns an error
  //echo 'Graph returned an error: ' . $e->getMessage();
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues
  //echo 'Facebook SDK returned an error: ' . $e->getMessage();
}
 
if (isset($accessToken)) {
  // Logged in!
  var_dump($accessToken->getValue());
  $_SESSION['facebook_access_token'] = (string) $accessToken;
 // echo $_SESSION['facebook_access_token'];
  
} elseif ($helper->getError()) {
  // The user denied the request
}

try {
  // Returns a `Facebook\FacebookResponse` object
  $response = $fb->get('/me?fields=id,name,email', $_SESSION['facebook_access_token']);
  var_dump($response);
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

?>  