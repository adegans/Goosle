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

class SearchRequest extends EngineRequest {
	protected $requests;

	public function __construct($search, $opts, $mh) {
		$this->requests = array();

		foreach($opts->engines as $engine => $engine_info) {
			if($engine_info['enabled'] == "on" && !has_timeout($engine)) {
				// Filter/exclude certain search engines based on search query or user settings
				$exclude = array();
				if(substr(strtolower($search->query), 0, 2) != 'tt' && in_array('imdb', $engine_info['filter'])) $exclude['imdb'] = 1;
				if($search->safe === 1 && in_array('nsfw', $engine_info['filter'])) $exclude['safe'] = 1;

				// Maybe do a search
				if(empty($exclude)) { 
					require ABSPATH.'engines/'.$engine_info['filename'];
					$this->requests[] = new $engine($search, $opts, $mh);
				}
			}
			
			unset($engine, $engine_info, $exclude);
		}
	}
}
?>