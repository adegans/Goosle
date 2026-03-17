<div class="columns footer">
	<div class="column-half">
		&copy; <?php echo the_date('Y'); ?> <a href="https://github.com/adegans/Goosle" target="_blank">Goosle <?php echo $current_version; ?></a>
	</div>
	<div class="column-half text-right">
		<a href="./index.php">Home</a> &sdot; <a href="./help.php">Help</a> &sdot; <a href="./stats.php">Stats</a> &sdot; <?php echo ($opts->user->uid != "guest") ? "<a href=\"./manage.php\">Manage</a> &sdot; <a href=\"./gsl-logout.php\">Log out</a>" : "<a href=\"./gsl-login.php?do_login\">Login</a>"; ?>
	</div>
</div>
