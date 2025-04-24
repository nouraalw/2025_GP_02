<?php
session_start();
require 'db_connection.php';
//require __DIR__ . '/vendor/autoload.php'; // Include PHPMailer
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['email'])) {
        $email = trim($_POST['email']);
        
        // Check if the email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->close();
            
            // Generate a secure token
            $token = bin2hex(random_bytes(50));
            $stmt = $conn->prepare("UPDATE users SET password_reset_token = ? WHERE email = ?");
            $stmt->bind_param("ss", $token, $email);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                //$resetLink = "http://localhost:8888/aspira4/reset_password.php?token=" . urlencode($token);
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                  $host = $_SERVER['HTTP_HOST']; // This includes localhost and the correct port automatically

                 $resetLink = "$protocol://$host/aspira4/reset_password.php?token=" . urlencode($token);


                // Send email using PHPMailer
                $mail = new PHPMailer(true);
                try {
                    //$mail->SMTPDebug = 2;
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'gp.finally@gmail.com';
                    $mail->Password   = 'rucf vidv sbut zeaj';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('gp.finally@gmail.com', 'Aspira Support');
                    $mail->addAddress($email);
                    //$mail->Subject = 'Password Reset Request';
                    //$mail->Body    = "Click the link to reset your password: $resetLink";
                    $mail->isHTML(true); // Enable HTML emails
                   $mail->Subject = 'Password Reset Request - Aspira';
                   $mail->Body    = "<p>Hello,</p>
                  <p>You requested a password reset. Click the link below:</p>
                  <p><a href='$resetLink'>$resetLink</a></p>
                  <p>If you did not request this, please ignore this email.</p>
                  <p>Regards,</p>
                  <p><strong>Aspira Support</strong></p>";
           $mail->AltBody = "Hello,\n\nYou requested a password reset. Click the link below:\n\n$resetLink\n\nIf you did not request this, please ignore this email.\n\nRegards,\nAspira Support";


                     // **Fix: Ensure the email is actually sent**
                     if ($mail->send()) {
                        $message = "Check your email for the reset link.";
                    } else {
                        $message = "Error: Email not sent. Please try again.";
                    }
                } catch (Exception $e) {
                    $message = "Email failed to send. Error: " . $mail->ErrorInfo;
                }
            } else {
                $message = "Error updating token. Please try again.";
            }
        } else {
            $message = "Email not found.";
        }
        $stmt->close();
    } else {
        $message = "Please enter a valid email.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
    
    
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
    position: fixed;
    top: 0;
    left: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 1000;
    box-shadow: none !important; /* ✅ إزالة أي ظل */
    border-bottom: 0.3px solid rgba(0, 0, 0, 0.05); /* ✅ خط خفيف جدًا */
}

/* Logo */
.logo {
    font-size: 24px;
    font-weight: bold;
    color: #a576ff;
}

.logo img {
    width: 24px;
    height: auto;
}

        .container {
             background: rgba(255, 255, 255, 0.95);
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
            width: 95%;
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
    <?php if (empty($message)): ?>
        <div class="input-group">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" required placeholder="Enter your email">
        </div>
        <button type="submit" class="btn">Send Reset Link</button>
    <?php endif; ?>
</form>

        </div>
    </div>
</body>
</html>