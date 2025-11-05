<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connection.php'; 
// ✅ تحديث الجلسات المنتهية: booked → cancelled بعد انتهاء الوقت (45 دقيقة)
date_default_timezone_set('Asia/Riyadh');

$mentor_id = (int)($_SESSION['user_id'] ?? 0);
$now = date('Y-m-d H:i:s');

$upd = $conn->prepare("
  UPDATE sessions
  SET status = 'cancelled'
  WHERE mentor_id = ?
    AND status = 'booked'
    AND TIMESTAMP(date, time) + INTERVAL 45 MINUTE <= ?
");
$upd->bind_param('is', $mentor_id, $now);
$upd->execute();
$upd->close();

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


$mentor_id = $_SESSION['user_id']; 
$query = "
  SELECT
    s.id, s.date, s.time, s.room_id,
    u.first_name, u.last_name, m.interests
  FROM sessions s
  LEFT JOIN mentees m ON s.mentee_id = m.user_id
  LEFT JOIN users   u ON m.user_id   = u.user_id
  WHERE s.mentor_id = ?
    AND s.status IN ('booked','cancelled')   -- نعرض الاثنين
  ORDER BY
    -- 0 للأحداث القادمة/الحيّة، 1 للمنتهية ⇒ المنتهية تروح تحت
    (TIMESTAMP(s.date, s.time) + INTERVAL 45 MINUTE <= NOW()) ASC,

    -- داخل غير المنتهية (فوق): تاريخ/وقت تصاعدي (الأقرب أولاً)
    CASE
      WHEN (TIMESTAMP(s.date, s.time) + INTERVAL 45 MINUTE > NOW())
      THEN s.date END ASC,
    CASE
      WHEN (TIMESTAMP(s.date, s.time) + INTERVAL 45 MINUTE > NOW())
      THEN s.time END ASC,

    -- داخل المنتهية (تحت): تاريخ/وقت تنازلي (الأحدث انتهاءً أولاً)
    CASE
      WHEN (TIMESTAMP(s.date, s.time) + INTERVAL 45 MINUTE <= NOW())
      THEN s.date END DESC,
    CASE
      WHEN (TIMESTAMP(s.date, s.time) + INTERVAL 45 MINUTE <= NOW())
      THEN s.time END DESC
";




$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error in preparing the query: " . $conn->error);
}


if (empty($mentor_id)) {
    die("Error : mentor_id not found ");
}

$stmt->bind_param('i', $mentor_id);
if (!$stmt->execute()) {
    die("Error in executing the query: " . $stmt->error);
}

$result = $stmt->get_result();
if (!$result) {
    die("Error while fetching results: " . $stmt->error);
}

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Upcoming Sessions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

       <link rel="icon" type="image/png" href="images/favicon.png">
    <style>
        
                      	/* Color Variables */
 @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
 * {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Poppins', sans-serif;
}
body{background:#f8f8f8}
:root {
    --primary-bg: #211742;
    --form-bg: #ffffff;
    --input-bg: #e5e5e5;
    --input-border: #b3b3b3;
    --button-color: #4b398e;
    --text-color: #a576ff;
    --highlight-text: #a576ff;
}


header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 50px;
    background: #ffffff;
    position: sticky;
    top: 0;
    box-shadow: none !important; 
    border-bottom: 0.4px solid rgba(0, 0, 0, 0.05); /* ✅ خط أخف */
}

/* ✅ تحسين الشعار */
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

/* ✅ تحسين الروابط */
nav {
    display: flex;
    gap: 30px;
}

/* ✅ تحسين الروابط داخل الهيدر */
nav a {
    color: black;
    font-size: 16px;
    font-weight: 500;
    text-decoration: none;
    padding-bottom: 5px;
    border-bottom: 2px solid transparent;
    transition: color 0.3s ease, border-bottom 0.3s ease;
}


nav a:hover, nav a.active {
    color: #4b398e;
    font-weight: bold;
    border-bottom: 2px solid #4b398e; /* خط بنفسجي يظهر عند التفعيل */
}


/* ✅ تحسين زر تسجيل الدخول */
.logout-btn {
    text-decoration: none;
    padding: 8px 20px;
    background-color: white; /* ✅ خلفية بيضاء */
    color: #2D2D69; /* ✅ لون النص مطابق للحدود */
    border: 2px solid #2D2D69; /* ✅ لون الأطراف */
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
    transition: background 0.3s ease, color 0.3s ease, border 0.3s ease;
}

/* ✅ تأثير الهوفر */
.logout-btn:hover {
    background-color: #211742; /* ✅ يتغير لون الخلفية */
    color: white; /* ✅ النص يصبح أبيض */
    border: 2px solid #211742; /* ✅ يتغير لون الحدود */
}


table {
    width: 80%;
    margin: 20px auto;
    border-collapse: collapse;
    background: #ffffff;
    box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}

th, td {
    padding: 15px;
    border: 1px solid #ddd;
    text-align: center;
}

th {
    background-color: #4b398e;
    color: white;
}

tr:hover {
    background-color: #f1f1f1;
}
        .join-btn {
   text-decoration: none;
    padding: 8px 20px;
    background-color: white; /* ✅ خلفية بيضاء */
    color: #2D2D69; /* ✅ لون النص مطابق للحدود */
    border: 2px solid #2D2D69; /* ✅ لون الأطراف */
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
    transition: background 0.3s ease, color 0.3s ease, border 0.3s ease;
}

.join-btn:hover {
    background-color: #211742; /* ✅ يتغير لون الخلفية */
    color: white; /* ✅ النص يصبح أبيض */
    border: 2px solid #211742; /* ✅ يتغير لون الحدود */
}

.session-ended {
    background-color: #f0f0f0;
    color: gray;
}
.icon-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.2s;
    color: inherit; /* يرث اللون الافتراضي */
    padding: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.delete-btn {
    color: #ff3b30;
}

.delete-btn:hover {
    transform: scale(1.2);
}

.edit-btn {
    color: #211742; /* لون أزرق للتعديل، تقدر تغيره */
}

.edit-btn:hover {
    transform: scale(1.2);
}
.add-session-form {
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.3);
    padding: 25px;
    width: 100%;
    max-width: 500px;
    animation: fadeIn 0.3s ease;
}

.form-title {
    font-size: 24px;
    color: #4b398e;
    margin-bottom: 20px;
    text-align: center;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 16px;
    color: #4b398e;
    margin-bottom: 8px;
}

.form-group input {
    width: 100%;
    padding: 12px;
    border-radius: 6px;
    border: 1px solid #b3b3b3;
    font-size: 16px;
    background-color: #f5f5f5;
}

.form-group input:focus {
    outline: none;
    border-color: #4b398e;
}

.add-btn {
    background: #4b398e;
    color: white;
    padding: 12px 25px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
    margin-top: 10px;
}

.add-btn:hover {
    background: #a576ff;
    transform: scale(1.02);
}

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
        
.sub-tabs {
  display:flex;
  gap:10px;
  justify-content:center;
  margin:12px 0 22px;
}

.sub-tab {
  padding:8px 20px;
  border-radius:20px;
  font-weight:500;
  text-decoration:none;
  background:#eee;       /* الافتراضي رمادي */
  color:#4b398e;         /* النص بنفسجي */
  transition:0.3s;
}

.sub-tab.active {
  background:#4b398e;   /* كحلي */
  color:#fff;           /* النص أبيض */
}

.sub-tab:hover {
  filter:brightness(.95);
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
        <a href="addPrivateSession.php" class="<?= basename($_SERVER['PHP_SELF']) == 'addPrivateSession.php' ? 'active' : '' ?>">Add Sessions</a>

        <a href="MentorUpcomingSession.php" class="<?= basename($_SERVER['PHP_SELF']) == 'MentorUpcomingSession.php' ? 'active' : '' ?>">Upcoming Sessions</a>
        <a href="mentor_past_sessions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'mentor_past_sessions.php' ? 'active' : '' ?>">Past Sessions</a>
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

    <main>
        <br>
        <h2 style='font-weight: bold; color: #4b398e; text-align: center;'>Upcoming Sessions</h2>
<div class="sub-tabs">
  <a href="MentorUpcomingSession.php"
     class="sub-tab <?= basename($_SERVER['PHP_SELF'])=='MentorUpcomingSession.php'?'active':'' ?>">
    Private
  </a>
  <a href="MentorUpcomingGroupSessions.php"
     class="sub-tab <?= basename($_SERVER['PHP_SELF'])=='MentorUpcomingGroupSessions.php'?'active':'' ?>">
    Group
  </a>
</div>


        <table>
            <tr>
    <th>Mentee Name</th>
    <th>Bio</th>
    <th>Date</th>
    <th>Time</th>
    <th>Duration</th>
    <th>Join</th>
    <th>Actions</th> <!-- ✅ العمود الجديد -->
</tr>
<tbody id="sessions-table-body">
<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <?php
        date_default_timezone_set('Asia/Riyadh');
        $current_time = date("H:i:s");
        $current_date = date("Y-m-d");

        $session_time = date("H:i:s", strtotime($row['time']));
        $session_date = $row['date'];
        $room_id = $row['room_id'];

        $session_end_timestamp = strtotime($session_time) + 45 * 60;
        $isEnded = ($session_date < $current_date) || ($session_date == $current_date && $session_end_timestamp < strtotime($current_time));

        // حساب الوقت المتبقي للجلسة بالثواني
        $session_datetime = strtotime($session_date . ' ' . $session_time);
        $time_diff = $session_datetime - time();
        ?>
        <tr id="row-<?= $row['id'] ?>" class="<?= $isEnded ? 'session-ended' : '' ?>">
    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
    <td><?= htmlspecialchars($row['interests'] ?? 'No bio available') ?></td>
    <td class="date-cell"><?= htmlspecialchars($row['date']) ?></td>
    <td class="time-cell"><?= htmlspecialchars($row['time']) ?></td>
   

            <td>45 min</td>

            <!-- ✅ عمود Join كما هو -->
            <td>
                <?php
                if ($session_date > $current_date || ($session_date == $current_date && $session_time > $current_time)) {
                    echo "<button class='join-btn' onclick='showJoinModal(\"The session hasn’t started yet. Please wait!\", \"wait\")'>Join</button>";
                } elseif ($isEnded) {
                    echo "Session Ended";
                } else {
                    echo "<a href='WEB_UIKITS.php?roomID=" . htmlspecialchars($room_id) . "' class='join-btn'>Join</a>";
                }
                ?>
            </td>

           <td>
    <!-- زر التعديل -->
    <button class="icon-btn edit-btn" 
        onclick="openEditModal(<?= $row['id'] ?>, '<?= $row['date'] ?>', '<?= $row['time'] ?>')" 
        <?= ($time_diff <= 3600 || $isEnded) ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
        <i class="fas fa-edit"></i>
    </button>

    <!-- زر الحذف -->
    <a href="delete_session.php?id=<?= $row['id'] ?>" 
       class="icon-btn delete-btn <?= ($time_diff <= 3600 || $isEnded) ? 'disabled-link' : '' ?>" 
       <?= ($time_diff <= 3600 || $isEnded) ? 'onclick="return false;" style="opacity:0.5;pointer-events:none;cursor:not-allowed;"' : '' ?>>
        <i class="fas fa-trash-alt"></i>
    </a>
</td>



        
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="7">There are no upcoming Sessions.</td></tr>
<?php endif; ?>
</tbody>

        </table>
    </main>
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


    <!-- ✅ Session Status Modal -->
<!-- ✅ Modal Template -->
<div id="joinModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:30px; border-radius:10px; text-align:center; max-width:400px; width:90%;">
        <img id="modalIcon" src="" alt="Status Icon" style="width: 80px; height: 80px; margin-bottom: 10px;">
        <h2 id="modalTitle" style="color:#4b398e;">Title</h2>
        <p id="joinModalMessage" style="margin-top:10px;">Message</p>
        <button onclick="closeJoinModal()" style="margin-top:20px; background-color:#4b398e; color:#fff; padding:10px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">OK</button>
    </div>
</div>


<script>
function showJoinModal(message, type = "wait") {
    const icon = document.getElementById("modalIcon");
    const title = document.getElementById("modalTitle");
    const msg = document.getElementById("joinModalMessage");

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


</script>

<script>
// ✅ فتح المودال
function openEditModal(id, date, time) {
    document.getElementById("editSessionId").value = id;
    document.getElementById("editDate").value = date;
    document.getElementById("editTime").value = time;
    document.getElementById("editModal").style.display = "flex";
}
// ✅ إغلاق المودال
function closeEditModal() {
    document.getElementById("editModal").style.display = "none";
}
// ✅ حفظ التعديل بـ AJAX
function saveEdit() {
    let id = document.getElementById("editSessionId").value;
    let date = document.getElementById("editDate").value;
    let time = document.getElementById("editTime").value;
    fetch('update_session.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&date=${date}&time=${time}`
    })
    .then(res => res.text())
    .then(data => {
  const msg = data.trim();
  if (msg === "success") {
    document.querySelector(`#row-${id} .date-cell`).textContent = date;
    document.querySelector(`#row-${id} .time-cell`).textContent = time;
    closeEditModal();
  } else if (msg === "conflict") {
    alert("❌ Can't save: there is another private/group session within 1 hour.");
  } else if (msg === "too_close_to_start") {
    alert("⛔ You can't edit within 1 hour of start time.");
  } else if (msg === "forbidden") {
    alert("⛔ Not allowed to edit this session.");
  } else {
    alert("❌ Error while updating.");
  }
});


}
</script>

<script>
let __pendingDeleteHref = null;

// افتح مودال التأكيد
function openConfirmDelete(href) {
  __pendingDeleteHref = href;
  document.getElementById('confirmDeleteModal').style.display = 'flex';
}

// أغلق المودال
function closeConfirmDelete() {
  __pendingDeleteHref = null;
  document.getElementById('confirmDeleteModal').style.display = 'none';
}

// تأكيد الحذف
document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
  if (__pendingDeleteHref) {
    // الانتقال للرابط (الحذف عبر delete_session.php)
    window.location.href = __pendingDeleteHref;
  }
});

// اربط كل روابط الحذف بالمودال
document.addEventListener('click', function (e) {
  const a = e.target.closest('a.delete-btn');
  if (!a) return;

  // تجاهل لو الرابط مُعطّل بصريًا أو فيه منع بالنقر
  const style = window.getComputedStyle(a);
  const disabledByStyle = style.pointerEvents === 'none' || a.classList.contains('disabled-link');
  if (disabledByStyle) return;

  // منع الانتقال الفوري وافتح المودال
  e.preventDefault();
  openConfirmDelete(a.getAttribute('href'));
});

// إغلاق المودال بالضغط خارج الصندوق
document.getElementById('confirmDeleteModal').addEventListener('click', function (e) {
  if (e.target === this) closeConfirmDelete();
});

// إغلاق بالـ ESC
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeConfirmDelete();
});
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

</body>
</html>