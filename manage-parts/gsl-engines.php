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
$profile_settings = get_profile($opts->user->uid, 'engines');

if($action == "engines-submit") {

	// Process form
	if(isset($_POST['gsl_engine']) && !empty($_POST['gsl_engine'])) {
		foreach($opts->engines as $engine => $engine_info) {
			$profile_settings[$engine] = (isset($_POST['gsl_engine'][$engine])) ? "on" : "off";
			
			unset($engine, $engine_info);
		}
	}

	if(update_profile($opts->user->uid, 'engines', $profile_settings)) {
		echo "<h2>Done!</h2>";
		echo "<p>Your settings have been saved!</p>";
		echo "<p><a href=\"/manage.php\">Back to Profile</a></p>";
	} else {
		// Unknown error
		echo "<h2>Beep-boop!</h2>";
		echo "<p>Unexpected error. Please check the server error log at (or seconds before) timestamp ".the_date('d-m-Y H:i:s')."!</p>";
	}
} else {
?>
	<h2>Select your search engines</h2>	
	<p>Goosle has detected the following search engines for you to use.</p>

	<form action="manage.php" method="post" autocomplete="off" class="form">
		<input type="hidden" name="view" value="engines"/>

		<?php
		foreach($opts->engines as $engine => $engine_info) {
			$selected = '';
			if(array_key_exists($engine, $profile_settings)) {
				$selected = ($profile_settings[$engine] == "on") ? "checked" : '';
			}

			echo "<p><input type=\"checkbox\" id=\"".$engine."\" name=\"gsl_engine[".$engine."]\" class=\"checkbox-field\" ".$selected."> ".$engine_info['name']."<br /><small>".$engine_info['description']."</small></p>";
			
			unset($engine, $engine_info, $selected);
		}
		?>
		
		<div class="text-center">
			<p><button tabindex="1000" name="action" value="engines-submit" type="submit">Save settings</button></p>
		</div>
	</form>
<?php
}
?>
