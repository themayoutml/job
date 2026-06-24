<?php
declare(strict_types=1);
/** @var array<int, string> $errors */
/** @var array<string, string>|null $vacancy */
/** @var string $mode */
/** @var int|null $vacancyId */
$isEdit = $mode === 'edit';
$values = [
    'title' => (string)($vacancy['title'] ?? $_POST['title'] ?? ''),
    'description' => (string)($vacancy['description'] ?? $_POST['description'] ?? ''),
    'salary' => (string)($vacancy['salary'] ?? $_POST['salary'] ?? ''),
    'location' => (string)($vacancy['location'] ?? $_POST['location'] ?? ''),
    'tags' => (string)($vacancy['tags'] ?? $_POST['tags'] ?? ''),
];
?>
<nav class="breadcrumbs"><a href="dashboard.php">Кабинет</a><span>/</span><span><?= $isEdit ? 'Редактирование' : 'Новая вакансия' ?></span></nav>

<div class="form-page-layout">
    <div class="card form-card">
        <div class="form-card-header">
            <div class="form-card-icon"><i class="fa-solid fa-<?= $isEdit ? 'pen-to-square' : 'plus' ?>"></i></div>
            <div>
                <h1><?= $isEdit ? 'Редактировать вакансию' : 'Опубликовать вакансию' ?></h1>
                <p>Укажите название, теги, условия и описание</p>
            </div>
        </div>
        <?php foreach ($errors as $error): ?><p class="error"><?= e($error) ?></p><?php endforeach; ?>
        <form method="post" class="vacancy-form">
            <section class="form-section">
                <h2 class="form-section-title"><i class="fa-solid fa-briefcase"></i> Основное</h2>
                <div class="form-group">
                    <label for="title">Название должности</label>
                    <div class="input-with-icon"><i class="fa-solid fa-user-tie"></i>
                        <input type="text" id="title" name="title" value="<?= e($values['title']) ?>" required></div>
                </div>
                <div class="form-group">
                    <label for="tags">Теги (через запятую)</label>
                    <div class="input-with-icon"><i class="fa-solid fa-tags"></i>
                        <input type="text" id="tags" name="tags" value="<?= e($values['tags']) ?>" placeholder="PHP, удалённо, junior"></div>
                </div>
            </section>
            <section class="form-section">
                <h2 class="form-section-title"><i class="fa-solid fa-coins"></i> Условия</h2>
                <div class="form-row">
                    <div class="form-group"><label for="salary">Зарплата</label>
                        <div class="input-with-icon"><i class="fa-solid fa-money-bill-wave"></i>
                            <input type="text" id="salary" name="salary" value="<?= e($values['salary']) ?>" required></div></div>
                    <div class="form-group"><label for="location">Город</label>
                        <div class="input-with-icon"><i class="fa-solid fa-location-dot"></i>
                            <input type="text" id="location" name="location" value="<?= e($values['location']) ?>" required></div></div>
                </div>
            </section>
            <section class="form-section">
                <h2 class="form-section-title"><i class="fa-solid fa-align-left"></i> Описание</h2>
                <div class="form-group">
                    <textarea id="description" name="description" rows="10" data-char-count required><?= e($values['description']) ?></textarea>
                    <div class="field-hint"><span>Подробное описание привлекает больше откликов</span><span class="char-counter" data-char-target="description">0 символов</span></div>
                </div>
            </section>
            <div class="form-footer">
                <button type="submit" class="button button-lg"><i class="fa-solid fa-check"></i> <?= $isEdit ? 'Сохранить' : 'Опубликовать' ?></button>
                <a class="button button-outline button-lg" href="<?= $isEdit ? 'vacancy_view.php?id='.(int)$vacancyId : 'dashboard.php' ?>">Отмена</a>
            </div>
        </form>
    </div>
    <aside class="form-aside">
        <div class="card aside-card">
            <h3><i class="fa-solid fa-lightbulb"></i> Советы</h3>
            <ul class="tips-list">
                <li>Добавьте теги: технологии, формат работы, уровень</li>
                <li>Укажите диапазон зарплаты</li>
                <li>Опишите задачи и требования списком</li>
            </ul>
        </div>
        <div class="card aside-card form-preview" id="vacancy-preview">
            <h3><i class="fa-solid fa-eye"></i> Превью</h3>
            <p class="preview-title" id="preview-title"><?= e($values['title'] ?: 'Название') ?></p>
            <div class="preview-chips">
                <span class="chip chip-accent" id="preview-salary"><?= e($values['salary'] ?: 'Зарплата') ?></span>
                <span class="chip" id="preview-location"><?= e($values['location'] ?: 'Город') ?></span>
            </div>
            <div class="preview-chips" id="preview-tags"><?= render_tags($values['tags']) ?></div>
            <p class="preview-desc" id="preview-desc"><?= e(text_excerpt($values['description'] ?: 'Описание...', 120)) ?></p>
        </div>
    </aside>
</div>
