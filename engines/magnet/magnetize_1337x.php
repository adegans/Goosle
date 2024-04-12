<?php
require "../../functions/tools.php";
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
set_curl_options($ch, $_REQUEST["url"], $opts->user_agents);
$response = curl_exec($ch);
curl_close($ch);

$xpath = get_xpath($response);

// No results
if(!$xpath) die();

$magnet = trim($xpath->query("//main/div/div/div/div/div/ul/li/a/@href")[0]->textContent);

header("Location: $magnet")
?>
