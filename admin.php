<?php
declare(strict_types=1);
require_once __DIR__ . '/partials_header.php';
require_auth();
$user = current_user();
if ($user['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = (int)($_POST['id'] ?? 0);
    $redirectTab = $_POST['tab'] ?? 'dashboard';
    try {
        switch ($action) {
            case 'toggle_user_active':
                if ($id > 0) {
                    $checkAdmin = db()->prepare('SELECT role FROM users WHERE id = ?');
                    $checkAdmin->execute([$id]);
                    $uRole = $checkAdmin->fetchColumn();
                    if ($uRole !== 'admin') {
                        db()->prepare('UPDATE users SET active = 1 - active WHERE id = ?')->execute([$id]);
                    }
                }
                break;
            case 'toggle_vacancy_active':
                if ($id > 0) {
                    db()->prepare('UPDATE vacancies SET active = 1 - active WHERE id = ?')->execute([$id]);
                }
                break;
            case 'delete_user':
                if ($id > 0) {
                    $checkAdmin = db()->prepare('SELECT role FROM users WHERE id = ?');
                    $checkAdmin->execute([$id]);
                    $uRole = $checkAdmin->fetchColumn();
                    if ($uRole !== 'admin') {
                        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
                    }
                }
                break;
            case 'delete_vacancy':
                if ($id > 0) {
                    db()->prepare('DELETE FROM vacancies WHERE id = ?')->execute([$id]);
                }
                break;
            case 'delete_application':
                if ($id > 0) {
                    db()->prepare('DELETE FROM applications WHERE id = ?')->execute([$id]);
                }
                break;
            case 'delete_review':
                if ($id > 0) {
                    db()->prepare('DELETE FROM employer_reviews WHERE id = ?')->execute([$id]);
                }
                break;
            case 'edit_user':
                if ($id > 0) {
                    $fullName = trim((string)($_POST['full_name'] ?? ''));
                    $email = trim((string)($_POST['email'] ?? ''));
                    $role = (string)($_POST['role'] ?? 'applicant');
                    $companyName = trim((string)($_POST['company_name'] ?? ''));
                    if ($fullName && $email) {
                        $stmt = db()->prepare('UPDATE users SET full_name = ?, email = ?, role = ?, company_name = ? WHERE id = ?');
                        $stmt->execute([$fullName, $email, $role, $companyName, $id]);
                    }
                }
                break;
        }
    } catch (PDOException $e) {
    }
    header('Location: admin.php?tab=' . urlencode($redirectTab));
    exit;
}

// Stats
$stats = [
    'users' => (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'vacancies' => (int)db()->query('SELECT COUNT(*) FROM vacancies')->fetchColumn(),
    'applications' => (int)db()->query('SELECT COUNT(*) FROM applications')->fetchColumn(),
    'reviews' => (int)db()->query('SELECT COUNT(*) FROM employer_reviews')->fetchColumn(),
];

// Active tab
$tab = (string)($_GET['tab'] ?? 'dashboard');
$validTabs = ['dashboard', 'users', 'vacancies', 'applications', 'reviews'];
if (!in_array($tab, $validTabs, true)) {
    $tab = 'dashboard';
}

// Fetch data based on tab
$users = [];
$vacancies = [];
$applications = [];
$reviews = [];
$editUser = null;
if (isset($_GET['edit_user'])) {
    $editId = (int)$_GET['edit_user'];
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch();
}

if ($tab === 'users') {
    $users = db()->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
} elseif ($tab === 'vacancies') {
    $vacancies = db()->query('SELECT v.*, u.full_name AS employer_name FROM vacancies v JOIN users u ON u.id = v.employer_id ORDER BY v.created_at DESC')->fetchAll();
} elseif ($tab === 'applications') {
    $applications = db()->query('SELECT a.*, v.title AS vacancy_title, u1.full_name AS applicant_name, u2.full_name AS employer_name FROM applications a JOIN vacancies v ON v.id = a.vacancy_id JOIN users u1 ON u1.id = a.applicant_id JOIN users u2 ON u2.id = v.employer_id ORDER BY a.created_at DESC')->fetchAll();
} elseif ($tab === 'reviews') {
    $reviews = db()->query('SELECT er.*, u1.full_name AS applicant_name, u2.full_name AS employer_name FROM employer_reviews er JOIN users u1 ON u1.id = er.applicant_id JOIN users u2 ON u2.id = er.employer_id ORDER BY er.created_at DESC')->fetchAll();
}
?>
<div class="page-header">
    <h1>Админ-панель</h1>
</div>

<div class="dashboard-grid">
    <div class="card stat-card">
        <div class="stat-value"><?= $stats['users'] ?></div>
        <div class="stat-label">Пользователи</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value"><?= $stats['vacancies'] ?></div>
        <div class="stat-label">Вакансии</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value"><?= $stats['applications'] ?></div>
        <div class="stat-label">Отклики</div>
    </div>
    <div class="card stat-card">
        <div class="stat-value"><?= $stats['reviews'] ?></div>
        <div class="stat-label">Отзывы</div>
    </div>
</div>

<div class="card">
    <div class="tabs">
        <a href="admin.php?tab=dashboard" class="tab <?= $tab === 'dashboard' ? 'active' : '' ?>">Обзор</a>
        <a href="admin.php?tab=users" class="tab <?= $tab === 'users' ? 'active' : '' ?>">Пользователи</a>
        <a href="admin.php?tab=vacancies" class="tab <?= $tab === 'vacancies' ? 'active' : '' ?>">Вакансии</a>
        <a href="admin.php?tab=applications" class="tab <?= $tab === 'applications' ? 'active' : '' ?>">Отклики</a>
        <a href="admin.php?tab=reviews" class="tab <?= $tab === 'reviews' ? 'active' : '' ?>">Отзывы</a>
    </div>

    <?php if ($editUser): ?>
        <div style="padding:1rem;border-bottom:1px solid #e5e7eb;">
            <h3 style="margin-bottom:1rem;">Редактирование пользователя #<?= (int)$editUser['id'] ?></h3>
            <form method="post" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;max-width:800px;">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>">
                <input type="hidden" name="tab" value="users">
                <div class="form-group">
                    <label>ФИО</label>
                    <input type="text" name="full_name" value="<?= e($editUser['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= e($editUser['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Роль</label>
                    <select name="role">
                        <option value="applicant" <?= $editUser['role'] === 'applicant' ? 'selected' : '' ?>>Соискатель</option>
                        <option value="employer" <?= $editUser['role'] === 'employer' ? 'selected' : '' ?>>Работодатель</option>
                        <?php if ($editUser['role'] === 'admin'): ?>
                            <option value="admin" selected>Администратор</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Название компании (для работодателя)</label>
                    <input type="text" name="company_name" value="<?= e($editUser['company_name']) ?>">
                </div>
                <div style="grid-column:1/-1;display:flex;gap:0.5rem;">
                    <button type="submit" class="button"><i class="fa-solid fa-save"></i> Сохранить</button>
                    <a href="admin.php?tab=users" class="button button-outline">Отмена</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'dashboard'): ?>
        <div style="padding:1.5rem;">
            <h3 style="margin-bottom:1rem;">Добро пожаловать в панель администратора!</h3>
            <p>Здесь вы можете:</p>
            <ul style="list-style-type:disc;padding-left:1.5rem;line-height:1.8;">
                <li>Просматривать и управлять всеми пользователями</li>
                <li>Просматривать и модерировать вакансии</li>
                <li>Просматривать и удалять отклики</li>
                <li>Просматривать и модерировать отзывы</li>
            </ul>
        </div>
    <?php elseif ($tab === 'users'): ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th>Статус</th>
                        <th>Создан</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td><?= e($u['full_name']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="chip"><?= e($u['role']) ?></span></td>
                        <td>
                            <span class="chip" style="background-color:<?= (int)$u['active'] === 1 ? '#10b981' : '#ef4444' ?>;color:white;">
                                <?= (int)$u['active'] === 1 ? 'Активен' : 'Заблокирован' ?>
                            </span>
                        </td>
                        <td><?= e($u['created_at']) ?></td>
                        <td style="display:flex;gap:0.25rem;flex-wrap:wrap;">
                            <a href="admin.php?tab=users&edit_user=<?= (int)$u['id'] ?>" class="button button-sm button-outline"><i class="fa-solid fa-edit"></i></a>
                            <?php if ($u['role'] !== 'admin'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_user_active">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <input type="hidden" name="tab" value="users">
                                    <button type="submit" class="button button-sm" style="background:#f59e0b;color:white;">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                </form>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <input type="hidden" name="tab" value="users">
                                    <button type="submit" class="button button-sm" style="background:#dc2626;color:white;" onclick="return confirm('Удалить пользователя?');">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($tab === 'vacancies'): ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Работодатель</th>
                        <th>Город</th>
                        <th>Статус</th>
                        <th>Просмотры</th>
                        <th>Создан</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vacancies as $v): ?>
                    <tr>
                        <td><?= (int)$v['id'] ?></td>
                        <td><a href="vacancy_view.php?id=<?= (int)$v['id'] ?>" target="_blank"><?= e($v['title']) ?></a></td>
                        <td><?= e($v['employer_name']) ?></td>
                        <td><?= e($v['location']) ?></td>
                        <td>
                            <span class="chip" style="background-color:<?= (int)$v['active'] === 1 ? '#10b981' : '#ef4444' ?>;color:white;">
                                <?= (int)$v['active'] === 1 ? 'Активна' : 'Скрыта' ?>
                            </span>
                        </td>
                        <td><?= (int)$v['views_count'] ?></td>
                        <td><?= e($v['created_at']) ?></td>
                        <td style="display:flex;gap:0.25rem;flex-wrap:wrap;">
                            <a href="vacancy_edit.php?id=<?= (int)$v['id'] ?>" target="_blank" class="button button-sm button-outline"><i class="fa-solid fa-edit"></i></a>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_vacancy_active">
                                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                <input type="hidden" name="tab" value="vacancies">
                                <button type="submit" class="button button-sm" style="background:#f59e0b;color:white;">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="delete_vacancy">
                                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                <input type="hidden" name="tab" value="vacancies">
                                <button type="submit" class="button button-sm" style="background:#dc2626;color:white;" onclick="return confirm('Удалить вакансию?');">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($tab === 'applications'): ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Вакансия</th>
                        <th>Соискатель</th>
                        <th>Работодатель</th>
                        <th>Статус</th>
                        <th>Создан</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $a): ?>
                    <tr>
                        <td><?= (int)$a['id'] ?></td>
                        <td><?= e($a['vacancy_title']) ?></td>
                        <td><?= e($a['applicant_name']) ?></td>
                        <td><?= e($a['employer_name']) ?></td>
                        <td><span class="chip"><?= application_status_label($a['status']) ?></span></td>
                        <td><?= e($a['created_at']) ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="delete_application">
                                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                <input type="hidden" name="tab" value="applications">
                                <button type="submit" class="button button-sm" style="background:#dc2626;color:white;" onclick="return confirm('Удалить отклик?');">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($tab === 'reviews'): ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Соискатель</th>
                        <th>Работодатель</th>
                        <th>Оценка</th>
                        <th>Отзыв</th>
                        <th>Создан</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= e($r['applicant_name']) ?></td>
                        <td><a href="employer_view.php?id=<?= (int)$r['employer_id'] ?>" target="_blank"><?= e($r['employer_name']) ?></a></td>
                        <td><?= (int)$r['rating'] ?>/5 <span style="color:#fbbf24;"><?= str_repeat('★', (int)$r['rating']) ?><?= str_repeat('☆', 5 - (int)$r['rating']) ?></span></td>
                        <td style="max-width:300px;"><?= e(text_excerpt($r['comment'], 100)) ?></td>
                        <td><?= e($r['created_at']) ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="delete_review">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="tab" value="reviews">
                                <button type="submit" class="button button-sm" style="background:#dc2626;color:white;" onclick="return confirm('Удалить отзыв?');">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.tabs {
    display: flex;
    gap: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
    padding: 0.75rem 1rem;
    flex-wrap: wrap;
}
.tab {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    text-decoration: none;
    color: #6b7280;
    font-weight: 500;
}
.tab:hover {
    background: #f3f4f6;
}
.tab.active {
    background: #2563eb;
    color: white;
}
.table-container {
    overflow-x: auto;
    padding: 1rem;
}
.data-table {
    width: 100%;
    border-collapse: collapse;
}
.data-table th, .data-table td {
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}
.data-table th {
    background: #f9fafb;
    font-weight: 600;
}
.data-table tr:hover {
    background: #f9fafb;
}
.button-sm {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
}
</style>
<?php require_once __DIR__ . '/partials_footer.php'; ?>