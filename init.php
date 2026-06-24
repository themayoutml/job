<?php
declare(strict_types=1);

// Подключаем конфиг, но не запускаем сессии
if (!defined('NO_SESSION')) {
    define('NO_SESSION', true);
}
require_once __DIR__ . '/config.php';

// Создаём простую блокировку, чтобы миграции запускались не чаще раза в 1 минуту
$lockFile = __DIR__ . '/.migrations.lock';
$runMigrations = true;
if (file_exists($lockFile)) {
    $lastRun = filemtime($lockFile);
    if (time() - $lastRun < 60) {
        $runMigrations = false;
    }
}

if ($runMigrations) {
    $pdo = db();

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL CHECK(role IN ("employer", "applicant", "admin")),
            bio TEXT NOT NULL DEFAULT "",
            phone TEXT NOT NULL DEFAULT "",
            skills TEXT NOT NULL DEFAULT "",
            desired_salary TEXT NOT NULL DEFAULT "",
            position_title TEXT NOT NULL DEFAULT "",
            education TEXT NOT NULL DEFAULT "",
            company_name TEXT NOT NULL DEFAULT "",
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS vacancies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employer_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            salary TEXT NOT NULL,
            location TEXT NOT NULL,
            tags TEXT NOT NULL DEFAULT "",
            views_count INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employer_id) REFERENCES users(id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS applications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vacancy_id INTEGER NOT NULL,
            applicant_id INTEGER NOT NULL,
            cover_letter TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT "new",
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(vacancy_id, applicant_id),
            FOREIGN KEY (vacancy_id) REFERENCES vacancies(id),
            FOREIGN KEY (applicant_id) REFERENCES users(id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS employer_reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employer_id INTEGER NOT NULL,
            applicant_id INTEGER NOT NULL,
            rating INTEGER NOT NULL CHECK(rating >= 1 AND rating <= 5),
            comment TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(employer_id, applicant_id),
            FOREIGN KEY (employer_id) REFERENCES users(id),
            FOREIGN KEY (applicant_id) REFERENCES users(id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sender_id INTEGER NOT NULL,
            receiver_id INTEGER NOT NULL,
            body TEXT NOT NULL,
            attachment_name TEXT,
            attachment_original TEXT,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id),
            FOREIGN KEY (receiver_id) REFERENCES users(id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS favorites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            vacancy_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, vacancy_id),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (vacancy_id) REFERENCES vacancies(id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS telegram_auth_codes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT NOT NULL UNIQUE,
            user_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL
        )'
    );

    $migrations = [
        'users' => [
            'bio' => 'TEXT NOT NULL DEFAULT ""',
            'phone' => 'TEXT NOT NULL DEFAULT ""',
            'skills' => 'TEXT NOT NULL DEFAULT ""',
            'desired_salary' => 'TEXT NOT NULL DEFAULT ""',
            'position_title' => 'TEXT NOT NULL DEFAULT ""',
            'education' => 'TEXT NOT NULL DEFAULT ""',
            'company_name' => 'TEXT NOT NULL DEFAULT ""',
            'active' => 'INTEGER NOT NULL DEFAULT 1',
            'telegram_id' => 'INTEGER',
            'telegram_username' => 'TEXT',
        ],
        'applications' => ['status' => 'TEXT NOT NULL DEFAULT "new"'],
        'messages' => [
            'is_read' => 'INTEGER NOT NULL DEFAULT 0',
            'attachment_name' => 'TEXT',
            'attachment_original' => 'TEXT',
        ],
        'vacancies' => [
            'views_count' => 'INTEGER NOT NULL DEFAULT 0',
            'tags' => 'TEXT NOT NULL DEFAULT ""',
            'active' => 'INTEGER NOT NULL DEFAULT 1',
        ],
    ];

    foreach ($migrations as $table => $columns) {
        $existing = $pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll();
        $names = array_column($existing, 'name');
        foreach ($columns as $name => $definition) {
            if (!in_array($name, $names, true)) {
                $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $name . ' ' . $definition);
            }
        }
    }

    // Создаём уникальный индекс для telegram_id, если его нет
    try {
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_users_telegram_id ON users(telegram_id)');
    } catch (Exception $e) {
        // Игнорируем ошибку, если индекс уже существует
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $adminEmail = 'admin@diplom.local';
    $adminExistsStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $adminExistsStmt->execute(['email' => $adminEmail]);
    if (!$adminExistsStmt->fetch()) {
        $insertAdminStmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, password_hash, role) VALUES (:full_name, :email, :password_hash, :role)'
        );
        $insertAdminStmt->execute([
            'full_name' => 'Главный администратор',
            'email' => $adminEmail,
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
        ]);
    }

    require_once __DIR__ . '/seed_vacancies.php';
    seed_demo_vacancies($pdo);

    // Обновляем блокировку
    touch($lockFile);
}
