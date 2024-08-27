<div class="footer grid-container">
	<div class="footer-grid">
		&copy; <?php echo the_date('Y'); ?> <a href="https://github.com/adegans/Goosle" target="_blank">Goosle <?php echo $current_version; ?></a> <?php echo show_update_notification(); ?>
	</div>
	<div class="footer-grid">
		<a href="./box-office.php?a=<?php echo $opts->hash; ?>&t=9">Box office</a> - <a href="./help.php?a=<?php echo $opts->hash; ?>">Help</a> - <a href="./stats.php?a=<?php echo $opts->hash; ?>">Stats</a>
	</div>
</div>
