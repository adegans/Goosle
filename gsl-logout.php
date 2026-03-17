<?php
session_start();
if(!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');

require ABSPATH.'functions/tools-files.php';

// Maybe remove session token
if(isset($_SESSION['gsl_token'])) {
	$tokens = load_file('profile-token.data');
	if(!empty($tokens)) {
		unset($tokens[$_SESSION['gsl_token']]);
		update_file('profile-token.data', $tokens);
	}
}

// End the session
session_destroy();
setcookie('gsl_logged_in', '', time() - 10, '/', 'goosle.test', 1, 1);

// Kick the user out
header('Location: '.$opts->baseurl);
?>
