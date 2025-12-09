<?php
/********************
 * (1) بدء الجلسة + الاتصال + حماية الدخول
 ********************/
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$mentor_id = (int)$_SESSION['user_id'];

/********************
 * (1.5) تحديث الحالات تلقائيًا: أي جلسة انتهت ← completed
 ********************/
date_default_timezone_set('Asia/Riyadh');
$now = date('Y-m-d H:i:s');

$autoCompleteSql = "
  UPDATE grp_sessions
     SET status = 'completed'
   WHERE mentor_id = ?
     AND status IN ('available','upcoming')
     AND TIMESTAMPADD(MINUTE, COALESCE(duration,45), CONCAT(session_date,' ',session_time)) <= ?
";
if ($u = $conn->prepare($autoCompleteSql)) {
  $u->bind_param('is', $mentor_id, $now);
  $u->execute();
  $u->close();
}
// === Avatar for header ===
$user_id = (int)($_SESSION['user_id'] ?? 0);
$cur_avatar = 'images/person.png'; // الافتراضي

if ($user_id) {
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
    if (!empty($row['profile_picture'])) {
      $cur_avatar = $row['profile_picture'];
    }
  }
  $avq->close();
}
/********************
 * (2) إحضار الجلسات التي لم تنتهِ بعد (نهاية الوقت > الآن)
 ********************/
$sql = "
   SELECT 
     gs.group_session_id,
     gs.title,
     gs.session_date,
     gs.session_time,
     COALESCE(gs.duration, 45) AS duration_minutes,
     COUNT(p.mentee_id) AS booked_count
   FROM grp_sessions gs
   LEFT JOIN grp_session_participants p
          ON p.group_session_id = gs.group_session_id
   WHERE gs.mentor_id = ?
     AND gs.status IN ('available','upcoming')
     AND TIMESTAMPADD(MINUTE, COALESCE(gs.duration,45), CONCAT(gs.session_date,' ',gs.session_time)) > NOW()
   GROUP BY gs.group_session_id, gs.title, gs.session_date, gs.session_time, gs.duration
   HAVING COUNT(p.mentee_id) > 0
   ORDER BY gs.session_date ASC, gs.session_time ASC
";
$stmt = $conn->prepare($sql);
if (!$stmt) { die('Error preparing query: '.$conn->error); }
$stmt->bind_param('i', $mentor_id);
if (!$stmt->execute()) { die('Error executing query: '.$stmt->error); }
$result = $stmt->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mentor Upcoming Group Sessions</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link rel="icon" type="image/png" href="images/favicon.png">
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
:root{--purple:#4b398e;--deep:#211742;--ink:#2D2D69}
body{background:#f8f8f8}
header{display:flex;justify-content:space-between;align-items:center;padding:15px 50px;background:#fff;position:sticky;top:0;border-bottom:.4px solid rgba(0,0,0,.05)}
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
nav{display:flex;gap:30px}
nav a{color:#000;font-size:16px;font-weight:500;text-decoration:none;padding-bottom:5px;border-bottom:2px solid transparent;transition:.3s}
nav a:hover,nav a.active{color:var(--purple);border-bottom:2px solid var(--purple)}
.logout-btn{text-decoration:none;padding:8px 20px;background:#fff;color:#2D2D69;border:2px solid #2D2D69;border-radius:20px;font-weight:bold;transition:.3s}
.logout-btn:hover{background:#211742;color:#fff;border-color:#211742}
.wrapper{width:90%;max-width:1200px;margin:22px auto 40px}
h2.section-title{font-weight:bold;color:var(--purple);text-align:center;margin:0 0 10px}
.note{color:#666;font-size:13px;margin:6px 0 20px;text-align:center}
table{width: 80%;
  margin: 20px auto;
  border-collapse: collapse;
  background: #ffffff;
  box-shadow: 0px 2px 10px rgba(0,0,0,0.1);
  border-radius: 10px;}
th,td{padding:14px;border:1px solid #eee;text-align:center}
th{background:var(--purple);color:#fff}
tr:hover{background:#f8f8f8}
.join-btn{text-decoration:none;padding:8px 20px;background:#fff;color:#2D2D69;border:2px solid #2D2D69;border-radius:20px;font-size:14px;font-weight:bold;transition:.3s}
.join-btn:hover{background:#211742;color:#fff;border-color:#211742}



/* ✅ توحيد تصميم المودالات والأزرار لتطابق ASPIRA */
.add-session-form {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
  padding: 25px;
  text-align: center;
  animation: fadeIn 0.25s ease-in-out;
}

.add-btn {
  background: #4b398e;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 10px 20px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
}

.add-btn:hover {
  background: #a576ff;
  transform: scale(1.03);
}

/* تحسين أزرار الجدول */
.icon-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 18px;
  color: inherit;
  padding: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s ease;
}

.icon-btn:hover {
  transform: scale(1.15);
  color: #4b398e;
}

/* أزرار خاصة */
.edit-btn { color: #211742; }
.delete-btn { color: #ff3b30; }
.delete-btn:hover { color: #d60c0c; transform: scale(1.2); }



/* مودال التأكيد */
#confirmDeleteModal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  justify-content: center;
  align-items: center;
  z-index: 2500;
  
}



.disabled{opacity:.5;pointer-events:none;cursor:not-allowed}
#editModal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);justify-content:center;align-items:center;z-index:2000}
.modal-card{background:#fff;border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,.12);width:90%;max-width:420px;padding:22px}
.modal-card h3{margin:0 0 12px;color:var(--purple)}
.form-group{margin-bottom:14px;text-align:left}
.form-group label{display:block;margin-bottom:6px;color:var(--purple)}
.form-group input{width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;background:#f7f7f7}
.btn{display:inline-block;padding:10px 16px;border-radius:8px;border:none;cursor:pointer;font-weight:600}
.btn-primary{background:var(--purple);color:#fff}
.btn-ghost{background:#eee;color:#333}
.btn + .btn{margin-left:8px}
#joinModal{display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); justify-content:center; align-items:center;}
#joinModal .card{background:#fff; padding:26px; border-radius:10px; text-align:center; max-width:400px; width:90%;}
.small{font-size:12px;color:#666;margin-top:6px}
.sub-tabs{display:flex;gap:10px;justify-content:center;margin:12px 0 22px}
.sub-tab{padding:8px 20px;border-radius:20px;font-weight:500;text-decoration:none;background:#eee;color:#4b398e;transition:0.3s}
.sub-tab.active{background:#4b398e;color:#fff}
.sub-tab:hover{filter:brightness(.95)}

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
   <!-- زر الموبايل -->
  <div class="hamburger" onclick="toggleMenu()">☰</div>

  <!-- القوائم -->
  <nav id="navMenu">
    <a href="addPrivateSession.php" class="<?= basename($_SERVER['PHP_SELF']) == 'addPrivateSession.php' ? 'active' : '' ?>">Add Sessions</a>
    <a href="MentorUpcomingSession.php" class="<?= basename($_SERVER['PHP_SELF']) == 'MentorUpcomingSession.php' ? 'active' : '' ?>">Upcoming Sessions</a>
    <a href="mentor_past_sessions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'mentor_past_sessions.php' ? 'active' : '' ?>">Past Sessions</a>
  </nav>

  <div class="profile-menu">
    <img src="<?php echo htmlspecialchars($cur_avatar, ENT_QUOTES); ?>" class="profile-pic" alt="Profile">
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
/* Footer */
        footer {
            text-align: center;
            padding: 10px;
            background: #ffffff;
            color: #211742;
            margin-top: 300px;
            box-shadow: none;
            border-top: 0.3px solid rgba(0, 0, 0, 0.05);
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

</style>


<div class="wrapper">
  <h2 class="section-title">Upcoming Group Sessions</h2>
<div class="sub-tabs">
  <a href="MentorUpcomingSession.php" class="sub-tab <?= basename($_SERVER['PHP_SELF'])=='MentorUpcomingSession.php'?'active':'' ?>">Private</a>
  <a href="MentorUpcomingGroupSessions.php" class="sub-tab <?= basename($_SERVER['PHP_SELF'])=='MentorUpcomingGroupSessions.php'?'active':'' ?>">Group</a>
</div>

  <table>
    <thead>
      <tr>
        <th>Title</th>
        <th>Date</th>
        <th>Time</th>
        <th>Duration</th>
        <th>Booked</th>
        <th>Join</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="public-body">
      <?php if ($result && $result->num_rows > 0): ?>
        <?php 
          date_default_timezone_set('Asia/Riyadh');
          while ($row = $result->fetch_assoc()):
            $startTs = strtotime($row['session_date'].' '.$row['session_time']);
            $durMin  = (int)$row['duration_minutes'];
            $endTs   = $startTs + $durMin * 60;
            $nowTs   = time();

            $isFuture     = ($nowTs < $startTs);
            $isOngoing    = ($nowTs >= $startTs && $nowTs < $endTs);
            $minsToStart  = (int) floor(($startTs - $nowTs) / 60);
            $lockActions  = $isOngoing || ($isFuture && $minsToStart <= 15);
        ?>
        <tr id="pub-<?= (int)$row['group_session_id'] ?>">
          <td><?= htmlspecialchars($row['title']) ?></td>
          <td class="u-date"><?= htmlspecialchars($row['session_date']) ?></td>
          <td class="u-time"><?= htmlspecialchars($row['session_time']) ?></td>
          <td><?= (int)$row['duration_minutes'] ?> min</td>
          <td class="u-booked"><?= (int)$row['booked_count'] ?></td>
          <td>
            <?php if ($isFuture): ?>
<button class="join-btn" onclick="showJoinModal('The session hasn’t started yet. Please wait!', 'wait')">Join</button>
              <?php elseif ($isOngoing): ?>
                 <a href="WEB_UIKITS.php?roomID=group_<?= (int)$row['group_session_id'] ?>" class="join-btn">Join</a>
               <?php else: ?>
              Session Ended
            <?php endif; ?>
          </td>
          <td>
      
  <!-- زر التعديل -->
  <button class="icon-btn edit-btn"
      onclick="openEditModal(<?= (int)$row['group_session_id'] ?>, '<?= htmlspecialchars($row['session_date']) ?>', '<?= htmlspecialchars($row['session_time']) ?>')"
      <?= $lockActions ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
      <i class="fas fa-edit"></i>
  </button>

  <!-- زر الحذف -->
  <a href="delete_group_session.php?id=<?= (int)$row['group_session_id'] ?>"
     class="icon-btn delete-btn <?= $lockActions ? 'disabled-link' : '' ?>"
     <?= $lockActions ? 'onclick="return false;" style="opacity:0.5;pointer-events:none;cursor:not-allowed;"' : 'onclick="openConfirmDelete(this.href); return false;"' ?>>
      <i class="fas fa-trash-alt"></i>
  </a>
</td>



        </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="7">No booked group sessions yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Edit Modal -->
<div id="editModal">
  <div class="modal-card">
    <h3>Edit Group Session</h3>
    <input type="hidden" id="editId">
    <div class="form-group">
      <label for="editDate">Date</label>
      <input type="date" id="editDate">
    </div>
    <div class="form-group">
      <label for="editTime">Time</label>
      <input type="time" id="editTime">
    </div>
    <div style="text-align:right">
      <button class="btn btn-primary" id="saveEditBtn" onclick="saveEdit()">Save</button>
      <button class="btn btn-ghost" onclick="closeEditModal()">Cancel</button>
    </div>
    <p class="note">When saving, all booked mentees will be notified by email</p>
  </div>
</div>

<!-- ✅ Join Modal (Matching Mentor Style) -->
<div id="joinModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
 background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
  <div style="background:#fff; padding:30px; border-radius:10px; text-align:center; max-width:400px; width:90%;">
      <img id="modalIcon" src="" alt="Status Icon"
           style="width: 80px; height: 80px; margin-bottom: 10px;">
      <h2 id="modalTitle" style="color:#4b398e;">Title</h2>
      <p id="joinModalMessage" style="margin-top:10px;">Message</p>
      <button onclick="closeJoinModal()" 
              style="margin-top:20px; background-color:#4b398e; color:#fff;
                     padding:10px 20px; border:none; border-radius:6px;
                     font-weight:bold; cursor:pointer;">OK</button>
  </div>
</div>


<script>
function openEditModal(id, date, time){
  document.getElementById('editId').value = id;
  document.getElementById('editDate').value = date;
  document.getElementById('editTime').value = time;
  document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal(){ document.getElementById('editModal').style.display = 'none'; }

function showJoinModal(message, type = "wait") {
    const icon = document.getElementById("modalIcon");
    const title = document.getElementById("modalTitle");
    const msg   = document.getElementById("joinModalMessage");

    if (type === "wait") {
        icon.src = "https://cdn-icons-png.flaticon.com/512/9625/9625452.png";
        title.textContent = "Wait";
    } else if (type === "end") {
        icon.src = "https://cdn-icons-png.flaticon.com/512/458/458594.png";
        title.textContent = "Sorry";
    }

    msg.textContent = message;
    document.getElementById("joinModal").style.display = "flex";
}

function closeJoinModal() {
    document.getElementById("joinModal").style.display = "none";
}

function saveEdit(){
  const id   = document.getElementById('editId').value;
  const date = document.getElementById('editDate').value;
  const time = document.getElementById('editTime').value;
  const btn  = document.getElementById('saveEditBtn');

  if(!date || !time){ alert('Please fill date and time'); return; }
  btn.disabled = true;

  fetch('update_group_session.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: `id=${encodeURIComponent(id)}&date=${encodeURIComponent(date)}&time=${encodeURIComponent(time)}`
  })
  .then(r => r.text())
  .then(t => {
    const ans = t.trim();
    if (ans === 'success') {
      const row = document.getElementById(`pub-${id}`);
      if (row){
        row.querySelector('.u-date').textContent = date;
        row.querySelector('.u-time').textContent = time;
      }
      closeEditModal();
      return;
    }
    if (ans === 'conflict_private') {
      alert('❌ Cannot save this time: there is a private session within less than 1 hour.');
    } else if (ans === 'conflict_group') {
      alert('❌ Cannot save this time: there is another group session within less than 1 hour.');
    } else if (ans === 'too_close') {
      alert('❌ Cannot edit less than 1 hour before the session starts.');
    } else if (ans === 'past_time') {
      alert('❌ The selected date/time is in the past.');
    } else if (ans === 'not found') {
      alert('❌ Session not found or you do not have permission.');
    } else {
      alert('❌ Update failed: ' + ans);
    }
  })
  .catch(() => alert('❌ Connection error with the server'))
  .finally(() => { btn.disabled = false; });
}

// function deleteGroupSession(id){
//   if(!confirm('Are you sure you want to delete this session?')) return;

//   fetch(`delete_group_session.php?id=${encodeURIComponent(id)}`, { method:'GET' })
//     .then(r => r.text())
//     .then(t => {
//       const ans = t.trim();
//       if(ans === 'success'){
//         const row = document.getElementById(`pub-${id}`);
//         if(row) row.remove();
//         alert('🗑️ Deleted successfully and notified all mentees');
//       } else if (ans === 'too_late') {
//         alert('❌ Cannot delete within 1 hour of start time.');
//       } else if (ans === 'not found') {
//         alert('❌ Session not found or you do not have permission.');
//       } else {
//         alert('❌ Delete failed: ' + ans);
//       }
//     })
//     .catch(e => alert('Error: ' + e));
// }
</script>
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



