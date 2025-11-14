<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connection.php';


session_start();

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





if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$sql = "SELECT 
    m.mentor_id, 
    m.profile_picture, 
    m.brief_description,  
    f.field_name, 
    COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'Unknown') AS mentor_name,
    y.experience_level AS experience_years,
    ROUND(AVG(r.rating), 1) AS avg_rating,
    COUNT(r.rating) AS total_reviews
FROM mentors m
LEFT JOIN users u ON m.user_id = u.user_id
LEFT JOIN field f ON m.field_id = f.id
LEFT JOIN years_of_experience y ON m.experience_id = y.id
LEFT JOIN ratings r ON r.mentor_id = m.user_id
WHERE m.status = 'approved'
GROUP BY m.mentor_id, m.profile_picture, m.brief_description, f.field_name, u.first_name, u.last_name, y.experience_level
ORDER BY mentor_name";


$result = $conn->query($sql);



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Center</title>
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


/* Layout */
.container {
    display: flex;
    width: 90%;
    margin: auto;
    padding: 20px;
}

/* Filter Section (Left Side) */
/* Filter Section */
/* Filter Section */
.filter-section {
    width: 25%;
    padding: 20px;
}

.filter-divider {
    margin: 20px 0;
    border-bottom: 2px solid rgba(0, 0, 0, 0.1); 
}


/* Filter Options */
.filter-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* Checkboxes */
.custom-checkbox {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 16px;
    cursor: pointer;
}

.custom-checkbox input {
    display: none;
}

.custom-checkbox .checkmark {
    width: 20px;
    height: 20px;
    border-radius: 5px;
    border: 2px solid var(--button-color);
    display: inline-block;
    margin-right: 10px;
    position: relative;
}

.custom-checkbox input:checked + .checkmark {
    background-color: var(--highlight-text);
}

.custom-checkbox input:checked + .checkmark::after {
    content: '✔';
    position: absolute;
    left: 5px;
    top: -2px;
    color: white;
    font-size: 16px;
}

/* Mentor Count */
.mentor-count {
    color: gray;
    font-weight: normal;
    margin-left: auto;
}

/* Show More & Show Less Text Styling */
.toggle-more {
    color: #444;
    font-size: 14px;
    cursor: pointer;
    margin-top: 10px;
    text-decoration: none;
    display: inline-block;
    font-weight: normal;
}

.toggle-more:hover {
    color: var(--highlight-text);
    text-decoration: none;
}

/* Hide Items Initially */
.hidden {
    display: none;
}

    .custom-checkbox b {
    font-size: 16px; /* Match mentor card text size */
    font-weight: 400; /* Regular weight, not bold */
    color: var(--button-color); /* Ensure it matches the theme */
    
}





/* Mentor Center (Right Side) */
.mentor-center {
    width: 75%;
    padding-left: 20px;
}

.mentor-center h1 {
    color: var(--highlight-text);
    font-size: 24px;
    margin-bottom: 20px;
}

/* Search Input */
#searchInput {
    width: 100%;
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #4b398e;
    background-color: var(--form-bg);
    font-size: 16px;
    margin-bottom: 20px;
}

.mentor-list {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: flex-start; /* يجعل الكروت في المنتصف */
}

/* ✅ تنسيق الكارد كرابط */
.mentor-card {
    position: relative; /* ✅ مهم جدًا */
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    text-decoration: none;
    background: #ffffff;
    padding: 15px;
    width: 240px;
    height: auto;
    border-radius: 12px;
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
    color: inherit;
}


/* ✅ تحسين تأثير الهوفر */
.mentor-card:hover {
    transform: scale(1.05);
    box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.15);
}


.mentor-img-container {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    margin-bottom: 8px;
    
    /* ✅ إضافة التالي لضمان التوسيط */
    display: flex;
    justify-content: center;
    align-items: center;
    margin-left: auto;
    margin-right: auto;
}

.mentor-details h3 {
    font-size: 18px;
    margin-bottom: 3px; /* ✅ تقليل المسافة */
}

.mentor-img-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mentor-details {
    text-align: center;
}

.mentor-field,
.mentor-experience {
    font-size: 14px;
    color: #4b398e;
    margin: 5px 0;
}

.mentor-rating {
    font-size: 18px;
    color: #FFD700; /* لون النجوم */
    margin-top: 5px;
}

.star {
    font-size: 18px;
}

.filled {
    color: #FFD700; /* لون النجوم الممتلئة */
}

.book-btn {
    display: inline-block;
    background: #4b398e;
    color: white;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
    margin-top: 10px;
}

.book-btn:hover {
    background: #a576ff;
    transform: scale(1.05);
}


.rating-top-left {
    position: absolute;
    top: 10px;
    left: 15px;
    font-size: 14px;
    font-weight: bold;
    color: #FFD700; /* لون النجوم */
    background: #fff;
    padding: 4px 8px;
    border-radius: 6px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.card-footer {
    display: flex;
    flex-direction: column;
    gap: 5px;
    font-size: 14px;
    color: var(--button-color);
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


@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

  * {
    margin: 0; padding: 0; box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
  }

  body { background:#f8f8f8; color:#4b398e; }

  header {
    display:flex; justify-content:space-between; align-items:center;
    padding:15px 30px; background:#fff;
    position:sticky; top:0; border-bottom:1px solid #eee;
    z-index:100;
  }
  nav { display:flex; gap:20px; flex-wrap:nowrap; }
  nav a { text-decoration:none; color:black; font-weight:500; padding:5px; }
  nav a.active, nav a:hover { color:#4b398e; border-bottom:2px solid #4b398e; }

  .profile-menu { position:relative; }
  .profile-pic { width:45px; height:45px; border-radius:50%; object-fit:cover; cursor:pointer; }
  .dropdown { display:none; position:absolute; right:0; top:55px; background:#fff; border:1px solid #eee; border-radius:10px; min-width:160px; list-style:none; padding:8px 0; }
  .dropdown li { padding:10px 16px; }
  .dropdown li a { color:#211742; text-decoration:none; display:block; }
  .dropdown li:hover { background:#f7f5ff; }
  .profile-menu:hover .dropdown { display:block; }

  /* ✅ Layout */
  .container {
    display:flex;
    flex-wrap:wrap;
    gap:20px;
    width:90%;
    margin:20px auto;
  }
  .filter-section {
    flex:1 1 250px;
    max-width:280px;
    background:#fff;
    padding:15px;
    border-radius:10px;
    box-shadow:0 2px 6px rgba(0,0,0,0.05);
  }
  .filter-options { display:flex; flex-direction:column; gap:10px; }
  .custom-checkbox { display:flex; align-items:center; justify-content:space-between; gap:6px; }
  .custom-checkbox .checkmark {
    width:20px; height:20px; border:2px solid #4b398e;
    border-radius:5px; display:inline-block; position:relative;
  }
  .custom-checkbox input { display:none; }
  .custom-checkbox input:checked + .checkmark { background:#a576ff; }
  .custom-checkbox input:checked + .checkmark::after {
    content:'✔'; position:absolute; top:-2px; left:4px; color:#fff; font-size:14px;
  }
  .mentor-count { color:gray; font-size:14px; }

  .mentor-center { flex:3 1 600px; min-width:0; }
  #searchInput {
    width:100%; padding:10px; border:1px solid #4b398e;
    border-radius:6px; margin-bottom:20px;
  }

  .mentor-list {
    display:flex; flex-wrap:wrap; gap:20px;
  }
  .mentor-card {
    flex:1 1 240px;
    max-width:280px;
    background:#fff; padding:15px;
    border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1);
    text-align:center; text-decoration:none; color:inherit;
    transition:.3s;
  }
  .mentor-card:hover { transform:scale(1.05); box-shadow:0 6px 15px rgba(0,0,0,0.15); }
  .mentor-img-container {
    width:100px; height:100px; border-radius:50%; overflow:hidden;
    margin:0 auto 10px;
    display:flex; align-items:center; justify-content:center;
  }
  .mentor-img-container img { width:100%; height:100%; object-fit:cover; }
  .mentor-details h3 { font-size:18px; margin-bottom:5px; }
  .mentor-field, .mentor-experience { font-size:14px; color:#4b398e; margin:3px 0; }

  .rating-top-left {
    position:absolute; top:10px; left:15px;
    background:#fff; padding:3px 6px; border-radius:6px;
    font-size:13px; font-weight:bold; color:#FFD700;
    box-shadow:0 2px 6px rgba(0,0,0,0.1);
  }

  /* ✅ Responsive */
  @media (max-width:992px) {
    .container { flex-direction:column; }
    .filter-section, .mentor-center { width:100%; max-width:100%; }
    .mentor-card { flex:1 1 100%; max-width:100%; }
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




    <main class="container">
 <aside class="filter-section">
    <!-- Fields Filter -->
    <h3>Fields</h3>
    <br>
    
   
    <div class="filter-options Fields-container">
        <?php 
        $fieldQuery = "SELECT field.field_name, COUNT(mentors.mentor_id) AS mentor_count 
                       FROM field 
                       LEFT JOIN mentors ON field.id = mentors.field_id 
                       WHERE mentors.status = 'approved'
                       GROUP BY field.field_name
                       ORDER BY mentor_count DESC";
         
        $fields = $conn->query($fieldQuery);

        $count = 0;
        while ($row = $fields->fetch_assoc()) {
            $count++;
            $hiddenClass = ($count > 5) ? "hidden Fields-hidden" : ""; // Show first 5 items, hide rest
            echo "<label class='custom-checkbox $hiddenClass'>";
            echo "<input type='checkbox' class='Fields-checkbox' value='" . $row['field_name'] . "'>";
            echo "<span class='checkmark'></span><b>" . $row['field_name'] . "</b>";
            echo "<span class='mentor-count'>" . $row['mentor_count'] . "</span>";
            echo "</label>";
        }
        ?>
    </div>

    <p class="toggle-more" id="showMoreFields">Show more</p>
    <p class="toggle-more hidden" id="showLessFields">Show less</p>

    <!-- Space between Filters -->
    <div class="filter-divider"></div>

    <!-- Experience Filter -->
    <h3>Years of Experience</h3>
<br>    
    <div class="filter-options experience-container">
        <?php 
        $experienceQuery = "SELECT years_of_experience.experience_level, COUNT(mentors.mentor_id) AS mentor_count 
                            FROM years_of_experience 
                            LEFT JOIN mentors ON years_of_experience.id = mentors.experience_id 
                            WHERE mentors.status = 'approved'
                            GROUP BY years_of_experience.experience_level 
                             ORDER BY mentor_count DESC";

        $experienceResults = $conn->query($experienceQuery);

        $count = 0;
        while ($row = $experienceResults->fetch_assoc()) {
            $count++;
            $hiddenClass = ($count > 5) ? "hidden experience-hidden" : ""; // Show first 5 items, hide rest
            echo "<label class='custom-checkbox $hiddenClass'>";
            echo "<input type='checkbox' class='experience-checkbox' value='" . $row['experience_level'] . "'>";
            echo "<span class='checkmark'></span><b>" . $row['experience_level'] . "</b>";
            echo "<span class='mentor-count'>" . $row['mentor_count'] . "</span>";
            echo "</label>";
        }
        ?>
    </div>

    <p class="toggle-more" id="showMoreExperience">Show more</p>
    <p class="toggle-more hidden" id="showLessExperience">Show less</p>
</aside>


        <section class="mentor-center">
            <div class="filter-search">
                <input type="text" id="searchInput" placeholder="Search mentor...">
            </div>
            <div class="mentor-list">
<?php 
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<a href='BookPrivateSession.php?mentor_id=" . $row['mentor_id'] . "' class='mentor-card' data-category='" . $row['field_name'] . "' data-experience='" . $row['experience_years'] . "'>";

    //  التقييم في الزاوية العلوية اليسرى
    $avg_rating = $row['avg_rating'] ?? 0;
    $total_reviews = $row['total_reviews'] ?? 0;

    echo "<div class='rating-top-left'>";
    if ($total_reviews > 0) {
echo "<strong>$avg_rating ★</strong>";

    } else {
        echo "<span style='font-size: 12px; color: gray;'>No rating</span>";
    }
    echo "</div>";

    // صورة المنتور
    echo "<div class='mentor-img-container'>";
    echo "<img src='" . $row['profile_picture'] . "' alt='Mentor'>";
    echo "</div>";

    //  تفاصيل المنتور
    echo "<div class='mentor-details'>";
    echo "<h3>" . $row['mentor_name'] . "</h3>";
    echo "<p class='mentor-field'>Field: " . $row['field_name'] . "</p>";
    echo "<p class='mentor-experience'>Experience: " . $row['experience_years'] . " years</p>";
    echo "</div>";

echo "</a>";

    }
} else {
    echo "<p>No mentors available.</p>";
}


?>
</div>


        </section>
    </main>

   <script>
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("searchInput").addEventListener("keyup", filterMentors);
    document.querySelectorAll(".custom-checkbox input").forEach(checkbox => {
        checkbox.addEventListener("change", filterMentors);
    });

    function filterMentors() {
        let searchInput = document.getElementById("searchInput").value.toLowerCase();
        let checkedFields = Array.from(document.querySelectorAll(".Fields-checkbox:checked")).map(cb => cb.value);
        let checkedExperience = Array.from(document.querySelectorAll(".experience-checkbox:checked")).map(cb => cb.value);
        let mentorCards = document.querySelectorAll(".mentor-card");

        mentorCards.forEach(card => {
            let name = card.querySelector("h3").textContent.toLowerCase();
            let category = card.getAttribute("data-category");
            let experience = card.getAttribute("data-experience");

            let matchesSearch = name.includes(searchInput);
            let matchesFields = (checkedFields.length === 0 || checkedFields.includes(category));
            let matchesExperience = (checkedExperience.length === 0 || checkedExperience.includes(experience));

            if (matchesSearch && matchesFields && matchesExperience) {
                card.style.display = "block";
            } else {
                card.style.display = "none";
            }
        });
    }

    // Show More/Less Functionality for Fields
    const showMoreFields = document.getElementById("showMoreFields");
    const showLessFields = document.getElementById("showLessFields");
    let hiddenFields = document.querySelectorAll(".Fields-hidden");

    showMoreFields.addEventListener("click", function () {
        hiddenFields.forEach(item => item.classList.remove("hidden"));
        showMoreFields.classList.add("hidden");
        showLessFields.classList.remove("hidden");
    });

    showLessFields.addEventListener("click", function () {
        hiddenFields.forEach(item => item.classList.add("hidden"));
        showMoreFields.classList.remove("hidden");
        showLessFields.classList.add("hidden");
    });

    // Show More/Less Functionality for Experience
    const showMoreExperience = document.getElementById("showMoreExperience");
    const showLessExperience = document.getElementById("showLessExperience");
    let hiddenExperience = document.querySelectorAll(".experience-hidden");

    showMoreExperience.addEventListener("click", function () {
        hiddenExperience.forEach(item => item.classList.remove("hidden"));
        showMoreExperience.classList.add("hidden");
        showLessExperience.classList.remove("hidden");
    });

    showLessExperience.addEventListener("click", function () {
        hiddenExperience.forEach(item => item.classList.add("hidden"));
        showMoreExperience.classList.remove("hidden");
        showLessExperience.classList.add("hidden");
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
$conn->close();
?>