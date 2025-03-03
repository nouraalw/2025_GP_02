<?php
include 'db_connection.php';
if (!$conn) {
    die("❌ Database connection failed: " . mysqli_connect_error());
} else {
    echo "✅ Database connected successfully!";
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/PHPMailer-master/src/Exception.php';


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = $_POST['email'];

    // ✅ Generate OTP
    $otp_code = rand(100000, 999999);
    $expiry_time = date("Y-m-d H:i:s", strtotime("+5 minutes")); // OTP expires in 5 minutes

    // ✅ Delete any existing OTP for this email
    $delete_query = "DELETE FROM otp_verification WHERE email = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    // ✅ Insert OTP into database
    $query = "INSERT INTO otp_verification (email, otp_code, expiry_time) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sss", $email, $otp_code, $expiry_time);

    if (!mysqli_stmt_execute($stmt)) {
        die("❌ Database Insertion Error: " . mysqli_error($conn)); // Print error if insertion fails
    }

    // ✅ Check if OTP is in database
    $check_query = "SELECT * FROM otp_verification WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo "✅ OTP inserted successfully!";
    } else {
        die("❌ OTP was not inserted into the database.");
    }

    // ✅ Send OTP via Email
    $mail = new PHPMailer(true);
    try {
        // ✅ Gmail SMTP Settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'atata.4343@gmail.com'; // Change this
        $mail->Password = 'fhoq vlgn wslj ubov';   // Change this
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // ✅ Email Details
        $mail->setFrom('atata.4343@gmail.com', 'ASPIRA');
        $mail->addAddress($email);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "Your OTP code is: $otp_code. It will expire in 5 minutes.";

        if ($mail->send()) {
            echo "✅ OTP sent successfully!";
        } else {
            die("❌ Email Sending Failed: " . $mail->ErrorInfo);
        }
    } catch (Exception $e) {
        die("❌ PHPMailer Error: " . $mail->ErrorInfo);
    }
}
if (mysqli_stmt_execute($stmt)) {
    echo "✅ OTP stored successfully in database: $otp_code";
} else {
    echo "❌ Error storing OTP: " . mysqli_error($conn);
}

?>
