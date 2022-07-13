<?php

    $_SERVER['DOCUMENT_ROOT'] = '/home/rsgrinko/sites/dev.it-stories.ru';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';
    print_r($_REQUEST);
    print_r($_SERVER);
    /*use Core\MQTasks;
    echo MQTasks::sendJokeToTelegram();*/
