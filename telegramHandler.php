<?php

    use Core\CoreException;
    use Core\ExternalServices\Telegram2;

    require_once __DIR__ . '/core/bootstrap.php';

    $data = file_get_contents('php://input');
    if(empty($data)) {
        die('Данные не получены');
    }
    $data = json_decode($data, true);


    $tg = new Telegram2(TELEGRAM_BOT_TOKEN);
    $tg->setRemoteRequest($data);

    //$res = $tg->setChat(TELEGRAM_ADMIN_CHAT_ID)->updateMessage(121, '');


   sendTelegram(print_r($data, true));