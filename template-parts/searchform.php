<div class="searchform">
	<form action="results.php" method="get" autocomplete="off">
		<div class="searchwrap">
			<input tabindex="1" type="search" id="search" class="search-field" name="q" value="<?php echo (strlen($search->query) > 0) ? htmlspecialchars($search->query) : "" ; ?>" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" /><input tabindex="2" type="submit" id="search-button" class="button" value="Search" />
		</div>
	</form>
</div>
