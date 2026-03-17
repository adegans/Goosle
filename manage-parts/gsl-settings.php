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
$profile_settings = get_profile($opts->user->uid, 'settings');

if($action == "settings-submit") {
	// Process form
	$settings['colorscheme'] = (isset($_POST['gsl_colorscheme'])) ? sanitize($_POST['gsl_colorscheme']) : 'default';
	$settings['show_search_source'] = (isset($_POST['gsl_show_search_source'])) ? sanitize($_POST['gsl_show_search_source']) : 'on';
	$settings['safemode'] = (isset($_POST['gsl_safemode'])) ? sanitize($_POST['gsl_safemode']) : 1;
	$settings['show_yts_highlight'] = (isset($_POST['gsl_show_yts_highlight'])) ? sanitize($_POST['gsl_show_yts_highlight']) : 'on';
	$settings['show_share_option'] = (isset($_POST['gsl_show_share_option'])) ? sanitize($_POST['gsl_show_share_option']) : 'on';
	$settings['show_zero_seeders'] = (isset($_POST['gsl_show_zero_seeders'])) ? sanitize($_POST['gsl_show_zero_seeders']) : 'off';

	if(update_profile($opts->user->uid, 'settings', $settings)) {
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
	<h2>Your profile settings</h2>	
	<p>Om this page you can set up the default settings and personalized options for your Goosle Profile.</p>

	<form action="manage.php" method="post" autocomplete="off" class="form">
		<input type="hidden" name="view" value="settings"/>

		<h3>Look and feel</h3>
		<div class="columns">
			<div class="column-twothird">
				<p>Colorscheme:</p>
				<ul class="text-s">
					<li><strong>Goosle:</strong> Has dark elements but white backgrounds for pages.</li>
					<li><strong>Dark:</strong> Almost the same as default, but the white backgrounds are made dark.</li>
					<li><strong>Light:</strong> Makes everything more bright.</li>
					<li><strong>Auto:</strong> Lets your browser decide between Default and Light.</li>
				</ul>
			</div>
			<div class="column-third">
				<select name="gsl_colorscheme" id="colorscheme" tabindex="10" class="select-field">
				  <option value="default" <?php echo ($profile_settings['colorscheme'] == 'default') ? "selected" : ""; ?>>Goosle (Default)</option>
				  <option value="dark" <?php echo ($profile_settings['colorscheme'] == 'dark') ? "selected" : ""; ?>>Dark</option>
				  <option value="light" <?php echo ($profile_settings['colorscheme'] == 'light') ? "selected" : ""; ?>>Light</option>
				  <option value="auto" <?php echo ($profile_settings['colorscheme'] == 'auto') ? "selected" : ""; ?>>Auto</option>
				</select>
			</div>
		</div>
		<div class="columns">
			<div class="column-twothird">
				<p>Search source:</p>
				<ul class="text-s">
					<li>Show the search engine where the result comes from under the search result.</li>
				</ul>
			</div>
			<div class="column-third">
				<select name="gsl_show_search_source" id="show_search_source" tabindex="20" class="select-field">
				  <option value="on" <?php echo ($profile_settings['show_search_source'] == "on") ? "selected" : ""; ?>>Enabled (Default)</option>
				  <option value="off" <?php echo ($profile_settings['show_search_source'] == "off") ? "selected" : ""; ?>>Disabled</option>
				</select>
			</div>
		</div>

		<h3>Explicit content</h3>
		<div class="columns">
			<div class="column-twothird">
				<p>Safe search:</p>
				<ul class="text-s">
					<li><strong>Enabled:</strong> A strong filter against explicit and mature content. Certain engines or categories are disabled and Goosle tries to filter out explicit Magnet results based on keywords.</li>
					<li><strong>Disabled:</strong> Everything goes. All filters are disabled.</li>
				</ul>
			</div>
			<div class="column-third">
				<select name="gsl_safemode" id="safemode" tabindex="50" class="select-field">
				  <option value="0" <?php echo ($profile_settings['safemode'] == 0) ? "selected" : ""; ?>>Disabled (Default)</option>
				  <option value="1" <?php echo ($profile_settings['safemode'] == 1) ? "selected" : ""; ?>>Enabled</option>
				</select>
			</div>
		</div>

		<h3>Search results</h3>
		<div class="columns">
			<div class="column-twothird">
				<p>Box office Highlight:</p>
				<ul class="text-s">
					<li>When enabled, shows a row of the latest movies available from the YTS website above Magnet Search.</li>
				</ul>
			</div>
			<div class="column-third">
				<select name="gsl_show_yts_highlight" id="show_yts_highlight" tabindex="60" class="select-field">
				  <option value="on" <?php echo ($profile_settings['show_yts_highlight'] == "on") ? "selected" : ""; ?>>Enabled (Default)</option>
				  <option value="off" <?php echo ($profile_settings['show_yts_highlight'] == "off") ? "selected" : ""; ?>>Disabled</option>
				</select>
			</div>
		</div>
		<div class="columns">
			<div class="column-twothird">
				<p>Share magnet links:</p>
				<ul class="text-s">
					<li>When enabled, shows a 'Share' option for results.</li>
					<li>Share the Magnet Link with your friends so they can download the content directly.</li>
				</ul>
			</div>
			<div class="column-third">
				<select name="gsl_show_share_option" id="show_share_option" tabindex="80" class="select-field">
				  <option value="on" <?php echo ($profile_settings['show_share_option'] == "on") ? "selected" : ""; ?>>Enabled (Default)</option>
				  <option value="off" <?php echo ($profile_settings['show_share_option'] == "off") ? "selected" : ""; ?>>Disabled</option>
				</select>
			</div>
		</div>
		<div class="columns">
			<div class="column-twothird">
				<p>Show results with zero seeders:</p>
				<ul class="text-s">
					<li>When enabled, shows a magnet result even if nobody is sharing the content.</li>
					<li>Note: Downloads with zero seeders may download very slowly or intermittently or, never finish downloading at all.</li>
				</ul>
			</div>
			<div class="column-third">
				<select name="gsl_show_zero_seeders" id="show_zero_seeders" tabindex="90" class="select-field">
				  <option value="on" <?php echo ($profile_settings['show_zero_seeders'] == "on") ? "selected" : ""; ?>>Enabled</option>
				  <option value="off" <?php echo ($profile_settings['show_zero_seeders'] == "off") ? "selected" : ""; ?>>Disabled (Default)</option>
				</select>
			</div>
		</div>

		<div class="text-center">
			<p><button tabindex="1000" name="action" value="settings-submit" type="submit">Save settings</button></p>
		</div>
	</form>
<?php
}
?>
