<?php

    function getSolImages(?int $sol = null)
    {
        $result = [];
        $needGet = true;
        if ($sol === null) {
            $sol = 1;
        }
        $page = 1;

        while($needGet) {
            $res = file_get_contents('https://api.nasa.gov/mars-photos/api/v1/rovers/curiosity/photos?sol=' . $sol . '&page=' . $page . '&api_key=5qRQx4su8if3zVeweadnbegxcs6gxG9pgRtB1Tmu');
            $res = json_decode($res, true);
            if (isset($res['photos']) && !empty($res['photos'])) {
                foreach($res['photos'] as $arImage) {
                    $result[] = [
                            'date' => $arImage['earth_date'],
                            'src' => $arImage['img_src'],
                    ];
                }
                $page++;
            } else {
                $needGet = false;
            }
        }
        return $result;
    }



?>
<div style="display:flex; flex-wrap: wrap">
    <?php
        $sol = $_REQUEST['sol'] ?: 1;
        $res = getSolImages($sol);

        foreach ($res as $item) {
            echo '<div><img src="' . $item['src'] . '" width="300px"> </div>';
        }
    ?>
</div>
