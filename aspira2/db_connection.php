<?php
$servername = "localhost";
$username = "root";
$password = "root"; // تحقق من كلمة مرور MAMP الافتراضية
$dbname = "aspira";
$port = "3306"; // أضف هذا إذا كنت غيرت المنفذ

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>
