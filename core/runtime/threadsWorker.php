<?php

    /**
     * Воркер диспетчера очереди
     */
    @ignore_user_abort(true);
    set_time_limit(0);

    require_once __DIR__ . '/init.php';

    use Core\Helpers\SystemFunctions;
    use Core\Models\MQ;

    try {
        (new MQ())->run();
    } catch (Throwable $t) {
        SystemFunctions::sendTelegram(
            'ThreadsWorker: Произошла ошибка' . PHP_EOL . $t->getMessage() . PHP_EOL . 'File: ' . $t->getFile() . PHP_EOL . 'Line: ' . $t->getLine()
        );
    }