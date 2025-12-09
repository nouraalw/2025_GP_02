<?php
session_start();
include 'db_connection.php';



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

// إحصائيات تقييم المنتور (المتوسط + عدد المراجعات)
$stat = $conn->prepare("
  SELECT ROUND(AVG(r.rating), 2) AS avg_rating,
         COUNT(r.id)          AS total_reviews
  FROM sessions s
  LEFT JOIN ratings r ON r.session_id = s.id
  WHERE s.mentor_id = ? AND s.status = 'completed'
");
$stat->bind_param("i", $mentor_id);
$stat->execute();
$stats = $stat->get_result()->fetch_assoc();



// $query = "SELECT s.id, s.date, s.time,
//                  u.first_name, u.last_name,
//                  m.interests,
//                  r.rating, r.comment
//           FROM sessions s
//           JOIN mentees m ON s.mentee_id = m.user_id
//           JOIN users   u ON m.user_id   = u.user_id
//           LEFT JOIN ratings r ON r.session_id = s.id
//           WHERE s.mentor_id = ? AND s.status = 'completed'
//           ORDER BY s.date DESC, s.time DESC";

$query = "SELECT s.id, s.date, s.time,
                 s.summary, s.summary_pdf,   -- ✅ نجيب الملخص والـ PDF
                 u.first_name, u.last_name,
                 m.interests
          FROM sessions s
          JOIN mentees m ON s.mentee_id = m.user_id
          JOIN users   u ON m.user_id   = u.user_id
          WHERE s.mentor_id = ? AND s.status = 'completed'
          ORDER BY s.date DESC, s.time DESC";


$stmt = $conn->prepare($query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Past Sessions - Mentor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

       <link rel="icon" type="image/png" href="images/favicon.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
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

.logo-text {
    font-size: 22px;
    font-weight: bold;
    color: #a576ff; /* لون مطابق للصورة */
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
        body{background:#f8f8f8}
        
        
        
        
        
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
    <h2 style='text-align: center; color: #4b398e;'>Past Sessions</h2>
 
<!-- Sub-tabs -->
<div class="sub-tabs">
  <a href="mentor_past_sessions.php"
     class="sub-tab <?= basename($_SERVER['PHP_SELF'])=='mentor_past_sessions.php'?'active':'' ?>">
    Private
  </a>
  <a href="mentor_past_group_sessions.php"
     class="sub-tab <?= basename($_SERVER['PHP_SELF'])=='mentor_past_group_sessions.php'?'active':'' ?>">
    Group
  </a>
</div>
    <table>
        <tr>
            <th>Mentee Name</th>
            <th>Bio</th>
            <th>Date</th>
            <th>Time</th>
            <th>Summary</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                    <td><?= htmlspecialchars($row['interests'] ?? 'No bio available') ?></td>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td><?= htmlspecialchars($row['time']) ?></td>
<td>
  <?php if (!empty($row['summary'])): ?>
    <button 
      type="button"
      class="view-summary-btn"
      data-session="<?= $row['id'] ?>"
      data-summary="<?= htmlspecialchars($row['summary']) ?>"
      style="display:inline-block; padding:8px 15px; background:#4b398e; color:#fff; border-radius:6px; text-decoration:none; font-size:14px; font-weight:500; border:none; cursor:pointer;">
       View Summary
    </button>
  <?php else: ?>
    <button 
      class="add-summary-btn"
      data-session="<?= $row['id'] ?>"
      style="display:inline-block; padding:8px 15px; background:#4b398e; color:#fff; border-radius:6px; text-decoration:none; font-size:14px; font-weight:500; border:none; cursor:pointer;">
       Add Summary
    </button>
  <?php endif; ?>
</td>




                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">No completed sessions found.</td></tr>
        <?php endif; ?>
    </table>
</main>
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

<div id="summaryModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3 id="modalTitle">Session Summary</h3>

    <form id="summaryForm" action="save_summary.php" method="POST">
      <input type="hidden" name="session_id" id="modalSessionId">
      <textarea 
        name="summary" 
        id="summaryText" 
        rows="10" 
        placeholder="Write the key points discussed..." 
        readonly></textarea>
      <br>
      <div style="display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" id="editBtn" style="display:none; background:#4b398e; color:#fff; border:none; padding:8px 20px; border-radius:6px; cursor:pointer;">️Edit</button>
        <button type="submit" id="saveBtn" style="display:none; background:#4b398e; color:#fff; border:none; padding:8px 20px; border-radius:6px; cursor:pointer;">Save</button>
      </div>
    </form>
  </div>
</div>


<style>
.modal {
  display: none; 
  position: fixed; 
  z-index: 1000;
  left: 0; top: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.5);
}
.modal-content {
  background: #fff;
  margin: 10% auto;
  padding: 20px;
  border-radius: 10px;
  width: 50%;
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.modal textarea {
  width: 100%;
  font-size: 14px;
  padding: 10px;
}
.modal .close {
  float: right;
  font-size: 22px;
  cursor: pointer;
}
#saveSummaryBtn {
  margin-top: 10px;
  padding: 8px 20px;
  background: #4b398e;
  color: #fff;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}
#saveSummaryBtn:disabled {
  background: #ccc;
  cursor: not-allowed;
}
</style>


<script>
const modal = document.getElementById("summaryModal");
const closeBtn = document.querySelector(".modal .close");
const sessionInput = document.getElementById("modalSessionId");
const summaryText = document.getElementById("summaryText");
const editBtn = document.getElementById("editBtn");
const saveBtn = document.getElementById("saveBtn");

// ✅ Add Summary
document.querySelectorAll(".add-summary-btn").forEach(btn => {
  btn.addEventListener("click", () => {
    modal.style.display = "block";
    sessionInput.value = btn.dataset.session;
    summaryText.value = "";
    summaryText.readOnly = false;
    editBtn.style.display = "none";
    saveBtn.style.display = "inline-block";
  });
});

// ✅ View Summary
document.querySelectorAll(".view-summary-btn").forEach(btn => {
  btn.addEventListener("click", () => {
    modal.style.display = "block";
    sessionInput.value = btn.dataset.session;
    summaryText.value = btn.dataset.summary || "No summary available.";
    summaryText.readOnly = true;
    editBtn.style.display = "inline-block";
    saveBtn.style.display = "none";
  });
});

// ✅ Edit Summary
editBtn.addEventListener("click", () => {
  summaryText.readOnly = false;
  editBtn.style.display = "none";
  saveBtn.style.display = "inline-block";
});

// ✅ Save Summary (AJAX)
saveBtn.addEventListener("click", (e) => {
  e.preventDefault();
  const sessionId = sessionInput.value;
  const summary = summaryText.value.trim();
  if (!summary) return alert("Summary cannot be empty.");

  fetch("save_summary.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `session_id=${encodeURIComponent(sessionId)}&summary=${encodeURIComponent(summary)}`
  })
  .then(res => res.text())
  .then(data => {
    if (data.trim() === "success") {
      alert("Summary saved successfully!");
      modal.style.display = "none";
      location.reload();
    } else {
      alert("No changes were made or an error occurred.");
    }
  })
  .catch(err => console.error("Error:", err));
});

// إغلاق المودال
closeBtn.onclick = () => modal.style.display = "none";
window.onclick = e => { if (e.target == modal) modal.style.display = "none"; }
</script>



<script>
function toggleMenu() {
  document.getElementById("navMenu").classList.toggle("active");
}
</script>

</body>
</html>
