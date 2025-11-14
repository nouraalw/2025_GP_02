<?php
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


$mentor_id = $_SESSION['user_id'];
$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST["date"]) || empty($_POST["date"]) || !isset($_POST["time"]) || empty($_POST["time"])) {
        $message = "Error: All fields are required!";
    }else {
        $date = $_POST["date"];
        $time = $_POST["time"];

        // ✅ فحص التعارض خلال 60 دقيقة مع الجلسات الخاصة + القروب
date_default_timezone_set('Asia/Riyadh');

// ⛔ تحقق أول: منع الجلسات الماضية
$selectedDateTime = strtotime("$date $time");
if ($selectedDateTime < time()) {
    $message = "❌ Can't add a session in the past!";
} else {

$sql_conflict = "
  SELECT 1
  FROM (
      SELECT CONCAT(date, ' ', time) AS dt
      FROM sessions
      WHERE mentor_id = ? AND date = ?

      UNION ALL

      SELECT CONCAT(session_date, ' ', session_time) AS dt
      FROM grp_sessions
      WHERE mentor_id = ? AND session_date = ?
      -- لا نفلتر بالحالة (available/booked/completed/cancelled)
  ) AS all_slots
  WHERE ABS(TIMESTAMPDIFF(MINUTE, CONCAT(?, ' ', ?), dt)) < 60
  LIMIT 1
";

$stmt_conflict = $conn->prepare($sql_conflict);
$stmt_conflict->bind_param("isisss", $mentor_id, $date, $mentor_id, $date, $date, $time);
$stmt_conflict->execute();
$res_conflict = $stmt_conflict->get_result();

if ($res_conflict && $res_conflict->num_rows > 0) {
    // ⛔ تعارض خلال أقل من ساعة (خاص/قروب)
    $message = "Can't add session because there is another session within 1 hour (private/group).";
} else {
    // ✅ لا يوجد تعارض → أدخل الجلسة في جدول sessions كما هو
    $sql_insert = "INSERT INTO sessions (mentor_id, date, time, status) VALUES (?, ?, ?, 'available')";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iss", $mentor_id, $date, $time);

    if ($stmt_insert->execute()) {
        $success = true;
        $message = "Session added successfully!";
        header("Location: addPrivateSession.php"); // إعادة تحميل الصفحة
        exit();
    } else {
        $message = "Error adding session " . $conn->error;
    }

    $stmt_insert->close();
}
$stmt_conflict->close();

       
    }

  }

}


// استرجاع الجلسات المتاحة من قاعدة البيانات
$sql = "SELECT id, date, time 
        FROM sessions 
        WHERE mentor_id = ? 
          AND status = 'available' 
          AND CONCAT(date, ' ', time) >= NOW()
        ORDER BY date, time";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$result = $stmt->get_result();
$availableSessions = $result->fetch_all(MYSQLI_ASSOC);



$stmt->close();
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Private Session</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        :root {
            --primary-bg: #211742;
            --form-bg: #ffffff;
            --input-bg: #e5e5e5;
            --input-border: #b3b3b3;
            --button-color: #4b398e;
            --text-color: #a576ff;
            --highlight-text: #a576ff;
        }

        body { 
            background-color: #f8f8f8; 
            padding: 0; 
            margin: 0;
        }
        
        /* Header */
       header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 50px;
    background: #ffffff;
    position: sticky;
    top: 0;
    box-shadow: none !important; /* ✅ تأكد من إزالة أي ظل */
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


.logo img {
    width: 24px;
    height: auto;
}


.logo-text {
    font-size: 22px;
    font-weight: bold;
    color: #a576ff; /* لون مطابق للصورة */
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

/* ✅ عند تمرير الماوس أو عند تفعيل الصفحة */
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

        /* Content wrapper */
        .content-wrapper {
            padding-top: 20px;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Main container */
        .main-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            padding: 20px;
            justify-content: center;
        }

        /* Add session form */
        .add-session-form {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.3);
            padding: 25px;
            width: 100%;
            max-width: 500px;
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

        /* Session list */
        .sessions-list {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.3);
            padding: 25px;
            width: 100%;
            max-width: 500px;
        }

        .sessions-title {
            font-size: 24px;
            color: #4b398e;
            margin-bottom: 20px;
            text-align: center;
        }

        .session-table {
            width: 100%;
            border-collapse: collapse;
        }

        .session-table th {
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #e0e0e0;
            color: #4b398e;
        }

        .session-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            color: #4b398e;
        }

        .session-table tr:hover {
            background-color: #f8f8f8;
        }

        .delete-btn {
            color: #ff3b30;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.2s;
        }

        .delete-btn:hover {
            transform: scale(1.2);
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        .BookedSessions{
            
              background: #211742;
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
        }
        
        .BookedSessions:hover{
               background: #a576ff;
    transform: scale(1.05);
            
        }
        
        

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .add-session-form,
            .sessions-list {
                max-width: 100%;
            }
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

    <!-- زر الموبايل -->
<div class="hamburger" onclick="toggleMenu()">☰</div>

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
            <!-- Add Session Form -->
            
            <div class="add-session-form">
                <h2 class="form-title">Add Private Session</h2>
                
                <?php if (!empty($message)): ?>
                  <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
                      <?php echo $message; ?>
                   </div>
                 <?php endif; ?>

                
                <form method="POST" action="">
                    <input type="hidden" name="mentor_id" value="<?php echo $_SESSION['user_id']; ?>">

                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="time">Time</label>
                        <input type="time" id="time" name="time" required>
                    </div>
                    
                    <button type="submit" class="add-btn">Add Session</button>
                </form>
            </div>
            
       <!-- قائمة الجلسات المتاحة -->
<div class="sessions-list">
    <h2 class="sessions-title">Available Sessions</h2>
    
    <?php if (empty($availableSessions)): ?>
        <p style="text-align: center; color: #4b398e;">No available sessions. Add some!</p>
    <?php else: ?>
        <table class="session-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($availableSessions as $session): ?>
                    <tr>
                        <td><?php echo $session['date']; ?></td>
                        <td><?php echo $session['time']; ?></td>
                        <td>  45 min  </td>
          
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


function toggleMenu() {
  document.getElementById("navMenu").classList.toggle("active");
}

</script>


 
</body>
</html>