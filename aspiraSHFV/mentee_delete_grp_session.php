<?php
session_start();
include 'db_connection.php';

// Basic validation
if (!isset($_GET['id'])) {
    die("Error: session ID not provided.");
}

$session_id = intval($_GET['id']);
$mentee_id = $_SESSION['user_id'] ?? 0;

if (!$mentee_id) {
    die("Error: Not logged in.");
}

// Delete only this mentee’s participation
$query = "DELETE FROM grp_session_participants 
          WHERE group_session_id = ? AND mentee_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $session_id, $mentee_id);

if ($stmt->execute()) {
    header("Location: menteeUpcomingSession.php?view=public&deleted=1");
    exit;
} else {
    echo "Error deleting session participation: " . $stmt->error;
}
?>

