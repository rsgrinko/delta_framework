<?php

    use Core\CoreException;
    use Core\ExternalServices\Telegram2;
    use Core\ExternalServices\ChatGPT;
    use Core\SystemConfig;

    require_once __DIR__ . '/core/bootstrap.php';

    $data = file_get_contents('php://input');
    if(empty($data)) {
        //die('Данные не получены');
    }
    $data = json_decode($data, true);


    $tg = new Telegram2(SystemConfig::getValue('TELEGRAM_BOT_TOKEN'));
    $tg->setRemoteRequest($data);

    $gptObject = new ChatGPT();

    $response = $gptObject->ask($tg->getMessage())['choices'][0]['message']['content'];
    $response = htmlspecialchars($response);
    $tg->sendMessage($response);
    //$res = $tg->setChat(TELEGRAM_ADMIN_CHAT_ID)->updateMessage(121, '');

