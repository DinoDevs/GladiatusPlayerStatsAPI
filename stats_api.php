<?php
/*
 * Gladiatus Battle Simulator by Gladiatus Crazy Team
 * https://www.facebook.com/GladiatusCrazyAddOn
 * Authors : GramThanos, GreatApo
 *
 * Gladiatus Player Stats API
 */

// Load request player data library
require_once('request_playerData.php');

// Check if all parameters are given
if (
	!isset($_REQUEST['country']) || 
	!isset($_REQUEST['server']) || 
	!(isset($_REQUEST['name']) || isset($_REQUEST['id']))
) {
	$output = array(
		'error' => true,
		'message' => 'At least one parameter is missing.'
	);

}

// All parameters were given
else {

	// Player info
	$player = array(
		'country' => $_REQUEST['country'],
		'server' => $_REQUEST['server'],
		'name' => NULL,
		'id' => NULL
	);
	
	// Get id or name
	if(isset($_REQUEST['id'])){
		$player['id'] = $_REQUEST['id'];
	}
	else if(isset($_REQUEST['name'])){
		$player['name'] = $_REQUEST['name'];
	}

	// Check options
	$options = array();

	// Profile
	$options['profile'] = isset($_REQUEST['profile']);
	// Statistics
	$options['statistics'] = isset($_REQUEST['statistics']);
	// Achievements
	$options['achievements'] = isset($_REQUEST['achievements']);
	// Turma
	$options['turma'] = isset($_REQUEST['turma']);

	// If nothing specific was requested, return the profile
	if (!$options['profile'] && !$options['statistics'] && !$options['achievements'] && !$options['turma']) {
		$options['profile'] = true;
	}

	// Get results from lib
	$output = getPlayerData($player, $options);

}

echo json_encode($output);
