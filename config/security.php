<?php

    declare(strict_types=1);

    use Delta\Config\Env;

    /**
     * Параметры безопасности.
     *
     * Ключ шифрования — секрет, задаётся в config.local.php либо переменной окружения.
     */
    return [
        'crypto_key'          => Env::get('CRYPTO_KEY', ''),
        'use_captcha'         => (bool)Env::get('USE_CAPTCHA', false),
        'use_ddos_protection' => (bool)Env::get('USE_DDOS_PROTECTION', false),
    ];
