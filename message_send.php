<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/init.php';
require_auth();
$user = current_user();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: messages.php'); exit; }

$receiverId = (int)($_POST['receiver_id'] ?? 0);
$vacancyId = (int)($_POST['vacancy_id'] ?? 0);
$body = trim((string)($_POST['body'] ?? ''));
$attachment = save_uploaded_file($_FILES['attachment'] ?? []);

if ($receiverId <= 0 || ($body === '' && !$attachment) || $receiverId === (int)$user['id']) {
    header('Location: '.($vacancyId > 0 ? 'vacancy_view.php?id='.$vacancyId : 'messages.php')); exit;
}

$originalName = $attachment ? (string)($_FILES['attachment']['name'] ?? 'file') : null;
db()->prepare(
    'INSERT INTO messages (sender_id,receiver_id,body,attachment_name,attachment_original) VALUES (:s,:r,:b,:a,:o)'
)->execute([
    's'=>$user['id'], 'r'=>$receiverId, 'b'=>$body ?: '📎 Файл',
    'a'=>$attachment, 'o'=>$originalName,
]);

header('Location: messages.php?with='.$receiverId.'&status=sent'); exit;
