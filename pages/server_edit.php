<?php
$page_title = "Редактировать сервер";
$page_description = "Редактируйте свой сервер в базе данных Novostroi";

include Base::PathTPL("header");
include Base::PathTPL("left_side");

$query = $db->execute("SELECT * FROM `servers_info` WHERE `server_id`='{$db->safe($lnk[1])}'");

$query = $db->fetch_array($query);

if (!$logged_user or ($logged_user->steamid() != $query['owner'] and !$logged_user->take_group_info("admin_panel"))) {
	include ROOT . "pages/403.php";
	exit();
}
$alert = '';
$owner = (isset($_POST['owner']) and $logged_user->take_group_info("admin_panel")) ? $_POST['owner'] : $query['owner'];
$name = $query['name'];
$ip = $query['ip'];
$port = $query['port'];
$on1 = $query['rem_rights'];
$api_key = $query['api_key'];

if (isset($_POST['name']) and isset($_POST['ip']) and isset($_POST['port'])) {
	$on = (isset($_POST['on']))? 0:1;
	$onpart = (isset($_POST['onpart']))? 1:0;
	$name = $_POST['name'];
	$ip = $_POST['ip'];
	$port = $_POST['port'];

	$url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$_STEAMAPI&steamids=" . Base::ToCommunityID($query['owner']);
	$json_object = file_get_contents($url);
	if (!$json_object or !strlen($json_object)) die ("Error");
	$json_decoded = json_decode($json_object);
	$is = false;
	$key = (!isset($_POST['api_key']) or !strlen($_POST['api_key']))? Base::randString(127):$key;
	foreach ($json_decoded->response->players as $player) {
		$is = true;
		$db->execute("INSERT INTO `user_info_cache` (`steamid`, `steam_url`, `avatar_url`, `nickname`) VALUES ('" . $db->safe(Base::ToSteamID($player->steamid)) . "', '" . $db->safe($player->profileurl) . "', '" . $db->safe($player->avatarfull) . "', '" . $db->safe($player->personaname) . "')"
			. "ON DUPLICATE KEY UPDATE `steam_url`='" . $db->safe($player->profileurl) . "', `avatar_url`='" . $db->safe($player->avatarfull) . "', `nickname`='" . $db->safe($player->personaname) . "'") or die($db->error());
		$db->execute("UPDATE `servers_info` SET `name`='{$db->safe($name)}', `ip`='{$db->safe($ip)}', `port`='{$db->safe($port)}', `api_key`='{$db->safe($api_key)}, `status`='{$db->safe($onpart)}',"
			. " `rem_rights`='{$db->safe($on)}', `owner`='{$db->safe($owner)}' WHERE `server_id`='{$db->safe($lnk[1])}'");
		Logger::Log(28, 0, '', '', $logged_user->steamid(),$_POST['name']);
	}
	$alert = ($is) ? 'Готово ;)' : 'Ниа, такого стим айди нет :(';
};
include Base::PathTPL("servers/admin/server_edit");

include Base::PathTPL("right_side");
include Base::PathTPL("footer");