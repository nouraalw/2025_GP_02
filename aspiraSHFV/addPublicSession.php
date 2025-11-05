<?php
/************************************************************
 * 1) بدء الجلسة + الاتصال بقاعدة البيانات + حماية الدخول
 ************************************************************/
include 'db_connection.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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


$mentor_id = (int)$_SESSION['user_id'];
$message   = "";
$success   = false;

// مدة الجلسة (للعرض والحساب)
const GRP_DURATION_MIN = 45;

/************************************************************
 * 2) دوال: حساب نهاية الجلسة + صيانة الحالات + تنظيف الملغاة
 ************************************************************/

/* تحسب وقت نهاية الجلسة */
function grp_end_dt(string $date, string $time, int $mins): DateTime {
    $start = new DateTime("$date $time");
    return (clone $start)->modify("+$mins minutes");
}

/* تحذف أي جلسات ملغاة من الداتابيس */
function purge_cancelled_sessions(mysqli $conn, int $mentorId): void {
    $del = $conn->prepare("DELETE FROM grp_sessions WHERE mentor_id=? AND status='cancelled'");
    $del->bind_param("i", $mentorId);
    $del->execute();
    $del->close();
}

/* صيانة: تحدّث status حسب الوقت وعدد الحاجزين */
function maintain_grp_statuses_silently(mysqli $conn, int $mentorId): void {
    // أولاً: نظف الملغاة نهائياً
    purge_cancelled_sessions($conn, $mentorId);

    $now = new DateTime();

    $q = $conn->prepare("
        SELECT group_session_id, session_date, session_time
        FROM grp_sessions
        WHERE mentor_id = ? AND status <> 'cancelled'
    ");
    $q->bind_param("i", $mentorId);
    $q->execute();
    $rs = $q->get_result();

    while ($row = $rs->fetch_assoc()) {
        $sid   = (int)$row['group_session_id'];
        $date  = $row['session_date'];
        $time  = $row['session_time'];
        $endAt = grp_end_dt($date, $time, GRP_DURATION_MIN);

        // عدّ الحجوزات
        $c = $conn->prepare("SELECT COUNT(*) AS c FROM grp_session_participants WHERE group_session_id=?");
        $c->bind_param("i", $sid);
        $c->execute();
        $booked = (int)$c->get_result()->fetch_assoc()['c'];
        $c->close();

        // حدد الحالة
        if ($endAt <= $now) {
            $newStatus = 'completed';
        } elseif ($booked > 0) {
            $newStatus = 'upcoming';
        } else {
            $newStatus = 'available';
        }

        $u = $conn->prepare("UPDATE grp_sessions SET status=? WHERE group_session_id=? AND mentor_id=?");
        $u->bind_param("sii", $newStatus, $sid, $mentorId);
        $u->execute();
        $u->close();
    }
    $q->close();
}

/* شغّل الصيانة */
maintain_grp_statuses_silently($conn, $mentor_id);

/************************************************************
 * 3) إضافة جلسة عامة
 *    - status = 'available'
 *    - منع التعارض أقل من ساعة مع (sessions) و (grp_sessions: available/upcoming فقط)
 ************************************************************/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (empty($_POST["title"]) || empty($_POST["date"]) || empty($_POST["time"])) {
        $message = "Error: All fields are required!";
    } else {
        $title = trim($_POST["title"]);
        $date  = $_POST["date"];   // session_date
        $time  = $_POST["time"];   // session_time

         // ⛔ تحقق أول: منع إضافة جلسة في الماضي
    $selectedDateTime = strtotime("$date $time");
    if ($selectedDateTime < time()) {
        $message = "❌ Can't add a group session in the past!";
    } else {
        $conflict = false;
        $new_ts   = strtotime($time);

        // تعارض مع الجلسات الخاصة
        $st = $conn->prepare("SELECT time FROM sessions WHERE mentor_id = ? AND date = ?   AND status IN ('available','booked')");
        $st->bind_param("is", $mentor_id, $date);
        $st->execute();
        $rs = $st->get_result();
        while ($r = $rs->fetch_assoc()) {
            if (abs($new_ts - strtotime($r['time']))/3600 < 1) { $conflict = true; break; }
        }
        $st->close();

        // تعارض مع الجلسات العامة (فقط available + upcoming)
        if (!$conflict) {
            $st2 = $conn->prepare("
                SELECT session_time
                FROM grp_sessions
                WHERE mentor_id = ? 
                  AND session_date = ? 
                  AND status IN ('available','upcoming')
            ");
            $st2->bind_param("is", $mentor_id, $date);
            $st2->execute();
            $rs2 = $st2->get_result();
            while ($r2 = $rs2->fetch_assoc()) {
                if (abs($new_ts - strtotime($r2['session_time']))/3600 < 1) { $conflict = true; break; }
            }
            $st2->close();
        }

        if ($conflict) {
            $message = "Can't add session because of an existing session within 1 hour!";
        } else {
            $ins = $conn->prepare("
                INSERT INTO grp_sessions (mentor_id, title, session_date, session_time, status)
                VALUES (?, ?, ?, ?, 'available')
            ");
            $ins->bind_param("isss", $mentor_id, $title, $date, $time);

            if ($ins->execute()) {
                header("Location: addPublicSession.php?msg=added");
                exit();
            } else {
                $message = "Error adding session: " . $conn->error;
            }
            $ins->close();
        }
    }
}
}
/************************************************************
 * 4) حذف جلسة (زر الحذف) = حذف فعلي
 ************************************************************/
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    $del = $conn->prepare("DELETE FROM grp_sessions WHERE group_session_id = ? AND mentor_id = ?");
    $del->bind_param("ii", $delete_id, $mentor_id);

    if ($del->execute()) {
        header("Location: addPublicSession.php?msg=deleted");
        exit();
    } else {
        $message = "Error deleting session: " . $conn->error;
    }
    $del->close();
}

/************************************************************
 * 5) جلب الجلسات للعرض
 *    - فقط المتاحة (status='available') والمستقبلية
 ************************************************************/
$list = $conn->prepare("
    SELECT group_session_id, title, session_date, session_time
    FROM grp_sessions
    WHERE mentor_id = ?
      AND status = 'available'
      AND CONCAT(session_date,' ',session_time) >= NOW()
    ORDER BY session_date, session_time
");
$list->bind_param("i", $mentor_id);
$list->execute();
$availableSessions = $list->get_result()->fetch_all(MYSQLI_ASSOC);
$list->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Group Session</title>
<link rel="icon" type="image/png" href="images/favicon.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
<style>
/* ===== نفس ستايلك ===== */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}

:root{
  --primary-bg:#211742;--form-bg:#fff;--input-bg:#e5e5e5;--input-border:#b3b3b3;
  --button-color:#4b398e;--text-color:#a576ff;--highlight-text:#a576ff;
}
body{background:#f8f8f8}

/* Header */
header{display:flex;justify-content:space-between;align-items:center;padding:15px 50px;background:#fff;position:sticky;top:0;border-bottom:.4px solid rgba(0,0,0,.05)}
  .logo {
            font-size: 24px;
            font-weight: bold;
            color: #a576ff;
            display: flex;
            align-items: center;
            gap: 2px;
        }
.logo img{width:24px;height:auto}
.logo-text{font-size:22px;font-weight:bold;color:#a576ff}
nav{display:flex;gap:30px}
nav a{color:#000;font-size:16px;font-weight:500;text-decoration:none;padding-bottom:5px;border-bottom:2px solid transparent;transition:.3s}
nav a:hover,nav a.active{color:#4b398e;font-weight:bold;border-bottom:2px solid #4b398e}
.logout-btn{text-decoration:none;padding:8px 20px;background:#fff;color:#2D2D69;border:2px solid #2D2D69;border-radius:20px;font-size:14px;font-weight:bold;transition:.3s}
.logout-btn:hover{background:#211742;color:#fff;border:2px solid #211742}

/* Layout */
.content-wrapper{padding-top:20px;max-width:1200px;margin:0 auto}
.main-container{display:flex;flex-wrap:wrap;gap:30px;padding:20px;justify-content:center}

/* Form & List */
.add-session-form,.sessions-list{background:#fff;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1);padding:25px;width:100%;max-width:500px}
.form-title,.sessions-title{font-size:24px;color:#4b398e;margin-bottom:20px;text-align:center}
.form-group{margin-bottom:18px}
.form-group label{display:block;font-size:16px;color:#4b398e;margin-bottom:8px}
.form-group input{width:100%;padding:12px;border-radius:6px;border:1px solid #b3b3b3;font-size:16px;background:#f5f5f5}
.form-group input:focus{outline:none;border-color:#4b398e}
.add-btn{background:#4b398e;color:#fff;padding:12px 25px;border:none;border-radius:6px;font-size:16px;font-weight:bold;cursor:pointer;transition:.3s;width:100%;margin-top:10px}
.add-btn:hover{background:#a576ff;transform:scale(1.02)}

/* Table */
.session-table{width:100%;border-collapse:collapse}
.session-table th{padding:12px;text-align:left;border-bottom:2px solid #e0e0e0;color:#4b398e}
.session-table td{padding:12px;border-bottom:1px solid #e0e0e0;color:#4b398e}
.session-table tr:hover{background:#f8f8f8}
.session-table td:last-child{text-align:center;vertical-align:middle}
.delete-btn{color:#ff3b30;background:none;border:none;cursor:pointer;font-size:18px;transition:.2s}
.delete-btn:hover{transform:scale(1.2)}
.title-wrap{white-space:normal;word-break:break-word;line-height:1.4}

/* Alerts */
.alert{padding:12px;margin-bottom:15px;border-radius:6px;font-size:14px}
.alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
.alert-danger{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}

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


@media(max-width:768px){.main-container{flex-direction:column}.add-session-form,.sessions-list{max-width:100%}}
</style>
 <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
<header>
  <div class="logo">
    <img src="images/logo.png" alt="ASPIRA"><span class="logo-text">SPIRA</span>
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

</style>

<div class="content-wrapper">
    <!-- Sub-tabs -->
<div class="sub-tabs">
  <a href="addPrivateSession.php"
     class="sub-tab <?= basename($_SERVER['PHP_SELF'])=='addPrivateSession.php'?'active':'' ?>">
    Private
  </a>
  <a href="addPublicSession.php"
     class="sub-tab <?= basename($_SERVER['PHP_SELF'])=='addPublicSession.php'?'active':'' ?>">
    Group
  </a>
</div>
    
    
    
    
  <div class="main-container">
    <!-- (A) نموذج إضافة جلسة عامة -->
    <div class="add-session-form">
      <h2 class="form-title">Add Group Session</h2>
      
      

      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success">Session deleted successfully!</div>
      <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
        <div class="alert alert-success">Session added successfully!</div>
      <?php endif; ?>

      <?php if (!empty($message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label for="title">Title</label>
          <input type="text" id="title" name="title" maxlength="120" placeholder="e.g. UX Portfolio Review" required>
        </div>
        <div class="form-group">
          <label for="date">Date</label>
          <input type="date" id="date" name="date" min="<?= date('Y-m-d'); ?>" required>
        </div>
        <div class="form-group">
          <label for="time">Time</label>
          <input type="time" id="time" name="time" required>
        </div>
        <button type="submit" class="add-btn">Add Session</button>
      </form>
    </div>

    <!-- (B) قائمة الجلسات المتاحة فقط -->
    <div class="sessions-list">
      <h2 class="sessions-title">Available Group Sessions</h2>

      <?php if (empty($availableSessions)): ?>
        <p style="text-align:center;color:#4b398e;">No available Group sessions. Add some!</p>
      <?php else: ?>
        <table class="session-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Date</th>
              <th>Time</th>
              <th>Duration</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($availableSessions as $s): ?>
            <tr>
              <td><span class="title-wrap"><?= htmlspecialchars($s['title']); ?></span></td>
              <td><?= htmlspecialchars($s['session_date']); ?></td>
              <td><?= htmlspecialchars($s['session_time']); ?></td>
              <td><?= GRP_DURATION_MIN; ?> min</td>
              <td>
                <a href="addPublicSession.php?delete_id=<?= (int)$s['group_session_id']; ?>"
                   onclick="return confirm('Are you sure you want to delete this session?');"
                   class="delete-btn" title="Delete">
                  <i class="fas fa-trash-alt"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

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
}</script>
</body>
</html>