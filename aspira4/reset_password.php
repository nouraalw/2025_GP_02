<?php
session_start();
require 'db_connection.php';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid request - Token is missing.<br>Debug: No token received. Please check your URL.");
} //else {
   // echo "Debug: Received token = " . htmlspecialchars($_GET['token']) . "<br>";
//}


require 'db_connection.php';
$token = $_GET['token'];

// Check if the token exists in the database
$stmt = $conn->prepare("SELECT user_id FROM users WHERE password_reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid or expired token.");
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['password'];
    
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $new_password)) {
        echo "Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ?, password_reset_token = NULL WHERE password_reset_token = ?");
        $stmt->bind_param("ss", $hashed_password, $token);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            //echo "Password successfully reset. Redirecting to login page...";
            header("Location: login.php");
            exit();
        } else {
            echo "Invalid or expired token.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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
            <h2 class="form-title">Reset Password</h2>
            <form method="post">
                <div class="input-group">
                    <label for="password">New Password</label>
                    <input type="password" name="password" id="password" required placeholder="Enter new password" onkeyup="validatePassword()">
                    <button type="button" class="toggle-password" onclick="togglePassword()">👁</button>
                </div>
                
                <div id="password-rules">
                    <p id="rule-length" class="rule">- Must be at least 8 characters</p>
                    <p id="rule-uppercase" class="rule">- Must include one uppercase letter</p>
                    <p id="rule-lowercase" class="rule">- Must include one lowercase letter</p>
                    <p id="rule-number" class="rule">- Must include one number</p>
                    <p id="rule-special" class="rule">- Must include one special character (@, #, $, etc.)</p>
                </div>
                
                <button type="submit" class="btn next-step" disabled>Reset Password</button>
            </form>
        </div>
    </div>
    
    <script>
        function validatePassword() {
            let password = document.getElementById("password").value;
            let rules = {
                length: document.getElementById("rule-length"),
                uppercase: document.getElementById("rule-uppercase"),
                lowercase: document.getElementById("rule-lowercase"),
                number: document.getElementById("rule-number"),
                special: document.getElementById("rule-special"),
            };
            let nextBtn = document.querySelector(".btn.next-step");

            let lengthCheck = password.length >= 8;
            let uppercaseCheck = /[A-Z]/.test(password);
            let lowercaseCheck = /[a-z]/.test(password);
            let numberCheck = /\d/.test(password);
            let specialCheck = /[@#$%^&*]/.test(password);

            rules.length.innerHTML = (lengthCheck ? "✔" : "-") + " Must be at least 8 characters";
            rules.uppercase.innerHTML = (uppercaseCheck ? "✔" : "-") + " Must include one uppercase letter";
            rules.lowercase.innerHTML = (lowercaseCheck ? "✔" : "-") + " Must include one lowercase letter";
            rules.number.innerHTML = (numberCheck ? "✔" : "-") + " Must include one number";
            rules.special.innerHTML = (specialCheck ? "✔" : "-") + " Must include one special character (@, #, $, etc.)";

            let allPassed = lengthCheck && uppercaseCheck && lowercaseCheck && numberCheck && specialCheck;
            nextBtn.disabled = !allPassed;
        }

        function togglePassword() {
            let passwordField = document.getElementById("password");
            passwordField.type = passwordField.type === "password" ? "text" : "password";
        }
    </script>
</body>
</html>