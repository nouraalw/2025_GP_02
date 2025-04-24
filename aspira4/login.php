<?php
session_start();
include 'db_connection.php';

$login_error = ""; // Store error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //$email = $_POST['email'];
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $password = $_POST['password'];
    
    $show_mentor_pending_modal = false;

    // Debug: Print email and password
    //echo "Email entered: " . $email . "<br>";
   // echo "Password entered: " . $password . "<br>";

    // Check if user is an admin
    $query = "SELECT * FROM admins WHERE email='$email'";
    $result = mysqli_query($conn, $query);
    $admin = mysqli_fetch_assoc($result);



    // ❌ DO NOT OVERRIDE $password HERE!
//$hashed_password = $admin['password']; // Get the stored hash from the database
if ($admin && password_verify($password, $admin['password'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_email'] = $admin['email'];
    header("Location: admin_panel.php");
    exit();
}
// If neither admin nor user login is successful, show a generic error message
$login_error = "Invalid email or password";


    

    
if ($admin && password_verify($password, $admin['password'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_email'] = $admin['email'];
    header("Location: admin_panel.php");
    exit();
}

// Now, correctly check if the user is a mentor/mentee
$query = "SELECT * FROM users WHERE email='$email'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_role'] = $user['role']; // ✅ تخزين الدور في الجلسة

    // ✅ تخزين الدور في localStorage عبر JavaScript
    echo "<script>
            localStorage.setItem('userRole', '" . $user['role'] . "');
    </script>";

    if ($user['role'] == 'mentor') {
        // ✅ جلب حالة المنتور من جدول mentors
        $mentor_query = "SELECT status FROM mentors WHERE user_id=" . $user['user_id'];
        $mentor_result = mysqli_query($conn, $mentor_query);
        $mentor = mysqli_fetch_assoc($mentor_result);

        if ($mentor['status'] == 'approved') {
            $_SESSION['mentor_logged_in'] = true;
            echo "<script>window.location.href = 'addPrivateSession.php';</script>";
            exit();
        } elseif ($mentor['status'] == 'pending') {
            echo "<script>window.location.href = 'login.php?status=pending';</script>";
            exit();
        } elseif ($mentor['status'] == 'rejected') {
            echo "<script>window.location.href = 'login.php?status=rejected';</script>";
            exit();
        }
    } elseif ($user['role'] == 'mentee') {
        $_SESSION['mentee_logged_in'] = true;
        echo "<script>window.location.href = 'MentorCenter.php';</script>";
        exit();
    }
} else {
    $login_error = "Invalid email or password"; // Store error locally
}

}

    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
    
</head>


<body>
    
     <header>
        <div class="logo">
            <div class="logo">
    <img src="images/logo.png" alt="ASPIRA">
    <span class="logo-text" >SPIRA</span>
</div>

        </div>
       
        
    </header>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
  /* Color Palette */
:root {
    --primary-bg: #211742;
    --form-bg: #ffffff;
    --input-bg: #e5e5e5;
    --input-border: #b3b3b3;
    --button-color: #4b398e;
    --text-color:  #211742;
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
    display: flex;
 
    
}
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

.container {
     background: rgba(255, 255, 255, 0.95);
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    width: 80%;
    max-width: 400px;
    
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

/* Inputs */
.input-group {
    margin-bottom: 12px;
}

.input-group input,
.input-group textarea,
.input-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid var(--input-border);
    border-radius: 5px;
}

/* Error and Success Styling */
.error-message {
    color: red;
    font-size: 12px;
    display: none;
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



/* Buttons */
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
    background: #a576ff;
}

.hidden {
    display: none;
}
/* Center the form title */
.form-title {
    text-align: center; /* Centers text */
    font-size: 22px; /* Adjust font size */
    color: var(--primary-bg); /* Use the theme color */
    display: block; /* Ensure it's treated as a block element */
    margin-bottom: 20px; /* Add spacing below */
}

/* Space between buttons */
.btn-container {
    display: flex;
    flex-direction: column;
    gap: 15px; /* Adjust this value for more spacing */
    margin-top: 15px;
}

/* "Already have an account?" Styling */
.already-account {
    text-align: center;
    font-size: 14px;
    color: #a576ff;
    margin-top: 10px;
}

/* Log in Link Styling (Underlined & Clickable) */
.already-account .login-link {
    color: #4b398e;
    font-weight: bold;
    text-decoration: underline; /* Underline by default */
    cursor: pointer;
}

.already-account .login-link:hover {
    text-decoration: underline;
    color: #3a2c76; /* Slightly darker on hover */
}
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    width: 300px;
}

.modal-icon {
    width: 50px;
    height: 50px;
}

.modal-btn {
    background-color: #ffc107;
    color: black;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 15px;
}

.modal-btn:hover {
    background-color: #e0a800;
}

/* Rejected Button */
#mentor-rejected-modal .modal-btn {
    background-color: #dc3545;
    color: white;
}

#mentor-rejected-modal .modal-btn:hover {
    background-color: #c82333;
}
  .error-message {
            color: red;
            font-size: 14px;
            margin-bottom: 5px;
            text-align: left;
            display: none; /* Initially hidden */
        }

    </style>
    <div class="container">
        <div class="form-box">
            <h2 class="form-title">Log in</h2>

            <form id="login-form" action="login.php" method="POST">
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <!-- ✅ Error message is placed inside this input-group under the Password field -->
                    <p id="error-message" class="error-message" style="<?php echo !empty($login_error) ? 'display:block;' : 'display:none;'; ?>">
                       <?php echo !empty($login_error) ? $login_error : ''; ?>
                      </p>

                </div>
 <p class="already-account"><a href="forgot_password.php" class="login-link">Forgot Password?</a></p>
                <button type="submit" class="btn">Log in</button>
            </form>
            
        </div>
    </div>

    <!-- Mentor Pending Approval Pop-up -->
    <div id="mentor-pending-modal" class="modal">
        <div class="modal-content">
            <img src="https://cdn-icons-png.flaticon.com/512/9625/9625452.png" alt="Pending" class="modal-icon">
            <h2>Pending Approval</h2>
            <p>Your request is still pending admin approval.</p>
            <button class="btn modal-btn" onclick="redirectToHome()">OK</button>
        </div>
    </div>

    <div id="mentor-rejected-modal" class="modal">
        <div class="modal-content">
            <img src="https://cdn-icons-png.flaticon.com/512/458/458594.png" alt="Rejected" class="modal-icon">
            <h2>Sorry</h2>
            <p>We regret to inform you that your request was not accepted.</p>
            <button class="btn modal-btn" onclick="redirectToLogin()">OK</button>
        </div>
    </div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function redirectToHome() {
            window.location.href = 'homepage.html'; // Redirect to home page
        }

        function redirectToLogin() {
            window.location.href = 'homepage.html'; // Redirect back to home page
        }

        // Show modal based on URL status parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'pending') {
            document.getElementById('mentor-pending-modal').style.display = 'flex';
        } else if (urlParams.get('status') === 'rejected') {
            document.getElementById('mentor-rejected-modal').style.display = 'flex';
        }
         document.addEventListener("DOMContentLoaded", function () {
            const errorMessage = document.getElementById("error-message");

            // Show error message if exists
            if (errorMessage.innerText.trim() !== "") {
                errorMessage.style.display = "block";
            }

            document.getElementById("login-form").addEventListener("submit", function (event) {
                let email = document.getElementById("email").value.trim();
                let password = document.getElementById("password").value.trim();
                
                if (email === "" || password === "") {
                    errorMessage.innerText = "Both fields are required!";
                    errorMessage.style.display = "block";
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
