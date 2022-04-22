<?php

    use Core\CoreException;
    use Core\ExternalServices\Telegram;
    use Core\Models\User;
    use Core\Helpers\{SystemFunctions, Cache, Log, Mail, Zip};

    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';

    echo '<a href="/admin/">Админка</a> | <a href="/?clear_cache=Y">Очистить кэш</a> | <a href="/">Обновить страницу</a> <br>';

    Telegram::init('5232660453:AAGfMWu6EcRfBGSSURJsEEvGPmAqhCyzYHU', './');
    //$userId = User::registration('test', '12345', 'support@it-stories.ru', 'user', 'Тестировщик', 'http://samag.ru/uploads/5222447.png');

    if (isset($_REQUEST['log']) && $_REQUEST['log'] === CODE_VALUE_Y) {
        Log::logToFile('Тестовое сообщение в лог', 'test.log', $_REQUEST);
    }

    /*$user = new User($userId);*/

    $userId = User::getCurrentUserId();
    try {
        $user = new User($userId);
        User::authorize((int)$userId);
        echo 'Current user id: ' . $userId . '<br>';
    } catch (CoreException $e) {
        echo $e->showTrace();
    }
    echo '<br><br>';

    if (User::isAuthorized()) {
        echo SystemFunctions::arrayToTable($user->getAllUserData(), 'Информация о пользователе');
    }


    echo SystemFunctions::arrayToTable(Cache::getCacheInfo(), 'Информация о кэше');

