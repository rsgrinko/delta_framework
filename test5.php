<?php

    use Core\Models\DB;

    const USE_ROUTER = false;
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';

    $DB = DB::getInstance();


    $res = $DB->query('SELECT * FROM `bashorg` limit 20');


    foreach ($res as $item) {
        ?>
<div>
    <h1><?= $item['date_created'] ?></h1>
    <p><?= $item['text'] ?></p>
    <span>рейтинг: <?= $item['rating'] ?></span>
</div>
<?php

    }