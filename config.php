<?php
declare(strict_types=1);

// Включаем отображение ошибок для диагностики
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

const DB_PATH = __DIR__ . '/database.sqlite';
const UPLOAD_DIR = __DIR__ . '/uploads';
const SESSION_DIR = __DIR__ . '/sessions';

if (!defined('NO_SESSION')) {
    // Создаём директорию для сессий, если нет
    if (!is_dir(SESSION_DIR)) {
        mkdir(SESSION_DIR, 0755, true);
    }
    session_save_path(SESSION_DIR);
    session_start();
}
const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;
const ALLOWED_UPLOAD_EXT = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png'];

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5); // Таймаут блокировки 5 секунд
    return $pdo;
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare(
        'SELECT id, full_name, email, role, bio, phone, skills, desired_salary, position_title, education, company_name
         FROM users WHERE id = :id'
    );
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_auth(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function application_status_label(string $status): string
{
    switch ($status) {
        case 'viewed':
            return 'Просмотрен';
        case 'invited':
            return 'Приглашение';
        case 'rejected':
            return 'Отказ';
        default:
            return 'Новый';
    }
}

function application_status_class(string $status): string
{
    switch ($status) {
        case 'viewed':
            return 'status-viewed';
        case 'invited':
            return 'status-invited';
        case 'rejected':
            return 'status-rejected';
        default:
            return 'status-new';
    }
}

function count_new_applications_for_employer(int $employerId): int
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM applications a
         JOIN vacancies v ON v.id = a.vacancy_id
         WHERE v.employer_id = :id AND a.status = "new"'
    );
    $stmt->execute(['id' => $employerId]);
    return (int)$stmt->fetchColumn();
}

function count_unread_messages(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = :id AND is_read = 0');
    $stmt->execute(['id' => $userId]);
    return (int)$stmt->fetchColumn();
}

function text_excerpt(string $text, int $length = 150): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    if ($text === '' || mb_strlen($text) <= $length) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $length), '.,; ') . '…';
}

function format_relative_time(string $datetime): string
{
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return $datetime;
    }
    $diff = time() - $timestamp;
    if ($diff < 60) return 'только что';
    if ($diff < 3600) return (int)floor($diff / 60) . ' мин. назад';
    if ($diff < 86400) return (int)floor($diff / 3600) . ' ч. назад';
    if ($diff < 604800) return (int)floor($diff / 86400) . ' дн. назад';
    return date('d.m.Y', $timestamp);
}

function user_favorite_ids(int $userId): array
{
    $stmt = db()->prepare('SELECT vacancy_id FROM favorites WHERE user_id = :id');
    $stmt->execute(['id' => $userId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function is_favorite(int $userId, int $vacancyId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM favorites WHERE user_id = :user_id AND vacancy_id = :vacancy_id');
    $stmt->execute(['user_id' => $userId, 'vacancy_id' => $vacancyId]);
    return (bool)$stmt->fetchColumn();
}

function mark_messages_as_read(int $userId, int $contactId): void
{
    $stmt = db()->prepare(
        'UPDATE messages SET is_read = 1
         WHERE receiver_id = :user_id AND sender_id = :contact_id AND is_read = 0'
    );
    $stmt->execute(['user_id' => $userId, 'contact_id' => $contactId]);
}

function increment_vacancy_views(int $vacancyId): void
{
    $stmt = db()->prepare('UPDATE vacancies SET views_count = views_count + 1 WHERE id = :id');
    $stmt->execute(['id' => $vacancyId]);
}

function build_url(string $path, array $params = []): string
{
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null) {
            unset($params[$key]);
        }
    }
    return $path . ($params ? '?' . http_build_query($params) : '');
}

function parse_tags(string $tags): array
{
    if ($tags === '') {
        return [];
    }
    $parts = preg_split('/[,;]+/u', $tags) ?: [];
    return array_values(array_filter(array_map('trim', $parts)));
}

function render_tags(string $tags): string
{
    $html = '';
    foreach (parse_tags($tags) as $tag) {
        $html .= '<span class="chip chip-tag">' . e($tag) . '</span>';
    }
    return $html;
}

function can_rate_employer(int $applicantId, int $employerId): bool
{
    $stmt = db()->prepare(
        'SELECT 1 FROM applications a
         JOIN vacancies v ON v.id = a.vacancy_id
         WHERE a.applicant_id = :applicant_id AND v.employer_id = :employer_id AND a.status = "invited"
         LIMIT 1'
    );
    $stmt->execute(['applicant_id' => $applicantId, 'employer_id' => $employerId]);
    return (bool)$stmt->fetchColumn();
}

function has_reviewed_employer(int $applicantId, int $employerId): bool
{
    $stmt = db()->prepare(
        'SELECT 1 FROM employer_reviews WHERE applicant_id = :applicant_id AND employer_id = :employer_id'
    );
    $stmt->execute(['applicant_id' => $applicantId, 'employer_id' => $employerId]);
    return (bool)$stmt->fetchColumn();
}

function save_uploaded_file(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return null;
    }
    if (($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
        return null;
    }
    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_UPLOAD_EXT, true)) {
        return null;
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    $stored = bin2hex(random_bytes(12)) . '.' . $ext;
    $target = UPLOAD_DIR . '/' . $stored;
    if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
        return null;
    }
    return $stored;
}

function attachment_url(string $storedName): string
{
    return 'download.php?file=' . urlencode($storedName);
}
