<?php
session_start();
include 'db_connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/PHPMailer-master/src/Exception.php'; // تأكد من أن PHPMailer مثبت
require 'PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/PHPMailer-master/src/SMTP.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$mentor_id = $_SESSION['user_id'];
$session_id = intval($_GET['id']);

// جلب بيانات الجلسة
$query = "SELECT s.*, u.email 
          FROM sessions s
          JOIN mentees m ON s.mentee_id = m.user_id
          JOIN users u ON m.user_id = u.user_id
          WHERE s.id = ? AND s.mentor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $session_id, $mentor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Session not found");
}

$session = $result->fetch_assoc();
$session_datetime = strtotime($session['date'] . ' ' . $session['time']);
$time_diff = $session_datetime - time();

if ($time_diff <= 3600) {
    die("You cannot delete a session less than 1 hour before it starts.");
}

// حذف الجلسة
$delete = $conn->prepare("DELETE FROM sessions WHERE id = ?");
$delete->bind_param("i", $session_id);
$delete->execute();

// إرسال إيميل عبر PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; // استبدله بمخدم بريدك
    $mail->SMTPAuth   = true;
    $mail->Username   = 'gp.finally@gmail.com'; 
    $mail->Password   = 'rucf vidv sbut zeaj';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('gp.finally@gmail.com', 'ASPIRA');
    $mail->addAddress($session['email']);

    $mail->isHTML(true);
    $mail->Subject = 'Your Session Has Been Canceled';
    $mail->Body    = "
        <h2>Session Canceled</h2>
        <p>Dear Mentee,</p>
        <p>Your session scheduled on <strong>{$session['date']}</strong> at <strong>{$session['time']}</strong> has been <span style='color:red;'>canceled</span> by the mentor.</p>
        <p>We apologize for any inconvenience.</p>
        <p>Best regards,<br>ASPIRA Team</p>
    <p><br><br>Visit our website: <a href='http://localhost/aspiraSHFV/homepage.html'>ASPIRA</a></p>";

    $mail->send();
} catch (Exception $e) {
    error_log("Mailer Error: {$mail->ErrorInfo}");
}

header("Location:MentorUpcomingSession.php?msg=deleted");
exit;
?>