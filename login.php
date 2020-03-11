<?php  
include_once('./phpsdk5/autoload.php');
session_start();


$fb = new Facebook\Facebook([
  'app_id' => '1619981548238247', // Replace {app-id} with your app id
  'app_secret' => '6abf3bd7b2b7be4a16fe060f2219c120',
  'default_graph_version' => 'v2.9',
  ]);

$helper = $fb->getRedirectLoginHelper();

$permissions = ['email']; // Optional permissions
$loginUrl = $helper->getLoginUrl('http://www.icmarts.com/phpsdk5/fb-callback.php', $permissions);

echo '<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';
?>  