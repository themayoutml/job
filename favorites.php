<?php
declare(strict_types=1);
require_once __DIR__ . '/partials_header.php';
require_auth();
$user = current_user();
if ($user['role'] !== 'applicant') { header('Location: index.php'); exit; }
$stmt = db()->prepare('SELECT v.*, u.full_name AS employer_name, u.company_name, f.created_at AS saved_at FROM favorites f JOIN vacancies v ON v.id=f.vacancy_id JOIN users u ON u.id=v.employer_id WHERE f.user_id=:id ORDER BY f.created_at DESC');
$stmt->execute(['id'=>$user['id']]); $favorites = $stmt->fetchAll();
?>
<div class="page-header"><h1>Избранное</h1></div>
<?php if (!$favorites): ?><div class="card empty-state"><p>Пусто.</p></div>
<?php else: foreach ($favorites as $v): ?>
<div class="card vacancy-card"><div class="vacancy-card-body">
    <h3><a href="vacancy_view.php?id=<?= (int)$v['id'] ?>"><?= e($v['title']) ?></a></h3>
    <p class="muted"><?= e((string)($v['company_name']?:$v['employer_name'])) ?></p>
</div><a class="button" href="vacancy_view.php?id=<?= (int)$v['id'] ?>">Открыть</a></div>
<?php endforeach; endif; ?>
<?php require_once __DIR__ . '/partials_footer.php'; ?>
