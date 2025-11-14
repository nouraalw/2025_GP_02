<?php
session_start();
include 'db_connection.php'; 



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



$query = "SELECT s.id, s.date, s.time, s.room_id, m.user_id AS mentor_user_id, m.profile_picture, u.first_name, u.last_name, f.field_name, r.rating, r.comment
          FROM sessions s
          JOIN mentors m ON s.mentor_id = m.user_id  
          JOIN users u ON m.user_id = u.user_id  
          LEFT JOIN field f ON m.field_id = f.id  
          LEFT JOIN ratings r ON s.id = r.session_id AND r.mentee_id = ?
          WHERE s.mentee_id = ? AND s.status = 'completed'
          ORDER BY s.date DESC, s.time DESC";


$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $mentee_id, $mentee_id);
$stmt->execute();
$result = $stmt->get_result();



?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Past Sessions</title>
       <link rel="icon" type="image/png" href="images/favicon.png">
    
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

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 25px;
            color: gray;
            cursor: pointer;
        }

        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: gold;
        }
        .mentor-photo {
    width: 50px;  /* ✅ نفس الحجم المحدد في HTML */
    height: 50px;
    border-radius: 50%; /* ✅ يجعلها دائرية */
    object-fit: cover; /* ✅ يضمن أن الصورة تتناسب داخل الدائرة بدون تشويه */
    border: 4px; /* ✅ إضافة حد بنفسجي جميل */
}
td textarea {
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    padding: 6px;
    border: 1px solid #ccc;
    border-radius: 6px;
    resize: vertical;
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
    .then(response => response.text())
    .then(() => {
        //  تعطيل النجوم
        document.querySelectorAll(`input[name="rating_${sessionId}"]`).forEach(input => {
            input.disabled = true;
        });

        //  تعطيل التعليق
        if (commentField) commentField.disabled = true;

        //  إظهار "Rated"
        const form = document.getElementById("ratingForm_" + sessionId);
        if (form) {
            form.querySelectorAll("button").forEach(btn => btn.remove());

            const existingRated = form.querySelector(".rated-text");
            if (!existingRated) {
                const ratedText = document.createElement('span');
                ratedText.className = "rated-text";
                ratedText.textContent = "Rated";
                ratedText.style.color = "green";
                ratedText.style.marginLeft = "10px";
                form.appendChild(ratedText);
            }
        }
    })
    .catch(error => {
        console.error("Error submitting rating:", error);
    });
}
</script>

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


    <main>
        <br>
        <h2 style='font-weight: bold; color: #4b398e; text-align: center;'>Past Sessions</h2>

        <table>
            <tr>
    <th>Photo</th>
    <th>Mentor Name</th>
    <th>Field</th>
    <th>Date</th>
    <th>Time</th>
    <th>Comment</th> 
    <th>Rating</th>
</tr>


            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                         <td>
    <?php 
        $profile_picture = trim($row['profile_picture']); 
        
        if (strpos($profile_picture, "uploads/") !== false) {
            $profile_picture = str_replace("uploads/", "", $profile_picture); 
        }
        
        $image_path = file_exists(__DIR__ . "/uploads/" . $profile_picture) 
                      ? "uploads/" . $profile_picture 
                      : "default.jpg"; 

        echo "<img src='" . htmlspecialchars($image_path) . "' class='mentor-photo' alt='Mentor Photo'>";
    ?>
</td>
                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                        <td><?= htmlspecialchars($row['field_name'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['time']) ?></td>
                        
<td>
        <?php if (!$row['rating']): ?>
            <textarea name="comment_<?= $row['id'] ?>" placeholder="Leave a comment..." style="width: 100%; height: 60px;"></textarea>
        <?php else: ?>
            <div style="color: #555; font-style: italic;">
                <?= htmlspecialchars($row['comment']) ?: 'No comment.' ?>
            </div>
        <?php endif; ?>
    </td>
<td>
                        <form id="ratingForm_<?= $row['id'] ?>" onsubmit="event.preventDefault(); submitRating(<?= $row['id'] ?>, <?= $row['mentor_user_id'] ?>);">
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
                <span style="color: green;">Rated</span>
            <?php endif; ?>

    </form>
</td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">No completed sessions available.</td></tr>
            <?php endif; ?>
        </table>
    </main>

    <script>
function toggleMenu() {
  document.getElementById("navMenu").classList.toggle("active");
}
</script>

</body>
</html>