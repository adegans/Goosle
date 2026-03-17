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
	<h2>Users</h2>
	<p>This page lists all profiles.<br>
	Profiles are simple accounts of your users so they can customize their Goosle experience with a few settings.</p>
	
	<p>A profile can be deleted at any time, but due to the anonymous nature of profiles you can not ban users.</p>
	
	<h3>Invite someone to register</h3>
	<p><?php echo $opts->baseurl; ?>gsl-register.php?invite=<?php echo $opts->hash; ?></p>
	!! Click to copy link maken !!
	
	<h3>Profiles</h3>
	<?php
	$profiles = load_file('profile.data');
	
	if(!empty($profiles)) {	
		foreach($profiles as $uid => $profile) {
			$class = ($profile['admin'] == 'yes') ? "red" : "green";
			echo "<p>".$profile['username'].": Admin: <span class=\"".$class."\">".$profile['admin']."</span> [Delete]</p>";
		}
	} else {
		echo "No profiles can be found!";
	}
} else {
	include ABSPATH . 'template-parts/role-error.php';
}
?>
