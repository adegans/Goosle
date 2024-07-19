<?php
/* ------------------------------------------------------------------------------------
*  Goosle - The fast, privacy oriented search tool that just works.
*
*  COPYRIGHT NOTICE
*  Copyright 2023-2024 Arnan de Gans. All Rights Reserved.
*
*  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
*  By using this code you agree to indemnify Arnan de Gans from any 
*  liability that might arise from its use.
------------------------------------------------------------------------------------ */
class YahooNewsRequest extends EngineRequest {
    public function get_request_url() {
		// Safe search override
		if($this->search->safe == 0) {
			$safe = '0';
		} else {
			$safe = '';
		}
	
		$url = 'https://news.search.yahoo.com/search?'.http_build_query(array(
        	'p' => $this->search->query, // Search query
        	'safe' => $safe // Safe search filter (0 = off, "" = on)
        ));
        
        unset($safe);

        return $url;
    }

    public function get_request_headers() {
		return array(
			'Accept' => 'text/html, application/xhtml+xml, application/xml;q=0.8, */*;q=0.7',
		);
	}

    public function parse_results($response) {
		$engine_temp = $engine_result = array();
		$xpath = get_xpath($response);

		// No response
		if(!$xpath) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, 'No response', 0);
			return $engine_result;
		}

		// Scrape the results (Max 30)
		$scrape = $xpath->query("//div[@id='web']/ol/li[position() < 30]");

		// Figure out results and base rank
		$number_of_results = $rank = count($scrape);

		// No results
        if($number_of_results == 0) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, 'No results', 0);	        
	        return $engine_result;
	    }

		foreach($scrape as $result) {
			// Find data
			$title = $xpath->evaluate("./div/ul/li/a[contains(@class, 'thmb')]/@title", $result);
			$url = $xpath->evaluate("./div/ul/li/h4[contains(@class, 's-title')]/a/@href", $result);
			$description = $xpath->evaluate("./div/ul/li/p[contains(@class, 's-desc')]", $result);
			$image = $xpath->evaluate("./div/ul/li/a/img[@class='s-img']/@src", $result);
			$date_added = $xpath->evaluate("./div/ul/li/span[contains(@class, 's-time')]", $result);

			// Skip broken results
			if($title->length == 0) continue;
			if($url->length == 0) continue;

			// Process data
			$title = strip_newlines(sanitize($title[0]->textContent));
			$url = (preg_match('/\/ru=(.+)(%3ffr|\/rk)/i', $url[0]->textContent, $found_url)) ? urldecode($found_url[1]) : $url[0]->textContent;
			$url = (preg_match('/\??&?(utm_).+?(&|$)$/i', $url, $found_url)) ? urldecode($found_url[1]) : $url;
			$url = sanitize(str_replace('?fr=sycsrp_catchall', '', $url));
			$description = ($description->length == 0) ? "No description was provided for this site." : limit_string_length(strip_newlines(sanitize($description[0]->textContent)));
			$image = ($image->length == 0) ? null : sanitize($image[0]->textContent);
			$source = str_replace('www.', '', strtolower(parse_url($url, PHP_URL_HOST)));
			$timestamp = ($date_added->length == 0) ? null : strtotime(sanitize(preg_replace('/[^a-z0-9 ]+/i', '', $date_added[0]->textContent)));

			// filter duplicate urls/results
            if(!empty($engine_temp)) {
                if(in_array($url, array_column($engine_temp, 'url'))) continue;
            }

			// Fix up the image
			if(!is_null($image)) {
				$image = explode('/http', $image);
				$image = parse_url('http'.$image[1]);
				$image = $image['scheme'].'://'.$image['host'].$image['path'];
			}

			$engine_temp[] = array(
				'title' => $title, // string
				'url' => $url, // string
				'description' => $description, // string
				'image' => $image, // string|null
				'source' => $source, // string
				'timestamp' => $timestamp, // int|null
				'engine_rank' => $rank // int
			);
			$rank -= 1;
		}

		// Base info
		if(!empty($engine_temp)) {
			$engine_result['source'] = 'Yahoo News';
			$engine_result['search'] = $engine_temp;
		}

		if($this->opts->querylog == 'on') querylog(get_class($this), 's', $this->url, $number_of_results, count($engine_temp));

		unset($response, $xpath, $scrape, $number_of_results, $rank, $engine_temp);

		return $engine_result;
    }
}
?>
