<?php
include 'db_connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/PHPMailer-master/src/Exception.php'; // تأكد من أن PHPMailer مثبت
require 'PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/PHPMailer-master/src/SMTP.php'; // تأكد من أن PHPMailer مثبت

if (isset($_GET['id'])) {
    $mentor_id = $_GET['id'];

    // ✅ جلب معلومات المنتور
    $query = "SELECT users.email, users.first_name FROM mentors 
              JOIN users ON mentors.user_id = users.user_id 
              WHERE mentors.mentor_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $mentor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $email = $row['email'];
        $first_name = $row['first_name'];

        // ✅ تحديث حالة المنتور إلى "rejected"
        $update_query = "UPDATE mentors SET status='rejected' WHERE mentor_id=?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $mentor_id);
        mysqli_stmt_execute($stmt);

        // ✅ إرسال بريد إلكتروني
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'gp.finally@gmail.com'; 
            $mail->Password   = 'rucf vidv sbut zeaj';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('atata.4343@gmail.com', 'ASPIRA Admin');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'ASPIRA Platform';
            $mail->Body    = "<h3>Hello $first_name,</h3>
                              <p>We appreciate your interest in joining the ASPIRA platform. After careful consideration, we regret to inform you that your request has been <b>declined</b>.</p>
                  <p>Please know that this decision does not reflect your potential or abilities. We have full confidence in your skills and encourage you to continue pursuing your goals with determination and passion.</p>
                  <p>We sincerely wish you success and fulfillment in all your future endeavors.</p>
                  <p>Best regards,<br><b>ASPIRA Team</b></p>";

            $mail->send();
        } catch (Exception $e) {
            echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}

// ✅ إعادة التوجيه بعد الإرسال
echo "<script> window.location.href='admin_panel.php';</script>";
?>

