<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/init.php';
require_auth();
$user = current_user();
if ($user['role'] !== 'applicant') { header('Location: index.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
$vacancyId = (int)($_POST['vacancy_id'] ?? 0);
$redirect = (string)($_POST['redirect'] ?? 'index.php');
if (str_contains($redirect, '..') || !preg_match('/^[a-z0-9_.\-]+(\.php)(\?[^\s#]*)?$/i', $redirect)) $redirect = 'index.php';
if ($vacancyId > 0) {
    $exists = db()->prepare('SELECT 1 FROM favorites WHERE user_id=:u AND vacancy_id=:v');
    $exists->execute(['u'=>$user['id'],'v'=>$vacancyId]);
    if ($exists->fetchColumn()) {
        db()->prepare('DELETE FROM favorites WHERE user_id=:u AND vacancy_id=:v')->execute(['u'=>$user['id'],'v'=>$vacancyId]);
    } else {
        db()->prepare('INSERT INTO favorites (user_id,vacancy_id) VALUES (:u,:v)')->execute(['u'=>$user['id'],'v'=>$vacancyId]);
    }
}
header('Location: '.$redirect); exit;
