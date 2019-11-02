<?php
$page_title = "API";

switch ($lnk[1]) {
	case '':
		include Base::PathTPL("api/api_head");
		$query = $db->execute("SELECT * FROM `servers_info` WHERE `deleted` = 0 $where ORDER BY  `servers_info`.`players` DESC") or die($db->error());
		$servers = array();
		while($server = $db->fetch_array($query)) {
			$online = ($server['status']);
			$servers[] = array('server'=>$server,'players'=>$players);
			include Base::PathTPL("api/api");
		}
		Base::TakeTPL("api/api_foot");
		break;
	case 'metadmin_info':
		$query = $db->execute("SELECT * FROM `data`");
		$query or die($db->error());
		if ($db->num_rows($query)) {
			$query = $db->fetch_array($query);
			header('Content-Type: application/json');
			exit($query['value']);
		}
		break;
	case 'bad':
		exit();
		break;
	case 'icon':
		$query = $db->execute("SELECT * FROM `players` WHERE `SID`='{$db->safe($lnk[2])}'");
		$query or die($db->error());
		if ($db->num_rows($query)) {
			$query = $db->fetch_array($query);
			header('Content-Type: application/json');
			exit($query['icon']);
		}
		break;
	case 'mag_bans':
		$query = $db->execute("SELECT * FROM `mag_bans` WHERE `mag_bans`.`mag_unban_date` > NOW() OR `mag_bans`.`mag_unban_date` IS NULL ORDER BY `mag_bans`.`mag_unban_date` DESC");
		$query or die($db->error());
		$bans = array();
		while ($ban = $db->fetch_array($query)) {
			array_push($bans, array('steamid'=>$ban['mag_steam_id'],'reason'=>$ban['mag_reason'],'unban_date'=>strtotime($ban['mag_unban_date']),));
		}
		header('Content-Type: application/json');
		exit(json_encode($bans));
		break;
	case 'key_check':
		$query = $db->execute("SELECT * FROM `servers` WHERE `ip`='" . Base::GetRealIp() . "' AND `port`='{$db->safe($_POST['port'])}'");
		$query or die($db->error());
		if ($db->num_rows($query)) {
			$query = $db->fetch_array($query);
			if ($_POST['hash'] != hash("sha256", $_POST['port'] . $_POST['date']. $query['key']))
				exit('bad hash');
			exit("ok");
		} else exit('bad ip or port');
		break;
	case 'report':
		if ($db->num_rows($query)) {
			$db->execute("INSERT INTO `mag_reports` (`mag_rserver`,`mag_reason`,`mag_badpl`,`mag_rdate`) VALUES ('{$db->safe($_POST['server'])}', '{$db->safe($_POST['reason'])}', '{$db->safe($_POST['target'])}', NOW())");
			exit("ok");
		} else exit('bad ip or port');
		break;
	case 'violation':
		if ($db->num_rows($query)) {
			$query = $db->fetch_array($query);
			$pl = new User($_POST['author'], 'SID');
			if ($pl->uid() < 1 or !$pl->take_group_info("warn"))
				exit("access denied");
			$db->execute("INSERT INTO `violations` (`server`,`violation`,`admin`,`SID`,`date`) VALUES ('{$db->safe($_POST['server'])}', '{$db->safe($_POST['reason'])}', '{$db->safe($_POST['author'])}', '{$db->safe($_POST['target'])}', '" . time() . "')");
			exit("ok");
		} else exit('bad ip or port');
		break;
	case 'set_coupon':
		if ($db->num_rows($query)) {
			$pl = new User($_POST['author'], 'SID');
			if ($pl->uid() < 1 or !$pl->take_group_info("give_coupon"))
				exit("access denied");
			if ($_POST['number'] < 1 or $_POST['number'] >3)
				exit("bad number");
			$status = json_encode(
				array(
					'admin' => $db->safe($_POST['author']),
					'nom' => (int) $db->safe($_POST['number']),
					'date' => $db->safe(time()),
				)
			);
			$db->execute("UPDATE `players` SET `status`='{$db->safe($status)}' WHERE `SID`='{$db->safe($_POST['target'])}'");
			exit("ok");
		} else exit('bad ip or port');
		break;
	case 'setrank':
		if ($db->num_rows($query)) {
			$query1 = $db->execute("SELECT `txtid` FROM `groups` WHERE NOT `txtid`='ple' ORDER BY `id`");
			$groups = array();
			while ($group = $db->fetch_array($query1)) {
				array_push($groups, $group['txtid']);
			}
			if (!in_array($_POST['group'], $groups))
				exit("bad group");
			$pl = new User($_POST['author'], 'SID');
			if ($pl->uid() < 1 or !($pl->take_group_info("up_down") or $pl->take_group_info("change_group")))
				exit("access denied");
			$db->execute("UPDATE `players` SET `group`='{$db->safe($_POST['group'])}' WHERE `SID`='{$db->safe($_POST['target'])}'");
			$db->execute("INSERT INTO `examinfo` (`SID`, `date`, `rank`, `examiner`, `note`, `type`, `server`)"
				. "VALUES ('{$db->safe($_POST['target'])}','" . time() . "','{$db->safe($_POST['group'])}','{$db->safe($_POST['author'])}','{$db->safe($_POST['reason'])}','{$db->safe($_POST['type'])}','{$db->safe($_POST['server'])}')");
			exit("ok");
		} else exit('bad ip or port');
		break;
	case 'servers_info':
		$query = $db->execute("SELECT * FROM `servers_info`");
		while($sv = $db->fetch_array($query)) {
			$sv_array = array(
				'id' => $sv['server_id'],
				'name' => $sv['name'],
				'online' => $sv['players'],
				'map' => $sv['map']
			);
			$json = json_encode($sv_array);
			echo $json;
		};
		break;
	case 'user':
		if (!isset($lnk[2])) {
			include ROOT . "pages/404.php";
			exit;
		}
		$pl = new User($lnk[2], 'SID');
		if ($pl->uid() < 1) {
			$url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$_STEAMAPI&steamids=" . Base::ToCommunityID($lnk[2]);
			$json_object = file_get_contents($url);
			$json_decoded = json_decode($json_object);
			foreach ($json_decoded->response->players as $player) {
				/*echo "
                    <br/>Player ID: $player->steamid
                    <br/>Player Name: $player->personaname
                    <br/>Profile URL: $player->profileurl
                    <br/>SmallAvatar: <img src='$player->avatar'/>
                    <br/>MediumAvatar: <img src='$player->avatarmedium'/>
                    <br/>LargeAvatar: <img src='$player->avatarfull'/>
                    ";*/
				$status = json_encode(
					array(
						'admin'=>'',
						'nom'=>1,
						'date'=>time()
					)
				);
				$db->execute("INSERT INTO `players` (`SID`, `group`, `status`) VALUES ('" . $db->safe(Base::ToSteamID($player->steamid)) . "', 'user', '$status')");
				$db->execute("INSERT INTO `user_info_cache` (`steamid`, `steam_url`, `avatar_url`, `nickname`) VALUES ('" . $db->safe(Base::ToSteamID($player->steamid)) . "', '" . $db->safe($player->profileurl) . "', '" . $db->safe($player->avatarfull) . "', '" . $db->safe($player->personaname) . "')"
					. "ON DUPLICATE KEY UPDATE `steam_url`='" . $db->safe($player->profileurl) . "', `avatar_url`='" . $db->safe($player->avatarfull) . "', `nickname`='" . $db->safe($player->personaname) . "'") or die($db->error());
			}
			$pl = new User($lnk[2], 'SID');
			if ($pl->uid() < 1) {
				include ROOT . "pages/404.php";
				exit;
			}
		}
		$pl_warns = $db->execute("SELECT * FROM `violations` LEFT JOIN `user_info_cache` ON `violations`.`admin`=`user_info_cache`.`steamid` WHERE `SID`='{$pl->steamid()}' ORDER BY `violations`.`date` DESC") or die ($db->error());
		$pl_exams = $db->execute("SELECT * FROM `groups`, `examinfo` LEFT JOIN `user_info_cache` ON `examiner`=`user_info_cache`.`steamid` WHERE `examinfo`.`rank`=`groups`.`txtid` AND `SID`='{$pl->steamid()}' ORDER BY `examinfo`.`date` DESC") or die ($db->error());
		$pl_tests = $db->execute("SELECT * FROM `tests_results` LEFT JOIN `user_info_cache` ON `reviewer`=`user_info_cache`.`steamid` WHERE `student`='{$pl->steamid()}' ORDER BY `tests_results`.`completed_date` DESC") or die ($db->error());
		$pl_tests_array = array();
		while ($pl_test = $db->fetch_array($pl_tests)) {
			$pl_test_array = array(
				'date' => $pl_test['completed_date'],
				'name' => $pl_test['trname'],
				'site' => "true",
				'ssadmin' => $pl_test['nickname'],
				'status' => $pl_test['status']
			);
			array_push($pl_tests_array, $pl_test_array);
		}
		$pl_warns_array = array();
		while ($pl_warn = $db->fetch_array($pl_warns)) {
			$pl_warn_array = array(
				'date' => $pl_warn['date'],
				'admin' => $pl_warn['nickname'],
				'server' => $pl_warn['server'],
				'violation' => $pl_warn['violation']
			);
			array_push($pl_warns_array, $pl_warn_array);
		}
		header('Content-Type: application/json');
		$pl_exams_array = array();
		while ($pl_exam = $db->fetch_array($pl_exams)) {
			$pl_exam_array = array(
				'date' => $pl_exam['date'],
				'examiner' => $pl_exam['nickname'],
				'rank' => $pl_exam['rank'],
				'server' => $pl_exam['server'],
				'type' => $pl_exam['type'],
				'note' => $pl_exam['note']
			);
			array_push($pl_exams_array, $pl_exam_array);
		}
		$pl_rights = array();
		foreach(Base::$RIGHTS as $RIGHT)
			if ($pl->take_group_info($RIGHT) AND $RIGHT != 'txtid' AND $RIGHT != 'name')
				array_push($pl_rights, $RIGHT);
		$pl_array = array(
			'SID' => $pl->steamid(),
			'count_mag_reports' => $pl->count_mag_reports(),
			'exam' => $pl_exams_array,
			'icon' => (int) $pl->max_icon_id(),
			'icons' => $pl->icons(),
			'mag_banned' => array(
				'reason' => $pl->take_mag_info('mag_reason'),
				'date' => ($pl->take_mag_info('mag_date') != null)? strtotime($pl->take_mag_info('mag_date')): null,
			),
			'nick' => $pl->take_steam_info('nickname'),
			'rank' => $pl->take_group_info('txtid'),
			'rank_name' => $pl->take_group_info('name'),
			'rights' => $pl_rights,
			'status' => array(
				'nom' => $pl->take_coupon_info('nom'),
				'admin' => $pl->take_coupon_info('admin'),
				'date' => (string) $pl->take_coupon_info('date'),
			),
			'tests_site' => $pl_tests_array,
			'violations' => $pl_warns_array,
		);
		exit(json_encode($pl_array));
	break;
	case 'server_info':
		$query = $db->execute("SELECT * FROM `servers_info` WHERE `ip`='" . Base::GetRealIp() . "'");
		$query or die($db->error());
		if ($db->num_rows($query)) {
			$query = $db->fetch_array($query);
			$db->execute("UPDATE `servers_info` SET `ip` = '{$db->safe($_POST['ip'])}', `players` = '{$db->safe($_POST['players'])}',`maxplayers` = '{$db->safe($_POST['maxplayers'])}', `map` = '{$db->safe($_POST['map'])}' WHERE `server_id` = '{$db->safe($_POST['id'])}'");
			exit("ok");
		} else exit('bad ip or port');
	break;
	case 'search':
		$pls_array = array();
		$query = $db->execute("SELECT `id` FROM `players` LEFT JOIN `user_info_cache` ON `SID`=`steamid` WHERE `SID`='{$db->safe($lnk[2])}' OR `nickname` LIKE '%{$db->safe($lnk[2])}%'");
		header('Content-Type: application/json');
		while ($plid = $db->fetch_array($query)) {
			$pl = new User($plid['id'], 'players`.`id');
			if ($pl->uid() < 1) continue;
			$pl_warns = $db->execute("SELECT * FROM `violations` LEFT JOIN `user_info_cache` ON `violations`.`admin`=`user_info_cache`.`steamid` WHERE `SID`='{$pl->steamid()}' ORDER BY `violations`.`date` DESC") or die ($db->error());
			$pl_exams = $db->execute("SELECT * FROM `groups`, `examinfo` LEFT JOIN `user_info_cache` ON `examiner`=`user_info_cache`.`steamid` WHERE `examinfo`.`rank`=`groups`.`txtid` AND `SID`='{$pl->steamid()}' ORDER BY `examinfo`.`date` DESC") or die ($db->error());
			$pl_warns_array = array();
			while ($pl_warn = $db->fetch_array($pl_warns)) {
				$pl_warn_array = array(
					'date' => $pl_warn['date'],
					'admin' => $pl_warn['nickname'],
					'server' => $pl_warn['server'],
					'violation' => $pl_warn['violation']
				);
				array_push($pl_warns_array, $pl_warn_array);
			}
			$pl_rights = array();
			foreach(Base::$RIGHTS as $RIGHT)
				if ($pl->take_group_info($RIGHT) AND $RIGHT != 'txtid' AND $RIGHT != 'name')
					array_push($pl_rights, $RIGHT);
			$pl_exams_array = array();
			while ($pl_exam = $db->fetch_array($pl_exams)) {
				$pl_exam_array = array(
					'date' => $pl_exam['date'],
					'examiner' => $pl_exam['nickname'],
					'rank' => $pl_exam['rank'],
					'server' => $pl_exam['server'],
					'type' => $pl_exam['type'],
					'note' => $pl_exam['note']
				);
				array_push($pl_exams_array, $pl_exam_array);
			}
			$pl_array = array(
				'nick' => $pl->take_steam_info('nickname'),
				'rank' => $pl->take_group_info('txtid'),
				'rank_name' => $pl->take_group_info('name'),
				'steamid' => $pl->steamid(),
				'badpl' => '',
				'mag_banned' => array(
					'reason' => $pl->take_mag_info('mag_reason'),
					'date' => ($pl->take_mag_info('mag_date') != null) ? strtotime($pl->take_mag_info('mag_date')) : null,
				),
				'status' => array(
					'nom' => $pl->take_coupon_info('nom'),
					'admin' => $pl->take_coupon_info('admin'),
					'date' => (string)$pl->take_coupon_info('date'),
				),
				'violations' => $pl_warns_array,
				'exam' => $pl_exams_array,
				'icon' => (int)$pl->max_icon_id(),
				'icons' => $pl->icons(),
				'rights' => $pl_rights, //Хелл, твою мать, на большее не рассчитывай
			);
			array_push($pls_array, $pl_array);
		}
		exit(json_encode($pls_array));
		break;
	case 'groups':
		$query = $db->execute("SELECT * FROM `groups`");
		$query or die($db->error());
		$groups_array = array();
		while ($group = $db->fetch_array($query)) {
			$group_array = array(
				$group['txtid'] => $group['name']
			);
			array_push($groups_array, $group_array);
		}
		header('Content-Type: application/json');
		exit(json_encode($groups_array));
		break;
	case 'icons':
		header('Content-Type: application/json');
		exit(json_encode(Base::$ICONS));
		break;
	case 'icon_view':
		include Base::PathTPL('api_icon');
		exit;
		break;
	default:
}