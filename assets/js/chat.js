$(document).ready(function () {
    let $panel = $('#chatPanel');
    if ($panel.length === 0) {
        return;
    }

    let dialogId       = $panel.data('dialog-id'),
        $messageBox     = $('#messageBox'),
        $form           = $('#chatComposeForm'),
        $textarea       = $('#chatMessageInput'),
        $fileInput      = $('#chatFileInput'),
        $fileLabel      = $('#chatFileLabel'),
        $fileLabelDefault = $fileLabel.html(),
        $sendButton     = $('#chatSendButton'),
        $sendButtonDefault = $sendButton.html(),
        $companionStatus = $('#companionStatus'),
        $companionStatusText = $('#companionStatusText'),
        $messagesCountText = $('#messagesCountText'),
        pollTimer       = null;

    function isNearBottom() {
        return $messageBox[0].scrollHeight - $messageBox.scrollTop() - $messageBox.outerHeight() < 80;
    }

    function scrollToBottom() {
        $messageBox.scrollTop($messageBox[0].scrollHeight);
    }

    function resizeTextarea() {
        $textarea.css('height', 'auto');
        $textarea.css('height', Math.min($textarea[0].scrollHeight, 200) + 'px');
    }

    function applyDialogState(data) {
        $panel.attr('data-messages-count', data.messagesCount);
        $messagesCountText.text(data.messagesCount);
        $companionStatus.toggleClass('is-online', !!data.companionOnline);
        $companionStatusText.text(data.companionOnline ? 'Онлайн' : 'Не в сети');
    }

    // Первичный скролл к последнему сообщению
    scrollToBottom();

    // Автоувеличение textarea
    $textarea.on('input', resizeTextarea);
    resizeTextarea();

    // Отправка по Enter, перенос строки по Shift+Enter
    $textarea.on('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $form.trigger('submit');
        }
    });

    // Отправка формы через AJAX, без перезагрузки страницы
    $form.on('submit', function (e) {
        e.preventDefault();

        if ($sendButton.prop('disabled')) {
            return;
        }

        let hasMessage = $textarea.val().trim().length > 0,
            hasFile     = $fileInput[0].files.length > 0;

        if (!hasMessage && !hasFile) {
            return;
        }

        let formData = new FormData($form[0]);
        $sendButton.prop('disabled', true).html('Отправка...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            dataType: 'json'
        }).done(function (data) {
            if (data && data.success) {
                $messageBox.html(data.html);
                applyDialogState(data);
                scrollToBottom();
                $textarea.val('');
                resizeTextarea();
                $fileInput.val('');
                $fileLabel.html($fileLabelDefault);
            }
        }).always(function () {
            $sendButton.prop('disabled', false).html($sendButtonDefault);
            $textarea.trigger('focus');
        });
    });

    // Периодический опрос новых сообщений
    pollTimer = setInterval(function () {
        let currentCount = parseInt($panel.attr('data-messages-count'), 10);
        let wasNearBottom = isNearBottom();

        $.getJSON('/dialog/' + dialogId + '/messages').done(function (data) {
            if (!data || !data.success) {
                return;
            }
            applyDialogState(data);
            if (data.messagesCount !== currentCount) {
                $messageBox.html(data.html);
                if (wasNearBottom) {
                    scrollToBottom();
                }
            }
        });
    }, 6000);

    $(window).on('beforeunload', function () {
        if (pollTimer) {
            clearInterval(pollTimer);
        }
    });
});
