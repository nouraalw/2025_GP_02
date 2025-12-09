<?php
session_start();
include 'db_connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/PHPMailer-master/src/SMTP.php';

$id       = $_POST['id']   ?? '';
$date     = $_POST['date'] ?? '';
$time     = $_POST['time'] ?? '';
$mentee_id= $_SESSION['user_id'] ?? '';

if ($id && $date && $time && $mentee_id) {

    // تأكد أن الجلسة تخص هذا المنتي
    $check = $conn->prepare("SELECT * FROM sessions WHERE id = ? AND mentee_id = ?");
    $check->bind_param("ii", $id, $mentee_id);
    $check->execute();
    $session = $check->get_result()->fetch_assoc();
    if (!$session) { exit("error"); }

    date_default_timezone_set('Asia/Riyadh');
    $current_dt = time();
    $old_dt     = strtotime($session['date'].' '.$session['time']);

    // لا تعديل إذا باقي ≤ 1 ساعة على الموعد الحالي
    if ($old_dt - $current_dt <= 3600) { exit("error"); }

    // الموعد الجديد يجب أن يكون في المستقبل (بدون هامش 15 دقيقة)
    $new_dt = strtotime($date.' '.$time);
    if ($new_dt <= $current_dt) { exit("error"); }

    // تأكد أن الوقت الجديد موجود كسلوْت متاح لنفس المنتور ونفس النوع
    $mentor_id = (int)$session['mentor_id'];
    $is_public = (int)$session['is_public'];

    $slotStmt = $conn->prepare("
        SELECT id FROM sessions
        WHERE mentor_id = ?
          AND is_public = ?
          AND status = 'available'
          AND date = ?
          AND time = ?
        LIMIT 1
    ");
    $slotStmt->bind_param("iiss", $mentor_id, $is_public, $date, $time);
    $slotStmt->execute();
    $slotRes = $slotStmt->get_result();
    $slotRow = $slotRes->fetch_assoc();
    if (!$slotRow) { exit('error'); }
    $available_slot_id = (int)$slotRow['id'];

    // منع التعارض مع جلسات المنتور (±45 دقيقة) لكن مع الجلسات المحجوزة فقط
    $checkSql = "SELECT time FROM sessions
                 WHERE mentor_id = ?
                   AND date = ?
                   AND id <> ?
                   AND status = 'booked'";
    $st = $conn->prepare($checkSql);
    $st->bind_param("isi", $mentor_id, $date, $id);
    $st->execute();
    $res = $st->get_result();

    $conflict = false;
    $target   = strtotime($time);
    while ($rowC = $res->fetch_assoc()) {
      $ex = strtotime($rowC['time']);
      if (abs($target - $ex) < 45*60) { $conflict = true; break; }
    }
    if ($conflict) { exit("error"); }

    // تحديث الجلسة
    $stmt = $conn->prepare("UPDATE sessions SET date=?, time=? WHERE id=? AND mentee_id=?");
    $stmt->bind_param("ssii", $date, $time, $id, $mentee_id);

    if ($stmt->execute()) {
        // حذف السلوْت المتاح الذي أصبح مستخدمًا الآن
        $cleanup = $conn->prepare("DELETE FROM sessions WHERE id = ? AND status = 'available' LIMIT 1");
        $cleanup->bind_param("i", $available_slot_id);
        $cleanup->execute();

        // إرسال إشعار للمنتور
        $q = "SELECT u.email, u.first_name
              FROM sessions s
              JOIN mentors m ON s.mentor_id = m.user_id
              JOIN users   u ON m.user_id = u.user_id
              WHERE s.id = ?";
        $stmtEmail = $conn->prepare($q);
        $stmtEmail->bind_param("i", $id);
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
                $mail->Subject = 'A Session Has Been Rescheduled by a Mentee';
                $mail->Body    = "
                    <h2>Session Rescheduled</h2>
                    <p>Dear $firstName,</p>
                    <p>The mentee has rescheduled the session to <strong>$date</strong> at <strong>$time</strong>.</p>
                    <p>Best regards,<br>ASPIRA Team</p>
                    <p><br><br>Visit our website: <a href='https://aspira.sbs/'>ASPIRA</a></p>
                ";
                $mail->send();
            } catch (Exception $e) {
                error_log("Mailer Error: {$mail->ErrorInfo}");
            }
        }

        echo "success";
    } else {
        echo "error";
    }

} else {
    echo "error";
}
