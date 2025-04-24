<?php
session_start();
include 'db_connection.php'; 

$mentee_id = $_SESSION['user_id'];

//  الجلسات المحجوزة من قبل هذا المستخدم
$query = "SELECT s.id, s.date, s.time, s.room_id, m.mentor_id, m.profile_picture, u.first_name, u.last_name, f.field_name
          FROM sessions s
          JOIN mentors m ON s.mentor_id = m.user_id  
          JOIN users u ON m.user_id = u.user_id  
          LEFT JOIN field f ON m.field_id = f.id  
          WHERE s.mentee_id = ? AND s.status = 'booked'
          ORDER BY s.date DESC, s.time DESC";



$stmt = $conn->prepare($query);
if (!$stmt) {
    die(" Error in preparing the query: " . $conn->error);
}

$stmt->bind_param('i', $mentee_id);
$stmt->execute();
$result = $stmt->get_result();


if (!$result) {
    die("Error in executing the query: " . $stmt->error);
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentee Upcoming Session</title>
 
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


.mentor-photo {
    width: 50px;  
    height: 50px;
    border-radius: 50%; 
    object-fit: cover; 
    border: 4px; 
}

        .session-ended {
    background-color: #f0f0f0 !important;
    color: gray;
}

        </style>
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

    
    <main>
        <br>
       <h2 style='font-weight: bold; color: #4b398e; text-align: center;'> Upcoming Sessions</h2>

       <table>
<tr>
    <th>Photo</th>
    <th>Mentor Name</th>
    <th>Field</th>
    <th>Date</th>
    <th>Time</th>
    <th>Duration</th>
    <th>Join</th>
</tr>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
           <?php
date_default_timezone_set('Asia/Riyadh'); //  هذا أول شيء
$current_time = date("H:i:s");
$current_date = date("Y-m-d");

$session_time = date("H:i:s", strtotime($row['time']));
$session_date = $row['date'];
$end_time = date("H:i:s", strtotime($session_time . " +45 minutes"));

$isEnded = ($session_date < $current_date || ($session_date == $current_date && $end_time < $current_time));
?>
<tr class="<?= $isEnded ? 'session-ended' : '' ?>">


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
<td><?= htmlspecialchars($row['field_name'] ?? 'Unknown') ?></td> <!--  عرض المجال -->

                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= htmlspecialchars($row['time']) ?></td>
                <td>  45 min  </td>
              <td>
    <?php
    
    $session_time = date("H:i:s", strtotime($row['time'])); //  تحويل وقت الجلسة إلى نفس التنسيق
    $session_date = $row['date']; //  جلب تاريخ الجلسة
    $room_id = $row['room_id']; //  جلب معرف الغرفة

    // حساب وقت انتهاء الجلسة
$end_time = date("H:i:s", strtotime($session_time . " +45 minutes"));

if ($session_date > $current_date || ($session_date == $current_date && $session_time > $current_time)) {
    // الجلسة لم تبدأ
   echo "<button class='join-btn' onclick='document.getElementById(\"waitModal\").style.display = \"flex\"'>Join</button>";


}  elseif ($isEnded) {
echo "Session Ended"; }
 else {
    // الجلسة جارية
    echo "<a href='WEB_UIKITS.php?roomID=" . htmlspecialchars($room_id) . "' class='join-btn'>Join</a>";
}

    ?>
</td>



            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="5">There are no upcoming Sessions.</td></tr>
    <?php endif; ?>
</table>

    </main>
    <!-- ✅ Custom Modal -->
<div id="waitModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:30px; border-radius:10px; text-align:center; max-width:400px; width:90%;">
        <img src="https://cdn-icons-png.flaticon.com/512/9625/9625452.png" alt="Wait" style="width: 80px; height: 80px; margin-bottom: 10px;">
        <h2 style="color:#4b398e;">Wait</h2>
        <p style="margin-top:10px;">The session hasn’t started yet. Please wait!</p>
        <button onclick="document.getElementById('waitModal').style.display='none'" style="margin-top:20px; background-color:#4b398e; color:#fff; padding:10px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">OK</button>
    </div>
</div>



</body>
</html>