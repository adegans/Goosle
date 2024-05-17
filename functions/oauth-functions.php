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

/*--------------------------------------
// Curl requests for oAUTH
--------------------------------------*/
function oath_curl_request($url, $user_agent, $method, $header, $post) {
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $url);
	if($method == "post") {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	} else {
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
	}
	curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(array('Accept: application/json, */*;q=0.8'), $header));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
	curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ch, CURLOPT_VERBOSE, false);
	
	$response = curl_exec($ch);
	curl_close($ch);
	
	return json_decode($response, true);
}

/*--------------------------------------
// Store generated tokens
--------------------------------------*/
function oath_store_token($token_file, $connect, $token) {
	if(!is_file($token_file)){
		// Create token file
	    file_put_contents($token_file, serialize(array($connect => $token)));
	} else {
		// Update token file
		$tokens = unserialize(file_get_contents($token_file));
		$tokens[$connect] = $token;
	    file_put_contents($token_file, serialize($tokens));
	}
}		

?>