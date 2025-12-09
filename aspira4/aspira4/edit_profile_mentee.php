<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include 'db_connection.php';
session_start();

if (!$conn) { die("Database connection failed: " . mysqli_connect_error()); }
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = (int)$_SESSION['user_id'];

// ================= PHPMailer (OTP) =================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require 'PHPMailer-master/PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/PHPMailer-master/src/SMTP.php';

function send_otp($toEmail, $otp) {
  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'gp.finally@gmail.com';
    $mail->Password = 'rucf vidv sbut zeaj'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('gp.finally@gmail.com', 'ASPIRA Team');
    $mail->addAddress($toEmail);
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP Verification Code';
    $mail->Body = "<p>Your verification code is: <strong>{$otp}</strong></p>
    <p><br><br>Visit our website: <a href='https://aspira.sbs/'>ASPIRA</a></p>";
    $mail->send();
    return true;
  } catch (Exception $e) { return false; }
}

// ================= Fetch user + role data =================
// NOTE: mentees no prifile picture but mentors have
$sql = "
SELECT 
  u.first_name, u.last_name, u.email, u.role,
  m.brief_description AS m_brief,
  me.student_status AS me_status, me.interests AS me_interests,
  m.profile_picture AS avatar
FROM users u
LEFT JOIN mentors m ON m.user_id = u.user_id
LEFT JOIN mentees me ON me.user_id = u.user_id
WHERE u.user_id = ?
LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result(
  $cur_fname, $cur_lname, $cur_email, $cur_role,
  $m_brief, $me_status, $me_interests, $cur_avatar
);
$has = $stmt->fetch();
$stmt->close();

if (!$has) { die("User not found."); }

$cur_role = strtolower(trim($cur_role));

if ($cur_role === 'mentor' && !empty($cur_avatar)) {

} else {
  $cur_avatar = 'images/person.png'; // ديفولت للمينتي وأي حالة بدون صورة
}

// ===== Flash success  (redirect) =====
$flash_success = (isset($_GET['saved']) && $_GET['saved'] == '1');

// ================= AJAX: verify_code =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
  
  $entered = preg_replace('/\D+/', '', (string)($_POST['code'] ?? ''));

  if (!isset($_SESSION['otp_code'])) { echo "session_expired"; exit; }

  if ($entered === (string)$_SESSION['otp_code']) {
    
    unset($_SESSION['otp_code'], $_SESSION['otp_last_time']);
    echo "verified";
  } else {
    echo "invalid";
  }
  exit;
}

// ================= AJAX: verify_email (unique + send OTP) =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_email'])) {
  $email = trim($_POST['email'] ?? '');
  if ($email === '') { echo 'invalid'; exit; }

  $sql = "SELECT 1 FROM users WHERE email = ? AND user_id <> ? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("si", $email, $user_id);
  $stmt->execute();
  $stmt->store_result();
  if ($stmt->num_rows > 0) { $stmt->close(); echo "exists"; exit; }
  $stmt->close();

  // Rate-limit: 60 sec no send again
  $now       = time();
  $lastEmail = $_SESSION['otp_email'] ?? '';
  $lastTime  = (int)($_SESSION['otp_last_time'] ?? 0);

  if ($lastEmail === $email && ($now - $lastTime) < 60 && !empty($_SESSION['otp_code'])) {
    echo "already_sent";
    exit;
  }

  $otp = random_int(100000, 999999);
  $_SESSION['otp_code']      = (string)$otp;
  $_SESSION['otp_email']     = $email;
  $_SESSION['otp_last_time'] = $now;

  echo send_otp($email, $otp) ? "code_sent" : "error";
  exit;
}

// ================= AJAX: check_availability (email) =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_availability'])) {
  $resp = "available";
  if (($_POST['check_availability'] ?? '') === 'email') {
    $email = trim($_POST['email'] ?? '');
    $sql = "SELECT 1 FROM users WHERE email=? AND user_id<>? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute(); $stmt->store_result();
    if ($stmt->num_rows > 0) $resp = "exists";
    $stmt->close();
  }
  echo $resp; exit;
}

// ================= SAVE: Mentor =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mentor'])) {
  $fname = trim($_POST['first_name'] ?? '');
  $lname = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $brief = trim($_POST['brief_description'] ?? '');
  $email_verified = trim($_POST['email_verified'] ?? '0') === '1';

  if ($fname==='' || $lname==='' || $email==='') {die("Please fill all required fields.");}
  if (strcasecmp($email, $cur_email) !== 0 && !$email_verified) { die("Email change requires OTP verification."); }

  // uniqueness
  $sql = "SELECT 1 FROM users WHERE email=? AND user_id<>? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("si", $email, $user_id);
  $stmt->execute(); $stmt->store_result();
  if ($stmt->num_rows>0){ $stmt->close(); die("Email already in use."); }
  $stmt->close();

 // --- File Upload (profile_picture) ---
$profile_pic_path = null;

if (!empty($_FILES['profile_picture']['name']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $tmp  = $_FILES['profile_picture']['tmp_name'];
    $size = (int)$_FILES['profile_picture']['size'];

    // max 5MB
    if ($size > 5 * 1024 * 1024) {
        // if pic large
    } else {
        // check real pic
        $imgInfo = @getimagesize($tmp);
        if ($imgInfo !== false) {
            $mime = $imgInfo['mime'] ?? '';
            // allowd
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];
            if (isset($allowed[$mime])) {
               
                $upload_rel    = 'uploads/';                     
                $upload_dir_fs = __DIR__ . '/' . $upload_rel; 

                if (!is_dir($upload_dir_fs)) {
                    mkdir($upload_dir_fs, 0777, true);
                }

                //uniqe
                $ext      = $allowed[$mime];
                $randPart = bin2hex(random_bytes(8));
                $basename = time() . '_' . $randPart . '.' . $ext;

                $dest_fs  = $upload_dir_fs . $basename;

                if (move_uploaded_file($tmp, $dest_fs)) {
                    @chmod($dest_fs, 0644); // صلاحيات مناسبة
                    $profile_pic_path = $upload_rel . $basename; // يُحفظ في قاعدة البيانات
                }
            }
        }
    }
}
  $conn->begin_transaction();
  try {
    // users
   $sql = "UPDATE users 
        SET first_name = ?, last_name = ?, email = ?
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $fname, $lname, $email, $user_id);
    $stmt->execute(); $stmt->close();

    // mentors
if ($profile_pic_path) {
  $sql = "UPDATE mentors SET brief_description=?, profile_picture=? WHERE user_id=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ssi", $brief, $profile_pic_path, $user_id);
} else {
  $sql = "UPDATE mentors SET brief_description=? WHERE user_id=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("si", $brief, $user_id);
}
$stmt->execute(); $stmt->close();


    //  keep mentor email in mentors table if column exists
    if ($conn->query("SHOW COLUMNS FROM mentors LIKE 'email'")->num_rows === 1) {
      $sql = "UPDATE mentors SET email=? WHERE user_id=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("si", $email, $user_id);
      $stmt->execute(); $stmt->close();
    }

    $conn->commit();
    $_SESSION['first_name']=$fname; $_SESSION['last_name']=$lname; $_SESSION['email']=$email;
    header("Location: edit_profile_mentee.php?saved=1"); exit;
  } catch (Throwable $e) {
    $conn->rollback(); die("Save failed: ".$e->getMessage());
  }
}

// ================= SAVE: Mentee =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mentee'])) {
  $fname = trim($_POST['first_name'] ?? '');
  $lname = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $status = trim($_POST['status'] ?? '');
  $interests = trim($_POST['description'] ?? '');
  $email_verified = trim($_POST['email_verified'] ?? '0') === '1';

  if ($fname==='' || $lname==='' || $email==='' || $status==='' || $interests==='') {
    die("Please fill all required fields.");
  }
  if (strcasecmp($email, $cur_email) !== 0 && !$email_verified) { die("Email change requires OTP verification."); }

  // uniqueness
  $sql = "SELECT 1 FROM users WHERE email=? AND user_id<>? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("si", $email, $user_id);
  $stmt->execute(); $stmt->store_result();
  if ($stmt->num_rows>0){ $stmt->close(); die("Email already in use."); }
  $stmt->close();

  $conn->begin_transaction();
  try {
    // users 
    $sql = "UPDATE users 
        SET first_name = ?, last_name = ?, email = ?
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $fname, $lname, $email, $user_id);
    $stmt->execute(); $stmt->close();

    // mentees
    $sql = "UPDATE mentees SET student_status=?, interests=? WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $status, $interests, $user_id);
    $stmt->execute(); $stmt->close();

    //  keep mentee email in table if column exists
    if ($conn->query("SHOW COLUMNS FROM mentees LIKE 'email'")->num_rows === 1) {
      $sql = "UPDATE mentees SET email=? WHERE user_id=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("si", $email, $user_id);
      $stmt->execute(); $stmt->close();
    }

    $conn->commit();
    $_SESSION['first_name']=$fname; $_SESSION['last_name']=$lname; $_SESSION['email']=$email;
    header("Location: edit_profile_mentee.php?saved=1"); exit;
  } catch (Throwable $e) {
    $conn->rollback(); die("Save failed: ".$e->getMessage());
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Edit Profile — ASPIRA</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="icon" type="image/png" href="images/favicon.png" />

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
:root{--primary:#4b398e;--accent:#a576ff}
body{background:#f8f8f8}

header{display:flex;justify-content:space-between;align-items:center;padding:15px 50px;background:#fff;border-bottom:.4px solid rgba(0,0,0,.05);position:sticky;top:0;z-index:5}
   .logo {
            font-size: 24px;
            font-weight: bold;
            color: #a576ff;
            display: flex;
            align-items: center;
            gap: 2px;
        }
.logo img{width:24px;height:auto}
.logo-text{font-size:22px;font-weight:700;color:#a576ff}
nav{display:flex;gap:30px}
nav a{color:#000;text-decoration:none;font-weight:500;padding-bottom:5px;border-bottom:2px solid transparent;transition:.2s}
nav a:hover,nav a.active{color:var(--primary);border-bottom:2px solid var(--primary)}
.logout-btn{text-decoration:none;padding:8px 20px;background:#fff;color:#2D2D69;border:2px solid #2D2D69;border-radius:20px;font-weight:700}
.logout-btn:hover{background:#211742;color:#fff;border-color:#211742}

/* Profile menu */
.profile-menu{position:relative;display:inline-block}
.profile-pic{width:45px;height:45px;border-radius:50%;cursor:pointer;object-fit:cover}
.dropdown{display:none;position:absolute;right:0;background:#fff;box-shadow:0px 4px 6px rgba(0,0,0,0.2);list-style:none;padding:0;margin:0;border-radius:8px;min-width:180px}
.dropdown li{padding:10px 20px}
.dropdown li a{color:#211742;text-decoration:none;display:block}
.dropdown li:hover{background:#f2f2f2}
.profile-menu:hover .dropdown{display:block}

/* Page content */
.content{max-width:1100px;margin:22px auto;padding:0 16px}
.card{background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1);padding:22px}
h2{color:var(--primary);margin-bottom:10px}
label{display:block;color:var(--primary);margin-top:12px}
input,textarea,select{width:100%;padding:11px;border:1px solid #b3b3b3;border-radius:6px;background:#f5f5f5}
textarea{min-height:90px}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:800px){.grid-2{grid-template-columns:1fr}}
.btn{background:var(--primary);color:#fff;border:none;border-radius:6px;padding:11px 18px;font-weight:700;cursor:pointer;transition:.2s;margin-top:14px}
.btn:hover{background:var(--accent)}
.note{font-size:12px;color:#666;margin-top:6px}
.otp-inline{display:none;margin-top:8px}
.error-message{color:#b00020;font-size:12px;margin-top:6px;display:none}

/* ====== style success msg ====== */
.alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:6px;padding:10px 12px;margin-bottom:12px}
hr.soft{margin:16px 0;border:none;border-top:1px solid #eee}
@media(max-width:900px){.grid{grid-template-columns:1fr}}
.pfp-wrap{width:120px;margin:10px auto 20px;position:relative}
.pfp-img{width:120px;height:120px;border-radius:50%;object-fit:cover;display:block;margin:0 auto}
.pfp-edit{position:absolute;right:6px;bottom:6px;border:none;background:#fff;border-radius:50%;width:34px;height:34px;box-shadow:0 2px 6px rgba(0,0,0,.2);cursor:pointer;font-size:16px;line-height:34px}

/* زر الهامبرجر */
.hamburger {
  display: none;
  font-size: 26px;
  cursor: pointer;
  color: #4b398e;
}

/* وضع الموبايل */
@media (max-width: 768px) {
  nav {
    position: fixed;
    top: 0;
    right: -250px; /* مخفي يمين */
    width: 220px;
    height: 100%;
    background: #fff;
    flex-direction: column;
    padding: 60px 20px;
    gap: 20px;
    box-shadow: -2px 0 8px rgba(0,0,0,0.1);
    transition: right 0.3s ease;
    z-index: 999;
  }

  nav a {
    font-size: 18px;
    border-bottom: 1px solid #eee;
    padding-bottom: 8px;
  }

  .hamburger {
    display: block; /* يظهر الزر */
  }

  nav.active {
    right: 0; /* يطلع المنيو */
  }
}
</style>
 <link rel="stylesheet" href="css/responsive.css">
</head>
<body>

<header>
  <div class="logo">
    <img src="images/Logo.png" alt="ASPIRA" >
    <span class="logo-text">SPIRA</span>
  </div>

    <!-- زر الموبايل -->
  <div class="hamburger" onclick="toggleMenu()">☰</div>

  <!-- القوائم -->
  <nav id="navMenu">
        <a href="cv_builder_mentee.php" class="<?= basename($_SERVER['PHP_SELF']) == 'cv_builder_mentee.php' ? 'active' : '' ?>">CV Builder</a>
        <a href="courses.php" class="<?= basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : '' ?>">Courses</a>
        <a href="MentorCenter.php" class="<?= basename($_SERVER['PHP_SELF']) == 'MentorCenter.php' ? 'active' : '' ?>">Mentor Center</a>
                <a href="GroupSessions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'GroupSessions.php' ? 'active' : '' ?>">Group Sessions</a>
        <a href="menteeUpcomingSession.php" class="<?= basename($_SERVER['PHP_SELF']) == 'menteeUpcomingSession.php' ? 'active' : '' ?>">Upcoming Sessions</a>
        <a href="mentee_past_sessions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'mentee_past_sessions.php' ? 'active' : '' ?>">Past Sessions</a>
   
   <a href="ask_aspira.php"
   class="<?= basename($_SERVER['PHP_SELF']) == 'ask_aspira.php' ? 'active' : '' ?>">
   AI Assistant
</a>

   
      </nav>

   
    
  <div class="profile-menu">
    <img src="<?php echo htmlspecialchars($cur_avatar); ?>" class="profile-pic" alt="Profile">
    <ul class="dropdown">
      <li><a href="edit_profile_mentee.php">Edit Profile</a></li>
      <li><a href="logout.php">Log Out</a></li>
    </ul>
  </div>
</header>

<div class="content">
  <div class="card">
    <?php if ($flash_success): ?>
      <div class="alert-success">Profile updated successfully.</div>
      <hr class="soft">
    <?php endif; ?>

    <?php if ($cur_role === 'mentor'): ?>
    <h2 style="text-align:center;">Edit profile (mentor)</h2>

      <form id="mentor-form" method="POST" action="edit_profile_mentee.php" enctype="multipart/form-data">
           <div class="pfp-wrap">
  <img src="<?= htmlspecialchars($cur_avatar) ?>" class="pfp-img" alt="Profile Picture">
  <button type="button" class="pfp-edit" id="pfpEditBtn" aria-label="Change photo">✎</button>
  <input type="file" id="pfpInput" name="profile_picture" accept="image/*" hidden>
   </div>
        <div class="grid-2">
          <div>
            <label>First Name</label>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($cur_fname); ?>" required>
          </div>
          <div>
            <label>Last Name</label>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($cur_lname); ?>" required>
          </div>
        </div>

        <label>Email</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($cur_email); ?>" required>
        <div id="otp-container" class="otp-inline">
          <label for="verification-code">Enter Verification Code</label>
          <div class="grid-2">
            <input type="text" id="verification-code" placeholder="6 digits">
            <button type="button" class="btn" id="verify-code" style="width:100%;">Verify</button>
          </div>
          <div class="note" id="otp-note" style="display:none;">Email verified ✓</div>
        </div>
        <input type="hidden" name="email_verified" id="email_verified" value="1">

        <label>Brief Description <small>(This will appear on your profile)</small></label>
        <textarea name="brief_description" rows="3"><?php echo htmlspecialchars($m_brief ?? ''); ?></textarea>

        <button type="submit" class="btn" name="save_mentor">Save Changes</button>
      </form>

    <?php else: ?>
    <h2 style="text-align:center;">Edit profile (mentee)</h2>

      <form id="mentee-form" method="POST" action="edit_profile_mentee.php">
        <div class="grid-2">
          <div>
            <label>First Name</label>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($cur_fname); ?>" required>
          </div>
          <div>
            <label>Last Name</label>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($cur_lname); ?>" required>
          </div>
        </div>

        <label>Email</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($cur_email); ?>" required>
        <div id="otp-container" class="otp-inline">
          <label for="verification-code">Enter Verification Code</label>
          <div class="grid-2">
            <input type="text" id="verification-code" placeholder="6 digits">
            <button type="button" class="btn" id="verify-code" style="width:100%;">Verify</button>
          </div>
          <div class="note" id="otp-note" style="display:none;">Email verified ✓</div>
        </div>
        <input type="hidden" name="email_verified" id="email_verified" value="1">


        <label>Description of Interests</label>
        <textarea id="description" name="description" rows="3" required><?php echo htmlspecialchars($me_interests ?? ''); ?></textarea>

        <label>Are you an Undergraduate or a Graduate?</label>
        <select id="status" name="status" required>
          <option value="" disabled <?php echo empty($me_status)?'selected':''; ?>>Select your status</option>
          <option value="undergraduate" <?php echo ($me_status==='undergraduate')?'selected':''; ?>>Undergraduate</option>
          <option value="graduate" <?php echo ($me_status==='graduate')?'selected':''; ?>>Graduate</option>
        </select>

        <button type="submit" class="btn" name="save_mentee">Save Changes</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<script>
// ===== OTP / Email flow =====
const emailInput = document.getElementById('email');
const otpBox = document.getElementById('otp-container');
const otpNote = document.getElementById('otp-note');
const codeInput = document.getElementById('verification-code');
const verifyBtn = document.getElementById('verify-code');
const emailVerifiedFlag = document.getElementById('email_verified');
const originalEmail = "<?php echo htmlspecialchars($cur_email); ?>";

// no duplicete
let sendingOTP = false;
let lastEmailRequested = null;
let otpLock = false; //no extra request 60 sec

if (emailInput) {
  
  emailInput.addEventListener('input', () => {
    emailVerifiedFlag.value = '0';
    if (otpNote) otpNote.style.display = 'none';
  });

  emailInput.addEventListener('blur', function(){
    const newEmail = emailInput.value.trim();
    if (newEmail.toLowerCase() === originalEmail.toLowerCase() || newEmail === '') {
      otpBox.style.display = 'none';
      if (otpNote) otpNote.style.display = 'none';
      emailVerifiedFlag.value = '1';
      return;
    }

   
    if (sendingOTP) return;
    if (otpLock && lastEmailRequested === newEmail) return;

    sendingOTP = true;
    fetch("edit_profile_mentee.php", {
      method: "POST",
      headers: {"Content-Type":"application/x-www-form-urlencoded"},
      body: "verify_email=1&email=" + encodeURIComponent(newEmail)
    })
    .then(r=>r.text())
    .then(txt=>{
      if (txt === "exists") {
        alert("❌ This email is already registered.");
        emailVerifiedFlag.value = '0';
        otpBox.style.display = 'none';
      } else if (txt === "code_sent" || txt === "already_sent") {
        if (txt === "code_sent") alert("✅ OTP has been sent to your email!");
        otpBox.style.display = 'block';
        emailVerifiedFlag.value = '0';
        lastEmailRequested = newEmail;

        
        otpLock = true;
        setTimeout(()=>{ otpLock = false; }, 60000);
      } else if (txt === "error") {
        alert("Failed to send OTP. Try again.");
        emailVerifiedFlag.value = '0';
      }
    })
    .catch(e=>console.error(e))
    .finally(()=>{ sendingOTP = false; });
  });
}

if (verifyBtn) {
  verifyBtn.addEventListener('click', function(){
    const code = (codeInput?.value || "").trim();
    if (code === "") { alert("Enter the verification code"); return; }
    fetch("edit_profile_mentee.php", {
      method: "POST",
      headers: {"Content-Type":"application/x-www-form-urlencoded"},
      body: "verify_code=1&code=" + encodeURIComponent(code)
    })
    .then(r=>r.text())
    .then(txt=>{
      if (txt === "verified") {
        if (otpNote) otpNote.style.display = 'block';
        emailVerifiedFlag.value = '1';
        alert("✅ Email verified successfully!");
       
        otpBox.style.display = 'none';
        lastEmailRequested = null;
      } else if (txt === "session_expired") {
        alert("Session expired. Re-enter email to get a new code.");
        emailVerifiedFlag.value = '0';
      } else {
        alert("Incorrect code.");
        emailVerifiedFlag.value = '0';
      }
    })
    .catch(e=>console.error(e));
  });
}

const pfpBtn = document.getElementById('pfpEditBtn');
const pfpInput = document.getElementById('pfpInput');
const pfpImg = document.querySelector('.pfp-img');

pfpBtn?.addEventListener('click', ()=> pfpInput.click());
pfpInput?.addEventListener('change', e=>{
  const f = e.target.files?.[0];
  if (f) {
    const url = URL.createObjectURL(f);
    pfpImg.src = url; 
  }
});
// ===== Validate First & Last Name (letters only) =====
document.addEventListener("DOMContentLoaded", () => {

  function validateName(input) {
    const pattern = /^[A-Za-z]+$/; // حروف فقط
    const value = input.value.trim();

    if (value === "") {
      input.style.borderColor = "red";
      return false;
    }

    if (!pattern.test(value)) {
      input.style.borderColor = "red";
      alert("Name must contain letters only (A–Z).");
      return false;
    }

    input.style.borderColor = "green";
    return true;
  }

  const firstName = document.querySelector("input[name='first_name']");
  const lastName = document.querySelector("input[name='last_name']");

  firstName.addEventListener("blur", () => validateName(firstName));
  lastName.addEventListener("blur", () => validateName(lastName));

});

</script>

<script>
function toggleMenu() {
  document.getElementById("navMenu").classList.toggle("active");
}
</script>
</body>
</html>
