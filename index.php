<?php

    use Core\CoreException;
    use Core\ExternalServices\Telegram;
    use Core\Models\User;
    use Core\Helpers\SystemFunctions;


    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';
    /*$userId = User::registration('test', '12345', 'support@it-stories.ru', 'user', 'Тестировщик', 'http://samag.ru/uploads/5222447.png');
    $user = new User($userId);*/

    $userId = User::getUserId();
    $user = new User($userId);
    User::authorize((int)$userId);
    echo $userId;

    echo '<br><br>';


    if(User::isUser()) {
        echo SystemFunctions::arrayToTable($user->getAllUserData(), 'Информация о пользователе');
    }
