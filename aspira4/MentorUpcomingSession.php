<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connection.php'; 


$mentor_id = $_SESSION['user_id']; 
$query = "SELECT s.id, s.date, s.time, s.room_id, u.first_name, u.last_name, m.interests
          FROM sessions s
          LEFT JOIN mentees m ON s.mentee_id = m.user_id
          LEFT JOIN users u ON m.user_id = u.user_id
          WHERE s.mentor_id = ? AND s.status = 'booked'
          ORDER BY s.date DESC, s.time DESC";


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
    <link rel="stylesheet" href="styles.css"> <!-- ملف التنسيق -->
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
    box-shadow: none !important; 
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

    </style>
</head>
<body>
         <header>
    <div class="logo">
        <img src="images/logo.png" alt="ASPIRA">
        <span class="logo-text">SPIRA</span>
    </div>
    <nav>
       <a href="addPrivateSession.php" class="<?= basename($_SERVER['PHP_SELF']) == 'addPrivateSession.php' ? 'active' : '' ?>">Add Sessions</a>
        <a href="MentorUpcomingSession.php" class="<?= basename($_SERVER['PHP_SELF']) == 'MentorUpcomingSession.php' ? 'active' : '' ?>">Upcoming Sessions</a>
        <a href="mentor_past_sessions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'mentor_past_sessions.php' ? 'active' : '' ?>">Past Sessions</a>
    </nav>
    <a href="homepage.html" class="logout-btn">Log out</a>
</header>
    <main>
        <br>
        <h2 style='font-weight: bold; color: #4b398e; text-align: center;'>Upcoming Sessions</h2>
        <table>
            <tr>
                <th>Mentee Name</th>
                <th>Bio</th>
                <th>Date</th>
                <th>Time</th>
                <th>Duration</th>
                <th>Join</th>
            </tr>
            <tbody id="sessions-table-body">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                   <?php
    // حساب وقت الانتهاء
    date_default_timezone_set('Asia/Riyadh');
    $current_time = date("H:i:s");
    $current_date = date("Y-m-d");

    $session_time = date("H:i:s", strtotime($row['time']));
    $session_date = $row['date'];
    $room_id = $row['room_id'];

    $session_end_timestamp = strtotime($session_time) + 45*60;
    $isEnded = ($session_date < $current_date) || ($session_date == $current_date && $session_end_timestamp < strtotime($current_time));
?>
<tr class="<?= $isEnded ? 'session-ended' : '' ?>">

                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                        <td><?= htmlspecialchars($row['interests'] ?? 'No bio available') ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['time']) ?></td>
                         <td>  45 min  </td>
                        <td>
    <?php
    date_default_timezone_set('Asia/Riyadh'); //   ضبط المنطقة الزمنية إلى السعودية 
    $current_time = date("H:i:s"); //  اجيب الوقت الحالي بتنسيق HH:MM:SS
    $current_date = date("Y-m-d"); //   التاريخ الحالي

    $session_time = date("H:i:s", strtotime($row['time'])); //  تحويل وقت الجلسة إلى نفس التنسيق
    $session_date = $row['date']; //   تاريخ الجلسة
    $room_id = $row['room_id']; 

   if ($session_date > $current_date || ($session_date == $current_date && $session_time > $current_time)) {
    echo "<button class='join-btn' onclick='showJoinModal(\"The session hasn’t started yet. Please wait!\", \"wait\")'>Join</button>";
} elseif ($isEnded) {
    echo "Session Ended";


} else {
    echo "<a href='WEB_UIKITS.php?roomID=" . htmlspecialchars($room_id) . "' class='join-btn'>Join</a>";
}


    ?>
</td>



                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">There are no upcoming Sessions.</td></tr>
            <?php endif; ?>
                 </tbody>
        </table>
    </main>
    
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



</body>
</html>
