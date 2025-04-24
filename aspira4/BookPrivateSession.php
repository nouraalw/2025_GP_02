<?php
include 'db_connection.php';
session_start(); 

error_reporting(E_ALL);
ini_set('display_errors', 1);


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mentee_id = $_SESSION['user_id']; 

// التأكد من وجود معرف المنتور في الرابط (GET)
if (!isset($_GET['mentor_id'])) {
    die("❌ خطأ: معرف المنتور غير موجود!");
}

$mentor_id = $_GET['mentor_id'];

//  جلب بيانات المنتور من users مع الحقل والـ brief_description
//  جلب بيانات المنتور من users مع الحقل والـ brief_description والصورة
$sql = "SELECT 
            users.first_name, 
            users.last_name, 
            COALESCE(mentors.profile_picture, '') AS profile_picture, 
            COALESCE(field.field_name, 'Unknown') AS field, 
            COALESCE(mentors.brief_description, 'No description') AS brief_description
        FROM mentors 
        JOIN users ON mentors.user_id = users.user_id 
        LEFT JOIN field ON mentors.field_id = field.id 
        WHERE mentors.mentor_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$result = $stmt->get_result();
$mentor = $result->fetch_assoc();
$stmt->close();


//  جلب الجلسات المتاحة لهذا المنتور
$sql = "SELECT user_id FROM mentors WHERE mentor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$result = $stmt->get_result();
$mentor_data = $result->fetch_assoc();
$actual_mentor_id = $mentor_data['user_id']; 
$stmt->close();

// اجيب الجلسات بناءً على user_id
$sql = "SELECT id, date, time 
        FROM sessions 
        WHERE mentor_id = ? 
          AND status = 'available' 
          AND CONCAT(date, ' ', time) >= NOW()
        ORDER BY date, time";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $actual_mentor_id);
$stmt->execute();
$result = $stmt->get_result();
$availableSessions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

//  اجيب تعليقات المينتي لهذا المنتور
$sql = "SELECT r.comment, r.rating, u.first_name, u.last_name
        FROM ratings r
        JOIN users u ON r.mentee_id = u.user_id
        WHERE r.mentor_id = ? AND r.comment IS NOT NULL AND r.comment <> ''
        ORDER BY r.id DESC";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $actual_mentor_id); 
$stmt->execute();
$result = $stmt->get_result();
$comments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();


$conn->close();

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Private Session</title>
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
  body { 
            font-family: 'Poppins', sans-serif; 
            text-align: center; 
            background-color: #f8f8f8; 
            padding: 0; 
            margin: 0;
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
    color:#a576ff;
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

.mentor-session-container {
    display: flex;
    justify-content: center; /* ✅ جعل العناصر في المنتصف أفقيًا */
    align-items: flex-start; /* ✅ ضمان عدم تمدد العناصر */
    gap: 30px; /* ✅ وضع مسافة متناسقة بين الحاويتين */
    margin-top: 30px;
    width: 80%; /* ✅ تحديد عرض مناسب ليتمركز في المنتصف */
    margin-left: auto;
    margin-right: auto;
}
    /* Mentor Profile */
      /* ✅ ضبط الحاوية لتكون في المنتصف */
.profile-container,
.time-slots-section {
   
    width: auto; /* ✅ يسمح بالتمدد */
    max-width: 650px; /* ✅ يحدد أقصى عرض */
    min-width: 350px; /* ✅ لا يسمح بأن يكون أصغر من هذا */
    padding: 20px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;

}

/* ✅ جعل الصورة دائرية في الأعلى */
.mentor-photo-container {
    margin-bottom: 10px;
}

.mentor-photo {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px ;
}

/* ✅ تحسين تنسيق النصوص */
.mentor-name {
    font-size: 20px;
    font-weight: bold;
    color: #2D2D69;
    margin-bottom: 5px;
}

.mentor-field {
    font-size: 16px;
    color: #4b398e;
    font-weight: 500;
    margin-bottom: 5px;
}

.mentor-description {
    font-size: 14px;
    color: #555;
    margin-top: 10px;
}

       

        .time-slots-title {
            font-size: 24px;
            color: #4b398e;
            margin-bottom: 20px;
        }

        .time-slots-container {
    display: flex;
    flex-wrap: wrap; /* ✅ يسمح بلف العناصر عند الحاجة */
    gap: 15px; /* ✅ مسافة بين الأوقات */
    justify-content: center; /* ✅ جعل العناصر في المنتصف */
    max-width: 600px; /* ✅ يحدد الحد الأقصى للعرض */
}

/* ✅ الحفاظ على شكل الـ time-slot كما هو، مع تعديل العرض */
.time-slot {
    background-color: #ffffff;
    border-radius: 8px;
    width: 160px; /* ✅ تصغير العرض قليلاً */
    height: 100px; /* ✅ تصغير الارتفاع قليلاً */
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s;
    color: #4b398e;
    box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.3);
    font-size: 13px; /* ✅ تصغير حجم الخط قليلاً */
}


        .time-slot:hover {
            transform: scale(1.05);
        }

        .time-slot.selected {
            background-color: #4b398e;
            color: white;
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 10px;
            background: #ffffff;
            color: #211742;
            margin-top: 100px;
        }
        
        .book-btn{
            
            background: #211742;
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
        }
        
        .book-btn:hover{
             background: #a576ff;
    transform: scale(1.05);
        }
        
        .mysession {
    
   background: #211742;
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
}

.mysession:hover{
     background: #a576ff;
    transform: scale(1.05);
}

.comment-box {
    width: 80%;
    margin: 40px auto;
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
}

.comment-title {
    font-size: 22px;
    font-weight: bold;
    color: #4b398e;
    margin-bottom: 25px;
    text-align: center;
}

.single-comment {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    margin-bottom: 25px;
    border-bottom: 1px solid #eee;
    padding-bottom: 15px;
}

.comment-img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    margin-top: 4px;
}

.comment-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.comment-name {
    font-weight: bold;
    color: #2D2D69;
    font-size: 16px;
}

.comment-stars {
    color: #ffc107;
    font-size: 16px;
    font-weight: bold;
}

.comment-text {
    margin-top: 8px;
    color: #555;
    font-size: 14px;
    text-align: left;        
    padding-left: 5px;  
}


    </style>
    
        <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.addEventListener('click', function() {
                document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                this.classList.add('selected');
                window.selectedSlotId = this.getAttribute('data-id');
                document.getElementById('book-button').style.display = 'block';
            });
        });
    });

    function bookSession() {
        if (!window.selectedSlotId) return;
        
        fetch('book_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'session_id=' + window.selectedSlotId
        }).then(response => response.text()).then(data => {
           showModal("Your session has been successfully booked!");

        });
    }
    
    function showModal(message) {
    document.getElementById("modalMessage").textContent = message;
    document.getElementById("successModal").style.display = "flex";
}

function closeModal() {
   window.location.href = "menteeUpcomingSession.php";

}

    </script>
 </head>
<body>
    <header>
    <div class="logo">
        <img src="images/logo.png" alt="ASPIRA">
        <span class="logo-text">SPIRA</span>
    </div>
    <nav>
        <a href="MentorCenter.php" class="<?= basename($_SERVER['PHP_SELF']) == 'MentorCenter.php' ? 'active' : '' ?>">Mentor Center</a>
        <a href="menteeUpcomingSession.php" class="<?= basename($_SERVER['PHP_SELF']) == 'menteeUpcomingSession.php' ? 'active' : '' ?>">Upcoming Sessions</a>
        <a href="past_sessions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'past_sessions.php' ? 'active' : '' ?>">Past Sessions</a>
    </nav>
    <a href="homepage.html" class="logout-btn">Log out</a>
</header>
    
    

    <div class="mentor-session-container">
<!-- عرض بيانات المنتور -->
<div class="profile-container">
    <!--  صورة المنتور  فوق -->
    <div class="mentor-photo-container">
        <?php 
            $profile_picture = !empty($mentor['profile_picture']) ? $mentor['profile_picture'] : "default.jpg";
            $image_path = "uploads/" . basename($profile_picture); 
        ?>
        <img src="<?= htmlspecialchars($image_path) ?>" class="mentor-photo" alt="Mentor Photo">
    </div>

    <!--  المعلومات تحت بعضها -->
    <h2 class="mentor-name"><?= htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']) ?></h2>
    <span class="mentor-field"><?= htmlspecialchars($mentor['field']) ?></span>
    <p class="mentor-description"><?= htmlspecialchars($mentor['brief_description']) ?></p>
</div>



<!-- عرض الجلسات المتاحة -->
<div class="time-slots-section">
        <h3 class="time-slots-title">Available Time Slots</h3>
        <div class="time-slots-container">
            <?php if (empty($availableSessions)): ?>
                <p style="text-align: center; color: #4b398e;">No available sessions at the moment.</p>
            <?php else: ?>
                <?php foreach ($availableSessions as $session): ?>
                    <div class="time-slot" data-id="<?php echo $session['id']; ?>">
                        <div><?php echo $session['date']; ?></div>
                        <div><?php echo $session['time']; ?></div>
                        <div class="session-duration">45 min</div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div style="text-align: center; margin-top: 30px; display: none;" id="book-button">
            <a href="#" class="book-btn" onclick="bookSession()">Book Session</a>
        </div>
    </div>
</div>
    
    <!-- ✅ Custom Modal -->
<!-- ✅ Custom Modal -->
<div id="successModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:30px; border-radius:10px; text-align:center; max-width:400px; width:90%;">
        <img src="https://cdn-icons-png.flaticon.com/512/5610/5610944.png" alt="Success" style="width: 80px; height: 80px; margin-bottom: 10px;">
        <h2 style="color:#4b398e;">Thank You!</h2>
        <p style="margin-top:10px;" id="modalMessage">Your session has been successfully booked.</p>
        <button onclick="closeModal()" style="margin-top:20px; background-color:#4b398e; color:#fff; padding:10px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">OK</button>
    </div>
</div>

<?php if (!empty($comments)): ?>
    <div class="comment-box">
        <div class="comment-title">What mentees say about this mentor</div>
        <?php foreach ($comments as $comment): ?>
           <div class="single-comment">
    <!-- صورة المستخدم -->
    <img src="https://i.pinimg.com/1200x/28/16/5a/28165aaca2ee560b4a6b760765efe976.jpg" class="comment-img" alt="User">
    
    <!-- محتوى التعليق -->
    <div class="comment-content">
        <div class="comment-header">
            <span class="comment-name">
                <?= htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']) ?>
            </span>
            <span class="comment-stars">
                <?= str_repeat('★', $comment['rating']) . str_repeat('☆', 5 - $comment['rating']) ?>
            </span>
        </div>
        <div class="comment-text"><?= htmlspecialchars($comment['comment']) ?></div>
    </div>
</div>

        <?php endforeach; ?>
    </div>
<?php endif; ?>


</body>
</html>