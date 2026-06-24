<?php
declare(strict_types=1);
require_once __DIR__ . '/partials_header.php';
$query = trim((string)($_GET['q'] ?? ''));
$location = trim((string)($_GET['location'] ?? ''));
$tag = trim((string)($_GET['tag'] ?? ''));
$sort = (string)($_GET['sort'] ?? 'newest');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
$fromSql = 'FROM vacancies v JOIN users u ON u.id=v.employer_id LEFT JOIN employer_reviews er ON er.employer_id=u.id';
$whereSql = ' WHERE v.active = 1'; $params = [];
if ($query !== '') { $whereSql .= ' AND (v.title LIKE :q OR v.description LIKE :q)'; $params['q'] = '%'.$query.'%'; }
if ($location !== '') { $whereSql .= ' AND v.location = :l'; $params['l'] = $location; }
if ($tag !== '') { $whereSql .= ' AND v.tags LIKE :t'; $params['t'] = '%'.$tag.'%'; }
$countStmt = db()->prepare('SELECT COUNT(DISTINCT v.id) '.$fromSql.$whereSql);
$countStmt->execute($params);
$totalResults = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalResults / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$orderSql = $sort === 'rating' ? ' ORDER BY employer_rating DESC, v.created_at DESC' : ' ORDER BY v.created_at DESC';
$sql = 'SELECT v.*, u.full_name AS employer_name, u.company_name, ROUND(AVG(er.rating),1) AS employer_rating '.$fromSql.$whereSql.' GROUP BY v.id'.$orderSql.' LIMIT :lim OFFSET :off';
$stmt = db()->prepare($sql);
foreach ($params as $k=>$v) $stmt->bindValue(':'.$k, $v);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$vacancies = $stmt->fetchAll();
$currentUser = current_user();
$appliedIds = $favIds = [];
if ($currentUser && $currentUser['role'] === 'applicant') {
    $as = db()->prepare('SELECT vacancy_id FROM applications WHERE applicant_id = :id');
    $as->execute(['id' => $currentUser['id']]);
    $appliedIds = array_map('intval', $as->fetchAll(PDO::FETCH_COLUMN));
    $favIds = user_favorite_ids((int)$currentUser['id']);
}
function build_index_url(array $overrides=[]): string { return build_url('index.php', array_merge($_GET, $overrides)); }
$allTags = [];
foreach (db()->query('SELECT tags FROM vacancies WHERE tags != "" AND active = 1')->fetchAll() as $row) {
    foreach (parse_tags((string)$row['tags']) as $t) $allTags[$t] = ($allTags[$t] ?? 0) + 1;
}
arsort($allTags);
// Collect all unique cities
$allCities = [];
foreach (db()->query('SELECT location FROM vacancies WHERE location != "" AND active = 1')->fetchAll() as $row) {
    $city = trim((string)$row['location']);
    if ($city !== '') {
        $allCities[$city] = ($allCities[$city] ?? 0) + 1;
    }
}
arsort($allCities);
?>

<div class="card hero">
    <h1>Найдите работу мечты</h1>
    <p class="hero-subtitle">Вакансии с тегами, профилями работодателей и удобным откликом</p>
    <div class="search-box">
        <form method="get" class="search-grid">
            <div class="search-field"><label>Должность</label><input type="text" name="q" value="<?= e($query) ?>" placeholder="PHP-разработчик"></div>
            <div class="search-field"><label>Город</label><input type="text" name="location" value="<?= e($location) ?>" placeholder="Алматы"></div>
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Найти</button>
        </form>
    </div>
</div>

<?php if ($allCities): ?>
<div class="filter-bar"><div class="filter-chips">
    <a class="filter-chip <?= $location===''?'active':'' ?>" href="<?= e(build_index_url(['location'=>'','page'=>1])) ?>">Все города</a>
    <?php $i=0; foreach ($allCities as $city=>$cnt): if (++$i>10) break; ?>
        <a class="filter-chip <?= $location===$city?'active':'' ?>" href="<?= e(build_index_url(['location'=>$city,'page'=>1])) ?>"><?= e($city) ?></a>
    <?php endforeach; ?>
</div></div>
<?php endif; ?>

<?php if ($allTags): ?>
<div class="filter-bar"><div class="filter-chips">
    <a class="filter-chip <?= $tag===''?'active':'' ?>" href="<?= e(build_index_url(['tag'=>'','page'=>1])) ?>">Все теги</a>
    <?php $i=0; foreach ($allTags as $t=>$cnt): if (++$i>10) break; ?>
        <a class="filter-chip <?= $tag===$t?'active':'' ?>" href="<?= e(build_index_url(['tag'=>$t,'page'=>1])) ?>">#<?= e($t) ?></a>
    <?php endforeach; ?>
</div></div>
<?php endif; ?>

<div class="section-title"><h2>Вакансии</h2><?php if ($totalResults): ?><span class="count-badge"><?= $totalResults ?></span><?php endif; ?></div>

<?php if (!$vacancies): ?>
    <div class="card empty-state"><p>Ничего не найдено.</p></div>
<?php else: foreach ($vacancies as $v):
    $isApplied = in_array((int)$v['id'], $appliedIds, true);
    $isFav = in_array((int)$v['id'], $favIds, true);
    $company = (string)($v['company_name'] ?: $v['employer_name']);
?>
    <div class="card vacancy-card">
        <div class="vacancy-card-body">
            <h3><a href="vacancy_view.php?id=<?= (int)$v['id'] ?>"><?= e($v['title']) ?></a></h3>
            <div class="meta">
                <span class="chip chip-accent"><?= e($v['salary']) ?></span>
                <span class="chip"><?= e($v['location']) ?></span>
                <?php if ($isApplied): ?><span class="chip chip-applied">Откликнулись</span><?php endif; ?>
            </div>
            <?php if ((string)$v['tags']): ?><div class="meta"><?= render_tags((string)$v['tags']) ?></div><?php endif; ?>
            <p class="vacancy-excerpt"><?= e(text_excerpt($v['description'])) ?></p>
            <p class="muted"><a href="employer_view.php?id=<?= (int)$v['employer_id'] ?>"><?= e($company) ?></a></p>
        </div>
        <div class="vacancy-card-actions">
            <?php if ($currentUser && $currentUser['role']==='applicant'): ?>
            <form method="post" action="favorite_toggle.php"><input type="hidden" name="vacancy_id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="redirect" value="<?= e(build_index_url(['page'=>$page])) ?>">
                <button class="button button-sm button-outline btn-favorite <?= $isFav?'active':'' ?>"><i class="fa-<?= $isFav?'solid':'regular' ?> fa-heart"></i></button></form>
            <?php endif; ?>
            <a class="button" href="vacancy_view.php?id=<?= (int)$v['id'] ?>">Подробнее</a>
        </div>
    </div>
<?php endforeach; if ($totalPages>1): ?>
<nav class="pagination">
    <?php if ($page>1): ?><a class="button button-sm button-outline" href="<?= e(build_index_url(['page'=>$page-1])) ?>">Назад</a><?php endif; ?>
    <span class="pagination-info"><?= $page ?> / <?= $totalPages ?></span>
    <?php if ($page<$totalPages): ?><a class="button button-sm button-outline" href="<?= e(build_index_url(['page'=>$page+1])) ?>">Далее</a><?php endif; ?>
</nav>
<?php endif; endif; ?>

<?php require_once __DIR__ . '/partials_footer.php'; ?>
