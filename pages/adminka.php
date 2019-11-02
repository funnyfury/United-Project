<?php
if (!$logged_user or !$logged_user->take_group_info("admin_panel")) {
	include ROOT . "pages/403.php";
	exit();
}
if (isset($_COOKIE['confirm'])) {
	$page_title = "Панель управления";
	include Base::PathTPL("header");
	include Base::PathTPL("left_side");

	include Base::PathTPL("adminka/adminka");

	include Base::PathTPL("right_side");
	include Base::PathTPL("footer");
} else {
	header('Location: /confirm');
};