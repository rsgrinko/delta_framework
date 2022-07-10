<?php
    @ignore_user_abort(true);
    set_time_limit(0);

    if (empty($_SERVER['DOCUMENT_ROOT'])) {
        $_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/..';
    }
    require_once __DIR__ . '/bootstrap.php';

    // Запускаем воркер диспетчера очереди
    exec('(php -f ' . __DIR__ . '/runtime/threadsWorker.php & ) >> /dev/null 2>&1');