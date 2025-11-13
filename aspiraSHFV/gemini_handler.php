<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$user_message = trim($input['message'] ?? '');

if ($user_message === '') {
  echo json_encode(["error" => "Empty message"]);
  exit;
}

$api_key = getenv('GEMINI_API_KEY');
if (!$api_key) {
  echo json_encode(["error" => "API key not found"]);
  exit;
}

$context_text = '';
$report_path = __DIR__ . "/2_ASPIRA_Report_(2).pdf";
if (file_exists($report_path)) {
    $temp_txt = tempnam(sys_get_temp_dir(), 'aspira_');
    $poppler_path = "C:\\poppler-25.07.0\\Library\\bin\\pdftotext.exe";
     @exec("\"$poppler_path\" -layout " . escapeshellarg($report_path) . " " . escapeshellarg($temp_txt));

    $context_text = @file_get_contents($temp_txt);
    unlink($temp_txt);
}

$system_prompt = "You are ASPIRA AI assistant. Use the following context about the ASPIRA mentorship platform when answering.
If the question is about ASPIRA, reply based on this context. Be concise and helpful.

Context:
$context_text
";

$final_prompt = $system_prompt . "\nUser: " . $user_message;

$data = [
  "contents" => [[
    "role" => "user",
    "parts" => [["text" => $final_prompt]]
  ]]
];

$model = "models/gemini-2.5-flash";

$url = "https://generativelanguage.googleapis.com/v1beta/$model:generateContent?key=$api_key";
$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_POSTFIELDS => json_encode($data),
  CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
  echo json_encode(["error" => "cURL error: " . curl_error($ch)]);
  curl_close($ch);
  exit;
}

curl_close($ch);
echo $response;
