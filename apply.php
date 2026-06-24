<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/init.php';
require_auth();
$user = current_user();
if ($user['role'] !== 'applicant') { header('Location: index.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vacancyId = (int)($_POST['vacancy_id'] ?? 0);
    $coverLetter = trim((string)($_POST['cover_letter'] ?? ''));
    if ($vacancyId > 0 && $coverLetter !== '') {
        try {
            db()->prepare('INSERT INTO applications (vacancy_id,applicant_id,cover_letter) VALUES (:v,:a,:c)')
                ->execute(['v'=>$vacancyId,'a'=>$user['id'],'c'=>$coverLetter]);
            header('Location: vacancy_view.php?id='.$vacancyId.'&applied=1'); exit;
        } catch (PDOException $e) {
            header('Location: vacancy_view.php?id='.$vacancyId.'&error=duplicate'); exit;
        }
    }
}
header('Location: index.php'); exit;
