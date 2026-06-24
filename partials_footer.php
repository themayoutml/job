    </div></main>
<footer><div class="container footer-inner">
    <span class="footer-brand">JobHub</span>
    <div class="footer-links">
        <a href="index.php">Вакансии</a>
        <?php if (is_logged_in()): ?>
            <a href="dashboard.php">Кабинет</a><a href="messages.php">Сообщения</a>
        <?php else: ?>
            <a href="login.php?role=applicant">Соискатель</a><a href="login.php?role=employer">Работодатель</a>
        <?php endif; ?>
    </div>
    <span>&copy; <?= date('Y') ?> JobHub</span>
</div></footer>
</body></html>
