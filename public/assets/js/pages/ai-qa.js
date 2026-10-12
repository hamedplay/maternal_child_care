document.addEventListener('DOMContentLoaded', function () {
    var chatBox     = document.getElementById('aiQaChat');
    var welcomeBox  = document.getElementById('aiQaWelcome');
    var messagesBox = document.getElementById('aiQaMessages');
    var form        = document.getElementById('aiQaForm');
    var input       = document.getElementById('aiQaInput');
    var submitBtn   = document.getElementById('aiQaSubmit');

    if (!chatBox || !messagesBox || !form || !input || !submitBtn) {
        return;
    }

    var ASSISTANT_AVATAR_SVG =
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" ' +
        'stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="8" width="16" height="12" rx="4" />' +
        '<path d="M12 8V4" /><circle cx="12" cy="3" r="1" /><circle cx="9" cy="14" r="1" />' +
        '<circle cx="15" cy="14" r="1" /><path d="M9 17h6" /></svg>';

    var USER_AVATAR_SVG =
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" ' +
        'stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />' +
        '<circle cx="12" cy="7" r="4" /></svg>';

    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /**
     * معادل جاوااسکریپتی aiqa_markdown_lite توی PHP.
     * فقط **بولد** و تیترهای ### رو تبدیل می‌کنه، بعد از escape کامل متن.
     */
    function markdownLiteToHtml(text) {
        var lines = escapeHtml(text).split('\n');
        var html = '';
        var prevWasText = false;

        lines.forEach(function (line) {
            var headingMatch = line.match(/^\s*#{1,6}\s*(.+)$/);

            if (headingMatch) {
                var headingText = headingMatch[1].replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
                html += '<div class="aiqa-md-heading">' + headingText + '</div>';
                prevWasText = false;
                return;
            }

            var formatted = line.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            if (prevWasText) {
                html += '<br>';
            }
            html += formatted;
            prevWasText = true;
        });

        return html;
    }

    function showChatView() {
        if (welcomeBox && !welcomeBox.hidden) {
            welcomeBox.hidden = true;
        }
        if (messagesBox.hidden) {
            messagesBox.hidden = false;
        }
    }

    function appendMessage(role, text, extraClass) {
        var wrapper = document.createElement('div');
        wrapper.className = 'aiqa-message ' + role + (extraClass ? ' ' + extraClass : '');

        if (role === 'assistant') {
            var avatar = document.createElement('span');
            avatar.className = 'aiqa-message-avatar';
            avatar.innerHTML = ASSISTANT_AVATAR_SVG;
            wrapper.appendChild(avatar);
        } else {
            var userAvatar = document.createElement('span');
            userAvatar.className = 'aiqa-message-avatar aiqa-message-avatar-user';
            userAvatar.innerHTML = USER_AVATAR_SVG;
            wrapper.appendChild(userAvatar);
        }

        var bubble = document.createElement('div');
        if (role === 'assistant') {
            bubble.className = 'bubble bubble-rich';
            bubble.innerHTML = markdownLiteToHtml(text);
        } else {
            bubble.className = 'bubble';
            bubble.textContent = text;
        }

        wrapper.appendChild(bubble);
        messagesBox.appendChild(wrapper);
        scrollToBottom();

        return wrapper;
    }

    function appendTypingIndicator() {
        var wrapper = document.createElement('div');
        wrapper.className = 'aiqa-message assistant typing';

        var avatar = document.createElement('span');
        avatar.className = 'aiqa-message-avatar';
        avatar.innerHTML = ASSISTANT_AVATAR_SVG;

        var bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.innerHTML =
            '<span class="aiqa-typing-dot"></span>' +
            '<span class="aiqa-typing-dot"></span>' +
            '<span class="aiqa-typing-dot"></span>';

        wrapper.appendChild(avatar);
        wrapper.appendChild(bubble);
        messagesBox.appendChild(wrapper);
        scrollToBottom();

        return wrapper;
    }

    function sendQuestion(question) {
        question = question.trim();
        if (!question) {
            return;
        }

        showChatView();
        appendMessage('user', question);
        input.value = '';
        input.style.height = 'auto';
        submitBtn.disabled = true;

        var typingEl = appendTypingIndicator();

        fetch('ai-qa-ask.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ question: question })
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                typingEl.remove();
                appendMessage('assistant', data.error ? data.error : data.answer);
            })
            .catch(function () {
                typingEl.remove();
                appendMessage('assistant', 'خطا در ارتباط با سرور. لطفاً دوباره تلاش کن.');
            })
            .finally(function () {
                submitBtn.disabled = false;
                input.focus();
            });
    }

    scrollToBottom();

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        sendQuestion(input.value);
    });

    // ارسال با Enter، خط جدید با Shift+Enter
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.requestSubmit();
        }
    });

    // بزرگ‌شدن خودکار textarea با تایپ کاربر
    input.addEventListener('input', function () {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    });

    // چیپ‌های پیشنهادی صفحه‌ی خوش‌آمدگویی: کلیک = پر کردن و ارسال خودکار
    document.querySelectorAll('.aiqa-quick-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            var prompt = chip.getAttribute('data-prompt');
            if (prompt) {
                sendQuestion(prompt);
            }
        });
    });

    // کارت‌های موضوعات سایدبار: کلیک = پر کردن ورودی و فوکوس (بدون ارسال خودکار)
    document.querySelectorAll('.aiqa-topic-card').forEach(function (card) {
        card.addEventListener('click', function () {
            var prompt = card.getAttribute('data-prompt');
            if (!prompt) {
                return;
            }
            input.value = prompt;
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';
            input.focus();
        });
    });
});