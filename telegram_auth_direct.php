<?php
declare(strict_types=1);

// ВАЖНО: Отключаем сессии для этой страницы
define('NO_SESSION', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/telegram_config.php';

// Получаем выбранную роль
$role = isset($_GET['role']) ? $_GET['role'] : null;

if (!$role || !in_array($role, ['applicant', 'employer'])) {
    // Показываем форму выбора роли
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выберите роль</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .auth-container { max-width: 400px; margin: 100px auto; padding: 40px; }
        .auth-container h1 { margin-bottom: 30px; }
        .role-card { display: block; text-decoration: none; color: var(--text); border: 2px solid var(--border); border-radius: var(--radius-md); padding: 25px; margin-bottom: 15px; text-align: center; transition: all 0.2s; }
        .role-card:hover { border-color: var(--primary); box-shadow: var(--shadow-card); }
        .role-card h3 { margin: 0 0 10px 0; font-size: 20px; }
        .role-card p { margin: 0; color: var(--text-muted); }
    </style>
</head>
<body>
    <div class="container auth-container">
        <h1>Выберите роль</h1>
        <a href="telegram_auth_direct.php?role=applicant" class="role-card">
            <h3><i class="fa-solid fa-user"></i> Соискатель</h3>
            <p>Ищу работу</p>
        </a>
        <a href="telegram_auth_direct.php?role=employer" class="role-card">
            <h3><i class="fa-solid fa-building"></i> Работодатель</h3>
            <p>Ищу сотрудников</p>
        </a>
    </div>
</body>
</html>
<?php
    exit;
}

// Генерация кода авторизации
$code = bin2hex(random_bytes(16));
$expiresAt = date('Y-m-d H:i:s', time() + AUTH_CODE_TTL);

$pdo = db();
$stmt = $pdo->prepare('INSERT INTO telegram_auth_codes (code, expires_at, user_id) VALUES (?, ?, ?)');
// Заносим роль в user_id как временное значение, чтобы бот её потом использовал
// Используем отрицательные ID для ролей: -1 = applicant, -2 = employer
$tempRoleId = $role === 'applicant' ? -1 : -2;
$stmt->execute([$code, $expiresAt, $tempRoleId]);

// Генерация ссылки на бота
$botLink = 'https://t.me/' . TELEGRAM_BOT_USERNAME . '?start=auth_' . $code;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход через Telegram</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .auth-container { max-width: 400px; margin: 100px auto; padding: 40px; }
        .auth-container h1 { margin-bottom: 20px; }
        .role-badge { display: inline-block; background: var(--primary-light); color: var(--primary); padding: 8px 16px; border-radius: var(--radius-full); font-weight: 600; margin-bottom: 20px; }
        .auth-link { display: block; background: #0088cc; color: white; text-decoration: none; padding: 15px; text-align: center; border-radius: var(--radius-md); font-size: 18px; margin-bottom: 20px; }
        .auth-link:hover { background: #006699; }
        .status-text { text-align: center; color: var(--text-muted); }
        .btn-check { display: block; width: 100%; margin-top: 20px; padding: 12px; }
    </style>
</head>
<body>
    <div class="container auth-container">
        <h1>Вход через Telegram</h1>
        <span class="role-badge">
            <?= $role === 'applicant' ? '<i class="fa-solid fa-user"></i> Соискатель' : '<i class="fa-solid fa-building"></i> Работодатель' ?>
        </span>
        <a href="<?= $botLink ?>" target="_blank" class="auth-link">
            <i class="fa-brands fa-telegram"></i> Открыть бота
        </a>
        <p class="status-text">После нажатия Start в боте нажмите "Проверить статус"</p>
        <button class="btn-check button" onclick="checkStatus('<?= $code ?>')">
            Проверить статус
        </button>
    </div>

    <script>
        let checkInterval = null;
        
        function checkStatus(code) {
            fetch('telegram_auth.php?action=check_status&code=' + encodeURIComponent(code))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.authenticated) {
                        if (checkInterval) clearInterval(checkInterval);
                        window.location.href = 'dashboard.php';
                    } else {
                        alert('Ещё не готово — подождите и попробуйте снова');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ошибка проверки');
                });
        }
        
        // Автоматически проверяем каждые 2 секунды
        checkInterval = setInterval(() => checkStatus('<?= $code ?>'), 2000);
    </script>
</body>
</html>
