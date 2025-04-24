<?php
$host = "localhost";
$dbname = "aspira";
$username = "root";
$password = "root";

// Connect to MySQL
$conn = new mysqli($host, $username, $password, $dbname);

$sql = "SELECT mentors.mentor_id, users.first_name, users.last_name, users.email, users.phone_number, mentors.cv_file, mentors.status 
        FROM mentors 
        JOIN users ON mentors.user_id = users.user_id 
        WHERE mentors.status = 'pending'";

$result = $conn->query($sql);
$mentors = [];
while ($row = $result->fetch_assoc()) {
    $mentors[] = $row;
}

echo json_encode($mentors);
$conn->close();
?>
