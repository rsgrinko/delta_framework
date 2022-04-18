<?php
    /**
     * test inde file
     */

    use Delta\ExternalServices\Telegram;

    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/lib/Delta/ExternalServices/Telegram.class.php';
    Telegram::init('5232660453:AAGfMWu6EcRfBGSSURJsEEvGPmAqhCyzYHU', './');
    Telegram::execute(412790359, 'Hello from test site');