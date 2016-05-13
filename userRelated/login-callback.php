<?php
session_start();
require_once __DIR__ . '/facebook-sdk-v5/autoload.php';
require_once(dirname(dirname(dirname(__FILE__))) . '/zp-core/admin-globals.php');

$fb = new Facebook\Facebook([
  'app_id' => getOption('fb_app_id'),
  'app_secret' => getOption('fb_app_secret'),
  'default_graph_version' => 'v2.5',
]);

$helper = $fb->getRedirectLoginHelper();
try {
  $accessToken = $helper->getAccessToken();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

if (isset($accessToken)) {
  // Logged in!
  $_SESSION['facebook_access_token'] = (string) $accessToken;
	header("Location: " . FULLWEBPATH . "/" . USER_PLUGIN_FOLDER . '/userRelated/userRelatedTab.php?page=users&tab=profile');
  // Now you can redirect to another page and use the
  // access token from $_SESSION['facebook_access_token']
}
?>
