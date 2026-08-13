<?php

    declare(strict_types=1);

    use Delta\Config\Env;

    /**
     * Параметры интеграции с Telegram.
     *
     * Токен бота — секрет, задаётся в config.local.php либо переменной окружения.
     */
    return [
        'bot_username'         => Env::get('TELEGRAM_BOT_USERNAME', 'deltacore_bot'),
        'bot_token'            => Env::get('TELEGRAM_BOT_TOKEN', ''),
        'notification_channel' => Env::get('TELEGRAM_NOTIFICATION_CHANNEL', ''),
        'admin_chat_id'        => Env::get('TELEGRAM_ADMIN_CHAT_ID', ''),
    ];
