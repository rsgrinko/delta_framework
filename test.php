<?php

    $_SERVER['DOCUMENT_ROOT'] = '/home/rsgrinko/sites/dev.it-stories.ru';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';
    use Core\MQTasks;
    echo MQTasks::sendJokeToTelegram();
