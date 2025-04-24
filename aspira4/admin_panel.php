<?php
session_start();
include 'db_connection.php';


// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Fetch pending mentor requests
$query = "SELECT mentors.mentor_id, 
                 mentors.profile_picture, 
                 users.first_name, 
                 users.last_name, 
                 users.email, 
                 mentors.cv_file, 
                 field.field_name, 
                 years_of_experience.experience_level, 
                 mentors.brief_description , mentors.certificate_file
          FROM mentors 
          JOIN users ON mentors.user_id = users.user_id
          JOIN field ON mentors.field_id = field.id
          JOIN years_of_experience ON mentors.experience_id = years_of_experience.id 
          WHERE mentors.status = 'pending'";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Mentor Requests</title>
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
            --text-color: #ffffff;
            --secondary-bg: #ffffff;
            --button-color: #4CAF50;
            --reject-color: #d9534f;
            --hover-dark: #333333;
            --card-bg: #ffffff;
        }
       

        body {
            font-family: Arial, sans-serif;
            background-color: var(--text-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
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

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #a576ff;
            display: flex;
            align-items: center;
        }

        .logo img {
            width: 24px;
            height: auto;
            margin-right: 5px;
        }

        .logo-text {
            color: #a576ff;
        }

        h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
           
        }

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

        .container {
            width: 90%;
            margin: auto;
            background: var(--secondary-bg);
            padding: 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
            border-radius: 10px;
        }

        h2 {
            color:#211742;
            text-align: center;
            font-weight: bold;
        }

        .mentor-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .mentor-card {
            background: var(--card-bg);
           
            width: 300px;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 8px rgba(75, 57, 142, 0.5);
            text-align: center;
            transition: transform 0.3s;
        }
        .mentor-card h3, .mentor-card p {
    color: #211742; 
}

        .mentor-card:hover {
            transform: scale(1.05);
        }

        .mentor-card img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ddd;
            margin-bottom: 10px;
        }

    

        .action-buttons {
    display: flex;
    justify-content: center; /* Centers buttons */
    gap: 15px; /* Space between buttons */
    margin-top: 10px;
}
.action-buttons button {
    flex: 1; /* Makes both buttons equal width */
    text-align: center;
    padding: 10px 15px;
    text-decoration: none;
    color: white;
    border-radius: 5px;
    font-weight: bold;
    border: none;
    cursor: pointer;
    font-size: 14px;
    min-width: 120px; /* Ensures equal width */
}

.action-buttons a {
    flex: 1; /* Makes both buttons equal width */
    text-align: center;
    padding: 10px 15px;
    text-decoration: none;
    color: white;
    border-radius: 5px;
    font-weight: bold;
}

        .approve {
            background: var(--button-color);
        }

        .reject {
            background: var(--reject-color);
        }

        .approve:hover {
            background: #45a049;
        }

        .reject:hover {
            background: #c9302c;
        }

        /* Pop-up Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
    background-color: white;
    color: black;
    margin: 15% auto;
    padding: 20px;
    border-radius: 10px;
    width: 40%;
    text-align: center; /* Center text */
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.modal-buttons {
    display: flex;
    justify-content: center; /* Centers buttons */
    align-items: center;
    gap: 30px; /* Increased space between "Yes" and "No" buttons */
    margin-top: 20px;
}


.confirm-yes {
    background: #28a745;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.confirm-no {
    background: #d9534f;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.confirm-buttons {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 30px; /* Increase the gap for better spacing */
    margin-top: 20px;
}

/* Ensure buttons have equal width */
.confirm-buttons button {
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    min-width: 120px; /* Ensures both buttons are equal in width */
}


/* Yes button (green) */
.confirm-buttons .yes-btn {
    background-color: #28a745;
    color: white;
}

.confirm-buttons .yes-btn:hover {
    background-color: #218838;
}

/* No button (red) */
.confirm-buttons .no-btn {
    background-color: #d9534f;
    color: white;
}

.confirm-buttons .no-btn:hover {
    background-color: #c9302c;
}

       .close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
}
 
 .button-container {
    display: flex;
    flex-direction: column; /* Stacks elements vertically */
    align-items: center; /* Centers elements horizontally */
    justify-content: center; /* Centers elements vertically */
    width: 100%; /* Ensures full-width alignment */
}

.button-group {
    display: flex;
    gap: 10px; /* Adds space between Description and CV buttons */
    justify-content: center; /* Centers buttons horizontally */
    margin-bottom: 10px; /* Adds space between button group and Certificate */
}

.Certificate-btn {
    display: block;
    text-align: center;
}

.description-btn, .cv-btn,.Certificate-btn {
    background-color: #ffffff; /* White background */
    color: #211742; /* Dark text color */
    text-decoration: none;
    display: inline-block;
    text-align: center;
    padding: 8px 12px;
    border-radius: 5px;
    font-weight: 500; /* Medium weight, not too bold */
    border: 1px solid #211742; /* Add a border for visibility */
    cursor: pointer;
    font-size: 14px;
    min-width: 100px;
    transition: background 0.3s ease-in-out, color 0.3s ease-in-out;
}

.description-btn:hover, .cv-btn:hover,.Certificate-btn:hover{
    background: #211742; /* Dark background on hover */
    color: #ffffff; /* White text on hover */
}

    </style>
</head>
<body>

    <header>
        <div class="logo">
            <img src="images/Logo.png" alt="ASPIRA">
            <span class="logo-text">SPIRA</span>
        </div>
         <h2>Pending Mentor Requests</h2>
        <a href="homepage.html" class="logout-btn">Logout</a>
    </header>


<div class="container">
   
    <br>
    <div class="mentor-list">
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <div class="mentor-card">
                <a href="uploads/<?php echo basename($row['profile_picture']); ?>" target="_blank">
                    <img src="uploads/<?php echo basename($row['profile_picture']); ?>" alt="Mentor Profile">
                </a>
                <h3><?php echo $row['first_name'] . " " . $row['last_name']; ?></h3>
                <br>
                <p><strong>Email:</strong> <?php echo $row['email']; ?></p>
                <br>
                <p><strong>Field:</strong> <?php echo $row['field_name']; ?></p>
                <br>
                <p><strong>Experience:</strong> <?php echo $row['experience_level']; ?></p>
                <br>
               <div class="button-container">
    <div class="button-group">
        <button class="description-btn" onclick="openModal('<?php echo addslashes($row['brief_description']); ?>')">description</button>
        <a href="<?php echo $row['cv_file']; ?>" target="_blank" class="cv-btn">View CV</a>
    </div>
   <!-- Display Multiple Certificates -->
<!-- Display Multiple Certificates -->

<div class="certificate-list">
    <p><strong>Certificates:</strong></p>
    <?php
    if (!empty($row['certificate_file'])) {
        $certificates = explode(",", $row['certificate_file']); // تقسيم النص إلى مصفوفة باستخدام الفاصلة
        foreach ($certificates as $certificate) {
            echo '<a href="' . trim($certificate) . '" target="_blank" class="Certificate-btn">View Certificate</a><br><br>'; // إضافة مسافة بين الأزرار
        }
    } else {
        echo "<p>No certificates uploaded.</p>";
    }
    ?>
</div>



</div>


                <br>
                <div class="action-buttons">
    <button class="approve" onclick="confirmAction('approve', '<?php echo $row['mentor_id']; ?>')">Approve</button>
    <button class="reject" onclick="confirmAction('reject', '<?php echo $row['mentor_id']; ?>')">Reject</button>
</div>


            </div>
        <?php } ?>
    </div>
</div>

<!-- Modal -->
<div id="descriptionModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <p id="fullDescription"></p>
    </div>
</div>

<script>
    function openModal(description) {
        document.getElementById("fullDescription").innerText = description;
        document.getElementById("descriptionModal").style.display = "block";
    }

    function closeModal() {
        document.getElementById("descriptionModal").style.display = "none";
    }
    // Function to open the confirmation modal
function confirmAction(action, mentorId) {
    let message = action === "approve" 
        ? "Are you sure you want to approve this mentor?" 
        : "Are you sure you want to reject this mentor?";

    document.getElementById("confirmMessage").innerText = message;
    document.getElementById("confirmYes").setAttribute("onclick", `processAction('${action}', '${mentorId}')`);
    document.getElementById("confirmModal").style.display = "block";
}

// Function to process the action after confirmation
function processAction(action, mentorId) {
    let url = action === "approve" 
        ? `approve_mentor.php?id=${mentorId}` 
        : `reject_mentor.php?id=${mentorId}`;

    window.location.href = url;
}

// Function to close the modal
function closeConfirmModal() {
    document.getElementById("confirmModal").style.display = "none";
}

</script>
<!-- Confirmation Modal -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeConfirmModal()">&times;</span>
        <p id="confirmMessage"></p>
        <div class="modal-buttons">
            <button id="confirmYes" class="confirm-yes">Yes</button>
            <button onclick="closeConfirmModal()" class="confirm-no">No</button>
        </div>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>