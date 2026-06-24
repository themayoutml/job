<?php
declare(strict_types=1);

// ВАЖНО: Отключаем сессии полностью для бота, чтобы не блокировать сайт
define('NO_SESSION', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/telegram_config.php';

// Функция для отправки запросов к Telegram API
function telegramRequest(string $method, array $data = []): array
{
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/' . $method;
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data),
        ],
    ];
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    return json_decode($response, true) ?: [];
}

// Функция для отправки сообщения
function sendMessage(int $chatId, string $text, array $keyboard = null): void
{
    $data = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown'];
    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard);
    }
    telegramRequest('sendMessage', $data);
}

// Обработчик команды /start
function handleStart(int $chatId, ?string $payload, array $from): void
{
    // Создаём новое соединение с БД каждый раз
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5);

    $telegramId = $from['id'];
    $username = $from['username'] ?? null;
    $firstName = $from['first_name'] ?? 'Пользователь';

    if ($payload && str_starts_with($payload, 'auth_')) {
        // Авторизация по коду
        $code = substr($payload, 5);
        $stmt = $pdo->prepare('SELECT * FROM telegram_auth_codes WHERE code = ? AND expires_at > datetime("now")');
        $stmt->execute([$code]);
        $authCode = $stmt->fetch();

        if ($authCode) {
            // Проверяем, есть ли пользователь с таким telegram_id
            $stmt = $pdo->prepare('SELECT * FROM users WHERE telegram_id = ?');
            $stmt->execute([$telegramId]);
            $user = $stmt->fetch();

            // Определяем роль из временного ID
            $role = 'applicant';
            if ($authCode['user_id'] == -2) {
                $role = 'employer';
            }

            if (!$user) {
                // Создаём нового пользователя или связываем с существующим
                if ($authCode['user_id'] > 0) {
                    // Связываем с существующим пользователем и меняем роль, если нужно
                    $stmt = $pdo->prepare('UPDATE users SET telegram_id = ?, telegram_username = ?, role = ? WHERE id = ?');
                    $stmt->execute([$telegramId, $username, $role, $authCode['user_id']]);
                } else {
                    // Создаём нового пользователя с выбранной ролью
                    $email = "tg_{$telegramId}@telegram.local";
                    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, telegram_id, telegram_username) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([
                        $firstName,
                        $email,
                        password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                        $role,
                        $telegramId,
                        $username
                    ]);
                }
            } else {
                // Пользователь уже есть — меняем роль на выбранную
                $stmt = $pdo->prepare('UPDATE users SET role = ?, telegram_username = ? WHERE id = ?');
                $stmt->execute([$role, $username, $user['id']]);
            }

            // Обновляем код авторизации, связываем с пользователем
            $stmt = $pdo->prepare('UPDATE telegram_auth_codes SET user_id = (SELECT id FROM users WHERE telegram_id = ?) WHERE code = ?');
            $stmt->execute([$telegramId, $code]);

            sendMessage($chatId, "✅ Авторизация успешна! Вернитесь на сайт и нажмите \"Проверить статус\".");
        } else {
            sendMessage($chatId, "❌ Код авторизации недействителен или истёк. Попробуйте снова.");
        }
    } else {
        // Обычный старт
        sendMessage($chatId, "👋 Привет, {$firstName}! Чтобы войти на сайт, перейдите по ссылке с кнопки \"Войти через Telegram\" на странице логина.");
    }

    // Закрываем соединение с БД
    $pdo = null;
}

// Обработчик входящих обновлений
function processUpdate(array $update): void
{
    if (isset($update['message'])) {
        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $from = $message['from'];
        $text = $message['text'] ?? '';

        if (str_starts_with($text, '/start')) {
            $payload = null;
            if (strpos($text, ' ') !== false) {
                $payload = explode(' ', $text, 2)[1];
            }
            handleStart($chatId, $payload, $from);
        }
    }
}

// Long polling
echo "🚀 Telegram бот запущен\n";
$offset = 0;

while (true) {
    $response = telegramRequest('getUpdates', [
        'offset' => $offset,
        'timeout' => 30
    ]);

    if (isset($response['result']) && is_array($response['result'])) {
        foreach ($response['result'] as $update) {
            $offset = $update['update_id'] + 1;
            processUpdate($update);
        }
    }

    sleep(1);
}
