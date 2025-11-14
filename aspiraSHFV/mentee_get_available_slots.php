<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
include 'db_connection.php';

$mentee_id  = $_SESSION['user_id'] ?? 0;
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

if (!$mentee_id || !$session_id) {
    echo json_encode([]);
    exit;
}

date_default_timezone_set('Asia/Riyadh');
$today = date('Y-m-d');
$now   = date('H:i:s');

/* 1) جيب جلسة المنتي الحالية للتحقق وجلب mentor_id + is_public + وقتها */
$sqlCur = $conn->prepare("
    SELECT s.id, s.mentor_id, s.is_public, s.date, s.time
    FROM sessions s
    WHERE s.id = ? AND s.mentee_id = ? AND s.status = 'booked'
    LIMIT 1
");
$sqlCur->bind_param("ii", $session_id, $mentee_id);
$sqlCur->execute();
$curRes = $sqlCur->get_result();
$cur    = $curRes->fetch_assoc();

if (!$cur) { echo json_encode([]); exit; }

$mentor_id   = (int)$cur['mentor_id'];
$is_public   = (int)$cur['is_public'];
$cur_date    = $cur['date'];
$cur_time    = $cur['time'];

/*
  2) رجّع فقط السلوْت المتاحة (status='available') لنفس المنتور ونفس النوع،
     مستقبلية فقط، واستبعد نفس وقت الجلسة الحالية.
*/
$sql = $conn->prepare("
    SELECT id, date, time
    FROM sessions
    WHERE mentor_id = ?
      AND is_public = ?
      AND status = 'available'
      AND (
            date > ?
         OR (date = ? AND time > ?)
      )
      AND NOT (date = ? AND time = ?)
    ORDER BY date ASC, time ASC
");
$sql->bind_param("iisssss", $mentor_id, $is_public, $today, $today, $now, $cur_date, $cur_time);
$sql->execute();
$res = $sql->get_result();

$slots = [];
while ($row = $res->fetch_assoc()) {
    $slots[] = [
        'id'   => (int)$row['id'],
        'date' => $row['date'],
        'time' => $row['time']
    ];
}

echo json_encode($slots);
