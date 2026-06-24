<?php
declare(strict_types=1);
require_once __DIR__ . '/partials_header.php';
require_auth();
$user = current_user();
$roleLabels = ['employer'=>'Работодатель','applicant'=>'Соискатель','admin'=>'Администратор'];
$initials = mb_strtoupper(mb_substr($user['full_name'], 0, 1));
?>
<div class="card profile-card">
    <div class="profile-avatar"><?= e($initials) ?></div>
    <div class="profile-info">
        <h2><?= e($user['full_name']) ?></h2>
        <div class="profile-meta">
            <span><?= e($user['email']) ?></span>
            <span class="role-tag"><?= e($roleLabels[$user['role']] ?? $user['role']) ?></span>
        </div>
    </div>
    <a class="button button-sm button-outline" href="profile.php"><i class="fa-solid fa-pen"></i> Профиль</a>
</div>

<?php if ($user['role'] === 'applicant'):
    $c1 = db()->prepare('SELECT COUNT(*) FROM applications WHERE applicant_id = :id');
    $c1->execute(['id' => $user['id']]);
    $apps = (int)$c1->fetchColumn();
    $c2 = db()->prepare('SELECT COUNT(*) FROM favorites WHERE user_id=:id'); $c2->execute(['id'=>$user['id']]); $favs = (int)$c2->fetchColumn();
?>
<div class="dashboard-grid">
    <div class="card stat-card"><div class="stat-value"><?= $apps ?></div><div class="stat-label">Отклики</div></div>
    <div class="card stat-card"><div class="stat-value"><?= $favs ?></div><div class="stat-label">Избранное</div></div>
</div>
<div class="card quick-links"><div class="button-group">
    <a class="button" href="index.php">Искать вакансии</a>
    <a class="button button-outline" href="my_applications.php">Мои отклики</a>
    <a class="button button-outline" href="favorites.php">Избранное</a>
</div></div>
<?php endif; ?>

<?php if ($user['role'] === 'employer' || $user['role'] === 'admin'):
    $vs = db()->prepare('SELECT v.*, COUNT(a.id) AS app_count FROM vacancies v LEFT JOIN applications a ON a.vacancy_id=v.id WHERE v.employer_id=:id GROUP BY v.id ORDER BY v.created_at DESC');
    $vs->execute(['id'=>$user['id']]); $myVacancies = $vs->fetchAll();
    $appsStmt = db()->prepare('SELECT a.*, v.title, u.full_name AS applicant_name, u.email, u.phone, u.skills, u.education, u.position_title, u.desired_salary FROM applications a JOIN vacancies v ON v.id=a.vacancy_id JOIN users u ON u.id=a.applicant_id WHERE v.employer_id=:id ORDER BY a.created_at DESC');
    $appsStmt->execute(['id'=>$user['id']]); $incoming = $appsStmt->fetchAll();
?>
<?php if (($_GET['status']??'')==='updated'): ?><p class="success">Статус обновлён. При «Приглашение» соискатель сможет оценить вас один раз.</p><?php endif; ?>
<div class="card" id="applications"><h2>Отклики</h2>
<?php if (!$incoming): ?><p class="muted">Пока нет откликов.</p><?php else: foreach ($incoming as $a): $st=(string)($a['status']??'new'); ?>
<div class="application-item">
    <div class="application-item-header">
        <div><h4><?= e($a['applicant_name']) ?></h4><p class="muted"><?= e($a['title']) ?></p>
        <?php if ($a['position_title']): ?><p class="muted">Должность: <?= e($a['position_title']) ?> | ЗП: <?= e($a['desired_salary']) ?></p><?php endif; ?>
        <?php if ($a['skills']): ?><p class="muted">Навыки: <?= e($a['skills']) ?></p><?php endif; ?>
        <?php if ($a['education']): ?><p class="muted">Образование: <?= e($a['education']) ?></p><?php endif; ?>
        </div>
        <span class="application-status <?= e(application_status_class($st)) ?>"><?= e(application_status_label($st)) ?></span>
    </div>
    <p class="muted" style="white-space:pre-line"><?= e($a['cover_letter']) ?></p>
    <form method="post" action="application_update.php" class="status-form">
        <input type="hidden" name="application_id" value="<?= (int)$a['id'] ?>">
        <select name="status"><option value="new" <?= $st==='new'?'selected':'' ?>>Новый</option><option value="viewed" <?= $st==='viewed'?'selected':'' ?>>Просмотрен</option><option value="invited" <?= $st==='invited'?'selected':'' ?>>Приглашение</option><option value="rejected" <?= $st==='rejected'?'selected':'' ?>>Отказ</option></select>
        <button type="submit" class="button-sm">Сохранить</button>
        <a class="button button-sm button-outline" href="messages.php?with=<?= (int)$a['applicant_id'] ?>">Написать</a>
    </form>
</div>
<?php endforeach; endif; ?></div>

<div class="card"><h2>Мои вакансии</h2>
<?php foreach ($myVacancies as $v): ?>
<div class="vacancy-list-item">
    <div class="vacancy-list-info"><a href="vacancy_view.php?id=<?= (int)$v['id'] ?>"><?= e($v['title']) ?></a><p class="muted"><?= (int)$v['app_count'] ?> откл. · <?= (int)$v['views_count'] ?> просм.</p></div>
    <div class="button-group">
        <a class="button button-sm button-outline" href="vacancy_edit.php?id=<?= (int)$v['id'] ?>">Редактировать</a>
        <a class="button button-sm button-danger" href="vacancy_delete.php?id=<?= (int)$v['id'] ?>" onclick="return confirm('Удалить?')">Удалить</a>
    </div>
</div>
<?php endforeach; ?>
<a class="button" href="vacancy_create.php" style="margin-top:12px"><i class="fa-solid fa-plus"></i> Новая вакансия</a>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/partials_footer.php'; ?>
