<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/init.php';
require_auth();
$user = current_user();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

$employerId = (int)($_POST['employer_id'] ?? 0);
$vacancyId = (int)($_POST['vacancy_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim((string)($_POST['comment'] ?? ''));

if ($user['role'] !== 'applicant' || $employerId <= 0 || $rating < 1 || $rating > 5 || $comment === '') {
    header('Location: vacancy_view.php?id='.$vacancyId); exit;
}

if (!can_rate_employer((int)$user['id'], $employerId)) {
    header('Location: vacancy_view.php?id='.$vacancyId.'&error=no_contract'); exit;
}

if (has_reviewed_employer((int)$user['id'], $employerId)) {
    header('Location: vacancy_view.php?id='.$vacancyId.'&error=already_rated'); exit;
}

try {
    db()->prepare('INSERT INTO employer_reviews (employer_id,applicant_id,rating,comment) VALUES (:e,:a,:r,:c)')
        ->execute(['e'=>$employerId,'a'=>$user['id'],'r'=>$rating,'c'=>$comment]);
    header('Location: vacancy_view.php?id='.$vacancyId.'&rated=1'); exit;
} catch (PDOException $e) {
    header('Location: vacancy_view.php?id='.$vacancyId.'&error=already_rated'); exit;
}
