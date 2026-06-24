<?php
declare(strict_types=1);
require_once __DIR__ . '/partials_header.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT v.*, u.full_name AS employer_name, u.bio AS employer_bio, u.company_name,
            ROUND(AVG(er.rating),1) AS employer_rating, COUNT(DISTINCT er2.id) AS reviews_count,
            COUNT(DISTINCT a.id) AS applications_count
     FROM vacancies v JOIN users u ON u.id=v.employer_id
     LEFT JOIN employer_reviews er ON er.employer_id=u.id
     LEFT JOIN employer_reviews er2 ON er2.employer_id=u.id
     LEFT JOIN applications a ON a.vacancy_id=v.id
     WHERE v.id=:id GROUP BY v.id'
);
$stmt->execute(['id'=>$id]);
$vacancy = $stmt->fetch();
$user = current_user();
$canManage = $user && ($user['role']==='admin' || ($vacancy && (int)$vacancy['employer_id']===(int)$user['id']));
if (!$vacancy || ((int)$vacancy['active'] !== 1 && !$canManage)): ?>
    <div class="card empty-state"><p>Вакансия не найдена или скрыта.</p><a class="button" href="index.php">На главную</a></div>
    <?php require_once __DIR__ . '/partials_footer.php'; exit; ?>
<?php endif;
increment_vacancy_views($id);
$vacancy['views_count'] = (int)$vacancy['views_count'] + 1;
$myApplication = null; $canRate = false; $alreadyRated = false;
if ($user && $user['role'] === 'applicant') {
    $as = db()->prepare('SELECT * FROM applications WHERE vacancy_id=:v AND applicant_id=:a');
    $as->execute(['v'=>$id,'a'=>$user['id']]); $myApplication = $as->fetch() ?: null;
    $canRate = can_rate_employer((int)$user['id'], (int)$vacancy['employer_id']);
    $alreadyRated = has_reviewed_employer((int)$user['id'], (int)$vacancy['employer_id']);
}
$isFavorite = $user && $user['role']==='applicant' && is_favorite((int)$user['id'],$id);
$company = (string)($vacancy['company_name'] ?: $vacancy['employer_name']);
$shareUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off')?'https':'http').'://'.($_SERVER['HTTP_HOST']??'localhost').($_SERVER['SCRIPT_NAME']??'/vacancy_view.php').'?id='.$id;
?>

<?php if (($_GET['published']??'')==='1'): ?><p class="success banner-alert">Вакансия опубликована!</p><?php endif; ?>
<?php if (($_GET['rated']??'')==='1'): ?><p class="success banner-alert">Оценка сохранена. Спасибо!</p><?php endif; ?>
<?php if (($_GET['error']??'')==='no_contract'): ?><p class="error banner-alert">Оценку можно оставить только после приглашения (договорённости) с работодателем.</p><?php endif; ?>

<nav class="breadcrumbs"><a href="index.php">Вакансии</a><span>/</span><span><?= e($vacancy['location']) ?></span></nav>

<div class="vacancy-hero card">
    <div class="vacancy-hero-top">
        <div class="vacancy-hero-main">
            <h1><?= e($vacancy['title']) ?></h1>
            <div class="vacancy-hero-employer">
                <a href="employer_view.php?id=<?= (int)$vacancy['employer_id'] ?>"><?= e($company) ?></a>
                <?php if ($vacancy['employer_rating']): ?><span class="hero-rating"><i class="fa-solid fa-star"></i> <?= e((string)$vacancy['employer_rating']) ?></span><?php endif; ?>
            </div>
            <?php if ((string)$vacancy['tags'] !== ''): ?><div class="meta" style="margin-top:10px"><?= render_tags((string)$vacancy['tags']) ?></div><?php endif; ?>
        </div>
        <div class="vacancy-hero-salary"><span class="salary-label">Зарплата</span><strong><?= e($vacancy['salary']) ?></strong></div>
    </div>
    <div class="vacancy-hero-chips">
        <span class="chip"><i class="fa-solid fa-location-dot"></i> <?= e($vacancy['location']) ?></span>
        <span class="chip"><i class="fa-solid fa-eye"></i> <?= (int)$vacancy['views_count'] ?> просмотров</span>
        <span class="chip"><i class="fa-solid fa-clock"></i> <?= e(format_relative_time((string)$vacancy['created_at'])) ?></span>
    </div>
    <div class="vacancy-hero-actions">
        <?php if ($user && $user['role']==='applicant'): ?>
            <a class="button button-lg" href="#respond"><i class="fa-solid fa-paper-plane"></i> Откликнуться</a>
            <form method="post" action="favorite_toggle.php" class="inline">
                <input type="hidden" name="vacancy_id" value="<?= $id ?>"><input type="hidden" name="redirect" value="vacancy_view.php?id=<?= $id ?>">
                <button type="submit" class="button button-outline btn-favorite <?= $isFavorite?'active':'' ?>"><i class="fa-<?= $isFavorite?'solid':'regular' ?> fa-heart"></i></button>
            </form>
        <?php endif; ?>
        <?php if ($canManage): ?><a class="button button-outline" href="vacancy_edit.php?id=<?= $id ?>"><i class="fa-solid fa-pen"></i> Редактировать</a><?php endif; ?>
        <a class="button button-outline" href="employer_view.php?id=<?= (int)$vacancy['employer_id'] ?>"><i class="fa-solid fa-building"></i> Профиль работодателя</a>
        <button type="button" class="button button-outline" id="copy-link-btn" data-url="<?= e($shareUrl) ?>"><i class="fa-solid fa-link"></i></button>
    </div>
</div>

<div class="vacancy-detail">
    <div class="vacancy-detail-main">
        <div class="card content-card"><div class="content-card-header"><h2><i class="fa-solid fa-file-lines"></i> Описание</h2></div><div class="prose"><?= nl2br(e($vacancy['description'])) ?></div></div>

        <?php if ($user && $user['role']==='applicant'): ?>
        <div class="interact-panel" id="respond">
            <h2 class="interact-panel-title">Ваши действия</h2>
            <?php if ($myApplication): ?>
                <div class="card interact-card interact-card-success">
                    <div class="applied-notice"><i class="fa-solid fa-circle-check"></i><div>
                        <strong>Отклик отправлен</strong>
                        <p><span class="application-status <?= e(application_status_class((string)$myApplication['status'])) ?>"><?= e(application_status_label((string)$myApplication['status'])) ?></span></p>
                        <p class="letter-preview"><?= nl2br(e($myApplication['cover_letter'])) ?></p>
                    </div></div>
                </div>
            <?php else: ?>
                <div class="card interact-card">
                    <h3><i class="fa-solid fa-pen-to-square"></i> Сопроводительное письмо</h3>
                    <form method="post" action="apply.php">
                        <input type="hidden" name="vacancy_id" value="<?= $id ?>">
                        <textarea name="cover_letter" rows="6" required><?= e((string)($user['bio']??'')) ?></textarea>
                        <button type="submit" class="button" style="margin-top:10px"><i class="fa-solid fa-paper-plane"></i> Отправить</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="interact-grid">
                <div class="card interact-card interact-card-compact">
                    <h3><i class="fa-solid fa-star"></i> Оценить работодателя</h3>
                    <?php if ($alreadyRated): ?>
                        <p class="muted">Вы уже оставили оценку этому работодателю (один раз).</p>
                    <?php elseif (!$canRate): ?>
                        <p class="muted">Оценка доступна только после приглашения на работу (статус «Приглашение»).</p>
                    <?php else: ?>
                        <form method="post" action="review_submit.php">
                            <input type="hidden" name="employer_id" value="<?= (int)$vacancy['employer_id'] ?>">
                            <input type="hidden" name="vacancy_id" value="<?= $id ?>">
                            <select name="rating" required><option value="">Оценка</option><?php for($i=5;$i>=1;$i--): ?><option value="<?= $i ?>"><?= $i ?> ★</option><?php endfor; ?></select>
                            <textarea name="comment" rows="3" placeholder="Отзыв..." required></textarea>
                            <button type="submit" class="button button-sm">Отправить оценку</button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card interact-card interact-card-compact">
                    <h3><i class="fa-solid fa-envelope"></i> Сообщение + файл</h3>
                    <form method="post" action="message_send.php" enctype="multipart/form-data">
                        <input type="hidden" name="receiver_id" value="<?= (int)$vacancy['employer_id'] ?>">
                        <input type="hidden" name="vacancy_id" value="<?= $id ?>">
                        <textarea name="body" rows="3" placeholder="Текст сообщения..."></textarea>
                        <div class="file-upload"><label><i class="fa-solid fa-paperclip"></i> Прикрепить файл<input type="file" name="attachment" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png"></label></div>
                        <button type="submit" class="button button-sm">Отправить</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="vacancy-sidebar">
        <div class="card summary-card sticky-summary">
            <div class="summary-salary"><?= e($vacancy['salary']) ?></div>
            <ul class="summary-list">
                <li><i class="fa-solid fa-location-dot"></i> <?= e($vacancy['location']) ?></li>
                <li><i class="fa-solid fa-building"></i> <a href="employer_view.php?id=<?= (int)$vacancy['employer_id'] ?>"><?= e($company) ?></a></li>
            </ul>
            <?php if ($user && $user['role']==='applicant' && !$myApplication): ?>
                <a class="button" href="#respond" style="width:100%;margin-top:12px">Откликнуться</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials_footer.php'; ?>
