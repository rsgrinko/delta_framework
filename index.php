<?php

    use Core\CoreException;
    use Core\ExternalServices\Telegram;
    use Core\Models\User;
    use Core\Helpers\{SystemFunctions, Cache};
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';

    /*$userId = User::registration('test', '12345', 'support@it-stories.ru', 'user', 'Тестировщик', 'http://samag.ru/uploads/5222447.png');
    $user = new User($userId);*/

    $userId = 2;//User::getUserId();
    try {
        $user = new User($userId);
        User::authorize((int)$userId);
        echo $userId;
    } catch (CoreException $e) {
        echo $e->showTrace();
    }

    echo '<br><br>';


    if(User::isUser()) {
        echo SystemFunctions::arrayToTable($user->getAllUserData(), 'Информация о пользователе');
    }


    echo SystemFunctions::arrayToTable(Cache::getCacheInfo(), 'Информация о кэше');

