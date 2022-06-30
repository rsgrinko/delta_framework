<?php

    $_SERVER['DOCUMENT_ROOT'] = '/home/rsgrinko/sites/dev.it-stories.ru';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';
    use Core\ExternalServices\TelegramSender;
    use Core\Helpers\Log;
rfrf
    //Log::logToSentry('Тестовая ошибка', 'ERROR', $_REQUEST);

    die();
    $numProc = 4;

    if (empty($argv[1])) {
        $currentRunThread = (int)exec('ps -awx | grep \'test.php\' | grep -v \'grep\' | wc -l');
        echo 'Now running: ' . $currentRunThread . PHP_EOL;
        while ($currentRunThread <= $numProc) {
            echo 'Run new (' . $currentRunThread . ')' . PHP_EOL;
            $handler = '(php -f /home/rsgrinko/sites/dev.it-stories.ru/test.php ' . $currentRunThread . ' &) >> /dev/null 2>&1';
            passthru($handler);
            $currentRunThread = (int)exec('ps -awx | grep \'test.php\' | grep -v \'grep\' | wc -l');
            echo 'Now running: ' . $currentRunThread . PHP_EOL;
        }
    } else {
        $res = (new TelegramSender(TELEGRAM_BOT_TOKEN))->setChat(TELEGRAM_NOTIFICATION_CHANNEL)->sendMessage('Start proc #' . $argv[1]);
        Log::logToFile(json_encode($res, JSON_UNESCAPED_UNICODE), 'test.log', $argv[1]);
        sleep(rand(2, 10));
        $res = (new TelegramSender(TELEGRAM_BOT_TOKEN))->setChat(TELEGRAM_NOTIFICATION_CHANNEL)->sendMessage('End proc #' . $argv[1]);
        Log::logToFile(json_encode($res, JSON_UNESCAPED_UNICODE), 'test.log', $argv[1]);
    }

