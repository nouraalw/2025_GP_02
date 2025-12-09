<?php

include 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo " You must be logged in!";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['session_id'])) {
    echo "Error: Invalid request!";
    exit();
}

$mentee_id = $_SESSION['user_id'];
$session_id = intval($_POST['session_id']);


$sql = "SELECT mentor_id, date, time, room_id FROM sessions WHERE id = ? AND status = 'available'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $session_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "❌ This session is no longer available!";
    exit();
}

$session = $result->fetch_assoc();
$selected_date = $session['date'];
$selected_time = $session['time'];
$selected_mentor = $session['mentor_id'];
$room_id = $session['room_id'];
$stmt->close();

//  إنشاء `room_id` إذا لم يكن موجودًا
if (empty($room_id)) {
    $room_id = uniqid('room_'); // توليد `room_id` جديد
}

// ✅ تحديث الجلسة في قاعدة البيانات
$sql = "UPDATE sessions SET mentee_id = ?, status = 'booked', room_id = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isi", $mentee_id, $room_id, $session_id);

if ($stmt->execute()) {
    
    session_write_close();
    
    echo "✅ The session has been successfully booked! Room ID: " . htmlspecialchars($room_id);
} else {
    echo "❌ Session not booked!";
}


$stmt->close();
$conn->close();
?>
