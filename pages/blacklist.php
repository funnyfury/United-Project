<?php
$amount_by_page = 25;
$page_title = "Список плохих игроков";
$page_description = "Список игроков занесёных в чёрный список базы данных Novostroi";

include Base::PathTPL("header");
include Base::PathTPL("left_side");

$page = (!isset($lnk[1]) or $lnk[1] <= 0)? 1: (int) $lnk[1];
$first = ($page - 1) * $amount_by_page;
$query = $db->execute("SELECT * FROM `groups`");
while ($gr = $db->fetch_array($query))
	$groups[$gr['txtid']] = $gr['name'];
$query = $db->execute("SELECT *  FROM `blacklist` LEFT JOIN `user_info_cache` ON `user_info_cache`.`steamid`=`blacklist`.`steam_id` LIMIT $first, $amount_by_page") or die($db->error());
Base::TakeTPL("blacklist/players_head");
for($c = $first + 1; $typical_ple = $db->fetch_array($query); $c++) {
	include Base::PathTPL("blacklist/player_row");
}
Base::TakeTPL("blacklist/players_foot");
$query = $db->execute("SELECT COUNT(*) FROM `blacklist`");
$query = $db->fetch_array($query);
echo Base::GeneratePagination($page, $amount_by_page, $query[0], "/players/");

include Base::PathTPL("right_side");

include Base::PathTPL("footer");