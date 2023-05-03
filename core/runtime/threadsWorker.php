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

    /**
     * Воркер диспетчера очереди
     */
    @ignore_user_abort(true);
    set_time_limit(0);
    ini_set('memory_limit', '512M');

    require_once __DIR__ . '/init.php';

    use Core\Helpers\{SystemFunctions, Log};
    use Core\Models\MQ;

    $isChildrenThread = isset($argv[1]) && $argv[1] === 'children';
    $workerId         = isset($argv[2]) && !empty($argv[2]) ? $argv[2] : null;
    try {
        (new MQ($workerId))->run($isChildrenThread);
    } catch (Throwable $t) {
        Log::logToFile(
            'Произошла ошибка при обработке очереди: ' . $t->getMessage(),
            'threadsWorker.log',
            [
                'file'  => $t->getFile(),
                'line'  => $t->getLine(),
                'code'  => $t->getCode(),
                'trace' => $t->getTraceAsString(),
            ],
            LOG_ERR,
            null,
            false
        );
        SystemFunctions::sendTelegram(
            'ThreadsWorker: Произошла ошибка' . PHP_EOL . $t->getMessage() . PHP_EOL . 'File: ' . $t->getFile() . PHP_EOL . 'Line: ' . $t->getLine()
        );
    }