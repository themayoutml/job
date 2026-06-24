<?php
declare(strict_types=1);
require_once __DIR__ . '/partials_header.php';

$regRole = (string)($_GET['role'] ?? $_POST['role'] ?? 'applicant');
if (!in_array($regRole, ['applicant', 'employer'], true)) {
    $regRole = 'applicant';
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $role = (string)($_POST['role'] ?? 'applicant');
    $companyName = trim((string)($_POST['company_name'] ?? ''));

    if ($fullName === '' || $email === '' || $password === '') {
        $errors[] = 'Заполните обязательные поля.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email.';
    }
    if (!in_array($role, ['employer', 'applicant'], true)) {
        $errors[] = 'Некорректная роль.';
    }
    if ($role === 'employer' && $companyName === '') {
        $errors[] = 'Укажите название компании.';
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO users (full_name, email, password_hash, role, company_name)
                 VALUES (:full_name, :email, :password_hash, :role, :company_name)'
            );
            $stmt->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'company_name' => $companyName,
            ]);
            header('Location: login.php?role=' . urlencode($role));
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Пользователь с таким email уже существует.';
        }
    }
    $regRole = $role;
}
?>

<div class="auth-wrapper">
    <div class="auth-card auth-card-wide">
        <div class="card">
            <div class="login-tabs">
                <a href="register.php?role=applicant" class="login-tab <?= $regRole === 'applicant' ? 'active' : '' ?>"><i class="fa-solid fa-user"></i> Соискатель</a>
                <a href="register.php?role=employer" class="login-tab <?= $regRole === 'employer' ? 'active' : '' ?>"><i class="fa-solid fa-building"></i> Работодатель</a>
            </div>
            <h2>Регистрация</h2>
            <?php foreach ($errors as $error): ?><p class="error"><?= e($error) ?></p><?php endforeach; ?>
            <form method="post">
                <input type="hidden" name="role" value="<?= e($regRole) ?>">
                <div class="form-group"><label>ФИО</label><input type="text" name="full_name" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                <div class="form-group"><label>Пароль</label><input type="password" name="password" required></div>
                <?php if ($regRole === 'employer'): ?>
                    <div class="form-group"><label>Название компании</label><input type="text" name="company_name" placeholder="ООО Компания" required></div>
                <?php endif; ?>
                <button type="submit"><i class="fa-solid fa-user-plus"></i> Создать аккаунт</button>
            </form>
            <p class="auth-footer">Уже есть аккаунт? <a href="login.php?role=<?= e($regRole) ?>">Войти</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials_footer.php'; ?>
