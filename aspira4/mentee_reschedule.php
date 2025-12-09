<?php
session_start();
include 'db_connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/PHPMailer-master/src/SMTP.php';

$mentee_id = $_SESSION['user_id'] ?? 0;

// JS يرسل id – date – time
$session_id = $_POST['id'] ?? 0;
$new_date   = $_POST['date'] ?? '';
$new_time   = $_POST['time'] ?? '';

if (!$mentee_id || !$session_id || !$new_date || !$new_time) {
    exit("error");
}

// 🔹 نجيب الجلسة القديمة
$q = $conn->prepare("SELECT mentor_id FROM sessions WHERE id=? AND mentee_id=? LIMIT 1");
$q->bind_param("ii", $session_id, $mentee_id);
$q->execute();
$cur = $q->get_result()->fetch_assoc();

if (!$cur) exit("error");

$mentor_id = $cur['mentor_id'];

// 🔹 نجيب السلوْت الجديد (بالـ date/time)
$s = $conn->prepare("
    SELECT id 
    FROM sessions 
    WHERE mentor_id=? AND date=? AND time=? AND status='available'
    LIMIT 1
");
$s->bind_param("iss", $mentor_id, $new_date, $new_time);
$s->execute();
$slot = $s->get_result()->fetch_assoc();

if (!$slot) exit("error");

$new_slot_id = $slot['id'];

$conn->begin_transaction();

// 🔹 1) رجّع الجلسة القديمة available
$up1 = $conn->prepare("UPDATE sessions SET status='available', mentee_id=NULL, room_id=NULL WHERE id=?");
$up1->bind_param("i", $session_id);
$up1->execute();

// 🔹 2) احجز السلوْت الجديد للمينتي
$up2 = $conn->prepare("
    UPDATE sessions 
    SET status='booked', mentee_id=?, room_id=NULL 
    WHERE id=?
");
$up2->bind_param("ii", $mentee_id, $new_slot_id);
$up2->execute();

$conn->commit();

// 🔹 3) إرسال إيميل للمنتور
$q = "
    SELECT u.email, u.first_name
    FROM mentors m
    JOIN users u ON m.user_id = u.user_id
    WHERE m.user_id = ?
";
$stmtEmail = $conn->prepare($q);
$stmtEmail->bind_param("i", $mentor_id);
$stmtEmail->execute();
$resultEmail = $stmtEmail->get_result();

if ($rowEmail = $resultEmail->fetch_assoc()) {
    $email = $rowEmail['email'];
    $firstName = $rowEmail['first_name'];

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gp.finally@gmail.com';
        $mail->Password   = 'rucf vidv sbut zeaj';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('gp.finally@gmail.com', 'ASPIRA');
        $mail->addAddress($email, $firstName);

        $mail->isHTML(true);
        $mail->Subject = 'A Session Was Rescheduled';
        $mail->Body    = "
            <h2>Session Rescheduled</h2>
            <p>Dear $firstName,</p>
            <p>The mentee has rescheduled the session.</p>
            <p>New date: <strong>$new_date</strong><br>
               New time: <strong>$new_time</strong></p>
            <p>Best regards,<br>ASPIRA Team</p>
            <p><br><br>Visit our website: <a href='https://aspira.sbs/'>ASPIRA</a></p>"
        ;

        $mail->send();

    } catch (Exception $e) {
        error_log('Email Error: ' . $mail->ErrorInfo);
    }
}

echo "success";
?>
