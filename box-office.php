<?php
if(!defined('ABSPATH')) define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
date_default_timezone_set('UTC');

require ABSPATH.'functions/tools.php';
require ABSPATH.'functions/tools-magnet.php';
require ABSPATH.'functions/tools-update.php';
require ABSPATH.'engines/boxoffice/yts.php';
require ABSPATH.'engines/boxoffice/eztv.php';
require ABSPATH.'engines/boxoffice/thepiratebay.php';
require ABSPATH.'engines/boxoffice/nyaa.php';

// Blue pixel
$blank_thumb = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mOUX3LxDAAE4AJiVKIoaQAAAABJRU5ErkJggg==';

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
	<title>Goosle Search Box Office</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="View the latest magnet links available for download!" />

	<meta property="og:site_name" content="Goosle Search Box Office" />
	<meta property="og:title" content="Goosle Search Box Office" />
	<meta property="og:description" content="View the latest magnet links available for download!" />
	<meta property="og:url" content="<?php echo get_base_url($opts->siteurl); ?>/box-office.php" />
	<meta property="og:image" content="<?php echo get_base_url($opts->siteurl); ?>/assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo get_base_url($opts->siteurl); ?>/box-office.php" />
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/styles.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url($opts->siteurl); ?>/assets/css/<?php echo $opts->colorscheme; ?>.css"/>

	<script src="<?php echo get_base_url($opts->siteurl); ?>/assets/js/goose.js" id="goosle-js"></script>
</head>

<body class="boxofficepage">
<?php
if(verify_hash($opts->hash_auth, $opts->hash, $auth)) {
?>
<div class="header">
	<form action="results.php" method="get" autocomplete="off">
	    <h1 class="logo"><a href="./?a=<?php echo $opts->hash; ?>"><span class="goosle-g">G</span>oosle</a></h1>        
	    <input tabindex="1" class="search-field" type="search" value="<?php echo (strlen($opts->query) > 0) ? htmlspecialchars($opts->query) : "" ; ?>" name="q" /><input tabindex="2" class="button" type="submit" value="Search" />
	
        <input type="hidden" name="t" value="<?php echo $opts->type; ?>"/>
	    <input type="hidden" name="a" value="<?php echo $opts->hash; ?>">
	</form>
 
    <div class="navigation">
        <a class="<?php echo ($opts->type == '0') ? 'active ' : ''; ?>tab-search" href="./results.php?q=<?php echo $opts->query; ?>&a=<?php echo $opts->hash; ?>&t=0">Search</a>

        <?php if($opts->enable_image_search == 'on') { ?>
        <a class="<?php echo ($opts->type == '1') ? 'active ' : ''; ?>tab-image" href="./results.php?q=<?php echo $opts->query; ?>&a=<?php echo $opts->hash; ?>&t=1" >Images</a>
        <?php } ?>

        <?php if($opts->enable_news_search == 'on') { ?>
        <a class="<?php echo ($opts->type == '2') ? 'active ' : ''; ?>tab-news" href="./results.php?q=<?php echo $opts->query; ?>&a=<?php echo $opts->hash; ?>&t=2">News</a>
        <?php } ?>

        <?php if($opts->enable_magnet_search == 'on') { ?>
        <a class="<?php echo ($opts->type == '9') ? 'active ' : ''; ?>tab-magnet" href="./results.php?q=<?php echo $opts->query; ?>&a=<?php echo $opts->hash; ?>&t=9">Magnet links</a>
        <?php } ?>
	</div>
</div>
	
<div class="content">
	<h2>The Box Office</h2>

	<div class="result-grid">
		<p>Click on any movie poster for more information and available download links. All other results are direct download links.</p>

		<h3>Recently added movies on YTS</h3>
		<?php
		$highlights = array_slice(yts_boxoffice($opts, 'date_added'), 0, 24);
		?>
		<ul>
			<?php
			foreach($highlights as $highlight) {
				$thumb = (!empty($highlight['thumbnail'])) ? $highlight['thumbnail'] : $blank_thumb;
				$search_query = urlencode($highlight['name']." ".$highlight['year']);

				echo "<li class=\"result highlight yts id-".$highlight['id']."\">";
				echo "	<div class=\"result-box\">";
				echo "		<a onclick=\"openpopup('highlight-".$highlight['id']."')\" title=\"More info: ".$highlight['name']."\"><img src=\"".$thumb."\" alt=\"".$highlight['name']."\" /></a>";
				echo "	</div>";
				echo "	<span><center><a onclick=\"openpopup('highlight-".$highlight['id']."')\" title=\"More info: ".$highlight['name']."\">".$highlight['name']."</a></center></span>";

				// HTML for popup
				echo "	<div id=\"highlight-".$highlight['id']."\" class=\"goosebox\">";
				echo "		<div class=\"goosebox-body\">";
				echo "			<h2>".$highlight['name']."</h2>";
				echo "			<p>".$highlight['summary']."</p>";
				echo "			<p><a href=\"./results.php?q=".$search_query."&a=".$opts->hash."&t=0\" title=\"Search on Goosle Web Search!\">Search on Goosle</a> &bull; <a href=\"./results.php?q=".$search_query."&a=".$opts->hash."&t=9\" title=\"Search on Goosle Magnet Search! For new additions results may be limited.\">Find more Magnet links</a></p>";
				echo "			<p><strong>Genre:</strong> ".$highlight['category']."<br /><strong>Released:</strong> ".$highlight['year']."<br /><strong>Rating:</strong> ".movie_star_rating($highlight['rating'])." <small>(".$highlight['rating']." / 10)</small></p>";

				// List downloads
				echo "			<h3>Downloads:</h3>";
				echo "			<p>";
				foreach($highlight['magnet_links'] as $magnet) {
					if(!is_null($magnet['quality'])) $meta[] = $magnet['quality'];
					if(!is_null($magnet['type'])) $meta[] = $magnet['type'];
					$meta[] = human_filesize($magnet['filesize']);
		
					echo "<button class=\"download\" onclick=\"location.href=".$magnet['magnet']."\">".implode(' / ', $meta)."</button>";
					unset($meta);
				}
				echo "			</p>";

				echo "			<p><a onclick=\"closepopup()\">Close</a></p>";
				echo "		</div>";
				echo "	</div>";

				echo "</li>";
		
				unset($highlight, $thumb, $search_query, $magnet);
			}
			unset($highlights);
			?>
	    </ul>
	
		<h3>Latest TV Show releases from EZTV</h3>
		<?php
		$highlights = array_slice(eztv_boxoffice($opts), 0, 24);
		?>
		<ul>
			<?php
			foreach($highlights as $highlight) {
				$thumb = (!empty($highlight['thumbnail'])) ? $highlight['thumbnail'] : $blank_thumb;
				
				echo "<li class=\"result highlight eztv id-".$highlight['id']."\">";
				echo "	<div class=\"result-box\">";
				echo "		<a onclick=\"openpopup('highlight-".$highlight['id']."')\" title=\"More info: ".$highlight['name']."\"><img src=\"".$thumb."\" alt=\"".$highlight['name']."\" /></a>";
				echo "	</div>";
				echo "	<span><center><a onclick=\"openpopup('highlight-".$highlight['id']."')\" title=\"More info: ".$highlight['name']."\">".$highlight['name']."</a></center></span>";

				// HTML for popup
				echo "	<div id=\"highlight-".$highlight['id']."\" class=\"goosebox\">";
				echo "		<div class=\"goosebox-body\">";
				echo "			<h2>".$highlight['name']."</h2>";
				echo "			<p><a href=\"./results.php?q=".urlencode($highlight['name'])."&a=".$opts->hash."&t=0\" title=\"Search on Goosle Web Search!\">Search on Goosle</a> &bull; <a href=\"./results.php?q=".urlencode($highlight['name'])."&a=".$opts->hash."&t=9\" title=\"Search on Goosle Magnet Search! For new additions results may be limited.\">Find more Magnet links</a></p>";

				// List downloads
				echo "			<h3>Downloads:</h3>";
				echo "			<p>";
				foreach($highlight['magnet_links'] as $magnet) {
					if(!is_null($magnet['quality'])) $meta[] = $magnet['quality'];
					$meta[] = human_filesize($magnet['filesize']);
		
					echo "<button class=\"download\" onclick=\"location.href=".$magnet['magnet']."\">".implode(' / ', $meta)."</button>";
					unset($meta);
				}
				echo "			</p>";

				echo "			<p><a onclick=\"closepopup()\">Close</a></p>";
				echo "		</div>";
				echo "	</div>";

				echo "</li>";
		
				unset($highlight, $thumb, $magnet);
			}
			unset($highlights);
			?>
	    </ul>
    </div>

	<div class="grid-container">

		<div class="list-grid piratebay">
			<h3>Newest downloads on ThePirateBay</h3>
			<ol>
			<?php
			foreach(piratebay_boxoffice($opts, 10) as $highlight) {
				echo "<li class=\"result magnet id-".$highlight['id']."\">";
				echo "<div class=\"title\"><a href=\"".$highlight['magnet']."\"><h2>".stripslashes($highlight['name'])."</h2></a></div>";
				echo "<div class=\"description\"><strong>Seeds:</strong> <span class=\"green\">".$highlight['seeders']."</span> - <strong>Peers:</strong> <span class=\"red\">".$highlight['leechers']."</span> - <strong>Size:</strong> ".human_filesize($highlight['filesize'])."<br /><strong>Category:</strong> ".$highlight['category']."</div>";
				echo "</li>";
		
				unset($highlight);
			}
			?>
    		</ol>
		</div>

		<div class="list-grid nyaa">
			<h3>Newest downloads on Nyaa</h3>
			<ol>
			<?php
			foreach(nyaa_boxoffice($opts, 10) as $highlight) {
				echo "<li class=\"result magnet id-".$highlight['id']."\">";
				echo "<div class=\"title\"><a href=\"".$highlight['magnet']."\"><h2>".stripslashes($highlight['name'])."</h2></a></div>";
				echo "<div class=\"description\"><strong>Seeds:</strong> <span class=\"green\">".$highlight['seeders']."</span> - <strong>Peers:</strong> <span class=\"red\">".$highlight['leechers']."</span> - <strong>Size:</strong> ".human_filesize($highlight['filesize'])."<br /><strong>Category:</strong> ".$highlight['category']."</div>";
				echo "</li>";
		
				unset($highlight);
			}
			?>
			</ol>
		</div>

	</div>

	<center><small>Goosle does not index, offer or distribute torrent files.</small></center>
</div>

<div class="footer grid-container">
	<div class="footer-grid">
		&copy; <?php echo date('Y'); ?> <?php echo show_version(); ?> By <a href="https://ajdg.solutions/" target="_blank">Arnan de Gans</a>.
	</div>
	<div class="footer-grid">
		<a href="./?a=<?php echo $opts->hash; ?>">Start</a> - <a href="./box-office.php?a=<?php echo $opts->hash; ?>&t=9">Box office</a> - <a href="./help.php?a=<?php echo $opts->hash; ?>">Help</a>
	</div>
</div>

<?php 
} else {
	echo "<div class=\"auth-error\">Goosle</div>";
} 
?>
</body>
</html>