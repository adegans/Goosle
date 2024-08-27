<?php
if(!defined('ABSPATH')) define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

require ABSPATH.'functions/tools.php';

$opts = load_opts();
$auth = (isset($_GET['a'])) ? sanitize($_GET['a']) : $opts->user_auth;
/* ------------------------------------------------------------------------------------
*  Goosle - The fast, privacy oriented search tool that just works.
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
	<link rel="canonical" href="<?php echo get_base_url($opts->siteurl); ?>/functions/oauth-openverse.php" />

    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/styles.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/<?php echo $opts->colorscheme; ?>.css"/>
</head>

<body class="plainpage">
<?php
if(verify_hash($opts->hash_auth, $opts->hash, $auth)) {
?>

<div class="content">
	<?php
	$connect = (isset($_REQUEST['oa'])) ? sanitize($_REQUEST['oa']) : '';
	$email = (isset($_REQUEST['oae'])) ? sanitize($_REQUEST['oae']) : '';
	$client_id = (isset($_REQUEST['oaid'])) ? sanitize($_REQUEST['oaid']) : '';
	$client_secret = (isset($_REQUEST['oacs'])) ? sanitize($_REQUEST['oacs']) : '';

	if(empty($connect)) {
	?>

	<div class="oauth-form">
		<h1><span class="goosle-g">G</span>oosle</h1>
		<p>Use this page to set up an authorization token for Openverse.<br />
		Fill in the relevant fields and click the button at the bottom to continue.</p>

		<form action="oauth-openverse.php" method="get" autocomplete="off">
			<h2>Registration</h2>
			<p>Email address:<br /><input tabindex="10" type="text" class="field" name="oae" /><br /><small>(Always required for verification)</small></p>

			<h3>Recovering a previous registration?</h3>
			<p>Client ID:<br /><input tabindex="20" type="text" class="field" name="oaid" /></p>
			<p>Client Secret:<br /><input tabindex="30" type="text" class="field" name="oacs" /></p>

			<input type="hidden" name="a" value="<?php echo $opts->hash; ?>"/>

			<div class="oauth-buttons">
				<button tabindex="100" name="oa" value="openverse" type="submit">Connect to Openverse</button>
			</div>
			<a href="/">Back to Goosle</a>
		</form>
	</div>

	<?php
	} else {
		$token_file = ABSPATH.'cache/token.data';

		if(empty($client_id) AND empty($client_secret) AND !empty($email)) {
			$registration = do_curl_request(
				'https://api.openverse.org/v1/auth_tokens/register/', // (string) Where?
				array('Accept: application/json, */*;q=0.8', 'User-Agent: '.$opts->user_agents[0].';'), // (array) Headers
				'post', // (string) post/get
				array('name' => 'Goosle Meta Search '.md5(get_base_url($opts->siteurl)), 'description' => 'Goosle Meta Search for '.get_base_url($opts->siteurl), 'email' => $email) // (assoc array) Post body
			);
			$registration = json_decode($registration, true);

			// Site already exists, get new token
			if(stristr($registration['name'][0], 'this name already exists')) {
				if(is_file($token_file)) {
					$tokens = unserialize(file_get_contents($token_file));
					$registration = $tokens['openverse'];
				} else {
					echo "<div class=\"auth-error\">Error - Token file is missing. Please recover your registration with the Client ID and Client Secret.<br /><a href=\"/functions/oauth-openverse.php?a=".$opts->hash."\">Try again</a></div>";
					exit;
				}
			}
		} else {
			$registration = array('client_id' => $client_id, 'client_secret' => $client_secret);
		}

		$new_token = do_curl_request(
			'https://api.openverse.org/v1/auth_tokens/token/', // (string) Where?
			array('Accept: application/json, */*;q=0.8', 'User-Agent: '.$opts->user_agents[0].';', 'Authorization: Bearer'.$registration['client_id']), // (array) Headers
			'post', // (string) post/get
			array('grant_type' => 'client_credentials', 'client_id' => $registration['client_id'], 'client_secret' => $registration['client_secret']) // (assoc array) Post body
		);
		$new_token = json_decode($new_token, true);

		$new_token['expires_in'] = time() + ($new_token['expires_in'] - 3600);

		oauth_store_token($token_file, $connect, array('client_id' => $registration['client_id'], 'client_secret' => $registration['client_secret'], 'access_token' => $new_token['access_token'], 'expires' => $new_token['expires_in']));

		echo "<div class=\"auth-success\"><p>SUCCESS!</p>";
		echo "<p>Goosle is now authorized and you can enable Openverse in your config.php!<br />If this is your first time authorizing with this email address you will receive an email from Openverse in a few minutes with a verification link that you need to click.</p>";
		echo "<p>To be able to recover your registration save these values:</p>";
		echo "<p>Used Email Address: ".$email."<br />Client ID: ".$registration['client_id']."<br />Client Secret: ".$registration['client_secret']."<br /><br /><a href=\"/results.php?a=".$opts->hash."&q=goose&t=1\">Continue to Goosle</div>";

		unset($registration, $new_token);
	}
	?>

</div>

<?php
} else {
	include_once('../error.php');
}
?>

</body>
</html>
