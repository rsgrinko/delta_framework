<?php

    namespace Core\Helpers;


    /**
     * Класс вспомогательных методов
     */
    class SystemFunctions
    {
        /**
         * Получение IP адреса посетителя
         *
         * @return string
         */
        public static function getIP(): string
        {
            $keys = [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'REMOTE_ADDR'
            ];
            foreach ($keys as $key) {
                if (!empty($_SERVER[$key])) {
                    $ip = trim(end(explode(',', $_SERVER[$key])));
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
            return 'undefined';
        }

        /**
         * Преобразование байт в человеческий вид
         *
         * @param int $size Размер в байтах
         * @return string
         */
        public static function convertBytes(int $size): string
        {
            $i = 0;
            while (floor($size / 1024) > 0) {
                ++$i;
                $size /= 1024;
            }

            $size = str_replace('.', ',', round($size, 1));
            switch ($i) {
                case 0:
                    return $size .= ' байт';
                case 1:
                    return $size .= ' КБ';
                case 2:
                    return $size .= ' МБ';
            }
            return '';
        }


        /**
         * Получение ОС клиента
         *
         * @return string
         */
        public static function getOS(): string
        {
            $undefinedOS = 'Unknown OS';

            if(empty($_SERVER['HTTP_USER_AGENT'])) {
                return $undefinedOS;
            }

            $oses = [
                'iOS' => '/(iPhone)|(iPad)/i',
                'Windows 3.11' => '/Win16/i',
                'Windows 95' => '/(Windows 95)|(Win95)|(Windows_95)/i',
                'Windows 98' => '/(Windows 98)|(Win98)/i',
                'Windows 2000' => '/(Windows NT 5.0)|(Windows 2000)/i',
                'Windows XP' => '/(Windows NT 5.1)|(Windows XP)/i',
                'Windows 2003' => '/(Windows NT 5.2)/i',
                'Windows Vista' => '/(Windows NT 6.0)|(Windows Vista)/i',
                'Windows 7' => '/(Windows NT 6.1)|(Windows 7)/i',
                'Windows 8' => '/(Windows NT 6.2)|(Windows 8)/i',
                'Windows 8.1' => '/(Windows NT 6.3)|(Windows 8.1)/i',
                'Windows 10' => '/(Windows NT 10.0)|(Windows 10)/i',
                'Windows NT 4.0' => '/(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)/i',
                'Windows ME' => '/Windows ME/i',
                'Open BSD' => '/OpenBSD/i',
                'Sun OS' => '/SunOS/i',
                'Android' => '/Android/i',
                'Linux' => '/(Linux)|(X11)/i',
                'Macintosh' => '/(Mac_PowerPC)|(Macintosh)/i',
                'QNX' => '/QNX/i',
                'BeOS' => '/BeOS/i',
                'OS/2' => '/OS/2/i',
                'Search Bot' => '/(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp/cat)|(msnbot)|(ia_archiver)/i',
            ];

            foreach ($oses as $os => $pattern) {
                if (preg_match($pattern, $_SERVER['HTTP_USER_AGENT'])) {
                    return $os;
                }
            }
            return $undefinedOS;
        }

        /**
         * Генерация GUID
         */
        public static function generateGUID(): string
        {
            $uid = dechex(microtime(true) * 1000) . bin2hex(random_bytes(8));
            $guid = vsprintf('DLT%s-1000-%s-8%.3s-%s%s%s0', str_split($uid, 4));
            return strtoupper($guid);
        }

        /**
         * Генерация пароля
         *
         * @param int  $number          Количество символов в пароле
         * @param bool $useSpecialChars Использовать ли спецсимволы
         *
         * @return string
         */
        public static function generatePassword(int $number = 10, bool $useSpecialChars = true): string
        {
            $number = ($number > 0) ? $number : 1;

            $arChars = [
                'a', 'b', 'c', 'd', 'e', 'f',
                'g', 'h', 'i', 'j', 'k', 'l',
                'm', 'n', 'o', 'p', 'r', 's',
                't', 'u', 'v', 'x', 'y', 'z',
                'A', 'B', 'C', 'D', 'E', 'F',
                'G', 'H', 'I', 'J', 'K', 'L',
                'M', 'N', 'O', 'P', 'R', 'S',
                'T', 'U', 'V', 'X', 'Y', 'Z',
                '1', '2', '3', '4', '5', '6',
                '7', '8', '9', '0'];
            $arSpecialChars = [
                '(', ')', '[', ']', '!', '?',
                '&', '^', '%', '@', '*', '$',
                '<', '>', '/', '|', '+', '-',
                '{', '}', '~'];

            if ($useSpecialChars) {
                $arChars = array_merge($arChars, $arSpecialChars);
            }

            $pass = '';
            for ($i = 0; $i < $number; $i++) {
                $index = rand(0, count($arChars) - 1);
                $pass .= $arChars[$index];
            }
            return $pass;
        }


        /**
         * Проверка UserAgent на бота
         *
         * @param string|null $userAgent User agent
         *
         * @return string|null
         */
        public static function isSearchBot(?string $userAgent): ?string
        {
            if (empty($userAgent)) {
                return null;
            }

            $bots = [
                // Yandex
                'YandexBot', 'YandexAccessibilityBot', 'YandexMobileBot', 'YandexDirectDyn', 'YandexScreenshotBot',
                'YandexImages', 'YandexVideo', 'YandexVideoParser', 'YandexMedia', 'YandexBlogs', 'YandexFavicons',
                'YandexWebmaster', 'YandexPagechecker', 'YandexImageResizer', 'YandexAdNet', 'YandexDirect',
                'YaDirectFetcher', 'YandexCalendar', 'YandexSitelinks', 'YandexMetrika', 'YandexNews',
                'YandexNewslinks', 'YandexCatalog', 'YandexAntivirus', 'YandexMarket', 'YandexVertis',
                'YandexForDomain', 'YandexSpravBot', 'YandexSearchShop', 'YandexMedianaBot', 'YandexOntoDB',
                'YandexOntoDBAPI', 'YandexTurbo', 'YandexVerticals',

                // Google
                'Googlebot', 'Googlebot-Image', 'Mediapartners-Google', 'AdsBot-Google', 'APIs-Google',
                'AdsBot-Google-Mobile', 'AdsBot-Google-Mobile', 'Googlebot-News', 'Googlebot-Video',
                'AdsBot-Google-Mobile-Apps',

                // Other
                'Mail.RU_Bot', 'bingbot', 'Accoona', 'ia_archiver', 'Ask Jeeves', 'OmniExplorer_Bot', 'W3C_Validator',
                'WebAlta', 'YahooFeedSeeker', 'Yahoo!', 'Ezooms', 'Tourlentabot', 'MJ12bot', 'AhrefsBot',
                'SearchBot', 'SiteStatus', 'Nigma.ru', 'Baiduspider', 'Statsbot', 'SISTRIX', 'AcoonBot', 'findlinks',
                'proximic', 'OpenindexSpider', 'statdom.ru', 'Exabot', 'Spider', 'SeznamBot', 'oBot', 'C-T bot',
                'Updownerbot', 'Snoopy', 'heritrix', 'Yeti', 'DomainVader', 'DCPbot', 'PaperLiBot', 'StackRambler',
                'msnbot', 'msnbot-media', 'msnbot-news',
            ];

            foreach ($bots as $bot) {
                if (stripos($userAgent, $bot) !== false) {
                    return $bot;
                }
            }

            return null;
        }

        /**
         * Метод для склонения слов в зависимости от числа
         * Пример: numWord($secs, array('секунда', 'секунды', 'секунд'))
         *
         * @param int   $value Число
         * @param array $words Массив слов
         *
         * @return string
         */
        public static function numWord(int $value, array $words): string
        {
            $num = $value % 100;
            if ($num > 19) {
                $num = $num % 10;
            }

            switch ($num) {
                case 1:
                    $out = $words[0];
                    break;
                case 2:
                case 3:
                case 4:
                    $out = $words[1];
                    break;
                default:
                    $out = $words[2];
                    break;
            }

            return $out;
        }


        /**
         * Преобразование количества секунд в строку с разбиением на дни, часы, минуты и секунды
         *
         * @param int $secs Секунды
         *
         * @return string
         */
        public static function secToString(int $secs): string
        {
            $res = '';

            $days = floor($secs / 86400);
            $secs = $secs % 86400;
            if ($days > 0) {
                $res .= $days . ' ' . self::numWord($days, ['день', 'дня', 'дней']) . ', ';
            }

            $hours = floor($secs / 3600);
            $secs = $secs % 3600;
            if ($hours > 0) {
                $res .= $hours . ' ' . self::numWord($hours, ['час', 'часа', 'часов']) . ', ';
            }

            $minutes = floor($secs / 60);
            $secs = $secs % 60;
            if ($minutes > 0) {
                $res .= $minutes . ' ' . self::numWord($minutes, ['минута', 'минуты', 'минут']) . ', ';
            }

            $res .= $secs . ' ' . self::numWord($secs, ['секунда', 'секунды', 'секунд']);

            return $res;
        }

        /**
         * Удаление GET параметров из строки URL
         *
         * @param string|null $url URL
         *
         * @return string|string[]|null
         */
        public static function getBaseUrl(?string $url = null): ?string
        {
            return !empty($url) ? preg_replace('/^([^?]+)(\?.*?)?(#.*)?$/', '$1$3', $url) : null;
        }

        /**
         * Транслитерация строки
         *
         * @param string|null $text       Строка для преобразования
         * @param bool        $noUseSpace Использование режима "для ЧПУ"
         *
         * @return string|null
         */
        public static function transliterate(?string $text, bool $noUseSpace = false): ?string
        {
            if (empty($text)) {
                return null;
            }

            $converter = [
                'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
                'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
                'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
                'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
                'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
                'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '',
                'э' => 'e', 'ю' => 'yu', 'я' => 'ya',

                'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
                'Е' => 'E', 'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
                'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
                'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
                'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'Ch',
                'Ш' => 'Sh', 'Щ' => 'Sch', 'Ь' => '', 'Ы' => 'Y', 'Ъ' => '',
                'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
            ];

            $result = strtr($text, $converter);

            if ($noUseSpace) {
                $result = mb_strtolower($result);
                $result = mb_ereg_replace('[^-0-9a-z]', '-', $result);
                $result = mb_ereg_replace('[-]+', '-', $result);
                $result = trim($result, '-');
            }
            return $result;
        }

        /**
         * Отправка сообщения в канал нотификаций
         *
         * @param string $message Сообщение
         *
         * @return void
         */
        public static function sendTelegram(string $message): void
        {
            (new \Core\ExternalServices\TelegramSender(TELEGRAM_BOT_TOKEN))
                ->setChat(TELEGRAM_NOTIFICATION_CHANNEL)
                ->sendMessage($message);
        }

        /**
         * Построение структурной таблицы из массива
         *
         * @param array|string|null $var   Массив
         * @param string            $title Заголовок
         *
         * @return string|null
         */
        public static function arrayToTable($var, string $title = ''): ?string
        {
            $tableStyle = 'border: 1px solid #7b9ea3;border-collapse: collapse;';
            $tdBoldStyle = 'padding: 10px;border: 1px solid #7b9ea3;background: powderblue;font-weight: bold; text-align: center;color: #226770;text-shadow: #7b9ea3 0px 0px 2px;';
            $tdStyle = 'padding: 10px;border: 1px solid #7b9ea3;';
            $thStyle = 'text-align: center;font-size: 1.3em;padding: 10px;background: linear-gradient(0deg, #b0e0e6, #90c1c7);color: #226770;text-shadow: #7b9ea3 1px 1px 0px;';
            if (is_array($var)) {
                $table = '<table style="' . $tableStyle . '">';
                if ($title) {
                    $table .= '<tr><th colspan="20" style="' . $thStyle . '">' . $title . '</th></tr>';
                }
                foreach ($var as $k => $v) {
                    $table .= '<tr>';
                    $table .= '<td style="' . $tdBoldStyle . '">' . htmlspecialchars($k) . '</td>';
                    $table .= '<td style="' . $tdStyle . '">';
                    if (is_array($v)) {
                        $table .= self::arrayToTable($v);
                    } else {
                        $table .= '<pre>' . htmlspecialchars($v) . '</pre>';
                    }
                    $table .= '</td>';
                    $table .= '</tr>';
                }
                $table .= '</table>';
            } else {
                $table = $var;
            }
            return $table;
        }


        public static function showPage()
        {
            $loader = new \Twig\Loader\FilesystemLoader(PATH_TO_TEMPLATES);
            $twig = new \Twig\Environment($loader, [
                'cache' => CACHE_DIR,
            ]);

            echo $twig->render('index.tpl', ['name' => 'Roman']);

        }
    }