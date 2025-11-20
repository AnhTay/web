<?php
require $_SERVER['DOCUMENT_ROOT'] . '/Core.php';
require $_SERVER['DOCUMENT_ROOT'] . '/lib/Pusher/Pusher.php';
$kun = new System;
$user = $kun->user();

// Pusher RealTime Noti By Kunkey
$options = array(
	'encrypted' => true
);
$pusher = new Pusher(
	'10d5ea7e7b632db09c72',
	'a496a6f084ba9c65fffb',
	'234217',
	$options
);


$myfile = fopen("log_card/log.txt", "a");
$txt = $_GET['status'] . "|" . $_GET['card_code'] . "|" . number_format($_GET['value_receive']) . "|" . $_GET['requestid'] . "\n";
fwrite($myfile, $txt);
fclose($myfile);

if ($_GET['status']) {

	$code = $_GET['status'];
	$tranid = $_GET['requestid'];
	$card_code = $_GET['card_code'];
	$card_seri = $_GET['card_seri'];
	$value_receive = $_GET['value_receive'];

	$get = mysqli_fetch_assoc(mysqli_query($kun->connect_db(), "SELECT * FROM `napthe` WHERE `pin` = '" . $card_code . "' AND `serial` = '" . $card_seri . "' "));

	$money = $get['amount'];
	$status_card = $get['status'];

	if($status_card == 2){
		if ($code == 200) {
			// update tiền
			mysqli_query($kun->connect_db(), "UPDATE `users` SET `money` = `money` + '" . $money . "' WHERE `username` = '" . $get['username'] . "' ");
			// update tiền nạp
			mysqli_query($kun->connect_db(), "UPDATE `users` SET `money_nap` = `money_nap` + '" . $money . "' WHERE `username` = '" . $get['username'] . "' ");
			// update trạng thái thẻ thành công
			mysqli_query($kun->connect_db(), "UPDATE `napthe` SET `status` = '1' WHERE `pin` = '" . $card_code . "' AND `serial` = '" . $card_seri . "'");
			// update lịch sử giao dịch
			mysqli_query($kun->connect_db(), "UPDATE `users` SET `diemtichluy` = `diemtichluy` + '5' WHERE `uid` = '" . $get['username'] . "' ");
			// Update vào lịch sử user
			mysqli_query($kun->connect_db(), "INSERT INTO `user_history_system` (`username`, `action`, `action_name`, `sotien`, `mota`, `time`) VALUES ('" . $get['username'] . "', 'Nạp Thẻ', 'Nạp Thẻ Tự Động', '+" . number_format($money) . "đ', 'Nạp Thẻ " . $get['type'] . " Thành Công!', '" . time() . "')");
	
			// Pusher
			$data['type'] = 'success';
			$data['title'] = 'Nạp Thẻ Thành Công!';
			$data['message'] = 'Nạp Thẻ ' . $get['type'] . ' Mệnh Giá ' . number_format($money) . 'đ Thành Công!';
			$pusher->trigger($get['username'], 'realtime', $data);
		} else if ($code == 100) {
			// update trạng thái thẻ thất bại
			mysqli_query($kun->connect_db(), "UPDATE `napthe` SET `status` = '0' WHERE `tranid`='" . $tranid . "'");
			// Pusher
			$data['type'] = 'error';
			$data['title'] = 'Nạp Thẻ Thất Bại!';
			$data['message'] = 'Nạp Thẻ ' . $get['type'] . ' Mệnh Giá ' . number_format($money) . 'đ Thất Bại!';
			$pusher->trigger($get['username'], 'realtime', $data);
		} else if ($code == 201) {
			// update trạng thái thẻ sai mệnh giá
			mysqli_query($kun->connect_db(), "UPDATE `napthe` SET `status` = '0' WHERE `tranid`='" . $tranid . "'");
			// Pusher
			$data['type'] = 'error';
			$data['title'] = 'Nạp Thẻ Thất Bại!';
			$data['message'] = 'Nạp Thẻ ' . $get['type'] . ' Mệnh Giá ' . number_format($money) . 'đ Thất Bại!';
			$pusher->trigger($get['username'], 'realtime', $data);
		}
		echo json_encode(array(
			'status' => 200,
			'messagge' => 'Đã nhận callback'
		));
	}
	else{
		echo json_encode(array(
			'status' => 200,
			'messagge' => 'Không thể thay đổi trạng thái thẻ'
		));
	}		
}