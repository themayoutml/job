<?php
declare(strict_types=1);
require_once __DIR__ . '/partials_header.php';
require_auth();
$user = current_user();
$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM vacancies WHERE id = :id');
$stmt->execute(['id' => $id]);
$vacancy = $stmt->fetch();
if (!$vacancy || !($user['role'] === 'admin' || (int)$vacancy['employer_id'] === (int)$user['id'])) {
    header('Location: dashboard.php'); exit;
}
$errors = []; $mode = 'edit'; $vacancyId = $id;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string)$_POST['title']); $description = trim((string)$_POST['description']);
    $salary = trim((string)$_POST['salary']); $location = trim((string)$_POST['location']);
    $tags = trim((string)$_POST['tags']);
    if ($title === '' || $description === '' || $salary === '' || $location === '') $errors[] = 'Заполните все поля.';
    else {
        db()->prepare('UPDATE vacancies SET title=:t,description=:d,salary=:s,location=:l,tags=:g WHERE id=:id')
            ->execute(['t'=>$title,'d'=>$description,'s'=>$salary,'l'=>$location,'g'=>$tags,'id'=>$id]);
        header('Location: vacancy_view.php?id='.$id.'&updated=1'); exit;
    }
    $vacancy = array_merge($vacancy, compact('title','description','salary','location','tags'));
}
require __DIR__ . '/partials_vacancy_form.php';
require_once __DIR__ . '/partials_footer.php';
