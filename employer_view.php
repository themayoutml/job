<?php
declare(strict_types=1);
require_once __DIR__ . '/partials_header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT u.*, ROUND(AVG(er.rating), 1) AS avg_rating, COUNT(er.id) AS reviews_count
     FROM users u
     LEFT JOIN employer_reviews er ON er.employer_id = u.id
     WHERE u.id = :id AND u.role IN ("employer", "admin")
     GROUP BY u.id'
);
$stmt->execute(['id' => $id]);
$employer = $stmt->fetch();

if (!$employer): ?>
    <div class="card empty-state"><p>Работодатель не найден.</p></div>
    <?php require_once __DIR__ . '/partials_footer.php'; exit; ?>
<?php endif;

$vacanciesStmt = db()->prepare('SELECT * FROM vacancies WHERE employer_id = :id ORDER BY created_at DESC');
$vacanciesStmt->execute(['id' => $id]);
$vacancies = $vacanciesStmt->fetchAll();

$reviewsStmt = db()->prepare(
    'SELECT er.*, u.full_name AS author_name FROM employer_reviews er
     JOIN users u ON u.id = er.applicant_id WHERE er.employer_id = :id ORDER BY er.created_at DESC LIMIT 10'
);
$reviewsStmt->execute(['id' => $id]);
$reviews = $reviewsStmt->fetchAll();

$displayName = (string)($employer['company_name'] ?: $employer['full_name']);
$initials = mb_strtoupper(mb_substr($displayName, 0, 1));
?>

<div class="employer-profile-hero card">
    <div class="employer-profile-top">
        <span class="employer-avatar-lg"><?= e($initials) ?></span>
        <div>
            <h1><?= e($displayName) ?></h1>
            <p class="muted"><?= e($employer['full_name']) ?></p>
            <?php if ($employer['avg_rating']): ?>
                <p class="hero-rating"><i class="fa-solid fa-star"></i> <?= e((string)$employer['avg_rating']) ?> / 5 (<?= (int)$employer['reviews_count'] ?> отзывов)</p>
            <?php endif; ?>
        </div>
    </div>
    <?php if ((string)$employer['bio'] !== ''): ?>
        <p class="prose"><?= nl2br(e((string)$employer['bio'])) ?></p>
    <?php endif; ?>
</div>

<div class="section-title"><h2>Активные вакансии</h2><span class="count-badge"><?= count($vacancies) ?></span></div>

<?php if (!$vacancies): ?>
    <div class="card empty-state"><p>Нет опубликованных вакансий.</p></div>
<?php else: ?>
    <div class="vacancy-grid">
        <?php foreach ($vacancies as $v): ?>
            <div class="card vacancy-card">
                <div class="vacancy-card-body">
                    <h3><a href="vacancy_view.php?id=<?= (int)$v['id'] ?>"><?= e($v['title']) ?></a></h3>
                    <div class="meta">
                        <span class="chip chip-accent"><i class="fa-solid fa-money-bill-wave"></i> <?= e($v['salary']) ?></span>
                        <span class="chip"><i class="fa-solid fa-location-dot"></i> <?= e($v['location']) ?></span>
                    </div>
                    <?php if ((string)$v['tags'] !== ''): ?><div class="meta"><?= render_tags((string)$v['tags']) ?></div><?php endif; ?>
                </div>
                <div class="vacancy-card-actions"><a class="button" href="vacancy_view.php?id=<?= (int)$v['id'] ?>">Открыть</a></div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($reviews): ?>
    <div class="card" style="margin-top:20px">
        <h2>Отзывы сотрудников и соискателей</h2>
        <?php foreach ($reviews as $r): ?>
            <div class="review-item">
                <p><strong><?= e($r['author_name']) ?></strong> <span class="stars"><?= str_repeat('★', (int)$r['rating']) ?></span></p>
                <p><?= nl2br(e($r['comment'])) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/partials_footer.php'; ?>
