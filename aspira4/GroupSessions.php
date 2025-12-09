<?php
/*********************************************************
 * (1) بدء الجلسة + الاتصال + حماية الوصول (مستخدم = منتي)
 *********************************************************/
session_start();
include 'db_connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user_id = (int)($_SESSION['user_id'] ?? 0); // ← ضعه بعد session_start مباشرة

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


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mentee_id = (int)$_SESSION['user_id'];

/*********************************************************
 * (2) حجز عبر AJAX:
 * - نسمح بالحجز فقط إذا الجلسة حالتها available/upcoming
 * - موعدها بالمستقبل
 * - لم يصل العدد للسعة
 * - نفس المنتي ما يكون حجزها قبل
 *********************************************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['session_id'])) {
    header('Content-Type: application/json; charset=utf-8');

    $session_id = (int)$_POST['session_id'];

    // تحقق الجلسة قابلة للحجز
    $q = $conn->prepare("
        SELECT gs.group_session_id, COALESCE(gs.capacity,100) AS capacity,
               gs.session_date, gs.session_time, gs.status
        FROM grp_sessions gs
        WHERE gs.group_session_id = ?
          AND gs.status IN ('available','upcoming')
          AND CONCAT(gs.session_date,' ',gs.session_time) >= NOW()
        LIMIT 1
    ");
    $q->bind_param("i", $session_id);
    $q->execute();
    $sess = $q->get_result()->fetch_assoc();
    $q->close();

    if (!$sess) {
        echo json_encode(["success"=>false, "message"=>"This session is not available to book."]);
        exit;
    }

    // هل المنتي حاجزها من قبل؟
    $q = $conn->prepare("
        SELECT 1 FROM grp_session_participants
        WHERE group_session_id=? AND mentee_id=? LIMIT 1
    ");
    $q->bind_param("ii", $session_id, $mentee_id);
    $q->execute();
    $already = $q->get_result()->fetch_assoc();
    $q->close();

    if ($already) {
        echo json_encode(["success"=>false, "message"=>"You already booked this session."]);
        exit;
    }

    // عدد الحجوزات الحالية
    $q = $conn->prepare("
        SELECT COUNT(*) AS c
        FROM grp_session_participants
        WHERE group_session_id=?
    ");
    $q->bind_param("i", $session_id);
    $q->execute();
    $booked = (int)$q->get_result()->fetch_assoc()['c'];
    $q->close();

    $capacity = (int)$sess['capacity'];
    if ($booked >= $capacity) {
        echo json_encode(["success"=>false, "message"=>"Sorry, this session is full."]);
        exit;
    }

    // إدراج الحجز
    $ins = $conn->prepare("
        INSERT INTO grp_session_participants (group_session_id, mentee_id)
        VALUES (?, ?)
    ");
    $ins->bind_param("ii", $session_id, $mentee_id);
    if (!$ins->execute()) {
        echo json_encode(["success"=>false, "message"=>"Booking failed."]);
        $ins->close();

        exit;
    }
    $ins->close();

    // بعد أول حجز: حوّل حالة الجلسة من available إلى upcoming
$upd = $conn->prepare("
    UPDATE grp_sessions
    SET status = 'upcoming'
    WHERE group_session_id = ? AND status = 'available'
");
$upd->bind_param("i", $session_id);
$upd->execute();
$upd->close();




    // عداد جديد بعد الإدراج
    $q = $conn->prepare("
        SELECT COUNT(*) AS c
        FROM grp_session_participants
        WHERE group_session_id=?
    ");
    $q->bind_param("i", $session_id);
    $q->execute();
    $newCount = (int)$q->get_result()->fetch_assoc()['c'];
    $q->close();

    echo json_encode([
        "success"=>true,
        "message"=>"Session booked successfully.",
        "newCount"=>$newCount
    ]);
    exit;
}

/*********************************************************
 * (3) جلب الجلسات للعرض:
 * - تظهر فقط الجلسات حالتها available أو upcoming
 * - موعدها لم يحن بعد
 * - نعرض اسم المنتور + عدد الحجوزات + هل هذا المنتي حجز
 *********************************************************/
$sql = "
    SELECT
        gs.group_session_id,
        gs.title,
        gs.session_date,
        gs.session_time,
        COALESCE(gs.photo,'')    AS photo,
        COALESCE(gs.capacity,10) AS capacity,
        u.first_name,
        u.last_name,
        /* عدد الحاجزين */
        (SELECT COUNT(*) FROM grp_session_participants p
         WHERE p.group_session_id = gs.group_session_id) AS booked_count,
        /* هل هذا المنتي حاجزها؟ */
        EXISTS (
            SELECT 1 FROM grp_session_participants p2
            WHERE p2.group_session_id = gs.group_session_id
              AND p2.mentee_id = ?
        ) AS is_booked
  FROM grp_sessions gs
JOIN mentors m ON m.user_id = gs.mentor_id
JOIN users u   ON u.user_id = m.user_id

    WHERE gs.status IN ('available','upcoming')
      AND CONCAT(gs.session_date,' ',gs.session_time) >= NOW()
    ORDER BY gs.session_date, gs.session_time
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mentee_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Group Sessions</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
:root {
    --primary-bg: #211742;
    --form-bg: #ffffff;
    --input-bg: #e5e5e5;
    --input-border: #b3b3b3;
    --button-color: #4b398e;
    --text-color: #a576ff;
    --highlight-text: #a576ff;
}

/* General Styles */
body{background:#f8f8f8;
    color: var(--button-color);
    margin: 0;
    padding: 0;
}

/* ✅ إعادة تعيين القيم الافتراضية */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

/* ✅ تصميم الهيدر */
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

/* Layout */
.container {
    display: flex;
    width: 90%;
    margin: auto;
    padding: 20px;
}

/* Footer */
footer {
    text-align: center;
    padding: 10px;
    background: #ffffff;
    color: #211742;
    margin-top: 400px;
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


/* ====== Cards Grid for Group Sessions ====== */
.container {
  display: grid;
  grid-template-columns: repeat( auto-fill, minmax(260px, 1fr) );
  gap: 18px;
  width: 90%;
  margin: 24px auto 0;
  padding: 0;
}

.card{
  background:#fff;
  border:1px solid #eee;
  border-radius:14px;
  box-shadow:0 6px 18px rgba(0,0,0,.06);
  overflow:hidden;
  position:relative;
  transition:transform .15s ease, box-shadow .15s ease;
  display:flex;
  flex-direction:column;
}

.card:hover{
  transform: translateY(-2px);
  box-shadow:0 10px 22px rgba(0,0,0,.08);
}

.badge{
  position:absolute;
  top:10px; left:10px;
  background:#4b398e;
  color:#fff;
  font-size:12px;
  padding:6px 10px;
  border-radius:999px;
  box-shadow:0 2px 8px rgba(75,57,142,.25);
}

.img-wrap{
  width:100%;
  aspect-ratio: 16 / 9;
  background:#f6f4ff;
  overflow:hidden;
}

.img-wrap img{
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
}

.h3{
  font-size:18px;
  font-weight:700;
  color:#211742;
  padding:12px 14px 4px;
  line-height:1.3;
  min-height:48px; /* يثبت العنوان بسطرين تقريبًا */
}

.sub{
  font-size:14px;
  color:#6f6b80;
  padding:0 14px 8px;
}

.counter{
  font-size:13px;
  color:#4b398e;
  background:#f7f5ff;
  margin:10px 14px 14px;
  padding:8px 10px;
  border-radius:10px;
}

.card .btn{
  margin: 10px 14px 0;
  border:none;
  border-radius:10px;
  padding:10px 14px;
  font-weight:700;
  cursor:pointer;
  background:#4b398e;
  color:#fff;
  transition:opacity .15s ease, transform .08s ease;
}

.card .btn:hover{ opacity:.92; }
.card .btn:active{ transform: scale(.98); }
.card .btn:disabled{
  background:#c7c2e0;
  cursor:not-allowed;
  opacity:1;
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


<div class="container">
<?php if ($result && $result->num_rows > 0): ?>
  <?php while ($row = $result->fetch_assoc()):
        $isBooked  = (int)$row['is_booked'] === 1;
        $bookedCnt = (int)$row['booked_count'];
        $capacity  = (int)$row['capacity'];
        $isFull    = $bookedCnt >= $capacity;
//        $photo     = trim($row['photo']) !== '' ? $row['photo'] : 'images/default-session.jpg';
        $photo = trim($row['photo']) !== '' ? $row['photo'] : 'images/logo.png'; 

        $dateBadge = date('M d', strtotime($row['session_date'])) . ' • ' . substr($row['session_time'],0,5);
  ?>
  <div class="card" data-session-id="<?= (int)$row['group_session_id'] ?>">
    <span class="badge"><?= htmlspecialchars($dateBadge) ?></span>
   <div class="img-wrap">
  <img src="<?= htmlspecialchars($photo, ENT_QUOTES) ?>" alt="Session Photo">
</div>

    <div class="h3"><?= htmlspecialchars($row['title']) ?></div>
    <div class="sub"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>

    <button class="btn"
            <?= $isBooked ? 'disabled' : '' ?>
            <?= $isFull   ? 'disabled' : '' ?>>
      <?= $isBooked ? 'Already Booked' : ($isFull ? 'Full' : 'Book') ?>
    </button>

    <div class="counter">
      Booked: <span class="booked-count"><?= $bookedCnt ?></span> / <?= $capacity ?>
    </div>
  </div>
  <?php endwhile; ?>
  
  <?php else: ?>
  <div style="grid-column:1/-1; text-align:center; color:#6f6b80; padding:40px 0;">
    No group sessions available right now.
  </div>
<?php endif; ?>

</div>



<script>
/* إرسال الحجز عبر POST وتحديث الكارد مباشرة */
document.querySelectorAll('.card .btn').forEach(btn => {
  btn.addEventListener('click', function(){
    if (this.disabled) return;

    const card = this.closest('.card');
    const sessionId = card.getAttribute('data-session-id');

    if (!confirm('Are you sure you want to book this session?')) return;

    fetch(location.href, {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'session_id=' + encodeURIComponent(sessionId)
    })
    .then(r => r.json())
    .then(data => {
      alert(data.message);
      if (data.success) {
        // عداد
        const cntEl = card.querySelector('.booked-count');
        cntEl.textContent = data.newCount;

        // تعطيل الزر (صار حاجز)
        this.disabled = true;
        this.textContent = 'Already Booked';

        // لو امتلأت السعة بعد الحجز حوّل للنص Full (اختياري)
        const capacity = parseInt(card.querySelector('.counter').textContent.split('/')[1]);
        if (data.newCount >= capacity) this.textContent = 'Full';
      }
    })
    .catch(console.error);
  });
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


<a href="ask_aspira.php" class="chatbot-float">
    💬
</a>
<style>
.chatbot-float {
    position: fixed;
    width: 60px;
    height: 60px;
    background-color: #7a4ff2; /* purple ASPIRA */
    bottom: 25px;
    right: 25px;
    color: white;
    border-radius: 50%;
    text-align: center;
    font-size: 28px;
    font-weight: bold;
    box-shadow: 0px 4px 12px rgba(0,0,0,0.2);
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
    z-index: 9999;
}

.chatbot-float:hover {
    background-color: #6435e9;
    transform: scale(1.1);
}
</style>

<?php if (basename($_SERVER['PHP_SELF']) != 'ask_aspira.php'): ?>
    <a href="ask_aspira.php" class="chatbot-float">💬</a>
<?php endif; ?>

</body>
</html>
<?php
$stmt->close();
$conn->close();
