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
	<meta property="og:title" content="Goosle Search error page" />
	<meta property="og:description" content="Get your Goosle on! - The best meta search engine for private and fast search results!" />
	<meta property="og:url" content="<?php echo get_base_url($opts->siteurl); ?>/error.php" />
	<meta property="og:image" content="<?php echo get_base_url($opts->siteurl); ?>/assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo get_base_url($opts->siteurl); ?>/error.php" />
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/styles.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/<?php echo $opts->colorscheme; ?>.css"/>
</head>

<body class="startpage">
	<div class="auth-error">
		<p>You can't use <span class="goosle-g">G</span>oosle without an authorization key!</p>
		<p>Contact the website administrator for more information.</p>
	</div>
</body>
</html>