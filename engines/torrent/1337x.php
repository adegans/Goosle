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
class LeetxRequest extends EngineRequest {
	public function get_request_url() {
		$url = "https://1337x.to/search/".urlencode($this->query)."/1/";

        return $url;

	}
	
	public function parse_results($response) {
		$results = array();
		$xpath = get_xpath($response);
		
		// Failed to load page
		if(!$xpath) return $results;
		
		$categories = array(
			1 => "DVD",
			2 => "Divx/Xvid",
			3 => "SVCD/VCD",
			4 => "Dubs/Dual Audio",
			5 => "DVD",
			6 => "Divx/Xvid",
			7 => "SVCD/VCD",
			9 => "Documentary",

			10 => "PC Game",
			11 => "PS2",
			12 => "PSP",
			13 => "Xbox",
			14 => "Xbox360",
			15 => "PS1",
			16 => "Dreamcast",
			17 => "Other (Gaming)",
			18 => "PC Software",
			19 => "Mac Software",

			20 => "Linux Software",
			21 => "Other (Software)",
			22 => "MP3",
			23 => "Lossless Audio",
			24 => "DVD (Music)",
			25 => "Music Video",
			26 => "Radio",
			27 => "Other (Audio)",
			28 => "Anime",

			33 => "Emulation",
			34 => "Tutorials",
			35 => "Sounds",
			36 => "E-Books",
			37 => "Images",
			38 => "Mobile Phone",
			39 => "Comics",

			40 => "Other",
			41 => "HD (Video)",
			42 => "HD (Video)",
			43 => "PS3",
			44 => "Wii",
			45 => "DS",
			46 => "GameCube",
			47 => "Nulled Script",
			48 => "Video",
			49 => "Picture",

			50 => "Magazine",
			51 => "Hentai",
			52 => "Audiobook",
			53 => "Album (Music)",
			54 => "h.264/x264",
			55 => "Mp4",
			56 => "Android",
			57 => "iOS",
			58 => "Box Set (Music)",
			59 => "Discography",

			60 => "Single (Music)",
			66 => "3D",
			67 => "Games",
			68 => "Concerts",
			69 => "AAC (Music)",

			70 => "HEVC/x265",
			71 => "HEVC/x265",
			72 => "3DS",
			73 => "Bollywood",
			74 => "Cartoon",
			75 => "SD (Video)",
			76 => "UHD",
			77 => "PS4",
			78 => "Dual Audio (Video)",
			79 => "Dubbed (Video)",

			80 => "Subbed",
			81 => "Raw",
			82 => "Switch",
		);

		// Scrape the page
		foreach($xpath->query("//table/tbody/tr") as $result) {
			$name = sanitize($xpath->evaluate(".//td[@class='coll-1 name']/a", $result)[1]->textContent);
			$url = "https://1337x.to".sanitize($xpath->evaluate(".//td[@class='coll-1 name']/a/@href", $result)[1]->textContent);
			$magnet = "./engines/torrent/magnetize_1337x.php?url=".$url;
			$seeders = sanitize($xpath->evaluate(".//td[@class='coll-2 seeds']", $result)[0]->textContent);
			$leechers = sanitize($xpath->evaluate(".//td[@class='coll-3 leeches']", $result)[0]->textContent);
			$size_unformatted = explode(" ", sanitize($xpath->evaluate(".//td[contains(@class, 'coll-4 size')]", $result)[0]->textContent));
			$size = $size_unformatted[0] . " " . preg_replace("/[0-9]+/", "", $size_unformatted[1]);
			
			// Ignore results with 0 seeders?
			if($this->opts->show_zero_seeders == "off" AND $seeders == 0) continue;
			
			// Get extra data
			$category = explode("/", sanitize($xpath->evaluate(".//td[@class='coll-1 name']/a/@href", $result)[0]->textContent));
			$category = $category[2];
			
			// Block these categories
			if(in_array($category, $this->opts->leetx_categories_blocked)) continue;
			
			// Filter by Season (S01) or Season and Episode (S01E01)
			// Where [0][0] = Season and [0][1] = Episode
			if(preg_match_all("/(S[0-9]{1,3})|(E[0-9]{1,3})/i", $this->query, $query_episode) && preg_match_all("/(S[0-9]{1,3})|(E[0-9]{1,3})/i", $name, $match_episode)) {
				if($query_episode[0][0] != $match_episode[0][0] || (array_key_exists(1, $query_episode[0]) && array_key_exists(1, $match_episode[0]) && $query_episode[0][1] != $match_episode[0][1])) {
					continue;
				}
			}

			$id = uniqid(rand(0, 9999));
			
			$results[] = array (
				// Required
				"id" => $id, "source" => "1337x.to", "name" => $name, "magnet" => $magnet, "hash" => null, "seeders" => $seeders, "leechers" => $leechers, "size" => $size,
				// Extra
				"category" => $categories[$category], "url" => $url
			);
		}
		unset($response, $xpath);
		
		return $results;
	}
}
?>