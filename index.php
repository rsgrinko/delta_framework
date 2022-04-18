<?php
    use Core\ExternalServices\Telegram;
    use Core\Models\User;
    use Core\Helpers\SystemFunctions;


    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';

    User::authorize(2);
    $res = User::getFields();

    echo SystemFunctions::arrayToTable($res, 'Информация о пользователе');
