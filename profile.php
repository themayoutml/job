<?php
declare(strict_types=1);
require_once __DIR__ . '/partials_header.php';
require_auth();
$user = current_user();
$errors = [];
$success = ($_GET['status'] ?? '') === 'saved';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $bio = trim((string)($_POST['bio'] ?? ''));
    $skills = trim((string)($_POST['skills'] ?? ''));
    $desiredSalary = trim((string)($_POST['desired_salary'] ?? ''));
    $positionTitle = trim((string)($_POST['position_title'] ?? ''));
    $education = trim((string)($_POST['education'] ?? ''));
    $companyName = trim((string)($_POST['company_name'] ?? ''));
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($fullName === '') $errors[] = 'Укажите имя.';
    if ($newPassword !== '' && strlen($newPassword) < 6) $errors[] = 'Пароль не короче 6 символов.';
    if ($newPassword !== '' && $newPassword !== $confirmPassword) $errors[] = 'Пароли не совпадают.';

    if (!$errors) {
        $params = [
            'full_name' => $fullName, 'phone' => $phone, 'bio' => $bio,
            'skills' => $skills, 'desired_salary' => $desiredSalary,
            'position_title' => $positionTitle, 'education' => $education,
            'company_name' => $companyName, 'id' => $user['id'],
        ];
        if ($newPassword !== '') {
            $params['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = 'UPDATE users SET full_name=:full_name, phone=:phone, bio=:bio, skills=:skills,
                    desired_salary=:desired_salary, position_title=:position_title, education=:education,
                    company_name=:company_name, password_hash=:password_hash WHERE id=:id';
        } else {
            $sql = 'UPDATE users SET full_name=:full_name, phone=:phone, bio=:bio, skills=:skills,
                    desired_salary=:desired_salary, position_title=:position_title, education=:education,
                    company_name=:company_name WHERE id=:id';
        }
        db()->prepare($sql)->execute($params);
        header('Location: profile.php?status=saved');
        exit;
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card auth-card-wide">
        <div class="page-header"><h1>Мой профиль</h1><p>Заполните данные — они помогут при откликах и поиске</p></div>

        <div class="card">
    <?php if ($success): ?><p class="success"><i class="fa-solid fa-check"></i> Профиль сохранён.</p><?php endif; ?>
    <?php foreach ($errors as $error): ?><p class="error"><?= e($error) ?></p><?php endforeach; ?>

    <form method="post">
        <div class="form-row">
            <div class="form-group"><label>ФИО</label><input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required></div>
            <div class="form-group"><label>Телефон</label><input type="text" name="phone" value="<?= e((string)$user['phone']) ?>"></div>
        </div>
        <div class="form-group"><label>Email</label><input type="email" value="<?= e($user['email']) ?>" disabled></div>

        <?php if ($user['role'] === 'applicant'): ?>
            <div class="form-row">
                <div class="form-group"><label>Желаемая должность</label><input type="text" name="position_title" value="<?= e((string)$user['position_title']) ?>" placeholder="PHP-разработчик"></div>
                <div class="form-group"><label>Желаемая зарплата</label><input type="text" name="desired_salary" value="<?= e((string)$user['desired_salary']) ?>" placeholder="300 000 ₸"></div>
            </div>
            <div class="form-group"><label>Навыки</label><input type="text" name="skills" value="<?= e((string)$user['skills']) ?>" placeholder="PHP, MySQL, Git (через запятую)"></div>
            <div class="form-group"><label>Образование</label><input type="text" name="education" value="<?= e((string)$user['education']) ?>" placeholder="ВУЗ, специальность"></div>
            <div class="form-group"><label>О себе / резюме</label><textarea name="bio" rows="6"><?= e((string)$user['bio']) ?></textarea></div>
        <?php else: ?>
            <div class="form-group"><label>Название компании</label><input type="text" name="company_name" value="<?= e((string)$user['company_name']) ?>"></div>
            <div class="form-group"><label>О компании</label><textarea name="bio" rows="6"><?= e((string)$user['bio']) ?></textarea></div>
        <?php endif; ?>

        <hr class="form-divider">
        <h3>Сменить пароль</h3>
        <div class="form-row">
            <div class="form-group"><label>Новый пароль</label><input type="password" name="new_password"></div>
            <div class="form-group"><label>Повторите</label><input type="password" name="confirm_password"></div>
        </div>
        <div class="button-group">
            <button type="submit"><i class="fa-solid fa-check"></i> Сохранить</button>
            <a class="button button-outline" href="dashboard.php">Назад</a>
        </div>
    </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials_footer.php'; ?>
