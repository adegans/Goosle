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
class NyaaRequest extends EngineRequest {
    public function get_request_url() {
        return "https://nyaa.si/?q=".urlencode($this->query);
    }

    public function parse_results($response) {
        $results = array();
        $xpath = get_xpath($response);

		// Failted to load page
        if(!$xpath) return $results;

		// Scrape the page
        foreach($xpath->query("//tbody/tr") as $result) {
 			$category = $xpath->evaluate(".//td[1]//a/@title", $result)[0]->textContent;
            $name = $xpath->evaluate(".//td[@colspan='2']//a[not(contains(@class, 'comments'))]/@title", $result)[0]->textContent;
            $url = $xpath->evaluate(".//td[@colspan='2']//a[not(contains(@class, 'comments'))]/@href", $result)[0]->textContent;
            $meta = $xpath->evaluate(".//td[@class='text-center']", $result);
            $seeders = $meta[3]->textContent;
            $leechers = $meta[4]->textContent;
            $size =  $meta[1]->textContent;
            $date_added =  $meta[2]->textContent;
            $date_added = explode("-", substr(sanitize($date_added), 0, 10));
			$date_added = mktime(0, 0, 0, intval($date_added[1]), intval($date_added[2]), intval($date_added[0]));
            $magnet = $xpath->evaluate(".//a[2]/@href", $meta[0])[0]->textContent;

            array_push($results, array (
                // Required
                "source" => "nyaa.si",
                "name" => sanitize($name),
                "magnet" => sanitize($magnet),
                "seeders" => sanitize($seeders),
                "leechers" => sanitize($leechers),
                "size" => sanitize($size),
				// Optional values
				"category" => str_replace(" - ", "/", sanitize($category)),
                "url" => "https://nyaa.si".sanitize($url),
				"date_added" => $date_added
            ));
        }

        return $results;
    }
}
?>
