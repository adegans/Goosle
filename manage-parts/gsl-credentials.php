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

$action = (isset($_POST['action'])) ? sanitize($_POST['action']) : '';

if($action == "credentials") {
	$current_password = (isset($_POST['opw'])) ? sanitize_credentials($_POST['opw']) : '';
	$password = (isset($_POST['npw'])) ? sanitize_credentials($_POST['npw']) : '';
	$password2 = (isset($_POST['npwv'])) ? sanitize_credentials($_POST['npwv']) : '';

	if($current_password === $password) {
		// New password is the same
		echo "<div class=\"error\"><p>The new password is the same as the old password. Please use something else!</p></div>";
	} else if($password !== $password2) {
		// New passwords don't match
		echo "<div class=\"error\"><p>The new password does not match!</p><br /><p><a href=\"/manage.php?view=credentials\">Try again</a></p></div>";
	} else if(strlen($password) < 10 || strlen($password) > 100) {
		// Password shorter than 10 or longer than 100 characters
		echo "<div class=\"error\"><p>The new password needs to be between 10 and 100 characters long!</p></div>";
	} else if(!preg_match('/[^a-zA-Z0-9]/', $password)) {
		// No special characters in password
		echo "<div class=\"error\"><p>The new password needs to contain at-least 1 special character!</p></div>";
	} else if(update_profile($opts->user->uid, 'password', password_hash($password, PASSWORD_DEFAULT))) {
		echo "<div class=\"success\"><p>Your password has been updated! Please use it the next time you log in.</p></div>";
	} else {
		// Unknown error
		echo "<div class=\"error\"><p>Unexpected error. Please check the server error log at (or seconds before) timestamp ".the_date('d-m-Y H:i:s')."!</p></div>";
	}
} else if($action == "nicename") {
	$nicename = (isset($_POST['nn'])) ? sanitize($_POST['nn']) : $opts->user->nicename;

	if(strlen($nicename) < 5 || strlen($nicename) > 15) {
		// Display name shorter than 5 or longer than 15 characters
		echo "<div class=\"error\"><p>Your display name must be between 5 and 20 characters long!</p></div>";
	} else if(preg_match('/[^a-zA-Z0-9 ]/', $nicename)) {
		// Has special characters in password
		echo "<div class=\"error\"><p>A display name can not contain any special characters or spaces!</p></div>";
	} else if(update_profile($opts->user->uid, 'nicename', $nicename)) {
		echo "<div class=\"success\"><p>Your display name has been updated! You should see it on the next page load!</p></div>";
	} else {
		// Unknown error
		echo "<div class=\"error\"><p>Unexpected error. Please check the server error log at (or seconds before) timestamp ".the_date('d-m-Y H:i:s')."!</p></div>";
	}
} else {
?>
	<h2>Your profile password</h2>
	<p>You can change your password and display name here. This is a simple process and does not require email verification. If you change the password and get it wrong afterwards there is no easy way to recover it. Take good care of entering the new password and remembering, or storing, it afterwards.</p>

	<form action="manage.php" method="post" autocomplete="off" class="form">
		<input type="hidden" name="view" value="credentials"/>

		<h3>Display name</h3>
		<div class="columns">
			<div class="column-half">
				<p><input tabindex="50" type="text" placeholder="Enter your display name" class="text-field" name="nn" value="<?php echo $opts->user->nicename; ?>" /></p>
			</div>
			<div class="column-half">
				<ul class="text-s">
					<li>A display name is a nicer presentation of your username.</li>
					<li>Use alphanumeric characters only.</li>
					<li>Display names can be between 5 and 15 characters long.</li>
				</ul>
			</div>
		</div>

		<div class="text-center">
			<p><button tabindex="100" name="action" value="nicename" type="submit">Update Displayname</button></p>
		</div>

		<h3>Update your password</h3>
		<div class="columns">
			<div class="column-half">
				<p><input tabindex="10" type="password" placeholder="Your current password" class="text-field" name="opw" /></p>
				<p><input tabindex="20" type="password" placeholder="Your new password" class="text-field" name="npw" /></p>
				<p><input tabindex="30" type="password" placeholder="Confirm new password" class="text-field" name="npwv" /></p>
			</div>
			<div class="column-half">
				<ul class="text-s">
					<li>Leave these fields empty when you're not changing the password.</li>
					<li>Passwords are between 10 and 100 characters in length.</li>
					<li>Passwords must have at least one special character.</li>
					<li>None of the password fields can contain any spaces.</li>
				</ul>

			</div>
		</div>

		<div class="text-center">
			<p><button tabindex="100" name="action" value="credentials" type="submit">Update password</button></p>
		</div>
	</form>
<?php
}
?>
