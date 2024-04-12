<?php
/* ------------------------------------------------------------------------------------
*  Goosle - A meta search engine for private and fast internet fun.
*
*  COPYRIGHT NOTICE
*  Copyright 2023-2024 Arnan de Gans. All Rights Reserved.
*
*  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
*  By using this code you agree to indemnify Arnan de Gans from any 
*  liability that might arise from its use.
------------------------------------------------------------------------------------ */
class RedditRequest extends EngineRequest {
    public function get_request_url() {
		$args = array("q" => $this->query, "type" => "link", "sort" => "top", "t" => "year");
        $url = "https://www.reddit.com/search.json?".http_build_query($args);

        return $url;
    }

	public function parse_results($response) {
		$results = array();
		$json_response = json_decode($response, true);
		
		if(empty($json_response)) return $results;
		
		// No results
		if($json_response['data']['dist'] == 0) return $results;
		
		$rank = $results['amount'] = count($json_response['data']['children']);
		foreach($json_response['data']['children'] as $result) {
			$result = $result['data'];

			$nsfw = sanitize($result['over_18']);
			
			// Ignore NSFW results
			if($this->opts->show_reddit_nsfw == "off" && $nsfw === 1) continue;
			
			$title = trim($result['title']);
			$url = "https://www.reddit.com".sanitize($result['permalink']);
			
			$postdate = date('M d, Y', sanitize($result['created']));
			$author = sanitize($result['author']);
			$reddit = sanitize($result['subreddit']);
			$votes = sanitize($result['score']);
			$comments = sanitize($result['num_comments']);
			$nsfw = ($nsfw === 1) ? "<span style=\"color:#cc0033;\">[NSFW 18+] Caution, this result contains mature content!</span><br />" : "";
			
			$description = $nsfw."In <a href=\"https://www.reddit.com/r/".$reddit."\" target=\"_blank\">r/".$reddit."</a> &sdot; ".$postdate." &sdot; <a href=\"https://www.reddit.com/user/".$author."\" target=\"_blank\">".$author."</a> &sdot; ".$votes." votes &sdot; ".$comments." comments";

			$id = uniqid(rand(0, 9999));
		
			$results['search'][] = array ("id" => $id, "source" => "Reddit", "title" => $title, "url" => $url, "description" => $description, "engine_rank" => $rank);
			$rank -= 1;
		}
		unset($response, $json_response, $rank);
		
		return $results;
	}
}
?>
