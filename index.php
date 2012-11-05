<?php
session_start();

$dir = __DIR__."/";
include_once $dir."config.php";
include_once $dir."class/Database.class.php";

$auth = Database::getSetting('auth');

if ($auth)
{
	if (empty($_SESSION['TM']))
		require_once "pages/auth.php";
	
	if ( ! empty($_SESSION['TM']))
	{
		$hash_pass = Database::getSetting('password');
		if ($_SESSION['TM'] != $hash_pass)
			require_once "pages/auth.php";
		else
			require_once "pages/main.php";
	}
}
if ( ! $auth)
	require_once "pages/main.php";
?>