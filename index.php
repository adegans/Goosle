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

if(!defined('ABSPATH')) define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

require ABSPATH.'functions/tools.php';

$opts = load_opts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Goosle Search</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Get your Goosle on! - The best meta search engine for private and fast search results!" />

	<meta property="og:site_name" content="Goosle Search" />
	<meta property="og:title" content="Goosle Search" />
	<meta property="og:description" content="Get your Goosle on! - The best meta search engine for private and fast search results!" />
	<meta property="og:url" content="<?php echo get_base_url($opts->siteurl); ?>/" />
	<meta property="og:image" content="<?php echo get_base_url($opts->siteurl); ?>/assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo get_base_url($opts->siteurl); ?>/" />
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/styles.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/<?php echo $opts->colorscheme; ?>.css"/>
</head>

<body class="startpage">
<?php
if(verify_hash($opts->hash_auth, $opts->hash, $opts->user_auth)) {
?>

<div class="content">
	<h1><span class="goosle-g">G</span>oosle</h1>

    <form action="results.php" method="get" autocomplete="off">
        <input tabindex="10" type="search" class="search-field" name="q" required autofocus />
        <input type="hidden" name="a" value="<?php echo $opts->hash; ?>"/>

        <div class="search-buttons">
	        <?php if($opts->enable_web_search == 'on') { ?>
	        <button tabindex="20" name="t" value="0" type="submit" class="web-search">Web search</button>
	        <?php } ?>

	        <?php if($opts->enable_image_search == 'on') { ?>
	        <button tabindex="40" name="t" value="1" type="submit" class="image-search">Image search</button>
	        <?php } ?>

	        <?php if($opts->enable_magnet_search == 'on') { ?>
	        <button tabindex="50" name="t" value="9" type="submit" class="magnet-search">Magnet search</button><a href="./box-office.php?a=<?php echo $opts->hash; ?>&t=9" class="box-office">Box office</a>
	        <?php } ?>
        </div>
    </form>

	<?php if($opts->password_generator == "on") { ?>
	<div class="password-generator">
		<form method="get" action="./" autocomplete="off">
			Password Generator<br/><input class="password" type="text" name="pw" maxlength="27" value="<?php echo string_generator(24, '-'); ?>" autocomplete="0" />
		</form>
	</div>
	<?php } ?>
</div>

<div class="footer grid-container">
	<div class="footer-grid">
		&copy; <?php echo the_date('Y'); ?> Goosle <?php echo $current_version; ?> <?php echo show_update_notification(); ?>
	</div>
	<div class="footer-grid">
		<a href="./box-office.php?a=<?php echo $opts->hash; ?>&t=9">Box office</a> - <a href="./help.php?a=<?php echo $opts->hash; ?>">Help</a> - <a href="./stats.php?a=<?php echo $opts->hash; ?>">Stats</a>
	</div>
</div>

<?php } else { ?>
	<div class="auth-error">Redirecting</div>
	<meta http-equiv="refresh" content="1; url=<?php echo get_base_url($opts->siteurl); ?>/error.php" />
<?php } ?>

</body>
</html>
