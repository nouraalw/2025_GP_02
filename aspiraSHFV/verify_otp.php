<?php
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["email"]) && isset($_POST["otp"])) {
    $email = $_POST["email"];
    $otp = $_POST["otp"];

    $query = "SELECT * FROM otp_verification WHERE email = ? AND otp_code = ? AND expiry_time >= NOW()";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $email, $otp);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo "valid"; // OTP is correct
    } else {
        echo "invalid"; // OTP is incorrect or expired
    }
}
?>
