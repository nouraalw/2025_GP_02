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


$user = ['first_name'=>'','last_name'=>'','email'=>'','phone_number'=>''];
$stmt = $conn->prepare("SELECT first_name,last_name,email,phone_number FROM users WHERE user_id=?");
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
  //snapshots without edit to users
  $first = trim($_POST['first_name'] ?? '');
  $last  = trim($_POST['last_name']  ?? '');
  $email = trim($_POST['email']      ?? '');
  $phone = trim($_POST['phone_number'] ?? '');

  // fallback if blanks
  if ($first === '') $first = $user['first_name'] ?? '';
  if ($last  === '') $last  = $user['last_name']  ?? '';
  if ($email === '') $email = $user['email']      ?? '';
  if ($phone === '') $phone = $user['phone_number'] ?? '';

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

// Prefill: snapshot first from users
$first_init = $cv['first_name_snapshot'] ?? ($user['first_name'] ?? '');
$last_init  = $cv['last_name_snapshot']  ?? ($user['last_name']  ?? '');
$email_init = $cv['email_snapshot']      ?? ($user['email']      ?? '');
$phone_init = $cv['phone_number']        ?? ($user['phone_number'] ?? '');

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
body{background:#ffffff;color:#333}
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
        <a href="cv_builder.php" class="<?= basename($_SERVER['PHP_SELF']) == 'cv_builder.php' ? 'active' : '' ?>">CV Builder</a>
        <a href="MentorCenter.php" class="<?= basename($_SERVER['PHP_SELF']) == 'MentorCenter.php' ? 'active' : '' ?>">Mentor Center</a>
        <a href="menteeUpcomingSession.php" class="<?= basename($_SERVER['PHP_SELF']) == 'menteeUpcomingSession.php' ? 'active' : '' ?>">Upcoming Sessions</a>
        <a href="past_sessions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'past_sessions.php' ? 'active' : '' ?>">Past Sessions</a>
    </nav>
  <div class="profile-menu">
  <img
    src="<?php echo htmlspecialchars($cur_avatar, ENT_QUOTES); ?>"
    class="profile-pic"
    alt="Profile"
  >
  <ul class="dropdown">
    <li><a href="edit_profile.php">Edit Profile</a></li>
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

      <label>Phone</label>
      <input name="phone_number" placeholder="+9665XXXXXXX" value="<?php echo h($phone_init); ?>">

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

<script>
function toggleMenu() {
  document.getElementById("navMenu").classList.toggle("active");
}
</script>
</body>
</html>
