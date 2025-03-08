<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connection.php';
 
session_start();  // Start session to store OTP

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require 'vendor/autoload.php';

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
        $mail->Username   = 'gp.finally@gmail.com'; 
        $mail->Password   = 'rucf vidv sbut zeaj';
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


if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}


// ✅ Handle AJAX Requests for Email & Phone Validation (Before Submission)
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
    exit(); // ✅ Stop further execution for AJAX requests
}





// ✅ Now Process Form Submission (AFTER Handling AJAX)
$show_modal = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST["check_availability"])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $field_id = $_POST['field_id'];
    $experience_id = $_POST['experience_id'];
    $brief_description = mysqli_real_escape_string($conn, $_POST['brief_description']);

    // ✅ Check if email exists before inserting
    $email_query = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $email_query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $email_result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($email_result) > 0) {
        echo "<script>alert('❌This email is already registered. Please use another one.'); window.history.back();</script>";
        exit();
    }
    mysqli_stmt_close($stmt);

    // ✅ Check if phone exists before inserting
    $phone_query = "SELECT * FROM users WHERE phone_number = ?";
    $stmt = mysqli_prepare($conn, $phone_query);
    mysqli_stmt_bind_param($stmt, "s", $phone);
    mysqli_stmt_execute($stmt);
    $phone_result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($phone_result) > 0) {
        echo "<script>alert('❌ This phone number is already in use. Please use another one.'); window.history.back();</script>";
        exit();
    }
    mysqli_stmt_close($stmt);

    // ✅ File Uploads Handling (Profile Picture)
    $profile_pic_path = "";
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $profile_pic_name = time() . "_" . basename($_FILES['profile_picture']['name']);
        $profile_pic_tmp = $_FILES['profile_picture']['tmp_name'];
        $upload_dir = "uploads/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $profile_pic_path = $upload_dir . $profile_pic_name;
        move_uploaded_file($profile_pic_tmp, $profile_pic_path);
    }

    // ✅ File Uploads Handling (CV)
    $cv_name = $_FILES['cv']['name'];
    $cv_tmp = $_FILES['cv']['tmp_name'];
    $cv_path = "uploads/" . $cv_name;

    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
    }

    move_uploaded_file($cv_tmp, $cv_path);
    
    // ✅ File Upload Handling (Certificate)
// ✅ File Upload Handling (Certificates) - يدعم الملفات المتعددة الآن
$certificate_paths = [];

if (isset($_FILES['certificates']) && !empty($_FILES['certificates']['name'][0])) {
    $upload_dir = "uploads/";

    // ✅ تأكد أن المجلد موجود، إذا لم يكن موجودًا قم بإنشائه
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // ✅ معالجة كل الملفات التي تم رفعها
    foreach ($_FILES["certificates"]["tmp_name"] as $key => $tmp_name) {
        if ($_FILES["certificates"]["error"][$key] === 0) { // التأكد من عدم وجود خطأ في الرفع
            $certificate_name = time() . "_" . basename($_FILES["certificates"]["name"][$key]);
            $targetFilePath = $upload_dir . $certificate_name;

            // ✅ نقل الملف إلى مجلد `uploads/`
            if (move_uploaded_file($tmp_name, $targetFilePath)) {
                $certificate_paths[] = $targetFilePath;
            }
        }
    }
}

// ✅ تحويل مصفوفة المسارات إلى نص مفصول بفواصل لتخزينه في قاعدة البيانات
$certificate_path = !empty($certificate_paths) ? implode(",", $certificate_paths) : "";



    // ✅ Insert into Users Table
    $query = "INSERT INTO users (first_name, last_name, email, password, phone_number, role) 
              VALUES (?, ?, ?, ?, ?, 'mentor')";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sssss", $first_name, $last_name, $email, $password, $phone);

    if (mysqli_stmt_execute($stmt)) {
        $user_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // ✅ Insert into Mentors Table
        $query2 = "INSERT INTO mentors (user_id, email, cv_file, field_id, experience_id, status, profile_picture, brief_description, certificate_file) 
           VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?)";

        $stmt2 = mysqli_prepare($conn, $query2);
      mysqli_stmt_bind_param($stmt2, "isssssss", $user_id, $email, $cv_path, $field_id, $experience_id, $profile_pic_path, $brief_description, $certificate_path);

        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        $show_modal = true;
    }
}

// ✅ Fetch Fields and Experience Levels for Dropdowns
$field_query = "SELECT * FROM field";
$field_result = mysqli_query($conn, $field_query);

$experience_query = "SELECT * FROM years_of_experience";
$experience_result = mysqli_query($conn, $experience_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Mentor_Registration.css">
    <style>@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');</style>
    <script src="Mscript.js"></script>


    <title>Mentor Registration</title>
     <link rel="icon" type="image/png" href="images/favicon.png">
   
    
</head>

<body>
  <header>
        <div class="logo">
            <img src="images/Logo.png" alt="ASPIRA">
            <span class="logo-text">SPIRA</span>
        </div>
    </header>
   
    <div class="container">
        <div class="form-box">
            <h2 class="form-title">Sign up as a Mentor</h2>
            <div class="progress-bar">
                <div class="step active" id="step-1">1</div>
                <div class="line"></div>
                <div class="step inactive" id="step-2">2</div>
            </div>
            <form id="mentor-form" action="Mentor_Registration.php" method="POST" enctype="multipart/form-data">

                <!-- Step 1 -->
               
                <div class="step-content step-1">
                     <p id="form-error" class="error-message" style="display:none; color:red;">You didn't complete all fields</p>
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
                         <p class="error-message" id="email-error" style="color: red; display: none;">Email must be vaild</p>
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
                   
       <p id="form-error" class="error-message" style="display:none; color:red;">You didn't complete all fields</p>
                    <div class="input-group">
                    <label for="field">Field of Expertise</label>
                    <select id="field" name="field_id" required>
                        <option value="" disabled selected>Select your field</option>
                        <?php while ($row = mysqli_fetch_assoc($field_result)) { ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo $row['field_name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="experience">Years of Experience</label>
                    <select id="experience" name="experience_id" required>
                        <option value="" disabled selected>Select experience level</option>
                        <?php while ($row = mysqli_fetch_assoc($experience_result)) { ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo $row['experience_level']; ?></option>
                        <?php } ?>
                    </select>
                </div>


<!-- Profile Picture Upload -->
<div class="input-group">
    <label for="profile-picture">Upload Personal Picture <small>(Allowed: PNG, JPG, JPEG)</small></label>
    <input type="file" id="profile-picture" name="profile_picture" accept="image/png, image/jpeg, image/jpg" required>
</div>

<!-- CV Upload -->
<div class="input-group">
    <label for="cv">Upload CV <small>(Allowed: PDF, DOC, DOCX)</small></label>
    <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx" required>
</div>

<!-- Certificate Upload -->
<div class="input-group">
        <label for="certificates">Upload Certificates <small>(Allowed: PDF, PNG, JPG, JPEG)</small></label>
        
        <!-- Hidden File Input -->
        <input type="file" id="certificate-input" name="certificates[]" accept=".pdf, image/png, image/jpeg, image/jpg" multiple hidden>
        
        <!-- Drag & Drop Zone -->
        <div id="drop-zone" class="drop-zone" onclick="document.getElementById('certificate-input').click();">
            <p>Drop files here or click to browse</p>
        </div>
        
        <!-- List of Selected Files -->
        <ul id="file-list"></ul>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // ✅ عناصر رفع الملفات
            const certificateInput = document.getElementById("certificate-input");
            const dropZone = document.getElementById("drop-zone");
            const fileList = document.getElementById("file-list");

            let selectedFiles = []; // تخزين الملفات المختارة

            if (certificateInput && dropZone && fileList) {
                console.log("✅ Elements found, initializing file upload logic.");

                // ✅ عند اختيار ملفات من المتصفح
                certificateInput.addEventListener("change", function (event) {
                    addFiles(Array.from(event.target.files));
                });

                // ✅ السحب والإفلات للملفات
                dropZone.addEventListener("dragover", function (event) {
                    event.preventDefault();
                    dropZone.style.background = "#e0e0e0";
                });

                dropZone.addEventListener("dragleave", function () {
                    dropZone.style.background = "#f8f8f8";
                });

                dropZone.addEventListener("drop", function (event) {
                    event.preventDefault();
                    dropZone.style.background = "#f8f8f8";
                    addFiles(Array.from(event.dataTransfer.files));
                });

                // ✅ إضافة الملفات إلى القائمة وتحديث الإدخال
                function addFiles(newFiles) {
                    newFiles.forEach(file => {
                        if (!selectedFiles.some(existingFile => existingFile.name === file.name)) {
                            selectedFiles.push(file);
                        }
                    });
                    updateFileInput();
                }

                // ✅ تحديث قائمة الملفات في `input` وعرضها في `file-list`
                function updateFileInput() {
                    let dt = new DataTransfer();
                    selectedFiles.forEach(file => dt.items.add(file));
                    certificateInput.files = dt.files;

                    fileList.innerHTML = "";
                    selectedFiles.forEach((file, index) => {
                        let listItem = document.createElement("li");
                        listItem.className = "file-item";
                        listItem.innerHTML = `
                            <span>${file.name} (${(file.size / 1024).toFixed(2)} KB)</span>
                            <button type="button" class="remove-btn" data-index="${index}">Remove</button>
                        `;
                        fileList.appendChild(listItem);
                    });

                    // ✅ إضافة وظيفة الإزالة للأزرار
                    document.querySelectorAll(".remove-btn").forEach(button => {
                        button.addEventListener("click", function () {
                            let index = parseInt(this.getAttribute("data-index"));
                            removeFile(index);
                        });
                    });
                }

                // ✅ إزالة ملف من القائمة
                function removeFile(index) {
                    selectedFiles.splice(index, 1);
                    updateFileInput();
                }
            } else {
                console.error("❌ Elements not found! Make sure certificate-input, drop-zone, and file-list exist.");
            }
        });
    </script>



                    <!-- ✅ New Brief Description Field -->
                    
<div class="input-group">
    <label for="brief-description">Brief Description <small>(This will appear on your profile)</small></label>
    <textarea id="brief-description" name="brief_description" rows="3" required></textarea>
</div>

                    
                    <div class="btn-container">
                        <button type="button" class="btn prev-step">Back</button>
                        <button type="submit" class="btn">Sign up</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
   <?php if ($show_modal): ?>
    <div id="confirmation-modal" class="modal">
        <div class="modal-content">
            <img src="https://cdn-icons-png.flaticon.com/512/5610/5610944.png" alt="Success" class="modal-icon">
            <h2>Thank You!</h2>
            <p>Your details have been successfully wait for admin to accept you.</p>
            <button class="btn modal-btn">OK</button>
        </div>
    </div>
    <script>
        document.getElementById('confirmation-modal').style.display = 'flex';
    </script>
<?php endif; ?>

</body>
</html>