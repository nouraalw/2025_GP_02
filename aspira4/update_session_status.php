<?php
include 'db_connection.php';

if (!isset($_GET['roomID'])) {
    echo json_encode(["success" => false, "error" => "❌ No Room ID provided"]);
    exit();
}

$roomID = $_GET['roomID'];

if (strpos($roomID, "room_") === 0) {
    // Private
    $sql = "UPDATE sessions SET status = 'completed' WHERE room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $roomID);
} elseif (strpos($roomID, "group_") === 0) {
    // Group
    $groupId = (int) str_replace("group_", "", $roomID);
    $sql = "UPDATE grp_sessions SET status = 'completed' WHERE group_session_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $groupId);
}


if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Session marked as completed"]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}

$stmt->close();
$conn->close();
?>

