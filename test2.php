<?php
    //use Core\CoreException;
    //use Core\ExternalServices\Telegram;
    use Core\Models\{User, DB, Roles};
    //use Core\Helpers\{SystemFunctions, Cache, Log, Mail, Zip, Pagination, Files};

    const USE_ROUTER = false;
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';

    $DB = DB::getInstance();
    $res = $DB->query('SELECT * FROM podslyshano WHERE note <> "" ORDER BY RAND() LIMIT 10');
    //$res = $DB->query('SELECT * FROM podslyshano ORDER BY ID DESC LIMIT 1')[0];

    //echo '<pre>' . print_r($res, true). '</pre>';



    function num_word($value, $words, $show = true)
    {
        $num = $value % 100;
        if ($num > 19) {
            $num = $num % 10;
        }

        $out = ($show) ?  $value . ' ' : '';
        switch ($num) {
            case 1:  $out .= $words[0]; break;
            case 2:
            case 3:
            case 4:  $out .= $words[1]; break;
            default: $out .= $words[2]; break;
        }

        return $out;
    }

    function secToStr($secs)
    {
        $days = floor($secs / 86400);
        $secs %= 86400;
        if ($days > 0) {
            return num_word($days, ['день', 'дня', 'дней']);
        }

        $hours = floor($secs / 3600);
        $secs  %= 3600;
        if ($hours > 0) {
            return num_word($hours, ['час', 'часа', 'часов']);
        }

        $minutes = floor($secs / 60);
        $secs    %= 60;
        if ($minutes > 0) {
            return num_word($minutes, ['минута', 'минуты', 'минут']);
        }
        return num_word($secs, ['секунда', 'секунды', 'секунд']);
    }
?>
<meta name="viewport" content="width=device-width, initial-scale=1">
<div class="content">
<?php foreach($res as $post) { ?>
    <div class="post">
        <div class="id">#<?= $post['post_id'] ?></div>
        <div class="createdAt" title="<?= $post['created_at'] ?>"><?= secToStr(time() - strtotime($post['created_at'])) ?> назад</div>
        <div class="note"><?= stripslashes($post['note']) ?></div>
        <div class="slug"><?= $post['category_name'] ?></div>
        <div class="likes">❤ <?= $post['likes_count'] ?></div>
    </div>
<?php }
    $counter = $DB->query('SELECT count(id) as counter FROM podslyshano')[0]['counter'];
    ?>
    <div class="footer">
        <div class="dbInfo">В базе записей: <?= $counter ?></div>
    </div>

</div>




<style>
    .content {
        width: 600px;
        margin: 0 auto;
    }

    .post {
        border: 1px solid #e3e3e3;
        padding: 10px;
        box-shadow: 2px 2px 12px 0px #cfcfcf;
        background: #fffef8;
        margin-bottom: 20px;
    }

    .id {
        display: inline-block;
        color: #0063dd;
        font-weight: bolder;
    }

    .createdAt {
        display: inline-block;
        float: right;
        color: #a7a7a7;
        font-style: italic;
    }

    .slug {
        color: #0063dd;
        font-weight: bolder;
        display: inline-block;
    }

    .note {
        margin-top: 10px;
        margin-bottom: 10px;
    }

    .likes {
        float: right;
        display: inline-block;
    }

</style>