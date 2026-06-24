<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

// Данные админа
$fullName = 'Администратор';
$email = 'admin@job.local';
$password = 'admin123'; // Можете сменить на любой
$role = 'admin';

$pdo = db();

// Проверяем, есть ли уже админ с таким email
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing) {
    die("Админ с email {$email} уже существует (ID: {$existing['id']}). Пароль: admin123");
}

// Создаём админа
$stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, active) VALUES (?, ?, ?, ?, 1)');
$stmt->execute([
    $fullName,
    $email,
    password_hash($password, PASSWORD_DEFAULT),
    $role
]);

$adminId = $pdo->lastInsertId();
echo "✅ Админ создан успешно!\n";
echo "ID: {$adminId}\n";
echo "Email: {$email}\n";
echo "Пароль: {$password}\n";
echo "\nТеперь можете зайти на сайт под этими данными и открыть /admin.php.";
?>
