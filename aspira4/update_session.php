
<?php
session_start();
include 'db_connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/PHPMailer-master/src/SMTP.php';

date_default_timezone_set('Asia/Riyadh');

$mentor_user_id = (int)($_SESSION['user_id'] ?? 0); // ✅ تأكد أن المعدّل هو نفس المنتور
$id   = $_POST['id']   ?? '';
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';

if (!$mentor_user_id || !$id || !$date || !$time) {
    echo "error";
    exit;
}

// 1) اقرأ الجلسة قبل أي تعديل (نحتاج القديم + التحقق من المالك)
$sel = $conn->prepare("
  SELECT s.id, s.mentor_id, s.mentee_id, s.date AS old_date, s.time AS old_time
  FROM sessions s
  WHERE s.id = ?
  LIMIT 1
");
$sel->bind_param("i", $id);
$sel->execute();
$cur = $sel->get_result()->fetch_assoc();
$sel->close();

if (!$cur) { echo "error"; exit; }
// if ((int)$cur['mentor_id'] !== $mentor_user_id) { echo "forbidden"; exit; }

// ✅ التحقق من أن المستخدم الحالي هو المنتور أو المنتي للجلسة
$current_user = (int)$_SESSION['user_id'];
if ($current_user !== (int)$cur['mentor_id'] && $current_user !== (int)$cur['mentee_id']) {
    echo "forbidden";
    exit;
}


// 2) منع التعديل إن كان باقي ≤ 60 دقيقة على الموعد الحالي
$old_dt = strtotime($cur['old_date'].' '.$cur['old_time']);
if (($old_dt - time()) <= 3600) {
    echo "too_close_to_start"; // باقي أقل من ساعة
    exit;
}

// 3) فحص التعارض خلال 60 دقيقة مع private + group (للموعد الجديد)
$sql_conflict = "
  SELECT 1
  FROM (
    SELECT CONCAT(date, ' ', time) AS dt
    FROM sessions
    WHERE mentor_id = ? AND date = ? AND id <> ?

    UNION ALL

    SELECT CONCAT(session_date, ' ', session_time) AS dt
    FROM grp_sessions
    WHERE mentor_id = ? AND session_date = ?
  ) AS all_slots
  WHERE ABS(TIMESTAMPDIFF(MINUTE, TIMESTAMP(?, ?), dt)) < 60
  LIMIT 1
";
$stmt_conflict = $conn->prepare($sql_conflict);
if (!$stmt_conflict) { echo "error"; exit; }

// كان: "isisss" ← خطأ
$stmt_conflict->bind_param(
    "isiisss",
    $mentor_user_id,  // i
    $date,            // s
    $id,              // i
    $mentor_user_id,  // i
    $date,            // s
    $date,            // s
    $time             // s
);

$stmt_conflict->execute();
$res_conflict = $stmt_conflict->get_result();
$stmt_conflict->close();

if ($res_conflict && $res_conflict->num_rows > 0) {
    echo "conflict"; // فيه جلسة خاصة/قروب أقل من ساعة حول الموعد الجديد
    exit;
}

// 4) نفّذ التحديث
$upd = $conn->prepare("UPDATE sessions SET date = ?, time = ? WHERE id = ?");
$upd->bind_param("ssi", $date, $time, $id);
$ok = $upd->execute();
$upd->close();

if (!$ok) { echo "error"; exit; }

// 5) جهّز الإيميل (نقرأ إيميل المنتي من users عبر mentees.user_id)
$email = null; $firstName = null;
if (!empty($cur['mentee_id'])) {
    $qEmail = $conn->prepare("
      SELECT u.email, u.first_name
      FROM users u
      WHERE u.user_id = ?
      LIMIT 1
    ");
    $qEmail->bind_param("i", $cur['mentee_id']);
    $qEmail->execute();
    $resEmail = $qEmail->get_result()->fetch_assoc();
    $qEmail->close();
    if ($resEmail) {
        $email = $resEmail['email'];
        $firstName = $resEmail['first_name'];
    }
}

// 6) أرسل الإشعار (إذا فيه إيميل)
if ($email) {
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
        $mail->addAddress($email, $firstName ?? '');

        $mail->isHTML(true);
        $mail->Subject = 'Your Session Has Been Rescheduled';
        $mail->Body    = sprintf(
            "<h2>Session Rescheduled</h2>
             <p>Dear %s,</p>
             <p>Your session has been rescheduled from <strong>%s %s</strong> to <strong>%s %s</strong>.</p>
             <p>If you have any questions, please contact us.</p>
             <p>Best regards,<br>ASPIRA Team</p>
             <p><br><br>Visit our website: <a href='https://aspira.sbs/'>ASPIRA</a></p>",
            htmlspecialchars($firstName ?? 'Mentee'),
            htmlspecialchars($cur['old_date']), htmlspecialchars($cur['old_time']),
            htmlspecialchars($date), htmlspecialchars($time)
        );

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        // ما نوقف النجاح بسبب فشل الإيميل
    }
}

echo "success";