<?php
include 'db_connection.php';
session_start(); 

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
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

        // check the conflict 
        $sql_check = "SELECT time FROM sessions WHERE mentor_id = ? AND date = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("is", $mentor_id, $date);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        $conflict = false;
        while ($row = $result_check->fetch_assoc()) {
            $existing_time = $row['time'];
            
            // حساب الفرق بين الوقت المدخل والجلسات الموجودة
            $new_time = strtotime($time);
            $existing_time = strtotime($existing_time);
            $time_difference = abs($new_time - $existing_time) / 3600; // تحويل الفرق إلى ساعات

            if ($time_difference < 1) { // أقل من ساعة
                $conflict = true;
                break;
            }
        }
        $stmt_check->close();

        if ($conflict) {
            $message = "Can't add session because of exsiting session at the same time! ";
        } else {
            // إدخال الجلسة في قاعدة البيانات
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
            margin-top: 100px;
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

    <div class="content-wrapper">
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
            <div id="aboutus">
                <h3 style="color:#4b398e;">About Us :</h3>
                <p class="aboutp">a mentorship platform designed to connect</p>
                <p class="aboutp">experenced professionals with ambitious individuals</p>
                <p class="aboutp">seeking guidance and growth.Through structured</p> 
                <p class="aboutp">mentorship programs, tailored support,</p>
                <p class="aboutp">and career development resources</p>
            </div>
            
            <div id="contact">
                <h3 style="color:#4b398e;">Contact Info :</h3>
                <p>Email: aspira@gmail.com</p>
                <img src="images/twitter.png" class="icon" alt="X" width="30" height="40">
                <img src="images/instagram.png" class="icon" alt="Instagram" width="40" height="40">
            </div>

            <p id="copy"> &copy; 2025 aspira</p>  
        </div>
    </footer>

 
</body>
</html>
