<?php
if (!defined('SMF'))
        die('Hacking attempt...');

function Render()
{
	global $txt, $scripturl, $modSettings, $user_info, $context, $smcFunc;
	$context['director_include'] = 'APIChecker/api.php';
	loadTemplate('Director');
}
?>

