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

    namespace Core;

    use Core\Helpers\Cache;
    use Core\Helpers\SystemFunctions;
    use Core\Helpers\Thumbs;
    use Core\ExternalServices\TelegramSender;
    use Core\Models\DB;
    use Core\Models\MQ;

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
            if ($i < 1) {
                throw new CoreException('Не найдено новых данных для добавления');
            }
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
                        'bashorg', ['hash' => $hash], ['ext_id' => (int)$element['id'], 'date' => $element['date'], 'rating' => $element['rating']]
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
            if($i < 1 ) {
                throw new CoreException('Новых данных не найдено');
            }

            return ['method' => __FUNCTION__, 'status' => 'OK', 'message' => 'Добавлено ' . $i . ' шуток из ' . count($arJokes)];
        }

        public static function anekdot2()
        {
            //$result = 'Ничего нового не добавлено';
            $result = null;
                /** @var DB $DB */
            $DB = DB::getInstance();

            /**
             * CType = ?
             * 1 - Анекдот;
             * 2 - Рассказы;
             * 3 - Стишки;
             * 4 - Афоризмы;
             * 5 - Цитаты;
             * 6 - Тосты;
             * 8 - Статусы;
             * 11 - Анекдот (+18);
             * 12 - Рассказы (+18);
             * 13 - Стишки (+18);
             * 14 - Афоризмы (+18);
             * 15 - Цитаты (+18);
             * 16 - Тосты (+18);
             * 18 - Статусы (+18);
             */
            $file = file_get_contents('http://rzhunemogu.ru/RandJSON.aspx?CType=1');
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

            if (empty($result)) {
                throw new CoreException('Ничего нового не добавлено');
            }
            return ['method' => __FUNCTION__, 'status' => 'OK', 'message' => $result];
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
            if (date('H') > 21 || date('H') < 9) {
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
            $result = 'Нет данных для обновления';
            /** @var DB $DB */
            $DB = DB::getInstance();


            $file  = file_get_contents('https://myslo.ru/news/criminal');
            $file  = preg_match_all('/"item width-100-tiny">(.*?)<div class="clear">/us', $file, $file2);
            $file2 = $file2[1][0];

            preg_match_all('/<h1 class="h3">(.*?)<\/h1>/us', $file2, $title);
            $title = trim(strip_tags($title[1][0]));

            preg_match_all('/src="(.*?)">/us', $file2, $image);
            $image = trim($image[1][0]);

            preg_match_all('/" href="(.*?)">/us', $file2, $link);
            $link = 'https://myslo.ru' . trim($link[1][0]);

            $hash = md5($link);
            $res  = $DB->query('SELECT * FROM myslo WHERE hash="' . $hash . '"');
            if (!$res) {

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

                $lidArticlePost  = SystemFunctions::previewText($lidArticle, 100);
                $fullArticlePost = SystemFunctions::previewText($fullArticle, 250);
                $fullArticlePost = strip_tags($fullArticlePost);

                $post = '<b>' . $title . '</b>' . PHP_EOL . $lidArticlePost . PHP_EOL . $fullArticlePost;
                $post .= PHP_EOL . '<a href="' . $link . '">Подробнее</a>';


                $telegram = new \Core\ExternalServices\TelegramSender(TELEGRAM_BOT_TOKEN);
                $telegram->setChat('-1001789206618');
                $res = $telegram->sendPhoto($tmpFile, $post);
                sendTelegram($post);
                @unlink($tmpFile);
                $itemId = $DB->addItem('myslo', ['hash' => $hash, 'title' => $title, 'image' => $image, 'text' => $lidArticle . PHP_EOL . $fullArticle, 'link' => $link]);

                return $res;//'Добавлен новый элемент с ID ' . $itemId;
            } else {
                return 'Обновление не требуется';
            }
        }

        public static function test(string $cmd, array $params = [])
        {
            $res = file_get_contents('https://dev.it-stories.ru/test.php?cmd=' . $cmd . '&' . http_build_query($params));
            return $res;
        }

        public static function getUser()
        {
            //sleep(10);
            $res = file_get_contents('https://randomuser.me/api/');
            $res = json_decode($res, true, 512, JSON_THROW_ON_ERROR);
            $result = [
                'gender' => $res['results'][0]['gender'],
                'name'   => $res['results'][0]['name'],
                'email'  => $res['results'][0]['email'],
                'login'  => $res['results'][0]['login'],
            ];
            //sendTelegram(print_r($res['results'][0]['login'], true));
            return $result;
        }

        public static function getSolImages(?int $sol = null)
        {
            set_time_limit(0);
            $result = [];
            $needGet = true;
            if ($sol === null) {
                $sol = 1;
            }
            $page = 1;

            while ($needGet) {
                $res = file_get_contents(
                    'https://api.nasa.gov/mars-photos/api/v1/rovers/curiosity/photos?sol=' . $sol . '&page=' . $page . '&api_key=3vGS2dOB7zd9Jyyrj3dNU0cTkfU4YRRh4M8SK6jl'
                );//5qRQx4su8if3zVeweadnbegxcs6gxG9pgRtB1Tmu');
                $res = json_decode($res, true);
                if (isset($res['photos']) && !empty($res['photos'])) {
                    foreach ($res['photos'] as $arImage) {
                        $result[] = [
                            'date' => $arImage['earth_date'],
                            'src'  => $arImage['img_src'],
                        ];
                    }
                    $page++;
                } else {
                    $needGet = false;
                }
            }
            return $result;
        }

        public static function saveSolImages($sol)
        {
            $counter = 0;
            $files   = [];
            $res     = self::getSolImages($sol);
            sleep(5);

            if (empty($res)) {
                throw new CoreException('Изображений с ровера за ' . $sol . ' сол нет');
            }
            foreach ($res as $arImage) {
                $folder = 'ftp://admin:j2medit@10.8.0.2/mars/' . $arImage['date'];
                if (!file_exists($folder)) {
                    if (!mkdir($folder) && !is_dir($folder)) {
                        //throw new CoreException('Папку "' . $folder . '" не удалось создать');
                    }
                }
                if (copy($arImage['src'], $folder . '/' . basename($arImage['src']))) {
                    $counter++;
                    $files[] = basename($arImage['src']);
                    //throw new CoreException('Не удалось сохранить файл  "' . $folder . '/' . basename($arImage['src']) . '" не удалось создать');
                }
            }
            return ['count' => $counter, 'files' => $files];
        }


        public static function getStory($from = null)
        {
            $tableName = 'podslyshano';
            $link = 'https://podslyshano.com/api/v3.5/posts';
            if (!empty($from) && $from !== '') {
                $link .= '?from=' . $from;
            }
            $response = file_get_contents($link);
            if (empty($response)) {
                throw new CoreException('Не удалось получить содержимое');
            }

            try {
                $response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            } catch(\Throwable $e) {
                throw new CoreException($e->getMessage());
            }

            if (empty($response['posts'])) {
                throw new CoreException('Постов по данной выборке не найдено');
            }

            $result = [];
            $lastId = null;
            /** @var DB $DB */
            $DB = DB::getInstance();
            foreach ($response['posts'] as $post) {
                if ($post['type'] !== 'post') {
                    continue;
                }
                if ($DB->getItem($tableName, ['post_id' => (int)$post['id']])) {
                    continue;
                }
                $DB->query('SET NAMES \'utf8mb4\'');
                $result[] = $DB->addItem(
                    $tableName, [
                                     'type'          => $post['type'],
                                     'post_id'       => (int)$post['id'],
                                     'category_id'   => (int)$post['category_id'],
                                     'category_name' => $post['category_name'],
                                     'category_slug' => $post['category_slug'],
                                     'likes_count'   => (int)$post['likes_count'],
                                     'created_at'    => $post['created_at'],
                                     'image_uri'     => $post['image_uri'],
                                     'note'          => addslashes($post['note']),
                                 ]
                );
                $lastId = (int)$post['id'];
            }

            if ($lastId !== null) {
                exec('(php -f ' . $_SERVER['DOCUMENT_ROOT'] . '/core/runtime/podslyshano.php "' . $lastId . '" & ) >> /dev/null 2>&1');
            }

            return 'Добавлено постов: ' . count($result);
        }

    }