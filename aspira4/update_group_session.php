<?php
// update_group_session.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db_connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/PHPMailer-master/src/SMTP.php';

// ===== Auth =====
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo "unauthorized"; exit; }
$mentor_id = (int)$_SESSION['user_id'];

// ===== Input =====
$id   = isset($_POST['id'])   ? (int)$_POST['id']   : 0;
$date = isset($_POST['date']) ? trim($_POST['date']) : '';
$time = isset($_POST['time']) ? trim($_POST['time']) : '';

if ($id <= 0 || $date === '' || $time === '') { echo "missing params"; exit; }

// ===== تأكيد ملكية الجلسة + معلومات قديمة =====
$chk = $conn->prepare("
  SELECT group_session_id, session_date, session_time, title
  FROM grp_sessions
  WHERE group_session_id = ? AND mentor_id = ?
  LIMIT 1
");
$chk->bind_param("ii", $id, $mentor_id);
$chk->execute();
$session = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$session) { echo "not found"; exit; }

$old_date = $session['session_date'];
$old_time = $session['session_time'];
$title    = $session['title'];

// ===== التحقق من الوقت (ممنوع التعديل قبل ساعة) =====
$now = time();
$session_ts = strtotime($old_date.' '.$old_time);

if ($session_ts - $now <= 3600) {
  echo " You cannot reschedule a session less than 1 hour before it starts.";
  exit;
}
// ===== فحص التعارضات (هامش أقل من ساعة) =====
$ONE_HR  = 3600;
$new_ts  = strtotime($date.' '.$time);

// 2.a) تعارض مع الجلسات الخاصة لنفس اليوم — نحسب فقط booked/available
$conflict_private = false;
$st = $conn->prepare("
  SELECT time
  FROM sessions
  WHERE mentor_id = ?
    AND date = ?
    AND status IN ('booked','available')
");
$st->bind_param("is", $mentor_id, $date);
$st->execute();
$rs = $st->get_result();
while ($r = $rs->fetch_assoc()) {
  $exists_ts = strtotime($date.' '.$r['time']);
  if (abs($new_ts - $exists_ts) < $ONE_HR) { $conflict_private = true; break; }
}
$st->close();
if ($conflict_private) { echo "conflict_private"; exit; }

// 2.b) تعارض مع القروب سشن الأخرى — نحسب فقط available/upcoming ونستثني نفسها
$conflict_group = false;
$st2 = $conn->prepare("
  SELECT session_time
  FROM grp_sessions
  WHERE mentor_id = ?
    AND session_date = ?
    AND group_session_id <> ?
    AND status IN ('available','upcoming')
");
$st2->bind_param("isi", $mentor_id, $date, $id);
$st2->execute();
$rs2 = $st2->get_result();
while ($r2 = $rs2->fetch_assoc()) {
  $exists_ts = strtotime($date.' '.$r2['session_time']);
  if (abs($new_ts - $exists_ts) < $ONE_HR) { $conflict_group = true; break; }
}
$st2->close();
if ($conflict_group) { echo "conflict_group"; exit; }

// ===== التحديث =====
$upd = $conn->prepare("
  UPDATE grp_sessions
     SET session_date = ?, session_time = ?
   WHERE group_session_id = ? AND mentor_id = ?
");
$upd->bind_param("ssii", $date, $time, $id, $mentor_id);
$ok = $upd->execute();
$upd->close();

if (!$ok) { echo "db error"; exit; }

// ===== جلب ايميلات المنتيز الحاجزين على هذه الجلسة =====
$q = $conn->prepare("
  SELECT u.email, u.first_name
  FROM grp_session_participants p
  JOIN users u ON u.user_id = p.mentee_id
  WHERE p.group_session_id = ?
");
$q->bind_param("i", $id);
$q->execute();
$rec = $q->get_result();
$mentees = $rec->fetch_all(MYSQLI_ASSOC);
$q->close();

// ===== إرسال الايميلات (للمنتيز فقط) =====
if (!empty($mentees)) {
  foreach ($mentees as $m) {
    $email = $m['email'];
    $first = $m['first_name'];

    $mail = new PHPMailer(true);
    try {
      $mail->isSMTP();
      $mail->Host       = 'smtp.gmail.com';
      $mail->SMTPAuth   = true;
      $mail->Username   = 'gp.finally@gmail.com';   // بريد الإرسال
      $mail->Password   = 'rucf vidv sbut zeaj';    // App Password
      $mail->SMTPSecure = 'tls';
      $mail->Port       = 587;

      $mail->setFrom('gp.finally@gmail.com', 'ASPIRA');
      $mail->addAddress($email, $first ?: '');

      $mail->isHTML(true);
      $mail->Subject = 'Group Session Rescheduled';
      $mail->Body    = "
        <h2>Session Rescheduled</h2>
        <p>Dear ".htmlspecialchars($first).",</p>
        <p>The group session <b>".htmlspecialchars($title)."</b> has been rescheduled.</p>
        <p><b>Old:</b> ".htmlspecialchars($old_date)." at ".htmlspecialchars($old_time)."</p>
        <p><b>New:</b> ".htmlspecialchars($date)." at ".htmlspecialchars($time)."</p>
        <p>Best regards,<br>ASPIRA Team</p>
        <p><br><br>Visit our website: <a href='https://aspira.sbs/'>ASPIRA</a></p>
      ";

      $mail->send();
    } catch (Exception $e) {
      error_log("Mailer Error (update_group_session): {$mail->ErrorInfo}");
    }
  }
}

echo "success";
