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

	return implode(' ', $return);
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

	return implode(' ', $return);
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