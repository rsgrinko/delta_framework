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

    namespace Core\Helpers;

    use Core\Models\User;
    use Core\CoreException;

    class Log
    {
        /**
         * Уровни логирования
         */
        private static $priorityList = [
            LOG_DEBUG   => 'DEBUG',
            LOG_INFO    => 'INFO',
            LOG_NOTICE  => 'NOTICE',
            LOG_WARNING => 'WARNING',
            LOG_ERR     => 'ERROR',
            LOG_CRIT    => 'CRITICAL',
            LOG_ALERT   => 'ALERT',
            LOG_EMERG   => 'EMERGENCY',
        ];

        /**
         * Логирование в файл
         *
         * @param string      $message  Сообщение
         * @param string      $filename Имя файла
         * @param mixed       $content  Дополнительные параметры в виде массива, объекта, строки и т.д.
         * @param int         $priority Код состояния
         * @param string|null $system   Название системы
         * @param bool        $addEnv   Флаг необходимости указания расширенных данных
         *
         * @return int             Количество сохранённых байт
         * @throws CoreException
         */
        public static function logToFile(
            string $message,
            string $filename = 'core.log',
            $content = null,
            int $priority = LOG_DEBUG,
            string $system = null,
            bool $addEnv = true
        ): int {
            if (!USE_LOG) {
                return 1;
            }
            $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
            $message     = str_replace(["\r\n", "\r", "\n"], PHP_EOL, trim($message));
            $logFile     = LOG_PATH . '/' . $filename;

            $logContent = '';
            if (!empty($content)) {
                if (!is_array($content) && !is_object($content)) {
                    $content = [$content];
                }
                $logContent = ' ' . json_encode($content, $jsonOptions);
                if (substr($logContent, 0, 4) == ' [{}') {
                    $logContent = ' #' . str_replace(PHP_EOL, '\n', serialize($content)) . '#';
                }
            }

            if (!array_key_exists($priority, self::$priorityList)) {
                $priority = LOG_DEBUG;
            }

            if (empty($system)) {
                $backtraceLimit = 2;
                $backtrace      = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $backtraceLimit);
                if (count($backtrace) < $backtraceLimit) {
                    $system = pathinfo($backtrace[0]['file'], PATHINFO_FILENAME);
                } else {
                    $system = $backtrace[$backtraceLimit - 1]['function'] ?: pathinfo($backtrace[$backtraceLimit - 1]['file'], PATHINFO_FILENAME);
                }
            }

            $logText    = date('[Y-m-d H:i:s] ') . $system . '.' . self::$priorityList[$priority] . ': ';
            $logTextLen = strlen($logText);
            $logText    .= str_replace(PHP_EOL, PHP_EOL . str_repeat(' ', $logTextLen), $message);

            $logTextPosEOL = strpos($logText, PHP_EOL);

            // Собираем информацию об окружении
            $logEnv = '';
            if ($addEnv) {
                $env = [
                    'ip' => SystemFunctions::getIP(),
                    'os' => SystemFunctions::getOS(),
                ];

                $userId = User::getCurrentUserId();
                if ($userId !== null) {
                    $objectUser       = new User($userId);
                    $env['userId']    = $objectUser->getId();
                    $env['userLogin'] = $objectUser->getLogin();
                }
                $env['memory'] = memory_get_usage(true);

                $_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? gethostname();
                $_SERVER['SERVER_ADDR'] = $_SERVER['SERVER_ADDR'] ?? gethostbyname($_SERVER['SERVER_NAME']);

                $envServerKeys = ['SERVER_ADDR', 'SERVER_NAME', 'HTTP_HOST', 'HTTP_REFERER'];
                foreach ($envServerKeys as $value) {
                    if (!empty($_SERVER[$value])) {
                        $env[strtolower($value)] = $_SERVER[$value];
                    }
                }
                $logEnv = ' ' . json_encode($env, $jsonOptions);
            }

            $logText .= $logContent;

            if ($logTextPosEOL === false) {
                $logText .= $logEnv;
            } else {
                $logText = preg_replace('/\n/', $logEnv . PHP_EOL, $logText, 1);
            }
            $result = @file_put_contents($logFile, $logText . PHP_EOL, FILE_APPEND | LOCK_EX);

            return $result ?: 0;
        }
    }