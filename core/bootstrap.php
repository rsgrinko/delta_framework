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

    use Core\CoreException;
    use Core\Helpers\Cache;
    use Core\Helpers\DDosProtection;
    use Core\Helpers\Log;
    use Core\Models\User;
    use Core\Models\UTM;
    use Core\SystemConfig;
    use Delta\Config\Config;
    use Delta\Error\ErrorHandler;
    use Delta\Http\Kernel;
    use Delta\Http\Request;
    use Delta\Routing\Router;

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../vendor/autoload.php';

    define('START_MEMORY', memory_get_usage());
    define('START_TIME', microtime(true)); // засекаем время старта скрипта
    const CORE_LOADED = true; // флаг корректного запуска

    if (empty($_SERVER['SERVER_NAME'])) {
        $_SERVER['SERVER_NAME'] = 'localhost';
    }

    if (empty($_SERVER['DOCUMENT_ROOT'])) {
        $_SERVER['DOCUMENT_ROOT'] = __DIR__. '/../';
    }

    // Если имеется файл локальной конфигурации - подключаем его.
    // Он идёт первым: заданные в нём константы имеют приоритет над всем остальным.
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/config.local.php')) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/config.local.php';
    }

    // Собираем конфигурацию из каталога config/
    Config::boot(__DIR__ . '/../config');

    // Публикуем значения конфигурации в виде legacy-констант
    require_once __DIR__ . '/config.php';

    // Режим вывода ошибок и часовой пояс определяются окружением
    if (DEBUG) {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    } else {
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
        ini_set('display_errors', '0');
    }
    date_default_timezone_set(Config::getInstance()->get('app.timezone'));

    /**
     * Центральный обработчик ошибок.
     * Регистрируется сразу после конфигурации, чтобы любая последующая ошибка попадала
     * в журнал приложения, а не зависела от display_errors в php.ini.
     */
    $errorHandler = (new ErrorHandler(
        DEBUG,
        static function (string $message, array $context): void {
            Log::logToFile($message, 'error.log', $context, LOG_ERR, 'core');
        },
    ))->register();

    $isCronProcess = false;
    if (defined('IS_CRON_PROCESS') && IS_CRON_PROCESS === true) {
        $isCronProcess = true;
    }
    define('CORE_FULL_LOAD', !$isCronProcess);

    /**
     * Отладочная функция: вывести данные
     * @param mixed $data Данные
     *
     * @return void
     */
    function dd($data): void
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }

    /**
     * Отладочная функция: вывести данные и завершить работу скрипта
     * @param mixed $data Данные
     *
     * @return void
     */
    function ddd($data): void
    {
        echo '<pre>' . print_r($data, true) . '</pre>';
        die();
    }

    // Инициализация кеша
    Cache::init(CACHE_DIR, USE_CACHE);

    if (CORE_FULL_LOAD) {
        // Обработка UTM меток
        (new UTM())->save();

        // очистка кэша
        if (isset($_REQUEST['clear_cache']) && $_REQUEST['clear_cache'] === CODE_VALUE_Y) {
            Cache::flush();
        }

        // выход из системы
        if (isset($_REQUEST['logout']) && $_REQUEST['logout'] === CODE_VALUE_Y) {
            User::logout();
        }

        $ddosProtectObject = new DDosProtection(basename(__FILE__));
        try {
            $userId = User::getCurrentUserId();
        } catch (CoreException $e) {
            $userId = null;
        }
        if (!empty($userId)) {
            try {
                $USER = new User($userId);
                $ddosProtectObject->setUserId($userId);
            } catch (CoreException $e) {
                $USER = null;
            }
        } else {
            $USER = null;
        }
        $ddosProtectObject->checkDDos();
    }

    // debug
    function sendTelegram(?string $message, ?string $file = null): void
    {
        (new \Core\ExternalServices\TelegramSender(SystemConfig::getValue('TELEGRAM_BOT_TOKEN')))->setChat(SystemConfig::getValue('TELEGRAM_NOTIFICATION_CHANNEL'))->sendMessage($message);

        if (!empty($file)) {
            (new \Core\ExternalServices\TelegramSender(SystemConfig::getValue('TELEGRAM_BOT_TOKEN')))->setChat(SystemConfig::getValue('TELEGRAM_NOTIFICATION_CHANNEL'))->sendDocument($file);
        }
    }

    //end debug

    /**
     * Запускаем ядро обработки запроса, если не сказано иного
     */
    if (CORE_FULL_LOAD && defined('USE_ROUTER') && USE_ROUTER === true) {
        /**
         * Инициализация шаблонизатора
         */
        $loader = new \Twig\Loader\FilesystemLoader(PATH_TO_TEMPLATES);
        $twig   = new \Twig\Environment($loader, [
            'debug' => DEBUG,
            'cache' => DEBUG ? false : CACHE_DIR . '/twig',
        ]);
        if (DEBUG) {
            $twig->addExtension(new \Twig\Extension\DebugExtension());
        }

        $router = new Router();
        require __DIR__ . '/routes.php';

        (new Kernel($router, $errorHandler))->handle(Request::capture())->send();
        die();
    }
