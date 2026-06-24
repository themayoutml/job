<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/init.php';
require_auth();
$user = current_user();
$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT employer_id FROM vacancies WHERE id = :id');
$stmt->execute(['id' => $id]);
$v = $stmt->fetch();
if ($v && ($user['role'] === 'admin' || (int)$v['employer_id'] === (int)$user['id'])) {
    db()->prepare('DELETE FROM vacancies WHERE id = :id')->execute(['id' => $id]);
}
header('Location: dashboard.php');
exit;
