<?php  
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connection.php'; 


$view = isset($_GET['view']) ? $_GET['view'] : 'private';




// ====== حماية أساسية ======
$mentee_id = $_SESSION['user_id'] ?? 0;
if (!$mentee_id) { die("Error: mentee_id not found"); }


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











    // ===== Private sessions query =====
    $query = "SELECT s.id, s.date, s.time, s.room_id, u.first_name, u.last_name
              FROM sessions s
              LEFT JOIN mentors m ON s.mentor_id = m.user_id
              LEFT JOIN users   u ON m.user_id = u.user_id
              WHERE s.mentee_id = ?
                AND s.status = 'booked' 
              ORDER BY s.date DESC, s.time DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $mentee_id);














if (!$stmt) { die("Error in preparing the query: " . $conn->error); }
//$stmt->bind_param('ii', $mentee_id, $is_public);
if (!$stmt->execute()) { die("Error in executing the query: " . $stmt->error); }
$result = $stmt->get_result();



if (!$result) { die("Error while fetching results: " . $stmt->error); }

// لضبط مقارنة الوقت
date_default_timezone_set('Asia/Riyadh');
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentee Upcoming Sessions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="icon" type="image/png" href="images/favicon.png">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
        :root {
            --primary-bg:#211742;
            --button-color:#4b398e;
            --highlight-text:#a576ff;
        }
        body{background:#f8f8f8}
        header { display:flex; justify-content:space-between; align-items:center; padding:15px 50px; background:#fff; border-bottom:0.4px solid rgba(0,0,0,0.05); }
            .logo {
            font-size: 24px;
            font-weight: bold;
            color: #a576ff;
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .logo img {
            width: 24px;
            height: auto;
        }

        .logo-text {
            font-size: 22px;
            font-weight: bold;
            color: #a576ff;
        }
        nav { display:flex; gap:30px; }
        nav a { color:black; font-size:16px; font-weight:500; text-decoration:none; padding-bottom:5px; border-bottom:2px solid transparent; transition:0.3s; }
        nav a:hover, nav a.active { color:#4b398e; border-bottom:2px solid #4b398e; font-weight:bold; }
        .logout-btn { text-decoration:none; padding:8px 20px; border:2px solid #2D2D69; border-radius:20px; font-weight:bold; color:#2D2D69; background:white; }
        .logout-btn:hover { background:#211742; color:white; border-color:#211742; }
        table { width:80%; margin:20px auto; border-collapse:collapse; background:#fff; box-shadow:0 2px 10px rgba(0,0,0,0.1); border-radius:10px; }
        th,td { padding:15px; border:1px solid #ddd; text-align:center; }
        th { background:#4b398e; color:white; }
        tr:hover { background:#f1f1f1; }
        .join-btn { padding:8px 20px; border:2px solid #2D2D69; border-radius:20px; font-weight:bold; color:#2D2D69; background:white; text-decoration:none; }
        .join-btn:hover { background:#211742; color:white; border-color:#211742; }
        .icon-btn { background:none; border:none; cursor:pointer; font-size:18px; padding:4px; }
        .delete-btn { color:#ff3b30; }
        .delete-btn:hover { transform:scale(1.2); }
        .edit-btn { color:#211742; }
        .edit-btn:hover { transform:scale(1.2); }
        .session-ended { background:#f0f0f0; color:gray; }
        .add-session-form { background:#fff; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.3); padding:25px; width:100%; max-width:500px; }
        .form-title { font-size:24px; color:#4b398e; margin-bottom:20px; text-align:center; }
        .form-group { margin-bottom:20px; }
        .form-group label { display:block; margin-bottom:8px; color:#4b398e; }
        .form-group input { width:100%; padding:12px; border:1px solid #b3b3b3; border-radius:6px; background:#f5f5f5; }
        .form-group input:focus { border-color:#4b398e; outline:none; }
        .add-btn { background:#4b398e; color:white; padding:12px 25px; border:none; border-radius:6px; font-weight:bold; cursor:pointer; width:100%; }
        .add-btn:hover { background:#a576ff; transform:scale(1.02); }
        
        
.sub-tabs {
  display:flex;
  gap:10px;
  justify-content:center;
  margin:10px 0 25px;
}
.sub-tab {
  padding:8px 20px;
  border-radius:20px;
  font-weight:500;
  text-decoration:none;
  background:#eee;
  color:#4b398e;
}
.sub-tab.active {
  background:#4b398e;
  color:#fff;
}
.sub-tab:hover { filter:brightness(.95); }

/* Footer */
footer {
    text-align: center;
    padding: 10px;
    background: #ffffff;
    color: #211742;
    margin-top: 300px;
    box-shadow: none !important; /* ✅ إزالة أي ظل */
    border-top: 0.3px solid rgba(0, 0, 0, 0.05); /* ✅ خط خفيف جدًا أعلى الفوتر */
}

footer img {
    margin-right: 10px;
}

.footer-links {
    display: flex;
    justify-content: space-between;
    padding: 0 5%;
    margin-top: 20px;
}

.footer-links div {
    text-align: left;
}

.footer-links ul {
    list-style: none;
    padding: 0;
}

.footer-links ul li {
    margin-bottom: 8px;
}

.footer-links ul li a {
    text-decoration: none;
    color: #211742;
    transition: color 0.3s;
}

.footer-links ul li a:hover {
    color: var(--primary-light);
}

.footer-social {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 20px;
}

.footer-social a {
    color: #211742;
    font-size: 20px;
    transition: color 0.3s;
}

.footer-social a:hover {
    color: var(--primary-light);
}



.footer-content {
  max-height: 0;
  overflow: hidden;
  font-size: 14px;
  color: #555;
  margin: 0;
  padding-left: 10px;
  border-left: 2px solid #a576ff;
  transition: max-height 0.4s ease, margin 0.4s ease;
}
 

.footer-links a {
  display: flex;
  justify-content: flex-start; /* يخلي النص والسهم قريبين */
  align-items: center;
  gap: 6px; /* مسافة صغيرة بين النص والسهم */
  cursor: pointer;
}

.arrow {
  font-size: 12px;  /* يصغر السهم شوي */
  transition: transform 0.3s ease;
}


.arrow.down {
  transform: rotate(90deg); /* يخلي السهم لأسفل */
}


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


<main>
    <br>
    <h2 style='font-weight:bold; color:#4b398e; text-align:center;'>Upcoming Sessions</h2>
    <?php
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    echo "<p style='color:green; text-align:center;'> Session removed successfully.</p>";
}
?>
    
    <div class="sub-tabs">
  <a href="menteeUpcomingSession.php" 
     class="sub-tab <?= basename($_SERVER['PHP_SELF']) == 'menteeUpcomingSession.php' ? 'active' : '' ?>">
     Private
  </a>
  <a href="menteeGroupUpcoming.php" 
     class="sub-tab <?= basename($_SERVER['PHP_SELF']) == 'menteeGroupUpcoming.php' ? 'active' : '' ?>">
     Group
  </a>
</div>

<table>
    <tr>
        <th>Mentor Name</th>
        <th>Date</th>
        <th>Time</th>
        <th>Duration</th>
        <th>Join</th>
        <th>Actions</th>
    </tr>

    <?php
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    if (count($rows) > 0) {
        foreach ($rows as $row) {
            $session_id   = $row['id'];
            $session_date = $row['date'];
            $session_time = $row['time'];
            $duration     = 45; 
            $room_id      = $row['room_id'];

            $start_ts = strtotime($session_date . ' ' . $session_time);
            $end_ts   = $start_ts + ($duration * 60);
            $isEnded  = time() > $end_ts;
            $time_diff = $start_ts - time();
            ?>
            

            <tr id="row-<?= $session_id ?>" class="<?= $isEnded ? 'session-ended' : '' ?>">
                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
               <td class="date-cell"><?= htmlspecialchars($session_date) ?></td>
            <td class="time-cell"><?= htmlspecialchars($session_time) ?></td>
                <td><?= $duration ?> min</td>
                <td>
                <?php
                $safe_room_id = htmlspecialchars($room_id ?? '', ENT_QUOTES, 'UTF-8');
                if (time() >= $start_ts && time() <= $end_ts && !empty($room_id)) {
                    echo "<a href='WEB_UIKITS.php?roomID={$safe_room_id}' class='join-btn' target='_blank'>Join</a>";
                } elseif (time() < $start_ts) {
echo "<button class='join-btn' type='button' onclick='showJoinModal(\"The session hasn’t started yet. Please wait!\", \"wait\")'>Join</button>";
                } elseif (time() > $end_ts) {
                    echo "<span style='color: gray;'>Session Ended</span>";
                } else {
                    echo "<button class='join-btn' type='button' onclick='showJoinModal(\"Room ID is not available yet. Please try again later.\")'>Join</button>";
                }
                ?>
                </td>
                <td>
                    <button class="icon-btn edit-btn" 
                            onclick="openEditModal(<?= $session_id ?>, '<?= $session_date ?>', '<?= $session_time ?>')" 
                            <?= ($time_diff <= 3600 || $isEnded) ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
                        <i class="fas fa-edit"></i>
                    </button>
                    <a href="mentee_delete_session.php?id=<?= $session_id ?>" 
                       class="icon-btn delete-btn" 
                       <?= ($time_diff <= 3600 || $isEnded) ? 'onclick="return false;" style="opacity:0.5;pointer-events:none;cursor:not-allowed;"' : '' ?>>
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </td>
            </tr>
            <?php
        }
    } else {
        echo "<tr><td colspan='6'>There are no upcoming Sessions.</td></tr>";
    }
    ?>
</table>

</main>

<!-- مودال التعديل -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:2000;">
    <div class="add-session-form">
        <h3 class="form-title">Edit Session</h3>
        <input type="hidden" id="editSessionId">
        <div class="form-group">
            <label for="editDate">Date:</label>
            <input type="date" id="editDate">
        </div>
        <div class="form-group">
            <label for="editTime">Time:</label>
            <input type="time" id="editTime">
        </div>
        <button class="add-btn" onclick="saveEdit()">Save</button>
        <button class="add-btn" style="background:#ccc; color:#333;" onclick="closeEditModal()">Cancel</button>
    </div>
</div>

<!-- مودال تحديث -->
<div id="updateModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:2000;">
  <div class="add-session-form">
    <h3 class="form-title">Available Time Slots</h3>
    <div id="slotsContainer"></div>
    <button class="add-btn" style="background:#ccc; color:#333;" onclick="closeUpdateModal()">Cancel</button>
  </div>
</div>


<!-- مودال Join -->
<div id="joinModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
    <div style="background:#fff; padding:30px; border-radius:10px; text-align:center; max-width:400px; width:90%;">
        <img id="modalIcon" src="" alt="Status Icon" style="width:80px; height:80px; margin-bottom:10px;">
        <h2 id="modalTitle" style="color:#4b398e;">Title</h2>
        <p id="joinModalMessage">Message</p>
        <button onclick="closeJoinModal()" style="margin-top:20px; background:#4b398e; color:#fff; padding:10px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">OK</button>
    </div>
</div>



<footer>
  <div class="footer-links">
    <div>
      <h3>Company</h3>
      <ul>
        <li>
          <a href="javascript:void(0)" onclick="toggleContent('about', this)">
            About Us <span class="arrow">&gt;</span>
          </a>
          <div id="about" class="footer-content">
            ASPIRA is a platform that connects students with professional mentors to help them build their future careers.
          </div>
        </li>
        <li>
          <a href="javascript:void(0)" onclick="toggleContent('careers', this)">
            Careers <span class="arrow">&gt;</span>
          </a>
          <div id="careers" class="footer-content">
            Join our team and contribute to developing innovative educational solutions.
          </div>
        </li>
        <li>
          <a href="javascript:void(0)" onclick="toggleContent('contact', this)">
            Contact <span class="arrow">&gt;</span>
          </a>
          <div id="contact" class="footer-content">
            Get in touch with us at gp.finally@gmail.com.
          </div>
        </li>
      </ul>
    </div>

    <div>
      <h3>Resources</h3>
      <ul>
        <li>
          <a href="javascript:void(0)" onclick="toggleContent('blog', this)">
            Blog <span class="arrow">&gt;</span>
          </a>
          <div id="blog" class="footer-content">
            Inspiring articles on mentorship, personal development, and career growth.
          </div>
        </li>
        <li>
          <a href="javascript:void(0)" onclick="toggleContent('help', this)">
            Help Center <span class="arrow">&gt;</span>
          </a>
          <div id="help" class="footer-content">
            Quick answers to common questions and a step-by-step user guide.
          </div>
        </li>
        <li>
          <a href="javascript:void(0)" onclick="toggleContent('privacy', this)">
            Privacy Policy <span class="arrow">&gt;</span>
          </a>
          <div id="privacy" class="footer-content">
            Your privacy matters. We are committed to protecting your data and using it only for platform purposes.
          </div>
        </li>
      </ul>
    </div>

    <div>
      <h3>Connect</h3>
      <ul>
        <li><a href="#">Twitter</a></li>
        <li><a href="#">LinkedIn</a></li>
        <li><a href="#">Instagram</a></li>
      </ul>
    </div>
  </div>

  <div class="footer-social">
    <a href="#"><i class="fab fa-twitter"></i></a>
    <a href="#"><i class="fab fa-linkedin"></i></a>
    <a href="#"><i class="fab fa-instagram"></i></a>
  </div>
  <p>&copy; 2025 ASPIRA. All rights reserved.</p>
</footer>
<script>
function showJoinModal(message, type = "wait") {
    const icon = document.getElementById("modalIcon");
    const title = document.getElementById("modalTitle");
    const msg   = document.getElementById("joinModalMessage");

    if (type === "wait") {
        icon.src = "https://cdn-icons-png.flaticon.com/512/9625/9625452.png"; // ⚠️ أيقونة الانتظار
        title.textContent = "Wait";
    } else if (type === "end") {
        icon.src = "https://cdn-icons-png.flaticon.com/512/458/458594.png"; // ❌ أيقونة الانتهاء
        title.textContent = "Sorry";
    } 

    msg.textContent = message;
    document.getElementById("joinModal").style.display = "flex";
}

function closeJoinModal() {
    document.getElementById("joinModal").style.display = "none";
}



function closeJoinModal() {
    document.getElementById("joinModal").style.display = "none";
}


function openEditModal(id,date,time){
    document.getElementById("editSessionId").value=id;
    document.getElementById("editDate").value=date;
    document.getElementById("editTime").value=time;
    document.getElementById("editModal").style.display="flex";
}
function closeEditModal(){ document.getElementById("editModal").style.display="none"; }

function saveEdit(){
    let id=document.getElementById("editSessionId").value;
    let date=document.getElementById("editDate").value;
    let time=document.getElementById("editTime").value;
    fetch('mentee_update_session.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`id=${id}&date=${date}&time=${time}`
    })
    .then(res=>res.text())
    .then(data=>{
        if(data.trim()==="success"){
            document.querySelector(`#row-${id} .date-cell`).textContent=date;
            document.querySelector(`#row-${id} .time-cell`).textContent=time;
            closeEditModal();
            alert("✅ تم تحديث الجلسة بنجاح");
        }else{
            alert("❌ حدث خطأ أثناء التحديث");
        }
    });
}

// فتح المودال وجلب الساعات
function openUpdateModal(sessionId){
  fetch("mentee_get_available_slots.php?session_id=" + encodeURIComponent(sessionId))
  .then(r=>r.json())
  .then(data=>{
    let html="";
    if(data.length > 0){
      data.forEach(slot=>{
        html+=`<div style="margin:10px 0;">
          <label>
            <input type="radio" name="slot" value="${slot.date}|${slot.time}"> 
            ${slot.date} - ${slot.time} (45 min)
          </label>
        </div>`;
      });
      html+=`<button class="add-btn" onclick="saveUpdate(${sessionId})">Save</button>`;
    } else {
      html="<p>No available slots</p>";
    }
    document.getElementById("slotsContainer").innerHTML=html;
    document.getElementById("updateModal").style.display="flex";
  });
}

function closeUpdateModal(){
  document.getElementById("updateModal").style.display="none";
}

function saveUpdate(sessionId){
  let val=document.querySelector("input[name='slot']:checked");
  if(!val){ alert("Please select a slot"); return; }
  let [date,time]=val.value.split("|");

  let fd=new FormData();
  fd.append("id",sessionId);
  fd.append("date",date);
  fd.append("time",time);

  // 👈 المهم: مرر قيمة is_public حسب التبويب الحالي
  //fd.append("is_public", = $is_public ); // 0 = Public, 1 = Private

  fetch("mentee_update_session.php",{method:"POST",body:fd})
  .then(r=>r.text()).then(d=>{
    if(d.trim()==="success"){ alert("✅ Updated!"); location.reload(); }
    else{ alert("❌ "+d); }
  });
}


</script>


<script>
function toggleContent(id, el) {
  const allContents = document.querySelectorAll('.footer-content');
  const allArrows = document.querySelectorAll('.arrow');

  allContents.forEach(div => {
    if (div.id !== id) {
      div.style.maxHeight = "0";
      div.style.margin = "0";
    }
  });

  allArrows.forEach(arrow => arrow.classList.remove('down'));

  const content = document.getElementById(id);
  const arrow = el.querySelector('.arrow');

  if (content.style.maxHeight && content.style.maxHeight !== "0px") {
    content.style.maxHeight = "0";
    content.style.margin = "0";
    arrow.classList.remove('down');
  } else {
    content.style.maxHeight = content.scrollHeight + "px";
    content.style.margin = "8px 0 12px 0";
    arrow.classList.add('down');
  }
}
</script>
<script>
function toggleMenu() {
  document.getElementById("navMenu").classList.toggle("active");
}
</script>



<!-- ✅ Confirm Delete Modal -->
<div id="confirmDeleteModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:2500; justify-content:center; align-items:center;">
  <div class="add-session-form" style="max-width:420px; width:90%;">
    <h3 class="form-title">Confirm Delete</h3>
    <p style="text-align:center; margin:-6px 0 16px;">Are you sure you want to delete this session?</p>
    <div style="display:flex; gap:10px;">
      <button id="confirmDeleteBtn" class="add-btn" style="flex:1; background:#ff3b30;">Yes, delete</button>
      <button class="add-btn" style="flex:1; background:#ccc; color:#333;" onclick="closeConfirmDelete()">Cancel</button>
    </div>
  </div>
</div>

<script>
let __pendingDeleteHref = null;

// فتح المودال
function openConfirmDelete(href) {
  __pendingDeleteHref = href;
  document.getElementById('confirmDeleteModal').style.display = 'flex';
}

// إغلاق المودال
function closeConfirmDelete() {
  __pendingDeleteHref = null;
  document.getElementById('confirmDeleteModal').style.display = 'none';
}

// تنفيذ الحذف عند الضغط على الزر الأحمر
document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
  if (__pendingDeleteHref) window.location.href = __pendingDeleteHref;
});

// ربط كل روابط الحذف بالمودال
document.addEventListener('click', function (e) {
  const a = e.target.closest('a.delete-btn');
  if (!a) return;

  const style = window.getComputedStyle(a);
  if (style.pointerEvents === 'none' || a.classList.contains('disabled-link')) return;

  e.preventDefault();
  openConfirmDelete(a.getAttribute('href'));
});

// إغلاق عند النقر خارج المودال
document.getElementById('confirmDeleteModal').addEventListener('click', function (e) {
  if (e.target === this) closeConfirmDelete();
});

// إغلاق بالـ ESC
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeConfirmDelete();
});
</script>

</body>
</html>
