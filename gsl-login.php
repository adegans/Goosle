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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Goosle Search | Login</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Access your Goosle Profile!" />

	<meta property="og:site_name" content="Goosle Search" />
	<meta property="og:title" content="Goosle Search Login Page" />
	<meta property="og:description" content="Access your Goosle Profile!" />
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

	<h3>Log in to your profile</h3>

	<?php
	if(isset($opts->user->error)) {
		echo "<p>ERROR: ".$opts->user->error."</p>";
	}
	?>

	<form action="<?php echo $opts->baseurl; ?>gsl-login.php" method="post">
		<p><input type="text" class="login-field" tabindex="10" name="username" placeholder="Username" id="username" required></p>
		<p><input type="password" class="login-field" tabindex="20" name="password" placeholder="Password" id="password" required></p>
		<p><button tabindex="100" value="Login" type="submit">Log in</button></p>
	</form>
</div>

<div class="columns footer">
	<div class="column-half">
		&copy; <?php echo the_date('Y'); ?> <a href="https://github.com/adegans/Goosle" target="_blank">Goosle <?php echo $current_version; ?></a>
	</div>
	<div class="column-half text-right">
		<a href="<?php echo $opts->baseurl; ?>index.php">Home</a> &sdot; <a href="<?php echo $opts->baseurl; ?>gsl-register.php">Create Profile</a>
	</div>
</div>

<?php
}
?>

</body>
</html>
