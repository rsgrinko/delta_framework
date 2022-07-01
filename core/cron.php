<?php
    require_once __DIR__ . '/bootstrap.php';
    use Core\Models\MQ;

    (new MQ())->run();