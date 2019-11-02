<?php
$page_title = "Добавить сервер";
$page_description = "Добавить свой сервер в базу данных Novostroi";
include Base::PathTPL("header");
include Base::PathTPL("left_side");
$alert = '';
$owner = (isset($_POST['owner']) and $logged_user->take_group_info("admin_panel")) ? $_POST['owner'] : $logged_user->steamid();
if (isset($_POST['server_id']) and isset($_POST['name']) and isset($_POST['ip']) and isset($_POST['port'])) {
	$active = (isset($_POST['active']))? 1:0;
	$url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$_STEAMAPI&steamids=" . Base::ToCommunityID($owner);
	$json_object = file_get_contents($url);
	if (!$json_object or !strlen($json_object)) die ("Error");
	$json_decoded = json_decode($json_object);
	$is = false;
	foreach ($json_decoded->response->players as $player) {
		$is = true;
		$db->execute("INSERT INTO `user_info_cache` (`steamid`, `steam_url`, `avatar_url`, `nickname`) VALUES ('" . $db->safe(BASE::ToSteamID($player->steamid)) . "', '" . $db->safe($player->profileurl) . "', '" . $db->safe($player->avatarfull) . "', '" . $db->safe($player->personaname) . "')"
			. "ON DUPLICATE KEY UPDATE `steam_url`='" . $db->safe($player->profileurl) . "', `avatar_url`='" . $db->safe($player->avatarfull) . "', `nickname`='" . $db->safe($player->personaname) . "'") or die($db->error());
		$db->execute("INSERT INTO `servers_info` (`server_id`, `name`, `owner`, `ip`, `port`, `active`, `api_key`, `rem_rights`)"
			. "VALUES ('{$db->safe($_POST['server_id'])}','{$db->safe($_POST['name'])}','{$db->safe(Base::ToSteamID($player->steamid))}','{$db->safe($_POST['ip'])}','{$db->safe((int)$_POST['port'])}','{$db->safe($active)}','{$db->safe(Base::randString(127))}', '1')");
	}
	$alert = ($is) ? 'Успешно! Вам осталось ждать одобрение сервера, если вы будете спамить запросами, будете забанены!' : 'Ниа, такого стим айди нет :(';
}
include Base::PathTPL("servers/admin/server_add");
include Base::PathTPL("right_side");
include Base::PathTPL("footer");