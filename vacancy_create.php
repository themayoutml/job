<?php
declare(strict_types=1);
require_once __DIR__ . '/partials_header.php';
require_auth();
$user = current_user();
if (!in_array($user['role'], ['employer', 'admin'], true)) { header('Location: index.php'); exit; }

$errors = []; $vacancy = null; $mode = 'create'; $vacancyId = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string)$_POST['title']); $description = trim((string)$_POST['description']);
    $salary = trim((string)$_POST['salary']); $location = trim((string)$_POST['location']);
    $tags = trim((string)$_POST['tags']);
    if ($title === '' || $description === '' || $salary === '' || $location === '') $errors[] = 'Заполните все поля.';
    if (!$errors) {
        $stmt = db()->prepare('INSERT INTO vacancies (employer_id,title,description,salary,location,tags) VALUES (:e,:t,:d,:s,:l,:g)');
        $stmt->execute(['e'=>$user['id'],'t'=>$title,'d'=>$description,'s'=>$salary,'l'=>$location,'g'=>$tags]);
        header('Location: vacancy_view.php?id='.(int)db()->lastInsertId().'&published=1'); exit;
    }
}
require __DIR__ . '/partials_vacancy_form.php';
require_once __DIR__ . '/partials_footer.php';
