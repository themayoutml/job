<?php
declare(strict_types=1);
require_once __DIR__ . '/partials_header.php';
require_auth();
$user = current_user();
if ($user['role'] !== 'applicant') { header('Location: index.php'); exit; }
$stmt = db()->prepare('SELECT a.*, v.title, v.location, v.salary, v.id AS vacancy_id FROM applications a JOIN vacancies v ON v.id=a.vacancy_id WHERE a.applicant_id=:id ORDER BY a.created_at DESC');
$stmt->execute(['id'=>$user['id']]); $applications = $stmt->fetchAll();
?>
<div class="page-header"><h1>Мои отклики</h1></div>
<?php if (!$applications): ?><div class="card empty-state"><p>Нет откликов.</p><a class="button" href="index.php">Искать</a></div>
<?php else: foreach ($applications as $a): $st=(string)($a['status']??'new'); ?>
<div class="card application-card">
    <h3><a href="vacancy_view.php?id=<?= (int)$a['vacancy_id'] ?>"><?= e($a['title']) ?></a></h3>
    <span class="application-status <?= e(application_status_class($st)) ?>"><?= e(application_status_label($st)) ?></span>
    <p class="muted" style="white-space:pre-line"><?= e($a['cover_letter']) ?></p>
</div>
<?php endforeach; endif; ?>
<?php require_once __DIR__ . '/partials_footer.php'; ?>
