<?php 
if(!defined('ABSPATH')) define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

require ABSPATH."functions/tools.php";
require ABSPATH."functions/oauth-functions.php";

$opts = load_opts();
$auth = (isset($_GET['a'])) ? sanitize($_GET['a']) : $opts->user_auth;
/* ------------------------------------------------------------------------------------
*  Goosle - A meta search engine for private and fast internet fun.
*
*  COPYRIGHT NOTICE
*  Copyright 2023-2024 Arnan de Gans. All Rights Reserved.
*
*  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
*  By using this code you agree to indemnify Arnan de Gans from any 
*  liability that might arise from its use.
------------------------------------------------------------------------------------ */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Goosle Search oAUTH</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Get your Goosle on! - The best meta search engine for private and fast internet fun!" />

	<link rel="icon" href="../favicon.ico" />
	<link rel="apple-touch-icon" href="../apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo get_base_url($opts->siteurl); ?>/functions/oauth.php" />

    <link rel="stylesheet" type="text/css" href="../assets/css/styles.css"/>
</head>

<body class="oauthpage">
<?php
if(verify_hash($opts, $auth)) {

$connect = (isset($_REQUEST['oa'])) ? sanitize($_REQUEST['oa']) : "";
// Openverse
$email = (isset($_REQUEST['oae'])) ? sanitize($_REQUEST['oae']) : "";
$client_id = (isset($_REQUEST['oaid'])) ? sanitize($_REQUEST['oaid']) : "";
$client_secret = (isset($_REQUEST['oacs'])) ? sanitize($_REQUEST['oacs']) : "";

if(empty($connect)) {
?>

<div class="oauth-form">
	<h1><span class="G">G</span>oosle</h1>
	<p>Use this page to set up an authorization token for Openverse.<br />
	Fill in the relevant fields and click the button at the bottom to continue.</p>
    
	<form action="oauth.php" method="get" autocomplete="off">
		<h2>(re)New registration</h2>
		<p>Email address:<br /><input tabindex="10" type="text" class="field" name="oae" /><br /><small>(Required for verification)</small></p>

		<h2>Recovering a previous registration?</h2>
		<p>Client ID:<br /><input tabindex="20" type="text" class="field" name="oaid" /></p>
		<p>Client Secret:<br /><input tabindex="30" type="text" class="field" name="oacs" /></p>
	
		<input type="hidden" name="a" value="<?php echo $opts->hash; ?>"/>
	
		<div class="oauth-buttons">
			<button tabindex="100" name="oa" value="openverse" type="submit">Connect to Openverse</button>
		</div>
	</form>
</div>

<?php
} else {
	$token_file = ABSPATH.'cache/token.data';
	
	if(empty($client_id) AND empty($client_secret) AND !empty($email)) {
		$registration = oath_curl_request(
			'https://api.openverse.org/v1/auth_tokens/register/', // Where?
			$opts->user_agents[0], // Who?
			'post', // post/get
			array(), // Additional headers
			array('name' => 'Goosle Meta Search '.md5(get_base_url($opts->siteurl)), 'description' => 'Goosle Meta Search for '.get_base_url($opts->siteurl), 'email' => $email) // Payload
		);

		// Site already exists, get new token
		if(stristr($registration['name'][0], 'this name already exists')) {
			if(is_file($token_file)) {
				$tokens = unserialize(file_get_contents($token_file));
				$registration = $tokens['openverse'];
			} else {
				echo "<div class=\"auth-error\">Error - Token file is missing. Please recover your registration with the Client ID and Client Secret.<br /><a href=\"/functions/oauth.php?a=".$opts->hash."\">Try again</a></div>";
				exit;
			}
		}
	} else {
		$registration = array('client_id' => $client_id, 'client_secret' => $client_secret);
	}

	$new_token = oath_curl_request(
		'https://api.openverse.org/v1/auth_tokens/token/', // Where?
		$opts->user_agents[0], // Who?
		'post', // post/get
		array('Authorization: Bearer'.$registration['client_id']), // Additional headers
		'grant_type=client_credentials&client_id='.$registration['client_id'].'&client_secret='.$registration['client_secret'] // Payload
	);

	$new_token['expires_in'] = time() + ($new_token['expires_in'] - 3600);

	oath_store_token($token_file, $connect, array("client_id" => $registration['client_id'], "client_secret" => $registration['client_secret'], "access_token" => $new_token['access_token'], "expires" => $new_token['expires_in']));

	echo "<div class=\"auth-success\">SUCCESS!<br />Goosle is now authorized and you can enable Openverse in your config.php if you haven't already!<br />If this is your first time authorizing with this email address you'll get an email from Openverse in a few moments with a verification link that you need to click.<br /><br />To be able to recover your registration save these values:<br />Used Email Address: ".$email."<br />Client ID: ".$registration['client_id']."<br />Client Secret: ".$registration['client_secret']."<br /><br /><a href=\"/results.php?a=".$opts->hash."&q=goose&t=1\">Continue to Goosle</div>";

	unset($registration, $new_token);
}
?>

<?php 
} else {
	echo "<div class=\"auth-error\">Goosle</div>";
} 
?>
</body>
</html>