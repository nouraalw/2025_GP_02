<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connection.php';
session_start();
if (!isset($_SESSION)) {
    die("❌ Session failed to start.");
}  // Start session to store OTP
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}


// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require 'PHPMailer-master/PHPMailer-master/src/Exception.php'; // 
require 'PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/PHPMailer-master/src/SMTP.php';


// ✅ Handle OTP Verification Request (AJAX)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["verify_code"])) {
    $entered_code = $_POST["code"];
    
    if (!isset($_SESSION['otp_code'])) {
        echo "session_expired"; // No OTP stored in session
        exit();
    }

    if ($entered_code == $_SESSION['otp_code']) {
        echo "verified";  // OTP is correct
    } else {
        echo "invalid";  // OTP is incorrect
    }
    exit();
}

// ✅ Handle Email Verification Request (AJAX)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["verify_email"])) {
    $email = $_POST["email"];

    // Check if email already exists
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        echo "exists";  // Email is already registered
        exit();
    }
    
    // Generate 6-digit OTP
    $otp = rand(100000, 999999);
    $_SESSION['otp_code'] = $otp;
    $_SESSION['otp_email'] = $email;

    // Send OTP via Email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gp.finally@gmail.com'; // Replace with your Gmail
        $mail->Password   = 'rucf vidv sbut zeaj'; // Use an App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('gp.finally@gmail.com', 'ASPIRA Team');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Verification Code';
        $mail->Body    = "<p>Your verification code is: <strong>$otp</strong></p>";

        $mail->send();
        echo "code_sent";  // Successfully sent
    } catch (Exception $e) {
        echo "error";  // Sending failed
    }
    exit();
}

// ✅ Handle OTP Verification Request (AJAX)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["verify_code"])) {
    $entered_code = $_POST["code"];
    
    if ($entered_code == $_SESSION['otp_code']) {
        echo "verified";  // OTP is correct
    } else {
        echo "invalid";  // OTP is incorrect
    }
    exit();
}
// ✅ Handle AJAX Requests for Real-time Validation (Before Submission)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["check_availability"])) {
    $response = "available";

    if ($_POST["check_availability"] == "email" && isset($_POST["email"])) {
        $email = $_POST["email"];
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $response = "exists";
        }
    }

    if ($_POST["check_availability"] == "phone" && isset($_POST["phone"])) {
        $phone = $_POST["phone"];
        $query = "SELECT * FROM users WHERE phone_number = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $phone);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $response = "exists";
        }
    }

    echo $response;
    exit();
}

// ✅ Process Form Submission (After Handling AJAX)
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST["check_availability"])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $status = $_POST['status'];
    $interests = $_POST['description'];

    // ✅ Check if Email Exists Before Inserting
    $email_query = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $email_query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $email_result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($email_result) > 0) {
        echo "<script> window.history.back();</script>";
        exit();
    }
    mysqli_stmt_close($stmt);

    // ✅ Check if Phone Exists Before Inserting
    $phone_query = "SELECT * FROM users WHERE phone_number = ?";
    $stmt = mysqli_prepare($conn, $phone_query);
    mysqli_stmt_bind_param($stmt, "s", $phone);
    mysqli_stmt_execute($stmt);
    $phone_result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($phone_result) > 0) {
        echo "<script> window.history.back();</script>";
        exit();
    }
    mysqli_stmt_close($stmt);

    // ✅ Insert into Users Table
    $query = "INSERT INTO users (first_name, last_name, email, password, phone_number, role) 
              VALUES (?, ?, ?, ?, ?, 'mentee')";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sssss", $first_name, $last_name, $email, $password, $phone);

    if (mysqli_stmt_execute($stmt)) {
       $user_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);


        // ✅ Insert into Mentees Table
        $query2 = "INSERT INTO mentees (user_id, student_status, interests) 
                   VALUES (?, ?, ?)";
        $stmt2 = mysqli_prepare($conn, $query2);
        mysqli_stmt_bind_param($stmt2, "iss", $user_id, $status, $interests);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        
//
        echo "<script>window.location.href='MentorCenter.php';</script>";
    }
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentee Registration</title>
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
       <link rel="icon" type="image/png" href="images/favicon.png">
</head>
<body>
     <header>
        <div class="logo">
            <img src="images/Logo.png" alt="ASPIRA">
            <span class="logo-text">SPIRA</span>
        </div>
    </header>
   <style>
        /* Color Palette */
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
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        /* Header */
       
        /* Header */
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


        .logo-text {
            color: #a576ff;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 500px;
            margin-top: 100px;
            margin-bottom: 50px;
            max-height: 80vh;
            overflow-y: auto;
        }

        /* Progress Bar */
        .progress-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--input-border);
            color: var(--primary-bg);
            font-weight: bold;
            text-align: center;
            line-height: 30px;
            margin: 0 5px;
        }

        .step.active {
            background: var(--button-color);
            color: white;
        }

        .line {
            width: 25px;
            height: 3px;
            background: var(--input-border);
        }

        /* Form Elements */
        .form-title {
            text-align: center;
            font-size: 24px;
            color: var(--primary-bg);
            margin-bottom: 20px;
        }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-color);
            font-weight: 500;
        }

        .input-group input,
        .input-group textarea,
        .input-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--input-border);
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }

        #password-rules {
            font-size: 12px;
            color: #555;
            margin-top: 5px;
        }

        #password-rules p {
            margin: 2px 0;
        }

        .error-message {
            color: red;
            font-size: 12px;
            display: none;
        }

        /* Buttons */
        .btn {
            width: 100%;
            padding: 12px;
            background: var(--button-color);
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .btn:hover {
            background: #a576ff;
        }

        .btn-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 15px;
        }

        .already-account {
            text-align: center;
            font-size: 14px;
            color: #a576ff;
            margin-top: 10px;
        }

        .login-link {
            color: #4b398e;
            font-weight: bold;
            text-decoration: underline;
            cursor: pointer;
        }

        .login-link:hover {
            color: #3a2c76;
        }

        .hidden {
            display: none;
        }

        /* Modal Styling */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1100;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        /* OTP Modal Styling */
        .otp-input-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        
        .otp-input {
            width: 50px;
            height: 50px;
            font-size: 24px;
            text-align: center;
            border: 2px solid var(--input-border);
            border-radius: 5px;
        }
        
        .otp-input:focus {
            border-color: var(--button-color);
            outline: none;
        }
        
        .otp-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .otp-btn, .otp-resend-btn {
            max-width: 150px;
        }
        
        .otp-resend-btn {
            background-color: #6c757d;
        }
        
        .otp-resend-btn:hover {
            background-color: #5a6268;
        }
    </style>

    <div class="container">
        <div class="form-box">
            <h2 class="form-title">Sign up as a Mentee</h2>
            <div class="progress-bar">
                <div class="step active" id="step-1">1</div>
                <div class="line"></div>
                <div class="step inactive" id="step-2">2</div>
            </div>
            <form id="registration-form" action="Mentee_Registration.php" method="POST" enctype="multipart/form-data">
                <!-- Step 1 -->
                <div class="step-content step-1">
                    
                    <div class="input-group">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="first_name" required>
                        <p class="error-message" style="display: none;">Invalid name. Use letters only</p>
                    </div>
                    <div class="input-group">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="last_name" required>
                        <p class="error-message" style="display: none;">Invalid name. Use letters only</p>
                    </div>
                    



                    <div class="input-group">
                      <label for="email">Email</label>
                      <input type="email" id="email" name="email" required>
                    <p class="error-message" id="email-error" style="color: red; display: none;">Email must be valid</p>
                    </div>


                    <div class="input-group" id="otp-container" style="display: none;">
                    <label for="verification-code">Enter Verification Code</label>
                   <input type="text" id="verification-code" name="verification_code" required>
                   <button type="button" id="verify-code">Verify</button>
                   <p class="error-message" id="code-error" style="color: red; display: none;">Incorrect Code</p>
                    </div>








                    <div class="input-group">
                     <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
                    <p class="error-message" id="phone-error" style="color: red; display: none;"></p>
                    </div>

                  






                   
<!-- Password Input -->
<div class="input-group password-group">
    <label for="password">Password</label>
    <div class="password-container">
        <input type="password" id="password" name="password" required onkeyup="validatePassword()">
        <span class="toggle-password" onclick="togglePassword()"></span>
    </div>
    
    <!-- ✅ Password Rules Display -->
      <div id="password-rules">
        <p id="rule-length" class="rule">- Must be at least 8 characters</p>
        <p id="rule-uppercase" class="rule">- Must include one uppercase letter</p>
        <p id="rule-lowercase" class="rule">- Must include one lowercase letter</p>
        <p id="rule-number" class="rule">- Must include one number</p>
        <p id="rule-special" class="rule">- Must include one special character (@, #, $, etc.)</p>
    </div>
  
</div>
<style>#password-rules {
    font-size: 12px;
    color: #555;
    margin-top: 5px;
}

#password-rules p {
    margin: 2px 0;
}
</style>

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
    if (password === "") {
        passwordField.style.borderColor = "red";
        for (let key in rules) {
            rules[key].innerHTML = "";
        }
        nextBtn.disabled = true;
        return;
    }

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

                    <button type="button" class="btn next-step">Next</button>
                    <p class="already-account">
                        Already have an account? <a href="login.php" class="login-link">Log in</a>
                    </p>
                    
                </div>

                <!-- Step 2 -->
                <div class="step-content step-2 hidden">
                
                    <div class="input-group">
                        <label for="description">Description of Interests</label>
                        <textarea id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="input-group">
                        <label for="status">Are you an Undergraduate or a Graduate?</label>
                        <select id="status" name="status" required>
                            <option value="" disabled selected>Select your status</option>
                            <option value="undergraduate">Undergraduate</option>
                            <option value="graduate">Graduate</option>
                        </select>
                    </div>
                    <div class="btn-container">
                        <button type="button" class="btn prev-step">Back</button>
                        <button type="submit" class="btn">Create an Account</button>
                    </div>
                    
                </div>
            </form>
        </div>
    </div>

    <script>
       document.addEventListener("DOMContentLoaded", function () {
    const nextBtns = document.querySelectorAll(".next-step");
    const prevBtns = document.querySelectorAll(".prev-step");
    const submitBtn = document.querySelector(".btn[type='submit']");
    const step1s = document.querySelectorAll(".step-1");
    const step2s = document.querySelectorAll(".step-2");
    const steps = document.querySelectorAll(".progress-bar .step");

    let isEmailVerified = false; // التحقق من البريد الإلكتروني

    function validateName(nameField) {
        const invalidPattern = /[^A-Za-z\s]/;
        let isValid = true;

        if (!nameField.value.trim()) {
            nameField.style.borderColor = "red";
            nameField.nextElementSibling.style.display = "none";
            isValid = false;
        } else if (invalidPattern.test(nameField.value)) {
            nameField.style.borderColor = "red";
            nameField.nextElementSibling.innerText = "Only letters are allowed";
            nameField.nextElementSibling.style.display = "block";
            isValid = false;
        } else {
            nameField.style.borderColor = "green";
            nameField.nextElementSibling.style.display = "none";
        }

        return isValid;
    }

    document.getElementById("first-name").addEventListener("blur", function () {
        validateName(this);
    });

    document.getElementById("last-name").addEventListener("blur", function () {
        validateName(this);
    });

    function validateStep1(form) {
        let valid = true;
        const firstName = form.querySelector("#first-name");
        const lastName = form.querySelector("#last-name");
        const email = form.querySelector("#email");
        const phone = form.querySelector("#phone");
        const password = form.querySelector("#password");
        const globalError = form.querySelector("#form-error");

        if (globalError) globalError.style.display = "none";

        if (!validateName(firstName) || !validateName(lastName)) valid = false;

        // التحقق من البريد الإلكتروني
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email.value)) {
            email.style.borderColor = "red";
            email.nextElementSibling.style.display = "block";
            valid = false;
        } else {
            email.style.borderColor = "green";
            email.nextElementSibling.style.display = "none";
        }

        // التحقق من رقم الهاتف
        if (phone && !/^\d{8,15}$/.test(phone.value)) {
            phone.style.borderColor = "red";
            phone.nextElementSibling.style.display = "block";
            valid = false;
        } else {
            phone.style.borderColor = "green";
            phone.nextElementSibling.style.display = "none";
        }

        if (!valid && globalError) {
            globalError.style.display = "block";
            globalError.textContent = "Please complete all fields";
        }
        if (!password.value.trim()) {
    password.style.borderColor = "red";
    password.parentElement.nextElementSibling.style.display = "block";
    valid = false;
  } else {
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#$&]).{8,}$/;
    if (!password.value.match(passwordPattern)) {
      password.style.borderColor = "red";
      password.parentElement.nextElementSibling.style.display = "block";
      valid = false;
    } else {
      password.style.borderColor = "green";
      password.parentElement.nextElementSibling.style.display = "none";
    }
  }

        return valid;
    }

    nextBtns.forEach((btn, index) => {
        btn.addEventListener("click", function () {
            const form = btn.closest("form");

            // التحقق من تأكيد البريد الإلكتروني قبل الانتقال للخطوة التالية
            if (!isEmailVerified) {
    alert("️ Please verify your email before proceeding.");
    return;
}

            else {
                // إخفاء رسالة الخطأ إذا كان البريد الإلكتروني مؤكدًا
                const emailError = document.getElementById("email-error");
                emailError.style.display = "none";
            }

            // التحقق من صحة بيانات النموذج قبل الانتقال إلى الخطوة التالية
            if (validateStep1(form)) {
                step1s[index].classList.add("hidden");
                step2s[index].classList.remove("hidden");

                // تحديث شريط التقدم وإضافة علامة ✅
                steps[index * 2].classList.add("completed");
                steps[index * 2].innerHTML = "✓";
                steps[index * 2 + 1].classList.add("active");
            }
        });
    });

    prevBtns.forEach((btn, index) => {
        btn.addEventListener("click", function () {
            step2s[index].classList.add("hidden");
            step1s[index].classList.remove("hidden");
            steps[index * 2].classList.remove("completed");
            steps[index * 2].innerHTML = "1";
            steps[index * 2 + 1].classList.remove("active");
        });
    });

    document.getElementById("verify-code").addEventListener("click", function () {
        let code = document.getElementById("verification-code").value.trim();
        fetch("Mentee_Registration.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "verify_code=1&code=" + encodeURIComponent(code)
        })
        .then(response => response.text())
        .then(data => {
            if (data === "verified") {
                alert("✅ Email verified successfully!");
                isEmailVerified = true;
                nextBtns.forEach(btn => btn.disabled = false);
                document.getElementById("otp-container").style.display = "none";
            } else {
                const otpError = document.getElementById("code-error");
                otpError.style.display = "block";
                otpError.textContent = "Incorrect code";
            }
        })
        .catch(error => console.error("Error:", error));
    });

    document.getElementById("email").addEventListener("blur", function () {
    let email = this.value.trim();
    let emailInput = document.getElementById("email");
    let emailError = document.getElementById("email-error");
    let otpContainer = document.getElementById("otp-container");
    let otpSent = false; // ✅ متغير لمنع إرسال OTP أكثر من مرة

    if (email === "") {
        emailError.innerText = "";
        emailError.style.display = "none";
        emailInput.style.borderColor = "red";
        otpContainer.style.display = "none";
        nextBtns.forEach(btn => btn.disabled = true);
        return;
    }

    fetch("Mentee_Registration.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "verify_email=1&email=" + encodeURIComponent(email)
    })
    .then(response => response.text())
    .then(data => {
        if (data === "exists") {
            emailInput.style.borderColor = "red"; // 🔴 تلوين الإطار بالأحمر
            emailError.innerText = "This email is already registered.";
            emailError.style.color = "red"; // 🔴 تغيير لون النص ليكون أحمر
            emailError.style.display = "block";
            isEmailVerified = false; // ❌ البريد غير صالح للاستخدام
            otpSent = false; // ❌ عدم إرسال OTP
            otpContainer.style.display = "none"; // ❌ إخفاء حقل OTP
        } else if (data === "code_sent" && !otpSent) {
            emailInput.style.borderColor = "green"; // ✅ تلوين الإطار بالأخضر
            emailError.style.display = "none"; // ✅ إخفاء رسالة الخطأ
            otpContainer.style.display = "block"; // ✅ عرض حقل OTP
            alert("✅ OTP has been sent to your email!"); // ✅ إرسال OTP فقط لأول مرة
            otpSent = true; // ✅ منع إرسال OTP مرة أخرى
        }
    })
    .catch(error => console.error("Error:", error));
});

    
    const phoneInput = document.getElementById("phone");
const phoneError = document.getElementById("phone-error");

phoneInput.addEventListener("blur", function () {
    let phone = phoneInput.value.trim();
    if (phone === "") {
        phoneError.innerText = ""; // لا تعرض أي رسالة
        phoneError.style.display = "none";
        phoneInput.style.borderColor = "red";
        return;
    } else {
        phoneError.style.display = "none";
    }

    // ✅ Validate that the phone number consists of exactly 8 and 15 digits
     if (!/^\d{8,15}$/.test(phone)) {
        phoneError.innerText = "Phone number must be between 8 and 15 digits";
        phoneError.style.display = "block";
        phoneInput.style.borderColor = "red";
        return; // Stop execution if the number is invalid
    } else {
        phoneError.style.display = "none";
        phoneInput.style.borderColor = ""; // Reset border color
    }

    // ✅ Check phone number availability in the database
    fetch("Mentee_Registration.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "check_availability=phone&phone=" + encodeURIComponent(phone)
    })
    .then(response => response.text())
    .then(data => {
        if (data === "exists") {
            phoneError.innerText = "This phone number is already registered";
            phoneError.style.display = "block";
            phoneInput.style.borderColor = "red";
        } else {
            phoneError.style.display = "none";
            phoneInput.style.borderColor = "green";
        }
    })
    .catch(error => console.error("Error checking phone number:", error));
});


});


  </script>
</body>
</html>

