<?php
declare(strict_types=1);
require_once __DIR__ . '/partials_header.php';

$loginRole = (string)($_GET['role'] ?? $_POST['role'] ?? 'applicant');
if (!in_array($loginRole, ['applicant', 'employer'], true)) {
    $loginRole = 'applicant';
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $role = (string)($_POST['role'] ?? 'applicant');

    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if (($user['role'] === 'admin' || $user['role'] === $role) && ($user['role'] === 'admin' || (int)$user['active'] === 1)) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: dashboard.php');
            exit;
        } elseif ((int)$user['active'] !== 1) {
            $error = 'Аккаунт заблокирован.';
        } else {
            $error = 'Этот аккаунт зарегистрирован как ' . ($user['role'] === 'employer' ? 'работодатель' : 'соискатель') . '.';
        }
    } else {
        $error = 'Неверный email или пароль.';
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card auth-card-wide">
        <div class="card">
            <div class="login-tabs">
                <a href="login.php?role=applicant" class="login-tab <?= $loginRole === 'applicant' ? 'active' : '' ?>">
                    <i class="fa-solid fa-user"></i> Вход соискателя
                </a>
                <a href="login.php?role=employer" class="login-tab <?= $loginRole === 'employer' ? 'active' : '' ?>">
                    <i class="fa-solid fa-building"></i> Вход работодателя
                </a>
            </div>

            <h2><?= $loginRole === 'employer' ? 'Кабинет работодателя' : 'Кабинет соискателя' ?></h2>
            <p class="auth-subtitle"><?= $loginRole === 'employer' ? 'Управляйте вакансиями и откликами' : 'Ищите работу и откликайтесь на вакансии' ?></p>

            <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>

            <form method="post">
                <input type="hidden" name="role" value="<?= e($loginRole) ?>">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit"><i class="fa-solid fa-right-to-bracket"></i> Войти</button>
            </form>

            <hr class="form-divider">

            <div id="telegram-auth-section">
                <a href="telegram_auth_direct.php" class="button" style="background: #0088cc; width: 100%; display: flex; justify-content: center;">
                    <i class="fa-brands fa-telegram"></i> Войти через Telegram
                </a>
                <p class="auth-footer" style="font-size: 12px; margin-top: 10px;">
                    ⚠️ Для работы авторизации нужно запустить файл <code>запуск_бота.bat</code>
                </p>
            </div>
            <p class="auth-footer">Нет аккаунта? <a href="register.php?role=<?= e($loginRole) ?>">Зарегистрироваться</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials_footer.php'; ?>
