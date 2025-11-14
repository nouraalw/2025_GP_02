<?php
// ================ bootstrap =================
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/PHPMailer-master/src/SMTP.php';

// ====== auth ======
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo "unauthorized";
  exit;
}
$mentor_user_id = (int)$_SESSION['user_id'];

// ====== input ======
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  echo "missing id";
  exit;
}

// ====== الجلسة + تأكيد الملكية ======
$chk = $conn->prepare("
  SELECT gs.group_session_id, gs.title, gs.session_date, gs.session_time
  FROM grp_sessions gs
  WHERE gs.group_session_id = ? AND gs.mentor_id = ?
  LIMIT 1
");
$chk->bind_param("ii", $id, $mentor_user_id);
$chk->execute();
$session = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$session) {
  echo "not found";
  exit;
}

$sessionDate  = $session['session_date'];
$sessionTime  = $session['session_time'];
$sessionTitle = $session['title'] ?? 'Group Session';

// (اختياري) منع الحذف قبل ساعة
$startTs = strtotime($sessionDate.' '.$sessionTime);
if ($startTs - time() <= 3600) {
  echo "too_late";
  exit;
}

// ====== إيميل المنتور لاستبعاده صريحاً ======
$mentorEmail = '';
$meq = $conn->prepare("SELECT email FROM users WHERE user_id=? LIMIT 1");
$meq->bind_param("i", $mentor_user_id);
$meq->execute();
if ($mr = $meq->get_result()->fetch_assoc()) {
  $mentorEmail = trim((string)$mr['email']);
}
$meq->close();

// ====== جيب إيميلات المنتيز فقط + استبعاد المنتور بالـ ID وبالإيميل ======
$emails = []; // email => first_name
$q = $conn->prepare("
  SELECT DISTINCT u.email, u.first_name
  FROM grp_session_participants p
  JOIN mentees mn ON mn.user_id = p.mentee_id   -- تأكيد أنه منتي
  JOIN users   u  ON u.user_id  = mn.user_id
  WHERE p.group_session_id = ?
    AND p.mentee_id <> ?                           -- استبعاد المنتور لو مضاف بالغلط
    AND u.email IS NOT NULL AND u.email <> ''      -- فقط إيميلات صالحة
");
$q->bind_param("ii", $id, $mentor_user_id);
$q->execute();
$res = $q->get_result();
while ($row = $res->fetch_assoc()) {
  $e = trim((string)$row['email']);
  $n = (string)$row['first_name'];

  // استبعاد أي احتمال لو كان الإيميل نفسه حق المنتور
  if ($e === '' || ($mentorEmail !== '' && strcasecmp($e, $mentorEmail) === 0)) {
    continue;
  }
  $emails[$e] = $n;
}
$q->close();

// ====== احذف المشاركين أولاً (لو ما عندك CASCADE) ======
$dp = $conn->prepare("DELETE FROM grp_session_participants WHERE group_session_id = ?");
$dp->bind_param("i", $id);
$dp->execute();
$dp->close();

// ====== احذف الجلسة ======
$del = $conn->prepare("DELETE FROM grp_sessions WHERE group_session_id = ? AND mentor_id = ?");
$del->bind_param("ii", $id, $mentor_user_id);
$ok = $del->execute();
$del->close();

if (!$ok) {
  echo "db error";
  exit;
}

// ====== أرسل إيميل إلغاء للمنتيز فقط ======
if (!empty($emails)) {
  $mail = new PHPMailer(true);
  try {
      $mail->isSMTP();
      $mail->Host       = 'smtp.gmail.com';
      $mail->SMTPAuth   = true;
      $mail->Username   = 'gp.finally@gmail.com';
      $mail->Password   = 'rucf vidv sbut zeaj';  // App Password
      $mail->SMTPSecure = 'tls';
      $mail->Port       = 587;
      $mail->setFrom('gp.finally@gmail.com', 'ASPIRA');
      $mail->isHTML(true);

      foreach ($emails as $toEmail => $firstName) {
        // safeguard أخير: لا تضيف أبدًا mentorEmail حتى لو وصل هنا بالخطأ
        if ($mentorEmail !== '' && strcasecmp($toEmail, $mentorEmail) === 0) {
          continue;
        }

        $mail->clearAllRecipients();
        $mail->addAddress($toEmail, $firstName);

        $mail->Subject = 'Group Session Canceled';
        $mail->Body = "
          <h2>Group Session Canceled</h2>
          <p>Hi ".htmlspecialchars($firstName).",</p>
          <p>Your group session <strong>".htmlspecialchars($sessionTitle)."</strong> scheduled on
             <strong>".htmlspecialchars($sessionDate)."</strong> at <strong>".htmlspecialchars($sessionTime)."</strong>
             has been <span style='color:#d00;'>canceled</span> by the mentor.</p>
          <p>— ASPIRA Team</p>
        <p><br><br>Visit our website: <a href='http://localhost/aspiraSHFV/homepage.html'>ASPIRA</a></p>";
        try { $mail->send(); } catch (Exception $e) { /* log only */ }
      }
  } catch (Exception $e) { /* ignore smtp init errors */ }
}

echo "success";
