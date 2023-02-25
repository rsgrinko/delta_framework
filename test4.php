<?php

    use Core\Models\{DB};

    const USE_ROUTER = false;
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';
    // ID статьи
    $post_id = 123;

    $DB = DB::getInstance();
    /** @var DB $DB */
    $res = $DB->query('SELECT * FROM `visits` WHERE `page_id` = ' . $post_id . ' AND `date` = CURDATE()');

    if ($res === null) {
        echo 'insert';
        $res = $DB->query('INSERT INTO `visits` SET `page_id` = ' . $post_id . ', `counter` = 1, `date` = CURDATE()');
    } else {
        echo 'update';
        $res = $DB->query('UPDATE `visits` SET `counter` = `counter` + 1 WHERE `id` = ' . $res[0]['id']);
    }