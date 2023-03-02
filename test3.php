<?php
    use Core\Models\{DB};

    const USE_ROUTER = false;
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';

    $DB = DB::getInstance();

    use Workerman\Worker;

    $worker = new Worker('websocket://0.0.0.0:8282');
    $worker->onMessage = function($connection, $data)
    {
        $connection->send($data);
    };
    Worker::runAll();