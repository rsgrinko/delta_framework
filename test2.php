<?php
    $array = [
        'login'    => 'Nominal',
        'password' => 'j2medit'
    ];
    $cookieFile = $_SERVER['DOCUMENT_ROOT'] . '/uploads/cookie.txt';

    $ch = curl_init('https://visavi.net/login');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array, '', '&'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    $html = curl_exec($ch);
    curl_close($ch);

    unset($html);
