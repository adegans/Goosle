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

if(!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');

session_start();
date_default_timezone_set('UTC');

require ABSPATH.'functions/tools-files.php';
require ABSPATH.'functions/tools.php';

$opts = load_opts();
$search = load_search();

if(isset($_REQUEST['view'])) {
	$view = sanitize($_REQUEST['view']);
} else if(isset($_POST['view'])) {
	$view = sanitize($_POST['view']);
} else {
	$view = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Goosle Search | Profile page</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Manage your Goosle profile and settings." />

	<meta property="og:site_name" content="Goosle Search" />
	<meta property="og:title" content="Profile page" />
	<meta property="og:description" content="Manage your Goosle profile and settings." />
	<meta property="og:url" content="<?php echo $opts->baseurl; ?>manage.php" />
	<meta property="og:image" content="<?php echo $opts->baseurl; ?>assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo $opts->baseurl; ?>manage.php" />
    <link rel="stylesheet" type="text/css" href="<?php echo $opts->baseurl; ?>assets/css/styles.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo $opts->baseurl; ?>assets/css/<?php echo $opts->colorscheme; ?>.css"/>
</head>

<body class="page profile">
<?php
if($opts->user->logged_in) {
?>
<div class="header">
	<div class="logo"><h1><a href="./"><span class="goosle-g">G</span>oosle</a></h1></div>
	<?php
	include ABSPATH . 'template-parts/searchform.php';
	include ABSPATH . 'template-parts/navigation.php';
	?>
</div>

<div class="content">
	<div class="columns">
		<div class="column-narrow">
			<h3>Menu</h3>
			<p>&raquo; <a href="<?php echo $opts->baseurl; ?>manage.php">Settings</a></p>
			<p>&raquo; <a href="<?php echo $opts->baseurl; ?>manage.php?view=engines">Search engines</a></p>
			<p>&raquo; <a href="<?php echo $opts->baseurl; ?>manage.php?view=credentials">Manage credentials</a></p>

			<?php if($opts->user->admin === "yes") { ?>
				<h3>Admin</h3>
				<p>&raquo; <a href="<?php echo $opts->baseurl; ?>manage.php?view=users">Users</a></p>
				<p>&raquo; <a href="<?php echo $opts->baseurl; ?>manage.php?view=timeouts">View Timeouts</a></p>
			<?php } ?>
		</div>
		<div class="column-wide">
			<?php
			if($view == 'users') {
				include_once(ABSPATH.'manage-parts/gsl-users.php');
			} else if($view == 'timeouts') {
				include_once(ABSPATH.'manage-parts/gsl-timeouts.php');
			} else if($view == 'engines') {
				include_once(ABSPATH.'manage-parts/gsl-engines.php');
			} else if($view == 'credentials') {
				include_once(ABSPATH.'manage-parts/gsl-credentials.php');
			} else {
				include_once(ABSPATH.'manage-parts/gsl-settings.php');
			}
			?>
		</div>
	</div>
</div>

<?php
	include ABSPATH . 'template-parts/footer.php';
} else {
	include ABSPATH . 'template-parts/login-error.php';
}
?>

</body>
</html>
