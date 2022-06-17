<?php

    //require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';
    if (php_sapi_name() === 'cli') {
        $cliMode = true;
    } else {
        $cliMode = false;
    }


    if ($cliMode) {
        echo 'run' . PHP_EOL;
        for ($i = 0; $i < 1024; $i++) {

        }
        echo 'stop' . PHP_EOL;
    } else {
        //$currentRunThread = (int)exec('ps -awx | grep \'test.php\' | grep -v \'grep\' | wc -l');
        //if ($currentRunThread < 4) {
        //echo 'run new (' . $currentRunThread . ')';
        $handler = 'php /home/rsgrinko/sites/dev.it-stories.ru/test.php cli &';
        exec($handler);
        //}
    }

