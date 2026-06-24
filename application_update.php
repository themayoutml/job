<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/init.php';
require_auth();
$user = current_user();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: dashboard.php'); exit; }
$applicationId = (int)($_POST['application_id'] ?? 0);
$status = (string)($_POST['status'] ?? '');
if ($applicationId <= 0 || !in_array($status, ['new','viewed','invited','rejected'], true)) { header('Location: dashboard.php'); exit; }
$stmt = db()->prepare('SELECT a.id, v.employer_id FROM applications a JOIN vacancies v ON v.id=a.vacancy_id WHERE a.id=:id');
$stmt->execute(['id'=>$applicationId]);
$app = $stmt->fetch();
if ($app && ($user['role'] === 'admin' || (int)$app['employer_id'] === (int)$user['id'])) {
    db()->prepare('UPDATE applications SET status=:s WHERE id=:id')->execute(['s'=>$status,'id'=>$applicationId]);
}
header('Location: dashboard.php?status=updated#applications'); exit;
