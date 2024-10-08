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
class ipRequest extends EngineRequest {
    public function get_request_url() {
        $url = 'https://api64.ipify.org?format=json&cache='.rand(1000, 9999);

        return $url;
    }

    public function get_request_headers() {
		return array(
			'Accept' => 'application/json, */*;q=0.8',
			'Accept-Language' => null,
			'Accept-Encoding' => null,
			'Sec-Fetch-Dest' => null,
			'Sec-Fetch-Mode' => null,
			'Sec-Fetch-Site' => null
		);
	}

    public function parse_results($response) {
		$engine_result = array();
		$json_response = json_decode($response, true);

		// No response
		if(empty($json_response)) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No response', 0);
			return $engine_result;
		}

		// Return result
        $engine_result = array(
            'title' => "Your IP Address: ".$_SERVER["REMOTE_ADDR"],
            'text' => "<p>All requests via Goosle use this as your IP Address: ".sanitize($json_response['ip'])."</p>",
            'source' => "https://www.ipify.org/",
            'note' => "Goosle is not a proxy server. Any website that you visit through Goosle Search Results will see your actual IP Address."
        );

		if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 1, 1);

		unset($response, $json_response);

		return $engine_result;
    }
}
?>
