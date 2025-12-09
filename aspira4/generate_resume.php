<?php

require __DIR__.'/fpdf/fpdf.php';
include 'db_connection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['cv_id']) || !ctype_digit($_GET['cv_id'])) {
    http_response_code(400);
    exit('Invalid CV ID.');
}
$cv_id = (int)$_GET['cv_id'];

//  Fetch CV (snapshots)
$sql = "
SELECT 
  cv.cv_id, cv.mentee_id, cv.title, cv.summary,
  cv.first_name_snapshot, cv.last_name_snapshot, cv.email_snapshot, cv.phone_number,
  cv.education, cv.experience, cv.skills, cv.certifications, cv.languages
FROM cv
WHERE cv.cv_id = ?
LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cv_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || !$res->num_rows) {
    http_response_code(404);
    exit('CV not found.');
}
$row = $res->fetch_assoc();
$stmt->close();
$conn->close();

//  Prepare fields
$fullName = trim(($row['first_name_snapshot'] ?? '').' '.($row['last_name_snapshot'] ?? ''));
$email    = $row['email_snapshot'] ?? '';
$phone    = $row['phone_number']   ?? '';

$title       = $row['title']   ?: 'Professional CV';
$summary     = $row['summary'] ?? '';

$EDU  = $row['education']      ? json_decode($row['education'], true)      : [];
$EXP  = $row['experience']     ? json_decode($row['experience'], true)     : [];
$SKL  = $row['skills']         ? json_decode($row['skills'], true)         : [];
$CERT = $row['certifications'] ? json_decode($row['certifications'], true) : [];
$LNG  = $row['languages']      ? json_decode($row['languages'], true)      : [];

//  Helpers
function t($s) {
    return iconv('UTF-8','Windows-1252//TRANSLIT',$s ?? '');
}
function lineDate($start, $end, $isCurrent = 0) {
    $s = $start ?: '';
    $e = $isCurrent ? 'Present' : ($end ?: '');
    if ($s && $e) return "$s - $e";
    if ($s) return "$s";
    if ($e) return "$e";
    return '';
}
function bulletsFromText($txt) {
    $out = [];
    foreach (preg_split("/\r\n|\n|\r/", (string)$txt) as $ln) {
        $ln = trim($ln);
        if ($ln !== '') $out[] = $ln;
    }
    return $out;
}

//  PDF
class CVPDF extends FPDF {
    function Header(){ }
    function SectionTitle($txt){
        $this->SetFont('Arial','B',12);
        $this->Cell(0,8, t($txt), 0, 1, 'L');
        $this->SetDrawColor(200,200,200);
        $this->SetLineWidth(0.2);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX()+190, $this->GetY());
        $this->Ln(4);
    }
    function BulletList($items) {
        $this->SetFont('Arial','',10);
        foreach($items as $it){
            $this->Cell(4,5, chr(149), 0, 0);
            $this->MultiCell(0,5, t($it), 0, 'L');
        }
    }
}

$pdf = new CVPDF('P','mm','A4');
$pdf->SetTitle(t($title));
$pdf->SetAuthor(t($fullName));
$pdf->AddPage();
$pdf->SetMargins(10,10,10);

// Header (Name big + contact line) — Center
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10, t($fullName ?: 'Unnamed'), 0, 1, 'C');

$pdf->SetFont('Arial','',10);
$contacts = [];
if ($email) $contacts[] = $email;
if ($phone) $contacts[] = $phone;
$pdf->Cell(0,6, t(implode('  |  ', $contacts)), 0, 1, 'C');
$pdf->Ln(4);

// Professional Summary
if (trim($summary) !== '') {
    $pdf->SectionTitle('Professional Summary');
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,6, t($summary));
    $pdf->Ln(2);
}

// Education
if (!empty($EDU)) {
    $pdf->SectionTitle('Education');
    foreach($EDU as $e){
        $school   = $e['school']   ?? '';
        $degree   = $e['degree']   ?? '';
        $field    = $e['field']    ?? ($e['field_of_study'] ?? '');
        $location = $e['location'] ?? '';
        $start    = $e['start']    ?? '';
        $end      = $e['end']      ?? '';
        $details  = $e['details']  ?? '';

        $pdf->SetFont('Arial','B',11);
        if ($school!=='') $pdf->Cell(0,6, t($school), 0, 1, 'L');

        $meta = array_filter([ $degree, $field, $location ]);
        $date = lineDate($start,$end,0);
        $pdf->SetFont('Arial','',10);
        if (!empty($meta)) {
            $pdf->MultiCell(0,5, t(implode(' • ', $meta)), 0, 'L');
        }
        if ($date!=='') {
            $pdf->SetFont('Arial','I',10);
            $pdf->MultiCell(0,5, t($date), 0, 'L');
        }
        if ($details!=='') {
            $pdf->SetFont('Arial','',10);
            $pdf->MultiCell(0,5, t($details), 0, 'L');
        }
        $pdf->Ln(2);
    }
}

// Work Experience
if (!empty($EXP)) {
    $pdf->SectionTitle('Work Experience');
    foreach($EXP as $x){
        $titleX   = $x['title']      ?? '';
        $company  = $x['company']    ?? '';
        $location = $x['location']   ?? '';
        $start    = $x['start']      ?? '';
        $end      = $x['end']        ?? '';
        $current  = !empty($x['is_current']) ? 1 : 0;
        $desc     = $x['description']?? '';

        $headline = array_filter([ $titleX, $company ]);
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(0,6, t(implode(' — ', $headline) ?: $titleX), 0, 1, 'L');

        $date  = lineDate($start,$end,$current);
        $info  = trim(implode(' • ', array_filter([ $location, $date ])));
        if ($info!=='') {
            $pdf->SetFont('Arial','I',10);
            $pdf->MultiCell(0,5, t($info), 0, 'L');
        }

        $bullets = bulletsFromText($desc);
        if (!empty($bullets)) {
            $pdf->BulletList($bullets);
        }
        $pdf->Ln(1);
    }
}

// Skills
if (!empty($SKL)) {
    $pdf->SectionTitle('Skills');
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,6, t(implode(', ', $SKL)), 0, 'L');
    $pdf->Ln(1);
}

// Certifications
if (!empty($CERT)) {
    $pdf->SectionTitle('Certifications');
    foreach($CERT as $c){
        $name   = $c['name']   ?? '';
        $issuer = $c['issuer'] ?? '';
        $issue  = $c['issue']  ?? '';
        $expiry = $c['expiry'] ?? '';
        if ($name!=='') {
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(0,6, t($name), 0, 1, 'L');
        }
        $pdf->SetFont('Arial','',10);
        $line2 = trim(implode(' • ', array_filter([ $issuer, lineDate($issue,$expiry,0) ])));
        if ($line2!=='') {
            $pdf->MultiCell(0,5, t($line2), 0, 'L');
        }
    }
    $pdf->Ln(1);
}

// Languages
if (!empty($LNG)) {
    $pdf->SectionTitle('Languages');
    $pairs = [];
    foreach($LNG as $l){
        $lang = $l['language'] ?? '';
        $lvl  = $l['level']    ?? ($l['proficiency'] ?? '');
        if ($lang!=='') $pairs[] = trim($lang.($lvl? " ($lvl)":""));
    }
    if (!empty($pairs)) {
        $pdf->SetFont('Arial','',10);
        $pdf->MultiCell(0,6, t(implode(', ', $pairs)), 0, 'L');
    }
}

$pdf->Ln(2);
$pdf->Output('D', 'resume.pdf');
exit;
