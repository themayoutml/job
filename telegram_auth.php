<?php
declare(strict_types=1);

try {
    // ВАЖНО: Отключаем сессии, но потом включим только для check_status
    define('NO_SESSION', true);

    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/telegram_config.php';

    header('Content-Type: application/json');

    $action = $_GET['action'] ?? '';

    if ($action === 'generate_code') {
        // Генерация кода авторизации
        $code = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() + AUTH_CODE_TTL);

        $pdo = db();
        $stmt = $pdo->prepare('INSERT INTO telegram_auth_codes (code, expires_at) VALUES (?, ?)');
        $stmt->execute([$code, $expiresAt]);

        // Генерация ссылки на бота
        $botLink = 'https://t.me/' . TELEGRAM_BOT_USERNAME . '?start=auth_' . $code;

        echo json_encode([
            'success' => true,
            'code' => $code,
            'bot_link' => $botLink
        ]);
        exit;
    }

    if ($action === 'check_status') {
        // Проверка статуса авторизации — включаем сессии только здесь
        if (!is_dir(SESSION_DIR)) {
            mkdir(SESSION_DIR, 0755, true);
        }
        session_save_path(SESSION_DIR);
        session_start();

        $code = $_GET['code'] ?? '';

        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM telegram_auth_codes WHERE code = ? AND expires_at > datetime("now")');
        $stmt->execute([$code]);
        $authCode = $stmt->fetch();

        if ($authCode && $authCode['user_id']) {
            // Авторизуем пользователя
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$authCode['user_id']]);
            $user = $stmt->fetch();

            if ($user && (int)$user['active'] === 1) {
                $_SESSION['user_id'] = $user['id'];

                // Удаляем использованный код
                $stmt = $pdo->prepare('DELETE FROM telegram_auth_codes WHERE code = ?');
                $stmt->execute([$code]);

                echo json_encode([
                    'success' => true,
                    'authenticated' => true
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'authenticated' => false,
                    'error' => 'Аккаунт заблокирован'
                ]);
            }
        } else {
            echo json_encode([
                'success' => true,
                'authenticated' => false
            ]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Некорректное действие']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
