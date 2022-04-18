<?php

    use Core\CoreException;
    use Core\ExternalServices\Telegram;
    use Core\Models\User;
    use Core\Helpers\{SystemFunctions, Cache, Log, Mail, Zip};

    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';

    Telegram::init('5232660453:AAGfMWu6EcRfBGSSURJsEEvGPmAqhCyzYHU', './');
    //$userId = User::registration('test', '12345', 'support@it-stories.ru', 'user', 'Тестировщик', 'http://samag.ru/uploads/5222447.png');



    /*$user = new User($userId);*/

    $userId = 2;//User::getUserId();
    try {
        $user = new User($userId);
        User::authorize((int)$userId);
        echo $userId;
    } catch (CoreException $e) {
        echo $e->showTrace();
    }
    die('stoped');

    echo '<br><br>';

    Zip::createArchive(LOG_PATH . '/', ROOT_PATH . '/core/cache/temp.zip');
    Telegram::sendDocument(412790359, ROOT_PATH . '/core/cache/temp.zip');
    unlink(ROOT_PATH . '/core/cache/temp.zip');

    if (User::isAuthorized()) {
        echo SystemFunctions::arrayToTable($user->getAllUserData(), 'Информация о пользователе');
    }


    echo SystemFunctions::arrayToTable(Cache::getCacheInfo(), 'Информация о кэше');

