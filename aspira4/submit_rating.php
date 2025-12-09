<?php
include 'db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Error: Invalid request!");
}

//  التحقق من القيم المستلمة
if (!isset($_POST['session_id'], $_POST['mentor_id'], $_POST['rating'])) {
    die("Error: Incomplete data!");
}

$session_id = intval($_POST['session_id']);
$mentor_id = intval($_POST['mentor_id']);
$mentee_id = $_SESSION['user_id']; 
$rating = intval($_POST['rating']);
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : null;

//  التحقق من صحة التقييم
if ($rating < 1 || $rating > 5) {
    die("Error: Rating must be between 1 and 5!");
}


$log_entry = "session: $session_id, mentor: $mentor_id, mentee: $mentee_id, rate: $rating, comment: $comment\n";
file_put_contents("debug_log.txt", $log_entry, FILE_APPEND);

//  حفظ التقييم والتعليق أو تحديثهما إذ  موجودين
$sql = "INSERT INTO ratings (session_id, mentor_id, mentee_id, rating, comment) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            rating = VALUES(rating),
            comment = VALUES(comment)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiis", $session_id, $mentor_id, $mentee_id, $rating, $comment);

if ($stmt->execute()) {
    echo "Your rating and comment have been saved successfully!";
} else {
    echo "Error while saving: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
