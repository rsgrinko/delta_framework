<?php

    use Core\CoreException;
    use Core\ExternalServices\Telegram;
    use Core\Models\User;
    use Core\Helpers\{SystemFunctions, Cache, Log, Mail, Zip};

    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';
    Log::logToFile('Скрипт начал свою работу', 'test.log');

    echo '<a href="/admin/">{{ADMIN_PANEL_LINK_NAME}}</a> | <a href="/?clear_cache=Y">{{CLEAR_CACHE_LINK_NAME}}</a> | <a href="/">{{REFRESH_PAGE_LINK_NAME}}</a> <br>';

    Telegram::init('5232660453:AAGfMWu6EcRfBGSSURJsEEvGPmAqhCyzYHU', './');
    //$userId = User::registration('test', '12345', 'support@it-stories.ru', 'user', 'Тестировщик', 'http://samag.ru/uploads/5222447.png');

    /*if (isset($_REQUEST['log']) && $_REQUEST['log'] === CODE_VALUE_Y) {
        Log::logToFile('Тестовое сообщение в лог', 'test.log', $_REQUEST);
    }*/

    /*$user = new User($userId);*/

    $userId = User::getCurrentUserId();
    Log::logToFile('Проверка пользователя на авторизованность', 'test.log', ['userId' => $userId]);
    try {
        $user = new User($userId);
        User::authorize((int)$userId);
        echo 'Current user id: ' . $userId . '<br>';
    } catch (CoreException $e) {
        Log::logToFile('CoreException: ' . $e->getMessage(), 'test.log', $_REQUEST);
        echo $e->showTrace();
    }
    echo '<br><br>';

    if (User::isAuthorized()) {
        Log::logToFile('Пользователь авторизован, выводим данные о нем', 'test.log', $user->getAllUserData());
        echo SystemFunctions::arrayToTable($user->getAllUserData(), 'Информация о пользователе');
    }


    echo SystemFunctions::arrayToTable(Cache::getCacheInfo(), 'Информация о кэше');


    Log::logToFile('Скрипт завершил свою работу', 'test.log');
