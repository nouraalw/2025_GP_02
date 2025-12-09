<?php
$filename = "Online_Courses.csv";
$category = $_GET['category'] ?? '';
$search = strtolower(trim($_GET['q'] ?? ''));
$skillsCount = [];

if ($category !== '' && file_exists($filename)) {
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== FALSE) {
            $row = array_combine($header, $data);
            if (isset($row['Category']) && trim($row['Category']) === trim($category)) {
                $skillsRaw = $row['Skills'] ?? $row['What you learn'] ?? '';
                $skillList = explode(',', $skillsRaw);
                foreach ($skillList as $s) {
                    $s = trim($s);
                    if ($s !== '') {
                        $skillsCount[$s] = ($skillsCount[$s] ?? 0) + 1;
                    }
                }
            }
        }
        fclose($handle);
    }
}

// ترتيب حسب التكرار
arsort($skillsCount);
$allSkills = array_keys($skillsCount);

// 🔹 لو المستخدم كتب شيء في السيرتش
if ($search !== '') {
    $filtered = array_filter($allSkills, function($skill) use ($search) {
        return stripos($skill, $search) !== false;
    });
    $skills = array_values($filtered); // كل النتائج اللي تحتوي الكلمة
} else {
    // 🔹 بدون بحث → فقط التوب 20
    $skills = array_slice($allSkills, 0, 20);
}

header('Content-Type: application/json');
echo json_encode($skills);
?>
