<?php
    use Core\Models\{DB};

    const USE_ROUTER = false;
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';

    $DB = DB::getInstance();

    $post_id = 123;

/*
    $res = $DB->query('SELECT `counter` AS `views` FROM `visits` WHERE `page_id` = ' . $post_id . ' AND `date` = CURDATE()')[0];
    echo $res['views'];*/


    $res = $DB->query('SELECT * FROM `visits` WHERE `page_id` = '.$post_id.' ORDER BY `date` limit 31') ?: [];


    $list = array();
    foreach ($res as $row) {
        $list[] = array('year' => date('d.m.Y', strtotime($row['date'])), 'value' => $row['counter']);
    }
?>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>
<?php if (empty($list)) { ?>
    <div>Статистика отсутствует</div>
<?php } else { ?>
    <div id="visits" style="height: 250px;"></div>

    <script>
        new Morris.Bar({
            element: 'visits',
            data: <?php echo json_encode($list); ?>,
            xkey: 'year',
            ykeys: ['value'],
            labels: ['Просмотры']
        });
    </script>
<?php }