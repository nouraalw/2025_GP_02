<?php
$filename = "Online_Courses_English_Final_Partial.csv";
include "db_connection.php";
$perPage = 20;
$search = $_GET['q'] ?? "";

session_start();

// جلب user_id من السيشن
$user_id = $_SESSION['user_id'] ?? 0;
$cur_avatar = "images/person.png"; // صورة افتراضية

if ($user_id) {
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
    
    if ($avres && $avres->num_rows) {
        $row       = $avres->fetch_assoc();
        $cur_role  = strtolower(trim($row['role'] ?? ''));
        $mentor_pp = $row['profile_picture'] ?? '';
        
        if ($cur_role === 'mentor' && !empty($mentor_pp)) {
            $cur_avatar = $mentor_pp;
        }
    }
    $avq->close();
}


// 🔹 جلب الكورسات المحفوظة
$saved_courses = [];
$stmt = $conn->prepare("SELECT title FROM saved_courses WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $saved_courses[] = $row['title'];
}
$stmt->close();


// 🔹 تحميل البيانات من CSV
$courses = [];
if (($handle = fopen($filename, "r")) !== FALSE) {
    $header = fgetcsv($handle);
    while (($data = fgetcsv($handle)) !== FALSE) {
        $row = array_combine($header, $data);
        $courses[] = $row;
    }
    fclose($handle);
}


// 🔹 جلب التصنيف والفلاتر
$filterCategory = $_GET['category'] ?? "All";
$filterLevel = $_GET['level'] ?? "All";
$filterSite = $_GET['site'] ?? "All";
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$catLimit = isset($_GET['catLimit']) ? (int)$_GET['catLimit'] : 5;
$showSaved = isset($_GET['saved']) && $_GET['saved'] == 1;
$filterSkill = $_GET['skill'] ?? "";


// ✅ فلترة الكورسات (يبحث فقط بالعنوان Title ويشمل كامل الداتا)
if ($search !== "") {
    $searchTerm = strtolower(trim($search));

    $filtered = array_filter($courses, function($course) use ($searchTerm) {
        $title = strtolower(trim($course['Title'] ?? ""));
        return strpos($title, $searchTerm) !== false;
    });

    // عرض كل النتائج بدون باجينيشن
    $shown = array_values($filtered);
    $total = count($filtered);
    $totalPages = 1;

} else {
    // ✅ فلترة الكورسات حسب الحالة (محفوظة فقط أو الكل)
    if ($showSaved) {
        // المستخدم ضغط الزر → نعرض فقط الكورسات المحفوظة
        $filtered = array_filter($courses, function($course) use ($saved_courses) {
            return in_array($course['Title'], $saved_courses);
        });
    } else {
        // الوضع الطبيعي → نعرض كل الكورسات بدون تفضيل المحفوظة
        $filtered = array_filter($courses, function($course) use ($filterCategory, $filterSkill) {
            if (!empty($filterCategory) && $filterCategory !== "All") {
                $category = strtolower(trim($course['Category'] ?? ""));
                if ($category !== strtolower(trim($filterCategory))) return false;
            }
            if (!empty($filterSkill)) {
                $skills = strtolower(trim($course['Skills'] ?? ""));
                if (strpos($skills, strtolower($filterSkill)) === false) return false;
            }
            return true;
        });
    }

    // 🔹 ما نرتّب المحفوظة فوق إلا لما المستخدم يطلب عرضها فقط
    $filtered = array_values($filtered);

    // 🔹 تقسيم الصفحات
    $total = count($filtered);
    $totalPages = ceil($total / $perPage);
    $start = ($page - 1) * $perPage;
    $shown = array_slice($filtered, $start, $perPage);
}



// 🔹 استخراج التصنيفات / المستويات / المواقع
$allCategories = array_unique(array_column($courses, 'Category'));
sort($allCategories);

$allLevels = array_unique(array_column($courses, 'Level'));
sort($allLevels);

$allSites = array_unique(array_column($courses, 'Site'));
sort($allSites);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Online Courses - ASPIRA</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Select2 CSS -->
    <!-- jQuery لازم قبل Select2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

 <style>
    /* ✅ إعادة تعيين القيم الافتراضية */
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
        background: #f8f8f8;
        color: var(--button-color);
        margin: 0;
        padding: 0;
        line-height: 1.6;
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
        box-shadow: none !important;
        border-bottom: 0.4px solid rgba(0, 0, 0, 0.05);
        z-index: 100;
    }

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

    nav {
        display: flex;
        gap: 30px;
    }

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
        border-bottom: 2px solid #4b398e;
    }

    .profile-menu {
        position: relative;
        display: inline-block;
    }

    .profile-pic {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        cursor: pointer;
        object-fit: cover;
    }

    .dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 52px;
        background: #fff;
        border: 1px solid #eee;
        border-radius: 10px;
        min-width: 180px;
        box-shadow: 0 6px 18px rgba(0,0,0,.08);
        list-style: none;
        padding: 8px 0;
        margin: 0;
        z-index: 10;
    }

    .dropdown li {
        padding: 10px 16px;
    }

    .dropdown li a {
        color: #211742;
        text-decoration: none;
        display: block;
    }

    .dropdown li:hover {
        background: #f7f5ff;
    }

    .profile-menu:hover .dropdown {
        display: block;
    }

    /* محتوى الصفحة الرئيسية */
    .container {
        width: 90%;
        max-width: 1200px;
        margin: 30px auto;
        padding: 20px;
    }

    .page-title {
        color: #4b398e;
        font-size: 40px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 700;
    }

    /* الفلاتر */
    .filters-section {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .filter-group {
        margin-bottom: 20px;
    }

    .filter-title {
        font-size: 18px;
        font-weight: 600;
        color: #4b398e;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-options {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .filter-card {
        padding: 10px 18px;
        border-radius: 999px;
        background: white;
        border: 1px solid #ddd;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: #4b398e;
        text-align: center;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-card:hover {
        background: #4b398e;
        color: white;
        border-color: #4b398e;
        transform: translateY(-2px);
    }

    .filter-card.active {
        background: #4b398e;
        color: white;
        border-color: #4b398e;
    }

    .show-more {
        padding: 10px 18px;
        border-radius: 999px;
        background: #f7f5ff;
        border: 1px solid #ddd;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: #4b398e;
        text-align: center;
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .show-more:hover {
        background: #4b398e;
        color: white;
        border-color: #4b398e;
    }

    .toolbar {
        position: relative;
        width: 100%;
        margin-bottom: 25px;
        background: white;
        padding: 15px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    /* 🔹 تعديل شكل زر البحث ليطابق ستايل ASPIRA */
    .search-row {
        display: flex;
        align-items: center;
        width: 100%;
        gap: 8px;
        background: #fff;
    }

    .search-row input[type="search"] {
        flex: 1;
        padding: 12px 16px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 15px;
        color: #4b398e;
        outline: none;
        background: #fff;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .search-row input[type="search"]:focus {
        border-color: #a576ff;
        box-shadow: 0 0 6px rgba(165, 118, 255, 0.25);
    }

    /* الزر بنفس الستايل القديم */
    .search-row button {
        background: #4b398e;
        color: #fff;
        border: none;
        border-radius: 8px;        /* ← ناعم مثل القديم */
        padding: 10px 25px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: background 0.3s ease, transform 0.1s ease;
    }

    .search-row button:hover {
        background: #a576ff;
        transform: translateY(-1px);
    }

    /* شبكة الكورسات - تحسينات لمنع خروج النص */
    .courses {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }

    .course-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: transform 0.3s, box-shadow 0.3s;
        border: 1px solid #f0f0f0;
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden; /* منع خروج المحتوى */
    }

    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    .course-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 12px;
        color: #211742;
        line-height: 1.4;
        /* تحسينات لمنع خروج النص */
        display: -webkit-box;
        -webkit-line-clamp: 2; /* عدد الأسطر المسموح به */
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        min-height: 50px; /* ارتفاع ثابت للعنوان */
    }

    .course-title a {
        color: inherit;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .course-title a:hover {
        color: #a576ff;
    }
    
    /* 🔹 تحسين تخطيط course-meta ليكون أفقيًا مع زر الحفظ */
    .course-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
        align-items: center;
        justify-content: space-between;
    }

    .course-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        flex: 1;
    }

    .course-tag {
        padding: 4px 10px;
        background: #f7f5ff;
        color: #4b398e;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
        white-space: nowrap; /* منع كسر النص */
    }

    /* 🔹 تحسين زر الحفظ ليكون على نفس السطر */
    .save-btn {
        padding: 6px 12px;
        background: #f7f5ff;
        color: #4b398e;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
        border: 1px solid #ddd;
        transition: all 0.3s ease;
        white-space: nowrap;
        margin-left: auto; /* يدفعه لليمين */
    }

    .save-btn:hover {
        background: #4b398e;
        color: white;
        transform: scale(1.05);
    }

    .save-btn.saved {
        background: #4b398e;
        color: white;
        border-color: #4b398e;
    }

    .save-btn.saved i {
        color: #fff;
    }

    .skills {
        font-size: 13px;
        color: #555;
        line-height: 1.5;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
        /* تحسينات لمنع خروج النص */
        display: -webkit-box;
        -webkit-line-clamp: 3; /* عدد الأسطر المسموح به للمهارات */
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        flex-grow: 1; /* شغل المساحة المتبقية */
    }

    /* تقسيم الصفحات */
    .pagination {
        text-align: center;
        margin: 30px 0;
    }

    .pagination a {
        margin: 0 5px;
        padding: 10px 15px;
        background: white;
        text-decoration: none;
        border-radius: 8px;
        border: 1px solid #ddd;
        color: #4b398e;
        font-weight: 500;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .pagination a:hover {
        background: #4b398e;
        color: white;
        border-color: #4b398e;
    }

    .pagination a.active {
        background: #4b398e;
        color: white;
        border-color: #4b398e;
    }

    /* Footer */
    footer {
        text-align: center;
        padding: 20px;
        background: #ffffff;
        color: #211742;
        margin-top: 50px;
        box-shadow: none !important;
        border-top: 0.3px solid rgba(0, 0, 0, 0.05);
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
        justify-content: flex-start;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }

    .arrow {
        font-size: 12px;
        transition: transform 0.3s ease;
    }

    .arrow.down {
        transform: rotate(90deg);
    }

    /* زر الهامبرجر */
    .hamburger {
        display: none;
        font-size: 26px;
        cursor: pointer;
        color: #4b398e;
    }

    /* Select2 تحسينات - تصميم متوافق مع ASPIRA */
    .filter-card-wrapper {
        background: #fff;
        padding: 25px 30px;
        border-radius: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        margin-bottom: 30px;
        position: relative;
    }

    .filter-card-box {
        display: flex;
        gap: 30px;
        align-items: center;
        flex-wrap: wrap;
        justify-content: flex-start;
    }

    .filter-select {
        flex: 1 1 250px;
        display: flex;
        flex-direction: column;
    }

    .filter-select label {
        font-weight: 600;
        color: #4b398e;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-select select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
        background: white;
        font-size: 14px;
        color: #4b398e;
        transition: all 0.3s ease;
    }

    .filter-select select:hover {
        border-color: #a576ff;
        box-shadow: 0 0 6px rgba(165, 118, 255, 0.3);
    }

    /* 🔹 تحسينات Select2 لتتناسب مع تصميم ASPIRA */
    .select2-container--default .select2-selection--single {
        border: 1px solid #ccc !important;
        border-radius: 8px !important;
        height: 40px !important;
        background: white !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px !important;
        color: #4b398e !important;
        font-size: 14px !important;
        padding-left: 12px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px !important;
        right: 6px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #4b398e transparent transparent transparent !important;
    }

    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        border-color: transparent transparent #4b398e transparent !important;
    }

    /* 🔹 تحسين dropdown الـ Select2 */
    .select2-container--default .select2-dropdown {
        border: 1px solid #ccc !important;
        border-radius: 8px !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
        background: white !important;
    }

    .select2-container--default .select2-results__option {
        padding: 8px 12px !important;
        color: #4b398e !important;
        font-size: 14px !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #a576ff !important;
        color: white !important;
    }

    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: #f7f5ff !important;
        color: #4b398e !important;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #ccc !important;
        border-radius: 6px !important;
        padding: 6px 10px !important;
        margin: 8px !important;
        color: #4b398e !important;
    }

    /* 🔹 زر صغير بنفس صف الفلاتر */
    .saved-btn-tiny {
    background: #4b398e;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 4px;
    align-self: flex-end; /* يخليه بمحاذاة الفلاتر */
}

.saved-btn-tiny:hover {
    background: #6c3fdd;
}

.saved-btn-tiny.active {
    background: #a576ff;
}


    /* Responsive Design */
    @media (max-width: 768px) {
        header {
            padding: 15px 20px;
            flex-wrap: wrap;
        }
        
        nav {
            position: fixed;
            top: 0;
            right: -250px;
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
            display: block;
        }

        nav.active {
            right: 0;
        }
        
        .container {
            width: 95%;
            padding: 10px;
        }
        
        .courses {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }
        
        .course-title {
            font-size: 16px;
            min-height: 45px;
        }
        
        .filter-options {
            justify-content: flex-start;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        
        .toolbar {
            flex-direction: column;
        }

        .footer-links {
            flex-direction: column;
            gap: 20px;
        }

        .filter-card-box {
            flex-direction: column;
            gap: 15px;
        }

        .filter-select {
            flex: 1 1 100%;
        }

        .saved-btn-tiny {
            position: relative;
            right: auto;
            top: auto;
            margin-top: 10px;
            align-self: flex-start;
        }

        /* 🔹 تحسين course-meta للجوال */
        .course-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .save-btn {
            margin-left: 0;
            align-self: flex-end;
        }
    }

    @media (max-width: 480px) {
        .courses {
            grid-template-columns: 1fr;
        }
        
        .course-meta {
            flex-direction: column;
            gap: 5px;
            align-items: flex-start;
        }

        .page-title {
            font-size: 32px;
        }

        .footer-links {
            padding: 0 2%;
        }

        .search-row {
            flex-direction: column;
            border: none;
        }
        
        .search-row input[type="search"] {
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .search-row button {
            width: 100%;
            border-radius: 8px;
        }
    }

    /* تحسينات إضافية للعناوين الطويلة */
    .course-title-long {
        -webkit-line-clamp: 3;
        min-height: 68px;
    }

    /* تحسينات للمهارات الطويلة */
    .skills-long {
        -webkit-line-clamp: 4;
    }

    /* حالة عدم وجود نتائج */
    .no-results {
        text-align: center;
        padding: 40px;
        color: #666;
        grid-column: 1 / -1;
    }

    .no-results i {
        font-size: 48px;
        margin-bottom: 15px;
        color: #ccc;
    }
</style>
     <link rel="stylesheet" href="css/responsive.css">
</head>
<body>

<header>
    <div class="logo">
        <img src="images/logo.png" alt="ASPIRA">
        <span class="logo-text">SPIRA</span>
    </div>
   
<div class="hamburger" onclick="toggleMenu()">☰</div>
<nav id="navMenu">
    <a href="cv_builder_mentee.php" class="<?= basename($_SERVER['PHP_SELF']) == 'cv_builder_mentee.php' ? 'active' : '' ?>">CV Builder</a>
    <a href="courses.php" class="<?= basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : '' ?>">Courses</a>
    <a href="MentorCenter.php" class="<?= basename($_SERVER['PHP_SELF']) == 'MentorCenter.php' ? 'active' : '' ?>">Mentor Center</a>
    <a href="GroupSessions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'GroupSessions.php' ? 'active' : '' ?>">Group Sessions</a>
    <a href="menteeUpcomingSession.php" class="<?= basename($_SERVER['PHP_SELF']) == 'menteeUpcomingSession.php' ? 'active' : '' ?>">Upcoming Sessions</a>
    <a href="mentee_past_sessions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'mentee_past_sessions.php' ? 'active' : '' ?>">Past Sessions</a>
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

<div class="container">
    <h1 class="page-title">Online Courses</h1>
    
<div class="filter-card-wrapper">
  <form id="searchForm" method="get" action="courses.php" style="margin-bottom: 0;">
    <div class="filters-container" style="display: flex; flex-direction: column; gap: 20px; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">

      <!-- ✅ صف البحث -->
      <div class="search-row">
        <input id="q" name="q" type="search" placeholder="Search courses... (e.g., Python, Data Science, Business)">
      </div>

           <!-- ✅ صف الفلاتر -->
      <div class="filters-row" style="display: flex; align-items: flex-end; gap: 20px; flex-wrap: wrap;">
        <div class="filter-select" style="flex: 1;">
          <label for="categoryDropdown" style="font-weight: 600; color: #4b398e;">
            <i class="fas fa-layer-group"></i> Category
          </label>
          <select id="categoryDropdown">
            <option value="All">All Categories</option>
            <?php foreach ($allCategories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>" <?= $cat == $filterCategory ? "selected" : "" ?>>
                <?= htmlspecialchars($cat) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-select" style="flex: 1;">
          <label for="skillsDropdown" style="font-weight: 600; color: #4b398e;">
            <i class="fas fa-cogs"></i> Skills
          </label>
          <select id="skillsDropdown" class="js-example-basic-single" style="width: 100%;">
            <?php if (!empty($filterSkill)): ?>
              <option value="<?= htmlspecialchars($filterSkill) ?>" selected><?= htmlspecialchars($filterSkill) ?></option>
            <?php else: ?>
              <option value="">Select a skill...</option>
            <?php endif; ?>
          </select>
        </div>

        <!-- ✅ الزر الآن داخل نفس الصف -->
        <button id="showSavedBtn" class="saved-btn-tiny" title="Show Saved Courses">
          <i class="fas fa-bookmark"></i>
        </button>
      </div>
    </div>
  </form>
</div>



    <!-- عرض الكورسات -->
    <div class="courses">
        <?php foreach ($shown as $course): ?>
            <div class="course-card">
                <div class="course-title">
                    <a href="<?= htmlspecialchars($course['URL'] ?? $course['Course URL']) ?>" target="_blank">
                        <?= htmlspecialchars($course['Title'] ?? $course['Course Title']) ?>
                    </a>
                </div>
                <div class="course-meta">
    <span class="course-tag">
        <i class="fas fa-tag"></i> <?= htmlspecialchars($course['Category'] ?? '') ?>
    </span>
    <span class="course-tag">
        <i class="fas fa-globe"></i> <?= htmlspecialchars($course['Site'] ?? '') ?>
    </span>

    <!-- زر الحفظ -->
<?php $is_saved = in_array($course['Title'], $saved_courses); ?>
<span class="save-btn"
    data-title="<?= htmlspecialchars($course['Title']) ?>" 
    data-url="<?= htmlspecialchars($course['URL']) ?>" 
    data-category="<?= htmlspecialchars($course['Category']) ?>" 
    data-site="<?= htmlspecialchars($course['Site']) ?>" 
    data-skills="<?= htmlspecialchars($course['Skills']) ?>">
    <i class="<?= $is_saved ? 'fas' : 'far' ?> fa-bookmark"
       style="color: <?= $is_saved ? '#6c3fdd' : 'inherit' ?>"></i>
</span>



</div>

                <div class="skills">
                    <strong><i class="fas fa-cogs"></i> Skills:</strong> <?= htmlspecialchars($course['Skills'] ?? $course['What you learn'] ?? '') ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- تقسيم الصفحات -->
    <div class="pagination">
        <?php 
        $range = 5; 
        $startPage = max(1, $page - floor($range/2));
        $endPage = min($totalPages, $startPage + $range - 1);

        if ($startPage > 1) {
            echo "<a href='?category=".urlencode($filterCategory)."&level=".urlencode($filterLevel)."&site=".urlencode($filterSite)."&page=".($startPage-1)."'><i class='fas fa-chevron-left'></i> Prev</a>";
        }
        for ($i = $startPage; $i <= $endPage; $i++): ?>
            <a href="?category=<?= urlencode($filterCategory) ?>&level=<?= urlencode($filterLevel) ?>&site=<?= urlencode($filterSite) ?>&page=<?= $i ?>" 
               class="<?= $i == $page ? "active" : "" ?>"><?= $i ?></a>
        <?php endfor; 
        if ($endPage < $totalPages) {
            echo "<a href='?category=".urlencode($filterCategory)."&level=".urlencode($filterLevel)."&site=".urlencode($filterSite)."&page=".($endPage+1)."'>Next <i class='fas fa-chevron-right'></i></a>";
        }
        ?>
    </div>
</div>

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
  <p>&copy; 2023 ASPIRA. All rights reserved.</p>
</footer>


<!-- ✅ البحث المباشر -->
<script>
let searchTimeout;
const searchInput = document.getElementById('q');
const coursesContainer = document.querySelector('.courses');
const pagination = document.querySelector('.pagination');

if (searchInput) {
  searchInput.addEventListener('input', () => {
    clearTimeout(searchTimeout); // منع التعليق أثناء الكتابة
    searchTimeout = setTimeout(async () => {
      const term = searchInput.value.trim();

      // إخفاء الباجينيشن وقت البحث
      if (pagination) pagination.style.display = term ? 'none' : 'block';

      // طلب البحث فقط لو فيه نص فعلاً
      const res = await fetch(`search_courses.php?q=${encodeURIComponent(term)}`);
      const html = await res.text();
      coursesContainer.innerHTML = html;
    }, 400); // تأخير بسيط لتجنب التعليق أثناء الكتابة
  });
}
</script>

<!-- ✅ القائمة الجانبية -->
<script>
function toggleMenu() {
  document.getElementById("navMenu").classList.toggle("active");
}
</script>

<!-- ✅ Select2 لتصفية المهارات -->
<script>
$(document).ready(function() {
  const skillDropdown = $('#skillsDropdown').select2({
    placeholder: 'Search skills...',
    allowClear: false,
    minimumInputLength: 0,
    ajax: {
      transport: function (params, success, failure) {
        const category = $('#categoryDropdown').val() || 'All';
        const query = params.data.term || '';
        fetch(`get_skills.php?category=${encodeURIComponent(category)}&q=${encodeURIComponent(query)}`)
          .then(res => res.json())
          .then(success)
          .catch(failure);
      },
      processResults: function (data) {
        return {
          results: data.map(skill => ({ id: skill, text: skill }))
        };
      }
    }
  });

  // تغيير الكاتيجوري
  $('#categoryDropdown').on('change', function() {
    const category = $(this).val();
    window.location.href = `?category=${encodeURIComponent(category)}&level=All&site=All`;
  });

  // اختيار مهارة
  skillDropdown.on('select2:select', function(e) {
    const skill = e.params.data.id;
    if (skill) {
      const params = new URLSearchParams(window.location.search);
      const category = params.get('category') || "All";
      const level = params.get('level') || "All";
      const site = params.get('site') || "All";
      window.location.href = `?category=${encodeURIComponent(category)}&level=${encodeURIComponent(level)}&site=${encodeURIComponent(site)}&skill=${encodeURIComponent(skill)}`;
    }
  });
});
</script>

<!-- ✅ زر Show Saved Courses -->
<script>
$(document).ready(function() {
  $('#showSavedBtn').on('click', function(e) {
    e.preventDefault();
    const params = new URLSearchParams(window.location.search);
    const isSavedView = params.get('saved') === '1';

    if (isSavedView) {
      params.delete('saved');
    } else {
      params.set('saved', '1');
    }

    window.location.search = params.toString();
  });

  // تفعيل مظهر الزر عند فتح الصفحة
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('saved') === '1') {
    $('#showSavedBtn').addClass('active');
  } else {
    $('#showSavedBtn').removeClass('active');
  }
});
</script>

<!-- ✅ كود الحفظ والحذف -->
<script>
function attachSaveHandlers() {
  document.querySelectorAll('.save-btn').forEach(btn => {
    btn.addEventListener('click', async function () {
      const icon = this.querySelector("i");
      const formData = new FormData();
      formData.append("title", this.dataset.title);
      formData.append("url", this.dataset.url);
      formData.append("category", this.dataset.category);
      formData.append("site", this.dataset.site);
      formData.append("skills", this.dataset.skills);

      try {
        const res = await fetch("save_course.php", { method: "POST", body: formData });
        const data = (await res.text()).trim();

        if (data === "saved") {
          icon.classList.remove("far");
          icon.classList.add("fas");
          icon.style.color = "#6c3fdd";
        } else if (data === "deleted") {
          icon.classList.remove("fas");
          icon.classList.add("far");
          icon.style.color = "inherit";
        }

        // تحديث فوري إذا المستخدم في وضع المحفوظة
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('saved') === '1') {
          const refreshed = await fetch(window.location.href);
          const text = await refreshed.text();
          const parser = new DOMParser();
          const newDoc = parser.parseFromString(text, "text/html");
          document.querySelector(".courses").innerHTML =
            newDoc.querySelector(".courses").innerHTML;
          attachSaveHandlers(); // إعادة تفعيل الأزرار بعد التحديث
        }
      } catch (err) {
        console.error("Fetch error:", err);
      }
    });
  });
}

// ✅ تشغيل عند تحميل الصفحة
document.addEventListener("DOMContentLoaded", attachSaveHandlers);
</script>

</body>
</html>