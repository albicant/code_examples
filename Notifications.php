<?php
	require("../Core/constants.php");
	require_once($path_core . "/main.php");
	
	class Notifications extends Main {
		public function include_template($name = null, $nofooter = false, $noheader = false) {
			Main::err404();
		}
		public function notifications_likes($message_id, $type, $likedby_current_user) {
			$dba = array("message_id" => $message_id);
			if ($type == "post") {
				$author_id = $this->db->fetch('SELECT `author` FROM `{$prefix}_posts` WHERE `id` = :message_id', $dba);
			}
			else if ($type == "comment") {
			 	$author_id = $this->db->fetch('SELECT `author` FROM `{$prefix}_comments` WHERE `id` = :message_id', $dba);
			}
			if ($likedby_current_user == $author_id[0]['author']) {
				return;
			}
			if ($author_id[0]) {
				$dba = array("author_id" => $author_id[0]['author']);
				$prev_notic_db = $this->db->fetch('SELECT `notifications`, `notifications_counter` FROM `{$prefix}_users` WHERE `id` = :author_id', $dba);
				$notic_counter = $prev_notic_db[0]["notifications_counter"];
				$prev_notic = json_decode($prev_notic_db[0]["notifications"], true);
				if (!$prev_notic) {
					$prev_notic = array();
				}
				$notic = array(
					"type" => $type,
					"message_id" => $message_id,
					"likedby" => $likedby_current_user
				);
				$flag = true;
				foreach ($prev_notic as $key=>$value) {
					if ($value["type"] == $notic["type"] && $value["message_id"] == $notic["message_id"] && $value["likedby"] == $notic["likedby"]) {
						unset($prev_notic[$key]);
						$prev_notic = array_values($prev_notic);
						$notic_counter--;
						$flag = false;
						break;
					}
				}
				if ($flag) { 
					$notic_counter++;
					$notic["notific_id"] = $notic_counter;
					array_unshift($prev_notic, $notic);
				}
				$arr = json_encode($prev_notic, JSON_UNESCAPED_UNICODE);
				$dba["notic"] = $arr;
				$dba["counter"] = $notic_counter;
				$this->db->query('UPDATE `{$prefix}_users` SET `notifications` = :notic, `notifications_counter` = :counter WHERE `id` = :author_id', $dba);
			}
		}
		public function notifications_tasks($task_id, $time_start, $season, $type) {
			if ($type == "photo" || $type == "video") { 
				if ($time_start <= (time() - 43200))
					return;
			}
			else {
				if ($time_start <= (time() - 97200))
					return;
			}
			$arr = array();
			$new_task = array(
				"type" => "task",
				"task_id" => $task_id,
				"time" => $time_start
				);
			array_unshift($arr, $new_task);
			$task_compare = $arr;
			
			$all_users = $this->db->fetch('SELECT `id`, `payments` FROM `{$prefix}_users`');
			foreach($all_users as $key => $value) {
				$tmp_payment = json_decode($value['payments'], true);
				if ($tmp_payment[$season]) {
					$dba = array("user_id" => $value['id']);
					$notic_sch_prev = $this->db->fetch('SELECT `notifications_scheduled` FROM `{$prefix}_users` WHERE `id` = :user_id', $dba);
					$notic_sch_prev = json_decode($notic_sch_prev[0]['notifications_scheduled'], true);
					if (!$notic_sch_prev) {
						$notic_sch_prev = array();
					}
					foreach ($notic_sch_prev as $key => $value) {
						if ($value == $task_compare[0]) {
							return;
						}
						else if ($value['task_id'] == $task_compare[0]['task_id']) {
							unset($notic_sch_prev[$key]);
						}
					}
					array_unshift($notic_sch_prev, $task_compare[0]);
					$notic_sch_prev = array_values($notic_sch_prev);		
					$arr = json_encode($notic_sch_prev, JSON_UNESCAPED_UNICODE);
					$dba['notic'] = $arr;
					$this->db->query('UPDATE `{$prefix}_users` SET `notifications_scheduled` = :notic WHERE `id` = :user_id', $dba);
				}
			}
		}
		public function notifications_update($user_id) {
			$dba = array("id" => $user_id);
			$all_notic_db = $this->db->fetch('SELECT `notifications_scheduled`, `notifications`, `notifications_counter` FROM `{$prefix}_users` WHERE `id`=:id', $dba);
			$notic_sched = json_decode($all_notic_db[0]['notifications_scheduled'], true);
			$notic = json_decode($all_notic_db[0]['notifications'], true);
			$notic_counter = $all_notic_db[0]['notifications_counter'];
			$notic_check = $notic;
			if (!$notic) {
				$notic = array();
			}
			if ($notic_sched) {
				foreach ($notic_sched as $key => $value) {
					if ($value['time'] <= time()) {
						if ($value['type'] == "mute") {
							$value['time'] = "";
							foreach ($notic as $k => $v) {
								if ($value['type'] === "mute") {
									unset($notic[$k]);
									$notic = array_values($notic);
									$notic_counter--;
									break;
								}
							}
						}
						$notic_counter++;
						$notic_sched[$key]['notific_id'] = $notic_counter;
						array_unshift($notic, $value);
						unset($notic_sched[$key]);
						$notic_sched = array_values($notic_sched);
					}
					
				}
				if ($notic_check != $notic) {
					$notic = json_encode($notic, JSON_UNESCAPED_UNICODE);
					$notic_sched = json_encode($notic_sched, JSON_UNESCAPED_UNICODE);
					$dba['notic'] = $notic;
					$dba['notic_sched'] = $notic_sched;
					$dba['notic_counter'] = $notic_counter;
					$this->db->query('UPDATE `{$prefix}_users` SET `notifications`=:notic, `notifications_scheduled`=:notic_sched, `notifications_counter`=:notic_counter WHERE `id` = :id', $dba);
				}
			}
		}
		public function notifications_achievements($user_id, $achiev_id) {
			$dba = array("id" => $user_id);
			$prev_notic_db = $this->db->fetch('SELECT `notifications`, `notifications_counter` FROM `{$prefix}_users` WHERE `id` = :id', $dba);
			$notic_counter = $prev_notic_db[0]["notifications_counter"];
			$prev_notic = json_decode($prev_notic_db[0]["notifications"], true);
			if (!$prev_notic) {
				$prev_notic = array();
			}
			$notic_counter++;
			$notic = array(
				"type" => "achiev",
				"achiev_id" => $achiev_id,
				"notific_id" => $notic_counter
			);
			array_unshift($prev_notic, $notic);
			$arr = json_encode($prev_notic, JSON_UNESCAPED_UNICODE);
			$dba["notic"] = $arr;
			$dba["counter"] = $notic_counter;
			$this->db->query('UPDATE `{$prefix}_users` SET `notifications` = :notic, `notifications_counter` = :counter WHERE `id` = :id', $dba);
		}
		
		public function notifications_display($user_id) {
			$dba = array("user_id" => $user_id);
			$notifications = $this->db->fetch('SELECT `notifications`, `notifications_counter` FROM `{$prefix}_users` WHERE `id` = :user_id', $dba);
			if ($notifications[0]) {
				$notifications_list = json_decode($notifications[0]["notifications"], true);
				if ($notifications[0]['notifications_counter'] <= 0) {
					$notifications[0]['notifications_counter'] = "";
				}
				$arr = array("counter" => $notifications[0]['notifications_counter']);
				if (!$notifications_list) {
					return null;
				}
				foreach ($notifications_list as $key => $value) {
					$notic_sentence_id = array("notific_id" => $value["notific_id"]);
					if ($value["type"] == "post" || $value["type"] == "comment") {
						$dba = array("likedby" => $value['likedby']);
						$personal_information = $this->db->fetch('SELECT `name`, `surname` FROM `{$prefix}_users` WHERE `id` = :likedby', $dba);
						$name = $personal_information[0]['name'] . " " . $personal_information[0]['surname'];
						if ($value["type"] == "post") {
							$notic_sentence = "<a href='https://" . $_SERVER['HTTP_HOST'] . "/feed/post/" . $value["message_id"] . "'>Пользователю " . $name . " понравился Ваш пост</a>";
						}
						else if ($value["type"] == "comment") {
							$notic_sentence = "<a href='https://" . $_SERVER['HTTP_HOST'] . "/feed/comment/" . $value["message_id"] . "'>Пользователю " . $name . " понравился Ваш комментарий</a>";
						}
					}
					else if ($value["type"] == "achiev") {
						$dba = array("achiev_id" => $value['achiev_id']);
						$achiev_name = $this->db->fetch('SELECT `name` FROM `{$prefix}_achievements` WHERE `id` = :achiev_id', $dba)[0]['name'];
						$notic_sentence = "<a href='#'>Вы получили новое достижение: " . $achiev_name . "! Поздравляем!</a>";
					}
					else if ($value["type"] == "task") {
						$dba = array("task_id" => $value['task_id']);
						$task_name = $this->db->fetch('SELECT `id`, `name` FROM `{$prefix}_tasks` WHERE `id` = :task_id', $dba)[0];
						$notic_sentence = "<a href='/tasks/open/" . $task_name['id'] . "'>Вы получили новое задание: " . $task_name['name'] . "!</a>";
					}
					else if ($value["type"] == "report") {
						if ($value["status"] == "accepted") {
							$status = " принят!";
						}
						else if ($value["status"] == "rejected") {
							$status = " возвращен на доработку!";
						}
						$notic_sentence = "<a href='/reports'>Ваш отчёт по заданию " . $value["task_name"] . $status . '</a>';
					}
					else if ($value["type"] == "answer") {
						$dba = array("id" => $value["who_answered"]);
						$name_db = $this->db->fetch('SELECT `login`, `name`, `surname` FROM `{$prefix}_users` WHERE `id` = :id', $dba)[0];
						if (!$name_db['name']) {
							$name = $name_db['login'];
						}
						else {
							$name = $name_db['name'] . " " . $name_db['surname'];
						}
						$notic_sentence = "<a href='/feed/comment/" . $value['orig_com_id'] . "/" . $value['new_com_id'] . "'>Пользователь " . $name . " ответил на Ваш комментарий</a>";
					}
					else if ($value["type"] == "subscr" || $value['type'] == "unsubscr") {
						$dba = array("id" => $value['subject']);
						$user = $this->db->fetch('SELECT `id`, `sex`, `login`, `name`, `surname` FROM `{$prefix}_users` WHERE `id`=:id', $dba)[0];
						$notic_sentence = '<a href="/user/open/' . $user['id'] . '">Пользователь ' . ($user['name'] != "" ? $user['name'] . ' ' . $user['surname'] : $user['login']) . ' ' . ($value["type"] == "subscr" ? (($user['sex'] == "female" ? "подписалась" : "подписался") . ' на Ваши обновления') : (($user['sex'] == "female" ? "отписалась" : "отписался") . ' от Ваших обновлений')) . '!</a>';
					}
					else if ($value['type'] == "mute") {
						if ($value['time'] == "") {
							$notic_sentence = '<a href="#">Время Вашей блокировки подошло к концу, теперь Вы вновь можете оставлять сообщения на сайте!</a>';
						}
						else {
							$notic_sentence = '<a href="#">Администратор запретил Вам оставлять сообщения на сайте до ' . ($value['time'] == "1" ? 'скончания времен' : date("d.m.Y H:i:s", $value['time'])) . '. :-(</a>';
						}
					}
					array_unshift($notic_sentence_id, $notic_sentence);
					array_unshift($arr, $notic_sentence_id);
				}
			}
			return $arr;
		}
		public function notifications_reports($status, $report_id) {
			$dba = array("id" => $report_id);
			$report_db = $this->db->fetch('SELECT `task`, `person` FROM `{$prefix}_reports` WHERE `id` = :id', $dba);
			$task = $report_db[0]['task'];
			$user_id = $report_db[0]['person'];
			$dba['id'] = $user_id;
			$prev_notic_db = $this->db->fetch('SELECT `notifications`, `notifications_counter` FROM `{$prefix}_users` WHERE `id` = :id', $dba);
			$notic_counter = $prev_notic_db[0]["notifications_counter"];
			$prev_notic = json_decode($prev_notic_db[0]["notifications"], true);
			if (!$prev_notic) {
				$prev_notic = array();
			}
			$dba['id'] = $task;
			$task_name = $this->db->fetch('SELECT `name` FROM `{$prefix}_tasks` WHERE `id` = :id', $dba)[0]['name'];
			$notic_counter++;
			$notic = array(
				"type" => "report",
				"status" => $status,
				"task_name" => $task_name,
				"notific_id" => $notic_counter
			);
			array_unshift($prev_notic, $notic);
			$arr = json_encode($prev_notic, JSON_UNESCAPED_UNICODE);
			$dba["id"] = $user_id;
			$dba["notic"] = $arr;
			$dba["counter"] = $notic_counter;
			$this->db->query('UPDATE `{$prefix}_users` SET `notifications` = :notic, `notifications_counter` = :counter WHERE `id` = :id', $dba);
		}
		public function url_notification_delete() {
			if (isset($_SESSION['logged']) && isset($_POST['data_id'])) {
				foreach ($_POST as $p => $v) {
					$_POST[$p] = htmlentities($v, ENT_QUOTES);
				}
				$dba = array(
					"user_id" => $_SESSION['logged']
				);
				$notifications_db = $this->db->fetch('SELECT `notifications`, `notifications_counter` FROM `{$prefix}_users` WHERE `id`=:user_id', $dba);
				$notific_counter = $notifications_db[0]['notifications_counter'];
				$notific = json_decode($notifications_db[0]['notifications'], true);
				if ($notific) {
					foreach ($notific as $key => $value) {
						if ($value['notific_id'] == $_POST['data_id']) {
							unset($notific[$key]);
							$notific_counter--;
							if($notific_counter <= 0) {
								$notific_counter = "";
							}
							$notific = array_values($notific);
							foreach ($notific as $key => $value) {
								$i = $key + 1;
								$notific[$key]['notific_id'] = $i;
							}
							$notific = json_encode($notific, JSON_UNESCAPED_UNICODE);
							$dba['notific'] = $notific;
							$dba['notific_counter'] = $notific_counter;
							$this->db->query('UPDATE `{$prefix}_users` SET `notifications` = :notific, `notifications_counter` = :notific_counter WHERE `id` = :user_id', $dba);
							break;
						}
					}
				}
			}
			else {
				Main::err404();
			}
		}
		
		public function url_notifications_answers() {
			if (isset($_SESSION['logged']) && isset($_POST['post_id']) && isset($_POST['big_arr']) && isset($_POST['text'])) {
				$big_arr = json_decode($_POST['big_arr'], true);
				if (!$big_arr) {
					return;
				}
				foreach ($_POST as $p => $v) {
					$_POST[$p] = htmlentities($v, ENT_QUOTES);
				}
				$comments_list = $big_arr[$_POST['post_id']];
				$dba = array();
				$users_list = array();
				foreach ($comments_list AS $key => $value) {
					$dba['id'] = $value;
					$users_list[$key]['com_id'] = $dba['id'];
					$user_id = $this->db->fetch('SELECT `author` FROM `{$prefix}_comments` WHERE `id` = :id', $dba)[0]['author'];
					$users_list[$key]['user_id'] = $user_id;
				}
				$users_information = array();
				foreach ($users_list AS $key => $value) {
					$dba['id'] = $value['user_id'];
					$user_information = $this->db->fetch('SELECT `name`, `surname`, `login` FROM `{$prefix}_users` WHERE `id` = :id', $dba)[0];
					array_unshift($users_information, $user_information);
				}	
				foreach ($users_information AS $key => $value) {
					if ($value['name']) {
						$name = $value['name'] . " " . $value['surname'];
					}
					else {
						$name = $value['login'];
					}
					$users_inform[$key]['id'] = $users_list[$key]['user_id'];
					$users_inform[$key]['name'] = $name;
					$users_inform[$key]['com_id'] = $users_list[$key]['com_id'];
				}
				$dba2 = array();
				foreach ($users_inform AS $key => $value) {
					if (strpos($_POST['text'], $value['name']) === false) {
						unset($users_inform[$key]);
						$users_inform = array_values($users_inform);
					}
					else if ($_SESSION['logged'] != $users_inform[$key]['id']) {
						$dba2['id'] = $users_inform[$key]['id'];
						$prev_notific_db = $this->db->fetch('SELECT `notifications`, `notifications_counter` FROM `{$prefix}_users` WHERE `id` = :id', $dba2);
						$counter = $prev_notific_db[0]['notifications_counter'];
						$counter++;
						$prev_notific = json_decode($prev_notific_db[0]['notifications'], true);
						if (!$prev_notific) {
							$prev_notific = array();
						}
						$dba3 = array("id" => $_SESSION['logged']);
						$new_com_id = $this->db->fetch('SELECT `id` FROM `{$prefix}_comments` WHERE `author` = :id ORDER BY `date` DESC LIMIT 1', $dba3)[0]['id'];
						$notific = array(
							"type" => "answer",
							"who_answered" => $_SESSION['logged'],
							"new_com_id" => $new_com_id,
							"orig_com_id" =>  $users_inform[$key]['com_id']
						);
						array_unshift($prev_notific, $notific);
						$prev_notific = json_encode($prev_notific, JSON_UNESCAPED_UNICODE);
						$dba2['counter'] = $counter;
						$dba2['notific'] = $prev_notific;
						$this->db->query('UPDATE `{$prefix}_users` SET `notifications` = :notific, `notifications_counter` = :counter WHERE `id` = :id', $dba2);
					}
				}
			}
			else {
				Main::err404();
			}
		}
				
		public function notifications_mute($time, $object) {			
			$dba = array("id" => $object);
			$notifications = $this->db->fetch('SELECT `notifications`, `notifications_scheduled`, `notifications_counter` FROM `{$prefix}_users` WHERE `id`=:id', $dba);
			if (isset($notifications[0])) {
				$json = json_decode($notifications[0]['notifications'], true);
				if (!is_array($json)) {
					$json = array();
				}
				else {
					foreach ($json as $key => $value) {
						if ($value['type'] === "mute") {
							unset($json[$key]);
							$json = array_values($json);
							$dba["notifications_counter"] = -1;
							break;
						}
					}
				}
				array_unshift($json, array("type" => "mute", "time" => $time));
				
				$json_scheduled = json_decode($notifications[0]['notifications_scheduled'], true);
				if (!is_array($json_scheduled)) {
					$json_scheduled = array();
				}
				else {
					foreach ($json_scheduled as $key => $value) {
						if ($value['type'] === "mute") {
							unset($json_scheduled[$key]);
							$json_scheduled = array_values($json_scheduled);
							break;
						}
					}
				}
				if ($time != "" && $time != "1") {
					array_unshift($json_scheduled, array("type" => "mute", "time" => $time));
				}
				
				$dba["notifications"] = json_encode($json, JSON_UNESCAPED_UNICODE);
				$dba["notifications_scheduled"] = json_encode($json_scheduled, JSON_UNESCAPED_UNICODE);
				$dba["notifications_counter"] += $notifications[0]['notifications_counter'] + 1;
				$this->db->query('UPDATE `{$prefix}_users` SET `notifications`=:notifications, `notifications_counter`=:notifications_counter, `notifications_scheduled`=:notifications_scheduled WHERE `id`=:id', $dba);
			}
		}
		
		public function notifications_subscriptions($object, $action, $subject) {
			$dba = array("id" => $object);
			$notifications = $this->db->fetch('SELECT `notifications`, `notifications_counter` FROM `{$prefix}_users` WHERE `id`=:id', $dba);
			if (isset($notifications[0])) {
				$json = json_decode($notifications[0]['notifications'], true);
				if (!is_array($json)) {
					$json = array();
				}
				array_unshift($json, array("type" => $action, "subject" => $subject));
				$dba["notifications"] = json_encode($json, JSON_UNESCAPED_UNICODE);
				$dba["notifications_counter"] = $notifications[0]['notifications_counter'] + 1;
				$this->db->query('UPDATE `{$prefix}_users` SET `notifications`=:notifications, `notifications_counter`=:notifications_counter WHERE `id`=:id', $dba);
			}
		}
	}
?>
