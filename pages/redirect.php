<?php
if (!$logged_user or !$logged_user->take_group_info("phpmyadmin")) 
	include ROOT . "pages/403.php";
	else 
		header("Location: https://spl26.hosting.reg.ru:8443");
exit();