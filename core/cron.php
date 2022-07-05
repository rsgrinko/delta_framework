<?php
    @ignore_user_abort(true);

    require_once __DIR__ . '/bootstrap.php';

    use Core\Models\MQ;

    try {
        (new MQ())->run();
    } catch (Throwable $t) {
        echo 'Error: ' . $t->getMessage() . ' / line: ' . $t->getLine();
    }