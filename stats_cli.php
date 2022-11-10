<?php
/*
 * Gladiatus Battle Simulator by Gladiatus Crazy Team
 * https://www.facebook.com/GladiatusCrazyAddOn
 * Authors : GramThanos, GreatApo
 *
 * Gladiatus Player Stats CLI
 */

parse_str(implode('&', array_slice($argv, 1)), $_REQUEST);

// Load stats API
require('stats_api.php');
