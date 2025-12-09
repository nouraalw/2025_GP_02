<?php
session_start();
include 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Error: Not logged in"]);
    exit;
}

$mentor_id = $_SESSION['user_id']; 


$query = "SELECT s.id, s.date, s.time, s.room_id, u.first_name, u.last_name, m.interests
          FROM sessions s
          LEFT JOIN mentees m ON s.mentee_id = m.user_id
          LEFT JOIN users u ON m.user_id = u.user_id
          WHERE s.mentor_id = ? AND s.status = 'booked'";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(["error" => "Error setting up query: " . $conn->error]);
    exit;
}

$stmt->bind_param('i', $mentor_id);
$stmt->execute();
$result = $stmt->get_result();

$sessions = [];
while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
}

$stmt->close();
$conn->close();

//  تصحيح مشكلة النتيجة الفارغة
if (empty($sessions)) {
    echo json_encode(["message" => "No current sessions"]);
} else {
    echo json_encode($sessions);
}
?>
