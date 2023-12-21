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
class CurrencyRequest extends EngineRequest {
    public function get_request_url() {
        return "https://cdn.moneyconvert.net/api/latest.json";
    }
    
    public function parse_results($response) {
        $json_response = json_decode($response, true);

		if(!empty($json_response)) {
	        $result = $json_response['rates'];

			// Process query
			// [0] = AMOUNT
			// [1] = FROM CURRENCY
			// [2] = (to|in)
			// [3] = TO CURRENCY
			
	        $query_terms = explode(" ", $this->query);
	        $amount = floatval($query_terms[0]);
	        $amount_currency = strtoupper($query_terms[1]);
	        $conversion_currency = strtoupper($query_terms[3]);

			// Unknown/misspelled currencies
	        if (!array_key_exists($amount_currency, $result) || !array_key_exists($conversion_currency, $result)) {
	            return array();
			}
	
			// Calculate exchange rate
	        $conversion = round(($result[$conversion_currency] / $result[$amount_currency]) * $amount, 4);

	        return array(
                "title" => "Currency conversion:",
                "text" => "$amount $amount_currency = $conversion $conversion_currency",
                "source" => "https://moneyconvert.net/"
	        );
	    } else {
	        return array(
                "title" => "Uh-oh...",
                "text" => "No exchange rates could be loaded. Try again later."
	        );
		}
    }
}
?>
