<?php

    function sendRequest(string $method, array $request = []): array
    {
        $ch = curl_init('https://api.telegram.org/bot1667667369:AAGv5Y1mLTndb1JEzwjzQ1yeelox2NRamR0/' . $method);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $res = json_decode($res, true);
        if (empty($res) || $res['ok'] !== true) {
            die($res['description'] . ' | ' . $res['error_code']);
        }
        return $res;
    }

    //sendRequest('deleteWebhook');
    $arUpdates = sendRequest('getUpdates?offset=');
    $result    = $arUpdates['result'];
    if(isset($result['update_id'])) {
        $result[] = $result;
    }
    $update_id = '';



    $res = [];
    foreach ($result as $element) {
        if(isset($element['message']['from']['id']) && !empty($element['message']['from']['id'])) {
            $res[] = sendRequest(
                'sendMessage',
                ['chat_id' => $element['message']['from']['id'], 'parse_mode' => 'html', 'text' => 'Ответ бота']
            );
        }
    }

    print_r($result);
