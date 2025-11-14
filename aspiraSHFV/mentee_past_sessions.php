<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connection.php';

//$mentee_id = $_SESSION['user_id'];


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



// ====== قراءة التبويب الفرعي (افتراضي = private) ======

    $query = "
      SELECT
        s.id,
        s.date  AS date,
        s.time  AS time,
        s.room_id,
        s.status,
         s.summary, s.summary_pdf,  -- ✅ هنا
        m.user_id       AS mentor_user_id,
        m.profile_picture,
        u.first_name, u.last_name,
        f.field_name,
        r.rating, r.comment
      FROM sessions s
      JOIN mentors m ON s.mentor_id = m.user_id      -- sessions.mentor_id stores the mentor's user_id
      JOIN users   u ON m.user_id   = u.user_id
      LEFT JOIN field   f ON m.field_id = f.id
      LEFT JOIN ratings r ON r.session_id = s.id AND r.mentee_id = ?
      WHERE s.mentee_id = ?
        AND s.status = 'completed'
      ORDER BY s.date DESC, s.time DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $mentee_id, $mentee_id);


// --- run the query and fetch results ---
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$result = $stmt->get_result();
if ($result === false) {
    die("get_result() failed (is mysqlnd enabled?): " . $stmt->error);
}



?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentee Past Sessions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="icon" type="image/png" href="images/favicon.png">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
        :root { --button-color:#4b398e; --highlight-text:#a576ff; }
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
        .rating-btn { color:#f39c12; cursor:pointer; font-size:20px; }
        .rating-btn.disabled { cursor:not-allowed; opacity:0.5; }
        .comment-box { width:100%; padding:8px; border-radius:6px; border:1px solid #ccc; margin-top:5px; }
        .submit-btn { background:#4b398e; color:#fff; border:none; padding:8px 15px; border-radius:6px; cursor:pointer; margin-top:8px; }
        .submit-btn:hover { background:#a576ff; }
        
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

.star-rating {
  display: inline-flex;
  flex-direction: row-reverse; /* fixes the order */
  justify-content: center;
}
.star-rating input {
  display: none;
}
.star-rating label {
  font-size: 25px;
  color: #ccc;
  cursor: pointer;
}
.star-rating input:checked ~ label {
  color: #f5b301 !important; /* gold */
}
.star-rating label:hover,
.star-rating label:hover ~ label {
  color: #f5b301;
}


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


<main>

    <br>
    <h2 style='font-weight:bold; color:#4b398e; text-align:center;'>Past Sessions</h2>
     
  
<!-- التبويبات الفرعية (Private ثم Group) للـ Past -->
<div class="sub-tabs">
  <a href="mentee_past_sessions.php" 
     class="sub-tab <?= basename($_SERVER['PHP_SELF'])=='mentee_past_sessions.php'?'active':'' ?>">
    Private
  </a>
  <a href="mentee_GroupPast_session.php" 
     class="sub-tab <?= basename($_SERVER['PHP_SELF'])=='mentee_GroupPast_session.php'?'active':'' ?>">
    Group
  </a>
</div>




    <table>
      <tr>
  <th>Photo</th>
  <th>Mentor Name</th>
  <th>Field</th>
  <th>Date</th>
  <th>Time</th>
    <th>Comment</th>
    <th>Rating</th>
<th>Summary</th>

</tr>



        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
  <td>
    <?php
      $profile_picture = trim($row['profile_picture'] ?? '');
      if (strpos($profile_picture, "uploads/") !== false) {
          $profile_picture = str_replace("uploads/", "", $profile_picture);
      }
      $image_path = file_exists(__DIR__."/uploads/".$profile_picture) && $profile_picture!=""
                    ? "uploads/".$profile_picture
                    : "default.jpg";
    ?>
    <img src="<?= htmlspecialchars($image_path) ?>" class="mentor-photo" alt="Mentor Photo" style="width:50px;height:50px;border-radius:50%;object-fit:cover;">
  </td>






 <td><?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?></td>
<td><?= htmlspecialchars($row['field_name'] ?? 'Unknown') ?></td>



<td><?= htmlspecialchars($row['date']) ?></td>
<td><?= htmlspecialchars($row['time']) ?></td>


 <!-- Comment -->
<td>
    <?php if (!$row['rating']): ?>
        <textarea name="comment_<?= $row['id'] ?>" placeholder="Leave a comment..." style="width: 100%; height: 60px;"></textarea>
    <?php else: ?>
        <div style="color: #555; font-style: italic;">
            <?= htmlspecialchars($row['comment']) ?: 'No comment.' ?>
        </div>
    <?php endif; ?>
</td>

<!-- Rating -->
<td>
    <form id="ratingForm_<?= $row['id'] ?>" onsubmit="event.preventDefault();">
        <div class="star-rating" data-session="<?= $row['id'] ?>">
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <input 
                    type="radio" 
                    id="star<?= $i ?>_<?= $row['id'] ?>" 
                    name="rating_<?= $row['id'] ?>" 
                    value="<?= $i ?>" 
                    <?= ($row['rating'] == $i) ? "checked" : "" ?>
                    <?= $row['rating'] ? "disabled" : "" ?> 
                    onchange="autoSubmitRating(<?= $row['id'] ?>, <?= $row['mentor_user_id'] ?>)" />
                <label for="star<?= $i ?>_<?= $row['id'] ?>">★</label>
            <?php endfor; ?>
        </div>
        <?php if ($row['rating']): ?>
            <div style="color: green; font-size: 14px; margin-top: 5px;">Rated</div>
        <?php endif; ?>
    </form>
</td>

<td>
  <?php if (!empty($row['summary_pdf'])): ?>
    <a href="<?= htmlspecialchars($row['summary_pdf']) ?>" 
       style="display:inline-block; padding:8px 15px; background:#4b398e; color:#fff; border-radius:6px; text-decoration:none; font-size:14px; font-weight:500;" 
       target="_blank">
       📄 Download Summary
    </a>
  <?php else: ?>
    <span style="color:#aaa;">Not provided yet</span>
  <?php endif; ?>
</td>


  
</tr>


            <?php endwhile; ?>
        <?php else: ?>
           <!-- <tr><td colspan="7">No past sessions found.</td></tr> -->
           

        <?php endif; ?>
    </table>
</main>


<script>
function autoSubmitRating(sessionId, mentorId) {
  const selected = document.querySelector(`input[name="rating_${sessionId}"]:checked`);
  const commentField = document.querySelector(`textarea[name="comment_${sessionId}"]`);
  if (!selected) return;

  const rating = selected.value;
  const comment = commentField ? encodeURIComponent(commentField.value.trim()) : "";

  fetch('submit_rating.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `session_id=${sessionId}&mentor_id=${mentorId}&rating=${rating}&comment=${comment}`
  })
  .then(r => r.text())
  .then(() => {
    // عطّل النجوم
    document.querySelectorAll(`input[name="rating_${sessionId}"]`).forEach(i => i.disabled = true);
    // عطّل التعليق
    if (commentField) commentField.disabled = true;
    // أضف شارة Rated
    const form = document.getElementById("ratingForm_" + sessionId);
    if (form && !form.querySelector(".rated-text")) {
      const x = document.createElement('span');
      x.className = "rated-text";
      x.textContent = "Rated";
      x.style.color = "green";
      x.style.marginLeft = "10px";
      form.appendChild(x);
    }
  })
  .catch(err => console.error("Error submitting rating:", err));
}



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