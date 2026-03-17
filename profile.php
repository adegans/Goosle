<?php
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

if(!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');

session_start();
date_default_timezone_set('UTC');

require ABSPATH.'functions/tools.php';

$opts = load_opts();
$user = do_login();
$search = load_search();

$view = (isset($_REQUEST['view'])) ? sanitize($_REQUEST['view']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Goosle Search | Profile page</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Profile and configuration page" />

	<meta property="og:site_name" content="Goosle Search" />
	<meta property="og:title" content="Profile page" />
	<meta property="og:description" content="Profile and configuration page" />
	<meta property="og:url" content="<?php echo $opts->baseurl; ?>profile.php" />
	<meta property="og:image" content="<?php echo $opts->baseurl; ?>assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo $opts->baseurl; ?>profile.php" />
    <link rel="stylesheet" type="text/css" href="<?php echo $opts->baseurl; ?>assets/css/styles.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo $opts->baseurl; ?>assets/css/<?php echo $opts->colorscheme; ?>.css"/>
</head>

<body class="resultspage">
<?php
//if(verify_hash($opts->hash_auth, $opts->hash, $opts->user_auth, $search->share)) {
if($user->logged_in) {
?>
<div class="header">
	<div class="logo"><h1><a href="./?a=<?php echo $opts->user_auth; ?>"><span class="goosle-g">G</span>oosle</a></h1></div>
	<?php
	include ABSPATH . 'template-parts/searchform.php';
	include ABSPATH . 'template-parts/navigation.php';
	?>
</div>

<div class="content">
	<div class="flex-row">
		<div class="col-2">
			<p>&bull; <a href="<?php echo $opts->baseurl; ?>profile.php?view=timeouts&a=<?php echo $opts->hash; ?>">View Timeouts</a></p>
			<p>&bull; <a href="<?php echo $opts->baseurl; ?>profile.php?view=openverse&a=<?php echo $opts->hash; ?>">Openverse</a></p>
			<p>&bull; <a href="<?php echo $opts->baseurl; ?>profile.php?view=pixabay&a=<?php echo $opts->hash; ?>">Pixabay</a></p>
		</div>
		<div class="col-10">
			<?php
			if($view == 'timeouts') {
				include_once(ABSPATH.'profile/timeouts.php');
			} else if($view == 'openverse') {
				include_once(ABSPATH.'profile/openverse.php');
			} else if($view == 'pixabay') {
				include_once(ABSPATH.'profile/pixabay.php');
			} else {
				echo "Goosle!!";
			}
			?>
		</div>
	</div>
</div>

<?php
	include ABSPATH . 'template-parts/footer.php';
} else {
	include ABSPATH . 'template-parts/error.php';
}
?>

</body>
</html>
