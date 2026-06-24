(function () {
    const html = document.documentElement;
    const saved = localStorage.getItem('theme');
    if (saved === 'light' || saved === 'dark') {
        html.setAttribute('data-theme', saved);
    } else {
        html.setAttribute('data-theme', window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    }

    const toggle = document.getElementById('theme-toggle');
    function updateThemeIcon() {
        if (!toggle) return;
        const isDark = html.getAttribute('data-theme') === 'dark';
        toggle.innerHTML = isDark ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
        toggle.setAttribute('aria-label', isDark ? 'Светлая тема' : 'Тёмная тема');
    }
    if (toggle) {
        toggle.addEventListener('click', function () {
            const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcon();
        });
    }
    updateThemeIcon();

    const navToggle = document.getElementById('nav-toggle');
    const navShell = document.querySelector('.nav-shell');
    if (navToggle && navShell) {
        navToggle.addEventListener('click', function () {
            navShell.classList.toggle('open');
        });
        navShell.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () { navShell.classList.remove('open'); });
        });
    }

    const descriptionField = document.getElementById('description');
    const charCounter = document.querySelector('[data-char-target="description"]');
    if (descriptionField && charCounter) {
        const updateCount = function () {
            charCounter.textContent = descriptionField.value.length + ' символов';
        };
        descriptionField.addEventListener('input', updateCount);
        updateCount();
    }

    const previewMap = {
        title: document.getElementById('preview-title'),
        salary: document.getElementById('preview-salary'),
        location: document.getElementById('preview-location'),
        description: document.getElementById('preview-desc'),
        tags: document.getElementById('preview-tags'),
    };
    const fields = {
        title: document.getElementById('title'),
        salary: document.getElementById('salary'),
        location: document.getElementById('location'),
        description: descriptionField,
        tags: document.getElementById('tags'),
    };

    function updatePreview() {
        if (previewMap.title && fields.title) {
            previewMap.title.textContent = fields.title.value.trim() || 'Название';
        }
        if (previewMap.salary && fields.salary) {
            previewMap.salary.textContent = fields.salary.value.trim() || 'Зарплата';
        }
        if (previewMap.location && fields.location) {
            previewMap.location.textContent = fields.location.value.trim() || 'Город';
        }
        if (previewMap.description && fields.description) {
            const text = fields.description.value.trim();
            previewMap.description.textContent = text
                ? (text.length > 120 ? text.slice(0, 120).trim() + '…' : text)
                : 'Описание...';
        }
        if (previewMap.tags && fields.tags) {
            const tags = fields.tags.value.split(/[,;]+/).map(function (t) { return t.trim(); }).filter(Boolean);
            previewMap.tags.innerHTML = tags.map(function (t) {
                return '<span class="chip chip-tag">' + t.replace(/</g, '&lt;') + '</span>';
            }).join('');
        }
    }
    Object.keys(fields).forEach(function (key) {
        if (fields[key]) fields[key].addEventListener('input', updatePreview);
    });
    updatePreview();

    const copyBtn = document.getElementById('copy-link-btn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            const url = copyBtn.dataset.url || window.location.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url);
            } else {
                window.prompt('Ссылка:', url);
            }
        });
    }

    // Telegram авторизация
    const telegramLoginBtn = document.getElementById('telegram-login-btn');
    const telegramAuthStatus = document.getElementById('telegram-auth-status');
    const telegramStatusText = document.getElementById('telegram-status-text');
    const telegramCheckBtn = document.getElementById('telegram-check-btn');
    let currentAuthCode = null;
    let checkInterval = null;

    if (telegramLoginBtn) {
        telegramLoginBtn.addEventListener('click', async function () {
            try {
                console.log('Starting Telegram auth...');
                const response = await fetch('telegram_auth.php?action=generate_code');
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error('Server responded with ' + response.status);
                }
                
                const data = await response.json();
                console.log('Response data:', data);

                if (data.success) {
                    currentAuthCode = data.code;
                    telegramLoginBtn.style.display = 'none';
                    telegramAuthStatus.style.display = 'block';
                    telegramStatusText.innerHTML = 'Перейдите в бот по ссылке: <a href="' + data.bot_link + '" target="_blank">' + data.bot_link + '</a><br><small>После подтверждения нажмите "Проверить статус"</small>';

                    // Открываем бота в новом окне
                    window.open(data.bot_link, '_blank');

                    // Автоматически проверяем статус каждые 2 секунды
                    checkInterval = setInterval(checkAuthStatus, 2000);
                } else {
                    alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
                }
            } catch (e) {
                console.error('Telegram auth error:', e);
                // Запасной вариант: открываем страницу напрямую
                window.location.href = 'telegram_auth_direct.php';
            }
        });
    }

    if (telegramCheckBtn) {
        telegramCheckBtn.addEventListener('click', checkAuthStatus);
    }

    async function checkAuthStatus() {
        if (!currentAuthCode) return;

        try {
            const response = await fetch('telegram_auth.php?action=check_status&code=' + encodeURIComponent(currentAuthCode));
            const data = await response.json();

            if (data.success && data.authenticated) {
                if (checkInterval) clearInterval(checkInterval);
                window.location.href = 'dashboard.php';
            }
        } catch (e) {
            console.error('Ошибка проверки статуса', e);
        }
    }
})();
