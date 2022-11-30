<?php

    require_once '../core/bootstrap.php';

    @ignore_user_abort(true); // игнорируем закрытие сессии клиентом
    set_time_limit(3600);     // лимит - час

    require_once $_SERVER['DOCUMENT_ROOT'] . '/backup/inc/CBackup.class.php';

    $CBackup = new CBackup('/home/rsgrinko/sites/it-stories.ru');
    $CBackup->setOutputFolder($_SERVER['DOCUMENT_ROOT'] . '/backup/backups');
    $CBackup->setIgnoreDirs(['backups', 'no_bkp']);
    $CBackup->useBackupDB(true);
    $CBackup->setDB('localhost', 'rsgrinko_wp', '2670135', 'rsgrinko_wp');
    $CBackup->setTTL(17);
    $CBackup->deleteOldBackups();

    $res = $CBackup->create();
    if (!$res) {
        echo 'Fail. Error: ' . $CBackup->getLastError();
        die();
    }

    echo 'Backup was created<br>';

    //echo '<pre>'.print_r($CBackup->getResult(), true).'</pre>';

    $arResult = $CBackup->getResult();

    $emailBody      = 'Скрипт создания бекапа закончил свою работу.' . PHP_EOL . 'Задание: it-stories.ru' . PHP_EOL . 'Файловый бекап ('
                      . $arResult['create']['files']['time'] . '):' . PHP_EOL . $arResult['create']['files']['path'] . PHP_EOL . PHP_EOL
                      . 'Бекап базы (' . $arResult['create']['db']['time'] . '):' . PHP_EOL . $arResult['create']['db']['path'] . PHP_EOL . PHP_EOL
                      . 'Обработка старых бекапов (' . $arResult['remove']['time'] . ')' . PHP_EOL . implode(PHP_EOL, $arResult['remove']['files']);
    $arMailFields   = [
        'MESSAGE' => nl2br($emailBody),
        'TITLE'   => 'Бекап завершен',
    ];
    $mail           = new Mail();
    $mail->dump     = false;
    $mail->dumpPath = '.';
    $mail->from('backup@cron.it-stories.ru', 'Система резервного копирования');
    $mail->to('rsgrinko@yandex.ru, rsgrinko@gmail.com', 'Роман Гринько');
    $mail->subject = 'Отчет о создании бекапа';
    $mail->assignTemplateVars($arMailFields);
    $mail->template    = 'scom';
    $mail->templateDir = $_SERVER['DOCUMENT_ROOT'] . '/core/mail_templates';
    $mail->send();


    /** @var DB $DB */
    $DB               = new DB('localhost', 'rsgrinko_wp', '2670135', 'rsgrinko_wp');
    $yandexOauthToken = $DB->query('SELECT * FROM its_yandex')[0]['accessToken'];
    if (empty($yandexOauthToken)) {
        $emailBody      = 'Токен авторизации не определен';
        $arMailFields   = [
            'MESSAGE' => nl2br($emailBody),
            'TITLE'   => 'Ошибка бекапа на Яндекс.Диск',
        ];
        $mail           = new Mail();
        $mail->dump     = false;
        $mail->dumpPath = '.';
        $mail->from('backup@cron.it-stories.ru', 'Система резервного копирования');
        $mail->to('rsgrinko@yandex.ru, rsgrinko@gmail.com', 'Роман Гринько');
        $mail->subject = 'Отчет о создании бекапа';
        $mail->assignTemplateVars($arMailFields);
        $mail->template    = 'scom';
        $mail->templateDir = $_SERVER['DOCUMENT_ROOT'] . '/core/mail_templates';
        $mail->send();
    }
    echo '<pre>' . print_r($yandexOauthToken, true) . '</pre>';

    $yandexDir = '/its_backup';

    $disk          = new Arhitector\Yandex\Disk($yandexOauthToken);
    $resourceFiles = $disk->getResource($yandexDir . '/' . basename($arResult['create']['files']['path']));
    $resourceDB    = $disk->getResource($yandexDir . '/' . basename($arResult['create']['db']['path']));

    $yandexFilePath = $yandexDBPath = '';

    $yandexStartTime = microtime(true);
    if (!$resourceFiles->has()) {
        $resourceFiles->upload($arResult['create']['files']['path']);
        $yandexFilePath = $resourceFiles->toArray()['path'];
    }

    if (!$resourceDB->has()) {
        $resourceDB->upload($arResult['create']['db']['path']);
        $yandexDBPath = $resourceDB->toArray()['path'];
    }


    $emailBody      = 'Бекап на Яндекс.Диск выполнен' . PHP_EOL . 'Время выполнения: ' . round(microtime(true) - $yandexStartTime, 4) . ' cek.'
                      . PHP_EOL . 'Расположения бекапов: ' . PHP_EOL . implode(PHP_EOL, [$yandexFilePath, $yandexDBPath]);
    $arMailFields   = [
        'MESSAGE' => nl2br($emailBody),
        'TITLE'   => 'Бекап на Яндекс.Диск выполнен',
    ];
    $mail           = new Mail();
    $mail->dump     = false;
    $mail->dumpPath = '.';
    $mail->from('backup@cron.it-stories.ru', 'Система резервного копирования');
    $mail->to('rsgrinko@yandex.ru, rsgrinko@gmail.com', 'Роман Гринько');
    $mail->subject = 'Отчет о создании бекапа';
    $mail->assignTemplateVars($arMailFields);
    $mail->template    = 'scom';
    $mail->templateDir = $_SERVER['DOCUMENT_ROOT'] . '/core/mail_templates';
    $mail->send();

    @unlink($arResult['create']['files']['path']);
    @unlink($arResult['create']['db']['path']);



