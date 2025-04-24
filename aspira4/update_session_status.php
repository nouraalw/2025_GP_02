<?php
include 'db_connection.php';

if (!isset($_GET['roomID'])) {
    echo json_encode(["success" => false, "error" => "Error: No Room ID!"]);
    exit();
}

$roomID = $_GET['roomID'];

//  تحديث حالة الجلسة إلى "completed" في قاعدة البيانات
$sql = "UPDATE sessions SET status = 'completed' WHERE room_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $roomID);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "update the session to 'completed'"]);
} else {
    echo json_encode(["success" => false, "error" => "error in update the session " . $conn->error]);
}

$stmt->close();
$conn->close();
?>
