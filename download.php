<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/init.php';
require_auth();

$file = basename((string)($_GET['file'] ?? ''));
if ($file === '' || !preg_match('/^[a-f0-9]{24}\.[a-z0-9]+$/', $file)) {
    http_response_code(404);
    exit;
}

$path = UPLOAD_DIR . '/' . $file;
if (!is_file($path)) {
    http_response_code(404);
    exit;
}

$stmt = db()->prepare(
    'SELECT attachment_original FROM messages
     WHERE attachment_name = :name AND (sender_id = :me OR receiver_id = :me2)
     LIMIT 1'
);
$stmt->execute(['name' => $file, 'me' => $_SESSION['user_id'], 'me2' => $_SESSION['user_id']]);
$row = $stmt->fetch();
if (!$row) {
    http_response_code(403);
    exit;
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt' => 'text/plain',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . basename((string)$row['attachment_original']) . '"');
readfile($path);
exit;
