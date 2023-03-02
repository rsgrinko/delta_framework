<?php
    use Core\Models\{DB};

    const USE_ROUTER = false;
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';

    $DB = DB::getInstance();
