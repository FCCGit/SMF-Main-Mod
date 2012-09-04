<?php
if (!defined('SMF'))
        die('Hacking attempt...');

function Render()
{
	global $context;
	
	if($_GET['area'] == 'slacker'){
		$context['director_include'] = 'SlackerTracker/slacker.php';
	} else if($_GET['area'] == 'apiCheck'){
		$context['director_include'] = 'APIChecker/api.php';
	} else {
		redirectexit('');
	}
	
	loadTemplate('Director');
}
?>

