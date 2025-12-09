<?php
session_start();
header("Content-Type: application/json");
include "db_connection.php";

$mentee_id  = $_SESSION['user_id'] ?? 0;
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

if (!$mentee_id || !$session_id) {
    echo json_encode([]);
    exit;
}

// نجيب جلسة المينتي الحالية
$sql = $conn->prepare("
    SELECT mentor_id, date, time
    FROM sessions
    WHERE id = ?
      AND mentee_id = ?
      AND status = 'booked'
    LIMIT 1
");
$sql->bind_param("ii", $session_id, $mentee_id);
$sql->execute();
$res = $sql->get_result();
$current = $res->fetch_assoc();

if (!$current) {
    echo json_encode([]);
    exit;
}

$mentor_id = (int)$current['mentor_id'];
$cur_date  = $current['date'];
$cur_time  = $current['time'];

// نجيب السلوطات المتاحة فقط
$q = $conn->prepare("
    SELECT id, date, time
    FROM sessions
    WHERE mentor_id = ?
      AND status = 'available'
      AND (
            date > CURDATE()
         OR (date = CURDATE() AND time > CURTIME())
      )
      AND NOT (date = ? AND time = ?)
    ORDER BY date ASC, time ASC
");

$q->bind_param("iss", $mentor_id, $cur_date, $cur_time);
$q->execute();
$r = $q->get_result();

$slots = [];
while ($row = $r->fetch_assoc()) {
    $slots[] = [
        'id'   => (int)$row['id'],
        'date' => $row['date'],
        'time' => $row['time']
    ];
}

echo json_encode($slots);
?>
