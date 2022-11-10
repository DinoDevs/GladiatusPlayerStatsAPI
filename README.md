# GladiatusPlayerStatsAPI
A Gladiatus player's page stats parser

## About

This library was developed for the Gladiatus Simulator web application. It was released so that other developers can create their own web applications related to the Gladiatus game.

## Usage

### Default stats_api.php
You may use the default `stats_api.php` and serve the data from your web server.

Parameters:
- country *(required)*
    - The country code as used on the Gladiatus' server URL (example: `gr`)
- server *(required)*
    - The server number as used on the Gladiatus' server URL (example: `4`)
- id *(required this or the `name`)*
    - The player id as used on his profile URL (example: `298498`)
    - This parameter may not be used if the `name` parameter is used, but it is preferred.
- name *(required this or the `id`)*
    - The player name as seen on his profile page (example: `DarkThanos`)
    - This parameter may not be used if the `id` parameter is used, the `id` parameter is preferred.
- profile *(optional, default)*
	- Get the player's profile page data
- statistics *(optional)*
	- Get the player's stats page data
- achievements *(optional)*
	- Get the player's achievements page data
- turma *(optional)*
	- Get the player's turma/dungeon team data

Example Queries:
```
http://yourserver.example/stats_api.php?country=gr&server=4&name=DarkThanos&profile
http://yourserver.example/stats_api.php?country=gr&server=4&name=DarkThanos&statistics
http://yourserver.example/stats_api.php?country=gr&server=4&name=DarkThanos&achievements
http://yourserver.example/stats_api.php?country=gr&server=4&id=298498&profile&statistics
```

Example Query - Response

`http://yourserver.example/stats_api.php?country=gr&server=4&id=298498`
```json
{
   "id":"298498",
   "name":"DarkThanos",
   "profile":{
      "level":"125",
      "life":[
         "10412",
         "14237"
      ],
      "experience":[
         "57581",
         "61535"
      ],
      "strength":"422",
      "skill":"1020",
      "agility":"1055",
      "constitution":"429",
      "charisma":"950",
      "intelligence":"1055",
      "armor":"18956",
      "damage":[
         "538",
         "626"
      ],
      "healing":"958",
      "avoid-critical-points":"266",
      "block-points":"674",
      "critical-points":"534",
      "critical-healing":"227",
      "avoid-critical-percent":"25",
      "block-percent":"50",
      "critical-percent":"57",
      "critical-healing-percent":"13",
      "buffs":{
         "minerva":false,
         "mars":false,
         "apollo":false,
         "honour_veteran":true,
         "honour_destroyer":false
      }
   },
   "game":{
      "country":"gr",
      "server":"4"
   },
   "img":"https:\/\/s4-gr.gladiatus.gameforge.com\/game\/9314\/img\/costumes\/sets\/male\/7_complete.png",
   "guild":{
      "id":"19676",
      "name":"Dream_Team",
      "tag":"ENOMENOI"
   }
}
```

Using the library directly from a console:
```bash
php ./stats_cli.php country=gr server=4 name=DarkThanos profile
```

### PHP Library Include

You may include the `request_playerData.php` file on your PHP code and call the library as you like.

Example Code:
```php
// Load request player data library
require_once('request_playerData.php');

// Get data
$player = getPlayerData(
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

// Print data in json format
echo json_encode($player);
```

## Caching

By default if there is a `request_playerData_cache` folder next to the `request_playerData.php` file, and the PHP has read/write access to this folder, the library will use that folder for caching. This cache links player names to ids so that the library can later avoid researching for the player id when a name is given. This cache should be cleared manually.

