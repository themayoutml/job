<?php
declare(strict_types=1);

const SESSION_DIR = __DIR__ . '/sessions';
if (!is_dir(SESSION_DIR)) {
    mkdir(SESSION_DIR, 0755, true);
}
session_save_path(SESSION_DIR);
session_start();
session_destroy();
header('Location: index.php');
exit;
