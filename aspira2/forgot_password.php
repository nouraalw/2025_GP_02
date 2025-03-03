<?php
session_start();
require 'db_connection.php';
require __DIR__ . '/vendor/autoload.php'; // Include PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $token = bin2hex(random_bytes(50));

    $stmt = $conn->prepare("UPDATE users SET password_reset_token = ? WHERE email = ?");
    $stmt->bind_param("ss", $token, $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $resetLink = "http://localhost/aspira2/reset_password.php?token=$token";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';  
            $mail->SMTPAuth   = true;
            $mail->Username   = 'gp.finally@gmail.com'; 
            $mail->Password   = 'rucf vidv sbut zeaj';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('gp.finally@gmail.com', 'Aspira Support');
            $mail->addAddress($email);

            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "Click the link to reset your password: $resetLink";

            $mail->send();
            $message = "Check your email for the reset link.";
        } catch (Exception $e) {
            $message = "Email failed to send. Error: {$mail->ErrorInfo}";
        }
    } else {
        $message = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="Student Registration.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        :root {
            --primary-bg: #211742;
            --form-bg: #ffffff;
            --input-bg: #e5e5e5;
            --input-border: #b3b3b3;
            --button-color: #4b398e;
            --text-color: #211742;
            --highlight-text: #a576ff;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-image: url('images/Dust.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        header {
            width: 100%;
            padding: 15px 50px;
            background: #ffffff;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.4);
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #a576ff;
        }

        .logo img {
            width: 50px;
            height: auto;
        }

        .container {
            background: rgba(0, 0, 0, 0.05);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 70%;
            max-width: 350px;
        }

        .form-title {
            text-align: center;
            font-size: 22px;
            color: var(--primary-bg);
            margin-bottom: 20px;
        }

        .input-group {
            margin-bottom: 12px;
        }

        .input-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--input-border);
            border-radius: 5px;
        }

        .btn {
            width: 100%;
            padding: 10px;
            background: var(--button-color);
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn:hover {
            background: var(--highlight-text);
        }

        .message {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
            color: #28a745;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="images/logo.png" alt="ASPIRA">
            <span class="logo-text">SPIRA</span>
        </div>
    </header>
    
    <div class="container">
        <div class="form-box">
            <h2 class="form-title">Forgot Password</h2>
            <?php if (!empty($message)): ?>
                <p class="message"><?php echo $message; ?></p>
            <?php endif; ?>
            <form method="post">
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" required placeholder="Enter your email">
                </div>
            <button type="button" class="btn" onclick="window.location.href='reset_password.php'">Reset Password</button>
            </form>
        </div>
    </div>
</body>
</html>