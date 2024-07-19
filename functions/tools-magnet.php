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

/*--------------------------------------
// Magnet popup for movies and tv-shows
--------------------------------------*/
function highlight_popup($opts_hash, $highlight) {
	$meta = $magnet_meta = array();

	$search_query = urlencode($highlight['title']." ".$highlight['year']);

	if(isset($highlight['category'])) $meta[] = "<strong>Genre:</strong> ".$highlight['category'];
	if(isset($highlight['language'])) $meta[] = "<strong>Language:</strong> ".get_language($highlight['language']);
	if(isset($highlight['year'])) $meta[] = "<strong>Released:</strong> ".$highlight['year'];
	if(isset($highlight['rating'])) $meta[] = "<strong>Rating:</strong> ".movie_star_rating($highlight['rating'])." <small>(".$highlight['rating']." / 10)</small>";
	if(isset($highlight['mpa_rating'])) $meta[] = "<strong>MPA Rating:</strong> ".movie_mpa_rating($highlight['mpa_rating']);

	$output = "<div id=\"highlight-".$highlight['id']."\" class=\"goosebox\">";
	$output .= "	<div class=\"goosebox-body\">";
	$output .= "		<h2>".$highlight['title']."</h2>";
	if(isset($highlight['summary'])) {
		$output .= "		<p>".$highlight['summary']."</p>";
	}
	$output .= "		<p><a href=\"./results.php?q=".$search_query."&a=".$opts_hash."&t=0\" title=\"Search on Goosle Web Search!\">Search on Goosle</a> &bull; <a href=\"./results.php?q=".$search_query."&a=".$opts_hash."&t=9\" title=\"Search on Goosle Magnet Search! For new additions results may be limited.\">Find more Magnet links</a></p>";
	if(!empty($meta)) {
		$output .= "		<p>".implode('<br />', $meta)."</p>";
	}
	unset($meta);

	// List downloads
	$output .= "		<h3>Downloads:</h3>";
	$output .= "		<p>";
	foreach($highlight['magnet_links'] as $magnet) {
		if(isset($magnet['quality'])) $magnet_meta[] = $magnet['quality'];
		if(isset($magnet['audio'])) $magnet_meta[] = $magnet['audio'];
		if(isset($magnet['type'])) $magnet_meta[] = $magnet['type'];
		$magnet_meta[] = human_filesize($magnet['filesize']);

		$output .= "<button class=\"download\" onclick=\"location.href='".$magnet['magnet']."'\">".implode(' / ', $magnet_meta)."</button>";
		unset($magnet_meta);
	}
	$output .= "		</p>";

	$output .= "		<p><a onclick=\"closepopup()\">Close</a></p>";
	$output .= "	</div>";
	$output .= "</div>";

	unset($highlight, $magnet, $magnet_meta);
	
	return $output;
}

/*--------------------------------------
// Detect NSFW results by keywords in the title
// True = nsfw, false = not nsfw
--------------------------------------*/
function detect_nsfw($string) {
	// Forbidden terms
	//Basic pattern: ^cum[-_\s]?play(ing|ed|s)?
	$nsfw_keywords = array(
		'/(deepthroat|gangbang|cowgirl|dildo|fuck|cuckold|anal|hump|finger|kiss|pegg|fist|ballbust|twerk|dogg|squirt)(ing|ed|s)?/', 
		'/(yaoi|porn|gonzo|erotica|blowbang|bukkake|gokkun|onlyfans|fansly|manyvids|softcore|hardcore|latex|lingerie|interracial|bdsm|chastity|hogtied|kinky|bondage|shibari|hitachi|upskirt)/', 
		'/(cock|creampie|cameltoe|enema|nipple|sybian|vibrator|cougar|threesome|foursome|pornstar|escort)(s)?/', 
		'/(cmnf|cfnm|pov|cbt|bbw|pawg|ssbbw|joi|cei)/', 
		'/(blow|rim|foot|hand)job(s)?/', 
		'/(org|puss)(y|ies)\s?/', 
		'/hentai(ed)?/', 
		'/jerk(ing)?[-_\s]?off/', 
		'/tw(i|u)nk(s)?/',
		'/cum(bot|ming|s)?/', 
		'/porn(hub)?/', 
		'/(m|g)ilf(s)?/', 
		'/clit(oris|s)?/', 
		'/tit(ties|s)/', 
		'/strap[-_\s]?on(ed|s)?/', 
		'/webcam(ming|s)?/', 
		'/doggy(style)?/', 
		'/(masturbat|penetrat)(e|ion|ing|ed)/', 
		'/face(fuck|sit)?(ing|ting|ed|s)?/', 
		'/gap(e|ing|ed)?/', 
		'/scissor(ing|ed)?/', 
		'/(fetish|penis|ass)(es)?/', 
		'/(fem|lez|male)dom/', 
		'/futa(nari)?/', 
		'/orgasm(ing|ed|s)?/', 
		'/(slave|pet)[-_\s]?play(ing|ed|s)?/', 
		'/submissive(d|s)?/', 
		'/tied[-_\s]?(up)?/', 
		'/glory[-_\s]?hole(d|s)?/', 
		'/swing(er|ers|ing)?/', 
	);

	// Replace everything but letters with a space
	$string = preg_replace('/\s{2,}|[^a-z0-9]+/', ' ', strtolower($string));

	preg_replace($nsfw_keywords, '*', $string, -1 , $count); 

    return ($count > 0) ? true : false;
}

/*--------------------------------------
// Depect video quality (720p/1080i etc.)
// Try to standardize terms
--------------------------------------*/
function find_video_quality($string) {
	$match = (preg_match('/[0-9]{3,4}[pi]{1}/i', $string, $match)) ? $match[0] : null;
	if(empty($match)) $match = (preg_match('/(3d|4k|5k|8k)/i', $string, $match)) ? $match[0] : null;

	if(!is_null($match)) {
		$match = trim(strtolower($match));

		if($match == '3d') $match = '3D';
		if($match == '4k') $match = '2160p (4K)';
		if($match == '5k') $match = '2880p (5K)';
		if($match == '8k') $match = '4320p (8K)';
	}
	
	return $match;
}

/*--------------------------------------
// Detect the video codec
// Try to standardize terms
--------------------------------------*/
function find_video_codec($string) {
	$return = array();

	// H.265/HEVC
	$codec = (preg_match('/\bhevc|(h|x) ?265\b/i', $string, $codec)) ? $codec[0] : null;
	// H.264/AVC
	if(empty($codec)) $codec = (preg_match('/\bavc|(h|x) ?264\b/i', $string, $codec)) ? $codec[0] : null;
	// DIVx/xVID
	if(empty($codec)) $codec = (preg_match('/\bx?(vid|div)x?\b/i', $string, $codec)) ? $codec[0] : null;
	// Other
	if(empty($codec)) $codec = (preg_match('/\bvp9|av1\b/i', $string, $codec)) ? $codec[0] : null;

	if(!is_null($codec)) {
		$codec = trim(strtolower($codec));

		if($codec == 'hevc' || $codec == 'h265') $codec = 'x265'; // Maybe it should be h.265?
		if($codec == 'avc' || $codec == 'h264') $codec = 'x264'; // Maybe it should be h.264?
		if($codec == 'xvid') $codec = 'XviD';
		if($codec == 'divx') $codec = 'DivX';
		if($codec == 'av1') $codec = 'AV1';
		if($codec == 'vp9') $codec = 'VP9';

		$return[] = $codec;
	}
	
	// Maybe a bitrate?
	$bitrate = (preg_match('/\b(8|10|12)-?bit\b/i', $string, $bitrate)) ? $bitrate[0] : null;	

	if(!is_null($bitrate)) {
		$return[] = trim(strtolower($bitrate));
	}

	// Maybe HDR?
	$hdr = (preg_match('/\bhdr|uhd|imax\b/i', $string, $hdr)) ? $hdr[0] : null;	

	if(!is_null($hdr)) {
		$return[] = trim(strtoupper($hdr));
	}

	if(count($return) > 0) return implode(' ', $return);
	
	return null;
}

/*--------------------------------------
// Detect audio type
// Try to standardize terms
--------------------------------------*/
function find_audio_codec($string) {
	$return = array();

	// Common movie codecs
	$codec = (preg_match('/\b(dts(-?hd)?|aac|e?ac3|dolby([ -]?pro[ -]?logic i{1,2})?|truehd|ddp|dd)/i', $string, $audio)) ? $audio[0] : null;
	// Common music codecs
	if(empty($codec)) $codec = (preg_match('/\b(flac|wav|mp3|ogg|pcm|wma|aiff)\b/i', $string, $codec)) ? $codec[0] : null;

	if(!is_null($codec)) {
		$codec = trim(strtoupper($codec));

		if($codec == 'EAC3' || $codec == 'DDPA' || $codec == 'DDP') $codec = 'Dolby Digital Plus';
		if($codec == 'DD') $codec = 'Dolby Digital';
		if($codec == 'DOLBY PRO LOGIC I') $codec = 'Dolby Pro Logic I';
		if($codec == 'DOLBY PRO LOGIC II') $codec = 'Dolby Pro Logic II';
		if($codec == 'DTSHD') $codec = 'DTS-HD';
		if($codec == 'TRUEHD') $codec = 'TrueHD';

		$return[] = $codec;
	}	

	// Try to add channels
	$channels = (preg_match('/(2|5|7|9)[ \.](0|1|2)\b/i', $string, $channels)) ? $channels[0] : null;
	if(empty($channels)) $channels = (preg_match('/(2|6|8) ?(ch|channel)/i', $string, $channels)) ? $channels[0] : null;

	if(!is_null($channels)) {
		$return[] = trim(str_replace(' ', '.', strtoupper($channels)));
	}

	// Try to add bitrate
	$bitrate = (preg_match('/[0-9]{2,3} ?kbp?s/i', $string, $bitrate)) ? $bitrate[0] : null;

	if(!is_null($bitrate)) {
		$return[] = trim(str_replace('kbs', 'kbps', str_replace(' ', '', strtolower($bitrate))));
	}

	// Maybe sub-codec?
	$codec2 = (preg_match('/\batmos\b/i', $string, $codec2)) ? $codec2[0] : null;	

	if(!is_null($codec2)) {
		$return[] = ucfirst(trim(strtolower($codec2)));
	}

	if(count($return) > 0) return implode(' ', $return);
	
	return null;
}

/*--------------------------------------
// Create visual star rating for some magnet results
--------------------------------------*/
function movie_star_rating($rating) {
	$rating = round($rating);
	
	$star_rating = '';
	for($i = 1; $i <= 10; $i++) {
		$star_rating .= ($i <= $rating) ? "<span class=\"star yellow\">&#9733;</span>" : "<span class=\"star\">&#9733;</span>";
	}
	
	return $star_rating;
}

/*--------------------------------------
// Create visual MPA rating for some magnet results
--------------------------------------*/
function movie_mpa_rating($rating) {
	// As described here: https://en.wikipedia.org/wiki/Motion_Picture_Association_film_rating_system
	if($rating == "G") {
		$rating = "<span class=\"mpa-g\"><strong>G - General Audiences</strong></span> &bull; <em>Suitable for all ages.</em>";
	} else if("PG") {
		$rating = "<span class=\"mpa-pg\"><strong>PG - Parental Guidance Suggested</strong></span> &bull; <em>May not be suitable for children.</em>";
	} else if("PG-13") {
		$rating = "<span class=\"mpa-pg13\"><strong>PG-13 - Parents Strongly Cautioned</strong></span> &bull; <em>May be inappropriate for children under 13.</em>";
	} else if("R") {
		$rating = "<span class=\"mpa-r\"><strong>R - Restricted</strong></span> &bull; <em>Persons under 17 require accompanying adult.</em>";
	} else if("NC-17") {
		$rating = "<span class=\"mpa-nc17\"><strong>NC-17 - Adults Only</strong></span> &bull; <em>Not suitable for persons under 17.</em>";
	} else {
		$rating = "<span>".$rating."</span>";
	}	
	
	return $rating;
}

/*--------------------------------------
// Return the language based on the ISO name
--------------------------------------*/
function get_language($string) {
	$languages = array("ab" => "Abkhaz", "aa" => "Afar", "af" => "Afrikaans", "ak" => "Akan", "sq" => "Albanian", "am" => "Amharic", "ar" => "Arabic", "an" => "Aragonese", "hy" => "Armenian", "as" => "Assamese", "av" => "Avaric", "ae" => "Avestan", "ay" => "Aymara", "az" => "Azerbaijani", "bm" => "Bambara", "ba" => "Bashkir", "eu" => "Basque", "be" => "Belarusian", "bn" => "Bengali", "bh" => "Bihari", "bi" => "Bislama", "bs" => "Bosnian", "br" => "Breton", "bg" => "Bulgarian", "my" => "Burmese", "ca" => "Catalan", "ch" => "Chamorro", "ce" => "Chechen", "ny" => "Nyanja", "zh" => "Chinese", "cn" => "Chinese", "cv" => "Chuvash", "kw" => "Cornish", "co" => "Corsican", "cr" => "Cree", "hr" => "Croatian", "cs" => "Czech", "da" => "Danish", "dv" => "Maldivian;", "nl" => "Dutch", "en" => "English", "eo" => "Esperanto", "et" => "Estonian", "ee" => "Ewe", "fo" => "Faroese", "fj" => "Fijian", "fi" => "Finnish", "fr" => "French", "ff" => "Fulah", "gl" => "Galician", "ka" => "Georgian", "de" => "German", "el" => "Greek, Modern", "gn" => "Guaraní", "gu" => "Gujarati", "ht" => "Haitian Creole", "ha" => "Hausa", "he" => "Hebrew (modern)", "hz" => "Herero", "hi" => "Hindi", "ho" => "Hiri Motu", "hu" => "Hungarian", "ia" => "Interlingua", "id" => "Indonesian", "ie" => "Interlingue", "ga" => "Irish", "ig" => "Igbo", "ik" => "Inupiaq", "io" => "Ido", "is" => "Icelandic", "it" => "Italian", "iu" => "Inuktitut", "ja" => "Japanese", "jv" => "Javanese", "kl" => "Kalaallisut", "kn" => "Kannada", "kr" => "Kanuri", "ks" => "Kashmiri", "kk" => "Kazakh", "km" => "Khmer", "ki" => "Kikuyu", "rw" => "Kinyarwanda", "ky" => "Kirghiz, Kyrgyz", "kv" => "Komi", "kg" => "Kongo", "ko" => "Korean", "ku" => "Kurdish", "kj" => "Kwanyama", "la" => "Latin", "lb" => "Luxembourgish", "lg" => "Luganda", "li" => "Limburgish, Limburgan, Limburger", "ln" => "Lingala", "lo" => "Lao", "lt" => "Lithuanian", "lu" => "Luba-Katanga", "lv" => "Latvian", "gv" => "Manx", "mk" => "Macedonian", "mg" => "Malagasy", "ms" => "Malay", "ml" => "Malayalam", "mt" => "Maltese", "mi" => "Māori", "mr" => "Marathi", "mh" => "Marshallese", "mn" => "Mongolian", "na" => "Nauru", "nv" => "Navajo, Navaho", "nb" => "Norwegian Bokmål", "nd" => "North Ndebele", "ne" => "Nepali", "ng" => "Ndonga", "nn" => "Norwegian Nynorsk", "no" => "Norwegian", "ii" => "Nuosu", "nr" => "South Ndebele", "oc" => "Occitan", "oj" => "Ojibwe, Ojibwa", "cu" => "Old Slavonic", "om" => "Oromo", "or" => "Oriya", "os" => "Ossetian", "pa" => "Punjabi", "pi" => "Pāli", "fa" => "Persian", "pl" => "Polish", "ps" => "Pashto, Pushto", "pt" => "Portuguese", "qu" => "Quechua", "rm" => "Romansh", "rn" => "Kirundi", "ro" => "Romanian", "ru" => "Russian", "sa" => "Sanskrit", "sc" => "Sardinian", "sd" => "Sindhi", "se" => "Northern Sami", "sm" => "Samoan", "sg" => "Sango", "sr" => "Serbian", "gd" => "Gaelic", "sn" => "Shona", "si" => "Sinhala", "sk" => "Slovak", "sl" => "Slovene", "so" => "Somali", "st" => "Southern Sotho", "es" => "Spanish", "su" => "Sundanese", "sw" => "Swahili", "ss" => "Swati", "sv" => "Swedish", "ta" => "Tamil", "te" => "Telugu", "tg" => "Tajik", "th" => "Thai", "ti" => "Tigrinya", "bo" => "Tibetan Standard, Tibetan, Central", "tk" => "Turkmen", "tl" => "Tagalog", "tn" => "Tswana", "to" => "Tonga", "tr" => "Turkish", "ts" => "Tsonga", "tt" => "Tatar", "tw" => "Twi", "ty" => "Tahitian", "ug" => "Uighur, Uyghur", "uk" => "Ukrainian", "ur" => "Urdu", "uz" => "Uzbek", "ve" => "Venda", "vi" => "Vietnamese", "vo" => "Volapük", "wa" => "Walloon", "cy" => "Welsh", "wo" => "Wolof", "fy" => "Western Frisian", "xh" => "Xhosa", "yi" => "Yiddish", "yo" => "Yoruba", "za" => "Zhuang, Chuang");
	
	return $languages[$string];
}

/*--------------------------------------
// Detect TV show Seasons and Episodes in results
--------------------------------------*/
function is_season_or_episode($search_query, $result_query) {
	// Check if you searched for a tv show and result is a tv show
	if(preg_match_all('/.+?(?=S[0-9]{1,4}E[0-9]{1,3})/', strtoupper($search_query), $match_query) && preg_match_all('/.+?(?=S[0-9]{1,4}E[0-9]{1,3})/', strtoupper($result_query), $match_result)) {
		// If a match: [0][0] = Season and [0][1] = Episode
		if($match_query[0][0] != $match_result[0][0] || (array_key_exists(1, $match_query[0]) && $match_query[0][1] != $match_result[0][1])) {
			return false; // Not the tv show (episode) you're looking for
		}
	}

    return true;
}
?>