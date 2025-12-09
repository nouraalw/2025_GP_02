<?php
// cv_builder.php — Snapshots in cv: first_name_snapshot, last_name_snapshot, email_snapshot, phone_number
include 'db_connection.php';
session_start();

error_reporting(E_ALL); ini_set('display_errors',1);

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id  = (int)$_SESSION['user_id'];
$mentee_id = $user_id; 


$cur_role   = null;
$cur_avatar = null;

$avq = $conn->prepare("
  SELECT u.role, m.profile_picture
  FROM users u
  LEFT JOIN mentors m ON m.user_id = u.user_id
  WHERE u.user_id = ?
  LIMIT 1
");
$avq->bind_param("i", $user_id); 
$avq->execute();
$avres = $avq->get_result();

$mentor_pp = '';
if ($avres && $avres->num_rows) {
  $row       = $avres->fetch_assoc();
  $cur_role  = strtolower(trim($row['role'] ?? ''));
  $mentor_pp = $row['profile_picture'] ?? '';
}
$avq->close();


if ($cur_role === 'mentor' && !empty($mentor_pp)) {
  $cur_avatar = $mentor_pp;              
} else {
  $cur_avatar = 'images/person.png';     
}
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$mentee_id = (int)$_SESSION['user_id'];

$message = ""; $success = false; $cv_id_existing = null;


$user = ['first_name' => '', 'last_name' => '', 'email' => ''];

$stmt = $conn->prepare("
    SELECT first_name, last_name, email
    FROM users
    WHERE user_id = ?
");
$stmt->bind_param("i",$mentee_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows) { $user = $res->fetch_assoc(); }
$stmt->close();


$cv = null;
$q=$conn->prepare("SELECT * FROM cv WHERE mentee_id=? ORDER BY updated_at DESC LIMIT 1");
$q->bind_param("i",$mentee_id); $q->execute();
$r=$q->get_result();
if ($r && $r->num_rows){ $cv = $r->fetch_assoc(); $cv_id_existing = (int)$cv['cv_id']; }
$q->close();


if ($_SERVER['REQUEST_METHOD']==='POST'){
  $first = trim($_POST['first_name'] ?? '');
  $last  = trim($_POST['last_name']  ?? '');
  $email = trim($_POST['email']      ?? '');

  // نقرأ الكود والرقم المحلي
  $country_code = trim($_POST['country_code'] ?? '');
  $phone_local  = preg_replace('/\D+/', '', $_POST['phone_local'] ?? ''); // نخليها أرقام فقط

  // fallback لو الاسم/الإيميل فاضي
  if ($first === '') $first = $user['first_name'] ?? '';
  if ($last  === '') $last  = $user['last_name']  ?? '';
  if ($email === '') $email = $user['email']      ?? '';

  // نركب رقم الجوال النهائي
  $phone = '';
  if ($country_code && $phone_local) {
      $phone = $country_code . $phone_local; // مثال: +966 + 512345678
  }

  // تحقق بسيط
  if ($phone === '') {
      $message = "Please select country code and enter your phone number.";
  } elseif (!preg_match('/^\+\d{6,20}$/', $phone)) {
      $message = "Invalid phone number. Make sure it includes country code.";
  }

  $title   = trim($_POST['cv_title'] ?? 'Professional CV');
  $summary = trim($_POST['summary'] ?? '');
  $title   = trim($_POST['cv_title'] ?? 'Professional CV');
  $summary = trim($_POST['summary'] ?? '');

   
  $educationArray=[];
  if(isset($_POST['edu_school'])){
    foreach($_POST['edu_school'] as $i=>$s){
      if(trim($s)==='') continue;
      $educationArray[]=[
        'school'=>$s,
        'degree'=>$_POST['edu_degree'][$i]??'',
        'field'=>$_POST['edu_field'][$i]??'',
        'location'=>$_POST['edu_location'][$i]??'',
        'start'=>$_POST['edu_start'][$i]??'',
        'end'=>$_POST['edu_end'][$i]??'',
        'details'=>$_POST['edu_details'][$i]??''
      ];
    }
  }

  $experienceArray=[];
  if(isset($_POST['exp_title'])){
    foreach($_POST['exp_title'] as $i=>$t){
      if(trim($t)==='') continue;
      $experienceArray[]=[
        'title'=>$t,
        'company'=>$_POST['exp_company'][$i]??'',
        'location'=>$_POST['exp_location'][$i]??'',
        'start'=>$_POST['exp_start'][$i]??'',
        'end'=>$_POST['exp_end'][$i]??'',
        'is_current'=>isset($_POST['exp_current'][$i])?1:0,
        'description'=>$_POST['exp_desc'][$i]??''
      ];
    }
  }

  $skillsArray = array_values(array_filter($_POST['skill_name']??[], fn($s)=>trim($s)!==''));

  $certArray=[];
  if(isset($_POST['cert_name'])){
    foreach($_POST['cert_name'] as $i=>$c){
      if(trim($c)==='') continue;
      $certArray[]=[
        'name'=>$c,
        'issuer'=>$_POST['cert_issuer'][$i]??'',
        'issue'=>$_POST['cert_issue'][$i]??'',
        'expiry'=>$_POST['cert_exp'][$i]??''
      ];
    }
  }

  $langArray=[];
  if(isset($_POST['language'])){
    foreach($_POST['language'] as $i=>$l){
      if(trim($l)==='') continue;
      $langArray[]=[
        'language'=>$l,
        'level'=>$_POST['language_level'][$i]??'Professional'
      ];
    }
  }

  // JSON
  $eduJson  = json_encode($educationArray, JSON_UNESCAPED_UNICODE);
  $expJson  = json_encode($experienceArray, JSON_UNESCAPED_UNICODE);
  $sklJson  = json_encode($skillsArray, JSON_UNESCAPED_UNICODE);
  $certJson = json_encode($certArray, JSON_UNESCAPED_UNICODE);
  $lngJson  = json_encode($langArray, JSON_UNESCAPED_UNICODE);

  if ($cv_id_existing){
    $stmt=$conn->prepare("
      UPDATE cv
      SET title=?, summary=?,
          first_name_snapshot=?, last_name_snapshot=?, email_snapshot=?, phone_number=?,
          education=?, experience=?, skills=?, certifications=?, languages=?
      WHERE cv_id=?");
    $stmt->bind_param(
      "sssssssssssi",
      $title,$summary,
      $first,$last,$email,$phone,
      $eduJson,$expJson,$sklJson,$certJson,$lngJson,
      $cv_id_existing
    );
    $stmt->execute(); $stmt->close();
  } else {
    $stmt=$conn->prepare("
      INSERT INTO cv
        (mentee_id,title,summary,
         first_name_snapshot,last_name_snapshot,email_snapshot,phone_number,
         education,experience,skills,certifications,languages)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param(
      "isssssssssss",
      $mentee_id,$title,$summary,
      $first,$last,$email,$phone,
      $eduJson,$expJson,$sklJson,$certJson,$lngJson
    );
    $stmt->execute(); $cv_id_existing=$stmt->insert_id; $stmt->close();
  }

  //reload after save
  $q=$conn->prepare("SELECT * FROM cv WHERE cv_id=?");
  $q->bind_param("i",$cv_id_existing); $q->execute();
  $cv=$q->get_result()->fetch_assoc(); $q->close();

  $success=true; $message="CV saved successfully.";
}

// helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }

// Decode JSON
$EDU  = $cv && $cv['education']      ? json_decode($cv['education'], true)      : [];
$EXP  = $cv && $cv['experience']     ? json_decode($cv['experience'], true)     : [];
$SKL  = $cv && $cv['skills']         ? json_decode($cv['skills'], true)         : [];
$CERT = $cv && $cv['certifications'] ? json_decode($cv['certifications'], true) : [];
$LNG  = $cv && $cv['languages']      ? json_decode($cv['languages'], true)      : [];

$title     = $cv['title']   ?? 'Professional CV';
$summary   = $cv['summary'] ?? '';

// Prefill: always prefer latest data from users
$first_init = $user['first_name'] ?? ($cv['first_name_snapshot'] ?? '');
$last_init  = $user['last_name']  ?? ($cv['last_name_snapshot']  ?? '');
$email_init = $user['email']      ?? ($cv['email_snapshot']      ?? '');


// نفصل رقم الجوال إلى: كود دولة + رقم محلي
$phone_full = $cv['phone_number'] ?? '';
$country_code_init = '+966'; // الافتراضي: السعودية
$phone_local_init  = '';

if ($phone_full && preg_match('/^(\+\d{1,4})(\d{5,15})$/', $phone_full, $m)) {
    $country_code_init = $m[1]; // مثلا +966
    $phone_local_init  = $m[2]; // مثلاً 512345678
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CV Builder — ASPIRA</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="images/favicon.png">
<style>
/* ===== ASPIRA styles ===== */
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
.content{max-width:1100px;margin:22px auto;padding:0 16px}
.card{background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1);padding:22px}
h2{color:var(--primary);margin-bottom:10px}
label{display:block;color:var(--primary);margin-top:12px}
input,textarea,select{width:100%;padding:11px;border:1px solid #b3b3b3;border-radius:6px;background:#f5f5f5}
textarea{min-height:90px}
.section{margin-top:18px}
.group{border:1px dashed #ddd;background:#fafafa;border-radius:8px;padding:14px;margin-top:10px}
.btn{background:var(--primary);color:#fff;border:none;border-radius:6px;padding:11px 18px;font-weight:700;cursor:pointer;transition:.2s}
.btn:hover{background:var(--accent)}
.mini{background:#eee;color:#333;border:none;border-radius:6px;padding:8px 12px;cursor:pointer}
.mini:hover{background:#ddd}
.inline-end{display:flex;justify-content:flex-end;margin-top:8px}
.alert{padding:10px;border-radius:6px;margin:10px 0}
.alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
hr.soft{margin:16px 0;border:none;border-top:1px solid #eee}
@media(max-width:900px){.grid{grid-template-columns:1fr}}

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

<script>
function toggleMenu() {
  document.getElementById("navMenu").classList.toggle("active");
}
</script>

 <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
<header>
  <div class="logo">
    <img src="images/Logo.png" alt="ASPIRA">
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
  <img
    src="<?php echo htmlspecialchars($cur_avatar, ENT_QUOTES); ?>"
    class="profile-pic"
    alt="Profile"
  >
  <ul class="dropdown">
    <li><a href="edit_profile_mentee.php">Edit Profile</a></li>
    <li><a href="logout.php">Log Out</a></li>
  </ul>
</div>

</header>

<style>
.profile-menu{position:relative;display:inline-block}
.profile-pic{width:45px;height:45px;border-radius:50%;cursor:pointer;object-fit:cover}
.dropdown{
  display:none;position:absolute;right:0;top:52px;background:#fff;
  border:1px solid #eee;border-radius:10px;min-width:180px;
  box-shadow:0 6px 18px rgba(0,0,0,.08);list-style:none;padding:8px 0;margin:0;z-index:10
}
.dropdown li{padding:10px 16px}
.dropdown li a{color:#211742;text-decoration:none;display:block}
.dropdown li:hover{background:#f7f5ff}
.profile-menu:hover .dropdown{display:block}

</style>


<div class="content">
  <div class="card">
    <h2 style="text-align:center;">Build Your ATS CV!</h2>

    <?php if($message): ?>
      <div class="alert alert-success"><?php echo h($message); ?></div>
    <?php endif; ?>

    <?php if(($success || $cv_id_existing) && $cv_id_existing): ?>
      <a class="btn" href="generate_resume.php?cv_id=<?php echo (int)$cv_id_existing; ?>">Download PDF</a>
      <hr class="soft">
    <?php endif; ?>

    <form method="POST" action="">
      
      <label>First Name</label>
      <input name="first_name" value="<?php echo h($first_init); ?>" required>

      <label>Last Name</label>
      <input name="last_name" value="<?php echo h($last_init); ?>" required>

      <label>Email</label>
      <input type="email" name="email" value="<?php echo h($email_init); ?>" required>
<style>
.phone-row label { margin-bottom: 6px; }
.phone-flex {
  display: flex;
  gap: 8px;
}
.phone-flex select {
  max-width: 200px;
}
</style>

<div class="section phone-row">
  <label>Phone</label>
  <div class="phone-flex">
    <select name="country_code" required>
      <option value="+966" <?= $country_code_init === '+966' ? 'selected' : '' ?>>Saudi Arabia (+966)</option>
<option value="+93" <?= $country_code_init === '+93' ? 'selected' : '' ?>>Afghanistan (+93)</option>
<option value="+1907" <?= $country_code_init === '+1907' ? 'selected' : '' ?>>Alaska (+1907)</option>
<option value="+355" <?= $country_code_init === '+355' ? 'selected' : '' ?>>Albania (+355)</option>
<option value="+213" <?= $country_code_init === '+213' ? 'selected' : '' ?>>Algeria (+213)</option>
<option value="+684" <?= $country_code_init === '+684' ? 'selected' : '' ?>>American Samoa (Pago-Pago) (+684)</option>
<option value="+376" <?= $country_code_init === '+376' ? 'selected' : '' ?>>Andorra (+376)</option>
<option value="+244" <?= $country_code_init === '+244' ? 'selected' : '' ?>>Angola (+244)</option>
<option value="+1264" <?= $country_code_init === '+1264' ? 'selected' : '' ?>>Anguilla (+1264)</option>
<option value="+6721" <?= $country_code_init === '+6721' ? 'selected' : '' ?>>Antarctica (+6721)</option>
<option value="+1268" <?= $country_code_init === '+1268' ? 'selected' : '' ?>>Antigua (+1268)</option>
<option value="+599" <?= $country_code_init === '+599' ? 'selected' : '' ?>>Antilles Netherlands (+599)</option>
<option value="+54" <?= $country_code_init === '+54' ? 'selected' : '' ?>>Argentina (+54)</option>
<option value="+374" <?= $country_code_init === '+374' ? 'selected' : '' ?>>Armenia (+374)</option>
<option value="+297" <?= $country_code_init === '+297' ? 'selected' : '' ?>>Aruba (+297)</option>
<option value="+247" <?= $country_code_init === '+247' ? 'selected' : '' ?>>Ascension Island (+247)</option>
<option value="+61" <?= $country_code_init === '+61' ? 'selected' : '' ?>>Australia (+61)</option>
<option value="+43" <?= $country_code_init === '+43' ? 'selected' : '' ?>>Austria (+43)</option>
<option value="+994" <?= $country_code_init === '+994' ? 'selected' : '' ?>>Azerbaijan (+994)</option>
<option value="+351296" <?= $country_code_init === '+351296' ? 'selected' : '' ?>>Azores (+351296)</option>
<option value="+1242" <?= $country_code_init === '+1242' ? 'selected' : '' ?>>Bahamas (+1242)</option>
<option value="+973" <?= $country_code_init === '+973' ? 'selected' : '' ?>>Bahrain (+973)</option>
<option value="+3471" <?= $country_code_init === '+3471' ? 'selected' : '' ?>>Balearic Islands (+3471)</option>
<option value="+880" <?= $country_code_init === '+880' ? 'selected' : '' ?>>Bangladesh (+880)</option>
<option value="+1246" <?= $country_code_init === '+1246' ? 'selected' : '' ?>>Barbados (+1246)</option>
<option value="+375" <?= $country_code_init === '+375' ? 'selected' : '' ?>>Belarus (+375)</option>
<option value="+32" <?= $country_code_init === '+32' ? 'selected' : '' ?>>Belgium (+32)</option>
<option value="+501" <?= $country_code_init === '+501' ? 'selected' : '' ?>>Belize (+501)</option>
<option value="+229" <?= $country_code_init === '+229' ? 'selected' : '' ?>>Benin (+229)</option>
<option value="+1441" <?= $country_code_init === '+1441' ? 'selected' : '' ?>>Bermuda (+1441)</option>
<option value="+975" <?= $country_code_init === '+975' ? 'selected' : '' ?>>Bhutan (+975)</option>
<option value="+591" <?= $country_code_init === '+591' ? 'selected' : '' ?>>Bolivia (+591)</option>
<option value="+387" <?= $country_code_init === '+387' ? 'selected' : '' ?>>Bosnia and Herzegovina (+387)</option>
<option value="+267" <?= $country_code_init === '+267' ? 'selected' : '' ?>>Botswana (+267)</option>
<option value="+55"  <?= $country_code_init === '+55'  ? 'selected' : '' ?>>Brazil (+55)</option>
<option value="+673" <?= $country_code_init === '+673' ? 'selected' : '' ?>>Brunei (+673)</option>
<option value="+359" <?= $country_code_init === '+359' ? 'selected' : '' ?>>Bulgaria (+359)</option>
<option value="+226" <?= $country_code_init === '+226' ? 'selected' : '' ?>>Burkina Faso (+226)</option>
<option value="+257" <?= $country_code_init === '+257' ? 'selected' : '' ?>>Burundi (+257)</option>
<option value="+855" <?= $country_code_init === '+855' ? 'selected' : '' ?>>Cambodia (+855)</option>
<option value="+237" <?= $country_code_init === '+237' ? 'selected' : '' ?>>Cameroon (+237)</option>
<option value="+1"   <?= $country_code_init === '+1'   ? 'selected' : '' ?>>Canada (+1)</option>
<option value="+3428" <?= $country_code_init === '+3428' ? 'selected' : '' ?>>Canary Islands (+3428)</option>
<option value="+238" <?= $country_code_init === '+238' ? 'selected' : '' ?>>Cape Verde (+238)</option>
<option value="+1345" <?= $country_code_init === '+1345' ? 'selected' : '' ?>>Cayman Islands (+1345)</option>
<option value="+236" <?= $country_code_init === '+236' ? 'selected' : '' ?>>Central African Republic (+236)</option>
<option value="+235" <?= $country_code_init === '+235' ? 'selected' : '' ?>>Chad (+235)</option>
<option value="+56"  <?= $country_code_init === '+56'  ? 'selected' : '' ?>>Chile (+56)</option>
<option value="+86"  <?= $country_code_init === '+86'  ? 'selected' : '' ?>>China (+86)</option>
<option value="+61891" <?= $country_code_init === '+61891' ? 'selected' : '' ?>>Christmas Island (+61891)</option>
<option value="+61891" <?= $country_code_init === '+61891' ? 'selected' : '' ?>>Cocos Islands (+61891)</option>
<option value="+57"  <?= $country_code_init === '+57'  ? 'selected' : '' ?>>Colombia (+57)</option>
<option value="+242" <?= $country_code_init === '+242' ? 'selected' : '' ?>>Congo (Brazaville) (+242)</option>
<option value="+243" <?= $country_code_init === '+243' ? 'selected' : '' ?>>Congo DR (+243)</option>
<option value="+682" <?= $country_code_init === '+682' ? 'selected' : '' ?>>Cook Islands (+682)</option>
<option value="+506" <?= $country_code_init === '+506' ? 'selected' : '' ?>>Costa Rica (+506)</option>
<option value="+385" <?= $country_code_init === '+385' ? 'selected' : '' ?>>Croatia (+385)</option>
<option value="+53"  <?= $country_code_init === '+53'  ? 'selected' : '' ?>>Cuba (+53)</option>
<option value="+357" <?= $country_code_init === '+357' ? 'selected' : '' ?>>Cyprus (+357)</option>
<option value="+420" <?= $country_code_init === '+420' ? 'selected' : '' ?>>Czech Republic (+420)</option>
<option value="+45"  <?= $country_code_init === '+45'  ? 'selected' : '' ?>>Denmark (+45)</option>
<option value="+246" <?= $country_code_init === '+246' ? 'selected' : '' ?>>Diego Garcia (+246)</option>
<option value="+253" <?= $country_code_init === '+253' ? 'selected' : '' ?>>Djibouti (+253)</option>
<option value="+1767" <?= $country_code_init === '+1767' ? 'selected' : '' ?>>Dominica (+1767)</option>
<option value="+1809" <?= $country_code_init === '+1809' ? 'selected' : '' ?>>Dominican Republic (+1809)</option>
<option value="+670" <?= $country_code_init === '+670' ? 'selected' : '' ?>>East Timor (+670)</option>
<option value="+593" <?= $country_code_init === '+593' ? 'selected' : '' ?>>Ecuador (+593)</option>
<option value="+20"  <?= $country_code_init === '+20'  ? 'selected' : '' ?>>Egypt (+20)</option>
<option value="+503" <?= $country_code_init === '+503' ? 'selected' : '' ?>>El Salvador (+503)</option>
<option value="+240" <?= $country_code_init === '+240' ? 'selected' : '' ?>>Equatorial Guinea (+240)</option>
<option value="+291" <?= $country_code_init === '+291' ? 'selected' : '' ?>>Eritrea (+291)</option>
<option value="+372" <?= $country_code_init === '+372' ? 'selected' : '' ?>>Estonia (+372)</option>
<option value="+251" <?= $country_code_init === '+251' ? 'selected' : '' ?>>Ethiopia (+251)</option>
<option value="+500" <?= $country_code_init === '+500' ? 'selected' : '' ?>>Falkland Islands (+500)</option>
<option value="+298" <?= $country_code_init === '+298' ? 'selected' : '' ?>>Faroe Islands (+298)</option>
<option value="+679" <?= $country_code_init === '+679' ? 'selected' : '' ?>>Fiji (+679)</option>
<option value="+358" <?= $country_code_init === '+358' ? 'selected' : '' ?>>Finland (+358)</option>
<option value="+33"  <?= $country_code_init === '+33'  ? 'selected' : '' ?>>France (+33)</option>
<option value="+594" <?= $country_code_init === '+594' ? 'selected' : '' ?>>French Guiana (+594)</option>
<option value="+689" <?= $country_code_init === '+689' ? 'selected' : '' ?>>French Polynesia (+689)</option>
<option value="+241" <?= $country_code_init === '+241' ? 'selected' : '' ?>>Gabon (+241)</option>
<option value="+220" <?= $country_code_init === '+220' ? 'selected' : '' ?>>Gambia (+220)</option>
<option value="+995" <?= $country_code_init === '+995' ? 'selected' : '' ?>>Georgia (+995)</option>
<option value="+49"  <?= $country_code_init === '+49'  ? 'selected' : '' ?>>Germany (+49)</option>
<option value="+233" <?= $country_code_init === '+233' ? 'selected' : '' ?>>Ghana (+233)</option>
<option value="+350" <?= $country_code_init === '+350' ? 'selected' : '' ?>>Gibraltar (+350)</option>
<option value="+30"  <?= $country_code_init === '+30'  ? 'selected' : '' ?>>Greece (+30)</option>
<option value="+299" <?= $country_code_init === '+299' ? 'selected' : '' ?>>Greenland (+299)</option>
<option value="+1473" <?= $country_code_init === '+1473' ? 'selected' : '' ?>>Grenada (+1473)</option>
<option value="+590" <?= $country_code_init === '+590' ? 'selected' : '' ?>>Guadeloupe (+590)</option>
<option value="+1671" <?= $country_code_init === '+1671' ? 'selected' : '' ?>>Guam (+1671)</option>
<option value="+502" <?= $country_code_init === '+502' ? 'selected' : '' ?>>Guatemala (+502)</option>
<option value="+245" <?= $country_code_init === '+245' ? 'selected' : '' ?>>Guinea-Bissau (+245)</option>
<option value="+224" <?= $country_code_init === '+224' ? 'selected' : '' ?>>Guinea (+224)</option>
<option value="+592" <?= $country_code_init === '+592' ? 'selected' : '' ?>>Guyana (+592)</option>
<option value="+509" <?= $country_code_init === '+509' ? 'selected' : '' ?>>Haiti (+509)</option>
<option value="+1808" <?= $country_code_init === '+1808' ? 'selected' : '' ?>>Hawaii (+1808)</option>
<option value="+504" <?= $country_code_init === '+504' ? 'selected' : '' ?>>Honduras (+504)</option>
<option value="+852" <?= $country_code_init === '+852' ? 'selected' : '' ?>>Hong Kong (+852)</option>
<option value="+36" <?= $country_code_init === '+36' ? 'selected' : '' ?>>Hungary (+36)</option>
<option value="+354" <?= $country_code_init === '+354' ? 'selected' : '' ?>>Iceland (+354)</option>
<option value="+91" <?= $country_code_init === '+91' ? 'selected' : '' ?>>India (+91)</option>
<option value="+62" <?= $country_code_init === '+62' ? 'selected' : '' ?>>Indonesia (+62)</option>
<option value="+98" <?= $country_code_init === '+98' ? 'selected' : '' ?>>Iran (+98)</option>
<option value="+964" <?= $country_code_init === '+964' ? 'selected' : '' ?>>Iraq (+964)</option>
<option value="+353" <?= $country_code_init === '+353' ? 'selected' : '' ?>>Ireland (+353)</option>
<option value="+39" <?= $country_code_init === '+39' ? 'selected' : '' ?>>Italy (+39)</option>
<option value="+225" <?= $country_code_init === '+225' ? 'selected' : '' ?>>Ivory Coast (+225)</option>
<option value="+1876" <?= $country_code_init === '+1876' ? 'selected' : '' ?>>Jamaica (+1876)</option>
<option value="+95" <?= $country_code_init === '+95' ? 'selected' : '' ?>>Myanmar (+95)</option>
<option value="+81" <?= $country_code_init === '+81' ? 'selected' : '' ?>>Japan (+81)</option>
<option value="+962" <?= $country_code_init === '+962' ? 'selected' : '' ?>>Jordan (+962)</option>
<option value="+7" <?= $country_code_init === '+7' ? 'selected' : '' ?>>Kazakhstan (+7)</option>
<option value="+254" <?= $country_code_init === '+254' ? 'selected' : '' ?>>Kenya (+254)</option>
<option value="+686" <?= $country_code_init === '+686' ? 'selected' : '' ?>>Kiribati (Gilbert) (+686)</option>
<option value="+850" <?= $country_code_init === '+850' ? 'selected' : '' ?>>North Korea (+850)</option>
<option value="+82" <?= $country_code_init === '+82' ? 'selected' : '' ?>>South Korea (+82)</option>
<option value="+965" <?= $country_code_init === '+965' ? 'selected' : '' ?>>Kuwait (+965)</option>
<option value="+996" <?= $country_code_init === '+996' ? 'selected' : '' ?>>Kyrgyzstan (+996)</option>
<option value="+856" <?= $country_code_init === '+856' ? 'selected' : '' ?>>Laos (+856)</option>
<option value="+371" <?= $country_code_init === '+371' ? 'selected' : '' ?>>Latvia (+371)</option>
<option value="+961" <?= $country_code_init === '+961' ? 'selected' : '' ?>>Lebanon (+961)</option>
<option value="+266" <?= $country_code_init === '+266' ? 'selected' : '' ?>>Lesotho (+266)</option>
<option value="+231" <?= $country_code_init === '+231' ? 'selected' : '' ?>>Liberia (+231)</option>
<option value="+218" <?= $country_code_init === '+218' ? 'selected' : '' ?>>Libya (+218)</option>
<option value="+423" <?= $country_code_init === '+423' ? 'selected' : '' ?>>Liechtenstein (+423)</option>
<option value="+370" <?= $country_code_init === '+370' ? 'selected' : '' ?>>Lithuania (+370)</option>
<option value="+352" <?= $country_code_init === '+352' ? 'selected' : '' ?>>Luxembourg (+352)</option>
<option value="+853" <?= $country_code_init === '+853' ? 'selected' : '' ?>>Macao (+853)</option>
<option value="+389" <?= $country_code_init === '+389' ? 'selected' : '' ?>>Macedonia (+389)</option>
<option value="+261" <?= $country_code_init === '+261' ? 'selected' : '' ?>>Madagascar (+261)</option>
<option value="+351291" <?= $country_code_init === '+351291' ? 'selected' : '' ?>>Madeira Islands (+351291)</option>
<option value="+265" <?= $country_code_init === '+265' ? 'selected' : '' ?>>Malawi (+265)</option>
<option value="+60" <?= $country_code_init === '+60' ? 'selected' : '' ?>>Malaysia (+60)</option>
<option value="+960" <?= $country_code_init === '+960' ? 'selected' : '' ?>>Maldives (+960)</option>
<option value="+223" <?= $country_code_init === '+223' ? 'selected' : '' ?>>Mali (+223)</option>
<option value="+356" <?= $country_code_init === '+356' ? 'selected' : '' ?>>Malta (+356)</option>
<option value="+1670" <?= $country_code_init === '+1670' ? 'selected' : '' ?>>Saipan Island (+1670)</option>
<option value="+692" <?= $country_code_init === '+692' ? 'selected' : '' ?>>Marshall Islands (+692)</option>
<option value="+596" <?= $country_code_init === '+596' ? 'selected' : '' ?>>Martinique (+596)</option>
<option value="+222" <?= $country_code_init === '+222' ? 'selected' : '' ?>>Mauritania (+222)</option>
<option value="+230" <?= $country_code_init === '+230' ? 'selected' : '' ?>>Mauritius (+230)</option>
<option value="+52" <?= $country_code_init === '+52' ? 'selected' : '' ?>>Mexico (+52)</option>
<option value="+691" <?= $country_code_init === '+691' ? 'selected' : '' ?>>Micronesia (+691)</option>
<option value="+373" <?= $country_code_init === '+373' ? 'selected' : '' ?>>Moldova (+373)</option>
<option value="+377" <?= $country_code_init === '+377' ? 'selected' : '' ?>>Monaco (+377)</option>
<option value="+976" <?= $country_code_init === '+976' ? 'selected' : '' ?>>Mongolia (+976)</option>
<option value="+1664" <?= $country_code_init === '+1664' ? 'selected' : '' ?>>Montserrat (+1664)</option>
<option value="+269" <?= $country_code_init === '+269' ? 'selected' : '' ?>>Comoros (+269)</option>
<option value="+212" <?= $country_code_init === '+212' ? 'selected' : '' ?>>Morocco (+212)</option>
<option value="+258" <?= $country_code_init === '+258' ? 'selected' : '' ?>>Mozambique (+258)</option>
<option value="+264" <?= $country_code_init === '+264' ? 'selected' : '' ?>>Namibia (+264)</option>
<option value="+674" <?= $country_code_init === '+674' ? 'selected' : '' ?>>Nauru (+674)</option>
<option value="+977" <?= $country_code_init === '+977' ? 'selected' : '' ?>>Nepal (+977)</option>
<option value="+31" <?= $country_code_init === '+31' ? 'selected' : '' ?>>Netherlands (+31)</option>
<option value="+687" <?= $country_code_init === '+687' ? 'selected' : '' ?>>New Caledonia (+687)</option>
<option value="+64" <?= $country_code_init === '+64' ? 'selected' : '' ?>>New Zealand (+64)</option>
<option value="+505" <?= $country_code_init === '+505' ? 'selected' : '' ?>>Nicaragua (+505)</option>
<option value="+227" <?= $country_code_init === '+227' ? 'selected' : '' ?>>Niger (+227)</option>
<option value="+234" <?= $country_code_init === '+234' ? 'selected' : '' ?>>Nigeria (+234)</option>
<option value="+683" <?= $country_code_init === '+683' ? 'selected' : '' ?>>Niue (+683)</option>
<option value="+6723" <?= $country_code_init === '+6723' ? 'selected' : '' ?>>Norfolk Island (+6723)</option>
<option value="+47" <?= $country_code_init === '+47' ? 'selected' : '' ?>>Norway (+47)</option>
<option value="+968" <?= $country_code_init === '+968' ? 'selected' : '' ?>>Oman (+968)</option>
<option value="+92" <?= $country_code_init === '+92' ? 'selected' : '' ?>>Pakistan (+92)</option>
<option value="+680" <?= $country_code_init === '+680' ? 'selected' : '' ?>>Palau (+680)</option>
<option value="+970" <?= $country_code_init === '+970' ? 'selected' : '' ?>>Palestine (+970)</option>
<option value="+507" <?= $country_code_init === '+507' ? 'selected' : '' ?>>Panama (+507)</option>
<option value="+675" <?= $country_code_init === '+675' ? 'selected' : '' ?>>Papua New Guinea (+675)</option>
<option value="+595" <?= $country_code_init === '+595' ? 'selected' : '' ?>>Paraguay (+595)</option>
<option value="+51" <?= $country_code_init === '+51' ? 'selected' : '' ?>>Peru (+51)</option>
<option value="+63" <?= $country_code_init === '+63' ? 'selected' : '' ?>>Philippines (+63)</option>
<option value="+48" <?= $country_code_init === '+48' ? 'selected' : '' ?>>Poland (+48)</option>
<option value="+351" <?= $country_code_init === '+351' ? 'selected' : '' ?>>Portugal (+351)</option>
<option value="+1787" <?= $country_code_init === '+1787' ? 'selected' : '' ?>>Puerto Rico (+1787)</option>
<option value="+974" <?= $country_code_init === '+974' ? 'selected' : '' ?>>Qatar (+974)</option>
<option value="+262" <?= $country_code_init === '+262' ? 'selected' : '' ?>>Reunion Island (+262)</option>
<option value="+40" <?= $country_code_init === '+40' ? 'selected' : '' ?>>Romania (+40)</option>
<option value="+7" <?= $country_code_init === '+7' ? 'selected' : '' ?>>Russia (+7)</option>
<option value="+250" <?= $country_code_init === '+250' ? 'selected' : '' ?>>Rwanda (+250)</option>
<option value="+378" <?= $country_code_init === '+378' ? 'selected' : '' ?>>San Marino (+378)</option>
<option value="+239" <?= $country_code_init === '+239' ? 'selected' : '' ?>>Sao Tome & Principe (+239)</option>
<option value="+221" <?= $country_code_init === '+221' ? 'selected' : '' ?>>Senegal (+221)</option>
<option value="+248" <?= $country_code_init === '+248' ? 'selected' : '' ?>>Seychelles (+248)</option>
<option value="+232" <?= $country_code_init === '+232' ? 'selected' : '' ?>>Sierra Leone (+232)</option>
<option value="+65" <?= $country_code_init === '+65' ? 'selected' : '' ?>>Singapore (+65)</option>
<option value="+421" <?= $country_code_init === '+421' ? 'selected' : '' ?>>Slovakia (+421)</option>
<option value="+386" <?= $country_code_init === '+386' ? 'selected' : '' ?>>Slovenia (+386)</option>
<option value="+677" <?= $country_code_init === '+677' ? 'selected' : '' ?>>Solomon Islands (+677)</option>
<option value="+252" <?= $country_code_init === '+252' ? 'selected' : '' ?>>Somalia (+252)</option>
<option value="+27" <?= $country_code_init === '+27' ? 'selected' : '' ?>>South Africa (+27)</option>
<option value="+34" <?= $country_code_init === '+34' ? 'selected' : '' ?>>Spain (+34)</option>
<option value="+94" <?= $country_code_init === '+94' ? 'selected' : '' ?>>Sri Lanka (+94)</option>
<option value="+290" <?= $country_code_init === '+290' ? 'selected' : '' ?>>St. Helena (+290)</option>
<option value="+1869" <?= $country_code_init === '+1869' ? 'selected' : '' ?>>St. Kitts & Nevis (+1869)</option>
<option value="+1758" <?= $country_code_init === '+1758' ? 'selected' : '' ?>>Saint Lucia (+1758)</option>
<option value="+508" <?= $country_code_init === '+508' ? 'selected' : '' ?>>St. Pierre & Miquelon (+508)</option>
<option value="+1784" <?= $country_code_init === '+1784' ? 'selected' : '' ?>>St. Vincent (+1784)</option>
<option value="+249" <?= $country_code_init === '+249' ? 'selected' : '' ?>>Sudan (+249)</option>
<option value="+211" <?= $country_code_init === '+211' ? 'selected' : '' ?>>South Sudan (+211)</option>
<option value="+597" <?= $country_code_init === '+597' ? 'selected' : '' ?>>Suriname (+597)</option>
<option value="+268" <?= $country_code_init === '+268' ? 'selected' : '' ?>>Swaziland (+268)</option>
<option value="+46" <?= $country_code_init === '+46' ? 'selected' : '' ?>>Sweden (+46)</option>
<option value="+41" <?= $country_code_init === '+41' ? 'selected' : '' ?>>Switzerland (+41)</option>
<option value="+963" <?= $country_code_init === '+963' ? 'selected' : '' ?>>Syria (+963)</option>
<option value="+886" <?= $country_code_init === '+886' ? 'selected' : '' ?>>Taiwan (+886)</option>
<option value="+992" <?= $country_code_init === '+992' ? 'selected' : '' ?>>Tajikistan (+992)</option>
<option value="+255" <?= $country_code_init === '+255' ? 'selected' : '' ?>>Tanzania (+255)</option>
<option value="+7513" <?= $country_code_init === '+7513' ? 'selected' : '' ?>>Tataristan (+7513)</option>
<option value="+66" <?= $country_code_init === '+66' ? 'selected' : '' ?>>Thailand (+66)</option>
<option value="+228" <?= $country_code_init === '+228' ? 'selected' : '' ?>>Togo (+228)</option>
<option value="+690" <?= $country_code_init === '+690' ? 'selected' : '' ?>>Tokelau (+690)</option>
<option value="+676" <?= $country_code_init === '+676' ? 'selected' : '' ?>>Tonga (+676)</option>
<option value="+1868" <?= $country_code_init === '+1868' ? 'selected' : '' ?>>Trinidad & Tobago (+1868)</option>
<option value="+216" <?= $country_code_init === '+216' ? 'selected' : '' ?>>Tunisia (+216)</option>
<option value="+90" <?= $country_code_init === '+90' ? 'selected' : '' ?>>Turkey (+90)</option>
<option value="+993" <?= $country_code_init === '+993' ? 'selected' : '' ?>>Turkmenistan (+993)</option>
<option value="+1649" <?= $country_code_init === '+1649' ? 'selected' : '' ?>>Turks & Caicos (+1649)</option>
<option value="+688" <?= $country_code_init === '+688' ? 'selected' : '' ?>>Tuvalu (+688)</option>
<option value="+256" <?= $country_code_init === '+256' ? 'selected' : '' ?>>Uganda (+256)</option>
<option value="+380" <?= $country_code_init === '+380' ? 'selected' : '' ?>>Ukraine (+380)</option>
<option value="+971" <?= $country_code_init === '+971' ? 'selected' : '' ?>>United Arab Emirates (+971)</option>
<option value="+44" <?= $country_code_init === '+44' ? 'selected' : '' ?>>United Kingdom (+44)</option>
<option value="+1" <?= $country_code_init === '+1' ? 'selected' : '' ?>>USA (+1)</option>
<option value="+598" <?= $country_code_init === '+598' ? 'selected' : '' ?>>Uruguay (+598)</option>
<option value="+998" <?= $country_code_init === '+998' ? 'selected' : '' ?>>Uzbekistan (+998)</option>
<option value="+678" <?= $country_code_init === '+678' ? 'selected' : '' ?>>Vanuatu (+678)</option>
<option value="+379" <?= $country_code_init === '+379' ? 'selected' : '' ?>>Vatican City (+379)</option>
<option value="+58" <?= $country_code_init === '+58' ? 'selected' : '' ?>>Venezuela (+58)</option>
<option value="+84" <?= $country_code_init === '+84' ? 'selected' : '' ?>>Vietnam (+84)</option>
<option value="+1284" <?= $country_code_init === '+1284' ? 'selected' : '' ?>>Virgin Islands (UK) (+1284)</option>
<option value="+1340" <?= $country_code_init === '+1340' ? 'selected' : '' ?>>Virgin Islands (US) (+1340)</option>
<option value="+1808" <?= $country_code_init === '+1808' ? 'selected' : '' ?>>Wake Island (+1808)</option>
<option value="+685" <?= $country_code_init === '+685' ? 'selected' : '' ?>>Western Samoa (+685)</option>
<option value="+967" <?= $country_code_init === '+967' ? 'selected' : '' ?>>Yemen (+967)</option>
<option value="+381" <?= $country_code_init === '+381' ? 'selected' : '' ?>>Yugoslavia (+381)</option>
<option value="+260" <?= $country_code_init === '+260' ? 'selected' : '' ?>>Zambia (+260)</option>
<option value="+263" <?= $country_code_init === '+263' ? 'selected' : '' ?>>Zimbabwe (+263)</option>

    </select>

    <input
      name="phone_local"
      placeholder="5XXXXXXXX"
      value="<?php echo h($phone_local_init); ?>"
      required
      pattern="^\d{5,15}$"
      title="Enter your phone number without country code"
    >
  </div>
</div>


      <label>CV Title</label>
      <input name="cv_title" value="<?php echo h($title); ?>">

      <label>Professional Summary</label>
      <textarea name="summary" required><?php echo h($summary); ?></textarea>

      <!-- Education -->
      <div class="section">
        <h3 style="color:var(--primary)">Education</h3>
        <div id="edu-wrap"></div>
        <div class="inline-end"><button type="button" class="mini" onclick="addEdu()">+ Add Education</button></div>
      </div>

      <!-- Experience -->
      <div class="section">
        <h3 style="color:var(--primary)">Work Experience</h3>
        <div id="exp-wrap"></div>
        <div class="inline-end"><button type="button" class="mini" onclick="addExp()">+ Add Experience</button></div>
      </div>

      <!-- Skills -->
      <div class="section">
        <h3 style="color:var(--primary)">Skills</h3>
        <div id="skill-wrap"></div>
        <div class="inline-end"><button type="button" class="mini" onclick="addSkill()">+ Add Skill</button></div>
      </div>

      <!-- Certifications -->
      <div class="section">
        <h3 style="color:var(--primary)">Certifications</h3>
        <div id="cert-wrap"></div>
        <div class="inline-end"><button type="button" class="mini" onclick="addCert()">+ Add Certification</button></div>
      </div>

      <!-- Languages -->
      <div class="section">
        <h3 style="color:var(--primary)">Languages</h3>
        <div id="lang-wrap"></div>
        <div class="inline-end"><button type="button" class="mini" onclick="addLang()">+ Add Language</button></div>
      </div>

      <button class="btn" type="submit">Save CV</button>
    </form>
  </div>
</div>

<script>
// helper
function el(html){const t=document.createElement('template');t.innerHTML=html.trim();return t.content.firstChild;}

// suggestions
const skillOptions = ["Python","Java","C++","SQL","HTML","CSS","JavaScript","React","Node.js","Machine Learning","Data Analysis","Project Management","Leadership","Communication","Problem Solving"];
const langOptions  = ["Arabic","English","French","German","Spanish","Chinese","Japanese","Korean","Italian","Turkish"];

// renderers
function addEdu(d={}) {
  const g = el(`<div class="group">
    <label>School</label><input name="edu_school[]" value="${(d.school||'').replace(/"/g,'&quot;')}">
    <label>Degree</label><input name="edu_degree[]" value="${(d.degree||'').replace(/"/g,'&quot;')}">
    <label>Field of Study</label><input name="edu_field[]" value="${(d.field||'').replace(/"/g,'&quot;')}">
    <label>Location</label><input name="edu_location[]" value="${(d.location||'').replace(/"/g,'&quot;')}">
    <label>Start</label><input type="date" name="edu_start[]" value="${d.start||''}">
    <label>End</label><input type="date" name="edu_end[]" value="${d.end||''}">
    <label>Details</label><textarea name="edu_details[]">${d.details||''}</textarea>
    <div class="inline-end"><button type="button" class="mini" onclick="this.closest('.group').remove()">Remove</button></div>
  </div>`);
  document.getElementById('edu-wrap').appendChild(g);
}

function addExp(d={}) {
  const g = el(`<div class="group">
    <label>Job Title</label><input name="exp_title[]" value="${(d.title||'').replace(/"/g,'&quot;')}">
    <label>Company</label><input name="exp_company[]" value="${(d.company||'').replace(/"/g,'&quot;')}">
    <label>Location</label><input name="exp_location[]" value="${(d.location||'').replace(/"/g,'&quot;')}">
    <label>Start</label><input type="date" name="exp_start[]" value="${d.start||''}">
    <label>End</label><input type="date" name="exp_end[]" value="${d.end||''}">
    <label><input type="checkbox" name="exp_current[]" value="1" ${d.is_current==1?'checked':''} onclick="toggleEnd(this)"> Current</label>
    <label>Description (one bullet per line)</label><textarea name="exp_desc[]">${d.description||''}</textarea>
    <div class="inline-end"><button type="button" class="mini" onclick="this.closest('.group').remove()">Remove</button></div>
  </div>`);
  if(d.is_current==1){ const endInput=g.querySelector('[name="exp_end[]"]'); endInput.value=''; endInput.disabled=true; }
  document.getElementById('exp-wrap').appendChild(g);
}
function toggleEnd(cb){ const end=cb.closest('.group').querySelector('[name="exp_end[]"]'); if(cb.checked){end.value=''; end.disabled=true;} else {end.disabled=false;} }

function addSkill(val='') {
  const g = el(`<div class="group">
    <label>Skill</label>
    <input name="skill_name[]" list="skillList" value="${(val||'').replace(/"/g,'&quot;')}">
    <datalist id="skillList">${skillOptions.map(s=>`<option value="${s}">`).join('')}</datalist>
    <div class="inline-end"><button type="button" class="mini" onclick="this.closest('.group').remove()">Remove</button></div>
  </div>`);
  document.getElementById('skill-wrap').appendChild(g);
}

function addCert(d={}) {
  const g = el(`<div class="group">
    <label>Name</label><input name="cert_name[]" value="${(d.name||'').replace(/"/g,'&quot;')}">
    <label>Issuer</label><input name="cert_issuer[]" value="${(d.issuer||'').replace(/"/g,'&quot;')}">
    <label>Issue Date</label><input type="date" name="cert_issue[]" value="${d.issue||''}">
    <label>Expiry Date</label><input type="date" name="cert_exp[]" value="${d.expiry||''}">
    <div class="inline-end"><button type="button" class="mini" onclick="this.closest('.group').remove()">Remove</button></div>
  </div>`);
  document.getElementById('cert-wrap').appendChild(g);
}

function addLang(d={}) {
  const g = el(`<div class="group">
    <label>Language</label>
    <input name="language[]" list="langList" value="${(d.language||'').replace(/"/g,'&quot;')}">
    <datalist id="langList">${langOptions.map(l=>`<option value="${l}">`).join('')}</datalist>
    <label>Proficiency</label>
    <select name="language_level[]">
      <option ${d.level==='Basic'?'selected':''}>Basic</option>
      <option ${d.level==='Conversational'?'selected':''}>Conversational</option>
      <option ${(!d.level||d.level==='Professional')?'selected':''}>Professional</option>
      <option ${d.level==='Fluent'?'selected':''}>Fluent</option>
      <option ${d.level==='Advanced'?'selected':''}>Advanced</option>
      <option ${d.level==='Native'?'selected':''}>Native</option>
    </select>
    <div class="inline-end"><button type="button" class="mini" onclick="this.closest('.group').remove()">Remove</button></div>
  </div>`);
  document.getElementById('lang-wrap').appendChild(g);
}


const EDU = <?php echo json_encode($EDU); ?>;
const EXP = <?php echo json_encode($EXP); ?>;
const SKL = <?php echo json_encode($SKL); ?>;
const CERT= <?php echo json_encode($CERT); ?>;
const LNG = <?php echo json_encode($LNG); ?>;


(EDU.length?EDU:[{}]).forEach(addEdu);
(EXP.length?EXP:[{}]).forEach(addExp);
(SKL.length?SKL:['']).forEach(addSkill);
(CERT.length?CERT:[{}]).forEach(addCert);
(LNG.length?LNG:[{}]).forEach(addLang);
</script>
</body>
</html>
