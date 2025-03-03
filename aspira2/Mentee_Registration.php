<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

session_start();

// Email verification and OTP sending
if (isset($_POST['verify_email']) && $_POST['verify_email'] == 1) {
    $email = $_POST['email'];
    
    // Generate a 4-digit OTP
    $otp = sprintf("%04d", rand(0, 9999));
    
    // Store OTP in session for verification
    $_SESSION['email_otp'] = $otp;
    $_SESSION['email_to_verify'] = $email;
    
   $mail = new PHPMailer(true);
try {
    // Set mailer to use SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; 
    $mail->SMTPAuth   = true;
    $mail->Username   = 'gp.finally@gmail.com'; 
            $mail->Password   = 'rucf vidv sbut zeaj';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            

            $mail->setFrom('gp.finally@gmail.com', 'Aspira Support'); 
    $mail->addAddress($email);

    // Email content
    $mail->Subject = 'Your Verification Code';
    $mail->Body    = "Your OTP code is: $otp";

    $mail->send();
    echo "code_sent";
} catch (Exception $e) {
    echo "error: " . $mail->ErrorInfo; // 🔹 This will show the exact SMTP error
}

    exit;
}

// OTP verification
if (isset($_POST['verify_code']) && $_POST['verify_code'] == 1) {
    $entered_code = $_POST['code'];
    
    // Check if OTP matches
    if (isset($_SESSION['email_otp']) && $entered_code == $_SESSION['email_otp']) {
        // Clear the OTP after successful verification
        unset($_SESSION['email_otp']);
        echo "verified";
    } else {
        echo "invalid";
    }
    exit;
}

// Email availability check
if (isset($_POST['check_availability']) && $_POST['check_availability'] == 'email') {
    $email = $_POST['email'];
    
    // Replace with your actual database connection and check
    // This is a placeholder logic
    $existing_emails = ['test@gmail.com', 'example@gmail.com'];
    
    if (in_array($email, $existing_emails)) {
        echo "exists";
    } else {
        echo "available";
    }
    exit;
}

// Phone number availability check
if (isset($_POST['check_availability']) && $_POST['check_availability'] == 'phone') {
    $phone = $_POST['phone'];
    
    // Replace with your actual database connection and check
    // This is a placeholder logic
    $existing_phones = ['1234567890', '9876543210'];
    
    if (in_array($phone, $existing_phones)) {
        echo "exists";
    } else {
        echo "available";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentee Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            <h2 class="form-title">Sign up as a Mentee</h2>
            <div class="progress-bar">
                <div class="step active" id="step-1">1</div>
                <div class="line"></div>
                <div class="step inactive" id="step-2">2</div>
            </div>
            <form id="mentor-form" action="Mentee_Registration.php" method="POST" enctype="multipart/form-data">
                <!-- Step 1 -->
                <div class="step-content step-1">
                    <div class="input-group">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="first_name" required>
                        <p class="error-message">Invalid name. Use letters only, and avoid repeating the same letter multiple times.</p>
                    </div>
                    <div class="input-group">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="last_name" required>
                        <p class="error-message">Invalid name. Use letters only, and avoid repeating the same letter multiple times.</p>
                    </div>
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                        <p class="error-message" id="email-error">Email must contain "@gmail.com"</p>
                    </div>

                    <div class="input-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" required>
                        <p class="error-message" id="phone-error">This phone number is already registered.</p>
                    </div>

                    <!-- Password Input -->
                    <div class="input-group password-group">
                        <label for="password">Password</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" required onkeyup="validatePassword()">
                            <span class="toggle-password" onclick="togglePassword()">👁</span>
                        </div>
                        
                        <!-- Password Rules Display -->
                        <div id="password-rules">
                            <p id="rule-length" class="rule">- Must be at least 8 characters</p>
                            <p id="rule-uppercase" class="rule">- Must include one uppercase letter</p>
                            <p id="rule-lowercase" class="rule">- Must include one lowercase letter</p>
                            <p id="rule-number" class="rule">- Must include one number</p>
                            <p id="rule-special" class="rule">- Must include one special character (@, #, $, etc.)</p>
                        </div>
                    </div>

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

    <!-- OTP Verification Modal -->
    <div id="otp-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Email Verification</h2>
            <p>Please enter the 4-digit verification code sent to your email</p>
            <div class="otp-input-container">
                <input type="text" class="otp-input" maxlength="1" autofocus>
                <input type="text" class="otp-input" maxlength="1">
                <input type="text" class="otp-input" maxlength="1">
                <input type="text" class="otp-input" maxlength="1">
            </div>
            <div class="otp-buttons">
                <button class="btn otp-btn" id="verify-otp-btn">Verify</button>
                <button class="btn otp-resend-btn" id="resend-otp-btn">Resend Code</button>
            </div>
            <p id="otp-error-message" class="error-message" style="text-align: center; margin-top: 10px;"></p>
        </div>
    </div>

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
            display: flex;
            align-items: center;
        }

        .logo img {
            width: 50px;
            height: auto;
            margin-right: 5px;
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

        document.addEventListener("DOMContentLoaded", function () {
            const nextBtns = document.querySelectorAll(".next-step");
            const prevBtns = document.querySelectorAll(".prev-step");
            const step1s = document.querySelectorAll(".step-1");
            const step2s = document.querySelectorAll(".step-2");
            const steps = document.querySelectorAll(".progress-bar .step");
            const emailInput = document.getElementById("email");
            const phoneInput = document.getElementById("phone");
            
            // OTP Modal Elements
            const otpModal = document.getElementById("otp-modal");
            const otpInputs = document.querySelectorAll('.otp-input');
            const verifyOtpBtn = document.getElementById("verify-otp-btn");
            const resendOtpBtn = document.getElementById("resend-otp-btn");
            const otpErrorMessage = document.getElementById("otp-error-message");

            // Name validation function
            function validateName(nameField) {
                const namePattern = /^[A-Za-z]{2,}(?: [A-Za-z]+)*$/;
                const repeatedPattern = /^(.)\1{2,}$/;
                
                if (!namePattern.test(nameField.value) || repeatedPattern.test(nameField.value)) {
                    nameField.style.borderColor = "red";
                    nameField.nextElementSibling.style.display = "block";
                    return false;
                } else {
                    nameField.style.borderColor = "green";
                    nameField.nextElementSibling.style.display = "none";
                    return true;
                }
            }

            // Step 1 validation
            function validateStep1(form) {
                let valid = true;
                const firstName = form.querySelector("#first-name");
                const lastName = form.querySelector("#last-name");
                const email = form.querySelector("#email");
                const password = form.querySelector("#password");

                if (!validateName(firstName)) valid = false;
                if (!validateName(lastName)) valid = false;

                if (!email.value.includes("@gmail.com")) {
                    email.style.borderColor = "red";
                    document.getElementById("email-error").style.display = "block";
                    valid = false;
                } else {
                    email.style.borderColor = "green";
                    document.getElementById("email-error").style.display = "none";
                }

                const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#const emailInput = ]).{8,}$/;
                if (!password.value.match(passwordPattern)) {
                    password.style.borderColor = "red";
                    valid = false;
                } else {
                    password.style.borderColor = "green";
                }

                return valid;
            }

            // Handle next button click
            nextBtns.forEach((btn, index) => {
                btn.addEventListener("click", function () {
                    const form = btn.closest("form");
                    if (validateStep1(form)) {
                        step1s[index].classList.add("hidden");
                        step2s[index].classList.remove("hidden");
                        steps[index * 2].classList.add("completed");
                        steps[index * 2].innerHTML = "✓";
                        steps[index * 2 + 1].classList.add("active");
                    }
                });
            });

            // Handle previous button click
            prevBtns.forEach((btn, index) => {
                btn.addEventListener("click", function () {
                    step2s[index].classList.add("hidden");
                    step1s[index].classList.remove("hidden");
                    steps[index * 2].classList.remove("completed");
                    steps[index * 2].innerHTML = "1";
                    steps[index * 2 + 1].classList.remove("active");
                });
            });

            // Email verification - OTP functionality
            emailInput.addEventListener("blur", function () {
                let email = emailInput.value.trim();
                if (email.includes("@gmail.com")) {
                    // Check if email exists 
                    fetch("Mentee_Registration.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: "check_availability=email&email=" + encodeURIComponent(email)
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data === "exists") {
                            document.getElementById("email-error").innerText = "This email is already registered.";
                            document.getElementById("email-error").style.display = "block";
                            emailInput.style.borderColor = "red";
                        } else {
                            document.getElementById("otp-modal").style.display = "flex";
                            // Focus on first OTP input
                            document.querySelector('.otp-input').focus();
                        }
                    })
                    .catch(error => console.error("Error:", error));
                }
            });
            
            // Auto-focus next OTP input
            otpInputs.forEach((input, index) => {
                input.addEventListener('input', function() {
                    if (this.value.length === this.maxLength) {
                        if (index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        }
                    }
                });
                
                // Allow backspace to go to previous input
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value.length === 0 && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                });
            });
            
            // Send OTP when modal first appears
            const sendOTP = function() {
                let email = emailInput.value.trim();
                fetch("Mentee_Registration.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "verify_email=1&email=" + encodeURIComponent(email)
                })
                .then(response => response.text())
                .then(data => {
                    if (data === "code_sent") {
                        // OTP sent successfully
                        otpErrorMessage.innerText = "✅ OTP sent to your email!";
                        otpErrorMessage.style.color = "green";
                        otpErrorMessage.style.display = "block";
                    } else {
                        // Error sending OTP
                        otpErrorMessage.innerText = "❌ Error sending OTP. Please try again.";
                        otpErrorMessage.style.color = "red";
                        otpErrorMessage.style.display = "block";
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    otpErrorMessage.innerText = "❌ Error sending OTP. Please try again.";
                    otpErrorMessage.style.color = "red";
                    otpErrorMessage.style.display = "block";
                });
            };
            
            // Trigger OTP sending when modal first appears
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === "style" && 
                        otpModal.style.display === "flex") {
                        sendOTP();
                    }
                });
            });
            observer.observe(otpModal, { attributes: true });
            
            // Resend OTP button
            resendOtpBtn.addEventListener("click", function() {
                sendOTP();
                
                // Clear OTP inputs
                otpInputs.forEach(input => {
                    input.value = "";
                });
                otpInputs[0].focus();
            });
            
            // Verify OTP
            verifyOtpBtn.addEventListener("click", function() {
                let otpCode = "";
                otpInputs.forEach(input => {
                    otpCode += input.value;
                });
                
                if (otpCode.length !== 4) {
                    otpErrorMessage.innerText = "Please enter all 4 digits";
                    otpErrorMessage.style.color = "red";
                    otpErrorMessage.style.display = "block";
                    return;
                }
                
                fetch("Mentee_Registration.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "verify_code=1&code=" + encodeURIComponent(otpCode)
                })
                .then(response => response.text())
                .then(data => {
                    if (data === "verified") {
                        // Close modal
                        otpModal.style.display = "none";
                        
                        // Mark email as verified visually
                        emailInput.style.borderColor = "green";
                        
                        // Enable next button
                        document.querySelector(".btn.next-step").disabled = false;
                        
                        // Show success message
                        const successMessage = document.createElement("p");
                        successMessage.innerText = "✅ Email verified successfully!";
                        successMessage.style.color = "green";
                        successMessage.style.fontSize = "12px";
                        successMessage.style.margin = "5px 0";
                        emailInput.parentNode.appendChild(successMessage);
                    } else {
                        otpErrorMessage.innerText = "❌ Incorrect code. Please try again.";
                        otpErrorMessage.style.color = "red";
                        otpErrorMessage.style.display = "block";
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    otpErrorMessage.innerText = "❌ Error verifying code. Please try again.";
                    otpErrorMessage.style.color = "red";
                    otpErrorMessage.style.display = "block";
                });
            });

            // Phone number validation
            phoneInput.addEventListener("blur", function () {
                let phone = phoneInput.value.trim();
                if (phone !== "") {
                    fetch("Mentee_Registration.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: "check_availability=phone&phone=" + encodeURIComponent(phone)
                    })
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById("phone-error").style.display = data === "exists" ? "block" : "none";
                    });
                }
            });
        });
    </script>
</body>

</html>