<?php

include "db_connection.php";
session_start();

// جلب user_id من السيشن
$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo "error: not logged in";
    exit;
}

// استقبال البيانات
$title = $_POST['title'] ?? '';
$url = $_POST['url'] ?? '';
$category = $_POST['category'] ?? '';
$site = $_POST['site'] ?? '';
$skills = $_POST['skills'] ?? '';

if ($title == '') {
    echo "error: no title";
    exit;
}


// 🔹 تحقق إذا الكورس محفوظ أصلاً
$check = $conn->prepare("SELECT id FROM saved_courses WHERE title = ? AND user_id = ?");
if (!$check) {
    echo "error: " . $conn->error;
    exit;
}
$check->bind_param("si", $title, $user_id);
$check->execute();
$result = $check->get_result();

if ($result && $result->num_rows > 0) {
    // موجود → نحذفه
    $delete = $conn->prepare("DELETE FROM saved_courses WHERE title = ? AND user_id = ?");
    if (!$delete) {
        echo "error: " . $conn->error;
        exit;
    }
    $delete->bind_param("si", $title, $user_id);
    if ($delete->execute()) {
        echo "deleted";
    } else {
        echo "error: " . $delete->error;
    }
} else {
    // غير موجود → نضيفه
    $insert = $conn->prepare("INSERT INTO saved_courses (title, url, category, site, skills, user_id) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$insert) {
        echo "error: " . $conn->error;
        exit;
    }
    $insert->bind_param("sssssi", $title, $url, $category, $site, $skills, $user_id);
    if ($insert->execute()) {
        echo "saved";
    } else {
        echo "error: " . $insert->error;
    }
}