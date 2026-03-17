<?php
/* ------------------------------------------------------------------------------------
*  Goosle - The fast, privacy oriented search tool that just works.
*
*  COPYRIGHT NOTICE
*  Copyright 2023-2025 Arnan de Gans. All Rights Reserved.
*
*  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
*  By using this code you agree to indemnify Arnan de Gans from any
*  liability that might arise from its use.
------------------------------------------------------------------------------------ */
session_start();
if(!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
$do_login = (isset($_REQUEST['do_login'])) ? true : false;

require ABSPATH.'functions/tools-files.php';
require ABSPATH.'functions/tools.php';

$opts = load_opts();

// Are we inviting someone?
$hash_placeholder = "Enter access hash";
if(isset($_GET['invite'])) {
	$invite_hash = sanitize($_GET['invite']);
	$hash_placeholder = ($invite_hash === $opts->hash) ? $invite_hash : $hash_placeholder;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Goosle Search | Login</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Create a Goosle Profile!" />

	<meta property="og:site_name" content="Goosle Search" />
	<meta property="og:title" content="Goosle Search Profile Creation Page" />
	<meta property="og:description" content="Create a Goosle Profile!" />
	<meta property="og:url" content="<?php echo $opts->baseurl; ?>" />
	<meta property="og:image" content="<?php echo $opts->baseurl; ?>assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo $opts->baseurl; ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo $opts->baseurl; ?>assets/css/styles.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo $opts->baseurl; ?>assets/css/<?php echo $opts->colorscheme; ?>.css"/>
</head>

<body class="page login">

<?php
if($opts->user->logged_in && !$do_login) {
	// Already logged in? Guest or not...
	header('Location: '.$opts->baseurl);
} else {
?>

<div class="content text-center">
	<h1><span class="goosle-g">G</span>oosle</h1>

	<div class="columns">
		<div class="column-half">
			<h3>Create a profile</h3>
			<?php
			if(isset($opts->user->error)) {
				echo "<p>ERROR: $user->error</p>";
			}
			?>

			<form action="<?php echo $opts->baseurl; ?>gsl-register.php" method="post">
				<p><input type="text" class="login-field" tabindex="1" name="userhash" placeholder="<?php echo $hash_placeholder; ?>" id="hash" autocomplete="off" required></p>
				<p><input type="text" class="login-field" tabindex="10" name="username" placeholder="Choose your username" id="username" autocomplete="off" required></p>
				<p><input type="password" class="login-field" tabindex="20" name="password" placeholder="And a password" id="password" autocomplete="off" required></p>
				<p><input type="password" class="login-field" tabindex="30" name="password_confirm" placeholder="Confirm your password" id="password_confirm" autocomplete="off" required></p>
				<p><button tabindex="100" name="reg" type="submit">Create Profile</button></p>
			</form>
		</div>

		<div class="column-half">
			<h4>Profile features:</h4>
			<p class="text-s">A profile might be required to use Goosle. In your profile you can manage which search engines are active as well as set a default for safe search, language and colorscheme.</p>

			<h4>Profile requirements:</h4>
			<ul class="text-s">
				<li>Contact the website administrator for an access hash.</li>
				<li>Usernames are between 5 and 15 alphanumeric characters.</li>
				<li>Passwords are between 10 and 100 characters.</li>
				<li>Passwords must have at least one special character.</li>
				<li>None of the fields can contain any spaces.</li>
			</ul>

			<h4>By creating a profile you agree to:</h4>
			<ul class="text-s">
				<li>Login data being stored in the Goosle database.</li>
				<li>There is no recovery option should your profile get lost.</li>
				<li>A login cookie that expires 30 days after your last login.</li>
			</ul>
		</div>
	</div>
</div>

<div class="columns footer">
	<div class="column-half">
		&copy; <?php echo the_date('Y'); ?> <a href="https://github.com/adegans/Goosle" target="_blank">Goosle <?php echo $current_version; ?></a>
	</div>
	<div class="column-half text-right">
		<a href="<?php echo $opts->baseurl; ?>index.php">Home</a> &sdot; <a href="<?php echo $opts->baseurl; ?>gsl-login.php?do_login">Login</a>
	</div>
</div>

<?php
}
?>

</body>
</html>
