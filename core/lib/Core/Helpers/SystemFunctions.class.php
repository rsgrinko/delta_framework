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


    use Core\CoreException;
    use Core\DTO\System\Memory;
    use Core\Models\Roles;
    use Core\Models\User;
    use Core\SystemConfig;

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
                'REMOTE_ADDR',
            ];
            foreach ($keys as $key) {
                if (!empty($_SERVER[$key])) {
                    $arData = explode(',', $_SERVER[$key]);
                    $ip     = trim(end($arData));
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
         *
         * @return string
         */
        public static function convertBytes(int $size): string
        {
            $i = 0;
            while (floor($size / 1024) > 0) {
                ++$i;
                $size /= 1024;
            }

            $size = str_replace('.', ',', round($size, 2));
            switch ($i) {
                case 0:
                    return $size .= ' b';
                case 1:
                    return $size .= ' Kb';
                case 2:
                    return $size .= ' Mb';
                case 3:
                    return $size .= ' Gb';
                case 4:
                    return $size .= 'Tb';
            }
            return 'Неизвестно';
        }


        /**
         * Получение ОС клиента
         *
         * @return string
         */
        public static function getOS(): string
        {
            $undefinedOS = 'Unknown OS';

            if (empty($_SERVER['HTTP_USER_AGENT'])) {
                return $undefinedOS;
            }

            $oses = [
                'Postman'            => '/PostmanRuntime/i',
                'iOS'            => '/(iPhone)|(iPad)/i',
                'Windows 3.11'   => '/Win16/i',
                'Windows 95'     => '/(Windows 95)|(Win95)|(Windows_95)/i',
                'Windows 98'     => '/(Windows 98)|(Win98)/i',
                'Windows 2000'   => '/(Windows NT 5.0)|(Windows 2000)/i',
                'Windows XP'     => '/(Windows NT 5.1)|(Windows XP)/i',
                'Windows 2003'   => '/(Windows NT 5.2)/i',
                'Windows Vista'  => '/(Windows NT 6.0)|(Windows Vista)/i',
                'Windows 7'      => '/(Windows NT 6.1)|(Windows 7)/i',
                'Windows 8'      => '/(Windows NT 6.2)|(Windows 8)/i',
                'Windows 8.1'    => '/(Windows NT 6.3)|(Windows 8.1)/i',
                'Windows 10'     => '/(Windows NT 10.0)|(Windows 10)/i',
                'Windows NT 4.0' => '/(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)/i',
                'Windows ME'     => '/Windows ME/i',
                'Open BSD'       => '/OpenBSD/i',
                'Sun OS'         => '/SunOS/i',
                'Android'        => '/Android/i',
                'Linux'          => '/(Linux)|(X11)/i',
                'Macintosh'      => '/(Mac_PowerPC)|(Macintosh)/i',
                'QNX'            => '/QNX/i',
                'BeOS'           => '/BeOS/i',
                'OS/2'           => '/OS/2/i',
                'Search Bot'     => '/(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp/cat)|(msnbot)|(ia_archiver)/i',
            ];

            foreach ($oses as $os => $pattern) {
                if (@preg_match($pattern, $_SERVER['HTTP_USER_AGENT'])) {
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
            $uid  = dechex(microtime(true) * 1000) . bin2hex(random_bytes(8));
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
            $number   = ($number > 0) ? $number : 1;
            $arChars  = [
                          'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j',
                          'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u',
                          'v', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F',
                          'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
                          'R', 'S', 'T', 'U', 'V', 'X', 'Y', 'Z', '1', '2',
                          '3', '4', '5', '6', '7', '8', '9', '0',
            ];
            $arSpecialChars = [
                          '(', ')', '[', ']', '!', '?', '&', '^', '%', '@',
                          '*', '$', '<', '>', '/', '|', '+', '-', '{', '}',
                          '~',
            ];

            if ($useSpecialChars) {
                $arChars = array_merge($arChars, $arSpecialChars);
            }

            $password = '';
            for ($i = 0; $i < $number; $i++) {
                $index = rand(0, count($arChars) - 1);
                $password  .= $arChars[$index];
            }
            return $password;
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
                'YandexBot',
                'YandexAccessibilityBot',
                'YandexMobileBot',
                'YandexDirectDyn',
                'YandexScreenshotBot',
                'YandexImages',
                'YandexVideo',
                'YandexVideoParser',
                'YandexMedia',
                'YandexBlogs',
                'YandexFavicons',
                'YandexWebmaster',
                'YandexPagechecker',
                'YandexImageResizer',
                'YandexAdNet',
                'YandexDirect',
                'YaDirectFetcher',
                'YandexCalendar',
                'YandexSitelinks',
                'YandexMetrika',
                'YandexNews',
                'YandexNewslinks',
                'YandexCatalog',
                'YandexAntivirus',
                'YandexMarket',
                'YandexVertis',
                'YandexForDomain',
                'YandexSpravBot',
                'YandexSearchShop',
                'YandexMedianaBot',
                'YandexOntoDB',
                'YandexOntoDBAPI',
                'YandexTurbo',
                'YandexVerticals',

                // Google
                'Googlebot',
                'Googlebot-Image',
                'Mediapartners-Google',
                'AdsBot-Google',
                'APIs-Google',
                'AdsBot-Google-Mobile',
                'AdsBot-Google-Mobile',
                'Googlebot-News',
                'Googlebot-Video',
                'AdsBot-Google-Mobile-Apps',

                // Other
                'Mail.RU_Bot',
                'bingbot',
                'Accoona',
                'ia_archiver',
                'Ask Jeeves',
                'OmniExplorer_Bot',
                'W3C_Validator',
                'WebAlta',
                'YahooFeedSeeker',
                'Yahoo!',
                'Ezooms',
                'Tourlentabot',
                'MJ12bot',
                'AhrefsBot',
                'SearchBot',
                'SiteStatus',
                'Nigma.ru',
                'Baiduspider',
                'Statsbot',
                'SISTRIX',
                'AcoonBot',
                'findlinks',
                'proximic',
                'OpenindexSpider',
                'statdom.ru',
                'Exabot',
                'Spider',
                'SeznamBot',
                'oBot',
                'C-T bot',
                'Updownerbot',
                'Snoopy',
                'heritrix',
                'Yeti',
                'DomainVader',
                'DCPbot',
                'PaperLiBot',
                'StackRambler',
                'msnbot',
                'msnbot-media',
                'msnbot-news',
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
         * Пример: numWord($secs, ['секунда', 'секунды', 'секунд'])
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
                $num %= 10;
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
            $secs %= 86400;
            if ($days > 0) {
                $res .= $days . ' ' . self::numWord($days, ['день', 'дня', 'дней']) . ', ';
            }

            $hours = floor($secs / 3600);
            $secs  %= 3600;
            if ($hours > 0) {
                $res .= $hours . ' ' . self::numWord($hours, ['час', 'часа', 'часов']) . ', ';
            }

            $minutes = floor($secs / 60);
            $secs    %= 60;
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
                'а' => 'a',
                'б' => 'b',
                'в' => 'v',
                'г' => 'g',
                'д' => 'd',
                'е' => 'e',
                'ё' => 'e',
                'ж' => 'zh',
                'з' => 'z',
                'и' => 'i',
                'й' => 'y',
                'к' => 'k',
                'л' => 'l',
                'м' => 'm',
                'н' => 'n',
                'о' => 'o',
                'п' => 'p',
                'р' => 'r',
                'с' => 's',
                'т' => 't',
                'у' => 'u',
                'ф' => 'f',
                'х' => 'h',
                'ц' => 'c',
                'ч' => 'ch',
                'ш' => 'sh',
                'щ' => 'sch',
                'ь' => '',
                'ы' => 'y',
                'ъ' => '',
                'э' => 'e',
                'ю' => 'yu',
                'я' => 'ya',
                'А' => 'A',
                'Б' => 'B',
                'В' => 'V',
                'Г' => 'G',
                'Д' => 'D',
                'Е' => 'E',
                'Ё' => 'E',
                'Ж' => 'Zh',
                'З' => 'Z',
                'И' => 'I',
                'Й' => 'Y',
                'К' => 'K',
                'Л' => 'L',
                'М' => 'M',
                'Н' => 'N',
                'О' => 'O',
                'П' => 'P',
                'Р' => 'R',
                'С' => 'S',
                'Т' => 'T',
                'У' => 'U',
                'Ф' => 'F',
                'Х' => 'H',
                'Ц' => 'C',
                'Ч' => 'Ch',
                'Ш' => 'Sh',
                'Щ' => 'Sch',
                'Ь' => '',
                'Ы' => 'Y',
                'Ъ' => '',
                'Э' => 'E',
                'Ю' => 'Yu',
                'Я' => 'Ya',
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
            (new \Core\ExternalServices\TelegramSender(SystemConfig::getValue('TELEGRAM_BOT_TOKEN')))
                ->setChat(SystemConfig::getValue('TELEGRAM_NOTIFICATION_CHANNEL'))
                ->sendMessage($message);
        }

        /**
         * Построение структурной таблицы из массива
         *
         * @param array|string|null $var   Массив
         * @param string            $title Заголовок
         *                                 
         *
         * @return string|null
         */
        public static function arrayToTable($var, string $title = ''): ?string
        {
            $tableStyle  = 'border: 1px solid #7b9ea3;border-collapse: collapse;';
            $tdBoldStyle = 'padding: 10px;border: 1px solid #7b9ea3;background: powderblue;font-weight: bold; text-align: center;color: #226770;text-shadow: #7b9ea3 0px 0px 2px;';
            $tdStyle     = 'padding: 10px;border: 1px solid #7b9ea3;';
            $thStyle     = 'text-align: center;font-size: 1.3em;padding: 10px;background: linear-gradient(0deg, #b0e0e6, #90c1c7);color: #226770;text-shadow: #7b9ea3 1px 1px 0px;';
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

        /**
         * Анонс из теста
         *
         * @param string $text  Текст
         * @param int    $limit Ограничение на количество символов
         *
         * @return string
         */
        public static function previewText(string $text, int $limit = 300): string
        {
            $text = stripslashes($text);
            $text = htmlspecialchars_decode($text, ENT_QUOTES);
            $text = str_ireplace(['<br>', '<br />', '<br/>'], ' ', $text);
            $text = strip_tags($text);
            $text = trim($text);

            if (mb_strlen($text) < $limit) {
                return $text;
            } else {
                $text   = mb_substr($text, 0, $limit);
                $length = mb_strripos($text, ' ');
                $end    = mb_substr($text, $length - 1, 1);

                if (empty($length)) {
                    return $text;
                } elseif (in_array($end, ['.', '!', '?'])) {
                    return mb_substr($text, 0, $length);
                } elseif (in_array($end, [',', ':', ';', '«', '»', '…', '(', ')', '—', '–', '-'])) {
                    return trim(mb_substr($text, 0, $length - 1)) . '...';
                } else {
                    return trim(mb_substr($text, 0, $length)) . '...';
                }

                return trim($text);
            }
        }


        /**
         * Хелпер для метода преобразования массива в XML
         *
         * @param array             $data     Массив данных
         * @param \SimpleXmlElement $xml_data Объект SimpleXmlElement
         *
         * @return void
         */
        public static function arrayToXmlHelper(array $data, \SimpleXmlElement &$xml_data): void
        {
            foreach ($data as $key => $value) {
                if (is_numeric($key)) {
                    $key = 'element_' . $key;
                }
                if (is_array($value)) {
                    $subnode = $xml_data->addChild($key);
                    static::arrayToXmlHelper($value, $subnode);
                } else {
                    $xml_data->addChild($key, htmlspecialchars($value));
                }
            }
        }

        /**
         * Преобразование массива в XML
         *
         * @param array $arData Массив данных
         *
         * @return void
         * @throws \Exception
         */
        public static function arrayToXml(array $arData = [], ?string $objectName = null): string
        {
            $rootData = '';
            if (!empty($objectName)) {
                $rootData = ' object="' . $objectName . '"';
            }

            $xml = new \SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8"?><DeltaCore version="' . CORE_VERSION . '"' . $rootData . ' date="' . date('d.m.Y H:i:s')
                . '"/>'
            );
            static::arrayToXmlHelper($arData, $xml);

            $dom                     = dom_import_simplexml($xml)->ownerDocument;
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput       = true;
            return $dom->saveXML();
        }

        /**
         * Сравнение текстов
         *
         * @param array $old Старый текст
         * @param array $new Новый текст
         *
         * @return array|array[]
         */
        public static function diffText(array $old, array $new): array
        {
            $matrix = [];
            $maxlen = 0;
            foreach ($old as $oindex => $ovalue) {
                $nkeys = array_keys($new, $ovalue);
                foreach ($nkeys as $nindex) {
                    $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                    if ($matrix[$oindex][$nindex] > $maxlen) {
                        $maxlen = $matrix[$oindex][$nindex];
                        $omax   = $oindex + 1 - $maxlen;
                        $nmax   = $nindex + 1 - $maxlen;
                    }
                }
            }
            if ($maxlen === 0) {
                return [['d' => $old, 'i' => $new]];
            }
            return array_merge(
                self::diffText(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
                array_slice($new, $nmax, $maxlen),
                self::diffText(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
            );
        }

        /**
         * Сравнение текстов и отдача HTML результата
         *
         * @param string $old Старый текст
         * @param string $new Новый текст
         *
         * @return string
         */
        public static function htmlDiffText(string $old, string $new): string
        {
            $result  = '';
            $diff = self::diffText(preg_split('/[\s]+/', $old), preg_split('/[\s]+/', $new));
            foreach ($diff as $k) {
                if (is_array($k)) {
                    $result .= (!empty($k['d']) ? '<del style="background: red;color: white;">' . implode(' ', $k['d']) . '</del> ' : '')
                            . (!empty($k['i']) ? '<ins style="background: #009d22;color: white;">' . implode(' ', $k['i']) . '</ins> ' : '');
                } else {
                    $result .= $k . ' ';
                }
            }
            return $result;
        }

        /**
         * Получение количества ядер процессора площадки
         *
         * @return int Количество ядер
         */
        public static function getCPUCount(): int
        {
            $count = (int)shell_exec('cat /proc/cpuinfo | grep processor | wc -l');
            if ($count === 0) {
                $count = 1;
            }
            return $count;
        }

        /**
         * Парсинг логов nginx
         *
         * @param string      $logPath     Путь до файла логов
         * @param bool        $isOnlyCount Только получение количества
         * @param string|null $limit       Ограничение выборки "0, 10"
         * @param bool        $isReverse   Перевернуть выборку
         *
         * @return array|int
         * @throws CoreException
         */
        public static function getNginxLogData(string $logPath, bool $isOnlyCount = false, ?string $limit = null, bool $isReverse = false)
        {
            if (!file_exists($logPath)) {
                throw new CoreException('Файл логов не найден', CoreException::ERROR_FILE_NOT_FOUND);
            }
            $arLogs = file($logPath);
            $logCount = count($arLogs);
            if ($isOnlyCount) {
                return $logCount;
            }
            if ($isReverse) {
                $arLogs = array_reverse($arLogs);
            }
            $arLimit = [0, $logCount];
            if ($limit !== null) {
                $arLimit['start'] = (int)explode(',', $limit)[0];
                $arLimit['stop']  = $arLimit['start'] + (int)explode(',', $limit)[1];
            }
            $regExp   = '/(?<ip>[0-9.]+)\s+\-\s+\-\s\[(?<date>[^\]]+)\]\s"(?<request>[^"]+)"\s(?<code>\d+)\s+(?<size>\d+)\s"(?<url>[^"]+)"\s"(?<useragent>[^"]+)"/m';
            $arResult = [];
            $result   = null;
            $counter = 0;
            foreach ($arLogs as $logEvent) {
                $counter++;
                if ($limit !== null && ($counter - 1) < $arLimit['start']) {
                    continue;
                }
                preg_match_all($regExp, $logEvent, $result, PREG_SET_ORDER, 0);
                $result     = array_shift($result);
                $arResult[] = [
                    'ip'        => $result['ip'],
                    'date'      => $result['date'],
                    'request'   => $result['request'],
                    'httpCode'  => $result['code'],
                    'size'      => $result['size'],
                    'url'       => $result['url'],
                    'userAgent' => $result['useragent'],
                ];
                $result     = null;
                if ($limit !== null && ($counter + 1) > $arLimit['stop']) {
                    break;
                }
            }
            return $arResult;
        }

        /**
         * Возвращает подсвеченную PHP строку
         *
         * @param string $string Строка кода
         *
         * @return string Подсвеченная строка
         */
        public static function highlightLine(string $string): string
        {
            $string = str_replace(['<?php', '<?'], '', $string);
            $string = htmlspecialchars_decode($string);
            ob_start();
            highlight_string('<?php ' . $string);
            $result = ob_get_clean();
            return str_replace('&lt;?php', '', $result);
        }

        /**
         * Возвращает кусок текстового содержимого с подсвеченной строкой
         *
         * @param string $fileText Текст
         * @param int    $lineNum Номер строки
         *
         * @return string|null
         */
        public static function getFilePreviewByLine(string $fileText, int $lineNum): ?string
        {
            $fileText = explode(PHP_EOL, $fileText);
            $fileText = array_map('htmlspecialchars', $fileText);
            $line = $lineNum - 1;

            if (!isset($fileText[$line])) {
                return null;
            }
            $lineStyle = 'border-bottom: 1px solid black; padding: 5px;';

            $result   = [];

            if ($lineNum - 2 > 0) {
                $result[] = '<div style="' . $lineStyle . '"><i>' . ($lineNum - 2) . '.</i> ' . self::highlightLine($fileText[$line - 2]) . '</div>';
            }

            if ($lineNum - 1 > 0) {
                $result[] = '<div style="' . $lineStyle . '"><i>' . ($lineNum - 1) . '.</i> ' . self::highlightLine($fileText[$line - 1]) . '</div>';
            }

            if ($lineNum > 0 && $lineNum <= count($fileText)) {
                $result[] = '<div style="' . $lineStyle . ' background: #b9ff9c;"><i>' . $lineNum . '.</i> <b>' . self::highlightLine($fileText[$line]) . '</b></div>';
            }

            if ($lineNum + 1 <= count($fileText)) {
                $result[] = '<div style="' . $lineStyle . '"><i>' . ($lineNum + 1) . '.</i> ' . self::highlightLine($fileText[$line + 1]) . '</div>';
            }

            if ($lineNum + 2 <= count($fileText)) {
                $result[] = '<div style="' . $lineStyle . '"><i>' . ($lineNum + 2) . '.</i> ' . self::highlightLine($fileText[$line + 2]) . '</div>';
            }

            $textBefore = '<div style="border:1px solid black">';
            $textAfter = '</div>';

            return $textBefore . implode(PHP_EOL . PHP_EOL, $result) . $textAfter;
        }

        /**
         * Выполняет поиск строки текста в файле и отдает аннотацию
         *
         * @param string $text Текст
         * @param string $file Путь к файлу
         *
         * @return string|null Результат поиска
         */
        public static function findText(string $text, string $file): ?string
        {
            $fileText = file_get_contents($file);
            $file     = explode(PHP_EOL, $fileText);
            foreach ($file as $line => $string) {
                $pos = strripos($string, $text);
                if ($pos !== false) {
                    return self::getFilePreviewByLine($fileText, $line + 1);
                }
            }

            return null;
        }

        /**
         * Преобразование строки в camelCase
         * Применение: преобразование названий столбцов таблицы в camelCase стиль
         *
         * @param string $string Строка вида "FIELD_ID"
         *
         * @return string Результат вида "fieldId"
         */
        public static function stringToCamelCase(string $string): string
        {
            $explodedString = explode('_', strtolower($string));
            foreach ($explodedString as $index => $value) {
                if ($index === 0) {
                    continue;
                }
                $explodedString[$index] = ucfirst($value);
            }
            return implode('', $explodedString);
        }

        /**
         * Генерация кода для каптчи
         *
         * TODO: реализовать схожий с генерацией пароля механизм
         *
         * @param int $min Минимальное количество символов
         * @param int $max Максимальное количество символов
         *
         * @return void
         */
        public static function generateCode(int $min = 4, int $max = 8): string
        {
            $chars  = 'ABCDEFGHJKLMNPRSTVXYZ23456789';
            $length = rand($min, $max);
            $numChars = strlen($chars);
            $str = '';
            for ($i = 0; $i < $length; $i++) {
                $str .= substr($chars, rand(1, $numChars) - 1, 1);
            }

            // Перемешиваем, на всякий случай
            $array_mix = preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
            srand ((float)microtime()*1000000);
            shuffle ($array_mix);

            return implode('', $array_mix);
        }

        /**
         * Получение информации о памяти сервера
         *
         * @return Memory DTO использования памяти
         */
        public static function getMemoryUse(): Memory
        {
            $memoryObject = new Memory();
            $memoryTotal  = null;
            $memoryFree   = null;
            if (!is_readable('/proc/meminfo')) {
                return $memoryObject;
            }
            $stats = @file_get_contents('/proc/meminfo');
            if ($stats === false) {
                return $memoryObject;
            }

            $stats = str_replace("\r", '', $stats);
            $stats = explode(PHP_EOL, $stats);
            foreach ($stats as $statLine) {
                $statLineData = explode(':', trim($statLine));

                // Всего
                if (count($statLineData) == 2 && trim($statLineData[0]) == 'MemTotal') {
                    $memoryTotal = trim($statLineData[1]);
                    $memoryTotal = explode(' ', $memoryTotal);
                    $memoryTotal = $memoryTotal[0];
                    $memoryTotal *= 1024;
                }

                // Свободно
                if (count($statLineData) == 2 && trim($statLineData[0]) == 'MemFree') {
                    $memoryFree = trim($statLineData[1]);
                    $memoryFree = explode(' ', $memoryFree);
                    $memoryFree = $memoryFree[0];
                    $memoryFree *= 1024;
                }
            }

            $memoryObject->total       = $memoryTotal;
            $memoryObject->free        = $memoryFree;
            $memoryObject->used        = $memoryTotal - $memoryFree;
            $memoryObject->usedPercent = 100 - ($memoryFree * 100 / $memoryTotal);

            return $memoryObject;
        }

        /**
         * Отправка уведомления о критическом событии
         *
         * @param string $text Текст алярма
         *
         * @return void
         */
        public static function sendAlarm(string $title, string $text): void
        {
            $userList = User::getListByRole(Roles::ALARM_ROLE_ID);

            foreach($userList as $userObject) {
                (new Mail())->setFrom(SERVER_EMAIL, SERVER_EMAIL_NAME)
                            ->setTemplate(MAIL_TEMPLATE_DEFAULT)
                            ->setTo($userObject->getEmail(), $userObject->getName())
                            ->setSubject('Alarm: ' . $title)
                            ->setBody($text)
                            ->send();
            }
            if (function_exists('sendTelegram')) {
                sendTelegram('<b>' . $title . '</b>' . PHP_EOL. $text);
            }
        }
    }
