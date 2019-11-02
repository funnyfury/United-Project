<?php
$amount_by_page = 25;
$page_title = "Список игроков";
$page_description = "Список игроков базы данных Novostroi";
include Base::PathTPL("header");
include Base::PathTPL("left_side");

$query = $db->execute("SELECT *  FROM `players`");
$plycount = $db->num_rows($query);

$page = (!isset($lnk[1]) or $lnk[1] <= 0)? 1: (int) $lnk[1];
$first = ($page - 1) * $amount_by_page;
$query = $db->execute("SELECT *  FROM `groups`, `players` LEFT JOIN `user_info_cache` ON `user_info_cache`.`steamid`=`players`.`SID` WHERE `players`.`group`=`groups`.`txtid` ORDER BY `groups`.`id` ASC, `user_info_cache`.`nickname` ASC LIMIT $first, $amount_by_page") or die($db->error());
for($c = $first + 1; $player = $db->fetch_array($query); $c++) {
	include Base::PathTPL("players/players");
}
$querycount = $db->execute("SELECT COUNT(*) FROM `players`");
$query = $db->fetch_array($querycount);
echo Base::GeneratePagination($page, $amount_by_page, $query[0], "/players/");
include Base::PathTPL("right_side");

include Base::PathTPL("footer");