<?php
	ini_set('html_errors', 0);
	ini_set('display_errors', 0);
	ini_set('log_errors', 1);
	ini_set('error_log', 'error_log.log');

	define('ROOT', dirname(__FILE__).'/');

	require ("config.php");
	$_STEAMAPI = $config['steam_api_key'];
	require ("classes/base.class.php");

	define('LANGUAGES_PATH', ROOT . '/langs'); 
	putenv("LC_ALL=" . $locale); 
	setlocale(LC_ALL, $locale, $locale); 
	bind_textdomain_codeset($locale, 'UTF-8'); 
	bindtextdomain($locale, LANGUAGES_PATH); 
	textdomain($locale);

	if (isset($_GET['page'])) $lnk = explode('/', $_GET['page']);

	Base::DetectTimeZone();
	Base::TakeClass('db');
	Base::TakeClass('user');
	Base::TakeClass('menu');
	Base::TakeClass('log');

	$main_page = 'home';
	$show_login = true;

	$db = new DB($config['db_base'],$config['db_host'],$config['db_user'], $config['db_pass'], $config['db_port']);
	$db->connect();

	$query = $db->execute("SELECT * FROM `groups`");
	while ($gr = $db->fetch_array($query))
		$groups[$gr['txtid']] = $gr['name'];

	Base::TakeAuth();

	$menu = new Menu();
	$mode = (isset($lnk[0]))? $lnk[0]: $main_page;
	
	header('Content-Type: text/html; charset=utf-8');
	include (file_exists(ROOT . "pages/$mode.php"))? (ROOT . "pages/$mode.php"): (ROOT . "pages/404.php");
