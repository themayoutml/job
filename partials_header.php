<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
$user = current_user();
$newApplicationsCount = 0;
$unreadMessagesCount = 0;
if ($user) {
    $unreadMessagesCount = count_unread_messages((int)$user['id']);
    if (in_array($user['role'], ['employer', 'admin'], true)) {
        $newApplicationsCount = count_new_applications_for_employer((int)$user['id']);
    }
}
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
function nav_active(string $page, string $current): string { return $page === $current ? ' active' : ''; }
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobHub — Платформа вакансий</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="app.js" defer></script>
</head>
<body>
<header>
    <div class="container">
        <div class="topbar">
            <a class="brand" href="index.php">
                <span class="brand-icon"><i class="fa-solid fa-briefcase"></i></span>
                <span class="brand-text"><strong>JobHub</strong><small>Платформа вакансий</small></span>
            </a>
            <div class="header-right">
                <nav class="nav-shell">
                    <a href="index.php" class="<?= nav_active('index.php', $currentPage) ?>"><i class="fa-solid fa-house"></i> Главная</a>
                    <?php if ($user): ?>
                        <a href="dashboard.php" class="<?= nav_active('dashboard.php', $currentPage) ?>">
                            <i class="fa-solid fa-user"></i> Кабинет
                            <?php if ($newApplicationsCount > 0): ?><span class="nav-badge"><?= $newApplicationsCount ?></span><?php endif; ?>
                        </a>
                        <a href="messages.php" class="<?= nav_active('messages.php', $currentPage) ?>">
                            <i class="fa-solid fa-comments"></i> Сообщения
                            <?php if ($unreadMessagesCount > 0): ?><span class="nav-badge"><?= $unreadMessagesCount ?></span><?php endif; ?>
                        </a>
                        <?php if ($user['role'] === 'employer' || $user['role'] === 'admin'): ?>
                            <a href="vacancy_create.php" class="nav-cta<?= nav_active('vacancy_create.php', $currentPage) ?>"><i class="fa-solid fa-plus"></i> Вакансия</a>
                        <?php endif; ?>
                        <?php if ($user['role'] === 'applicant'): ?>
                            <a href="my_applications.php" class="<?= nav_active('my_applications.php', $currentPage) ?>"><i class="fa-solid fa-paper-plane"></i> Отклики</a>
                            <a href="favorites.php" class="<?= nav_active('favorites.php', $currentPage) ?>"><i class="fa-solid fa-heart"></i> Избранное</a>
                        <?php endif; ?>
                        <?php if ($user['role'] === 'admin'): ?>
                            <a href="admin.php" class="<?= nav_active('admin.php', $currentPage) ?>"><i class="fa-solid fa-shield"></i> Админ</a>
                        <?php endif; ?>
                        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Выход</a>
                    <?php else: ?>
                        <a href="login.php?role=applicant" class="<?= nav_active('login.php', $currentPage) ?>"><i class="fa-solid fa-user"></i> Соискатель</a>
                        <a href="login.php?role=employer" class="nav-cta<?= nav_active('login.php', $currentPage) ?>"><i class="fa-solid fa-building"></i> Работодатель</a>
                    <?php endif; ?>
                </nav>
                <div class="header-actions">
                    <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Переключить тему"><i class="fa-solid fa-moon"></i></button>
                    <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Меню"><i class="fa-solid fa-bars"></i></button>
                </div>
            </div>
        </div>
    </div>
</header>
<main><div class="container">
