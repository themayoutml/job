<?php
declare(strict_types=1);
require_once __DIR__ . '/partials_header.php';
require_auth();
$user = current_user();

$contactsStmt = db()->prepare(
    'SELECT u.id AS contact_id, u.full_name, MAX(m.created_at) AS last_message_at,
            SUM(CASE WHEN m.receiver_id = :me4 AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
     FROM users u JOIN messages m ON ((m.sender_id=:me AND m.receiver_id=u.id) OR (m.receiver_id=:me2 AND m.sender_id=u.id))
     WHERE u.id != :me3 GROUP BY u.id ORDER BY last_message_at DESC'
);
$contactsStmt->execute(['me'=>$user['id'],'me2'=>$user['id'],'me3'=>$user['id'],'me4'=>$user['id']]);
$contacts = $contactsStmt->fetchAll();
$selectedId = (int)($_GET['with'] ?? 0);
if ($selectedId <= 0 && $contacts) $selectedId = (int)$contacts[0]['contact_id'];
$thread = []; $selectedUser = null;
if ($selectedId > 0) {
    $su = db()->prepare('SELECT id, full_name FROM users WHERE id=:id');
    $su->execute(['id'=>$selectedId]); $selectedUser = $su->fetch();
    if ($selectedUser) {
        $ts = db()->prepare('SELECT m.*, s.full_name AS sender_name FROM messages m JOIN users s ON s.id=m.sender_id WHERE (m.sender_id=:me AND m.receiver_id=:c) OR (m.sender_id=:c2 AND m.receiver_id=:me2) ORDER BY m.created_at ASC');
        $ts->execute(['me'=>$user['id'],'c'=>$selectedId,'c2'=>$selectedId,'me2'=>$user['id']]);
        $thread = $ts->fetchAll();
        mark_messages_as_read((int)$user['id'], $selectedId);
    }
}
?>

<div class="page-header"><h1>Сообщения</h1><p>Можно прикреплять файлы (PDF, DOC, изображения до 5 МБ)</p></div>
<?php if (($_GET['status']??'')==='sent'): ?><p class="success">Сообщение отправлено.</p><?php endif; ?>

<div class="chat-layout">
    <div class="card contacts-card">
        <h3>Диалоги</h3>
        <?php foreach ($contacts as $c): ?>
            <a class="contact-item <?= (int)$c['contact_id']===$selectedId?'active':'' ?>" href="messages.php?with=<?= (int)$c['contact_id'] ?>">
                <span class="contact-name"><?= e($c['full_name']) ?><?php if ((int)$c['unread_count']>0): ?><span class="nav-badge"><?= (int)$c['unread_count'] ?></span><?php endif; ?></span>
                <span class="muted"><?= e(format_relative_time((string)$c['last_message_at'])) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <?php if ($selectedUser): ?>
    <div class="card chat-card">
        <h3><?= e($selectedUser['full_name']) ?></h3>
        <div class="chat-thread">
            <?php foreach ($thread as $m): ?>
                <div class="message <?= (int)$m['sender_id']===(int)$user['id']?'outgoing':'incoming' ?>">
                    <p><?= nl2br(e($m['body'])) ?></p>
                    <?php if ($m['attachment_name']): ?>
                        <p><a href="<?= e(attachment_url((string)$m['attachment_name'])) ?>"><i class="fa-solid fa-paperclip"></i> <?= e((string)$m['attachment_original']) ?></a></p>
                    <?php endif; ?>
                    <p class="muted"><?= e(format_relative_time((string)$m['created_at'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <form method="post" action="message_send.php" class="chat-form" enctype="multipart/form-data">
            <input type="hidden" name="receiver_id" value="<?= (int)$selectedUser['id'] ?>">
            <div class="chat-compose">
                <textarea name="body" rows="2" placeholder="Сообщение..."></textarea>
                <label class="file-upload-inline"><i class="fa-solid fa-paperclip"></i><input type="file" name="attachment" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png"></label>
            </div>
            <button type="submit"><i class="fa-solid fa-paper-plane"></i></button>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials_footer.php'; ?>
