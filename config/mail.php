<?php

    declare(strict_types=1);

    use Delta\Config\Env;

    /**
     * Параметры почтовых отправлений
     */

    $rootPath = Env::get('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__));

    return [
        'from_email'       => Env::get('SERVER_EMAIL', 'noreply@dev.it-stories.ru'),
        'from_name'        => Env::get('SERVER_EMAIL_NAME', 'Delta Framework'),
        'templates_path'   => Env::get('MAIL_TEMPLATES_PATH', $rootPath . '/core/mail_templates'),
        'template_default' => Env::get('MAIL_TEMPLATE_DEFAULT', 'default'),
    ];
