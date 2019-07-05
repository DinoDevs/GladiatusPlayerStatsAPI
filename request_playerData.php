<?php
/*
 * Gladiatus Battle Simulator by Gladiatus Crazy Team
 * https://www.facebook.com/GladiatusCrazyAddOn
 * Authors : Gramthanos, Greatapo
 *
 * Gladiatus Player Data Get Library
 */

/*
	Example Use

	$stats = getPlayerData(
		array(
			'country' => 'gr',
			'server' => '4',
			'name' => 'darkthanos',
			'id' => null
		),
		array(
			'profile' => true,
			'statistics' => false,
			'achievements' => false,
			'turma' => false
		)
	);
*/

	/*
		Validate Functions
	*/
		// Country Validate
		function request_validate_country ($country) {
			switch ($country) {
				// 31 servers
				case 'ar': case 'ba': case 'br': case 'dk': case 'de': case 'ee': case 'es': case 'fr': case 'it': case 'lv': case 'lt': case 'hu': case 'mx': case 'nl': case 'no': case 'pl': case 'pt': case 'ro': case 'sk': case 'fi': case 'se': case 'tr': case 'us': case 'en': case 'cz': case 'gr': case 'bg': case 'ru': case 'il': case 'ae': case 'tw':
					return true;
					break;
				default:
					return false;
					break;
			}
		}

		// Validate Server
		function request_validate_server ($server) {
			// This should be strictier with /^[1-9]+$/
			if (!preg_match("/[1-9]+/i", $server)) {
				return false;
			}
			return true;
		}

		// Validate Player Name
		function request_validate_name ($name) {
			if (!preg_match('/[^~#&\\[\\]\\{\\}\|\\/\'\\";:?,<>]{3,15}/', $name)) {
				return false;
			}
			return true;
		}

		// Validate Player ID
		function request_validate_id ($id) {
			// This should be strictier with /^[1-9]+$/
			if (!preg_match("/[1-9]+/i", $id)) {
				return false;
			}
			return true;
		}
		
	/*
		Cache Functions
	*/
		// Cache Variable
		$request_cache = array();

		// Load a cache
		// This needs more options
		// like a global on/off option and a folder location option
		function request_cache_load($country, $server) {
			// Pointer to cashe variable
			global $request_cache;
			// Load Cache File
			$cache = @file_get_contents(dirname(__File__).'/request_playerData_cache/'.$country.'_'.$server.'.json');
			// If country section do not exist create
			if (!isset($request_cache[$country])) {
				$request_cache[$country] = array();
			}
			// Check if cache exists
			if (!$cache) {
				return false;
			}
			// Parse cache
			$cache = json_decode($cache, true);
			if (is_null($cache) || !$cache) {
				return false;
			}
			// Save cache
			$request_cache[$country][$server] = $cache;
			// Return
			return true;
		}
		// Check the cache exist
		function request_cache_check($country, $server) {
			// Pointer to cashe variable
			global $request_cache;
			// If cache not loaded 
			if (!isset($request_cache[$country]) || !isset($request_cache[$country][$server])) {
				// Load cache
				request_cache_load($country, $server);
				// No cache
				if (!isset($request_cache[$country][$server])) {
					return false;
				}
			}
			return true;
		}
		// Check the cache if name exist
		function request_cache_check_name($country, $server, $name) {
			// Pointer to cashe variable
			global $request_cache;
			// Check cache ready
			if (!request_cache_check($country, $server)) {
				return false;
			}
			$name = strtolower($name);
			// Check if player exsist on cache
			if (isset($request_cache[$country][$server][$name])) {
				// Return data
				return $request_cache[$country][$server][$name];
			}
			return false;
		}
		// Check the cache if id exist
		function request_cache_check_id($country, $server, $id) {
			// Pointer to cashe variable
			global $request_cache;
			// Check cache ready
			if (!request_cache_check($country, $server)) {
				return false;
			}
			// Check if player exsist on cache
			if (isset($request_cache[$country][$server]['id'][$id])) {
				// Return data
				$cache = $request_cache[$country][$server]['id'][$id];
				// If data exsist too
				if (isset($request_cache[$country][$server][$cache['name']])) {
					// Return data
					return $request_cache[$country][$server][$cache['name']];
				}
				// Insert more data
				$cache['id'] = $id;
				$cache['game'] = array(
					'country' => $country,
					'server' => $server
				);
				// Return data
				return $cache;
			}
			return false;
		}
		// Save
		function request_cache_save($country, $server) {
			// Pointer to cashe variable
			global $request_cache;
			// Save Cache File
			$cache = @file_put_contents(dirname(__File__).'/request_playerData_cache/'.$country.'_'.$server.'.json', json_encode($request_cache[$country][$server]));
			if (!$cache) {
				return false;
			}
			return true;
			
		}
		function request_cache_save_search($country, $server, $data) {
			// Pointer to cashe variable
			global $request_cache;
			// Check if exist
			if (!isset($request_cache[$country][$server])) {
				$request_cache[$country][$server] = array();
				if (!isset($request_cache[$country][$server]['id'])) {
					$request_cache[$country][$server]['id'] = array();
				}
			}
			$request_cache[$country][$server]['id'][$data['id']] = array(
				'name' => $data['name']
			);
			$request_cache[$country][$server][strtolower($data['name'])] = array(
				'id' => $data['id']
			);
		}
	
	/*
		Request Functions
	*/
		// Make a player search by name request
		function request_searchPlayerByName($country, $server, $name) {
			// Check cashe
			$cache = request_cache_check_name($country, $server, $name);
			if ($cache) {
				return $cache;
			}

			// Post data and get search results
			$html = @file_get_contents(
				'https://s'.$server.'-'.$country.'.gladiatus.gameforge.com/game/index.php?mod=highscore&submod=suche',
				false,
				stream_context_create(array(
					'http' => array(
						'method' => 'POST',
						'header' => 'Content-type: application/x-www-form-urlencoded',
						'content' => http_build_query(array('s' => 's', 'xs' => $name))
					)
				))
			);
			// On request error
			if (!$html) {
				// Return page not found error
				return array(
					'error' => true,
					'message' => 'Our monkeys-workers failed to complete the task.'
				);
			}
			// Shrink string by deleting useless data
			$html = substr($html, 45000, -2000);

			// Match results patterns
			$found = preg_match_all('/<a\\s+href="index\\.php\\?mod=player&p=(\\d+)[^>]+>\\s*([^<]+)<\\/a>(<a href="index\\.php\\?mod=guild&i=(\\d+)[^>]+>\\s*([^<]+)<\\/a>)*/', $html, $matches);

			// Check if no player was found
			$index = 0;
			if($found && $matches[2][0] != $name ){
				$found = false;
				for ($i = 0; $i < count($matches[1]); $i++) {
					if($matches[2][$i] == $name){
						$found = true;
						$index = $i;
						break;
					}
				}
			}
			if (!$found) {
				return array(
					'error' => true,
					'message' => 'Player not found.'
				);
			}

			// Create player object
			$player = array(
				'name' => $matches[2][0],
				'id' => $matches[1][0]
			);
			// Cache it
			for ($i = 0; $i < count($matches[1]); $i++) {
				request_cache_save_search($country, $server, array(
					'name' => $matches[2][$i],
					'id' => $matches[1][$i]
				));
			}
			request_cache_save($country, $server);

			// Insert guild if any
			if (isset($matches[4]) && strlen($matches[4][0]) > 0) {
				$player['guild'] = array(
					'tag' => $matches[5][0],
					'id' => $matches[4][0]
				);
			}
			// Game server info
			$player['game'] = array(
				'country' => $country,
				'server' => $server
			);

			// Return the object
			return $player;
		}

		// Request player's profile page data
		function request_getPlayerProfileData($country, $server, $id) {
			// Get profile page code
			$html = @file_get_contents(
				'https://s'.$server.'-'.$country.'.gladiatus.gameforge.com/game/index.php?mod=player&p='.$id,
				false,
				stream_context_create(array(
					'http' => array('method' => 'GET')
				))
			);
			// On request error
			if (!$html) {
				// Return page not found error
				return array(
					'error' => true,
					'message' => 'Our monkeys-workers failed to complete the task.'
				);
			}
			// Shrink string by deleting useless data
			$html = substr($html, 46000, -2000);

			// Create player object
			$player = array(
				'id' => $id,
				'name' => NULL,
				'profile' => array(),
				'game' => array(
					'country' => $country,
					'server' => $server
				)
			);

			// Get player's name
			$found = preg_match('/<div class="playername(_achievement)* ellipsis">([^<]+)<\\/div>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [001].');}
			$player['name'] = preg_replace('/\s+/', '', $matches[2]);

			// Get player's image
			$found = preg_match('/<div id="avatar" class="player_picture"[^>]*>[^<]*(<[^>]*>)/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [002].');}
			$found = preg_match('/background-image: *url\\(([^\\)]+)\\);*/', $matches[1], $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [003].');}
			$player['img'] = 'https://s'.$server.'-'.$country.'.gladiatus.gameforge.com/game/'.$matches[1];

			$custom_img = false;
			$found = preg_match('/##GTI=(http[^#]+)##/', $html, $matches);
			if ($found) {
				//##GTI=http://i617.photobucket.com/albums/tt260/goldisever/GCAO/dragoni.jpg##
				$found = preg_match('/(\\.png|\\.jpeg|\\.jpg|\\.gif|\\.bmp)$/', $matches[1]);
				if ($found) {
					$player['img'] = $matches[1];
					$custom_img = true;
				}
			}

			if(!$custom_img){
				//avatar avatar_costume_part
				$found = preg_match_all('/"avatar avatar_costume_part"[^>]+/', $html, $matches);
				if ($found) {
					$player['img'] = [];
					foreach ($matches[0] as $image_part) {
						$found = preg_match('/background-image: *url\\(([^\\)]+)\\);*/', $image_part, $matches_image_part);
						array_push($player['img'], 'https://s'.$server.'-'.$country.'.gladiatus.gameforge.com/game/'.$matches_image_part[1]);
					}
				}
			}

			// Get guild info
			$found = preg_match('/<a href="index.php\\?mod=guild&i=(\\d+)">([^ ]+) \\[([^<]+)\\]<\\/a>/', $html, $matches);
			if ($found) {
				$player['guild'] = array();
				$player['guild']['id'] = $matches[1];
				$player['guild']['name'] = $matches[2];
				$player['guild']['tag'] = $matches[3];
			}

			// Get player's level
			$found = preg_match('/<span id="char_level" class="charstats_value22">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [004].');}
			$player['profile']['level'] = $matches[1];

			// Get player's life
			$found = preg_match('/<div\\s+class="charstats_bg"\\s+id="char_leben_tt"\\s+data-tooltip="\\[\\[\\[\\[[^,]+,&quot;(\\d+)\\s*\\\\\\/\\s*(\\d+)&quot;/', $html, $matches);
			if (!$found) {
				return array('error' => true,'message' => 'Internal parse error [005].');
			}
			$player['profile']['life'] = array(
				$matches[1],
				$matches[2]
			);

			// Get player's experience
			$found = preg_match('/<div\\s+class="charstats_bg"\\s+id="char_exp_tt"\\s+data-tooltip="\\[\\[\\[\\[[^,]+,&quot;(\\d+)\\s*\\\\\\/\\s*(\\d+)&quot;/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [006].');}
			$player['profile']['experience'] = array(
				$matches[1],
				$matches[2]
			);

			// Get player's strength
			$found = preg_match('/<span id="char_f0" class="charstats_value">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [007].');}
			$player['profile']['strength'] = $matches[1];

			// Get player's skill
			$found = preg_match('/<span id="char_f1" class="charstats_value">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [008].');}
			$player['profile']['skill'] = $matches[1];

			// Get player's agility
			$found = preg_match('/<span id="char_f2" class="charstats_value">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [009].');}
			$player['profile']['agility'] = $matches[1];

			// Get player's constitution
			$found = preg_match('/<span id="char_f3" class="charstats_value">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [010].');}
			$player['profile']['constitution'] = $matches[1];

			// Get player's charisma
			$found = preg_match('/<span id="char_f4" class="charstats_value">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [011].');}
			$player['profile']['charisma'] = $matches[1];

			// Get player's intelligence
			$found = preg_match('/<span id="char_f5" class="charstats_value">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [012].');}
			$player['profile']['intelligence'] = $matches[1];

			// Get player's armor
			$found = preg_match('/<span id="char_panzer" class="charstats_value22">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [013].');}
			$player['profile']['armor'] = $matches[1];

			// Get player's damage
			$found = preg_match('/<span id="char_schaden" class="charstats_value22">(\\d+)\\s*-\\s*(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [014].');}
			$player['profile']['damage'] = array(
				$matches[1],
				$matches[2]
			);

			// Get player's healing
			$found = preg_match('/<span id="char_healing" class="charstats_value22">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [015].');}
			$player['profile']['healing'] = $matches[1];

			// Find criticals
			$found = preg_match_all('/&quot;,(-?\\d+)],\s*\\[&quot;#BA9700&quot;,&quot;#BA9700&quot;\\]\\]/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [016].');}
			if (count($matches[1]) <= 10) {return array('error' => true,'message' => 'Internal parse error [016].');}

			$player['profile']['avoid-critical-points'] = $matches[1][7];
			$player['profile']['block-points'] = $matches[1][8];
			$player['profile']['critical-points'] = $matches[1][9];
			$player['profile']['critical-healing'] = $matches[1][11];

			// Find criticals
			$found = preg_match_all('/&quot;,&quot;(\d+) %&quot;\],\[&quot;#DDDDDD&quot;,&quot;#DDDDDD&quot;\]\]/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [017].');}
			if (count($matches[1]) < 4) {return array('error' => true,'message' => 'Internal parse error [017].');}

			$player['profile']['avoid-critical-percent'] = $matches[1][0];
			$player['profile']['block-percent'] = $matches[1][1];
			$player['profile']['critical-percent'] = $matches[1][2];
			$player['profile']['critical-healing-percent'] = $matches[1][3];

			// Buffs
			$player['profile']['buffs'] = array(
				'minerva' => false,			// Can't detect
				'mars' => false,				// Can't detect
				'apollo' => false,				// Can't detect
				'honour_veteran' => false,
				'honour_destroyer' => false	// Can't detect
			);

			$level_factor = $player['profile']['level'] - 8;
			if($level_factor < 2) $level_factor = 2;

			// Detect buff honour of the veteran
			if ($player['profile']['critical-percent'] - round($player['profile']['critical-points'] * 52 / $level_factor / 5) == 10) {
				$player['profile']['buffs']['honour_veteran'] = true;
			}
			
			// Return the object
			return $player;
		}

		// Request player's turma players data
		function request_getPlayerTurmaData($country, $server, $id) {
			// Players array
			$turma_data = array(
				'id' => $id,
				'players' => array(),
				'game' => array(
					'country' => $country,
					'server' => $server
				)
			);

			// Get main player data
			$data = request_getPlayerTurmaPlayerData($country, $server, $id, 0, true);
			if (isset($data['error'])) {return $data;}
			$team = $data['team'];
			$data['player']['role'] = $team['0'];
			array_push($turma_data['players'], $data['player']);

			// Get player 1
			if ($team['1']) {
				$data = request_getPlayerTurmaPlayerData($country, $server, $id, 1);
				if (isset($data['error'])) {return $data;}
				$data['player']['role'] = $team['1'];
				array_push($turma_data['players'], $data['player']);
			}

			// Get player 2
			if ($team['2']) {
				$data = request_getPlayerTurmaPlayerData($country, $server, $id, 2);
				if (isset($data['error'])) {return $data;}
				$data['player']['role'] = $team['2'];
				array_push($turma_data['players'], $data['player']);
			}

			// Get player 3
			if ($team['3']) {
				$data = request_getPlayerTurmaPlayerData($country, $server, $id, 3);
				if (isset($data['error'])) {return $data;}
				$data['player']['role'] = $team['3'];
				array_push($turma_data['players'], $data['player']);
			}

			// Get player 4
			if ($team['4']) {
				$data = request_getPlayerTurmaPlayerData($country, $server, $id, 4);
				if (isset($data['error'])) {return $data;}
				$data['player']['role'] = $team['4'];
				array_push($turma_data['players'], $data['player']);
			}

			return $turma_data;
		}
		// Request player's turma player data
		function request_getPlayerTurmaPlayerData($country, $server, $id, $player_index, $getOtherPlayers = false) {
			// Get profile page code
			$html = @file_get_contents(
				'https://s'.$server.'-'.$country.'.gladiatus.gameforge.com/game/index.php?mod=player&doll='.($player_index+2).'&p='.$id,
				false,
				stream_context_create(array(
					'http' => array('method' => 'GET')
				))
			);
			// On request error
			if (!$html) {
				// Return page not found error
				return array(
					'error' => true,
					'message' => 'Our monkeys-workers failed to complete the task.'
				);
			}
			// Shrink string by deleting useless data
			$html = substr($html, 50000, -2000);

			// Create player object
			$data = array(
				'player' => array(),
				'team' => array(
					'0' => false,
					'1' => false,
					'2' => false,
					'3' => false,
					'4' => false
				)
			);

			// Get player's name
			$found = preg_match('/<div class="playername(_achievement)* ellipsis">([^<]+)<\\/div>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [T001].');}
			$data['player']['name'] = trim($matches[2]);

			// Get player's level
			$found = preg_match('/<span id="char_level" class="charstats_value22">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [T002].');}
			$data['player']['level'] = $matches[1];

			// Get player's life
			$found = preg_match('/<div\\s+class="charstats_bg"\\s+id="char_leben_tt"\\s+data-tooltip="\\[\\[\\[\\[[^,]+,&quot;(\\d+)\\s*\\\\\\/\\s*(\\d+)&quot;/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [T003].');}
			$data['player']['life'] = array(
				$matches[1],
				$matches[2]
			);

			// Get player's strength
			$found = preg_match('/<span id="char_f0" class="charstats_value">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [T004].');}
			$data['player']['strength'] = $matches[1];

			// Get player's skill
			$found = preg_match('/<span id="char_f1" class="charstats_value">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [T005].');}
			$data['player']['skill'] = $matches[1];

			// Get player's agility
			$found = preg_match('/<span id="char_f2" class="charstats_value">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [T006].');}
			$data['player']['agility'] = $matches[1];

			// Get player's constitution
			$found = preg_match('/<span id="char_f3" class="charstats_value">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [T007].');}
			$data['player']['constitution'] = $matches[1];

			// Get player's charisma
			$found = preg_match('/<span id="char_f4" class="charstats_value">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [T008].');}
			$data['player']['charisma'] = $matches[1];

			// Get player's intelligence
			$found = preg_match('/<span id="char_f5" class="charstats_value">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [T009].');}
			$data['player']['intelligence'] = $matches[1];

			// Get player's armor
			$found = preg_match('/<span id="char_panzer" class="charstats_value22">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [T010].');}
			$data['player']['armor'] = $matches[1];

			// Get player's damage
			$found = preg_match('/<span id="char_schaden" class="charstats_value22">(\\d+)\\s*-\\s*(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [T011].');}
			$data['player']['damage'] = array(
				$matches[1],
				$matches[2]
			);

			// Get player's healing
			$found = preg_match('/<span id="char_healing" class="charstats_value22">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [T012].');}
			$data['player']['healing'] = $matches[1];

			// Get player's threat
			$found = preg_match('/<span id="char_threat" class="charstats_value22">(\\d+)<\\/span>/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [T013].');}
			$data['player']['threat'] = $matches[1];

			// Find criticals
			$found = preg_match_all('/&quot;,(\\d+)],\s*\\[&quot;#BA9700&quot;,&quot;#BA9700&quot;\\]\\]/', $html, $matches);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error [016].');}
			if (count($matches[1]) <= 10) {return array('error' => true,'message' => 'Internal parse error [016].');}

			// Get player's avoid critical points
			$data['player']['avoid-critical-points'] = $matches[1][7];
			
			// Get player's block points
			$data['player']['block-points'] = $matches[1][8];
			
			// Get player's critical points
			$data['player']['critical-points'] = $matches[1][9];

			// Get player's critical healing
			$data['player']['critical-healing'] = isset($matches[1][12]) ? $matches[1][12] : 0;

			if ($getOtherPlayers) {
				// Get Roles
				$roles = request_turma_roles_translation($country);

				// Main Player
				$found = preg_match('/<div class="charmercpic doll2"\\s*data-tooltip="([^"]+)"/', $html, $matches);
				if (!$found) {return array('error' => true,'message' => 'Internal parse error [T002R].');}
				$data['team']['0'] = request_turma_resolve_role($roles, $matches[1]);

				// Player 1
				$found = preg_match('/<div class="charmercpic doll3"/', $html);
				if ($found) {
					$found = preg_match('/<div class="charmercpic doll3"\\s*data-tooltip="([^"]+)"/', $html, $matches);
					if (!$found) {return array('error' => true,'message' => 'Internal parse error [T003R].');}
					$data['team']['1'] = request_turma_resolve_role($roles, $matches[1]);
				}
				// Player 2
				$found = preg_match('/<div class="charmercpic doll4"/', $html);
				if ($found) {
					$found = preg_match('/<div class="charmercpic doll4"\\s*data-tooltip="([^"]+)"/', $html, $matches);
					if (!$found) {return array('error' => true,'message' => 'Internal parse error [T004R].');}
					$data['team']['2'] = request_turma_resolve_role($roles, $matches[1]);
				}
				// Player 3
				$found = preg_match('/<div class="charmercpic doll5"/', $html);
				if ($found) {
					$found = preg_match('/<div class="charmercpic doll5"\\s*data-tooltip="([^"]+)"/', $html, $matches);
					if (!$found) {return array('error' => true,'message' => 'Internal parse error [T005R].');}
					$data['team']['3'] = request_turma_resolve_role($roles, $matches[1]);
				}
				// Player 4
				$found = preg_match('/<div class="charmercpic doll6"/', $html);
				if ($found) {
					$found = preg_match('/<div class="charmercpic doll6"\\s*data-tooltip="([^"]+)"/', $html, $matches);
					if (!$found) {return array('error' => true,'message' => 'Internal parse error [T006R].');}
					$data['team']['4'] = request_turma_resolve_role($roles, $matches[1]);
				}
			}

			// Return the object
			return $data;
		}

		// Request player's profile page data
		function request_getPlayerStatisticsData($country, $server, $id) {
			// Get statistics page code
			$html = @file_get_contents(
				'https://s'.$server.'-'.$country.'.gladiatus.gameforge.com/game/index.php?mod=player&submod=stats&p='.$id,
				false,
				stream_context_create(array(
					'http' => array('method' => 'GET')
				))
			);
			// On request error
			if (!$html) {
				// Return page not found error
				return array(
					'error' => true,
					'message' => 'Our monkeys-workers failed to complete the task.'
				);
			}
			// Shrink string by deleting useless data
			$html = substr($html, 50000, -2000);

			// Create player object
			$player = array(
				'id' => $id,
				'statistics' => array(),
				'game' => array(
					'country' => $country,
					'server' => $server
				)
			);

			// Get player's statistics
			$found = preg_match_all('/<th>[^<]+<\\/th>\\s*<td class="stats_value">(\\d+\\.*\\d*\\.*\\d*\\.*\\d*)\\s*</', $html, $statistics_code);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error.');}
			// Counting index
			$index = 0;

			$statistics_order_names = array(
				'arena' => array(
					'Battles',
					'Wins',
					'Defeats',
					'Draws',
					'Issued hit points',
					'Taken hit points',
					'Gold captured',
					'Gold lost',
					'Wins in a row'
				),
				'turma' => array(
					'Battles',
					'Wins',
					'Defeats',
					'Draws',
					'Gold captured',
					'Gold lost',
					'Wins in a row'
				),
				'quests' => array(
					'Completed quests',
					'Completed quests with a time limit'
				),
				'victories' => array(
					'Points',
					'Honour',
					'Fame',
					'People mugged'
				)
			);

			// Arena
			for ($i = 0; $i < count($statistics_order_names['arena']); $i++) {
				$satistic_name = str_replace(array('(', ')'), '', str_replace(' ', '_', strtolower($statistics_order_names['arena'][$i])));
				$player['statistics']['arena'][$satistic_name] = str_replace(array('.',','), '', $statistics_code[1][$index]);
				$index++;
			}
			// Turma
			for ($i = 0; $i < count($statistics_order_names['turma']); $i++) {
				$satistic_name = str_replace(array('(', ')'), '', str_replace(' ', '_', strtolower($statistics_order_names['turma'][$i])));
				$player['statistics']['turma'][$satistic_name] = str_replace(array('.',','), '', $statistics_code[1][$index]);
				$index++;
			}
			// Quests
			for ($i = 0; $i < count($statistics_order_names['quests']); $i++) {
				$satistic_name = str_replace(array('(', ')'), '', str_replace(' ', '_', strtolower($statistics_order_names['quests'][$i])));
				$player['statistics']['quests'][$satistic_name] = str_replace(array('.',','), '', $statistics_code[1][$index]);
				$index++;
			}
			// Victories
			for ($i = 0; $i < count($statistics_order_names['victories']); $i++) {
				$satistic_name = str_replace(array('(', ')'), '', str_replace(' ', '_', strtolower($statistics_order_names['victories'][$i])));
				$player['statistics']['victories'][$satistic_name] = str_replace(array('.',','), '', $statistics_code[1][$index]);
				$index++;
			}

			// Return the object
			return $player;
		}

		// Request player's achievements page data
		function request_getPlayerAchievementsData($country, $server, $id) {
			// Get achievements page code
			$html = @file_get_contents(
				'https://s'.$server.'-'.$country.'.gladiatus.gameforge.com/game/index.php?mod=player&submod=achievements&p='.$id,
				false,
				stream_context_create(array(
					'http' => array('method' => 'GET')
				))
			);
			// On request error
			if (!$html) {
				// Return page not found error
				return array(
					'error' => true,
					'message' => 'Our monkeys-workers failed to complete the task.'
				);
			}
			// Shrink string by deleting useless data
			$html = substr($html, 50000, -2000);

			// Create player object
			$player = array(
				'id' => $id,
				'achievements' => array(),
				'game' => array(
					'country' => $country,
					'server' => $server
				)
			);

			// Get player's achievements
			$found = preg_match_all('/<div class="achievement_detail_current">\\s*(\\d+\\.*\\d*\\.*\\d*)\\s*\\/\\s*(\\d+\\.*\\d*\\.*\\d*)\\s*<\\/div>/', $html, $achievements_code);
			if (!$found) {return array('error' => true,'message' => 'Internal parse error.');}
			// Counting index
			$index = 0;

			$achievements_order_names = array(
				'general' => array(
					'Earn gold',
					'Train strength',
					'Train dexterity',
					'Train mobility',
					'Train constitution',
					'Train Charisma',
					'Train intelligence',
					'Collect honour',
					'Collect honour (Provinciarum)',
					'Get fame',
					'Get fame (Provinciarum)',
					'Go to work'
				),
				'items' => array(
					'Find items',
					'Find blue items',
					'Find purple items',
					'Find orange items'
				),
				'social' => array(
					'Increase Circle of Buddies',
					'Look at profiles',
					'Be the centre of attention'
				),
				'guild' => array(
					'Donate gold',
					'Store items',
					'Have yourself healed',
					'Pray in the temple',
					'Fight with the guild',
					'Win guild battles',
					'Store recipes',
					'Activate recipes',
					'Catch dungeon bosses',
					'Defeat dungeon bosses'
				),
				'trade' => array(
					'Sell items',
					'Buy items',
					'Sell market items',
					'Buy market items',
					'Win auctions'
				),
				'arena' => array(
					'Win in the arena',
					'Win in the Arena (Provinciarum)',
					'Deal out damage',
					'Accept damage',
					'Absorb damage',
					'Win in succession',
					'Win arena pot',
					'Kill gladiators',
					'Die in the arena',
					'Win naked'
				),
				'turma' => array(
					'Win in Circus Turma',
					'Defeat in Circus Turma (Provinciarum)',
					'Deal out damage',
					'Accept damage',
					'Absorb damage',
					'Win in succession',
					'Win Circus Turma Pot'
				),
				'dungeons' => array(
					'Dungeon successfully completed'
				)
			);
			
			// General
			for ($i = 0; $i < count($achievements_order_names['general']); $i++) {
				$achiv_name = str_replace(array('(', ')'), '', str_replace(' ', '_', strtolower($achievements_order_names['general'][$i])));
				if ($achievements_code[1][$index] != $achievements_code[2][$index])
					$player['achievements']['general'][$achiv_name] = str_replace(array('.',','), '', $achievements_code[1][$index]);
				$index++;
			}
			// Items
			for ($i = 0; $i < count($achievements_order_names['items']); $i++) {
				$achiv_name = str_replace(array('(', ')'), '', str_replace(' ', '_', strtolower($achievements_order_names['items'][$i])));
				if ($achievements_code[1][$index] != $achievements_code[2][$index])
					$player['achievements']['items'][$achiv_name] = str_replace(array('.',','), '', $achievements_code[1][$index]);
				$index++;
			}
			// Social
			for ($i = 0; $i < count($achievements_order_names['social']); $i++) {
				$achiv_name = str_replace(array('(', ')'), '', str_replace(' ', '_', strtolower($achievements_order_names['social'][$i])));
				if ($achievements_code[1][$index] != $achievements_code[2][$index])
					$player['achievements']['social'][$achiv_name] = str_replace(array('.',','), '', $achievements_code[1][$index]);
				$index++;
			}
			// Guild
			for ($i = 0; $i < count($achievements_order_names['guild']); $i++) {
				$achiv_name = str_replace(array('(', ')'), '', str_replace(' ', '_', strtolower($achievements_order_names['guild'][$i])));
				if ($achievements_code[1][$index] != $achievements_code[2][$index])
					$player['achievements']['guild'][$achiv_name] = str_replace(array('.',','), '', $achievements_code[1][$index]);
				$index++;
			}
			// Trade
			for ($i = 0; $i < count($achievements_order_names['trade']); $i++) {
				$achiv_name = str_replace(array('(', ')'), '', str_replace(' ', '_', strtolower($achievements_order_names['trade'][$i])));
				if ($achievements_code[1][$index] != $achievements_code[2][$index])
					$player['achievements']['trade'][$achiv_name] = str_replace(array('.',','), '', $achievements_code[1][$index]);
				$index++;
			}
			// Arena
			for ($i = 0; $i < count($achievements_order_names['arena']); $i++) {
				$achiv_name = str_replace(array('(', ')'), '', str_replace(' ', '_', strtolower($achievements_order_names['arena'][$i])));
				if ($achievements_code[1][$index] != $achievements_code[2][$index])
					$player['achievements']['arena'][$achiv_name] = str_replace(array('.',','), '', $achievements_code[1][$index]);
				$index++;
			}
			// Turma
			for ($i = 0; $i < count($achievements_order_names['turma']); $i++) {
				$achiv_name = str_replace(array('(', ')'), '', str_replace(' ', '_', strtolower($achievements_order_names['turma'][$i])));
				if ($achievements_code[1][$index] != $achievements_code[2][$index])
					$player['achievements']['turma'][$achiv_name] = str_replace(array('.',','), '', $achievements_code[1][$index]);
				$index++;
			}
			// Dungeons
			for ($i = 0; $i < count($achievements_order_names['dungeons']); $i++) {
				$achiv_name = str_replace(array('(', ')'), '', str_replace(' ', '_', strtolower($achievements_order_names['dungeons'][$i])));
				if ($achievements_code[1][$index] != $achievements_code[2][$index])
					$player['achievements']['dungeons'][$achiv_name] = str_replace(array('.',','), '', $achievements_code[1][$index]);
				$index++;
			}

			// Return the object
			return $player;
		}


	/*
		Turma Roles Translations
	*/
		function request_turma_resolve_role($roles, $html) {
			$hasRoles = array(
				'isRoleTank' => false,
				'isRoleDps' => false,
				'isRoleHealer' => false,
				'isRoleOut' => false,
				'isRoleUnknown' => false
			);

			$html = json_decode(htmlspecialchars_decode($html));
			$html = $html[0][0][0];
			$found = preg_match('/<font style=font-size:smaller;color:#DDDDDD>([^<]+)<\/font>/', $html, $role_translation);
			if (!$found) {
				$hasRoles['isRoleUnknown'] = true;
				return $hasRoles;
			}
			$role_translation = $role_translation[1];

			switch($role_translation){
				case $roles['tank']:
					$hasRoles['isRoleTank'] = true;
					break;
				case $roles['dps']:
					$hasRoles['isRoleDps'] = true;
					break;
				case $roles['healer']:
					$hasRoles['isRoleHealer'] = true;
					break;
				case $roles['out']:
					$hasRoles['isRoleOut'] = true;
					break;
				default:
					$hasRoles['isRoleUnknown'] = true;
					break;
			}

			return $hasRoles;
		}

		function request_turma_roles_translation($country) {
			/*
				Javascript code to crawl high scores to get translations for turma/dungeon roles

				var players_rows = jQuery('#highscore_table table tr.alt');
				var index_row = 0;
				var tran_found = 0;
				var tran_data = {};
				var tran_to_find = 3;
				function check_row(){
					if(index_row >= players_rows.length || tran_found>=tran_to_find){
						console.log('Done');
						var str = "\n\n";
						for(var i in tran_data){
							str += i + "\n";
						}
						str += "\n\n";
						console.log(str);
						return;
					}
					var index = index_row;
					console.log(index);
					index_row++;
					jQuery.get(
						players_rows[index].getElementsByTagName('a')[0].href,
						function(data, status){
							var titles = data.match(/color:#DDDDDD>([^<]+)<\/font><\/td><\/tr><\/table>'\)/ig);
							for(var i=0; i<titles.length; i++){
								var text = titles[i].match(/color:#DDDDDD>([^<]+)<\/font><\/td><\/tr><\/table>'\)/i)[1];
								if(!tran_data[text]){
									tran_data[text] = true;
									tran_found++;
								}
							};
							check_row();
						}
					);
				}
				check_row();
			*/
			// color:#DDDDDD>Αποστολή Θεραπεύστε τα μέλη της ομάδας σας</font></td></tr></table>')
			switch ($country) {
				// 31 servers
				case 'ar':
					/*
						Misión: Ocupate de vos mismo
						Misión: Distribuí el daño
						Misión: Curá a los miembros de tu grupo
						Misión: No te lleves
					*/
					return array("tank"=>"Misión: Ocupate de vos mismo", "dps"=>"Misión: Distribuí el daño", "healer"=>"Misión: Curá a los miembros de tu grupo", "out"=>"Misión: No te lleves");
					break;
				case 'ba':
					/*
						Zadatak: Usmjeri pozornost prema sebi.
						Zadatak: Isperi štetu
						Zadatak: Izlječi grupu
						Zadatak: Nemoj uzeti sa
					*/
					return array("tank"=>"Zadatak: Usmjeri pozornost prema sebi.", "dps"=>"Zadatak: Isperi štetu", "healer"=>"Zadatak: Izlječi grupu", "out"=>"Zadatak: Nemoj uzeti sa");
					break;
				case 'br':
					/*
						Missão: Atenção direta a si
						Missão: Prato de danos
						Missão: Curar membros do grupos
						Missão: Não tome com
					*/
					return array("tank"=>"Missão: Atenção direta a si", "dps"=>"Missão: Prato de danos", "healer"=>"Missão: Curar membros do grupos", "out"=>"Missão: Não tome com");
					break;
				case 'dk':
					/*
						Opgave: Rette opmærksomheden mod én selv
						Opgave: Uddel skade
						Opgave: Heal gruppemedlemmer
						Opgave: Tag ikke med
					*/
					return array("tank"=>"Opgave: Rette opmærksomheden mod én selv", "dps"=>"Opgave: Uddel skade", "healer"=>"Opgave: Heal gruppemedlemmer", "out"=>"Opgave: Tag ikke med");
					break;
				case 'de':
					return array("tank"=>"Aufgabe: Aufmerksamkeit auf sich ziehen", "dps"=>"Aufgabe: Schaden austeilen", "healer"=>"Aufgabe: Gruppenmitglieder heilen", "out"=>"Aufgabe: Nicht mitnehmen");
					break;
				case 'ee':
					/*
						Retk: Otsene tähelepanu endale
						Retk: Ründesse
						Retk: Ravi grupi liikmeid
						Retk: Ära võta ühes
					*/
					return array("tank"=>"Retk: Otsene tähelepanu endale", "dps"=>"Retk: Ründesse", "healer"=>"Retk: Ravi grupi liikmeid", "out"=>"Retk: Ära võta ühes");
					break;
				case 'es':
					/*
						Misión: Llamar la atención
						Misión: Reparte el daño
						Misión: Curar miembros del grupo
						Misión: Sin utilizar
					*/
					return array("tank"=>"Misión: Llamar la atención", "dps"=>"Misión: Reparte el daño", "healer"=>"Misión: Curar miembros del grupo", "out"=>"Misión: Sin utilizar");
					break;
				case 'fr':
					/*
						Mission : Attirer l`attention sur soi
						Mission : Infliger des dégâts
						Mission : Soigner les membres du groupe
						Mission : Ne pas prendre avec soi
					*/
					return array("tank"=>"Mission : Attirer l`attention sur soi", "dps"=>"Mission : Infliger des dégâts", "healer"=>"Mission : Soigner les membres du groupe", "out"=>"Mission : Ne pas prendre avec soi");
					break;
				case 'it':
					/*
						Incarico: Attrae l`attenzione su di sé
						Incarico: Distribuisce i danni
						Incarico: Guarisce i membri del gruppo
						Incarico: Disattivato
					*/
					return array("tank"=>"Incarico: Attrae l`attenzione su di sé", "dps"=>"Incarico: Distribuisce i danni", "healer"=>"Incarico: Guarisce i membri del gruppo", "out"=>"Incarico: Disattivato");
					break;
				case 'lv':
					/*
						Uzdevums: Pievērs uzmanību pats sev
						Uzdevums: Izpostīt bojājumu
						Uzdevums: Dziedināt grupas biedrus
						Uzdevums: Neņem ar
					*/
					return array("tank"=>"Uzdevums: Pievērs uzmanību pats sev", "dps"=>"Uzdevums: Izpostīt bojājumu", "healer"=>"Uzdevums: Dziedināt grupas biedrus", "out"=>"Uzdevums: Neņem ar");
					break;
				case 'lt':
					/*
						Užduotis: Tiesioginis dėmesys į save
						Užduotis: Dalinti žalą
						Užduotis: Pagydyti grupės narius
						Užduotis: Neimti su
					*/
					return array("tank"=>"Užduotis: Tiesioginis dėmesys į save", "dps"=>"Užduotis: Dalinti žalą", "healer"=>"Užduotis: Pagydyti grupės narius", "out"=>"Užduotis: Neimti su");
					break;
				case 'hu':
					/*
						Feladat: Ellenfél figyelmét magára vonja
						Feladat: Ellenfél támadása
						Feladat: Csapat tagjainak gyógyítása
						Feladat: Tétlen
					*/
					return array("tank"=>"Feladat: Ellenfél figyelmét magára vonja", "dps"=>"Feladat: Ellenfél támadása", "healer"=>"Feladat: Csapat tagjainak gyógyítása", "out"=>"Feladat: Tétlen");
					break;
				case 'mx':
					/*
						Tarea: Atención directa a uno mismo
						Tarea: Reparta el daño
						Tarea: Cura a los miembros del grupo
						Tarea: No tomes
					*/
					return array("tank"=>"Tarea: Atención directa a uno mismo", "dps"=>"Tarea: Reparta el daño", "healer"=>"Tarea: Cura a los miembros del grupo", "out"=>"Tarea: No tomes");
					break;
				case 'nl':
					/*
						Quest: Richt de aandacht op jezelf
						Quest: Schade verdelen
						Quest: Groepsleden genezen
						Quest: Niet meenemen
					*/
					return array("tank"=>"Quest: Richt de aandacht op jezelf", "dps"=>"Quest: Schade verdelen", "healer"=>"Quest: Groepsleden genezen", "out"=>"Quest: Niet meenemen");
					break;
				case 'no':
					/*
						Ekspedisjon: Diriger oppmerksomhet til seg selv
						Ekspedisjon: Server ut skade
						Ekspedisjon: Helbred gruppe medlemmer
						Ekspedisjon: Ikke ta med
					*/
					return array("tank"=>"Ekspedisjon: Diriger oppmerksomhet til seg selv", "dps"=>"Ekspedisjon: Server ut skade", "healer"=>"Ekspedisjon: Helbred gruppe medlemmer", "out"=>"Ekspedisjon: Ikke ta med");
					break;
				case 'pl':
					/*
						Zadanie: Prowokuj przeciwnika
						Zadanie: Atakuj
						Zadanie: Uzdrawiaj członków grupy
						Zadanie: Usuń z grupy
					*/
					return array("tank"=>"Zadanie: Prowokuj przeciwnika", "dps"=>"Zadanie: Atakuj", "healer"=>"Zadanie: Uzdrawiaj członków grupy", "out"=>"Zadanie: Usuń z grupy");
					break;
				case 'pt':
					/*
						Missão: Chama a atenção para si mesmo
						Missão: Reparte o dano
						Missão: Cura os membros do grupo
						Missão: Não tome com
					*/
					return array("tank"=>"Missão: Chama a atenção para si mesmo", "dps"=>"Missão: Reparte o dano", "healer"=>"Missão: Cura os membros do grupo", "out"=>"Missão: Não tome com");
					break;
				case 'ro':
					/*
						Cercetare: Atentie directa de sine
						Cercetare: Distribuie daunele
						Cercetare: Vindeca membrii grupului
						Cercetare: Fara a folosi
					*/
					return array("tank"=>"Cercetare: Atentie directa de sine", "dps"=>"Cercetare: Distribuie daunele", "healer"=>"Cercetare: Vindeca membrii grupului", "out"=>"Cercetare: Fara a folosi");
					break;
				case 'sk':
					/*
						Úloha: Upútať pozornosť
						Úloha: Rozdávať zranenia
						Úloha: Liečiť
						Úloha: Nevziať
					*/
					return array("tank"=>"Úloha: Upútať pozornosť", "dps"=>"Úloha: Rozdávať zranenia", "healer"=>"Úloha: Liečiť", "out"=>"Úloha: Nevziať");
					break;
				case 'fi':
					/*
						Tehtävä: Kerää huomion itseensä
						Tehtävä: Tekee vauriota
						Tehtävä: Parantaa ryhmän jäseniä
						Tehtävä: Ei oteta mukaan
					*/
					return array("tank"=>"Tehtävä: Kerää huomion itseensä", "dps"=>"Tehtävä: Tekee vauriota", "healer"=>"Tehtävä: Parantaa ryhmän jäseniä", "out"=>"Tehtävä: Ei oteta mukaan");
					break;
				case 'se':
					/*
						Uppgift: Tar emot skada
						Uppgift: Dela ut skada
						Uppgift: Läker gruppmedlemmar
						Uppgift: Ta inte med
					*/
					return array("tank"=>"Uppgift: Tar emot skada", "dps"=>"Uppgift: Dela ut skada", "healer"=>"Uppgift: Läker gruppmedlemmar", "out"=>"Uppgift: Ta inte med");
					break;
				case 'tr':
					/*
						Görev: Dikkati kendi üzerine çekmek
						Görev: Hasarı paylaştır
						Görev: Grup üyelerini iyileştir
						Görev: Beraberinde alma
					*/
					return array("tank"=>"Görev: Dikkati kendi üzerine çekmek", "dps"=>"Görev: Hasarı paylaştır", "healer"=>"Görev: Grup üyelerini iyileştir", "out"=>"Görev: Beraberinde alma");
					break;
				case 'us':
					/*
						Task: Direct attention to oneself
						Task: Dish out damage
						Task: Heal group members
						Task: Do not take with
					*/
					return array("tank"=>"Task: Direct attention to oneself", "dps"=>"Task: Dish out damage", "healer"=>"Task: Heal group members", "out"=>"Task: Do not take with");
					break;
				case 'en':
					/*
						Quest: Direct attention to oneself
						Quest: Dish out damage
						Quest: Heal group members
						Quest: Do not take with
					*/
					return array("tank"=>"Quest: Direct attention to oneself", "dps"=>"Quest: Dish out damage", "healer"=>"Quest: Heal group members", "out"=>"Quest: Do not take with");
					break;
				case 'cz':
					/*
						Úkol: Přitáhni na sebe pozornost
						Úkol: Rozdávej rány
						Úkol: Uzdrav členy družiny
						Úkol: Nezahrávej si s
					*/
					return array("tank"=>"Úkol: Přitáhni na sebe pozornost", "dps"=>"Úkol: Rozdávej rány", "healer"=>"Úkol: Uzdrav členy družiny", "out"=>"Úkol: Nezahrávej si s");
					break;
				case 'gr':
					return array("tank"=>"Αποστολή Πρόσεχε τον εαυτό σου", "dps"=>"Αποστολή Προβλεπόμενη ζημιά", "healer"=>"Αποστολή Θεραπεύστε τα μέλη της ομάδας σας", "out"=>"Αποστολή Μη πάρεις μαζί");
					break;
				case 'bg':
					/*
						Куест: Насочи вниманието към себе си
						Куест: Прави поражение
						Куест: Лекувайте членове на групата
						Куест: Не вземай участие
					*/
					return array("tank"=>"Куест: Насочи вниманието към себе си", "dps"=>"Куест: Прави поражение", "healer"=>"Куест: Лекувайте членове на групата", "out"=>"Куест: Не вземай участие");
					break;
				case 'ru':
					/*
						Задание: Защищать
						Задание: Наносить урон
						Задание: Лечить членов группы
						Задание: Не использовать
					*/
					return array("tank"=>"Задание: Защищать", "dps"=>"Задание: Наносить урон", "healer"=>"Задание: Лечить членов группы", "out"=>"Задание: Не использовать");
					break;
				case 'il':
					/*
						משימה: תשומת לב על עצמו
						משימה: נזק במנות
						משימה: רפא חברי קבוצה
						משימה: אל תקח עם
					*/
					return array("tank"=>"משימה: תשומת לב על עצמו", "dps"=>"משימה: נזק במנות", "healer"=>"משימה: רפא חברי קבוצה", "out"=>"משימה: אל תקח עם");
					break;
				case 'ae':
					/*
						مهمة: إثارة الانتباه
						مهمة: توزيع الضرر
						مهمة: معالجة أعضاء المجموعة
						مهمة: لا تأخده معك
					*/
					return array("tank"=>"مهمة: إثارة الانتباه", "dps"=>"مهمة: توزيع الضرر", "healer"=>"مهمة: معالجة أعضاء المجموعة", "out"=>"مهمة: لا تأخده معك");
					break;
				case 'tw':
					/*
						任務: 吸引攻擊
						任務: 實施攻擊
						任務: 負責治癒
						任務: 不加入隊伍
					*/
					return array("tank"=>"任務: 吸引攻擊", "dps"=>"任務: 實施攻擊", "healer"=>"任務: 負責治癒", "out"=>"任務: 不加入隊伍");
					break;
				default:
					return array("tank"=>"[Error]", "dps"=>"[Error]", "healer"=>"[Error]", "out"=>"[Error]");
					break;
			}
		}

	/*
		Main Functions For getting player's data
	*/

		// Get general player
		function getPlayerData($player, $actions) {
			// Check if all data are provided
			if (!isset($player['country']) || !isset($player['server']) || (!isset($player['name']) and !isset($player['id']))) {
				// Return missing data error
				return array(
					'error' => true,
					'message' => 'Our monkeys-workers do not have enought bananas to complete the task.'
				);
			}
			// Check if data provided are valid
			if (!request_validate_country($player['country']))
				return array('error' => true, 'message' => 'Country is not valid.');
			if (!request_validate_server($player['server']))
				return array('error' => true, 'message' => 'Server number is not valid.');
			if (isset($player['name']) && !request_validate_name($player['name']))
				return array('error' => true, 'message' => 'Player\'s name is not valid.');
			if (isset($player['id']) && !request_validate_id($player['id']))
				return array('error' => true, 'message' => 'Player\'s id is not valid.');

			// Player Object variable
			$player_data = array();

			// If we do not have player's id
			if (!isset($player['id'])) {
				// Search player using his name
				$data = request_searchPlayerByName($player['country'], $player['server'], $player['name']);
				// On error return error message
				if (isset($data['error'])) {
					return $data;
				}
				// Overwrite old data
				$player_data = $data;
			
			} else {
				// Set some default data
				$player_data = array(
					'id' => $player['id'],
					'game' => array(
						'country' => $player['country'],
						'server' => $player['server']
					)
				);
			}
			
			// Profile Action
			if (isset($actions['profile']) && $actions['profile']) {
				// Get player's profile data
				$data = request_getPlayerProfileData($player['country'], $player['server'], $player_data['id']);
				// On error return error message
				if (isset($data['error'])) {
					return $data;
				}
				// Overwrite old data
				$player_data = $data;
			}

			// Statistics Action
			if (isset($actions['statistics']) && $actions['statistics']) {
				// Get player's profile data
				$data = request_getPlayerStatisticsData($player['country'], $player['server'], $player_data['id']);
				// On error return error message
				if (isset($data['error'])) {
					return $data;
				}
				// Overwrite old data
				$player_data['statistics'] = $data['statistics'];
			}


			// Statistics Action
			if (isset($actions['achievements']) && $actions['achievements']) {
				// Get player's profile data
				$data = request_getPlayerAchievementsData($player['country'], $player['server'], $player_data['id']);
				// On error return error message
				if (isset($data['error'])) {
					return $data;
				}
				// Overwrite old data
				$player_data['achievements'] = $data['achievements'];
			}
			
			// Turma Action
			if (isset($actions['turma']) && $actions['turma']) {
				// Get player's profile data
				$data = request_getPlayerTurmaData($player['country'], $player['server'], $player_data['id']);
				// On error return error message
				if (isset($data['error'])) {
					return $data;
				}
				// Overwrite old data
				$player_data['turma'] =  $data;
			}

			return $player_data;
		}
