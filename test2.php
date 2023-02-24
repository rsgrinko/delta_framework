<?php
    //use Core\CoreException;
    //use Core\ExternalServices\Telegram;
    //use Core\Models\{User, DB, Roles};
    //use Core\Helpers\{SystemFunctions, Cache, Log, Mail, Zip, Pagination, Files};

    //const USE_ROUTER = false;
    //require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';



    //echo getFilePreviewByLine(file_get_contents('./index.php'), 12);

    //echo SystemFunctions::findText('DB::getInstance()', './index.php');

    $arr = array(1, 2, 3, 4, 5, 6);
    foreach ($arr as &$value) {
        $value = $value * 3;
        echo $value.'
';
    }
