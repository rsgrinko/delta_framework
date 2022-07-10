<?php
    @ignore_user_abort(true);
    set_time_limit(0);

    if (empty($_SERVER['DOCUMENT_ROOT'])) {
        $_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/..';
    }
    require_once __DIR__ . '/bootstrap.php';

    use Core\Helpers\SystemFunctions;
    use Core\Models\MQ;

    try {
        (new MQ())->run();
    } catch (Throwable $t) {
        SystemFunctions::sendTelegram('CRON: Произошла ошибка' . PHP_EOL . $t->getMessage() . PHP_EOL . 'File: ' . $t->getFile() . PHP_EOL . 'Line: ' . $t->getLine());
        echo 'Error: ' . $t->getMessage() . ' / line: ' . $t->getLine();
    }