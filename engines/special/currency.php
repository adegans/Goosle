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
class CurrencyRequest extends EngineRequest {
    public function get_request_url() {
        $url = 'https://cdn.moneyconvert.net/api/latest.json';
        
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

		// No results
        if(count($json_response['rates']) == 0) {
			if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 'No results', 0);
			return $engine_result;
		}

		// Process query
		// [0] = AMOUNT
		// [1] = FROM CURRENCY
		// [2] = (to|in)
		// [3] = TO CURRENCY
		
        $amount = floatval($this->search->query_terms[0]);
        $amount_currency = strtoupper($this->search->query_terms[1]);
        $conversion_currency = strtoupper($this->search->query_terms[3]);
        $last_update = the_date('M d, Y H:i', strtotime(sanitize($json_response['lastupdate'])));

		// Unknown/misspelled currencies
        if(!array_key_exists($amount_currency, $json_response['rates']) || !array_key_exists($conversion_currency, $json_response['rates'])) {
            return $engine_result;
		}

		// Calculate exchange rate
        $conversion = round(($json_response['rates'][$conversion_currency] / $json_response['rates'][$amount_currency]) * $amount, 2);
        $one_to_n = round(($json_response['rates'][$conversion_currency] / $json_response['rates'][$amount_currency]) * 1, 2);

		// Return result
        $engine_result = array(
            'title' => "Currency conversion: ".$amount." ".$amount_currency." = ".$conversion." ".$conversion_currency,
            'text' => "<p>1 ".$amount_currency." = ".$one_to_n." ".$conversion_currency."</p><p><small>Updated: ".$last_update."</small></p>",
            'source' => "https://moneyconvert.net/"
        );

		if($this->opts->querylog == 'on') querylog(get_class($this), 'a', $this->url, 1, 1);

		unset($response, $json_response, $amount, $amount_currency, $conversion, $one_to_n, $conversion_currency, $last_update);

		return $engine_result;
    }
}
?>
