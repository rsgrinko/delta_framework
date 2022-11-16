<?php

    /**
     * Copyright (c) 2022 Roman Grinko <rsgrinko@gmail.com>
     * Permission is hereby granted, free of charge, to any person obtaining
     * a copy of this software and associated documentation files (the
     * "Software"), to deal in the Software without restriction, including
     * without limitation the rights to use, copy, modify, merge, publish,
     * distribute, sublicense, and/or sell copies of the Software, and to
     * permit persons to whom the Software is furnished to do so, subject to
     * the following conditions:
     * The above copyright notice and this permission notice shall be included
     * in all copies or substantial portions of the Software.
     * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
     * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
     * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
     * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
     * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
     * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
     * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
     */
    @ignore_user_abort(true);
    set_time_limit(0);

    use Core\Helpers\Log;
    use Core\Models\MQ;

    if (empty($_SERVER['DOCUMENT_ROOT'])) {
        $_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/..';
    }
    require_once __DIR__ . '/bootstrap.php';

    // Запускаем воркер диспетчера очереди
    exec('(php -f ' . $_SERVER['DOCUMENT_ROOT'] . '/core/runtime/threadsWorker.php & ) >> /dev/null 2>&1');

    //$result = (new MQ())->setAttempts(3)->setPriority(1)->setCheckDuplicates(true)->createTask('Core\MQTasks', 'getMySLO');

    // Попытка получения валюты сайта за ежедневные авторизации
    $cookieFile = $_SERVER['DOCUMENT_ROOT'] . '/uploads/visaviCookie.txt';
    $logFileName = 'visavi.log';

    if (!file_exists($cookieFile) || time() - @filectime($cookieFile) > 60 * 60 * 23) {
        Log::logToFile('Время пришло - начинаем попытку авторизации', $logFileName);
        @unlink($cookieFile);
        Log::logToFile('Файл куков удален', $logFileName);

        $array = [
            'login'    => 'Nominal',
            'password' => 'j2medit',
        ];
        $ch = curl_init('https://visavi.net/login');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array, '', '&'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        unset($html);

        Log::logToFile('Запрос на авторизацию произведен. Код ответа: ' . $httpCode, $logFileName);
    }