<?php

    namespace Core;

    use Core\Helpers\SystemFunctions;
    use Core\Helpers\Thumbs;
    use Core\ExternalServices\TelegramSender;
    use Core\Models\DB;

    class MQTasks
    {
        public static function testSendTelegram()
        {
            SystemFunctions::sendTelegram('test task was completed');
        }

        /**
         * @throws CoreException
         */
        public static function resizeImage(string $filename, int $width, int $height) {
            $saveImage = $_SERVER['DOCUMENT_ROOT'] . '/uploads/images/resized/' . $filename;
            $image = new Thumbs($_SERVER['DOCUMENT_ROOT'] . '/uploads/images/original/' . $filename);
            $image->thumb($width, $height);
            $image->watermark($_SERVER['DOCUMENT_ROOT'] . '/uploads/images/w.png', 'bottom-right', 70);
            $image->save($saveImage);

            return $saveImage;
        }

        public static function sendBash(...$args): ?string
        {
            /** @var DB $DB */
            $DB = DB::getInstance();

            $file = file_get_contents('http://bashorg.org/random');
            $file = preg_match_all('|<div class="quote">(.+)</div>|U', $file, $frazes);

            $frazes = $frazes[1];
            $frazes = array_map(function ($e) {
                return \iconv('windows-1251//IGNORE', 'UTF-8//IGNORE', $e);
            }, $frazes);

            $i = 0;

            foreach ($frazes as $post) {
               //sendTelegram($post);
                $text = str_replace('<br />', PHP_EOL, $post);
                $text = htmlspecialchars_decode($text);
                $text = str_replace('\'', '\\\'', $text);
                $hash = md5($text);
                $res  = $DB->query('SELECT * FROM bashorg WHERE hash="' . $hash . '"');
                if (!$res) {
                    $i++;
                    $DB->addItem('bashorg', ['hash' => $hash, 'text' => $text]);
                }
            }
            sleep(2);
            return 'Добавлено ' . $i . ' записей из ' . count($frazes);
        }

        public static function bashPage($page)
        {
            /** @var DB $DB */
            $DB = DB::getInstance();

            $file = file_get_contents('http://bashorg.org/page/' . $page . '/');
            $file = preg_match_all('|<div class="quote">(.+)</div>|U', $file, $frazes);

            $frazes = $frazes[1];
            $frazes = array_map(function ($e) {
                return \iconv('windows-1251//IGNORE', 'UTF-8//IGNORE', $e);
            }, $frazes);

            $i =0;
            foreach ($frazes as $post) {
                //sendTelegram($post);
                $text = str_replace('<br />', PHP_EOL, $post);
                $text = htmlspecialchars_decode($text);
                $text = str_replace('\'', '\\\'', $text);
                $hash = md5($text);
                $res  = $DB->query('SELECT * FROM bashorg WHERE hash="' . $hash . '"');
                if (!$res) {
                    $i++;
                    $itemId = $DB->addItem('bashorg', ['hash' => $hash, 'text' => $text]);
                    sendTelegram('<b>Добавлена цитата ID ' . $itemId . ' (' . $page . ' страница)</b>' . PHP_EOL . $text);
                }
            }
            sleep(2);
            return 'bashPage: Добавлено ' . $i . ' записей из ' . count($frazes);
        }

        public static function bashPageMeta($page)
        {
            /** @var DB $DB */
            $DB = DB::getInstance();

            $file = file_get_contents('http://bashorg.org/page/' . $page . '/');
            $file = \iconv('windows-1251//IGNORE', 'UTF-8//IGNORE', $file);
            preg_match_all('|(?<=<div\ class="q">)[\w\W]*?(?=<hr)|U', $file, $frazes);
            $frazes = $frazes[0][0];
            // тут готовый массив блоков с постами

            preg_match_all('|<div class="quote">(.+)</div>|U', $frazes, $posts);
            $posts = $posts[1];
            if(empty($posts)) {
                throw new CoreException('Ошибка обработки данных');
            }

            preg_match_all('|Цитата #(.+)</a>|U', $frazes, $ids);
            $ids = $ids[1];

            preg_match_all('|</a> \| (.+) \| добавлено|U', $frazes, $dates);
            $dates = $dates[1];

            preg_match_all('|<span id=\'result-(.+)\'>(.+)</span>|U', $frazes, $ratings);
            $ratings = $ratings[2];

            $result = [];
            foreach ($posts as $key => $post) {
                $result[] = [
                    'post'   => $post,
                    'date'   => $dates[$key],
                    'rating' => $ratings[$key],
                    'id'     => $ids[$key],
                ];
            }

            $i =0;
            $j = 0;
            foreach ($result as $element) {
                $text = str_replace('<br />', PHP_EOL, $element['post']);
                $text = htmlspecialchars_decode($text);
                $text = str_replace('\'', '\\\'', $text);
                $hash = md5($text);
                $res  = $DB->query('SELECT * FROM bashorg WHERE hash="' . $hash . '"');
                if (!$res) {
                    $i++;
                    $itemId = $DB->addItem('bashorg', ['hash' => $hash, 'text' => $text, 'etx_id' => (int)$element['id'], 'date' => $element['date'], 'rating' => $element['rating']]);
                    sendTelegram('<b>Добавлена цитата ID ' . $itemId . ' (' . $page . ' страница)</b>' . PHP_EOL . $text);
                } else {
                    $j++;
                    $DB->update('bashorg', ['hash' => $hash], ['ext_id' => (int)$element['id'], 'date' => $element['date'], 'rating' => $element['rating']]);
                }
            }
            //sleep(5);
            return 'bashMeta: Добавлено ' . $i . ', обновлено ' . $j . ' записей из ' . count($result);
        }

        public static function anekdot()
        {
            /** @var DB $DB */
            $DB = DB::getInstance();

            $file = file_get_contents('https://www.anekdot.ru/rss/randomu.html');
            $file = explode('JSON.parse(\'', $file)[1];
            $file = explode('\');', $file)[0];
            $file = stripcslashes($file);
            $arJokes = json_decode($file);

            if(empty($arJokes)) {
                throw new CoreException('Ошибка получения данных с сайта');
            }
            $i = 0;
            foreach($arJokes as $joke) {
                $hash = md5($joke);
                $joke = str_replace('<br>', PHP_EOL, $joke);
                $joke = addslashes($joke);
                $res  = $DB->query('SELECT * FROM jokes WHERE hash="' . $hash . '"');
                if (!$res) {
                    $i++;
                    $itemId = $DB->addItem('jokes', ['hash' => $hash, 'text' => $joke]);
                    //sendTelegram('<b>Добавлена шутка ID ' . $itemId . '</b>' . PHP_EOL . $joke);
                }
            }



            //sleep(2);
            return 'Добавлено ' . $i . ' шуток из ' . count($arJokes);
        }
    }