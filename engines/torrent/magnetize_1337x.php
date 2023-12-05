<?php
require "../../misc/tools.php";
$opts = require "../../config.php";

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

$ch = curl_init();

curl_setopt($this->ch, CURLOPT_URL, $_REQUEST["url"]);
curl_setopt($this->ch, CURLOPT_HTTPGET, 1); // Redundant? Probably...
curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($this->ch, CURLOPT_VERBOSE, false);
curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($this->ch, CURLOPT_USERAGENT, $opts->user_agents[array_rand($opts->user_agents)]);
curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Accept-Language: en-US,en;q=0.5',
    'Upgrade-Insecure-Requests: 1'
));
curl_setopt($this->ch, CURLOPT_ENCODING, "gzip,deflate");
curl_setopt($this->ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_WHATEVER);
curl_setopt($this->ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
curl_setopt($this->ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
curl_setopt($this->ch, CURLOPT_MAXREDIRS, 5);
curl_setopt($this->ch, CURLOPT_TIMEOUT, 3);
curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
curl_close($ch);

$xpath = get_xpath($response);

// No results
if(!$xpath) die();

$magnet = $xpath->query("//main/div/div/div/div/div/ul/li/a/@href")[0]->textContent;
$magnet = trim($magnet);

header("Location: $magnet")
?>
