<?php
$filename = "Online_Courses_English_Final_Partial.csv";
$search = $_GET['q'] ?? "";

// تحميل CSV
$courses = [];
if (($handle = fopen($filename, "r")) !== FALSE) {
    $header = fgetcsv($handle);
    while (($data = fgetcsv($handle)) !== FALSE) {
        $row = array_combine($header, $data);
        $courses[] = $row;
    }
    fclose($handle);
}

// بحث بالعنوان فقط
$searchTerm = strtolower(trim($search));
$filtered = array_filter($courses, function($course) use ($searchTerm) {
    if ($searchTerm === "") return true; // لو الحقل فاضي، رجّع الكل
    $title = strtolower(trim($course['Title'] ?? ""));
    return strpos($title, $searchTerm) !== false;
});

// رجع النتائج كـ HTML
if (empty($filtered)) {
    echo "<div class='no-results'>
            <i class='fas fa-exclamation-circle' style='color:#a576ff;font-size:22px;'></i>
            <p style='color:#4b398e;font-weight:500;'>No courses found matching your search.</p>
          </div>";
    exit;
}

foreach ($filtered as $course) {
    $title = htmlspecialchars($course['Title'] ?? "");
    $url   = htmlspecialchars($course['URL'] ?? "");
    $category = htmlspecialchars($course['Category'] ?? "");
    $site  = htmlspecialchars($course['Site'] ?? "");
    $skills = htmlspecialchars($course['Skills'] ?? "");

    echo "
    <div class='course-card'>
        <div class='course-title'>
            <a href='$url' target='_blank'>$title</a>
        </div>
        <div class='course-meta'>
            <span class='course-tag'><i class='fas fa-tag'></i> $category</span>
            <span class='course-tag'><i class='fas fa-globe'></i> $site</span>
        </div>
        <div class='skills'>
            <strong><i class='fas fa-cogs'></i> Skills:</strong> $skills
        </div>
    </div>
    ";
}
?>
