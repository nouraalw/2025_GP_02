<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connection.php';

// 🔒 لو المستخدم مو مسجل دخول رجعيه للـ login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// 🎭 نجيب دوره + صورة البروفايل عشان الهيدر
$cur_role   = null;
$cur_avatar = 'images/person.png'; // صورة افتراضية

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

if ($avres && $avres->num_rows) {
    $row = $avres->fetch_assoc();
    $cur_role = strtolower(trim($row['role'] ?? ''));
    if (!empty($row['profile_picture'])) {
        $cur_avatar = $row['profile_picture'];
    }
}
$avq->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>Ask ASPIRA 💬</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="images/favicon.png">
<style>
/* ========== ASPIRA unified theme ========== */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
:root{--primary:#4b398e;--accent:#a576ff}
body{background:#fff;color:#333;overflow:hidden;height:100vh;display:flex;flex-direction:column;}
header{display:flex;justify-content:space-between;align-items:center;padding:15px 50px;background:#fff;border-bottom:.4px solid rgba(0,0,0,.05);position:sticky;top:0;z-index:5}
.logo{display:flex;align-items:center;gap:8px;color:#a576ff;font-weight:700}
.logo img{width:24px}
.logo-text{font-size:22px;font-weight:700;color:#a576ff}
nav{display:flex;gap:30px}
nav a{color:#000;text-decoration:none;font-weight:500;padding-bottom:5px;border-bottom:2px solid transparent;transition:.2s}
nav a:hover,nav a.active{color:var(--primary);border-bottom:2px solid var(--primary)}

/* ========== Chat container ========== */
.chat-container{flex:1;display:flex;flex-direction:column;max-width:900px;width:100%;margin:20px auto;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.05);overflow:hidden}
.chat-box{flex:1;padding:20px;overflow-y:auto;background:#fafafa}
.message{max-width:80%;margin:10px 0;padding:12px 16px;border-radius:16px;line-height:1.4;font-size:15px}
.user{align-self:flex-end;background:var(--accent);color:#fff;border-top-right-radius:0}
.bot{align-self:flex-start;background:#f0ebff;border-top-left-radius:0;color:#211742}
.input-area{display:flex;border-top:1px solid #eee;background:#fff;padding:14px;gap:10px}
.input-area input{flex:1;padding:12px 15px;border:1px solid #ccc;border-radius:8px;font-size:15px}
.input-area button{background:var(--primary);color:#fff;border:none;border-radius:8px;padding:12px 22px;font-weight:600;cursor:pointer;transition:.2s}
.input-area button:hover{background:var(--accent)}
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
        <img src="images/logo.png" alt="ASPIRA">
        <span class="logo-text">SPIRA</span>
    </div>
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

<div class="chat-container">
  <div class="chat-box" id="chatBox">
    <div class="message bot">👋 Hi there! I'm ASPIRA AI assistant. How can I help you today?</div>
  </div>
  <div class="input-area">
    <input type="text" id="userInput" placeholder="Type your message..." onkeydown="if(event.key==='Enter')sendMessage()">
    <button onclick="sendMessage()">Send</button>
  </div>
</div>

<script>
async function sendMessage(){
  const input=document.getElementById('userInput');
  const msg=input.value.trim();
  if(!msg) return;
  appendMessage(msg,'user');
  input.value='';
  const chatBox=document.getElementById('chatBox');
  chatBox.scrollTop=chatBox.scrollHeight;
  try{
    const res=await fetch('gemini_handler.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({message:msg})
    });
    const data=await res.json();
    const reply=data.candidates?.[0]?.content?.parts?.[0]?.text || "Sorry, I couldn't understand that.";
    appendMessage(reply,'bot');
    chatBox.scrollTop=chatBox.scrollHeight;
  }catch(e){
    appendMessage("⚠️ Connection error. Please try again.",'bot');
  }
}

function appendMessage(text,type){
  const div=document.createElement('div');
  div.className='message '+type;
  div.innerHTML = text
  .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') 
  .replace(/\n/g, '<br>');                     

  document.getElementById('chatBox').appendChild(div);
}
</script>
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

<script>
  // ✅ هذا سكربت الشات موجود عندك أصلاً (sendMessage / appendMessage / ...)

  function toggleMenu() {
    document.getElementById("navMenu").classList.toggle("active");
  }
</script>



</body>
</html>
