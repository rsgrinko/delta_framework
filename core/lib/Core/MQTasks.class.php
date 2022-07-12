<?php

    namespace Core;

    use Core\Helpers\Cache;
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
        public static function resizeImage(string $filename, int $width, int $height)
        {
            $saveImage = $_SERVER['DOCUMENT_ROOT'] . '/uploads/images/resized/' . $filename;
            $image     = new Thumbs($_SERVER['DOCUMENT_ROOT'] . '/uploads/images/original/' . $filename);
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
            if (empty($posts)) {
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

            $i = 0;
            $j = 0;
            foreach ($result as $element) {
                $text = str_replace('<br />', PHP_EOL, $element['post']);
                $text = htmlspecialchars_decode($text);
                $text = str_replace('\'', '\\\'', $text);
                $hash = md5($text);
                $res  = $DB->query('SELECT * FROM bashorg WHERE hash="' . $hash . '" AND date is null');
                if ($res) {
                    $j++;
                    $DB->update(
                        'bashorg',
                        ['hash' => $hash],
                        ['ext_id' => (int)$element['id'], 'date' => $element['date'], 'rating' => $element['rating']]
                    );
                }
            }
            //sleep(5);
            return 'bashMeta: Добавлено ' . $i . ', обновлено ' . $j . ' записей из ' . count($result);
        }

        public static function anekdot()
        {
            /** @var DB $DB */
            $DB = DB::getInstance();

            $file    = file_get_contents('https://www.anekdot.ru/rss/randomu.html');
            $file    = explode('JSON.parse(\'', $file)[1];
            $file    = explode('\');', $file)[0];
            $file    = stripcslashes($file);
            $arJokes = json_decode($file);

            if (empty($arJokes)) {
                throw new CoreException('Ошибка получения данных с сайта');
            }
            $i = 0;
            foreach ($arJokes as $joke) {
                $hash = md5($joke);
                $joke = str_replace('<br>', PHP_EOL, $joke);
                $joke = addslashes($joke);
                $res  = $DB->query('SELECT * FROM jokes WHERE hash="' . $hash . '"');
                if (!$res) {
                    $i++;
                    $itemId = $DB->addItem('jokes', ['hash' => $hash, 'text' => $joke]);
                }
            }
            return 'Добавлено ' . $i . ' шуток из ' . count($arJokes);
        }

        public static function anekdot2()
        {
            $result = 'Ничего нового не добавлено';
            /** @var DB $DB */
            $DB = DB::getInstance();

            $file = file_get_contents('http://rzhunemogu.ru/RandJSON.aspx?CType=11');
            $file = \iconv('windows-1251//IGNORE', 'UTF-8//IGNORE', $file);
            $file = str_replace('{"content":"', '', $file);
            $file = str_replace('"}', '', $file);

            if (empty($file)) {
                throw new CoreException('Ошибка получения данных с сайта');
            }

            $hash = md5($file);
            $file = addslashes($file);
            $res  = $DB->query('SELECT * FROM jokes WHERE hash="' . $hash . '"');
            if (!$res) {
                $itemId = $DB->addItem('jokes', ['hash' => $hash, 'text' => $file]);
                $result = 'Добавлен новый элемент с ID ' . $itemId;
            }

            return $result;
        }


        public static function clearDuplicates()
        {
            /** @var DB $DB */
            $DB = DB::getInstance();
            $DB->query(
                'CREATE TEMPORARY TABLE `t_temp` AS  (SELECT min(id) as id FROM `jokes` GROUP BY hash );
DELETE from `jokes` WHERE `jokes`.id not in (SELECT id FROM t_temp);'
            );
            return 'Дубликаты удалены';
        }


        public static function sendJokeToTelegram()
        {
            if(date('H') > 21 || date('H') < 9) {
                return 'Нерабочее время :(';
            }
            $telegram = new \Core\ExternalServices\TelegramSender(TELEGRAM_BOT_TOKEN);
            $telegram->setChat(' -1001610334197');

            /** @var DB $DB */
            $DB = DB::getInstance();

            $joke = $DB->getItem('jokes', ['sended' => 'N']);
            $joke = $DB->query('select * from jokes where sended="N" order by id desc')[0];
            $telegram->sendMessage('<b>Шутейка #' . $joke['id'] . '</b>' . PHP_EOL . $joke['text']);
            $DB->update('jokes', ['id' => $joke['id']], ['sended' => 'Y']);
            return 'Шутка ' . $joke['id'] . ' отправлена в канал';
        }


        public static function getMySLO()
        {
            $cacheId = md5('getMySLO_criminal');
            if(Cache::check($cacheId)) {
                $oldLink = Cache::get($cacheId);
            } else {
                $oldLink = '';
            }

            $file = file_get_contents('https://myslo.ru/news/criminal');
            $file = preg_match_all('/"item width-100-tiny">(.*?)<div class="clear">/us', $file, $file2);
            $file2 = $file2[1][0];

            preg_match_all('/<h1 class="h3">(.*?)<\/h1>/us', $file2, $title);
            $title = trim(strip_tags($title[1][0]));

            preg_match_all('/src="(.*?)">/us', $file2, $image);
            $image = trim($image[1][0]);

            preg_match_all('/" href="(.*?)">/us', $file2, $link);
            $link = 'https://myslo.ru' . trim($link[1][0]);



            if($oldLink !== $link) {
                $full = file_get_contents($link);

                preg_match_all('/<div class="h3 lid"><p>(.*?)<\/p><\/div>/us', $full, $lidArticle);
                $lidArticle = html_entity_decode($lidArticle[1][0]);

                preg_match_all('/class="myslo_insert"  >(.*?)<a class="media/us', $full, $fullArticle);
                $fullArticle = html_entity_decode($fullArticle[1][0]);

                $fullArticle = str_replace("\r", '', $fullArticle);
                $fullArticle = str_replace("\n\n", '', $fullArticle);
                $fullArticle = trim($fullArticle);
                $fullArticle = preg_replace('/\s+/', ' ', $fullArticle);

                if (empty($fullArticle)) {
                    throw new CoreException('Не удалось получить данные');
                }

                $tmpFile = CACHE_DIR . '/' . basename($image);
                file_put_contents($tmpFile, file_get_contents($image));

                $lidArticle  = SystemFunctions::previewText($lidArticle, 100);
                $fullArticle = SystemFunctions::previewText($fullArticle, 300);

                $post = '<b>' . $title . '</b>' . PHP_EOL . $lidArticle . PHP_EOL . $fullArticle;
                $post .= PHP_EOL . '<a href="' . $link . '">Подробнее</a>';

                $telegram = new \Core\ExternalServices\TelegramSender(TELEGRAM_BOT_TOKEN);
                $telegram->setChat(TELEGRAM_NOTIFICATION_CHANNEL);
                $res = $telegram->sendPhoto($tmpFile, $post);
                @unlink($tmpFile);
                Cache::set($cacheId, $link);
                return $res;
            } else {
                return 'Обновление не требуется';
            }

            return $res;
        }
    }