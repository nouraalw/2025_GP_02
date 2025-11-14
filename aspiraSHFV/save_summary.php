<?php
session_start();
include "db_connection.php";
require("fpdf/fpdf.php"); // تأكد أن المكتبة موجودة في مجلد fpdf

$mentor_id  = $_SESSION['user_id'] ?? 0;
$session_id = $_POST['session_id'] ?? 0;
$summary    = trim($_POST['summary'] ?? '');

if (!$mentor_id || !$session_id || empty($summary)) {
    echo "invalid";
    exit;
}

// 🔹 1) حفظ النص في قاعدة البيانات
$stmt = $conn->prepare("UPDATE sessions SET summary=? WHERE id=? AND mentor_id=?");
$stmt->bind_param("sii", $summary, $session_id, $mentor_id);
$stmt->execute();
$stmt->close();

// 🔹 2) جلب بيانات الجلسة (اسم المنتور وتاريخ الجلسة)
$q = $conn->prepare("
    SELECT s.date, s.time, u.first_name, u.last_name
    FROM sessions s
    JOIN users u ON s.mentor_id = u.user_id
    WHERE s.id = ? AND s.mentor_id = ?
    LIMIT 1
");
$q->bind_param("ii", $session_id, $mentor_id);
$q->execute();
$session = $q->get_result()->fetch_assoc();
$q->close();

// 🔹 3) توليد ملف PDF
$pdf = new FPDF();
$pdf->AddPage();

// شعار ASPIRA
$pdf->Image('images/logo.png', 10, 8, 20);

// العنوان
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, "Session Summary", 0, 1, 'C');
$pdf->Ln(5);

// بيانات الجلسة
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, "Mentor: " . $session['first_name'] . " " . $session['last_name'], 0, 1);
$pdf->Cell(0, 8, "Date: " . $session['date'] . "   Time: " . $session['time'], 0, 1);
$pdf->Ln(5);

// الملخص
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, "Key Points:", 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->MultiCell(0, 8, $summary);
$pdf->Ln(10);

// 🔹 4) حفظ الـ PDF في مجلد معين
$folder = "uploads/summaries/";
if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}
$filename = $folder . "session_" . $session_id . ".pdf";
$pdf->Output("F", $filename);

// 🔹 5) تحديث الرابط في قاعدة البيانات
$stmt2 = $conn->prepare("UPDATE sessions SET summary_pdf=? WHERE id=?");
$stmt2->bind_param("si", $filename, $session_id);
$stmt2->execute();
$stmt2->close();

// 🔹 6) إرسال النتيجة إلى JavaScript
echo "success";
exit;
?>
