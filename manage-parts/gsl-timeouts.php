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

if(!defined('ABSPATH')) exit;

// Make sure only the admin is allowed in
if($opts->user->admin == 'yes') {
?>
	<h2>Timeouts</h2>
	<p>This page lists all timeouts set by Goosle.<br>
	If a search engine doesn't work, or its results are missing you can check this page to see why.</p>
	
	<p>A timeout is set by Goosle if a search engine blocks your request or when you make too many requests. Depending on the response code a timeout of several minutes up-to a day will be set. Dates in red are still in effect.</p>
	
	<h3>Current timeouts</h3>
	<?php
	$timeouts = load_file('timeout.data');
	
	if(!empty($timeouts)) {
		foreach($timeouts as $engine => $expiry) {
			$class = ($expiry > time()) ? "red" : "green";
			echo "<p>".$engine.": <span class=\"".$class."\">".the_date('M d, Y H:i:s', $expiry)."</span></p>";
		}
	} else {
		echo "No timeouts have been set";
	}
} else {
	include ABSPATH . 'template-parts/role-error.php';
}
?>
